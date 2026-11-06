<?php

declare(strict_types=1);

it('melewati db:lock-public di driver selain pgsql', function (): void {
    $this->artisan('db:lock-public')
        ->expectsOutputToContain('dilewati')
        ->assertSuccessful();
});

it('memakai zona waktu WITA dan model dari config', function (): void {
    expect(config('app.timezone'))->toBe('Asia/Makassar')
        ->and(config('rahina.gemini.model'))->toBe('test-model')
        ->and(config('rahina.agent.max_steps'))->toBe(4);
});
