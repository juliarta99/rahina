<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Langganan Rahina Pagi via WhatsApp. Nomor & tanggal lahir terenkripsi (cast `encrypted`), dicari lewat
 * `phone_hash` (HMAC dengan APP_KEY). Hanya status `active` yang dikirimi pesan pagi.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $phone_encrypted
 * @property string|null $phone_hash
 * @property string|null $birth_date_encrypted
 * @property string $frequency
 * @property string $status
 * @property Carbon $consent_daily_at
 * @property Carbon|null $consent_birthdate_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $opted_out_at
 * @property Carbon|null $last_inbound_at
 * @property Carbon|null $last_sent_on
 * @property int $consecutive_failures
 */
class WhatsappSubscription extends Model
{
    public const PENDING = 'pending';

    public const ACTIVE = 'active';

    public const PAUSED = 'paused';

    public const OPTED_OUT = 'opted_out';

    public const FAILED = 'failed';

    protected $fillable = [
        'user_id', 'phone_encrypted', 'phone_hash', 'birth_date_encrypted', 'frequency', 'status',
        'consent_daily_at', 'consent_birthdate_at', 'verified_at', 'opted_out_at', 'last_inbound_at',
        'last_sent_on', 'consecutive_failures',
    ];

    protected $hidden = ['phone_encrypted', 'phone_hash', 'birth_date_encrypted'];

    protected function casts(): array
    {
        return [
            'phone_encrypted' => 'encrypted',
            'birth_date_encrypted' => 'encrypted',
            'consent_daily_at' => 'datetime',
            'consent_birthdate_at' => 'datetime',
            'verified_at' => 'datetime',
            'opted_out_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_sent_on' => 'date',
            'consecutive_failures' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashPhone(string $phone): string
    {
        return hash_hmac('sha256', $phone, (string) config('app.key'));
    }

    public static function findByPhone(string $phone): ?self
    {
        return self::query()->where('phone_hash', self::hashPhone($phone))->first();
    }

    public function setPhone(string $phone): void
    {
        $this->phone_encrypted = $phone;
        $this->phone_hash = self::hashPhone($phone);
    }

    /** Nomor disamarkan untuk tampilan, mis. 6281******789. */
    public function maskedPhone(): ?string
    {
        $phone = $this->phone_encrypted;

        return $phone === null ? null : substr($phone, 0, 4).str_repeat('*', max(0, strlen($phone) - 7)).substr($phone, -3);
    }
}
