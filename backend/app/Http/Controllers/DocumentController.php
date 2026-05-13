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
     * ${DOC_NUMBER}          — Hujjat raqami (masalan: АТ/125)
     * ${DATE}                — Sana (masalan: 13 may 2026 yil)
     * ${RECIPIENT_ORG}       — Tashkilot nomi
     * ${RECIPIENT_POSITION}  — Rahbar lavozimi (faqat yuqori turuvchida)
     * ${RECIPIENT_NAME}      — Rahbar FISH (faqat yuqori turuvchida)
     * ${GREETING}            — "Hurmatli, [FISH]!" (faqat yuqori turuvchida)
     * ${SIGNATORY_POSITION}  — Imzolovchi lavozimi
     * ${SIGNATORY_NAME}      — Imzolovchi FISH
     * ${EXECUTOR_NAME}       — Ijrochi (bo'lim rahbari FISH)
     * ${EXECUTOR_PHONE}      — Ichki telefon
     * ${TEXT}                — Hujjat asosiy matni
     */

    public function generate(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'department_id'          => 'required|exists:departments,id',
            'organization_id'        => 'required|exists:organizations,id',
            'organization_leader_id' => 'nullable|exists:organization_leaders,id',
            'signatory_id'           => 'required|exists:signatories,id',
            'document_number'        => 'required|string|max:50',
            'document_date'          => 'required|date',
            'text_content'           => 'required|string',
            'template_id'            => 'nullable|exists:document_templates,id',
        ]);

        $department = Department::findOrFail($data['department_id']);
        $organization = Organization::findOrFail($data['organization_id']);
        $signatory = Signatory::findOrFail($data['signatory_id']);
        $leader = isset($data['organization_leader_id'])
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

        // Matnni Gemini bilan tuzatish
        $correctedText = $this->correctWithGemini($data['text_content']);

        // Placeholder qiymatlar
        $fullDocNumber = $department->index_code . $data['document_number'];
        $formattedDate = $this->formatDateUzbek($data['document_date']);

        $values = [
            'DOC_NUMBER'          => $fullDocNumber,
            'DATE'                => $formattedDate,
            'RECIPIENT_ORG'       => $organization->name,
            'RECIPIENT_POSITION'  => $leader?->position ?? '',
            'RECIPIENT_NAME'      => $leader?->full_name ?? '',
            'GREETING'            => $leader ? 'Hurmatli, ' . $leader->full_name . '!' : '',
            'SIGNATORY_POSITION'  => $signatory->position,
            'SIGNATORY_NAME'      => $signatory->full_name,
            'EXECUTOR_NAME'       => $department->head_name,
            'EXECUTOR_PHONE'      => $department->head_phone ?? '',
            'TEXT'                => $correctedText,
        ];

        // Vaqtinchalik fayl yaratish
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';
        copy($templatePath, $tempFile);

        $processor = new TemplateProcessor($tempFile);

        foreach ($values as $key => $value) {
            // Ko'p qatorli matn uchun Word line break
            $value = str_replace(
                ["\r\n", "\r", "\n"],
                '</w:t><w:br/><w:t xml:space="preserve">',
                $value
            );
            $processor->setValue($key, $value);
        }

        $processor->saveAs($tempFile);

        $filename = 'hujjat_' . preg_replace('/[\/\\\\]/', '-', $fullDocNumber) . '_' . date('Ymd') . '.docx';

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
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
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$key}",
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

    private function formatDateUzbek(string $date): string
    {
        $months = [
            1 => 'yanvar', 2 => 'fevral', 3 => 'mart',
            4 => 'aprel', 5 => 'may', 6 => 'iyun',
            7 => 'iyul', 8 => 'avgust', 9 => 'sentabr',
            10 => 'oktabr', 11 => 'noyabr', 12 => 'dekabr',
        ];
        $ts = strtotime($date);

        return date('d', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts) . ' yil';
    }
}
