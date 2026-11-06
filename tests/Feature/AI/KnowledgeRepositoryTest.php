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

it('dapat mengekstrak banyak tanggal dari teks bebas dengan parseAllDates', function (): void {
    // 1. Format teks bahasa Indonesia dengan nama bulan
    $dates1 = $this->knowledge->parseAllDates('Kecocokan jodoh antara kelahiran 15 Mei 1998 dan 20 Oktober 1999');
    expect($dates1)->toHaveCount(2)
        ->and($dates1[0]->toDateString())->toBe('1998-05-15')
        ->and($dates1[1]->toDateString())->toBe('1999-10-20');

    // 2. Format ISO YYYY-MM-DD
    $dates2 = $this->knowledge->parseAllDates('1998-05-15 dengan 1999-10-20');
    expect($dates2)->toHaveCount(2)
        ->and($dates2[0]->toDateString())->toBe('1998-05-15')
        ->and($dates2[1]->toDateString())->toBe('1999-10-20');

    // 3. Format DD/MM/YYYY
    $dates3 = $this->knowledge->parseAllDates('15/05/1998 dan 20/10/1999');
    expect($dates3)->toHaveCount(2)
        ->and($dates3[0]->toDateString())->toBe('1998-05-15')
        ->and($dates3[1]->toDateString())->toBe('1999-10-20');
});

it('dapat menghitung perjodohan Lontar Tri Pramana (mod 16) dengan variasi kategori yang benar', function (): void {
    // 1. Pasangan dengan verdict 'ayu' (Baik / Rukun & Bahagia)
    // 1998-05-15 (urip 22) + 1999-10-20 (urip 17) = 39. 39 mod 16 = 7 -> ayu (suka duka akhirnya bahagia)
    $jodohAyu = $this->knowledge->calculateJodoh('1998-05-15', '1999-10-20');
    expect($jodohAyu['verdict'])->toBe('ayu')
        ->and($jodohAyu['kategori'])->toBe('Baik / Rukun & Bahagia')
        ->and($jodohAyu['sisa'])->toBe(7)
        ->and($jodohAyu['cultural_notice'])->toBeTrue();

    // 2. Pasangan dengan verdict 'ala' (Kurang Baik / Perlu Kehati-hatian)
    // Cek bahwa sistem memiliki aturan ala dan dapat menghitungnya
    $jodohSample = $this->knowledge->calculateJodoh('1990-01-01', '1992-06-05');
    // Jika sisa bukan ala, cari tanggal yang menghasilkan ala
    $targetAla = null;
    $checkDates = ['1990-01-01', '1990-01-02', '1990-01-03', '1990-01-04', '1990-01-05', '1990-01-06'];
    foreach ($checkDates as $cd) {
        $c = $this->knowledge->calculateJodoh('1990-01-01', $cd);
        if ($c['verdict'] === 'ala') {
            $targetAla = $c;
            break;
        }
    }
    expect($targetAla)->not->toBeNull()
        ->and($targetAla['verdict'])->toBe('ala')
        ->and($targetAla['kategori'])->toBe('Kurang Baik / Perlu Kehati-hatian');

    // 3. Pasangan dengan verdict 'campur' (Suka Duka Berimbang)
    $targetCampur = null;
    $checkDatesCampur = ['1990-01-01', '1990-01-02', '1990-01-03', '1990-01-04', '1990-01-05', '1990-01-06', '1990-01-07', '1990-01-08'];
    foreach ($checkDatesCampur as $cd) {
        $c = $this->knowledge->calculateJodoh('1990-01-01', $cd);
        if ($c['verdict'] === 'campur') {
            $targetCampur = $c;
            break;
        }
    }
    expect($targetCampur)->not->toBeNull()
        ->and($targetCampur['verdict'])->toBe('campur')
        ->and($targetCampur['kategori'])->toBe('Suka Duka Berimbang');

    // 4. Modulo 16 tepat (sisa 0 dipetakan ke 16)
    // Cek sisa 16 menghasilkan ayu
    $allJodoh = $this->knowledge->allJodoh();
    $sisa16Rule = array_values(array_filter($allJodoh['rules'], fn ($r) => (int) $r['sisa'] === 16))[0] ?? null;
    expect($sisa16Rule)->not->toBeNull()
        ->and($sisa16Rule['verdict'])->toBe('ayu');
});

it('dapat menangani konsultasi jodoh lewat tool consult_knowledge dalam berbagai format query', function (): void {
    // 1. Argumen birthdate dan partner_birthdate terpisah
    $res1 = $this->tool->run([
        'topic' => 'jodoh',
        'birthdate' => '15 Mei 1998',
        'partner_birthdate' => '20 Oktober 1999',
    ], $this->context);
    expect($res1['found'])->toBeTrue()
        ->and($res1['data']['verdict'])->toBe('ayu')
        ->and($this->context->allowedDates)->toContain('1998-05-15', '1999-10-20');

    // 2. Argumen kedua tanggal digabung dalam satu teks query
    $context2 = new AgentContext('2026-11-06');
    $res2 = $this->tool->run([
        'topic' => 'jodoh',
        'query' => 'Kecocokan 15 Mei 1998 dan 20 Oktober 1999',
    ], $context2);
    expect($res2['found'])->toBeTrue()
        ->and($res2['data']['verdict'])->toBe('ayu')
        ->and($context2->allowedDates)->toContain('1998-05-15', '1999-10-20');

    // 3. Topik search tetapi ada kata kunci jodoh dan 2 tanggal
    $context3 = new AgentContext('2026-11-06');
    $res3 = $this->tool->run([
        'topic' => 'search',
        'query' => 'bagaimana kecocokan jodoh antara kelahiran 15 Mei 1998 dan 20 Oktober 1999',
    ], $context3);
    expect($res3['found'])->toBeTrue()
        ->and($res3['topic'])->toBe('jodoh')
        ->and($res3['data']['verdict'])->toBe('ayu');

    // 4. Topik search dengan kata kunci jodoh tetapi HANYA 1 tanggal -> minta tanggal pasangan
    $context4 = new AgentContext('2026-11-06');
    $res4 = $this->tool->run([
        'topic' => 'search',
        'query' => 'bagaimana ramalan jodoh saya kelahiran 15 Mei 1998',
    ], $context4);
    expect($res4['found'])->toBeFalse()
        ->and($res4['needs_clarification'])->toBeTrue()
        ->and($res4['clarification_question'])->toContain('pasangan');
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

it('meloloskan jawaban Tanya Wariga yang menyebut tanggal lahir dan zodiak tanpa dibuang oleh OutputGuard', function (): void {
    seedReferenceCalendar();
    $guard = app(\App\AI\OutputGuard::class);

    // Kasus 1: Sifat kelahiran 29 Januari 2006 (menyebut 29 Januari 2006 dan zodiak 20 Januari - 18 Februari)
    $context1 = new AgentContext('2026-11-06');
    $this->tool->run(['topic' => 'arti_hari', 'query' => '29 Januari 2006'], $context1);

    $answerText1 = 'Kelahiran 29 Januari 2006 memiliki bintang Aquarius 20 Januari - 18 Februari dengan Angka Garis Hidup 2. Lahir pada hari Redite Minggu yang memiliki watak tenang.';
    $output1 = [
        'answer' => $answerText1,
        'dates' => [],
        'needs_clarification' => false,
        'clarification_options' => [],
        'cultural_notice' => true,
        'out_of_scope' => false,
    ];
    $ans1 = $guard->answer($output1, $context1);
    expect($ans1->generatedBy)->toBe(\App\Data\AgentAnswer::BY_AI)
        ->and($ans1->answer)->toBe($answerText1);

    // Kasus 2: Jodoh 15 Mei 1998 dan 20 Oktober 1999
    $context2 = new AgentContext('2026-11-06');
    $this->tool->run(['topic' => 'jodoh', 'query' => '15 Mei 1998 dan 20 Oktober 1999'], $context2);

    $answerText2 = 'Menurut Lontar Tri Pramana, pasangan kelahiran 15 Mei 1998 dan 20 Oktober 1999 memiliki total urip 39 dengan sisa 7. Kategori hubungan ini adalah Baik / Rukun & Bahagia.';
    $output2 = [
        'answer' => $answerText2,
        'dates' => [],
        'needs_clarification' => false,
        'cultural_notice' => true,
        'out_of_scope' => false,
    ];
    $ans2 = $guard->answer($output2, $context2);
    expect($ans2->generatedBy)->toBe(\App\Data\AgentAnswer::BY_AI)
        ->and($ans2->answer)->toBe($answerText2);
});



