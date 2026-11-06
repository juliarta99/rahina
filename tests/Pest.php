<?php

use Database\Seeders\ActivityCategorySeeder;
use Database\Seeders\SourceSeeder;
use Database\Seeders\WarigaMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Seed master + import tanggal acuan; opsional muat aturan SINTETIS dan hitung dewasanya.
 */
function seedMasters(): void
{
    // Tanpa DewasaRuleSeeder: test memakai aturan sintetis, bukan aturan produksi.
    test()->seed([SourceSeeder::class, WarigaMasterSeeder::class, ActivityCategorySeeder::class]);
}

function seedReferenceCalendar(bool $withSyntheticRules = true): void
{
    seedMasters();
    test()->artisan('wariga:import', ['path' => base_path('tests/Fixtures/calendar/reference.jsonl')])->assertSuccessful();

    if ($withSyntheticRules) {
        test()->artisan('wariga:load-rules', ['path' => base_path('tests/Fixtures/dewasa_rules.synthetic.json')])->assertSuccessful();
        test()->artisan('wariga:compute-dewasa')->assertSuccessful();
    }
}

function ruleId(string $code): int
{
    return (int) DB::table('dewasa_rules')->where('code', $code)->value('id');
}

/**
 * Respons Gemini dari fixture tests/Fixtures/gemini/{name}.json; `__TEXT__` diganti teks/JSON keluaran model.
 *
 * @param  array<string, mixed>|string|null  $text
 * @return array<string, mixed>
 */
function geminiFixture(string $name, array|string|null $text = null): array
{
    $raw = (string) file_get_contents(base_path("tests/Fixtures/gemini/{$name}.json"));
    $data = json_decode($raw, true);

    if ($text !== null) {
        $value = is_array($text) ? json_encode($text, JSON_UNESCAPED_UNICODE) : $text;
        array_walk_recursive($data, function (mixed &$v) use ($value): void {
            if ($v === '__TEXT__') {
                $v = $value;
            }
        });
    }

    return $data;
}

const GEMINI_URL = 'generativelanguage.googleapis.com/*';
