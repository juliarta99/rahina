<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rahina Pagi via WhatsApp (docs/WHATSAPP.md, DATABASE_SCHEMA.md §3.6b).
 * Nomor & tanggal lahir disimpan terenkripsi; isi pesan masuk TIDAK disimpan (hanya hash untuk idempotensi).
 * Persona dihapus dari produk (2026-11-06), sehingga kolom persona tidak dibuat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->text('phone_encrypted')->nullable();
            $table->char('phone_hash', 64)->nullable()->unique();
            $table->text('birth_date_encrypted')->nullable();
            $table->enum('frequency', ['daily', 'important_only'])->default('daily');
            $table->enum('status', ['pending', 'active', 'paused', 'opted_out', 'failed'])->default('pending');
            $table->timestampTz('consent_daily_at');
            $table->timestampTz('consent_birthdate_at')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('opted_out_at')->nullable();
            $table->timestampTz('last_inbound_at')->nullable();
            $table->date('last_sent_on')->nullable();
            $table->smallInteger('consecutive_failures')->default(0);
            $table->timestampsTz();
            $table->index('status');
        });

        Schema::create('whatsapp_link_codes', function (Blueprint $table): void {
            $table->char('code', 6)->primary();
            $table->foreignId('subscription_id')->constrained('whatsapp_subscriptions')->cascadeOnDelete();
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('whatsapp_subscriptions')->cascadeOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->string('provider', 16);
            $table->string('provider_message_id', 64)->nullable();
            $table->char('inbound_hash', 64)->nullable()->unique();
            $table->enum('kind', ['morning_brief', 'answer', 'system', 'inbound']);
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed', 'received']);
            $table->string('error_code', 120)->nullable();
            // Idempotensi pesan pagi: satu langganan, satu pesan per tanggal.
            $table->date('brief_date')->nullable();
            $table->timestampsTz();
            $table->unique(['subscription_id', 'kind', 'brief_date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_link_codes');
        Schema::dropIfExists('whatsapp_subscriptions');
    }
};
