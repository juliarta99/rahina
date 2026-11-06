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

        // Normalisasi tanggal lahir ke format ISO YYYY-MM-DD
        if ($birthdate !== '') {
            $parsedB = $this->knowledge->parseDate($birthdate);
            $birthdate = $parsedB !== null ? $parsedB->toDateString() : $birthdate;
            $this->allowDate($birthdate, $context);
        }
        if ($partnerDate !== '') {
            $parsedP = $this->knowledge->parseDate($partnerDate);
            $partnerDate = $parsedP !== null ? $parsedP->toDateString() : $partnerDate;
            $this->allowDate($partnerDate, $context);
        }

        // Jika topik jodoh dan salah satu atau kedua tanggal belum terisi, coba ekstrak dari query atau birthdate
        if ($topic === 'jodoh' && ($partnerDate === '' || $birthdate === '')) {
            $combined = trim("{$birthdate} {$partnerDate} {$query}");
            $dates = $this->knowledge->parseAllDates($combined);
            if (count($dates) >= 2) {
                $birthdate = $dates[0]->toDateString();
                $partnerDate = $dates[1]->toDateString();
                $this->allowDate($birthdate, $context);
                $this->allowDate($partnerDate, $context);
            }
        }

        $res = match ($topic) {
            'bintang' => $this->handleBintang($query, $birthdate, $context),
            'garis_hidup' => $this->handleGarisHidup($lifeNumber, $birthdate, $query, $context),
            'jodoh' => $this->handleJodoh($birthdate, $partnerDate),
            'arti_hari' => $this->handleArtiHari($query, $birthdate, $context),
            default => $this->handleSearch($query !== '' ? $query : $birthdate, $context),
        };

        if (($res['cultural_notice'] ?? false) === true) {
            $context->culturalNoticeRequested = true;
        }

        return $res;
    }

    private function allowDate(string $date, AgentContext $context): void
    {
        $parsed = $this->knowledge->parseDate($date);
        if ($parsed !== null) {
            $iso = $parsed->toDateString();
            if (! in_array($iso, $context->allowedDates, true)) {
                $context->allowedDates[] = $iso;
            }
        } elseif (! in_array($date, $context->allowedDates, true)) {
            $context->allowedDates[] = $date;
        }
    }

    private function allowZodiacDates(?array $bintang, AgentContext $context): void
    {
        if ($bintang === null || ! isset($bintang['dates'])) {
            return;
        }

        $idToMonth = [
            'januari' => '01', 'februari' => '02', 'pebruari' => '02',
            'maret' => '03', 'april' => '04', 'mei' => '05',
            'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10',
            'nopember' => '11', 'november' => '11', 'desember' => '12',
        ];

        $pattern = implode('|', array_keys($idToMonth));
        if (preg_match_all('/\b(\d{1,2})\s+('.$pattern.')\b/iu', (string) $bintang['dates'], $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $month = $idToMonth[mb_strtolower($m[2])];
                $mmdd = "{$month}-{$day}";
                if (! in_array($mmdd, $context->allowedDates, true)) {
                    $context->allowedDates[] = $mmdd;
                }
            }
        }
    }

    private function handleBintang(string $query, string $birthdate, AgentContext $context): array
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

        $this->allowZodiacDates($bintang, $context);
        $responseData = $bintang;
        $date = $this->knowledge->parseDate($target);
        if ($date !== null) {
            $this->allowDate($date->toDateString(), $context);
            $profile = $this->knowledge->getBirthProfile($date);
            $responseData['profil_kelahiran'] = [
                'birthdate_formatted' => $profile['birthdate_formatted'],
                'garis_hidup_nomor' => $profile['garis_hidup']['number'] ?? null,
                'garis_hidup_uraian' => $profile['garis_hidup']['description'] ?? null,
                'hari_lahir' => $profile['hari_lahir']['nama'] ?? null,
                'hari_lahir_karakter' => $profile['hari_lahir']['karakter'] ?? null,
                'pancawara' => $profile['pancawara']['nama'] ?? null,
                'sadwara' => $profile['sadwara']['nama'] ?? null,
            ];
        }

        return [
            'found' => true,
            'topic' => 'bintang',
            'data' => $responseData,
            'cultural_notice' => true,
        ];
    }

    private function handleGarisHidup(?int $number, string $birthdate, string $query, AgentContext $context): array
    {
        $target = $number ?? ($birthdate !== '' ? $birthdate : (is_numeric($query) ? (int) $query : null));
        if ($target === null) {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Untuk menghitung angka Garis Hidup, mohon sebutkan tanggal, bulan, dan tahun lahir lengkap Anda (contoh: 17 Agustus 1995).',
            ];
        }

        if (is_string($target)) {
            $this->allowDate($target, $context);
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

    private function handleArtiHari(string $query, string $birthdate, AgentContext $context): array
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
            $this->allowDate($date->toDateString(), $context);
            $profile = $this->knowledge->getBirthProfile($date);
            $this->allowZodiacDates($profile['bintang'] ?? null, $context);

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

    private function handleSearch(string $query, AgentContext $context): array
    {
        if ($query === '') {
            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => 'Mohon sebutkan topik atau pertanyaan yang ingin Anda tanyakan.',
            ];
        }

        // Cek bila query terkait kecocokan jodoh / pasangan
        $isJodoh = preg_match('/\b(jodoh|cocok|pasangan|perjodohan|nikah|kawin)\b/iu', $query) === 1;
        $allDates = $this->knowledge->parseAllDates($query);
        if ($isJodoh && count($allDates) >= 2) {
            $b1 = $allDates[0]->toDateString();
            $b2 = $allDates[1]->toDateString();
            $this->allowDate($b1, $context);
            $this->allowDate($b2, $context);

            return $this->handleJodoh($b1, $b2);
        }

        if ($isJodoh && count($allDates) === 1) {
            $b1 = $allDates[0]->toDateString();
            $this->allowDate($b1, $context);

            return [
                'found' => false,
                'needs_clarification' => true,
                'clarification_question' => "Untuk menghitung ramalan kecocokan jodoh kelahiran {$b1} menurut Lontar Tri Pramana, mohon sebutkan tanggal lahir pasangan Anda.",
            ];
        }

        // Periksa bila query mengandung tanggal lahir tunggal
        if ($allDates !== []) {
            $date = $allDates[0];
            $this->allowDate($date->toDateString(), $context);
            $profile = $this->knowledge->getBirthProfile($date);
            $this->allowZodiacDates($profile['bintang'] ?? null, $context);

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
