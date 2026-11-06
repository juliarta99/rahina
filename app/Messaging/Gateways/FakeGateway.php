<?php

declare(strict_types=1);

namespace App\Messaging\Gateways;

use App\Messaging\Inbound;
use App\Messaging\MessagingGateway;
use App\Messaging\SendResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gateway palsu untuk test & lokal (MESSAGING_DRIVER=fake). Tidak mengirim apa pun: pesan disimpan di memori
 * agar bisa diperiksa test, dan dicatat ke log tanpa nomor lengkap maupun isi.
 */
final class FakeGateway implements MessagingGateway
{
    /** @var list<array{to: string, message: string}> */
    public array $sent = [];

    public bool $failNext = false;

    public function name(): string
    {
        return 'fake';
    }

    public function send(string $to, string $message): SendResult
    {
        if ($this->failNext) {
            $this->failNext = false;

            return new SendResult(false, error: 'fake_failure');
        }
        $this->sent[] = ['to' => $to, 'message' => $message];
        Log::info('[wa-fake] pesan ke ****'.substr($to, -4).' ('.mb_strlen($message).' karakter)');

        return new SendResult(true, 'fake-'.count($this->sent));
    }

    public function deviceInfo(): array
    {
        return ['device' => (string) config('messaging.fake_device'), 'connected' => true, 'quota' => null];
    }

    public function parseInbound(Request $request): ?Inbound
    {
        return Inbound::fromFonnte($request);
    }
}
