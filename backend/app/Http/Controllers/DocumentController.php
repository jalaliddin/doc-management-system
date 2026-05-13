<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\Organization;
use App\Models\OrganizationLeader;
use App\Models\Signatory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /*
     * Shablonda ishlatiladigan placeholder lar:
     *
     * ${DOC_NUMBER}          — Hujjat indeksi (masalan: АТ/)
     * ${DATE}                — Sana (masalan: 13.05.2026-yil)
     * ${RECIPIENT_ORG}       — Tashkilot nomi
     * ${RECIPIENT_POSITION}  — Rahbar lavozimi
     * ${RECIPIENT_NAME}      — Rahbar qisqartma FISH (masalan: A.A. Hamidov)
     * ${GREETING}            — Hurmatli, Ism Otasining-ismi! (masalan: Hurmatli, Akmal Anvarovich!)
     * ${SIGNATORY_POSITION}  — Imzolovchi lavozimi
     * ${SIGNATORY_NAME}      — Imzolovchi FISH
     * ${EXECUTOR_NAME}       — Ijrochi (bo'lim rahbari FISH)
     * ${EXECUTOR_PHONE}      — Ichki telefon
     * ${TEXT}                — Hujjat asosiy matni
     * ${MANUAL_ORG}          — Qo'lda: boshqarma nomi
     * ${MANUAL_POSITION}     — Qo'lda: rahbar lavozimi
     * ${MANUAL_NAME}         — Qo'lda: rahbar qisqartma FISH
     */

    public function generate(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'department_id'          => 'required|exists:departments,id',
            'organization_id'        => 'required|exists:organizations,id',
            'organization_leader_id' => 'nullable|exists:organization_leaders,id',
            'recipient_position'     => 'nullable|string|max:255',
            'recipient_name'         => 'nullable|string|max:255',
            'signatory_id'           => 'required|exists:signatories,id',
            'template_id'            => 'nullable|exists:document_templates,id',
            'document_date'          => 'required|date',
            'text_content'           => 'required|string',
            'manual_org'             => 'nullable|string|max:255',
            'manual_position'        => 'nullable|string|max:255',
            'manual_name'            => 'nullable|string|max:255',
        ]);

        $department  = Department::findOrFail($data['department_id']);
        $organization = Organization::findOrFail($data['organization_id']);
        $signatory   = Signatory::findOrFail($data['signatory_id']);
        $leader      = isset($data['organization_leader_id'])
            ? OrganizationLeader::find($data['organization_leader_id'])
            : null;

        // Shablon tanlash
        $template = isset($data['template_id'])
            ? DocumentTemplate::findOrFail($data['template_id'])
            : DocumentTemplate::where('is_active', true)->first();

        if (! $template) {
            abort(422, 'Faol shablon topilmadi. Iltimos admin panelda shablon yuklang.');
        }

        $templatePath = Storage::disk('local')->path($template->file_path);

        if (! file_exists($templatePath)) {
            abort(422, 'Shablon fayli topilmadi. Qaytadan yuklang.');
        }

        // Qabul qiluvchi ma'lumotlari
        $recipientPosition = $leader?->position ?? '';
        $recipientName     = $leader ? $this->abbreviateName($leader->full_name) : '';
        // Greeting faqat yuqori turuvchi tashkilotlarda
        $greeting = ($leader && $organization->type === 'yuqori')
            ? 'Hurmatli, ' . $this->getGreetingName($leader->full_name) . '!'
            : '';

        // Qo'lda yoziladigan blok
        $manualFullName = $data['manual_name'] ?? '';
        $manualName     = $manualFullName ? $this->abbreviateName($manualFullName) : '';

        // Matnni Gemini bilan tuzatish
        $correctedText = $this->correctWithGemini($data['text_content']);

        $formattedDate = $this->formatDateUzbek($data['document_date']);

        $values = [
            'DOC_NUMBER'          => $department->index_code,
            'DATE'                => $formattedDate,
            'RECIPIENT_ORG'       => $organization->name,
            'RECIPIENT_POSITION'  => $recipientPosition,
            'RECIPIENT_NAME'      => $recipientName,
            'GREETING'            => $greeting,
            'SIGNATORY_POSITION'  => $signatory->position,
            'SIGNATORY_NAME'      => $signatory->full_name,
            'EXECUTOR_NAME'       => $department->head_name,
            'EXECUTOR_PHONE'      => $department->head_phone ?? '',
            'TEXT'                => $correctedText,
            'MANUAL_ORG'          => $data['manual_org'] ?? '',
            'MANUAL_POSITION'     => $data['manual_position'] ?? '',
            'MANUAL_NAME'         => $manualName,
        ];

        // Vaqtinchalik fayl yaratish
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';
        copy($templatePath, $tempFile);

        $processor = new TemplateProcessor($tempFile);

        foreach ($values as $key => $value) {
            $value = str_replace(
                ["\r\n", "\r", "\n"],
                '</w:t><w:br/><w:t xml:space="preserve">',
                $value
            );
            $processor->setValue($key, $value);
        }

        $processor->saveAs($tempFile);

        $filename = 'hujjat_' . preg_replace('/[\/\\\\]/', '-', $department->index_code) . date('Ymd') . '.docx';

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    // "Familiya Ism Otasining-ismi" -> "I.O. Familiya"
    private function abbreviateName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));
        if (count($parts) < 2) return $fullName;

        $lastName       = $parts[0];
        $firstInitial   = mb_strtoupper(mb_substr($parts[1], 0, 1)) . '.';
        $patronymicInit = isset($parts[2]) ? mb_strtoupper(mb_substr($parts[2], 0, 1)) . '.' : '';

        return $firstInitial . $patronymicInit . ' ' . $lastName;
    }

    // "Familiya Ism Otasining-ismi" -> "Ism Otasining-ismi" (greeting uchun)
    private function getGreetingName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));
        if (count($parts) < 2) return $fullName;

        $firstName  = $parts[1];
        $patronymic = $parts[2] ?? '';

        return trim($firstName . ' ' . $patronymic);
    }

    private function correctWithGemini(string $text): string
    {
        $key = config('services.gemini.api_key');

        if (empty($key)) {
            return $text;
        }

        try {
            $prompt = "Quyidagi o'zbek tilidagi rasmiy hujjat matnini FAQAT grammatik va imlo xatolarini tuzat. "
                . "Matnning ma'nosini, tuzilishini, so'z tartibini O'ZGARTIRMA. "
                . "Faqat tuzatilgan matnni qaytargin, boshqa hech narsani yozma.\n\nMatn: {$text}";

            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$key}",
                ['contents' => [['parts' => [['text' => $prompt]]]]]
            );

            if ($response->successful()) {
                $corrected = $response->json('candidates.0.content.parts.0.text');
                if ($corrected) {
                    return trim($corrected);
                }
            }
        } catch (\Exception) {
            // Gemini ishlamasa asl matnni qaytarish
        }

        return $text;
    }

    // "13.05.2026-yil" formatida sana
    private function formatDateUzbek(string $date): string
    {
        return date('d.m.Y', strtotime($date)) . '-yil';
    }
}
