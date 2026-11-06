<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'phone' => $user->phone,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/profil');
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'phone' => $user->phone,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'phone' => $user->phone,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->phone, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'phone' => $user->phone,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});

test('nomor HP diterima dalam format lokal maupun internasional', function (string $format) {
    $user = User::factory()->create(['phone' => '6281234567890']);

    $this->post(route('login.store'), ['phone' => $format, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
})->with(['0812-3456-7890', '+62 812 3456 7890', '6281234567890', '81234567890']);

test('halaman masuk tidak menawarkan passkey atau lupa kata sandi lewat email', function () {
    $this->get(route('login'))->assertInertia(fn ($page) => $page->component('auth/login')->missing('canResetPassword'));
    expect(Route::has('password.request'))->toBeFalse()
        ->and(Route::has('passkey.login'))->toBeFalse();
});
