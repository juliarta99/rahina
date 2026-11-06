<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Webhooks\FonnteWebhookController;
use App\Messaging\Gateways\FonnteGateway;
use App\Messaging\MessagingGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * `wa:device`: status device WhatsApp (nomor disamarkan, status, kuota). `wa:device --webhook`: URL webhook
 * untuk ditempel di dashboard Fonnte (Device → Edit → Webhook). Tidak mengirim pesan.
 */
class WaDevice extends Command
{
    protected $signature = 'wa:device {--webhook : Tampilkan URL webhook} {--fresh : Abaikan cache /device}';

    protected $description = 'Cek device WhatsApp (Fonnte) dan URL webhook';

    public function handle(MessagingGateway $gateway): int
    {
        if ($this->option('webhook')) {
            $this->line(rtrim((string) config('app.url'), '/').route('webhooks.fonnte', ['secret' => FonnteWebhookController::secret()], false));

            return self::SUCCESS;
        }
        if ($this->option('fresh')) {
            Cache::forget(FonnteGateway::DEVICE_CACHE_KEY);
        }

        $info = $gateway->deviceInfo();
        $device = $info['device'];
        $this->components->twoColumnDetail('Driver', $gateway->name());
        $this->components->twoColumnDetail('Nomor device', $device === null ? '-' : substr($device, 0, 4).'****'.substr($device, -3));
        $this->components->twoColumnDetail('Status', $info['connected'] ? 'terhubung' : 'TIDAK terhubung');
        $this->components->twoColumnDetail('Kuota', $info['quota'] ?? '-');

        return $info['connected'] ? self::SUCCESS : self::FAILURE;
    }
}
