<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use ZipArchive;

class DocumentSecurityService
{
    /** Map đuôi file -> danh sách MIME hợp lệ thực sự phát hiện qua finfo */
    private array $allowedSignatures = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/octet-stream'],
        'docx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/octet-stream'],
        'pptx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'mp4'  => ['video/mp4'],
    ];

    /**
     * Chạy toàn bộ pipeline kiểm duyệt.
     * Trả về:
     * [
     *   'status' => 'passed' | 'flagged' | 'failed',
     *   'checks' => [...chi tiết từng bước...],
     *   'extracted_text' => string|null,
     * ]
     */
    public function scan(UploadedFile $file): array
    {
        $checks = [];
        $ext = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        // ── Bước 1: Magic bytes / File signature ──────────────────────────
        $sig = $this->checkSignature($realPath, $ext);
        $checks['signature'] = $sig;
        if (!$sig['valid']) {
            return [
                'status' => 'failed',
                'checks' => $checks,
                'extracted_text' => null,
            ];
        }

        // ── Bước 2: Virus scan (ClamAV REST, optional) ─────────────────────
        $virus = $this->scanVirus($realPath, $file->getClientOriginalName());
        $checks['virus'] = $virus;
        if ($virus['status'] === 'infected') {
            return [
                'status' => 'failed',
                'checks' => $checks,
                'extracted_text' => null,
            ];
        }

        // ── Bước 3: Trích xuất nội dung ─────────────────────────────────
        $extracted = $this->extractText($realPath, $ext);
        $checks['extraction'] = [
            'supported' => $extracted !== null,
            'length'    => $extracted ? mb_strlen($extracted) : 0,
        ];

        // ── Bước 4: Kiểm tra nội dung cơ bản (rỗng bất thường / spam đơn giản) ──
        $content = $this->basicContentCheck($extracted);
        $checks['content'] = $content;

        $status = 'passed';
        if (($virus['status'] ?? null) === 'error' || $content['suspicious']) {
            $status = 'flagged'; // không chặn, nhưng admin cần chú ý kỹ hơn
        }

        return [
            'status' => $status,
            'checks' => $checks,
            'extracted_text' => $extracted ? mb_substr($extracted, 0, 20000) : null, // giới hạn tránh phình DB
        ];
    }

    private function checkSignature(string $realPath, string $ext): array
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->file($realPath) ?: 'unknown';

        $allowed = $this->allowedSignatures[$ext] ?? [];
        $valid = in_array($detected, $allowed, true);

        if (!$valid) {
            Log::warning('File signature mismatch', [
                'ext' => $ext, 'detected_mime' => $detected,
            ]);
        }

        return ['detected_mime' => $detected, 'expected_ext' => $ext, 'valid' => $valid];
    }

    private function scanVirus(string $realPath, string $originalName): array
    {
        $clamUrl = config('services.clamav.url'); // ví dụ: http://clamav-rest.internal:8080/scan

        if (!$clamUrl) {
            return ['status' => 'skipped', 'reason' => 'ClamAV chưa được cấu hình'];
        }

        try {
            $response = Http::timeout(15)
                ->attach('file', file_get_contents($realPath), $originalName)
                ->post($clamUrl);

            if (!$response->successful()) {
                Log::warning('ClamAV scan lỗi HTTP', ['status' => $response->status()]);
                return ['status' => 'error', 'reason' => 'ClamAV không phản hồi hợp lệ'];
            }

            $data = $response->json();
            // Định dạng tuỳ theo service ClamAV REST bạn dùng (vd: ["Status" => "OK"/"FOUND", "Signature" => "..."])
            $infected = ($data['Status'] ?? $data['status'] ?? '') === 'FOUND';

            return [
                'status' => $infected ? 'infected' : 'clean',
                'signature' => $data['Signature'] ?? $data['signature'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('ClamAV scan exception', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    private function extractText(string $realPath, string $ext): ?string
    {
        try {
            return match ($ext) {
                'pdf'  => $this->extractPdf($realPath),
                'docx' => $this->extractDocx($realPath),
                'pptx' => $this->extractPptx($realPath),
                default => null, // doc, ppt (binary cũ), mp4 -> không hỗ trợ
            };
        } catch (\Throwable $e) {
            Log::warning('Trích xuất nội dung thất bại', ['ext' => $ext, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractPdf(string $path): ?string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        $text = trim($pdf->getText());
        return $text !== '' ? $text : null;
    }

    private function extractDocx(string $path): ?string
    {
        $phpWord = WordIOFactory::load($path);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $t = $element->getText();
                    $text .= is_string($t) ? $t . "\n" : '';
                }
            }
        }
        $text = trim($text);
        return $text !== '' ? $text : null;
    }

    private function extractPptx(string $path): ?string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $text = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
                $xml = $zip->getFromName($name);
                // Lấy nội dung trong thẻ <a:t>...</a:t>
                preg_match_all('/<a:t>(.*?)<\/a:t>/s', $xml, $matches);
                $text .= implode(' ', array_map('html_entity_decode', $matches[1] ?? [])) . "\n";
            }
        }
        $zip->close();

        $text = trim($text);
        return $text !== '' ? $text : null;
    }

    private function basicContentCheck(?string $text): array
    {
        if ($text === null) {
            return ['suspicious' => false, 'reason' => 'Không trích xuất được nội dung để kiểm tra'];
        }

        $len = mb_strlen($text);
        $blacklist = ['xxx', 'casino', 'crack', 'keygen', 'torrent']; // ví dụ tối thiểu, nên mở rộng
        $lower = mb_strtolower($text);

        foreach ($blacklist as $word) {
            if (str_contains($lower, $word)) {
                return ['suspicious' => true, 'reason' => "Phát hiện từ khoá nghi vấn: {$word}"];
            }
        }

        if ($len < 30) {
            return ['suspicious' => true, 'reason' => 'Nội dung trích xuất quá ngắn, có thể là file rỗng/giả'];
        }

        return ['suspicious' => false, 'reason' => null];
    }
}