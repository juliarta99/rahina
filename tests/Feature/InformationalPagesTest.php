<?php

declare(strict_types=1);

it('dapat menampilkan halaman Tentang Kami & Silsilah Pustaka', function (): void {
    $this->get('/tentang')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('about')
        );

    $this->get('/tentang-kami')
        ->assertRedirect('/tentang');
});

it('dapat menampilkan halaman Kebijakan Privasi', function (): void {
    $this->get('/kebijakan-privasi')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('privacy')
        );
});

it('dapat menampilkan halaman Syarat & Ketentuan Layanan', function (): void {
    $this->get('/syarat-ketentuan')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('terms')
        );
});

it('dapat menampilkan pratinjau halaman error kustom untuk status 404 dan 500', function (): void {
    $this->get('/error/404')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('error')
            ->where('status', 404)
        );

    $this->get('/error/500')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('error')
            ->where('status', 500)
        );

    $this->get('/error/999')
        ->assertNotFound();
});

it('merender komponen error kustom Inertia saat mengakses rute yang tidak ditemukan', function (): void {
    $this->get('/halaman-yang-pasti-tidak-ada-di-rahina', ['X-Inertia' => 'true'])
        ->assertStatus(404)
        ->assertHeader('X-Inertia', 'true')
        ->assertJson([
            'component' => 'error',
            'props' => ['status' => 404],
        ]);
});
