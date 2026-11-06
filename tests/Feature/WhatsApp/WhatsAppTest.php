<?php

declare(strict_types=1);

use App\Data\AgentAnswer;
use App\Http\Controllers\Webhooks\FonnteWebhookController;
use App\Jobs\SendMorningBrief;
use App\Messaging\Gateways\FakeGateway;
use App\Messaging\MessageFormatter;
use App\Messaging\MessagingGateway;
use App\Messaging\WhatsAppSender;
use App\Models\User;
use App\Models\WhatsappSubscription;
use App\Wariga\CalendarRepository;
use App\Wariga\Otonan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;

const WA_PHONE = '6281234567890';

beforeEach(function (): void {
    Sleep::fake();
    seedReferenceCalendar();
    config(['messaging.driver' => 'fake']);
});

function fakeWa(): FakeGateway
{
    return app(FakeGateway::class);
}

/** Kirim webhook Fonnte palsu. */
function inbound(string $message, string $sender = WA_PHONE, ?string $device = null, ?string $inboxId = null): TestResponse
{
    return test()->postJson('/webhooks/fonnte/'.FonnteWebhookController::secret(), [
        'device' => $device ?? config('messaging.fake_device'),
        'sender' => $sender,
        'message' => $message,
        'inboxid' => $inboxId ?? (string) random_int(1, PHP_INT_MAX),
    ]);
}

function pendingSubscription(?string $birthDate = null): array
{
    $user = User::factory()->create();
    test()->actingAs($user)->post('/profil/whatsapp', array_filter([
        'consent_daily' => '1',
        'birth_date' => $birthDate,
        'consent_birthdate' => $birthDate === null ? null : '1',
    ]))->assertRedirect('/profil');

    $sub = WhatsappSubscription::query()->where('user_id', $user->id)->firstOrFail();
    $code = (string) DB::table('whatsapp_link_codes')->where('subscription_id', $sub->id)->value('code');

    return [$user, $sub, $code];
}

function activeSubscription(?string $birthDate = null): WhatsappSubscription
{
    [, $sub, $code] = pendingSubscription($birthDate);
    inbound("DAFTAR {$code}")->assertOk();

    return $sub->fresh();
}

describe('profil web', function (): void {
    it('mewajibkan login', function (): void {
        $this->get('/profil')->assertRedirect(route('login'));
    });

    it('mewajibkan persetujuan sebelum membuat kode DAFTAR', function (): void {
        $this->actingAs(User::factory()->create())->post('/profil/whatsapp', [])->assertSessionHasErrors('consent_daily');
        expect(WhatsappSubscription::query()->count())->toBe(0);
    });

    it('membuat langganan pending dan tautan wa.me berisi kode', function (): void {
        [$user, $sub, $code] = pendingSubscription();

        expect($sub->status)->toBe('pending')->and($code)->toMatch('/^[A-Z2-9]{6}$/');
        $this->actingAs($user)->get('/profil')->assertInertia(fn ($page) => $page
            ->component('profil')
            ->where('subscription.status', 'pending')
            ->where('link.code', $code)
            ->where('link.url', 'https://wa.me/'.config('messaging.fake_device').'?text=DAFTAR%20'.$code));
    });

    it('meminta persetujuan terpisah untuk tanggal lahir', function (): void {
        $this->actingAs(User::factory()->create())
            ->post('/profil/whatsapp', ['consent_daily' => '1', 'birth_date' => '1990-05-01'])
            ->assertSessionHasErrors('consent_birthdate');
    });

    it('bisa menjeda, melanjutkan, dan menghapus data', function (): void {
        $sub = activeSubscription();
        $user = User::query()->findOrFail($sub->user_id);

        $this->actingAs($user)->patch('/profil/whatsapp', ['action' => 'pause'])->assertRedirect();
        expect($sub->fresh()->status)->toBe('paused');
        $this->actingAs($user)->patch('/profil/whatsapp', ['action' => 'resume'])->assertRedirect();
        expect($sub->fresh()->status)->toBe('active');
        $this->actingAs($user)->delete('/profil/whatsapp')->assertRedirect();
        expect(WhatsappSubscription::query()->count())->toBe(0)
            ->and(DB::table('whatsapp_messages')->whereNotNull('subscription_id')->count())->toBe(0);
    });

    it('bisa menghubungkan nomor WhatsApp langsung dari profil web', function (): void {
        $user = User::factory()->create(['phone' => '6281234567890']);
        $this->actingAs($user)->post('/profil/whatsapp', [
            'consent_daily' => '1',
            'connect_now' => '1',
        ])->assertRedirect('/profil');

        $sub = WhatsappSubscription::query()->where('user_id', $user->id)->firstOrFail();
        expect($sub->status)->toBe('active')
            ->and($sub->phone_encrypted)->toBe('6281234567890')
            ->and($sub->verified_at)->not->toBeNull();
    });

    it('bisa mengubah nomor WhatsApp dari profil web', function (): void {
        $sub = activeSubscription();
        $user = User::query()->findOrFail($sub->user_id);

        $this->actingAs($user)->patch('/profil/whatsapp', [
            'action' => 'change_phone',
            'phone' => '0819 8765 4321',
        ])->assertRedirect('/profil');

        $fresh = $sub->fresh();
        expect($fresh->phone_encrypted)->toBe('6281987654321')
            ->and($fresh->status)->toBe('active');
    });

    it('menolak nomor WhatsApp yang tidak valid saat ganti nomor', function (): void {
        $sub = activeSubscription();
        $user = User::query()->findOrFail($sub->user_id);

        $this->actingAs($user)->patch('/profil/whatsapp', [
            'action' => 'change_phone',
            'phone' => '12345',
        ])->assertSessionHasErrors('phone');
    });

    it('menolak nomor WhatsApp yang sudah terhubung ke akun lain', function (): void {
        $sub = activeSubscription();
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)->post('/profil/whatsapp', [
            'consent_daily' => '1',
            'connect_now' => '1',
            'phone' => '081299990001',
        ]);

        $user = User::query()->findOrFail($sub->user_id);
        $this->actingAs($user)->patch('/profil/whatsapp', [
            'action' => 'change_phone',
            'phone' => '081299990001',
        ])->assertSessionHasErrors('phone');
    });
});

describe('webhook', function (): void {
    it('menolak secret yang salah dan device yang berbeda', function (): void {
        $this->postJson('/webhooks/fonnte/salah', ['device' => config('messaging.fake_device'), 'sender' => WA_PHONE, 'message' => 'halo'])->assertForbidden();
        inbound('halo', device: '628999')->assertForbidden();
        expect(fakeWa()->sent)->toBe([]);
    });

    it('mengaktifkan langganan lewat DAFTAR <kode> dan menyimpan nomor terenkripsi', function (): void {
        [, $sub, $code] = pendingSubscription();

        inbound('daftar '.strtolower($code))->assertOk();

        $fresh = $sub->fresh();
        $raw = (array) DB::table('whatsapp_subscriptions')->find($sub->id);
        expect($fresh->status)->toBe('active')
            ->and($fresh->phone_encrypted)->toBe(WA_PHONE)
            ->and(json_encode($raw))->not->toContain(WA_PHONE)
            ->and($raw['phone_hash'])->toHaveLength(64)
            ->and(fakeWa()->sent[0]['to'])->toBe(WA_PHONE)
            ->and(fakeWa()->sent[0]['message'])->toContain('Rahina Pagi aktif')
            ->and(DB::table('whatsapp_link_codes')->where('code', $code)->value('used_at'))->not->toBeNull();
    });

    it('menolak kode yang salah atau kedaluwarsa', function (): void {
        [, $sub, $code] = pendingSubscription();
        DB::table('whatsapp_link_codes')->where('code', $code)->update(['expires_at' => now()->subMinute()]);

        inbound("DAFTAR {$code}");

        expect($sub->fresh()->status)->toBe('pending')
            ->and(fakeWa()->sent[0]['message'])->toContain('tidak ditemukan');
    });

    it('langsung tersambung bila DAFTAR (tanpa kode) dikirim dari nomor akun', function (): void {
        $user = User::factory()->create(['phone' => WA_PHONE]);
        $this->actingAs($user)->post('/profil/whatsapp', ['consent_daily' => '1'])->assertRedirect('/profil');

        inbound('Daftar')->assertOk();

        $sub = WhatsappSubscription::query()->where('user_id', $user->id)->firstOrFail();
        expect($sub->status)->toBe('active')->and($sub->phone_encrypted)->toBe(WA_PHONE);
    });

    it('DAFTAR tanpa kode dari nomor yang bukan nomor akun tidak mengaktifkan apa pun', function (): void {
        [, $sub] = pendingSubscription();

        inbound('DAFTAR', sender: '628111222333');

        expect($sub->fresh()->status)->toBe('pending')
            ->and(fakeWa()->sent[0]['message'])->toContain('tidak ditemukan');
    });

    it('idempoten terhadap retry webhook (inboxid sama)', function (): void {
        $sub = activeSubscription();
        $before = count(fakeWa()->sent);

        inbound('BANTUAN', inboxId: 'abc-1')->assertOk();
        inbound('BANTUAN', inboxId: 'abc-1')->assertOk()->assertJsonPath('duplicate', true);

        expect(count(fakeWa()->sent))->toBe($before + 1);
    });

    it('memproses BERHENTI, MULAI, dan HAPUS DATA', function (): void {
        $sub = activeSubscription();

        inbound('BERHENTI');
        expect($sub->fresh()->status)->toBe('opted_out');
        inbound('mulai');
        expect($sub->fresh()->status)->toBe('active');
        inbound('HAPUS DATA');
        expect(WhatsappSubscription::query()->count())->toBe(0)
            ->and(end(fakeWa()->sent)['message'])->toContain('sudah dihapus');
    });

    it('meminta nomor tak dikenal mendaftar lewat situs, tanpa memanggil AI', function (): void {
        Http::fake();
        inbound('hari baik menanam padi?', sender: '628111');

        expect(fakeWa()->sent[0]['message'])->toContain('belum terdaftar');
        Http::assertNothingSent();
    });

    it('menjawab pertanyaan lewat agent Tanya Wariga tanpa mengirim nomor ke Gemini', function (): void {
        activeSubscription();
        Http::fake([GEMINI_URL => Http::sequence()
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => 'find_good_days', 'args' => ['category_codes' => ['pertanian.padi'], 'start' => '2025-04-01', 'end' => '2025-05-31']]]]], 'finishReason' => 'STOP']]])
            ->push(geminiFixture('text_response', [
                'answer' => 'Menurut perhitungan wariga, ada hari baik untuk menanam padi.',
                'dates' => [['date' => '2025-04-23', 'reason' => 'Didukung [[DEWASA:SINTETIS_A]].', 'support_rule_ids' => [], 'block_rule_ids' => []]],
                'needs_clarification' => false, 'clarification_options' => [], 'cultural_notice' => false, 'out_of_scope' => false,
            ]))]);

        inbound('Hari baik menanam padi April 2025?');

        $reply = end(fakeWa()->sent)['message'];
        expect($reply)->toContain('menanam padi')->toContain('23 Apr 2025')->toContain('/hari/2025-04-23');
        Http::assertSent(fn ($r): bool => ! str_contains(json_encode($r->data()), WA_PHONE));
    });
});

describe('ringkasan pagi', function (): void {
    it('menjadwalkan hanya langganan aktif & terverifikasi, bertahap', function (): void {
        $active = activeSubscription();
        pendingSubscription(); // belum DAFTAR → tidak dikirim
        Http::fake([GEMINI_URL => Http::response(['error' => 'down'], 503)]);
        Bus::fake();

        $this->artisan('wa:prepare-morning', ['--date' => '2025-04-23'])->assertSuccessful();

        Bus::assertDispatchedTimes(SendMorningBrief::class, 1);
        Bus::assertDispatched(SendMorningBrief::class, fn (SendMorningBrief $j): bool => $j->subscriptionId === $active->id && $j->date === '2025-04-23');
    });

    it('mengirim pesan pagi berformat, sekali per tanggal', function (): void {
        Http::fake([GEMINI_URL => Http::response(['error' => 'down'], 503)]); // ringkasan jatuh ke template
        $sub = activeSubscription();

        dispatch_sync(new SendMorningBrief($sub->id, '2025-04-23'));
        dispatch_sync(new SendMorningBrief($sub->id, '2025-04-23'));

        $morning = array_values(array_filter(fakeWa()->sent, fn (array $m): bool => str_contains($m['message'], 'Hari ini secara umum')));
        expect($morning)->toHaveCount(1)
            ->and($morning[0]['message'])->toContain('Buda Kliwon')->toContain('Wuku Dungulan')->toContain('/hari/2025-04-23')->toContain('BERHENTI')
            ->and(preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $morning[0]['message']))->toBe(0)
            ->and($sub->fresh()->last_sent_on->toDateString())->toBe('2025-04-23');
    });

    it('menambahkan catatan otonan tanpa mengirim tanggal lahir ke Gemini', function (): void {
        Http::fake([GEMINI_URL => Http::response(['error' => 'down'], 503)]);
        // Lahir tepat 210 × 40 hari sebelum 2025-04-23 → hari ini otonan.
        $birth = CarbonImmutable::parse('2025-04-23')->subDays(210 * 40)->toDateString();
        $sub = activeSubscription($birth);

        dispatch_sync(new SendMorningBrief($sub->id, '2025-04-23'));

        expect(end(fakeWa()->sent)['message'])->toContain('Hari ini otonan Anda');
        Http::assertSent(fn ($r): bool => ! str_contains(json_encode($r->data()), $birth));
    });

    it('menunda batch bila device tidak terhubung', function (): void {
        activeSubscription();
        Bus::fake();
        $this->mock(MessagingGateway::class, fn ($m) => $m->shouldReceive('deviceInfo')->andReturn(['device' => null, 'connected' => false, 'quota' => null]));

        $this->artisan('wa:prepare-morning', ['--date' => '2025-04-23'])->assertFailed();
        Bus::assertNotDispatched(SendMorningBrief::class);
    });

    it('menandai langganan gagal setelah 3 kegagalan beruntun', function (): void {
        $sub = activeSubscription();
        $sender = app(WhatsAppSender::class);
        foreach (range(1, 3) as $i) {
            fakeWa()->failNext = true;
            $sender->send($sub, 'system', 'tes');
        }

        expect($sub->fresh()->status)->toBe('failed');
    });
});

it('otonan berulang tiap 210 hari, sesuai pawukon di tabel kalender', function (): void {
    $calendar = app(CalendarRepository::class);
    // Galungan 2025-04-23 dan 2025-11-19 berjarak 210 hari: wuku, saptawara, pancawara harus sama.
    $a = $calendar->day('2025-04-23');
    $b = $calendar->day('2025-11-19');

    expect(Otonan::next(CarbonImmutable::parse('2025-04-23'), CarbonImmutable::parse('2025-04-24'))->toDateString())->toBe('2025-11-19')
        ->and([$a->wuku->name, $a->saptawara(), $a->pancawara()])->toBe([$b->wuku->name, $b->saptawara(), $b->pancawara()])
        ->and(Otonan::daysUntilNext(CarbonImmutable::parse('1990-01-01'), CarbonImmutable::parse('1990-01-01')->addDays(420)))->toBe(0);
});

it('format jawaban WhatsApp menampilkan pilihan kategori saat klarifikasi', function (): void {
    $text = app(MessageFormatter::class)->answer(new AgentAnswer(
        answer: 'Aktivitas ini bisa masuk beberapa kategori.', dates: [], needsClarification: true,
        clarificationOptions: [['code' => 'peralatan', 'name' => 'Peralatan'], ['code' => 'aneka', 'name' => 'Aneka kegiatan']],
        culturalNotice: false, outOfScope: false, generatedBy: 'ai',
    ));

    expect($text)->toContain('Peralatan, Aneka kegiatan');
});
