<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Messaging\LinkCodes;
use App\Messaging\MessagingGateway;
use App\Models\WhatsappSubscription;
use App\Support\Phone;
use App\Wariga\CalendarRepository;
use App\Wariga\Otonan;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman "Profil" Rahina: mengaktifkan ringkasan pagi WhatsApp, ganti nomor WhatsApp,
 * hubungkan nomor langsung atau via pesan DAFTAR, jeda/lanjutkan, tanggal lahir otonan, dan hapus data.
 */
class RahinaProfileController extends Controller
{
    public function show(Request $request, LinkCodes $codes, MessagingGateway $gateway, CalendarRepository $calendar): Response
    {
        $subscription = $this->subscription($request);
        $device = $gateway->deviceInfo();
        $code = $subscription !== null && $subscription->status === WhatsappSubscription::PENDING ? $codes->active($subscription) : null;

        $birth = $subscription?->birth_date_encrypted;
        $otonan = null;
        if ($birth !== null) {
            $next = Otonan::next(CarbonImmutable::parse($birth), CarbonImmutable::today());
            $day = $calendar->day($next);
            $otonan = [
                'date' => $next->toDateString(),
                'days_until' => Otonan::daysUntilNext(CarbonImmutable::parse($birth), CarbonImmutable::today()),
                'label' => $day === null ? null : "{$day->saptawara()} {$day->pancawara()} · Wuku {$day->wuku->name}",
            ];
        }

        return Inertia::render('profil', [
            'subscription' => $subscription === null ? null : [
                'status' => $subscription->status,
                'phone' => $subscription->maskedPhone(),
                'display_phone' => Phone::display($subscription->phone_encrypted),
                'raw_phone' => $subscription->phone_encrypted,
                'verified_at' => $subscription->verified_at?->toIso8601String(),
                'has_birth_date' => $birth !== null,
                'birth_date' => $birth,
                'last_sent_on' => $subscription->last_sent_on?->toDateString(),
            ],
            'link' => $code === null || $device['device'] === null ? null : [
                'code' => $code,
                'url' => LinkCodes::waLink($device['device'], $code),
                // Dari nomor akun cukup kirim "DAFTAR" (tanpa kode).
                'direct_url' => 'https://wa.me/'.$device['device'].'?text=DAFTAR',
                'minutes' => (int) config('messaging.link_code_minutes'),
            ],
            'accountPhone' => Phone::display($request->user()?->phone),
            'rawAccountPhone' => $request->user()?->phone,
            'deviceReady' => $device['device'] !== null,
            'otonan' => $otonan,
            'sendTime' => (string) config('messaging.send_time'),
        ]);
    }

    /** Aktifkan: catat persetujuan, buat/aktifkan langganan, dan hubungkan nomor WhatsApp. */
    public function enable(Request $request, LinkCodes $codes): RedirectResponse
    {
        $data = $request->validate([
            'consent_daily' => ['accepted'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            'consent_birthdate' => $request->filled('birth_date') ? ['required', 'accepted'] : ['nullable'],
            'phone' => ['nullable', 'string'],
            'connect_now' => ['nullable'],
        ], [
            'consent_daily.accepted' => 'Centang persetujuan menerima ringkasan pagi via WhatsApp.',
            'consent_birthdate.required' => 'Centang persetujuan pemakaian tanggal lahir, atau kosongkan tanggal lahir.',
        ]);

        $rawPhone = (string) ($request->input('phone') ?: $request->user()?->phone);
        $normalizedPhone = Phone::normalize($rawPhone);
        if ($rawPhone !== '' && ! Phone::isValid($normalizedPhone)) {
            return back()->withErrors(['phone' => 'Nomor WhatsApp tidak valid. Masukkan nomor seluler Indonesia (contoh: 081234567890).']);
        }

        $subscription = $this->subscription($request) ?? new WhatsappSubscription(['user_id' => $request->user()?->getAuthIdentifier()]);
        $subscription->consent_daily_at = now();
        $this->applyBirthDate($subscription, $data['birth_date'] ?? null);

        if ($normalizedPhone !== '') {
            $existing = WhatsappSubscription::findByPhone($normalizedPhone);
            if ($existing !== null && $existing->id !== $subscription->id) {
                return back()->withErrors(['phone' => 'Nomor WhatsApp ini sudah terhubung ke akun lain.']);
            }
            $subscription->setPhone($normalizedPhone);
        }

        $connectNow = $request->boolean('connect_now');
        if ($connectNow && $normalizedPhone !== '') {
            $subscription->status = WhatsappSubscription::ACTIVE;
            $subscription->verified_at = now();
            $subscription->consecutive_failures = 0;
            $subscription->opted_out_at = null;
        } else {
            if ($subscription->verified_at === null) {
                $subscription->status = WhatsappSubscription::PENDING;
            } elseif (in_array($subscription->status, [WhatsappSubscription::PAUSED, WhatsappSubscription::OPTED_OUT, WhatsappSubscription::FAILED], true)) {
                $subscription->status = WhatsappSubscription::ACTIVE;
                $subscription->consecutive_failures = 0;
                $subscription->opted_out_at = null;
            }
        }

        $subscription->save();

        if ($subscription->status === WhatsappSubscription::PENDING) {
            $codes->issue($subscription);
        }

        $statusMsg = $subscription->status === WhatsappSubscription::ACTIVE ? 'whatsapp-connected' : 'whatsapp-updated';

        return to_route('rahina.profile')->with('status', $statusMsg);
    }

    public function newCode(Request $request, LinkCodes $codes): RedirectResponse
    {
        $subscription = $this->subscription($request);
        if ($subscription !== null && $subscription->status === WhatsappSubscription::PENDING) {
            $codes->issue($subscription);
        }

        return to_route('rahina.profile');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['pause', 'resume', 'birth_date', 'change_phone', 'connect_now'])],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            'consent_birthdate' => $request->input('action') === 'birth_date' && $request->filled('birth_date') ? ['required', 'accepted'] : ['nullable'],
            'phone' => ['nullable', 'string'],
        ], ['consent_birthdate.required' => 'Centang persetujuan pemakaian tanggal lahir.']);

        $subscription = $this->subscription($request);
        abort_if($subscription === null, 404);

        match ($data['action']) {
            'pause' => $subscription->status = WhatsappSubscription::PAUSED,
            'resume' => $subscription->status = $subscription->verified_at === null ? WhatsappSubscription::PENDING : WhatsappSubscription::ACTIVE,
            'birth_date' => $this->applyBirthDate($subscription, $data['birth_date'] ?? null),
            'change_phone' => $this->changePhone($request, $subscription),
            'connect_now' => $this->connectNow($request, $subscription),
        };

        if ($data['action'] === 'resume') {
            $subscription->consecutive_failures = 0;
        }

        $subscription->save();

        $status = match ($data['action']) {
            'change_phone' => 'whatsapp-phone-updated',
            'connect_now' => 'whatsapp-connected',
            default => 'whatsapp-updated',
        };

        return to_route('rahina.profile')->with('status', $status);
    }

    private function changePhone(Request $request, WhatsappSubscription $subscription): void
    {
        $rawPhone = (string) $request->input('phone');
        $normalized = Phone::normalize($rawPhone);
        if (! Phone::isValid($normalized)) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor WhatsApp tidak valid. Masukkan nomor seluler Indonesia (contoh: 081234567890).',
            ]);
        }

        $existing = WhatsappSubscription::findByPhone($normalized);
        if ($existing !== null && $existing->id !== $subscription->id) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor WhatsApp ini sudah terhubung ke akun lain.',
            ]);
        }

        $subscription->setPhone($normalized);
        $subscription->status = WhatsappSubscription::ACTIVE;
        $subscription->verified_at = now();
        $subscription->consecutive_failures = 0;
        $subscription->opted_out_at = null;
    }

    private function connectNow(Request $request, WhatsappSubscription $subscription): void
    {
        $rawPhone = (string) ($request->input('phone') ?: ($subscription->phone_encrypted ?: $request->user()?->phone));
        $normalized = Phone::normalize($rawPhone);
        if (! Phone::isValid($normalized)) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor WhatsApp tidak valid. Masukkan nomor seluler Indonesia (contoh: 081234567890).',
            ]);
        }

        $existing = WhatsappSubscription::findByPhone($normalized);
        if ($existing !== null && $existing->id !== $subscription->id) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor WhatsApp ini sudah terhubung ke akun lain.',
            ]);
        }

        $subscription->setPhone($normalized);
        $subscription->status = WhatsappSubscription::ACTIVE;
        $subscription->verified_at = now();
        $subscription->consecutive_failures = 0;
        $subscription->opted_out_at = null;
    }

    /** Hapus langganan, nomor, tanggal lahir, kode, dan log pesan (cascade). */
    public function destroy(Request $request): RedirectResponse
    {
        $this->subscription($request)?->delete();

        return to_route('rahina.profile')->with('status', 'whatsapp-deleted');
    }

    private function subscription(Request $request): ?WhatsappSubscription
    {
        return WhatsappSubscription::query()->where('user_id', $request->user()?->getAuthIdentifier())->first();
    }

    private function applyBirthDate(WhatsappSubscription $subscription, ?string $birthDate): void
    {
        $subscription->birth_date_encrypted = $birthDate;
        $subscription->consent_birthdate_at = $birthDate === null ? null : now();
    }
}
