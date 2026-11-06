<?php

declare(strict_types=1);

namespace App\AI\Tools;

use App\AI\AgentContext;
use App\Data\DewasaMatch;
use App\Wariga\CalendarRepository;
use App\Wariga\DateRanker;
use InvalidArgumentException;

/**
 * `get_day`: detail satu tanggal (wewaran, wuku, sasih, rahinan, dewasa yang boleh tampil + efeknya).
 */
final class GetDayTool implements Tool
{
    public function __construct(
        private readonly CalendarRepository $calendar,
        private readonly DateRanker $ranker,
    ) {}

    public function name(): string
    {
        return 'get_day';
    }

    public function declaration(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Mengambil detail kalender Bali satu tanggal (2020-2035): wewaran, wuku, sasih, penanggal/pangelong, rahinan, dan dewasa. Untuk ramalan/karakter kelahiran seseorang (tahun berapa pun seperti 2006, 2001, dll.), gunakan consult_knowledge.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'date' => ['type' => 'STRING', 'description' => 'Tanggal YYYY-MM-DD.'],
                ],
                'required' => ['date'],
            ],
        ];
    }

    public function status(array $args): string
    {
        return 'Membaca detail tanggal '.(string) ($args['date'] ?? '?');
    }

    public function run(array $args, AgentContext $context): array
    {
        $date = (string) ($args['date'] ?? '');
        $this->ranker->validateRange($date, $date);
        $day = $this->calendar->day($date);
        if ($day === null) {
            throw new InvalidArgumentException('Tanggal belum tersedia di kalender Rahina.');
        }

        $dewasa = $this->calendar->dewasa($date);
        $support = [];
        $block = [];
        foreach ($dewasa as $d) {
            $effects = array_column($d->effects, 'effect');
            if (in_array('baik', $effects, true)) {
                $support[] = ['id' => $d->ruleId, 'name' => $d->name];
            }
            if (in_array('tidak_baik', $effects, true)) {
                $block[] = ['id' => $d->ruleId, 'name' => $d->name];
            }
        }

        $context->addDate([
            'date' => $date,
            'saptawara' => $day->saptawara(),
            'pancawara' => $day->pancawara(),
            'wuku' => $day->wuku->name,
            'sasih' => $day->sasih->name,
            'rahinan' => array_column($day->rahinan, 'name'),
            'score' => null,
            'support' => $support,
            'block' => $block,
            'origin' => 'get_day',
        ]);

        return [
            'date' => $date,
            'saptawara' => $day->saptawara(),
            'pancawara' => $day->pancawara(),
            'wuku' => $day->wuku->name,
            'sasih' => $day->sasih->name.($day->isNampihSasih ? ' (nampih)' : ''),
            'sasih_day' => ($day->isPenanggal ? 'penanggal ' : 'pangelong ').$day->sasihDay,
            'purnama' => $day->isPurnama,
            'tilem' => $day->isTilem,
            'rahinan' => array_column($day->rahinan, 'name'),
            'dewasa' => array_map(fn (DewasaMatch $d): array => [
                'id' => $d->ruleId,
                'code' => $d->code,
                'name' => $d->name,
                'verdict' => $d->verdict,
                'alahing_level' => $d->alahingLevel,
                'effects' => array_map(fn (array $e): array => ['category' => $e['category'], 'effect' => $e['effect']], $d->effects),
            ], $dewasa),
        ];
    }
}
