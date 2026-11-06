<?php

declare(strict_types=1);

namespace App\AI;

use App\Wariga\CalendarRepository;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Knowledge Repository & In-Memory RAG Engine untuk data kearifan lokal Kalender Bali:
 * 1. Ramalan Bintang / Zodiak (12 zodiak)
 * 2. Numerologi Garis Hidup (Life Path 1–9)
 * 3. Perjodohan Lontar Tri Pramana (Kombinasi Urip Panca/Sad/Sapta Wara mod 16)
 * 4. Kamus Karakteristik Kelahiran & Arti Hari (Wewaran, Wuku, Lintang, Pratiti)
 */
final class KnowledgeRepository
{
    /** @var list<array<string, mixed>>|null */
    private ?array $bintang = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $garisHidup = null;

    /** @var array<string, mixed>|null */
    private ?array $jodoh = null;

    /** @var array<string, array<string, string>>|null */
    private ?array $artihari = null;

    /** @var list<array{source: string, title: string, text: string, keywords: list<string>}>|null */
    private ?array $ragIndex = null;

    public function __construct(private readonly ?CalendarRepository $calendar = null) {}

    /**
     * Memuat dataset Bintang (12 zodiak).
     *
     * @return list<array<string, mixed>>
     */
    public function allBintang(): array
    {
        if ($this->bintang === null) {
            $path = database_path('data/knowledge_bintang.json');
            $this->bintang = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
        }

        return $this->bintang;
    }

    /**
     * Memuat dataset Garis Hidup (1-9).
     *
     * @return list<array<string, mixed>>
     */
    public function allGarisHidup(): array
    {
        if ($this->garisHidup === null) {
            $path = database_path('data/knowledge_garishidup.json');
            $this->garisHidup = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
        }

        return $this->garisHidup;
    }

    /**
     * Memuat dataset Perjodohan Lontar Tri Pramana.
     *
     * @return array<string, mixed>
     */
    public function allJodoh(): array
    {
        if ($this->jodoh === null) {
            $path = database_path('data/knowledge_jodoh.json');
            $this->jodoh = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
        }

        return $this->jodoh;
    }

    /**
     * Memuat kamus Arti Hari & Wewaran.
     *
     * @return array<string, array<string, string>>
     */
    public function allArtiHari(): array
    {
        if ($this->artihari === null) {
            $path = database_path('data/knowledge_artihari.json');
            $this->artihari = file_exists($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
        }

        return $this->artihari;
    }

    /**
     * Cari ramalan bintang berdasarkan nama atau tanggal lahir.
     *
     * @return array<string, mixed>|null
     */
    public function findBintang(string $queryOrDate): ?array
    {
        $all = $this->allBintang();
        $q = mb_strtolower(trim($queryOrDate));

        // 1. Coba cocokkan tanggal (format YYYY-MM-DD atau DD-MM atau D Month)
        $date = $this->parseDate($queryOrDate);
        if ($date !== null) {
            $m = (int) $date->format('n');
            $d = (int) $date->format('j');
            $zodiacName = $this->zodiacFromMonthDay($m, $d);
            if ($zodiacName !== null) {
                foreach ($all as $b) {
                    if (mb_strtolower((string) $b['name']) === mb_strtolower($zodiacName)) {
                        return $b;
                    }
                }
            }
        }

        // 2. Coba cocokkan nama zodiak
        foreach ($all as $b) {
            $name = mb_strtolower((string) $b['name']);
            if ($name === $q || str_contains($q, $name) || str_contains($name, $q)) {
                return $b;
            }
        }

        return null;
    }

    /**
     * Cari ramalan Garis Hidup berdasarkan angka (1-9) atau tanggal lahir.
     *
     * @return array<string, mixed>|null
     */
    public function findGarisHidup(int|string $numberOrDate): ?array
    {
        $all = $this->allGarisHidup();
        $target = null;

        if (is_numeric($numberOrDate)) {
            $num = (int) $numberOrDate;
            if ($num >= 1 && $num <= 9) {
                $target = $num;
            }
        }

        if ($target === null && is_string($numberOrDate)) {
            $date = $this->parseDate($numberOrDate);
            if ($date !== null) {
                $target = $this->calculateLifePathNumber($date);
            }
        }

        if ($target !== null) {
            foreach ($all as $g) {
                if ((int) ($g['number'] ?? 0) === $target) {
                    return $g;
                }
            }
        }

        return null;
    }

    /**
     * Hitung perjodohan menurut Lontar Tri Pramana dari dua tanggal lahir.
     *
     * @return array<string, mixed>
     */
    public function calculateJodoh(string $birthdate1, string $birthdate2): array
    {
        $d1 = $this->parseDate($birthdate1);
        $d2 = $this->parseDate($birthdate2);

        if ($d1 === null || $d2 === null) {
            throw new InvalidArgumentException('Kedua tanggal lahir wajib valid format YYYY-MM-DD.');
        }

        $urip1 = $this->getUripForDate($d1->toDateString());
        $urip2 = $this->getUripForDate($d2->toDateString());

        $totalUrip = $urip1['total'] + $urip2['total'];
        $sisa = $totalUrip % 16; // 0 dianggap sisa 16

        $jodohData = $this->allJodoh();
        $rule = null;
        foreach ($jodohData['rules'] ?? [] as $r) {
            if ((int) $r['sisa'] === $sisa) {
                $rule = $r;
                break;
            }
        }

        return [
            'person1' => [
                'date' => $d1->toDateString(),
                'saptawara' => $urip1['saptawara'],
                'saptawara_urip' => $urip1['saptawara_urip'],
                'pancawara' => $urip1['pancawara'],
                'pancawara_urip' => $urip1['pancawara_urip'],
                'sadwara' => $urip1['sadwara'],
                'sadwara_urip' => $urip1['sadwara_urip'],
                'total_urip' => $urip1['total'],
            ],
            'person2' => [
                'date' => $d2->toDateString(),
                'saptawara' => $urip2['saptawara'],
                'saptawara_urip' => $urip2['saptawara_urip'],
                'pancawara' => $urip2['pancawara'],
                'pancawara_urip' => $urip2['pancawara_urip'],
                'sadwara' => $urip2['sadwara'],
                'sadwara_urip' => $urip2['sadwara_urip'],
                'total_urip' => $urip2['total'],
            ],
            'total_urip' => $totalUrip,
            'modulo' => 16,
            'sisa' => $sisa,
            'sisa_label' => $sisa === 0 ? 'Sisa 0 / 16' : "Sisa {$sisa}",
            'ramalan' => $rule['ramalan'] ?? 'Tidak ada data spesifik untuk sisa ini.',
            'verdict' => $rule['verdict'] ?? 'campur',
            'cultural_notice' => true,
            'source' => 'Lontar Tri Pramana (Kalender Bali)',
        ];
    }

    /**
     * Hitung urip (Pancawara, Sadwara, Saptawara) untuk satu tanggal.
     *
     * @return array{saptawara: string, saptawara_urip: int, pancawara: string, pancawara_urip: int, sadwara: string, sadwara_urip: int, total: int}
     */
    public function getUripForDate(string $date): array
    {
        $pancawaraUrip = ['Umanis' => 5, 'Paing' => 9, 'Pon' => 7, 'Wage' => 4, 'Kliwon' => 8];
        $sadwaraUrip = ['Tungleh' => 7, 'Aryang' => 6, 'Urukung' => 5, 'Paniron' => 8, 'Was' => 9, 'Maulu' => 3];
        $saptawaraUrip = ['Redite' => 5, 'Soma' => 4, 'Anggara' => 3, 'Buda' => 7, 'Wraspati' => 8, 'Sukra' => 6, 'Saniscara' => 9];

        $sapta = 'Redite';
        $panca = 'Umanis';
        $sad = 'Tungleh';

        if ($this->calendar !== null) {
            $day = $this->calendar->day($date);
            if ($day !== null) {
                $sapta = $day->saptawara() !== '' ? $day->saptawara() : $sapta;
                $panca = $day->pancawara() !== '' ? $day->pancawara() : $panca;
                $sad = $day->wewaran['sadwara']->name ?? $sad;
            }
        }

        // Fallback hitung jika calendar repository belum melingkupi tanggal tersebut
        if (! isset($saptawaraUrip[$sapta])) {
            $carbon = CarbonImmutable::parse($date);
            $dayOfWeek = (int) $carbon->format('w'); // 0 = Sunday = Redite
            $saptaNames = ['Redite', 'Soma', 'Anggara', 'Buda', 'Wraspati', 'Sukra', 'Saniscara'];
            $sapta = $saptaNames[$dayOfWeek];
        }

        $sUrip = $saptawaraUrip[$sapta] ?? 5;
        $pUrip = $pancawaraUrip[$panca] ?? 5;
        $sdUrip = $sadwaraUrip[$sad] ?? 7;

        return [
            'saptawara' => $sapta,
            'saptawara_urip' => $sUrip,
            'pancawara' => $panca,
            'pancawara_urip' => $pUrip,
            'sadwara' => $sad,
            'sadwara_urip' => $sdUrip,
            'total' => $sUrip + $pUrip + $sdUrip,
        ];
    }

    /**
     * Cari arti elemen wewaran / wuku / lintang / pratiti.
     *
     * @return array<string, mixed>|null
     */
    public function findArtiHari(string $elementName, ?string $category = null): ?array
    {
        $dict = $this->allArtiHari();
        $needle = mb_strtolower(trim($elementName));

        // Jika kategori ditentukan (misal 'wuku', 'lintang')
        if ($category !== null && isset($dict[$category])) {
            foreach ($dict[$category] as $name => $desc) {
                if (mb_strtolower($name) === $needle || str_contains(mb_strtolower($name), $needle)) {
                    return ['category' => $category, 'name' => $name, 'description' => $desc];
                }
            }
        }

        // Cari di seluruh kategori
        foreach ($dict as $cat => $items) {
            foreach ($items as $name => $desc) {
                if (mb_strtolower($name) === $needle || str_contains(mb_strtolower($name), $needle)) {
                    return ['category' => $cat, 'name' => $name, 'description' => $desc];
                }
            }
        }

        return null;
    }

    /**
     * RAG Fast Search: Mencari di seluruh dokumen pengetahuan (Bintang, Garis Hidup, Jodoh, Arti Hari)
     *
     * @return list<array{source: string, title: string, snippet: string, score: float}>
     */
    public function search(string $query, int $limit = 3): array
    {
        $this->ensureRagIndex();
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($tokens === []) {
            return [];
        }

        $scored = [];
        foreach ($this->ragIndex as $doc) {
            $score = 0;
            $titleLower = mb_strtolower($doc['title']);
            $textLower = mb_strtolower($doc['text']);

            foreach ($tokens as $token) {
                if (mb_strlen($token) < 2) {
                    continue;
                }
                if (str_contains($titleLower, $token)) {
                    $score += 5.0;
                }
                if (in_array($token, $doc['keywords'], true)) {
                    $score += 3.0;
                }
                $count = substr_count($textLower, $token);
                if ($count > 0) {
                    $score += min(4.0, $count * 0.8);
                }
            }

            if ($score > 0) {
                $snippet = mb_substr($doc['text'], 0, 300).(mb_strlen($doc['text']) > 300 ? '…' : '');
                $scored[] = [
                    'source' => $doc['source'],
                    'title' => $doc['title'],
                    'snippet' => $snippet,
                    'score' => $score,
                ];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Inisialisasi in-memory RAG index.
     */
    private function ensureRagIndex(): void
    {
        if ($this->ragIndex !== null) {
            return;
        }

        $docs = [];

        // 1. Index Bintang
        foreach ($this->allBintang() as $b) {
            $name = (string) $b['name'];
            $text = "Zodiak {$name} ({$b['dates']}). Simbol: {$b['simbul']}. Karakter: {$b['karakter']}. Kesehatan: {$b['kesehatan']}. Pekerjaan yang cocok: {$b['pekerjaan']}. Jodoh: {$b['jodoh']}. Hari baik: {$b['hari']}, Warna: {$b['warna']}, Batu: {$b['batu']}, Angka keberuntungan: {$b['angka']}.";
            $docs[] = [
                'source' => 'bintang',
                'title' => "Ramalan Zodiak {$name}",
                'text' => $text,
                'keywords' => [mb_strtolower($name), 'zodiak', 'bintang', mb_strtolower((string) $b['simbul'])],
            ];
        }

        // 2. Index Garis Hidup
        foreach ($this->allGarisHidup() as $g) {
            $num = $g['number'];
            $docs[] = [
                'source' => 'garis_hidup',
                'title' => "Garis Hidup {$num}",
                'text' => (string) $g['description'],
                'keywords' => ["garis hidup {$num}", "angka {$num}", 'numerologi', 'misi hidup'],
            ];
        }

        // 3. Index Jodoh Lontar Tri Pramana
        $jodoh = $this->allJodoh();
        foreach ($jodoh['rules'] ?? [] as $r) {
            $sisa = $r['sisa'];
            $docs[] = [
                'source' => 'jodoh',
                'title' => "Perjodohan Lontar Tri Pramana ({$r['label']})",
                'text' => "Berdasarkan ramalan perjodohan dari Lontar Tri Pramana, {$r['ramalan']}",
                'keywords' => ['jodoh', 'perjodohan', 'tri pramana', "sisa {$sisa}", 'pasangan'],
            ];
        }

        // 4. Index Arti Hari (Wuku, Lintang, Pratiti)
        foreach ($this->allArtiHari() as $cat => $items) {
            foreach ($items as $name => $desc) {
                $docs[] = [
                    'source' => "artihari_{$cat}",
                    'title' => ucfirst($cat).": {$name}",
                    'text' => "Arti {$cat} {$name}: {$desc}",
                    'keywords' => [mb_strtolower($cat), mb_strtolower($name), 'kelahiran', 'karakter'],
                ];
            }
        }

        $this->ragIndex = $docs;
    }

    public function parseDate(string $str): ?CarbonImmutable
    {
        $clean = trim($str);
        if ($clean === '') {
            return null;
        }

        // Ekstrak pola tanggal YYYY-MM-DD
        if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/u', $clean, $matches)) {
            $clean = $matches[0];
        }
        // Ekstrak pola tanggal DD Month YYYY (misal "29 Januari 2006")
        elseif (preg_match('/\b(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})\b/u', $clean, $matches)) {
            $clean = $matches[0];
        }
        // Ekstrak pola tanggal DD Month tanpa tahun (misal "29 Januari")
        elseif (preg_match('/\b(\d{1,2})\s+([a-zA-Z]+)\b/u', $clean, $matches)) {
            $clean = $matches[0];
        }

        // Terjemahkan nama bulan Indonesia ke Inggris untuk Carbon/strtotime
        $idToEn = [
            'januari' => 'January',
            'februari' => 'February',
            'pebruari' => 'February',
            'maret' => 'March',
            'april' => 'April',
            'mei' => 'May',
            'juni' => 'June',
            'juli' => 'July',
            'agustus' => 'August',
            'september' => 'September',
            'oktober' => 'October',
            'nopember' => 'November',
            'november' => 'November',
            'desember' => 'December',
        ];

        $normalized = str_ireplace(array_keys($idToEn), array_values($idToEn), $clean);

        try {
            return CarbonImmutable::parse($normalized);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Profil lengkap karakter kelahiran (Zodiak Bintang, Numerologi Garis Hidup, dan Karakter Hari Lahir).
     *
     * @return array<string, mixed>
     */
    public function getBirthProfile(DateTimeInterface $date): array
    {
        $d = (int) $date->format('j');
        $m = (int) $date->format('n');
        $y = (int) $date->format('Y');

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $formattedDate = "{$d} {$monthNames[$m]} {$y}";

        // 1. Zodiak Bintang
        $zodiacName = $this->zodiacFromMonthDay($m, $d);
        $bintangData = null;
        if ($zodiacName !== null) {
            foreach ($this->allBintang() as $b) {
                if (mb_strtolower((string) $b['name']) === mb_strtolower($zodiacName)) {
                    $bintangData = $b;
                    break;
                }
            }
        }

        // 2. Angka Garis Hidup (1-9)
        $lifeNumber = $this->calculateLifePathNumber($date);
        $garisData = $this->findGarisHidup($lifeNumber);

        // 3. Saptawara (Hari Kelahiran)
        $dayOfWeek = (int) $date->format('w'); // 0 = Sunday
        $saptaMap = [
            0 => ['name' => 'Redite (Minggu)', 'code' => 'Redite', 'urip' => 5],
            1 => ['name' => 'Soma (Senin)', 'code' => 'Soma', 'urip' => 4],
            2 => ['name' => 'Anggara (Selasa)', 'code' => 'Anggara', 'urip' => 3],
            3 => ['name' => 'Buda (Rabu)', 'code' => 'Buda', 'urip' => 7],
            4 => ['name' => 'Wraspati (Kamis)', 'code' => 'Wraspati', 'urip' => 8],
            5 => ['name' => 'Sukra (Jumat)', 'code' => 'Sukra', 'urip' => 6],
            6 => ['name' => 'Saniscara (Sabtu)', 'code' => 'Saniscara', 'urip' => 9],
        ];
        $saptaInfo = $saptaMap[$dayOfWeek];
        $allArti = $this->allArtiHari();
        $saptaDesc = $allArti['saptawara'][$saptaInfo['code']] ?? null;

        $profile = [
            'birthdate' => $date->format('Y-m-d'),
            'birthdate_formatted' => $formattedDate,
            'bintang' => $bintangData,
            'garis_hidup' => $garisData,
            'hari_lahir' => [
                'nama' => $saptaInfo['name'],
                'urip' => $saptaInfo['urip'],
                'karakter' => $saptaDesc,
            ],
            'ringkasan_karakter' => sprintf(
                'Kelahiran %s berbintang %s (%s) dengan Angka Garis Hidup %d. Lahir pada hari %s.',
                $formattedDate,
                $bintangData['name'] ?? '-',
                $bintangData['dates'] ?? '-',
                $lifeNumber,
                $saptaInfo['name']
            ),
        ];

        // Jika ada di kalender (2020-2035), tambahkan detail wuku & wewaran lengkap
        if ($this->calendar !== null) {
            $year = (int) $date->format('Y');
            if ($year >= 2020 && $year <= 2035) {
                try {
                    $day = $this->calendar->day($date->format('Y-m-d'));
                    if ($day !== null) {
                        $lintangName = $day->paringkelan['lintang'] ?? ($day->wewaran['lintang']->name ?? null);
                        $profile['kalender_bali'] = [
                            'wuku' => $day->wuku->name,
                            'wuku_karakter' => $allArti['wuku'][$day->wuku->name] ?? null,
                            'pancawara' => $day->pancawara(),
                            'pancawara_karakter' => $allArti['pancawara'][$day->pancawara()] ?? null,
                            'lintang' => $lintangName,
                            'lintang_karakter' => $lintangName ? ($allArti['lintang'][$lintangName] ?? null) : null,
                        ];
                    }
                } catch (\Throwable) {
                    // Abaikan jika database kalender tidak tersedia atau di luar cakupan
                }
            }
        }

        return $profile;
    }

    /**
     * Hitung Angka Garis Hidup (1-9) dari tanggal lahir.
     */
    public function calculateLifePathNumber(DateTimeInterface $date): int
    {
        $d = (int) $date->format('j');
        $m = (int) $date->format('n');
        $y = (int) $date->format('Y');

        $sum = $this->reduceDigits($d) + $this->reduceDigits($m) + $this->reduceDigits($y);

        return $this->reduceDigits($sum);
    }

    private function reduceDigits(int $n): int
    {
        while ($n > 9) {
            $sum = 0;
            foreach (str_split((string) $n) as $digit) {
                $sum += (int) $digit;
            }
            $n = $sum;
        }

        return max(1, $n);
    }

    /**
     * Tentukan nama zodiak barat dari bulan dan hari.
     */
    private function zodiacFromMonthDay(int $m, int $d): ?string
    {
        return match ($m) {
            1 => $d <= 19 ? 'Capricorn' : 'Aquarius',
            2 => $d <= 18 ? 'Aquarius' : 'Pisces',
            3 => $d <= 20 ? 'Pisces' : 'Aries',
            4 => $d <= 19 ? 'Aries' : 'Taurus',
            5 => $d <= 20 ? 'Taurus' : 'Gemini',
            6 => $d <= 20 ? 'Gemini' : 'Cancer',
            7 => $d <= 22 ? 'Cancer' : 'Leo',
            8 => $d <= 22 ? 'Leo' : 'Virgo',
            9 => $d <= 22 ? 'Virgo' : 'Libra',
            10 => $d <= 22 ? 'Libra' : 'Scorpio',
            11 => $d <= 21 ? 'Scorpio' : 'Sagitarius',
            12 => $d <= 21 ? 'Sagitarius' : 'Capricorn',
            default => null,
        };
    }
}
