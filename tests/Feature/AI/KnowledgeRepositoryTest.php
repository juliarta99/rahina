<?php

declare(strict_types=1);

use App\AI\KnowledgeRepository;
use App\AI\Tools\ConsultKnowledgeTool;
use App\AI\AgentContext;

beforeEach(function (): void {
    $this->knowledge = app(KnowledgeRepository::class);
    $this->tool = app(ConsultKnowledgeTool::class);
    $this->context = new AgentContext('2026-11-06');
});

it('dapat memuat dan mencari data 12 zodiak bintang', function (): void {
    $all = $this->knowledge->allBintang();
    expect($all)->toHaveCount(12);

    $scorpio = $this->knowledge->findBintang('Scorpio');
    expect($scorpio)->not->toBeNull()
        ->and($scorpio['name'])->toBe('Scorpio')
        ->and($scorpio['simbul'])->toBe('Kalajengking');

    $byDate = $this->knowledge->findBintang('2000-08-15');
    expect($byDate)->not->toBeNull()
        ->and($byDate['name'])->toBe('Leo');

    // 20 April adalah Taurus (bukan Aries)
    $taurus = $this->knowledge->findBintang('20 April 2001');
    expect($taurus)->not->toBeNull()
        ->and($taurus['name'])->toBe('Taurus');

    // 19 April adalah Aries
    $aries = $this->knowledge->findBintang('19 April 2001');
    expect($aries)->not->toBeNull()
        ->and($aries['name'])->toBe('Aries');
});

it('dapat menghitung dan memuat numerologi 9 garis hidup', function (): void {
    $all = $this->knowledge->allGarisHidup();
    expect($all)->toHaveCount(9);

    $g1 = $this->knowledge->findGarisHidup(1);
    expect($g1)->not->toBeNull()
        ->and($g1['number'])->toBe(1)
        ->and($g1['description'])->not->toBeEmpty();

    // 1995-08-17: 1+9+9+5 + 0+8 + 1+7 = 24 + 8 + 8 = 40 -> 4
    $byDate = $this->knowledge->findGarisHidup('1995-08-17');
    expect($byDate)->not->toBeNull()
        ->and($byDate['number'])->toBe(4);
});

it('dapat menghitung perjodohan Lontar Tri Pramana (mod 16)', function (): void {
    $jodoh = $this->knowledge->calculateJodoh('2000-01-01', '2000-02-02');

    expect($jodoh)->toHaveKeys(['person1', 'person2', 'total_urip', 'modulo', 'sisa', 'ramalan', 'verdict'])
        ->and($jodoh['modulo'])->toBe(16)
        ->and($jodoh['sisa'])->toBeGreaterThanOrEqual(0)
        ->and($jodoh['sisa'])->toBeLessThan(16)
        ->and($jodoh['ramalan'])->not->toBeEmpty();
});

it('dapat memuat kamus arti wewaran, wuku, lintang, dan pratiti', function (): void {
    $dict = $this->knowledge->allArtiHari();

    expect($dict)->toHaveKeys(['wuku', 'lintang', 'pratiti', 'pancawara', 'saptawara', 'sadwara'])
        ->and($dict['wuku'])->toHaveCount(30)
        ->and($dict['lintang'])->toHaveCount(35)
        ->and($dict['pratiti'])->toHaveCount(12);

    $sinta = $this->knowledge->findArtiHari('Sinta', 'wuku');
    expect($sinta)->not->toBeNull()
        ->and($sinta['name'])->toBe('Sinta')
        ->and($sinta['description'])->toContain('Yamadipati');
});

it('melakukan RAG semantic/keyword search dengan cepat', function (): void {
    $results = $this->knowledge->search('kalajengking', 3);
    expect($results)->not->toBeEmpty()
        ->and($results[0]['title'])->toContain('Scorpio');

    $jodohSearch = $this->knowledge->search('Lontar Tri Pramana', 3);
    expect($jodohSearch)->not->toBeEmpty()
        ->and($jodohSearch[0]['source'])->toBe('jodoh');
});

it('meminta klarifikasi saat data yang dimasukkan ambigu pada tool consult_knowledge', function (): void {
    // 1. Topik jodoh tanpa tanggal
    $resJodoh = $this->tool->run(['topic' => 'jodoh'], $this->context);
    expect($resJodoh['found'])->toBeFalse()
        ->and($resJodoh['needs_clarification'])->toBeTrue()
        ->and($resJodoh['clarification_question'])->toContain('tanggal lahir');

    // 2. Topik bintang tanpa nama zodiak atau tanggal
    $resBintang = $this->tool->run(['topic' => 'bintang'], $this->context);
    expect($resBintang['found'])->toBeFalse()
        ->and($resBintang['needs_clarification'])->toBeTrue();

    // 3. Topik garis hidup tanpa angka atau tanggal lahir
    $resGaris = $this->tool->run(['topic' => 'garis_hidup'], $this->context);
    expect($resGaris['found'])->toBeFalse()
        ->and($resGaris['needs_clarification'])->toBeTrue();
});

it('dapat menghitung dan mengembalikan profil kelahiran lengkap untuk 29 Januari 2006 secara akurat', function (): void {
    // 1. Zodiak 29 Januari harus Aquarius (bukan Capricorn)
    $bintang = $this->knowledge->findBintang('29 Januari 2006');
    expect($bintang)->not->toBeNull()
        ->and($bintang['name'])->toBe('Aquarius')
        ->and($bintang['dates'])->toBe('20 Januari - 18 Februari');

    // 2. Garis hidup 2006-01-29: 2+9 + 1 + 2+0+0+6 = 11 + 1 + 8 = 20 -> 2+0 = 2
    $garis = $this->knowledge->findGarisHidup('2006-01-29');
    expect($garis)->not->toBeNull()
        ->and($garis['number'])->toBe(2);

    // 3. getBirthProfile
    $profile = $this->knowledge->getBirthProfile(new \DateTimeImmutable('2006-01-29'));
    expect($profile['bintang']['name'])->toBe('Aquarius')
        ->and($profile['garis_hidup']['number'])->toBe(2)
        ->and($profile['hari_lahir']['nama'])->toBe('Redite (Minggu)')
        ->and($profile['hari_lahir']['karakter'])->not->toBeNull();

    // 4. Tool consult_knowledge dengan topic arti_hari
    $resTool = $this->tool->run(['topic' => 'arti_hari', 'query' => '29 Januari 2006'], $this->context);
    expect($resTool['found'])->toBeTrue()
        ->and($resTool['topic'])->toBe('karakter_kelahiran')
        ->and($resTool['data']['bintang']['name'])->toBe('Aquarius')
        ->and($resTool['data']['garis_hidup']['number'])->toBe(2)
        ->and($resTool['data']['hari_lahir']['nama'])->toBe('Redite (Minggu)');

    // 5. Tool consult_knowledge dengan kalimat query panjang
    $resSearch = $this->tool->run(['topic' => 'search', 'query' => 'bagaimana sifat kelahiran tanggal 29 Januari 2006'], $this->context);
    expect($resSearch['found'])->toBeTrue()
        ->and($resSearch['topic'])->toBe('karakter_kelahiran')
        ->and($resSearch['data']['bintang']['name'])->toBe('Aquarius');
});


