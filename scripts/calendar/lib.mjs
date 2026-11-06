// Pemetaan hasil balinese-date-js → baris JSONL untuk wariga:import.
// Tidak ada logika kalender di sini: semua nilai berasal dari library.
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join } from 'node:path';
import * as B from 'balinese-date-js';

const require = createRequire(import.meta.url);
// package.json tidak diekspor oleh library; entry ada di <pkg>/dist/index.cjs.
const pkgPath = join(
    dirname(require.resolve('balinese-date-js')),
    '..',
    'package.json',
);
const pkg = JSON.parse(readFileSync(pkgPath, 'utf8'));

export const ENGINE = `${pkg.name}@${pkg.version}`;

/** Urutan siklus wewaran (cycle 1–10) beserta properti BalineseDate dan daftar nilainya. */
export const WEWARAN = [
    { cycle: 1, key: 'ekawara', prop: 'ekaWara', values: B.EKA_WARA_VALUES },
    { cycle: 2, key: 'dwiwara', prop: 'dwiWara', values: B.DWI_WARA_VALUES },
    { cycle: 3, key: 'triwara', prop: 'triWara', values: B.TRI_WARA_VALUES },
    {
        cycle: 4,
        key: 'caturwara',
        prop: 'caturWara',
        values: B.CATUR_WARA_VALUES,
    },
    {
        cycle: 5,
        key: 'pancawara',
        prop: 'pancaWara',
        values: B.PANCA_WARA_VALUES,
    },
    { cycle: 6, key: 'sadwara', prop: 'sadWara', values: B.SAD_WARA_VALUES },
    {
        cycle: 7,
        key: 'saptawara',
        prop: 'saptaWara',
        values: B.SAPTA_WARA_VALUES,
    },
    { cycle: 8, key: 'astawara', prop: 'astaWara', values: B.ASTA_WARA_VALUES },
    {
        cycle: 9,
        key: 'sangawara',
        prop: 'sangaWara',
        values: B.SANGA_WARA_VALUES,
    },
    {
        cycle: 10,
        key: 'dasawara',
        prop: 'dasaWara',
        values: B.DASA_WARA_VALUES,
    },
];

/** Paringkelan (nama saja) → kolom calendar_days. */
export const PARINGKELAN = [
    ['jejepan', 'jejepan'],
    ['lintang', 'lintang'],
    ['panca_suda', 'pancaSuda'],
    ['rakam', 'rakam'],
    ['eka_jala_rsi', 'ekaJalaRsi'],
    ['watek_alit', 'watekAlit'],
    ['watek_madya', 'watekMadya'],
    ['pararasan', 'pararasan'],
    ['pratithi_samut_pada', 'pratithiSamutPada'],
];

const pad = (n) => String(n).padStart(2, '0');

/** @param {number} y @param {number} m 1–12 @param {number} d */
export function toRecord(y, m, d) {
    const bd = new B.BalineseDate(new Date(y, m - 1, d));
    const record = {
        date: `${y}-${pad(m)}-${pad(d)}`,
        saka_year: bd.saka,
        wuku: bd.wuku.id + 1,
    };

    for (const w of WEWARAN) {
        const info = bd[w.prop];
        // Ekawara hanya bernilai pada hari "Luang"; selain itu kosong.
        record[w.key] =
            info.name === '' ? null : info.id + (w.cycle === 1 ? 0 : 1);
    }

    const dayInfo = bd.sasihDayInfo;
    record.urip_total = bd.saptaWara.urip + bd.pancaWara.urip;
    record.sasih = bd.sasih.id + 1;
    record.sasih_day = bd.sasihDay[0];
    record.sasih_day_end = bd.sasihDay.length > 1 ? bd.sasihDay[1] : null;
    record.is_penanggal = dayInfo.refId === B.SasihDayInfo.PENANGGAL;
    record.is_purnama = dayInfo.id === B.SasihDayInfo.PURNAMA;
    record.is_tilem = dayInfo.id === B.SasihDayInfo.TILEM;
    record.is_nguna_ratri = bd.sasihDay.length > 1;
    record.ingkel = bd.ingkel.name;
    for (const [key, prop] of PARINGKELAN) record[key] = bd[prop].name || null;
    record.rahinan = B.getRahinan(bd).map((r) => r.id + 1);
    record.engine = ENGINE;

    return record;
}

/** Iterasi tanggal sipil [start, end] inklusif, format YYYY-MM-DD. */
export function* eachDate(startYear, endYear) {
    for (let y = startYear; y <= endYear; y++) {
        for (let m = 1; m <= 12; m++) {
            const days = new Date(y, m, 0).getDate();
            for (let d = 1; d <= days; d++) yield [y, m, d];
        }
    }
}

// Dasar perhitungan rahinan (pawukon / sasih / kajeng kliwon / purnama-tilem).
// Ini klasifikasi teknis berdasar siklus yang dipakai library, dicatat di CULTURAL_REVIEW (CR-005).
const RAHINAN_KIND = {
    [B.Rahinan.KAJENG_KLIWON]: 'kajeng_kliwon',
    [B.Rahinan.PURNAMA]: 'purnama_tilem',
    [B.Rahinan.TILEM]: 'purnama_tilem',
    [B.Rahinan.SIWA_RATRI]: 'sasih',
    [B.Rahinan.TAWUR_AGUNG_KASANGA]: 'sasih',
    [B.Rahinan.NYEPI]: 'sasih',
    [B.Rahinan.NGEMBAK_GENI]: 'sasih',
};

export function masters() {
    const slug = (s) =>
        s
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '');

    return {
        engine: ENGINE,
        wuku: B.WUKU_VALUES.map((w) => ({
            id: w.id + 1,
            name: w.name,
            urip: w.urip,
        })),
        wewaran: WEWARAN.flatMap((w) =>
            w.values
                .filter((v) => v.name !== '')
                .map((v) => ({
                    cycle: w.cycle,
                    position: w.cycle === 1 ? v.id : v.id + 1,
                    name: v.name,
                    urip: v.urip,
                })),
        ),
        ingkel: B.INGKEL_VALUES.map((i) => i.name),
        sasih: B.SASIH_VALUES.map((s) => ({
            id: s.id + 1,
            name: s.name,
            base_id: s.refId + 1,
            is_intercalary: s.id !== s.refId,
        })),
        rahinan: B.RAHINAN_VALUES.map((r) => ({
            id: r.id + 1,
            slug: slug(r.name),
            name: r.name,
            kind: RAHINAN_KIND[r.id] ?? 'pawukon',
        })),
    };
}
