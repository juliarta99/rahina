<?php

declare(strict_types=1);

namespace App\AI;

use App\Data\AgentAnswer;
use Illuminate\Support\Facades\DB;

/**
 * Jawaban Tanya Wariga TANPA LLM, disusun langsung dari hasil tool di AgentContext.
 * Dipakai saat model gagal (timeout, 429, JSON rusak) atau semua tanggal model dibuang guard.
 */
final class AnswerTemplate
{
    public const OUT_OF_SCOPE = 'Maaf, Tanya Wariga hanya menjawab pertanyaan seputar kalender Bali dan hari baik. Contoh: "Hari baik menanam padi bulan depan?"';

    public const CLARIFY = 'Aktivitas ini bisa masuk beberapa kategori wariga. Pilih yang paling sesuai agar tanggalnya tepat.';

    public const UNAVAILABLE = 'Maaf, Tanya Wariga belum bisa menjawab sekarang. Silakan coba lagi sebentar lagi, atau buka kalender untuk melihat dewasa per tanggal.';

    public const QUOTA = 'Tanya Wariga sedang mencapai batas pemakaian layanan untuk hari ini. Silakan coba lagi nanti; kalender dan detail dewasa per tanggal tetap bisa dibuka.';

    public const NO_RESULT = 'Menurut data perhitungan wariga Rahina, belum ada tanggal dengan dewasa pendukung untuk kegiatan itu pada rentang yang diminta. Coba rentang lain.';

    /**
     * @param  list<string>  $dropped
     * @param  string|null  $unavailableText  pesan bila tidak ada hasil tool (mis. kuota habis)
     */
    public function fromContext(AgentContext $context, array $dropped = [], ?string $unavailableText = null): AgentAnswer
    {
        $dates = [];
        foreach (array_slice($context->rankedOrder, 0, 5) as $date) {
            $known = $context->dates[$date] ?? null;
            if ($known !== null) {
                $dates[] = $this->dateCard($known, $this->reason($known));
            }
        }

        $searched = array_filter($context->toolCalls, fn (array $c): bool => $c['name'] === 'find_good_days' && $c['ok']);
        $text = $dates !== [] ? $this->summary($context) : ($searched !== [] ? self::NO_RESULT : ($unavailableText ?? self::UNAVAILABLE));

        return new AgentAnswer(
            answer: $text,
            dates: $dates,
            needsClarification: false,
            clarificationOptions: [],
            culturalNotice: $context->requiresCulturalNotice(),
            outOfScope: false,
            generatedBy: AgentAnswer::BY_TEMPLATE,
            droppedDates: array_values(array_unique($dropped)),
            steps: $context->steps,
            sourceCount: self::sourceCount($dates),
        );
    }

    public function summary(AgentContext $context): string
    {
        $names = array_column($context->categories, 'name');

        return $names === []
            ? 'Menurut perhitungan wariga, berikut tanggal yang didukung data dewasa.'
            : 'Menurut perhitungan wariga, berikut tanggal dengan dewasa pendukung untuk '.mb_strtolower(implode(', ', array_slice($names, 0, 2))).'.';
    }

    /**
     * @param  array{support: list<array{id: int, name: string}>, block: list<array{id: int, name: string}>}  $known
     */
    public function reason(array $known): string
    {
        $support = array_column($known['support'], 'name');
        $block = array_column($known['block'], 'name');
        if ($support === []) {
            return $block === [] ? 'Belum ada dewasa tervalidasi pada tanggal ini.' : 'Ada penghalang: '.implode(', ', array_slice($block, 0, 2)).'.';
        }
        $text = 'Didukung '.implode(', ', array_slice($support, 0, 2));

        return mb_substr($text.($block === [] ? '.' : ', namun ada penghalang.'), 0, 160);
    }

    /**
     * Kartu tanggal untuk UI (DateRefCard). Semua isinya dari hasil tool.
     *
     * @param  array{date: string, saptawara: string, pancawara: string, wuku: string, sasih: string, rahinan: list<string>, support: list<array{id: int, name: string}>, block: list<array{id: int, name: string}>}  $known
     * @return array{date: string, saptawara: string, pancawara: string, wuku: string, sasih: string, rahinan: list<string>, verdict: string, reason: string, support: list<array{id: int, name: string}>, block: list<array{id: int, name: string}>}
     */
    public function dateCard(array $known, string $reason): array
    {
        $verdict = $known['support'] === [] ? ($known['block'] === [] ? 'info' : 'ala') : ($known['block'] === [] ? 'ayu' : 'campur');

        return [
            'date' => $known['date'],
            'saptawara' => $known['saptawara'],
            'pancawara' => $known['pancawara'],
            'wuku' => $known['wuku'],
            'sasih' => $known['sasih'],
            'rahinan' => $known['rahinan'],
            'verdict' => $verdict,
            'reason' => $reason,
            'support' => $known['support'],
            'block' => $known['block'],
        ];
    }

    /**
     * @param  list<array{support: list<array{id: int}>, block: list<array{id: int}>}>  $dates
     */
    public static function sourceCount(array $dates): int
    {
        $ids = [];
        foreach ($dates as $d) {
            array_push($ids, ...array_column($d['support'], 'id'), ...array_column($d['block'], 'id'));
        }

        return $ids === [] ? 0 : DB::table('dewasa_rules')->whereIn('id', array_unique($ids))->distinct()->count('source_id');
    }
}
