#!/usr/bin/env node
// Menghasilkan JSONL kalender (1 baris per hari) dari balinese-date-js.
//
//   node scripts/calendar/compute.mjs 2020 2035              → storage/app/calendar/YYYY.jsonl
//   node scripts/calendar/compute.mjs --dates <file.json> --out <file.jsonl>
//        (file.json = array objek ber-"date" atau {days: [...]}, mis. tests/Fixtures/reference_days.json)
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { eachDate, toRecord, ENGINE } from './lib.mjs';

const args = process.argv.slice(2);
const opt = (name) => {
    const i = args.indexOf(name);
    return i === -1 ? null : args[i + 1];
};

const datesFile = opt('--dates');

if (datesFile) {
    const out = opt('--out');
    if (!out) throw new Error('--out wajib diisi bersama --dates');
    const parsed = JSON.parse(readFileSync(datesFile, 'utf8'));
    const entries = Array.isArray(parsed) ? parsed : parsed.days;
    const lines = entries.map((e) => {
        const [y, m, d] = e.date.split('-').map(Number);
        return JSON.stringify(toRecord(y, m, d));
    });
    mkdirSync(dirname(out), { recursive: true });
    writeFileSync(out, lines.join('\n') + '\n');
    console.log(`${lines.length} tanggal → ${out} (${ENGINE})`);
    process.exit(0);
}

const start = Number(args[0]);
const end = Number(args[1] ?? args[0]);
if (
    !Number.isInteger(start) ||
    !Number.isInteger(end) ||
    start > end ||
    start < 1900 ||
    end > 2100
) {
    console.error(
        'Pemakaian: node scripts/calendar/compute.mjs <tahun_awal> [tahun_akhir]',
    );
    process.exit(1);
}

const outDir = opt('--out-dir') ?? join('storage', 'app', 'calendar');
mkdirSync(outDir, { recursive: true });

for (let year = start; year <= end; year++) {
    const lines = [];
    for (const [y, m, d] of eachDate(year, year))
        lines.push(JSON.stringify(toRecord(y, m, d)));
    writeFileSync(join(outDir, `${year}.jsonl`), lines.join('\n') + '\n');
    console.log(`${year}: ${lines.length} hari`);
}
console.log(`Selesai (${ENGINE}) → ${outDir}`);
