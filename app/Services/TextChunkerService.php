<?php

namespace App\Services;

class TextChunkerService
{
    public function chunk(string $text, int $chunkSize = 800, int $overlap = 150): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') return [];

        // Tách theo câu (đơn giản, đủ dùng cho tiếng Việt có dấu câu chuẩn)
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($current) + mb_strlen($sentence) > $chunkSize && $current !== '') {
                $chunks[] = trim($current);
                // Giữ lại phần overlap cuối chunk trước để nối tiếp
                $current = mb_substr($current, max(0, mb_strlen($current) - $overlap)) . ' ' . $sentence;
            } else {
                $current .= ' ' . $sentence;
            }
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return $chunks;
    }
}