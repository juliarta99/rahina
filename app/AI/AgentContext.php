<?php

declare(strict_types=1);

namespace App\AI;

/**
 * Bukti yang dikumpulkan selama satu sesi Tanya Wariga: tanggal & aturan yang BENAR-BENAR dikembalikan tool.
 * OutputGuard hanya menerima tanggal/aturan yang tercatat di sini (AI_IMPLEMENTATION.md §6).
 */
final class AgentContext
{
    /**
     * Tanggal → data tampilan dari tool (wewaran, wuku, sasih, pendukung, penghalang).
     *
     * @var array<string, array{date: string, saptawara: string, pancawara: string, wuku: string, sasih: string, rahinan: list<string>, score: ?int, support: list<array{id: int, name: string}>, block: list<array{id: int, name: string}>, origin: string}>
     */
    public array $dates = [];

    /** @var array<string, array{code: string, name: string, requires_cultural_notice: bool}> */
    public array $categories = [];

    /** @var list<array{name: string, args: array<string, mixed>, ok: bool, error?: string}> */
    public array $toolCalls = [];

    /** Urutan tanggal hasil find_good_days terbaik (untuk jawaban template). @var list<string> */
    public array $rankedOrder = [];

    /** Nama dewasa yang dikembalikan explain_dewasa (boleh disebut di teks). @var list<string> */
    public array $explained = [];

    /** Status langkah agent untuk UI ("Mencocokkan aktivitas…"). @var list<string> */
    public array $steps = [];

    /** Pengguna sudah memilih kategori (dari tombol klarifikasi). */
    public bool $categoryChosen = false;

    /** Pertanyaan pengguna (hanya untuk pemeriksaan kategori di tool; tidak disimpan ke log). */
    public string $question = '';

    /** Kandidat kategori saat map_activity ragu; selama terisi, find_good_days ditolak sampai pengguna memilih. @var list<string>|null */
    public ?array $pendingClarification = null;

    public function __construct(public readonly string $today) {}

    /**
     * @param  array{date: string, saptawara: string, pancawara: string, wuku: string, sasih: string, rahinan: list<string>, score: ?int, support: list<array{id: int, name: string}>, block: list<array{id: int, name: string}>, origin: string}  $data
     */
    public function addDate(array $data): void
    {
        $existing = $this->dates[$data['date']] ?? null;
        if ($existing !== null && $existing['origin'] === 'find_good_days' && $data['origin'] !== 'find_good_days') {
            return; // hasil pemeringkatan lebih spesifik (per kategori) daripada detail hari umum.
        }
        $this->dates[$data['date']] = $data;
    }

    /**
     * @return list<int> aturan yang boleh dirujuk untuk tanggal tersebut.
     */
    public function ruleIdsFor(string $date): array
    {
        $d = $this->dates[$date] ?? null;

        return $d === null ? [] : [...array_column($d['support'], 'id'), ...array_column($d['block'], 'id')];
    }

    /**
     * Semua nama dewasa yang muncul di hasil tool sesi ini.
     *
     * @return list<string>
     */
    public function knownRuleNames(): array
    {
        $names = $this->explained;
        foreach ($this->dates as $d) {
            array_push($names, ...array_column($d['support'], 'name'), ...array_column($d['block'], 'name'));
        }

        return array_values(array_unique($names));
    }

    public function requiresCulturalNotice(): bool
    {
        foreach ($this->categories as $c) {
            if ($c['requires_cultural_notice']) {
                return true;
            }
        }

        return false;
    }
}
