<?php

declare(strict_types=1);

namespace App\Wariga;

use Illuminate\Support\Facades\DB;

/**
 * Memetakan kalimat bebas ("nanam singkong", "buka warung") ke `activity_categories`
 * memakai pencarian leksikal (AI_IMPLEMENTATION.md §4). Tanpa AI: bila hasilnya tidak yakin,
 * pemanggil (agent) memilih dari daftar kategori resmi atau bertanya balik.
 *
 * Postgres: `pg_trgm` (similarity / word_similarity). Driver lain (SQLite test): pembanding trigram di PHP
 * dengan rumus yang sama, sehingga perilaku test mendekati produksi.
 */
final class ActivityMatcher
{
    /** Skor minimal & selisih dengan peringkat 2 agar hasil dianggap yakin (AI_IMPLEMENTATION.md §4.3). */
    public const CONFIDENT_SCORE = 0.60;

    public const CONFIDENT_GAP = 0.10;

    public function __construct(private readonly CategoryCatalog $catalog) {}

    private const STOPWORDS = [
        'hari', 'baik', 'bagus', 'ayu', 'dewasa', 'untuk', 'utk', 'kapan', 'yang', 'yg', 'tanggal', 'buat',
        'mau', 'ingin', 'akan', 'saya', 'kami', 'kita', 'aku', 'apa', 'apakah', 'bulan', 'tahun', 'ini', 'depan',
        'di', 'ke', 'pada', 'dan', 'atau', 'cocok', 'tepat', 'waktu', 'sebaiknya', 'minggu', 'besok', 'lusa',
        'januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober',
        'november', 'desember', 'tolong', 'dong', 'ya',
    ];

    /**
     * @return list<array{code: string, name: string, domain: string, score: float, requires_cultural_notice: bool}>
     */
    public function match(string $text, int $limit = 3): array
    {
        $query = self::normalize($text);
        if ($query === '') {
            return [];
        }

        $scores = DB::getDriverName() === 'pgsql' ? $this->scoresPgsql($query) : $this->scoresPhp($query);
        // Skor tertinggi dulu; bila seri, kategori yang lebih spesifik (anak) didahulukan.
        $depth = fn (int $id): int => substr_count($this->catalog->byId($id)['code'] ?? '', '.');
        uksort($scores, fn (int $a, int $b): int => [$scores[$b], $depth($b)] <=> [$scores[$a], $depth($a)]);
        $scores = array_slice($scores, 0, $limit, true);
        if ($scores === []) {
            return [];
        }

        $result = [];
        foreach ($scores as $id => $score) {
            $c = $this->catalog->byId($id);
            if ($c === null) {
                continue;
            }
            $result[] = [
                'code' => $c['code'],
                'name' => $c['name'],
                'domain' => $c['domain'],
                'score' => round($score, 2),
                'requires_cultural_notice' => $c['requires_cultural_notice'],
            ];
        }

        return $result;
    }

    /**
     * Apakah peringkat 1 cukup yakin untuk langsung dipakai.
     *
     * @param  list<array{score: float}>  $matches
     */
    public static function isConfident(array $matches): bool
    {
        if ($matches === [] || $matches[0]['score'] < self::CONFIDENT_SCORE) {
            return false;
        }

        // Pesaing dari keluarga yang sama (induk/anak, mis. pertanian vs pertanian.padi) bukan tanda ambigu.
        foreach (array_slice($matches, 1) as $other) {
            if (! self::sameFamily($matches[0]['code'], $other['code'])) {
                return $matches[0]['score'] - $other['score'] >= self::CONFIDENT_GAP;
            }
        }

        return true;
    }

    private static function sameFamily(string $a, string $b): bool
    {
        return str_starts_with($a, $b.'.') || str_starts_with($b, $a.'.');
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = (string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $words = array_filter(
            preg_split('/\s+/u', $text) ?: [],
            fn (string $w): bool => $w !== '' && ! in_array($w, self::STOPWORDS, true) && ! is_numeric($w),
        );

        return implode(' ', $words);
    }

    /**
     * @return array<int, float> category_id → skor
     */
    private function scoresPgsql(string $query): array
    {
        $rows = DB::select(<<<'SQL'
            SELECT category_id, MAX(GREATEST(similarity(phrase, ?), word_similarity(phrase, ?))) AS score
            FROM (
                SELECT category_id, phrase FROM activity_aliases
                UNION ALL
                SELECT id AS category_id, lower(name) AS phrase FROM activity_categories
            ) candidates
            WHERE similarity(phrase, ?) > 0.2 OR word_similarity(phrase, ?) > 0.3
            GROUP BY category_id
            SQL, [$query, $query, $query, $query]);

        $scores = [];
        foreach ($rows as $r) {
            $scores[(int) $r->category_id] = (float) $r->score;
        }

        return $scores;
    }

    /**
     * @return array<int, float>
     */
    private function scoresPhp(string $query): array
    {
        $candidates = DB::table('activity_aliases')->get(['category_id', 'phrase'])
            ->map(fn (object $r): array => [(int) $r->category_id, (string) $r->phrase])->all();
        foreach ($this->catalog->all() as $c) {
            $candidates[] = [$c['id'], mb_strtolower($c['name'])];
        }

        $scores = [];
        foreach ($candidates as [$id, $phrase]) {
            $score = max(self::similarity($phrase, $query), self::wordSimilarity($phrase, $query));
            if ($score > 0.3) {
                $scores[$id] = max($scores[$id] ?? 0.0, $score);
            }
        }

        return $scores;
    }

    /** Setara `similarity()` pg_trgm: |A∩B| / |A∪B| atas himpunan trigram. */
    private static function similarity(string $a, string $b): float
    {
        $ta = self::trigrams($a);
        $tb = self::trigrams($b);
        $union = count(array_unique([...$ta, ...$tb]));

        return $union === 0 ? 0.0 : count(array_intersect($ta, $tb)) / $union;
    }

    /** Pendekatan `word_similarity(phrase, query)`: kemiripan terbaik frasa terhadap potongan kata di query. */
    private static function wordSimilarity(string $phrase, string $query): float
    {
        $words = explode(' ', $query);
        $len = max(1, count(explode(' ', $phrase)));
        $best = 0.0;
        for ($i = 0; $i < count($words); $i++) {
            for ($n = 1; $n <= $len + 1 && $i + $n <= count($words); $n++) {
                $best = max($best, self::similarity($phrase, implode(' ', array_slice($words, $i, $n))));
            }
        }

        return $best;
    }

    /**
     * @return list<string>
     */
    private static function trigrams(string $text): array
    {
        $grams = [];
        foreach (explode(' ', $text) as $word) {
            if ($word === '') {
                continue;
            }
            $padded = '  '.$word.' ';
            $chars = mb_str_split($padded);
            for ($i = 0; $i + 2 < count($chars); $i++) {
                $grams[] = $chars[$i].$chars[$i + 1].$chars[$i + 2];
            }
        }

        return array_values(array_unique($grams));
    }
}
