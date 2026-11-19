<?php

namespace App\Support;

final class HollowfangAudit
{
    /**
     * Filter the bounded, read-only V3 snapshot. All values are treated as
     * untrusted because action detail comes from Discord/user input.
     *
     * @param array<int, mixed> $entries
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $entries, string $search = '', string $category = 'all'): array
    {
        $search = mb_strtolower(trim(mb_substr($search, 0, 100)));
        $prefixes = [
            'moderation' => 'moderation.',
            'role' => 'role.',
            'event' => 'event.',
        ];
        $category = array_key_exists($category, $prefixes) ? $category : 'all';

        return array_values(array_filter($entries, static function ($entry) use ($search, $category, $prefixes): bool {
            if (! is_array($entry)) {
                return false;
            }
            $action = (string) ($entry['action'] ?? '');
            if ($category !== 'all' && ! str_starts_with($action, $prefixes[$category])) {
                return false;
            }
            if ($search === '') {
                return true;
            }

            $haystack = mb_strtolower(implode(' ', array_map(static fn ($value) => is_scalar($value) ? (string) $value : '', [
                $entry['action'] ?? '', $entry['actor'] ?? '', $entry['target'] ?? '',
                $entry['detail'] ?? '', $entry['at'] ?? '',
            ])));

            return str_contains($haystack, $search);
        }));
    }

    /**
     * Prevent spreadsheet formula evaluation and preserve long Discord IDs
     * when opening the CSV in Excel.
     */
    public static function safeCell(mixed $value): string
    {
        $text = is_scalar($value) ? (string) $value : '';
        $text = str_replace(["\r", "\n", "\0"], ' ', $text);
        if (preg_match('/^\s*[=+\-@]/u', $text) || preg_match('/^[\t\x0B\x0C]/', $text)) {
            return "'".$text;
        }
        if (preg_match('/^\d{15,22}$/', $text)) {
            return "'".$text;
        }

        return $text;
    }

    /**
     * @param resource $stream
     * @param array<int, array<string, mixed>> $entries
     */
    public static function writeCsv($stream, array $entries): void
    {
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Waktu (UTC)', 'Aksi', 'Actor Discord ID', 'Target Discord ID', 'Keterangan'], ',', '"', '');
        foreach ($entries as $entry) {
            fputcsv($stream, array_map(self::safeCell(...), [
                $entry['at'] ?? '', $entry['action'] ?? '', $entry['actor'] ?? '',
                $entry['target'] ?? '', $entry['detail'] ?? '',
            ]), ',', '"', '');
        }
    }
}
