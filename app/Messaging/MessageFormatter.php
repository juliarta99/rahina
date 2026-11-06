<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Data\AgentAnswer;
use App\Data\CalendarDay;
use App\Data\DailyBrief;
use Carbon\CarbonImmutable;

/**
 * Format teks WhatsApp (docs/WHATSAPP.md §4). Hanya format: isi poin berasal dari ringkasan yang sudah lewat
 * OutputGuard / template dan dari hasil tool agent. Tanpa emoji, satu tautan per pesan.
 */
final class MessageFormatter
{
    private const OPENINGS = ['Rahajeng semeng.', 'Selamat pagi.', 'Selamat pagi dari Rahina.'];

    private const CLOSINGS = [
        'Balas pesan ini untuk bertanya hari baik. Ketik BERHENTI untuk berhenti.',
        'Ingin tahu hari baik untuk kegiatan Anda? Balas pesan ini. Ketik BERHENTI untuk berhenti.',
        'Balas dengan pertanyaan, mis. "hari baik menanam padi bulan depan". Ketik BERHENTI untuk berhenti.',
    ];

    public const VERDICT_LABEL = ['ayu' => 'Baik', 'ala' => 'Hindari', 'campur' => 'Pertimbangkan', 'info' => 'Info'];

    /**
     * @param  list<string>  $personal
     */
    public function morning(DailyBrief $brief, CalendarDay $day, array $personal): string
    {
        $date = CarbonImmutable::parse($day->date);
        $variant = $date->dayOfYear;

        $lines = [
            self::OPENINGS[$variant % count(self::OPENINGS)],
            '*Rahina · '.$date->locale('id')->translatedFormat('l, j F Y').'*',
            "{$day->saptawara()} {$day->pancawara()} · Wuku {$day->wuku->name} · Sasih {$day->sasih->name}",
            '',
            '*Hari ini secara umum*',
        ];
        foreach ($brief->points as $p) {
            $label = self::VERDICT_LABEL[$p['verdict']] ?? 'Info';
            $lines[] = "- [{$label}] {$p['text']}";
        }
        if ($brief->rahinanNote !== null) {
            $lines[] = "- {$brief->rahinanNote}";
        }
        if ($personal !== []) {
            $lines[] = '';
            $lines[] = '*Untuk Anda*';
            foreach ($personal as $note) {
                $lines[] = "- {$note}";
            }
        }
        $lines[] = '';
        $lines[] = 'Detail: '.self::url("/hari/{$day->date}");
        $lines[] = '_Menurut perhitungan wariga. Untuk upacara besar, konsultasikan dengan sulinggih atau pemangku._';
        $lines[] = self::CLOSINGS[$variant % count(self::CLOSINGS)];

        return self::clean(implode("\n", $lines));
    }

    public function answer(AgentAnswer $answer): string
    {
        $lines = [$answer->answer];

        if ($answer->needsClarification && $answer->clarificationOptions !== []) {
            $lines[] = '';
            $lines[] = 'Pilihan kategori: '.implode(', ', array_column($answer->clarificationOptions, 'name')).'.';
            $lines[] = 'Kirim ulang pertanyaan dengan menyebut kegiatannya, mis. "hari baik menanam padi bulan Desember".';
        }

        foreach (array_slice($answer->dates, 0, 5) as $d) {
            $date = CarbonImmutable::parse($d['date'])->locale('id')->translatedFormat('D, j M Y');
            $label = self::VERDICT_LABEL[$d['verdict']] ?? 'Info';
            $lines[] = "- *{$date}* ({$d['saptawara']} {$d['pancawara']}, Wuku {$d['wuku']}) · {$label}: {$d['reason']}";
        }
        if ($answer->dates !== []) {
            $lines[] = '';
            $lines[] = 'Detail: '.self::url('/hari/'.$answer->dates[0]['date']);
        }
        if ($answer->culturalNotice) {
            $lines[] = '_Untuk upacara besar, mohon konsultasikan dewasa dengan sulinggih atau pemangku setempat._';
        }

        return self::clean(implode("\n", $lines));
    }

    public static function help(): string
    {
        return implode("\n", [
            '*Rahina · Bantuan*',
            '- Kirim pertanyaan, mis. "hari baik buka warung bulan depan".',
            '- BERHENTI: berhenti menerima ringkasan pagi.',
            '- MULAI: aktifkan lagi ringkasan pagi.',
            '- HAPUS DATA: hapus nomor & data Anda dari Rahina.',
        ]);
    }

    private static function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    /** Buang emoji & spasi berlebih; batasi panjang (WhatsApp nyaman dibaca < 1.500 karakter). */
    private static function clean(string $text): string
    {
        $text = (string) preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text);
        $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);

        return mb_substr(trim($text), 0, 1500);
    }
}
