<?php

declare(strict_types=1);

namespace App\AI;

use RuntimeException;

/**
 * Membaca prompt dari `prompts/{id}.v{version}.md`, memisahkan header front matter dari isinya.
 * Mengubah prompt = menaikkan versi (AI_IMPLEMENTATION.md §11).
 *
 * @throws RuntimeException jika file tidak ada atau header tidak cocok.
 */
final class PromptRepository
{
    public function __construct(private readonly ?string $directory = null) {}

    /**
     * @return array{id: string, version: int, body: string}
     */
    public function get(string $id, int $version): array
    {
        $path = ($this->directory ?? base_path('prompts')).DIRECTORY_SEPARATOR."{$id}.v{$version}.md";
        if (! is_file($path)) {
            throw new RuntimeException("Prompt tidak ditemukan: {$path}");
        }

        $raw = str_replace("\r\n", "\n", (string) file_get_contents($path));
        if (preg_match('/^---\n(.*?)\n---\n(.*)$/s', $raw, $m) !== 1) {
            throw new RuntimeException("Prompt {$path} tidak memiliki header front matter");
        }

        $meta = [];
        foreach (explode("\n", $m[1]) as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = array_map('trim', explode(':', $line, 2));
                $meta[$k] = $v;
            }
        }

        if (($meta['id'] ?? null) !== $id || (int) ($meta['version'] ?? 0) !== $version) {
            throw new RuntimeException("Header prompt {$path} tidak cocok dengan id/versi");
        }

        return ['id' => $id, 'version' => $version, 'body' => trim($m[2])];
    }

    public static function versionTag(string $id, int $version): string
    {
        return "{$id}.v{$version}";
    }
}
