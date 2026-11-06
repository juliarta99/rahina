<?php

use App\Models\User;

test('dashboard bawaan diarahkan ke profil Rahina', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')->assertRedirect('/profil');
});

test('tamu yang membuka profil diarahkan ke halaman masuk', function () {
    $this->get('/profil')->assertRedirect(route('login'));
});
