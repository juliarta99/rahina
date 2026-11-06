import fs from 'node:fs';

async function fetchPage(url, retries = 3) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0' } });
      if (res.ok) {
        return await res.text();
      }
    } catch (e) {
      if (i === retries - 1) throw e;
    }
    await new Promise(r => setTimeout(r, 1000));
  }
  throw new Error(`Failed to fetch ${url}`);
}

const mapCategory = (rawCat) => {
  const c = rawCat.toLowerCase().trim();
  if (c.includes('ekawara')) return 'ekawara';
  if (c.includes('dwiwara')) return 'dwiwara';
  if (c.includes('triwara')) return 'triwara';
  if (c.includes('caturwara')) return 'caturwara';
  if (c.includes('pancawara') || c.includes('panca wara')) return 'pancawara';
  if (c.includes('sadwara') || c.includes('sad wara')) return 'sadwara';
  if (c.includes('saptawara') || c.includes('sapta wara') || c.includes('saptwara')) return 'saptawara';
  if (c.includes('astawara') || c.includes('asta wara')) return 'astawara';
  if (c.includes('sangawara') || c.includes('sanga wara')) return 'sangawara';
  if (c.includes('dasawara') || c.includes('dasa wara')) return 'dasawara';
  if (c.includes('wuku')) return 'wuku';
  if (c.includes('lintang')) return 'lintang';
  if (c.includes('tanggal') || c.includes('panglong') || c.includes('pangelong') || c.includes('penanggal')) return 'tanggal_panglong';
  if (c.includes('ekajalaresi')) return 'ekajalaresi';
  if (c.includes('pararasan')) return 'pararasan';
  if (c.includes('pancasuda')) return 'pancasuda';
  if (c.includes('pratiti')) return 'pratiti';
  return null;
};

async function run() {
  const dictionary = {
    ekawara: {},
    dwiwara: {},
    triwara: {},
    caturwara: {},
    pancawara: {},
    sadwara: {},
    saptawara: {},
    astawara: {},
    sangawara: {},
    dasawara: {},
    wuku: {},
    lintang: {},
    tanggal_panglong: {},
    ekajalaresi: {},
    pararasan: {},
    pancasuda: {},
    pratiti: {}
  };

  const processHtml = (html) => {
    const matches = [...html.matchAll(/<strong>([^<]+)<\/strong><\/div><div[^>]*>([\s\S]*?)<\/div>/gi)];
    for (const match of matches) {
      const fullHeader = match[1].replace(/<[^>]+>/g, '').trim();
      const value = match[2].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

      const parts = fullHeader.split(':');
      if (parts.length >= 2) {
        const cat = mapCategory(parts[0]);
        const key = parts.slice(1).join(':').trim();
        if (cat && key && value) {
          dictionary[cat][key] = value;
        }
      }
    }
  };

  // Phase 1: 35 consecutive days (covers all 35 Lintang, all 7 Saptawara, 5 Pancawara, 6 Sadwara, etc.)
  console.log('--- Phase 1: 35 consecutive days (Lintang & Wewaran) ---');
  let current = new Date(Date.UTC(2026, 0, 1));
  for (let i = 0; i < 35; i++) {
    const d = current.getUTCDate();
    const m = current.getUTCMonth() + 1;
    const y = current.getUTCFullYear();
    const url = `https://www.kalenderbali.org/artihari.php?tanggal=${d}&bulan=${m}&tahun=${y}`;
    const html = await fetchPage(url);
    processHtml(html);
    current.setUTCDate(current.getUTCDate() + 1);
    await new Promise(r => setTimeout(r, 200));
  }
  console.log(`Phase 1 done. Lintang found: ${Object.keys(dictionary.lintang).length}/35, Wuku found: ${Object.keys(dictionary.wuku).length}/30`);

  // Phase 2: 30 weeks (one date per week, covers all 30 Wuku)
  console.log('--- Phase 2: 30 weeks (All 30 Wuku) ---');
  let weekDate = new Date(Date.UTC(2026, 0, 1));
  for (let w = 0; w < 30; w++) {
    const d = weekDate.getUTCDate();
    const m = weekDate.getUTCMonth() + 1;
    const y = weekDate.getUTCFullYear();
    const url = `https://www.kalenderbali.org/artihari.php?tanggal=${d}&bulan=${m}&tahun=${y}`;
    const html = await fetchPage(url);
    processHtml(html);
    weekDate.setUTCDate(weekDate.getUTCDate() + 7);
    await new Promise(r => setTimeout(r, 200));
  }
  console.log(`Phase 2 done. Wuku found: ${Object.keys(dictionary.wuku).length}/30`);

  console.log('\n=== SUMMARY OF ARTIAHRI DICTIONARY ===');
  for (const [cat, items] of Object.entries(dictionary)) {
    console.log(`${cat.padEnd(18)}: ${Object.keys(items).length} entries`);
  }

  fs.writeFileSync('database/data/knowledge_artihari.json', JSON.stringify(dictionary, null, 2));
  console.log('Saved dictionary to database/data/knowledge_artihari.json');
}

run().catch(console.error);

