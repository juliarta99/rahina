#!/usr/bin/env node
// Konversi data ala-ayuning dewasa kalenderbali.org (diambil DENGAN IZIN pemilik, lihat
// docs/DATA_SOURCES.md §2.3) menjadi database/data/dewasa_rules.json untuk `wariga:load-rules`.
//
//   node scripts/dewasa/convert-kalenderbali-org.mjs \
//        --referensi storage/app/scrape/kalenderbali-org/referensialaayu.html \
//        --days storage/app/scrape/kalenderbali-org/days \
//        [--categories storage/app/scrape/kalenderbali-org/kategori.json] \
//        --out database/data/dewasa_rules.json
//
// Halaman per tanggal (--days, alaayu.php) dipakai untuk: (1) tingkat alahing, (2) menentukan arti
// token ambigu ("Sri" ada di caturwara/astawara/dasawara) dengan mencocokkan ke daftar situs.
// Pemetaan efek ke kategori memakai --categories (dari pilihdewasa.php) bila ada, selain itu
// kamus kata kunci KEYWORDS di bawah. Kalimat asli disimpan di `note` setiap efek.
import { existsSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { toRecord } from '../calendar/lib.mjs';

const args = process.argv.slice(2);
const opt = (name) => {
    const i = args.indexOf(name);
    return i === -1 ? null : args[i + 1];
};
const referensiPath = opt('--referensi');
const daysDir = opt('--days');
const _categoriesPath = opt('--categories'); // dicadangkan: pemetaan kategori dari pilihdewasa.php
const outPath = opt('--out') ?? 'database/data/dewasa_rules.json';
if (!referensiPath) {
    console.error('Wajib: --referensi <referensialaayu.html>');
    process.exit(1);
}

const master = JSON.parse(
    readFileSync('database/data/wariga_master.json', 'utf8'),
);
const decode = (s) =>
    s
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/&quot;/g, '"')
        .replace(/&#0?39;/g, "'")
        // Situs menyajikan UTF-8 berlabel iso-8859-1; NBSP ter-encode ganda menjadi U+00C2 U+00A0.
        .replace(new RegExp(String.fromCharCode(0xc2), 'g'), '')
        .replace(new RegExp(String.fromCharCode(0xa0), 'g'), ' ')
        .replace(new RegExp(String.fromCharCode(0xfffd), 'g'), '')
        .replace(/\s+/g, ' ')
        .trim();

// ---------------------------------------------------------------- 1. parse referensi
const html = readFileSync(referensiPath, 'utf8');
const body = html.slice(html.indexOf('Wewaran Penyusun</div>'));
const rowRe =
    /<tr class="daftar[^"]*">\s*<td[^>]*>([\s\S]*?)<\/td>\s*<td[^>]*>([\s\S]*?)<\/td>\s*<td[^>]*>([\s\S]*?)(?=<tr class="daftar|<\/table>)/g;
const entries = [];
for (const m of body.matchAll(rowRe)) {
    entries.push({
        name: decode(m[1]),
        description: decode(m[2]),
        combos: decode(m[3])
            .split('.')
            .map((c) => c.trim())
            .filter(Boolean)
            .map((c) => c.split(' ')),
    });
}

// ---------------------------------------------------------------- 2. token → field
const cycles = {
    dwiwara: master.wewaran.filter((w) => w.cycle === 2).map((w) => w.name),
    triwara: master.wewaran.filter((w) => w.cycle === 3).map((w) => w.name),
    caturwara: master.wewaran.filter((w) => w.cycle === 4).map((w) => w.name),
    pancawara: master.wewaran.filter((w) => w.cycle === 5).map((w) => w.name),
    sadwara: master.wewaran.filter((w) => w.cycle === 6).map((w) => w.name),
    saptawara: master.wewaran.filter((w) => w.cycle === 7).map((w) => w.name),
    astawara: master.wewaran.filter((w) => w.cycle === 8).map((w) => w.name),
    sangawara: master.wewaran.filter((w) => w.cycle === 9).map((w) => w.name),
    dasawara: master.wewaran.filter((w) => w.cycle === 10).map((w) => w.name),
    wuku: master.wuku.map((w) => w.name),
    sasih: master.sasih.filter((s) => !s.is_intercalary).map((s) => s.name),
};
// Ejaan situs → ejaan data master (hanya ejaan, tidak mengubah makna).
const SPELLING = {
    Keliwon: 'Kliwon',
    Dunggulan: 'Dungulan',
    Kulawu: 'Klawu',
    Kedasa: 'Kadasa',
    pengelong: 'Pangelong',
};
// Prioritas siklus bila satu nama ada di beberapa siklus (dipakai hanya bila tidak ada resolusi dari data).
const ORDER = [
    'saptawara',
    'pancawara',
    'triwara',
    'wuku',
    'sadwara',
    'astawara',
    'sangawara',
    'dasawara',
    'caturwara',
    'dwiwara',
    'sasih',
];

function fieldsOf(token) {
    return ORDER.filter((f) => cycles[f].includes(token));
}

/** Satu kombinasi → daftar alternatif kondisi (lebih dari satu bila ada token ambigu). */
function comboAlternatives(rawTokens) {
    const tokens = rawTokens.map((t) => SPELLING[t] ?? t.replace(/,$/, ''));
    if (tokens.includes('=') && tokens.includes('Sasih')) {
        // Naga Naut: "Penanggal, pengelong = Sasih" → angka hari sasih = urutan sasih.
        return [[{ field: 'sasih_day', eq_field: 'sasih_number' }]];
    }
    let alts = [[]];
    for (let i = 0; i < tokens.length; i++) {
        const t = tokens[i];
        let options;
        if (
            (t === 'Penanggal' || t === 'Pangelong') &&
            /^\d+$/.test(tokens[i + 1] ?? '')
        ) {
            options = [
                {
                    field: 'sasih_day',
                    eq: Number(tokens[i + 1]),
                    with: { is_penanggal: t === 'Penanggal' },
                },
            ];
            i++;
        } else if (t === 'Penanggal' || t === 'Pangelong') {
            // Tanpa angka: cukup fase sasihnya (mis. Kajeng Rendetan = ... Kajeng Penanggal).
            options = [{ field: 'is_penanggal', eq: t === 'Penanggal' }];
        } else if (t === 'Purnama') {
            options = [{ field: 'is_purnama', eq: true }];
        } else if (t === 'Tilem') {
            options = [{ field: 'is_tilem', eq: true }];
        } else {
            const fields = fieldsOf(t);
            if (fields.length === 0)
                throw new Error(`Token tidak dikenal: "${t}"`);
            options = fields.map((f) => ({ field: f, in: [t] }));
        }
        alts = alts.flatMap((a) =>
            options
                // Dua token sama ("Sri Sri") harus jatuh ke siklus berbeda.
                .filter((o) => !a.some((x) => x.field === o.field && o.in))
                .map((o) => [...a, o]),
        );
    }
    return alts;
}

// ---------------------------------------------------------------- 3. evaluator kecil (untuk resolusi)
function facts(date) {
    const [y, m, d] = date.split('-').map(Number);
    const r = toRecord(y, m, d);
    const name = (cycle, pos) =>
        master.wewaran.find((w) => w.cycle === cycle && w.position === pos)
            ?.name ?? null;
    const sasih = master.sasih.find((s) => s.id === r.sasih);
    const keys = [
        'ekawara',
        'dwiwara',
        'triwara',
        'caturwara',
        'pancawara',
        'sadwara',
        'saptawara',
        'astawara',
        'sangawara',
        'dasawara',
    ];
    const f = {
        wuku: master.wuku.find((w) => w.id === r.wuku).name,
        sasih: sasih.name,
        sasih_number: sasih.base_id,
        sasih_day:
            r.sasih_day_end === null
                ? [r.sasih_day]
                : [r.sasih_day, r.sasih_day_end],
        is_penanggal: r.is_penanggal,
        is_purnama: r.is_purnama,
        is_tilem: r.is_tilem,
    };
    keys.forEach((k, i) => (f[k] = r[k] === null ? null : name(i + 1, r[k])));
    return f;
}
function leafOk(leaf, f) {
    const vals = Array.isArray(f[leaf.field]) ? f[leaf.field] : [f[leaf.field]];
    let ok;
    if ('in' in leaf) ok = vals.some((v) => leaf.in.includes(v));
    else if ('eq_field' in leaf) ok = vals.some((v) => v === f[leaf.eq_field]);
    else ok = vals.some((v) => v === leaf.eq);
    if (ok && leaf.with)
        ok = Object.entries(leaf.with).every(([k, v]) => f[k] === v);
    return ok;
}
const comboOk = (combo, f) => combo.every((l) => leafOk(l, f));

// ---------------------------------------------------------------- 4. halaman per tanggal
const siteDays = []; // {date, names:Set, alahing:{name:level}}
if (daysDir && existsSync(daysDir)) {
    for (const file of readdirSync(daysDir)
        .filter((n) => n.endsWith('.html'))
        .sort()) {
        const page = readFileSync(join(daysDir, file), 'utf8');
        const names = new Set();
        const alahing = {};
        for (const m of page.matchAll(
            /<li[^>]*><b>([^<]+)<\/b>\.([\s\S]*?)<\/li>/g,
        )) {
            const n = decode(m[1]);
            names.add(n);
            const lv = m[2].match(/Alahing dewasa (\d)/);
            if (lv) alahing[n] = Number(lv[1]);
        }
        siteDays.push({
            date: file.replace('.html', ''),
            names,
            alahing,
            facts: facts(file.replace('.html', '')),
        });
    }
}

// ---------------------------------------------------------------- 5. efek → kategori
const KEYWORDS = [
    [
        'upacara.pawiwahan',
        /\b(nikah|menikah|pernikahan|perkawinan|wiwaha|pawiwahan)\b/,
    ],
    [
        'upacara.ngaben',
        /\b(ngaben|atiwa|pitra yadnya|penguburan|mengubur|nyekah|ngasti|mayat|jenazah)/,
    ],
    ['upacara.melaspas', /\b(melaspas|mlaspas)\b/],
    [
        'upacara',
        /\b(yadnya|upacara|sembahyang|memuja|pemujaan|piodalan|odalan|mapendes|potong gigi|potong rambut|tapa|brata|yoga|semadi|penyucian|mabersih|banten|pura|tempat suci)/,
    ],
    ['pertanian.padi', /\bpadi\b/],
    ['pertanian.umbi', /\b(umbi|ubi|singkong|ketela|keladi)\b/],
    [
        'pertanian',
        /\b(menanam|tanam|tanaman|bercocok|pertanian|sawah|kebun|perkebunan|bibit|benih|panen|memetik|pohon|buah|irigasi|lahan)\b/,
    ],
    [
        'peternakan',
        /\b(ternak|hewan|sapi|babi|ayam|itik|bebek|kerbau|kambing|kuda|ikan|berburu|mapikat|memikat|jerat|burung|kandang|wewalungan|unggas|perikanan|nelusuk)\b/,
    ],
    [
        'peralatan',
        /\b(senjata|alat|peralatan|besi|pande|keris|tombak|perkakas|pisau)\b/,
    ],
    [
        'pembangunan',
        /\b(rumah|bangunan|membangun|mengatapi|atap|pekarangan|pondasi|tembok|pindah rumah|sumur|dapur|lumbung|bata|genteng|bale|kayu)\b/,
    ],
    [
        'dagang.buka_usaha',
        /\b(dagang|berdagang|jual|menjual|beli|membeli|usaha|pasar|warung|toko|berniaga|modal|meminjam|meminjamkan|utang|hutang|uang)\b/,
    ],
];

const ALL_DOMAINS = [
    'upacara',
    'pertanian',
    'peternakan',
    'peralatan',
    'pembangunan',
    'aneka',
];

function effectsOf(description) {
    const text = description.toLowerCase();
    const markers = [
        ...text.matchAll(
            /(tidak\s+baik|kurang\s+baik|jangan|pantang|hindari|baik)/g,
        ),
    ];
    const effects = new Map();
    markers.forEach((m, i) => {
        const segment = text.slice(
            m.index,
            markers[i + 1]?.index ?? text.length,
        );
        const effect = /^(tidak|kurang|jangan|pantang|hindari)/.test(m[0])
            ? 'tidak_baik'
            : 'baik';
        const original = description
            .slice(m.index, markers[i + 1]?.index ?? description.length)
            .trim()
            .replace(/[,.]$/, '');
        const cats = KEYWORDS.filter(([, re]) => re.test(segment)).map(
            ([c]) => c,
        );
        // Kategori sub (mis. pertanian.padi) sudah mewakili domainnya.
        const specific = cats.filter(
            (c) => !cats.some((o) => o !== c && o.startsWith(`${c}.`)),
        );
        // Pernyataan umum ("dewasa ayu", "segala pekerjaan") berlaku untuk semua domain kegiatan.
        const general =
            /(dewasa ayu|karya ayu|segala (pekerjaan|kegiatan|macam)|semua (pekerjaan|kegiatan)|hari baik)/.test(
                segment,
            );
        const targets =
            specific.length > 0 ? specific : general ? ALL_DOMAINS : ['aneka'];
        for (const c of targets) {
            const key = `${c}|${effect}`;
            if (!effects.has(key))
                effects.set(key, {
                    category: c,
                    effect,
                    note: original.slice(0, 300),
                });
        }
    });
    return [...effects.values()];
}

// ---------------------------------------------------------------- 6. bangun aturan
const slug = (s) =>
    s
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '')
        .slice(0, 64);
const rules = [];
const report = {
    ambiguous: [],
    noEffect: [],
    fallbackAneka: 0,
    validation: { tp: 0, fp: 0, fn: 0 },
};
const used = new Set();

for (const e of entries) {
    // Kombinasi dengan token ambigu: pilih alternatif yang paling cocok dengan daftar situs per tanggal.
    const combos = e.combos.map((tokens) => {
        const alts = comboAlternatives(tokens);
        if (alts.length === 1 || siteDays.length === 0) return alts[0];
        // Skor = hari kombinasi berlaku & situs mencantumkan, dikurangi 2× hari berlaku tapi situs tidak.
        const hits = (alt) => {
            const on = siteDays.filter((d) => comboOk(alt, d.facts));
            const tp = on.filter((d) => d.names.has(e.name)).length;
            return { tp, fp: on.length - tp, score: tp - 2 * (on.length - tp) };
        };
        const best = [...alts].sort((a, b) => hits(b).score - hits(a).score)[0];
        const h = hits(best);
        report.ambiguous.push(
            `${e.name}: "${tokens.join(' ')}" → ${best.map((l) => l.field).join(' + ')} (berlaku ${h.tp + h.fp} hari, ${h.tp} dicantumkan situs; alternatif: ${alts.map((a) => `${a.map((l) => l.field).join('+')}=${hits(a).score}`).join(', ')})`,
        );
        return best;
    });

    let code = slug(e.name);
    while (used.has(code)) code = `${code}_2`;
    used.add(code);

    const levels = siteDays
        .map((d) => d.alahing[e.name])
        .filter((v) => v !== undefined);
    const alahing = levels.length
        ? levels.sort(
              (a, b) =>
                  levels.filter((v) => v === b).length -
                  levels.filter((v) => v === a).length,
          )[0]
        : null;

    let effects = effectsOf(e.description);
    if (effects.length === 0) {
        report.noEffect.push(e.name);
        effects = [
            {
                category: 'aneka',
                effect: 'baik',
                note: e.description.slice(0, 300),
            },
        ];
    }
    report.fallbackAneka += effects.filter(
        (x) => x.category === 'aneka',
    ).length;
    const kinds = new Set(effects.map((x) => x.effect));

    rules.push({
        code,
        name: e.name,
        summary: e.description.slice(0, 600),
        verdict: kinds.size > 1 ? 'campur' : kinds.has('baik') ? 'ayu' : 'ala',
        alahing_level: alahing,
        conditions:
            combos.length === 1
                ? { all: combos[0] }
                : { any: combos.map((c) => ({ all: c })) },
        source: 'kalenderbali-org',
        source_page: 'referensialaayu.php',
        effects,
    });

    for (const d of siteDays) {
        const ours = combos.some((c) => comboOk(c, d.facts));
        const theirs = d.names.has(e.name);
        if (ours && theirs) report.validation.tp++;
        else if (ours) report.validation.fp++;
        else if (theirs) report.validation.fn++;
    }
}

writeFileSync(
    outPath,
    JSON.stringify(
        {
            _note: 'Dihasilkan otomatis oleh scripts/dewasa/convert-kalenderbali-org.mjs dari kalenderbali.org (dengan izin pemilik, docs/DATA_SOURCES.md §2.3). Jangan edit manual; perbaiki converter lalu jalankan ulang. Aturan belum ditinjau narasumber (validated_at kosong).',
            rules,
            precedence: [],
        },
        null,
        2,
    ) + '\n',
);

const v = report.validation;
console.log(
    `${rules.length} aturan, ${rules.reduce((n, r) => n + (r.conditions.any?.length ?? 1), 0)} kombinasi → ${outPath}`,
);
console.log(
    `Alahing terisi: ${rules.filter((r) => r.alahing_level !== null).length}/${rules.length}`,
);
console.log(
    `Validasi vs ${siteDays.length} hari situs: cocok ${v.tp}, hanya engine ${v.fp}, hanya situs ${v.fn}`,
);
console.log(
    'Token ambigu:',
    report.ambiguous.length ? '\n  ' + report.ambiguous.join('\n  ') : '-',
);
console.log(
    `Efek tanpa kata kunci kategori (→ aneka): ${report.fallbackAneka}; tanpa penanda baik/tidak baik: ${report.noEffect.join(', ') || '-'}`,
);
