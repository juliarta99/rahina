<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppInbound;
use App\Messaging\MessagingGateway;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Webhook pesan masuk Fonnte (WHATSAPP.md §2.2, §2.4).
 *  1. Secret di URL = 32 karakter pertama HMAC('fonnte-webhook', APP_KEY); salah → 403.
 *  2. Field `device` harus sama dengan nomor device Rahina; salah → 403.
 *  3. Idempoten: hash pesan disimpan unik; retry Fonnte → 200 tanpa diproses ulang.
 *  4. Simpan hash (bukan isi), langsung 200 OK, proses di queue.
 */
class FonnteWebhookController extends Controller
{
    public static function secret(): string
    {
        return substr(hash_hmac('sha256', 'fonnte-webhook', (string) config('app.key')), 0, 32);
    }

    public function __invoke(Request $request, string $secret, MessagingGateway $gateway): JsonResponse
    {
        if (! hash_equals(self::secret(), $secret)) {
            return response()->json(['ok' => false], 403);
        }

        $inbound = $gateway->parseInbound($request);
        if ($inbound === null) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $device = $gateway->deviceInfo()['device'];
        if ($device !== null && $inbound->device !== $device) {
            return response()->json(['ok' => false], 403);
        }

        try {
            DB::table('whatsapp_messages')->insert([
                'direction' => 'in',
                'provider' => $gateway->name(),
                'inbound_hash' => $inbound->idempotencyKey,
                'kind' => 'inbound',
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        ProcessWhatsAppInbound::dispatch($inbound->sender, $inbound->text);

        return response()->json(['ok' => true]);
    }
}
