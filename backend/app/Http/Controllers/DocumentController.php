<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Organization;
use App\Models\OrganizationLeader;
use App\Models\Signatory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function generate(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'organization_id' => 'required|exists:organizations,id',
            'organization_leader_id' => 'nullable|exists:organization_leaders,id',
            'signatory_id' => 'required|exists:signatories,id',
            'document_number' => 'required|string|max:50',
            'document_date' => 'required|date',
            'text_content' => 'required|string',
            'gemini_api_key' => 'nullable|string',
        ]);

        $department = Department::findOrFail($data['department_id']);
        $organization = Organization::findOrFail($data['organization_id']);
        $signatory = Signatory::findOrFail($data['signatory_id']);
        $leader = isset($data['organization_leader_id'])
            ? OrganizationLeader::find($data['organization_leader_id'])
            : null;

        $correctedText = $this->correctTextWithGemini(
            $data['text_content'],
            $data['gemini_api_key'] ?? null
        );

        $fullDocNumber = $department->index_code . $data['document_number'];
        $formattedDate = $this->formatDateUzbek($data['document_date']);

        $docx = $this->buildWordDocument(
            $department,
            $organization,
            $leader,
            $signatory,
            $fullDocNumber,
            $formattedDate,
            $correctedText
        );

        $filename = 'hujjat_' . $fullDocNumber . '_' . date('Ymd') . '.docx';
        $filename = preg_replace('/[\/\\\\]/', '-', $filename);

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_');

        $writer = IOFactory::createWriter($docx, 'Word2007');
        $writer->save($tempFile);

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            unlink($tempFile);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function correctTextWithGemini(string $text, ?string $apiKey): string
    {
        $key = $apiKey ?: config('services.gemini.api_key');

        if (empty($key)) {
            return $text;
        }

        try {
            $prompt = "Quyidagi o'zbek tilidagi rasmiy hujjat matnini FAQAT grammatik va imlo xatolarini tuzat. "
                . "Matnning ma'nosini, tuzilishini, so'z tartibini O'ZGARTIRMA. "
                . "Faqat xato so'zlarni to'g'irla. Boshqa hech narsa qo'shma yoki o'chirma. "
                . "Faqat tuzatilgan matnni qaytargin, boshqa hech narsani yozma.\n\nMatn: {$text}";

            $response = Http::timeout(15)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$key}",
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                ]
            );

            if ($response->successful()) {
                $result = $response->json();
                $corrected = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($corrected) {
                    return trim($corrected);
                }
            }
        } catch (\Exception $e) {
            // Gemini API ishlamasa asl matnni qaytarish
        }

        return $text;
    }

    private function buildWordDocument(
        Department $department,
        Organization $organization,
        ?OrganizationLeader $leader,
        Signatory $signatory,
        string $docNumber,
        string $docDate,
        string $textContent
    ): PhpWord {
        $phpWord = new PhpWord();

        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $phpWord->addFontStyle('headerFont', ['name' => 'Times New Roman', 'size' => 14, 'bold' => true]);
        $phpWord->addFontStyle('boldFont', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true]);
        $phpWord->addFontStyle('normalFont', ['name' => 'Times New Roman', 'size' => 12]);
        $phpWord->addFontStyle('smallFont', ['name' => 'Times New Roman', 'size' => 11]);
        $phpWord->addFontStyle('rightBoldFont', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true]);

        $phpWord->addParagraphStyle('rightAlign', ['alignment' => 'right', 'spaceAfter' => 0]);
        $phpWord->addParagraphStyle('leftAlign', ['alignment' => 'left', 'spaceAfter' => 0]);
        $phpWord->addParagraphStyle('centerAlign', ['alignment' => 'center', 'spaceAfter' => 0]);
        $phpWord->addParagraphStyle('normalPara', ['alignment' => 'both', 'spaceAfter' => 120, 'lineHeight' => 1.5]);
        $phpWord->addParagraphStyle('spacePara', ['spaceAfter' => 240]);

        $section = $phpWord->addSection([
            'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(2),
            'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(3),
            'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.5),
        ]);

        // ===== HEADER — Firmenniy blanka =====
        $header = $section->addHeader();
        $headerTable = $header->addTable(['borderSize' => 0, 'cellMargin' => 100]);
        $headerTable->addRow();
        $headerCell = $headerTable->addCell(null, ['gridSpan' => 1]);
        $headerCell->addText(
            '"URGANCHTRANSGAZ" UNITAR KORXONASI',
            ['name' => 'Times New Roman', 'size' => 11, 'bold' => true],
            ['alignment' => 'center']
        );
        $headerCell->addText(
            'Xorazm viloyati, Urganch shahri',
            ['name' => 'Times New Roman', 'size' => 10],
            ['alignment' => 'center']
        );

        $section->addLine(['weight' => 1, 'width' => 15840, 'height' => 0, 'color' => '000000']);
        $section->addTextBreak(1);

        // ===== QABUL QILUVCHI (o'ng tomonda) =====
        if ($organization->type === 'yuqori' && $leader) {
            $section->addText($organization->name, 'rightBoldFont', 'rightAlign');
            $section->addText($leader->position, 'normalFont', 'rightAlign');
            $section->addText($leader->full_name, 'boldFont', 'rightAlign');
            $section->addTextBreak(1);

            $section->addText('Hurmatli, ' . $leader->full_name . '!', 'boldFont', 'leftAlign');
            $section->addTextBreak(1);
        } else {
            $section->addText($organization->name, 'rightBoldFont', 'rightAlign');
            $section->addTextBreak(1);
        }

        // ===== HUJJAT RAQAMI VA SANASI =====
        $table = $section->addTable(['borderSize' => 0, 'cellMarginTop' => 0, 'cellMarginBottom' => 0]);
        $table->addRow();
        $leftCell = $table->addCell(4500);
        $leftCell->addText('№ ' . $docNumber, 'boldFont', 'leftAlign');
        $rightCell = $table->addCell(5000);
        $rightCell->addText($docDate, 'normalFont', 'rightAlign');

        $section->addTextBreak(1);

        // ===== ASOSIY MATN =====
        $paragraphs = explode("\n", $textContent);
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para !== '') {
                $section->addText($para, 'normalFont', 'normalPara');
            }
        }

        $section->addTextBreak(2);

        // ===== IMZOLOVCHI =====
        $signTable = $section->addTable(['borderSize' => 0, 'cellMarginTop' => 0]);
        $signTable->addRow();
        $posCell = $signTable->addCell(6000);
        $posCell->addText($signatory->position, 'normalFont', 'leftAlign');
        $nameCell = $signTable->addCell(4000);
        $nameCell->addText($signatory->full_name, 'normalFont', 'rightAlign');

        $section->addTextBreak(2);

        // ===== IJROCHI =====
        $section->addLine(['weight' => 1, 'width' => 15840, 'height' => 0]);
        $section->addText(
            'Ijrochi: ' . $department->head_name,
            'smallFont',
            'leftAlign'
        );
        $section->addText(
            'Tel: ' . ($department->head_phone ?? ''),
            'smallFont',
            'leftAlign'
        );

        return $phpWord;
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
        $day = date('d', $ts);
        $month = $months[(int) date('n', $ts)];
        $year = date('Y', $ts);

        return "{$day} {$month} {$year} yil";
    }
}
