<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

it('menampilkan landing page publik dengan data hari ini dan maskot', function (): void {
    $response = $this->get('/');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('today')
            ->has('todayFormatted')
        );
});

it('dapat mengakses landing page lewat route landing', function (): void {
    $response = $this->get('/landing');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
        );
});

it('tetap mengarahkan ke kalender jika query bulan disertakan di root', function (): void {
    seedReferenceCalendar();
    $this->travelTo('2025-04-23 09:00:00');

    $response = $this->get('/?bulan=2025-04');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar')
            ->where('month', '2025-04')
        );
});
