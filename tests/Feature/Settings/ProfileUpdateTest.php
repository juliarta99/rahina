<?php

use App\Models\User;
use App\Models\WhatsappSubscription;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('nama dan nomor HP bisa diubah (dinormalkan ke 62)', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Penguji',
            'phone' => '0812 9999 0000',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Penguji');
    expect($user->phone)->toBe('6281299990000');
});

test('mengganti nomor HP memutus langganan WhatsApp lama', function () {
    $user = User::factory()->create();
    WhatsappSubscription::query()->create(['user_id' => $user->id, 'status' => 'active', 'consent_daily_at' => now()]);

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'phone' => '081299990000'])->assertSessionHasNoErrors();

    expect(WhatsappSubscription::query()->count())->toBe(0);
});

test('menyimpan nomor yang sama tidak memutus langganan', function () {
    $user = User::factory()->create();
    WhatsappSubscription::query()->create(['user_id' => $user->id, 'status' => 'active', 'consent_daily_at' => now()]);

    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Baru', 'phone' => $user->phone])->assertSessionHasNoErrors();

    expect(WhatsappSubscription::query()->count())->toBe(1);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
