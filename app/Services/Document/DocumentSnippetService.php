<?php

namespace App\Services\Document;

class DocumentSnippetService
{
    public function makeSnippet(string $text, string $query, int $radius = 90): string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($plain === '') {
            return '';
        }

        $keywords = $this->keywords($query);
        if ($keywords === []) {
            return mb_substr($plain, 0, 200) . (mb_strlen($plain) > 200 ? '...' : '');
        }

        $lower = mb_strtolower($plain);
        $position = null;

        foreach ($keywords as $keyword) {
            $pos = mb_stripos($lower, mb_strtolower($keyword));
            if ($pos !== false) {
                $position = (int) $pos;
                break;
            }
        }

        if ($position === null) {
            return mb_substr($plain, 0, 200) . (mb_strlen($plain) > 200 ? '...' : '');
        }

        $start = max(0, $position - $radius);
        $length = ($radius * 2) + 60;
        $slice = mb_substr($plain, $start, $length);

        if ($start > 0) {
            $slice = '...' . ltrim($slice);
        }

        if (($start + $length) < mb_strlen($plain)) {
            $slice = rtrim($slice) . '...';
        }

        return $this->highlight($slice, $keywords);
    }

    public function highlight(string $text, array $keywords): string
    {
        $safe = e($text);

        foreach ($keywords as $keyword) {
            $pattern = '/' . preg_quote(e($keyword), '/') . '/iu';
            $safe = preg_replace($pattern, '<mark>$0</mark>', $safe) ?? $safe;
        }

        return $safe;
    }

    public function keywords(string $query): array
    {
        return collect(preg_split('/\s+/u', trim($query)) ?: [])
            ->map(fn(string $term): string => trim($term))
            ->filter(fn(string $term): bool => mb_strlen($term) >= 2)
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }
}

