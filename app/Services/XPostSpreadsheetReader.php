<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads MoW X Posts spreadsheet (xlsx) and maps rows to X post data.
 *
 * Expected columns: Date, Day, Time (EST), Post Type, Theme, Content,
 * Visual/Media Notes, Media, Call to Action, Hashtags, CC.
 */
class XPostSpreadsheetReader
{
    private const EST = 'America/New_York';

    /**
     * @return array<int, array{content: string, media_urls: array<string>, scheduled_for: string|null, theme: string|null, post_type: string|null, row_number: int, raw: array<string, mixed>}>
     */
    public function read(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return [];
        }

        $headers = $this->normalizeHeaders($rows[0]);
        $result = [];

        for ($i = 1; $i < count($rows); $i++) {
            $rawRow = $rows[$i];
            $assoc = $this->rowToAssoc($rawRow, $headers);
            $content = $this->trimString($assoc['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $result[] = [
                'content' => $this->truncateContent($content),
                'media_urls' => $this->extractMediaUrls($assoc),
                'scheduled_for' => $this->parseScheduledFor($assoc),
                'theme' => $this->trimString($assoc['theme'] ?? null),
                'post_type' => $this->trimString($assoc['post_type'] ?? null),
                'row_number' => $i + 1,
                'raw' => $assoc,
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function rowToXPostAttributes(array $row): array
    {
        $attrs = [
            'content' => $row['content'],
            'media_urls' => $row['media_urls'],
            'thread_parts' => null,
        ];
        if ($row['scheduled_for'] !== null) {
            $attrs['scheduled_for'] = $row['scheduled_for'];
        }

        return $attrs;
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headerRow): array
    {
        $out = [];
        foreach ($headerRow as $colIndex => $value) {
            $key = is_string($value) ? strtolower(preg_replace('/\s+/', '_', trim($value))) : 'col_'.$colIndex;
            $out[$colIndex] = $key ?: 'col_'.$colIndex;
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $rawRow
     * @param  array<int, string>  $headers
     * @return array<string, mixed>
     */
    private function rowToAssoc(array $rawRow, array $headers): array
    {
        $assoc = [];
        foreach ($headers as $colIndex => $key) {
            $value = $rawRow[$colIndex] ?? null;
            if ($value !== null && $value !== '') {
                $assoc[$key] = is_numeric($value) && (int) $value == $value ? (int) $value : (string) $value;
            } else {
                $assoc[$key] = null;
            }
        }

        return $assoc;
    }

    /**
     * @param  array<string, mixed>  $assoc
     * @return array<string>
     */
    private function extractMediaUrls(array $assoc): array
    {
        $media = $assoc['media'] ?? null;
        if ($media === null || $media === '') {
            return [];
        }
        $url = is_string($media) ? trim($media) : (string) $media;
        if ($url === '' || ! $this->looksLikeUrl($url)) {
            return [];
        }

        return [$url];
    }

    private function looksLikeUrl(string $s): bool
    {
        return str_starts_with($s, 'http://') || str_starts_with($s, 'https://');
    }

    private function trimString(?string $s): string
    {
        return $s !== null ? trim($s) : '';
    }

    private function truncateContent(string $content): string
    {
        $max = \App\Models\XPost::MAX_TWEET_LENGTH;
        if (mb_strlen($content) <= $max) {
            return $content;
        }

        return mb_substr($content, 0, $max - 3).'...';
    }

    /**
     * @param  array<string, mixed>  $assoc
     */
    private function parseScheduledFor(array $assoc): ?string
    {
        $dateStr = $assoc['date'] ?? null;
        $timeStr = $assoc['time_(est)'] ?? null;
        if ($dateStr === null || $timeStr === null || $dateStr === '' || $timeStr === '') {
            return null;
        }
        $dateStr = trim((string) $dateStr);
        $timeStr = trim((string) $timeStr);
        try {
            $carbon = Carbon::parse($dateStr.' '.$timeStr, self::EST);
            $carbon->setTimezone(config('app.timezone'));

            return $carbon->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
