#!/usr/bin/env node
// Menulis data master (wuku, wewaran, sasih, rahinan) dari balinese-date-js
// ke database/data/wariga_master.json, yang dibaca oleh WarigaMasterSeeder.
//   node scripts/calendar/masters.mjs
import { writeFileSync } from 'node:fs';
import { masters } from './lib.mjs';

const out = 'database/data/wariga_master.json';
writeFileSync(out, JSON.stringify(masters(), null, 2) + '\n');
console.log(`→ ${out}`);
