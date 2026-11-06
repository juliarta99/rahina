<?php

declare(strict_types=1);

use App\Actions\AskWarigaAction;
use App\AI\AnswerTemplate;
use App\AI\GeminiClient;
use App\Data\AgentAnswer;
use App\Wariga\ActivityMatcher;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    Sleep::fake();
    seedReferenceCalendar();
});

/**
 * Respons Gemini berisi satu functionCall.
 *
 * @param  array<string, mixed>  $args
 * @return array<string, mixed>
 */
function geminiCall(string $name, array $args): array
{
    return ['candidates' => [[
        'content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => $name, 'args' => $args], 'thoughtSignature' => 'sig-test']]],
        'finishReason' => 'STOP',
    ]], 'modelVersion' => 'test-model-001'];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function finalAnswer(array $overrides = []): array
{
    return geminiFixture('text_response', array_merge([
        'answer' => 'Menurut perhitungan wariga, ada hari baik untuk menanam padi.',
        'dates' => [['date' => '2025-04-23', 'reason' => 'Didukung [[DEWASA:SINTETIS_A]].', 'support_rule_ids' => [ruleId('test_sintetis_buda_kliwon')], 'block_rule_ids' => []]],
        'needs_clarification' => false,
        'clarification_options' => [],
        'cultural_notice' => false,
        'out_of_scope' => false,
    ], $overrides));
}

function findPadi(string $start = '2025-04-01', string $end = '2025-05-31'): array
{
    return geminiCall('find_good_days', ['category_codes' => ['pertanian.padi'], 'start' => $start, 'end' => $end]);
}

function ask(string $question = 'Hari baik menanam padi April 2025?', ?string $category = null): AgentAnswer
{
    return app(AskWarigaAction::class)->execute($question, $category);
}

it('menjawab dengan tanggal dari tool, lengkap dengan dewasa pendukung dari data', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(finalAnswer())]);

    $answer = ask();

    expect($answer->generatedBy)->toBe('ai')
        ->and($answer->dates)->toHaveCount(1)
        ->and($answer->dates[0]['date'])->toBe('2025-04-23')
        ->and($answer->dates[0]['verdict'])->toBe('ayu')
        ->and($answer->dates[0]['support'][0]['name'])->toBe('[[DEWASA:SINTETIS_A]]')
        ->and($answer->dates[0]['saptawara'])->toBe('Buda')
        ->and($answer->sourceCount)->toBe(1)
        ->and($answer->steps[0])->toContain('2025-04-01');

    // Langkah 2 mengirim balik konten model (dengan thoughtSignature) + functionResponse hasil tool.
    Http::assertSent(fn (Request $r): bool => count($r['contents']) === 3
        && $r['contents'][1]['parts'][0]['thoughtSignature'] === 'sig-test'
        && $r['contents'][2]['parts'][0]['functionResponse']['response']['days'][0]['date'] === '2025-04-23');

    $log = DB::table('ai_interactions')->where('feature', 'ask')->first();
    expect($log->status)->toBe('ok')
        ->and(json_decode($log->returned_dates, true))->toBe(['2025-04-23']);
});

it('membuang tanggal halusinasi dan mencatatnya di dropped_dates', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(finalAnswer(['dates' => [
        ['date' => '2025-04-23', 'reason' => 'Baik.', 'support_rule_ids' => [], 'block_rule_ids' => []],
        ['date' => '2025-04-24', 'reason' => 'Karangan model.', 'support_rule_ids' => [999], 'block_rule_ids' => []],
    ]]))]);

    $answer = ask();

    expect(array_column($answer->dates, 'date'))->toBe(['2025-04-23'])
        ->and($answer->droppedDates)->toBe(['2025-04-24'])
        ->and(json_decode((string) DB::table('ai_interactions')->value('dropped_dates'), true))->toBe(['2025-04-24']);
});

it('menyusun jawaban template dari hasil tool bila semua tanggal model dibuang', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(finalAnswer([
        'answer' => 'Tanggal 24 April 2025 paling baik.',
        'dates' => [['date' => '2025-04-24', 'reason' => 'x', 'support_rule_ids' => [], 'block_rule_ids' => []]],
    ]))]);

    $answer = ask();

    expect($answer->generatedBy)->toBe('template')
        ->and(array_column($answer->dates, 'date'))->toBe(['2025-04-23'])
        ->and($answer->answer)->not->toContain('24 April')
        ->and($answer->droppedDates)->toBe(['2025-04-24']);
});

it('mengganti teks yang menyebut tanggal di luar hasil tool', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(finalAnswer([
        'answer' => 'Pilih 2025-04-30 atau tanggal di bawah.',
    ]))]);

    $answer = ask();

    expect($answer->answer)->not->toContain('2025-04-30')
        ->and($answer->dates[0]['date'])->toBe('2025-04-23');
});

it('mengganti alasan yang menyebut "hari ini" untuk tanggal lain', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(finalAnswer(['dates' => [
        ['date' => '2025-04-23', 'reason' => 'Hari ini sangat baik.', 'support_rule_ids' => [], 'block_rule_ids' => []],
    ]]))]);

    expect(ask()->dates[0]['reason'])->toBe('Didukung [[DEWASA:SINTETIS_A]].');
});

it('mengirim error tool ke model sebagai functionResponse.error agar dijelaskan', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(findPadi('2025-01-01', '2025-12-31'))
        ->push(finalAnswer(['answer' => 'Rentang terlalu panjang, persempit menjadi satu bulan.', 'dates' => []]))]);

    $answer = ask('Hari baik menanam padi sepanjang 2025?');

    Http::assertSent(fn (Request $r): bool => isset($r['contents'][2])
        && str_contains((string) ($r['contents'][2]['parts'][0]['functionResponse']['response']['error'] ?? ''), 'Rentang maksimal 92 hari'));
    expect($answer->dates)->toBe([])
        ->and($answer->answer)->toContain('Rentang terlalu panjang');
});

it('memakai template ramah bila Gemini gagal (5xx), tanpa melempar exception', function (): void {
    Http::fake([GEMINI_URL => Http::response(['error' => 'boom'], 503)]);

    $answer = ask();

    expect($answer->generatedBy)->toBe('template')
        ->and($answer->answer)->toBe(AnswerTemplate::UNAVAILABLE)
        ->and(DB::table('ai_interactions')->value('status'))->toBe('fallback');
    Http::assertSentCount(3); // 1 + retry 2×
});

it('menghentikan panggilan Gemini saat kuota harian habis (circuit breaker), tanpa retry', function (): void {
    Http::fake([GEMINI_URL => Http::response(['error' => [
        'code' => 429,
        'status' => 'RESOURCE_EXHAUSTED',
        'message' => 'Quota exceeded for metric: generate_content_free_tier_requests. Generate content requests per day limit exceeded on Free Tier. Please retry in 2h0m5s.',
        'details' => [['@type' => 'type.googleapis.com/google.rpc.RetryInfo', 'retryDelay' => '7205s']],
    ]], 429)]);

    $first = ask();
    $second = ask();

    expect($first->answer)->toBe(AnswerTemplate::QUOTA)
        ->and($second->answer)->toBe(AnswerTemplate::QUOTA)
        ->and(Cache::has(GeminiClient::QUOTA_CACHE_KEY))->toBeTrue()
        ->and(DB::table('ai_interactions')->where('error', 'like', 'quota%')->count())->toBe(2);
    Http::assertSentCount(1); // tidak di-retry, dan pertanyaan kedua tidak memanggil API
});

it('tetap me-retry 429 per menit (bukan kuota harian)', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(['error' => ['code' => 429, 'message' => 'Resource has been exhausted (e.g. check quota).']], 429)
        ->push(findPadi())
        ->push(finalAnswer())]);

    expect(ask()->generatedBy)->toBe('ai');
    Http::assertSentCount(3);
});

it('memakai hasil tool yang sudah ada bila Gemini gagal di tengah agent', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(['error' => 'boom'], 503)->push(['error' => 'boom'], 503)->push(['error' => 'boom'], 503)]);

    $answer = ask();

    expect($answer->generatedBy)->toBe('template')
        ->and(array_column($answer->dates, 'date'))->toBe(['2025-04-23'])
        ->and(DB::table('ai_interactions')->value('error'))->toBe('gemini_http');
});

it('tetap menampilkan hasil tool bila langkah agent habis', function (): void {
    config(['rahina.agent.max_steps' => 1]);
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(findPadi())]);

    $answer = ask();

    expect($answer->generatedBy)->toBe('template')
        ->and(array_column($answer->dates, 'date'))->toBe(['2025-04-23']);
    // Langkah terakhir mematikan pemanggilan tool.
    Http::assertSent(fn (Request $r): bool => ($r['toolConfig']['functionCallingConfig']['mode'] ?? null) === 'NONE');
});

it('tidak menjalankan ulang tool dengan argumen identik dan mengirim thinkingConfig', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(findPadi())->push(finalAnswer())]);

    $answer = ask();

    expect($answer->steps)->toHaveCount(1)
        ->and($answer->dates[0]['date'])->toBe('2025-04-23');
    Http::assertSent(fn (Request $r): bool => ($r['generationConfig']['thinkingConfig']['thinkingLevel'] ?? null) === 'minimal');
});

it('menolak sopan pertanyaan di luar topik', function (): void {
    Http::fake([GEMINI_URL => Http::response(finalAnswer([
        'answer' => 'Maaf, saya hanya menjawab seputar kalender Bali.',
        'dates' => [['date' => '2025-04-23', 'reason' => 'x', 'support_rule_ids' => [], 'block_rule_ids' => []]],
        'out_of_scope' => true,
    ]))]);

    $answer = ask('Resep nasi goreng enak?');

    expect($answer->outOfScope)->toBeTrue()
        ->and($answer->dates)->toBe([])
        ->and(DB::table('ai_interactions')->value('status'))->toBe('refused');
});

it('bertanya balik dengan pilihan kategori resmi saja bila aktivitas ambigu', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('map_activity', ['text' => 'beli motor']))
        ->push(finalAnswer([
            'answer' => 'Kegiatan ini bisa masuk beberapa kategori.',
            'dates' => [],
            'needs_clarification' => true,
            'clarification_options' => ['peralatan', 'dagang.buka_usaha', 'kategori.karangan'],
        ]))]);

    $answer = ask('Kapan hari baik beli motor?');

    expect($answer->needsClarification)->toBeTrue()
        ->and(array_column($answer->clarificationOptions, 'code'))->toBe(['peralatan', 'dagang.buka_usaha'])
        ->and($answer->clarificationOptions[0]['name'])->toBe('Peralatan');
});

it('menegakkan klarifikasi di kode bila pemetaan ragu walau model mencoba menebak', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('map_activity', ['text' => 'beli motor']))
        ->push(geminiCall('find_good_days', ['category_codes' => ['peralatan'], 'start' => '2025-04-01', 'end' => '2025-04-30']))
        ->push(finalAnswer(['answer' => 'Berikut hari baik membeli motor.', 'dates' => []]))]);

    $answer = ask('Kapan hari baik beli motor?');

    Http::assertSent(fn (Request $r): bool => str_contains((string) ($r['contents'][4]['parts'][0]['functionResponse']['response']['error'] ?? ''), 'belum pasti'));
    expect($answer->needsClarification)->toBeTrue()
        ->and($answer->answer)->toBe(AnswerTemplate::CLARIFY)
        ->and($answer->clarificationOptions)->not->toBeEmpty()
        ->and($answer->dates)->toBe([]);
});

it('tetap bertanya balik bila model melewati map_activity untuk aktivitas samar', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('find_good_days', ['category_codes' => ['dagang.buka_usaha'], 'start' => '2025-04-01', 'end' => '2025-04-30']))
        ->push(finalAnswer(['answer' => 'Berikut hari baik memulai proyek.', 'dates' => []]))]);

    $answer = ask('Tanggal baik untuk mulai proyek April 2025');

    expect($answer->needsClarification)->toBeTrue()
        ->and($answer->dates)->toBe([]);
});

it('menganggap induk dan anak kategori yang seri sebagai pemetaan yakin', function (): void {
    $matches = app(ActivityMatcher::class)->match('Hari baik menanam padi bulan Desember');

    expect($matches[0]['code'])->toBe('pertanian.padi')
        ->and(ActivityMatcher::isConfident($matches))->toBeTrue();
});

it('meneruskan kategori pilihan klarifikasi ke model', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(finalAnswer())]);

    ask('Kapan hari baik?', 'pertanian.padi');

    Http::assertSent(fn (Request $r): bool => str_contains($r['contents'][0]['parts'][0]['text'], 'pertanian.padi - Menanam padi'));
});

it('selalu menampilkan catatan budaya untuk kategori upacara besar', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('find_good_days', ['category_codes' => ['upacara.pawiwahan'], 'start' => '2025-04-01', 'end' => '2025-04-30']))
        ->push(finalAnswer(['answer' => 'Belum ada tanggal yang didukung data.', 'dates' => [], 'cultural_notice' => false]))]);

    $answer = ask('Hari baik menikah April 2025?');

    expect($answer->culturalNotice)->toBeTrue();
});

it('mengoreksi JSON rusak sekali lalu menerima perbaikannya', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(findPadi())
        ->push(geminiFixture('text_response', 'ini bukan json'))
        ->push(finalAnswer())]);

    expect(ask()->generatedBy)->toBe('ai');
    Http::assertSentCount(3);
});

it('tidak menyimpan teks pertanyaan atau teks aktivitas di log', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('map_activity', ['text' => 'nanam padi di sawah pak made']))
        ->push(finalAnswer(['dates' => []]))]);

    ask('Nama saya Made, kapan nanam padi di sawah pak made?');

    $row = (array) DB::table('ai_interactions')->first();
    expect(json_encode($row))->not->toContain('Made')->not->toContain('made')
        ->and($row['input_hash'])->toHaveLength(64);
});

it('dapat menjawab ramalan bintang melalui consult_knowledge tool', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('consult_knowledge', ['topic' => 'bintang', 'query' => 'Scorpio']))
        ->push(finalAnswer([
            'answer' => 'Zodiak Scorpio memiliki simbol kalajengking dengan karakter ulet.',
            'dates' => [],
            'cultural_notice' => true,
        ]))]);

    $answer = ask('Bagaimana ramalan bintang Scorpio?');

    expect($answer->generatedBy)->toBe('ai')
        ->and($answer->answer)->toContain('Scorpio')
        ->and($answer->culturalNotice)->toBeTrue();
});

it('meminta klarifikasi kepastian bila pertanyaan jodoh ambigu tanpa tanggal', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('consult_knowledge', ['topic' => 'jodoh']))
        ->push(finalAnswer([
            'answer' => 'Untuk menghitung kecocokan jodoh menurut Lontar Tri Pramana, mohon sebutkan tanggal lahir Anda dan pasangan.',
            'dates' => [],
            'needs_clarification' => true,
        ]))]);

    $answer = ask('Bagaimana kecocokan jodoh saya?');

    expect($answer->needsClarification)->toBeTrue()
        ->and($answer->answer)->toContain('tanggal lahir');
});

it('berhasil menghitung kecocokan jodoh dua tanggal tanpa tertahan guardrail', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('consult_knowledge', [
            'topic' => 'jodoh',
            'birthdate' => '15 Mei 1998',
            'partner_birthdate' => '20 Oktober 1999',
        ]))
        ->push(finalAnswer([
            'answer' => 'Menurut ramalan Lontar Tri Pramana, pasangan kelahiran 15 Mei 1998 dan 20 Oktober 1999 memiliki total urip 39 dengan sisa 7 yang tergolong Baik / Rukun & Bahagia.',
            'dates' => [],
            'cultural_notice' => true,
        ]))]);

    $answer = ask('Bagaimana kecocokan jodoh antara kelahiran 15 Mei 1998 dan 20 Oktober 1999?');

    expect($answer->generatedBy)->toBe('ai')
        ->and($answer->answer)->toContain('15 Mei 1998')
        ->and($answer->answer)->toContain('20 Oktober 1999')
        ->and($answer->culturalNotice)->toBeTrue();
});

it('berhasil menjawab karakter kelahiran 29 Januari 2006 dengan zodiak Aquarius', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiCall('consult_knowledge', [
            'topic' => 'arti_hari',
            'query' => '29 Januari 2006',
        ]))
        ->push(finalAnswer([
            'answer' => 'Kelahiran 29 Januari 2006 memiliki bintang Aquarius 20 Januari - 18 Februari dan Angka Garis Hidup 2, berwatak mandiri dan tenang.',
            'dates' => [],
            'cultural_notice' => true,
        ]))]);

    $answer = ask('Bagaimana sifat kelahiran tanggal 29 Januari 2006?');

    expect($answer->generatedBy)->toBe('ai')
        ->and($answer->answer)->toContain('Aquarius')
        ->and($answer->answer)->toContain('29 Januari 2006');
});

describe('endpoint /tanya', function (): void {
    it('menampilkan halaman Tanya Wariga', function (): void {
        $this->get('/tanya')->assertOk()->assertInertia(fn ($page) => $page->component('ask')->missing('persona'));
    });

    it('mengembalikan jawaban JSON yang sudah lewat guard', function (): void {
        Http::fake([GEMINI_URL => Http::sequence()->push(findPadi())->push(finalAnswer())]);

        $this->postJson('/tanya', ['question' => 'Hari baik menanam padi April 2025?'])
            ->assertOk()
            ->assertJsonPath('dates.0.date', '2025-04-23')
            ->assertJsonPath('generated_by', 'ai');
    });

    it('memvalidasi input', function (): void {
        $this->postJson('/tanya', ['question' => ''])->assertUnprocessable();
        $this->postJson('/tanya', ['question' => str_repeat('a', 501)])->assertUnprocessable();
        $this->postJson('/tanya', ['question' => 'hari baik', 'category' => 'tidak.ada'])->assertUnprocessable();
    });

    it('membatasi laju pertanyaan per pengguna', function (): void {
        config(['rahina.ai_rate_per_min' => 1]);
        Http::fake([GEMINI_URL => Http::response(finalAnswer(['dates' => [], 'out_of_scope' => true]))]);

        $this->postJson('/tanya', ['question' => 'halo'])->assertOk();
        $this->postJson('/tanya', ['question' => 'halo'])->assertStatus(429)->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'Tunggu'));
    });
});
