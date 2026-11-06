<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('pengguna baru mendaftar dengan nomor HP, disimpan dalam format 62', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Penguji',
        'phone' => '0812-3456-7890',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/profil');
    expect(User::query()->first()->phone)->toBe('6281234567890')
        ->and(User::query()->first()->email)->toBeNull();
});

test('menolak nomor HP yang tidak valid atau sudah dipakai', function (string $phone) {
    User::factory()->create(['phone' => '6281234567890']);

    $this->post(route('register.store'), [
        'name' => 'Penguji',
        'phone' => $phone,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('phone');

    $this->assertGuest();
})->with(['bukan nomor' => ['abc'], 'terlalu pendek' => ['0812'], 'bukan seluler' => ['0361 123456'], 'sudah dipakai' => ['+62 812-3456-7890']]);
