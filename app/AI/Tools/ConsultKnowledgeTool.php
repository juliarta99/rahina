<?php

declare(strict_types=1);

namespace App\AI\Tools;

use App\AI\AgentContext;
use App\AI\KnowledgeRepository;
use InvalidArgumentException;

/**
 * `consult_knowledge`: RAG & Knowledge Retrieval untuk kearifan lokal kalender Bali:
 * - Ramalan Zodiak / Bintang (12 zodiak)
 * - Numerologi Garis Hidup (1-9)
 * - Perjodohan Lontar Tri Pramana
 * - Arti Wewaran, Wuku, Lintang, dan Pratiti Kelahiran
 *
 * Bila input ambigu atau tidak lengkap, tool mengembalikan `needs_clarification = true`
 * beserta `question` agar model wajib bertanya balik untuk kepastian.
 */
final class ConsultKnowledgeTool implements Tool
{
    public function __construct(private readonly KnowledgeRepository $knowledge) {}

    public function name(): string
    {
        return 'consult_knowledge';
    }

    public function declaration(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Membaca kearifan lokal kalender Bali: ramalan zodiak/bintang, garis hidup numerologi (1-9), kecocokan jodoh Lontar Tri Pramana, dan arti wewaran/wuku/lintang kelahiran. Bila informasi pengguna kurang lengkap/ambigu, tool akan meminta klarifikasi tanggal lahir atau data yang dibutuhkan.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'topic' => [
                        'type' => 'STRING',
                        'enum' => ['bintang', 'garis_hidup', 'jodoh', 'arti_hari', 'search'],
                        'description' => 'Topik pengetahuan yang dicari: bintang (zodiak), garis_hidup (life path 1-9), jodoh (Lontar Tri Pramana), arti_hari (wuku/wewaran/lintang), atau search (pencarian umum).',
                    ],
                    'query' => [
                        'type' => 'STRING',
                        'description' => 'Kata kunci pencarian, nama zodiak (misal Leo, Scorpio), nama wuku (misal Sinta), nama lintang, dsb.',
                    ],
                    'birthdate' => [
                        'type' => 'STRING',
                        'description' => 'Tanggal lahir format YYYY-MM-DD.',
                    ],
                    'partner_birthdate' => [
                        'type' => 'STRING',
                        'description' => 'Tanggal lahir pasangan format YYYY-MM-DD (khusus topik jodoh).',
                    ],
                    'life_path_number' => [
                        'type' => 'INTEGER',
                        'description' => 'Nomor garis hidup 1 sampai 9 (bila sudah diketahui).',
                    ],
                ],
                'required' => ['topic'],
            ],
        ];
    }

    public function status(array $args): string
    {
        $topic = (string) ($args['topic'] ?? 'pengetahuan');

        return match ($topic) {
            'bintang' => 'Membaca ramalan zodiak/bintang…',
            'garis_hidup' => 'Menghitung dan membaca numerologi garis hidup…',
            'jodoh' => 'Menghitung ramalan jodoh Lontar Tri Pramana…',
            'arti_hari' => 'Membaca arti wewaran dan wuku kelahiran…',
            default => 'Mencari informasi kearifan lokal wariga…',
        };
    }

    public function run(array $args, AgentContext $context): array
    {
        $topic = (string) ($args['topic'] ?? 'search');
        $query = trim((string) ($args['query'] ?? ''));
        $birthdate = trim((string) ($args['birthdate'] ?? ''));
        $partnerDate = trim((string) ($args['partner_birthdate'] ?? ''));
        $lifeNumber = isset($args['life_path_number']) ? (int) $args['life_path_number'] : null;

        if ($birthdate !== '') {
            $context->allowedDates[] = $birthdate;
        }
        if ($partnerDate !== '') {
            $context->allowedDates[] = $partnerDate;
        }

        $res = match ($topic) {
            'bintang' => $this->handleBintang($query, $birthdate),
            'garis_hidup' => $this->handleGarisHidup($lifeNumber, $birthdate, $query),
            'jodoh' => $this->handleJodoh($birthdate, $partnerDate),
            'arti_hari' => $this->handleArtiHari($query, $birthdate),
            default => $this->handleSearch($query !== '' ? $query : $birthdate),
        };

        if (($res['cultural_notice'] ?? false) === true) {
            $context->culturalNoticeRequested = true;
        }

        return $res;
    }

    private function handleBintang(string $query, string $birthdate): array
    {
        $target = $query !== '' ? $query : $birthdate;
        if ($target === '') {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Mohon sebutkan nama zodiak/bintang Anda atau tanggal lahir Anda agar saya dapat melihat ramalannya.',
            ];
        }

        $bintang = $this->knowledge->findBintang($target);
        if ($bintang === null) {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => "Zodiak '{$target}' tidak dikenali. Mohon sebutkan nama zodiak (misal Aries, Leo, Scorpio) atau tanggal lahir Anda.",
            ];
        }

        $responseData = $bintang;
        $date = $this->knowledge->parseDate($target);
        if ($date !== null) {
            $profile = $this->knowledge->getBirthProfile($date);
            $responseData['profil_kelahiran'] = [
                'birthdate_formatted' => $profile['birthdate_formatted'],
                'garis_hidup_nomor' => $profile['garis_hidup']['number'] ?? null,
                'garis_hidup_uraian' => $profile['garis_hidup']['description'] ?? null,
                'hari_lahir' => $profile['hari_lahir']['nama'] ?? null,
                'hari_lahir_karakter' => $profile['hari_lahir']['karakter'] ?? null,
            ];
        }

        return [
            'found' => true,
            'topic' => 'bintang',
            'data' => $responseData,
            'cultural_notice' => true,
        ];
    }

    private function handleGarisHidup(?int $number, string $birthdate, string $query): array
    {
        $target = $number ?? ($birthdate !== '' ? $birthdate : (is_numeric($query) ? (int) $query : null));
        if ($target === null) {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Untuk menghitung angka Garis Hidup, mohon sebutkan tanggal, bulan, dan tahun lahir lengkap Anda (contoh: 17 Agustus 1995).',
            ];
        }

        $garis = $this->knowledge->findGarisHidup($target);
        if ($garis === null) {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Angka garis hidup harus antara 1 sampai 9 atau tanggal lahir lengkap. Mohon periksa kembali tanggal lahir Anda.',
            ];
        }

        return [
            'found' => true,
            'topic' => 'garis_hidup',
            'data' => $garis,
            'cultural_notice' => true,
        ];
    }

    private function handleJodoh(string $birthdate, string $partnerDate): array
    {
        if ($birthdate === '' || $partnerDate === '') {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Untuk menghitung kecocokan jodoh menurut Lontar Tri Pramana, mohon sebutkan tanggal lahir Anda dan tanggal lahir pasangan Anda lengkap dengan tahunnya.',
            ];
        }

        try {
            $calculation = $this->knowledge->calculateJodoh($birthdate, $partnerDate);

            return [
                'found' => true,
                'topic' => 'jodoh',
                'data' => $calculation,
                'cultural_notice' => true,
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Format tanggal lahir belum valid. Mohon sebutkan tanggal lahir kedua pihak dalam format yang jelas (contoh: 21 April 1996 dan 5 Juni 1998).',
            ];
        }
    }

    private function handleArtiHari(string $query, string $birthdate): array
    {
        $target = $query !== '' ? $query : $birthdate;
        if ($target === '') {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Mohon sebutkan nama elemen kelahiran yang ingin diketahui (misalnya nama wuku, wewaran, lintang) atau tanggal lahir Anda.',
            ];
        }

        // 1. Bila target adalah tanggal lahir (misal "29 Januari 2006" atau "2006-01-29")
        $date = $this->knowledge->parseDate($target);
        if ($date !== null) {
            $profile = $this->knowledge->getBirthProfile($date);

            return [
                'found' => true,
                'topic' => 'karakter_kelahiran',
                'data' => $profile,
                'cultural_notice' => true,
            ];
        }

        // 2. Bila nama elemen wuku / wewaran / lintang
        $result = $this->knowledge->findArtiHari($target);
        if ($result !== null) {
            return [
                'found' => true,
                'topic' => 'arti_hari',
                'data' => $result,
                'cultural_notice' => true,
            ];
        }

        // 3. Fallback RAG search
        $rag = $this->knowledge->search($target, 2);
        if ($rag !== []) {
            return [
                'found' => true,
                'topic' => 'arti_hari',
                'data' => $rag[0],
                'cultural_notice' => true,
            ];
        }

        return [
            'found' => false,
            'needs_clarification' => true,
            'clarification_question' => "Karakter kelahiran atau wewaran '{$target}' belum ditemukan di data. Mohon pastikan ejaan nama wuku, wewaran, atau tanggal lahir.",
        ];
    }

    private function handleSearch(string $query): array
    {
        if ($query === '') {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Mohon sebutkan topik atau pertanyaan yang ingin Anda tanyakan.',
            ];
        }

        // Periksa bila query mengandung tanggal lahir
        $date = $this->knowledge->parseDate($query);
        if ($date !== null) {
            $profile = $this->knowledge->getBirthProfile($date);

            return [
                'found' => true,
                'topic' => 'karakter_kelahiran',
                'data' => $profile,
                'cultural_notice' => true,
            ];
        }

        $results = $this->knowledge->search($query, 3);
        if ($results === []) {
            return [
                'found' => false,
                'message' => "Tidak ditemukan informasi yang cocok untuk '{$query}'.",
            ];
        }

        return [
            'found' => true,
            'topic' => 'search',
            'data' => $results,
            'cultural_notice' => true,
        ];
    }
}
