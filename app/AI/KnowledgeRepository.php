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
 * 2. Numerologi Garis Hidup (Life Path 1-9)
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
        $rawSisa = $totalUrip % 16;
        $sisa = $rawSisa === 0 ? 16 : $rawSisa;

        $jodohData = $this->allJodoh();
        $rule = null;
        foreach ($jodohData['rules'] ?? [] as $r) {
            if ((int) $r['sisa'] === $sisa || ((int) $r['sisa'] === 0 && $sisa === 16)) {
                $rule = $r;
                break;
            }
        }

        $verdict = $rule['verdict'] ?? 'campur';
        $kategoriLabel = match ($verdict) {
            'ayu' => 'Baik / Rukun & Bahagia',
            'campur' => 'Suka Duka Berimbang',
            default => 'Kurang Baik / Perlu Kehati-hatian',
        };

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
            'sisa_label' => $sisa === 16 ? 'Sisa 16 (0)' : "Sisa {$sisa}",
            'ramalan' => $rule['ramalan'] ?? 'Tidak ada data spesifik untuk sisa ini.',
            'verdict' => $verdict,
            'kategori' => $kategoriLabel,
            'cultural_notice' => true,
            'source' => 'Lontar Tri Pramana (Kalender Bali)',
        ];
    }

    /**
     * Hitung urip (Pancawara, Sadwara, Saptawara) untuk satu tanggal.
     * Menggunakan komputasi kalender Pawukon deterministik yang berlaku untuk tahun berapa pun.
     *
     * @return array{saptawara: string, saptawara_urip: int, pancawara: string, pancawara_urip: int, sadwara: string, sadwara_urip: int, total: int}
     */
    public function getUripForDate(string $date): array
    {
        $pancawaraUrip = ['Umanis' => 5, 'Paing' => 9, 'Pon' => 7, 'Wage' => 4, 'Kliwon' => 8];
        $sadwaraUrip = ['Tungleh' => 7, 'Aryang' => 6, 'Urukung' => 5, 'Paniron' => 8, 'Was' => 9, 'Maulu' => 3];
        $saptawaraUrip = ['Redite' => 5, 'Soma' => 4, 'Anggara' => 3, 'Buda' => 7, 'Wraspati' => 8, 'Sukra' => 6, 'Saniscara' => 9];

        $pancawaraList = ['Umanis', 'Paing', 'Pon', 'Wage', 'Kliwon'];
        $sadwaraList = ['Tungleh', 'Aryang', 'Urukung', 'Paniron', 'Was', 'Maulu'];
        $saptaNames = ['Redite', 'Soma', 'Anggara', 'Buda', 'Wraspati', 'Sukra', 'Saniscara'];

        $carbon = CarbonImmutable::parse($date);
        $sapta = null;
        $panca = null;
        $sad = null;

        // 1. Jika tahun berada pada database kalender aktif (2020-2035), baca langsung
        $year = (int) $carbon->format('Y');
        if ($this->calendar !== null && $year >= 2020 && $year <= 2035) {
            try {
                $day = $this->calendar->day($carbon->toDateString());
                if ($day !== null) {
                    $sapta = $day->saptawara() !== '' ? $day->saptawara() : null;
                    $panca = $day->pancawara() !== '' ? $day->pancawara() : null;
                    $sad = $day->wewaran['sadwara']->name ?? null;
                }
            } catch (\Throwable) {
                // Abaikan jika koneksi db kalender tidak siap
            }
        }

        // 2. Hitung matematis Pawukon Bali deterministik (referensi baku 2000-01-01)
        // 2000-01-01: Saniscara (6), Umanis (0), Paniron (3)
        if ($sapta === null) {
            $sapta = $saptaNames[(int) $carbon->format('w')];
        }

        if ($panca === null || $sad === null) {
            $ref = CarbonImmutable::parse('2000-01-01');
            $diffDays = (int) $ref->diffInDays($carbon, false);

            if ($panca === null) {
                $pancaIdx = ((($diffDays % 5) + 5) % 5);
                $panca = $pancawaraList[$pancaIdx];
            }

            if ($sad === null) {
                $sadIdx = (((($diffDays + 3) % 6) + 6) % 6);
                $sad = $sadwaraList[$sadIdx];
            }
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

    /**
     * Ekstrak semua tanggal yang ada di dalam teks input (format ISO YYYY-MM-DD, DD/MM/YYYY, atau DD Bulan YYYY).
     *
     * @return list<CarbonImmutable>
     */
    public function parseAllDates(string $str): array
    {
        $clean = trim($str);
        if ($clean === '') {
            return [];
        }

        $idToEn = [
            'januari' => 'January', 'februari' => 'February', 'pebruari' => 'February',
            'maret' => 'March', 'april' => 'April', 'mei' => 'May',
            'juni' => 'June', 'juli' => 'July', 'agustus' => 'August',
            'september' => 'September', 'oktober' => 'October',
            'nopember' => 'November', 'november' => 'November', 'desember' => 'December',
        ];

        $dates = [];
        $seen = [];

        // 1. Pola ISO YYYY-MM-DD atau YYYY/MM/DD
        if (preg_match_all('/\b(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})\b/u', $clean, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                try {
                    $c = CarbonImmutable::createFromDate((int) $m[1], (int) $m[2], (int) $m[3]);
                    $iso = $c->toDateString();
                    if (! isset($seen[$iso])) {
                        $seen[$iso] = true;
                        $dates[] = $c;
                    }
                } catch (\Throwable) {
                }
            }
        }

        // 2. Pola DD Bulan YYYY (misal "15 Mei 1998" atau "20 Oktober 1999")
        $monthPattern = implode('|', array_keys($idToEn));
        if (preg_match_all('/\b(\d{1,2})\s+('.$monthPattern.')\s+(\d{4})\b/iu', $clean, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $enMonth = $idToEn[mb_strtolower($m[2])];
                try {
                    $c = CarbonImmutable::parse("{$m[1]} {$enMonth} {$m[3]}");
                    $iso = $c->toDateString();
                    if (! isset($seen[$iso])) {
                        $seen[$iso] = true;
                        $dates[] = $c;
                    }
                } catch (\Throwable) {
                }
            }
        }

        // 3. Pola DD-MM-YYYY atau DD/MM/YYYY
        if (preg_match_all('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\b/u', $clean, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                try {
                    $c = CarbonImmutable::createFromDate((int) $m[3], (int) $m[2], (int) $m[1]);
                    $iso = $c->toDateString();
                    if (! isset($seen[$iso])) {
                        $seen[$iso] = true;
                        $dates[] = $c;
                    }
                } catch (\Throwable) {
                }
            }
        }

        return $dates;
    }

    public function parseDate(string $str): ?CarbonImmutable
    {
        $all = $this->parseAllDates($str);
        if ($all !== []) {
            return $all[0];
        }

        $clean = trim($str);
        if ($clean === '') {
            return null;
        }

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

        // Ekstrak pola tanggal DD Month tanpa tahun (misal "29 Januari")
        $monthPattern = implode('|', array_keys($idToEn));
        if (preg_match('/\b(\d{1,2})\s+('.$monthPattern.')\b/iu', $clean, $matches)) {
            $enMonth = $idToEn[mb_strtolower($matches[2])];
            try {
                return CarbonImmutable::parse("{$matches[1]} {$enMonth}");
            } catch (\Throwable) {
                return null;
            }
        }

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

        $uripInfo = $this->getUripForDate($date->format('Y-m-d'));
        $pancaName = $uripInfo['pancawara'];
        $pancaDesc = $allArti['pancawara'][$pancaName] ?? null;
        $sadName = $uripInfo['sadwara'];
        $sadDesc = $allArti['sadwara'][$sadName] ?? null;

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
            'pancawara' => [
                'nama' => $pancaName,
                'urip' => $uripInfo['pancawara_urip'],
                'karakter' => $pancaDesc,
            ],
            'sadwara' => [
                'nama' => $sadName,
                'urip' => $uripInfo['sadwara_urip'],
                'karakter' => $sadDesc,
            ],
            'ringkasan_karakter' => sprintf(
                'Kelahiran %s berbintang %s (%s) dengan Angka Garis Hidup %d. Lahir pada hari %s %s.',
                $formattedDate,
                $bintangData['name'] ?? '-',
                $bintangData['dates'] ?? '-',
                $lifeNumber,
                $saptaInfo['name'],
                $pancaName
            ),
        ];

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
