import fs from 'node:fs';

async function fetchPage(url, retries = 5) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url, {
        headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' }
      });
      if (res.ok) {
        return await res.text();
      }
      if (res.status === 429) {
        console.warn(`[429 Rate Limit] Waiting 12 seconds before retry ${i + 1}...`);
        await new Promise(r => setTimeout(r, 12000));
        continue;
      }
    } catch (e) {
      console.warn(`Fetch error: ${e.message}, retry ${i + 1}...`);
    }
    await new Promise(r => setTimeout(r, 3000));
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
  let dictionary = {
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

  const outFile = 'database/data/knowledge_artihari.json';
  if (fs.existsSync(outFile)) {
    try {
      dictionary = JSON.parse(fs.readFileSync(outFile, 'utf8'));
    } catch {}
  }

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

  console.log('--- Phase 1: 35 days (Lintang & Wewaran) ---');
  let current = new Date(Date.UTC(2026, 0, 1));
  for (let i = 0; i < 35; i++) {
    const d = current.getUTCDate();
    const m = current.getUTCMonth() + 1;
    const y = current.getUTCFullYear();
    const url = `https://www.kalenderbali.org/artihari.php?tanggal=${d}&bulan=${m}&tahun=${y}`;
    try {
      const html = await fetchPage(url);
      processHtml(html);
      if ((i + 1) % 5 === 0) {
        fs.writeFileSync(outFile, JSON.stringify(dictionary, null, 2));
        console.log(`[P1] Day ${i + 1}/35 (${y}-${m}-${d}): Lintang=${Object.keys(dictionary.lintang).length}/35, Pratiti=${Object.keys(dictionary.pratiti).length}/12`);
      }
    } catch (e) {
      console.error(`P1 Error on ${y}-${m}-${d}:`, e.message);
    }
    current.setUTCDate(current.getUTCDate() + 1);
    await new Promise(r => setTimeout(r, 1200));
  }

  console.log('--- Phase 2: 30 weeks (30 Wuku) ---');
  let weekDate = new Date(Date.UTC(2026, 0, 1));
  for (let w = 0; w < 30; w++) {
    const d = weekDate.getUTCDate();
    const m = weekDate.getUTCMonth() + 1;
    const y = weekDate.getUTCFullYear();
    const url = `https://www.kalenderbali.org/artihari.php?tanggal=${d}&bulan=${m}&tahun=${y}`;
    try {
      const html = await fetchPage(url);
      processHtml(html);
      if ((w + 1) % 5 === 0) {
        fs.writeFileSync(outFile, JSON.stringify(dictionary, null, 2));
        console.log(`[P2] Week ${w + 1}/30 (${y}-${m}-${d}): Wuku=${Object.keys(dictionary.wuku).length}/30`);
      }
    } catch (e) {
      console.error(`P2 Error on week ${w}:`, e.message);
    }
    weekDate.setUTCDate(weekDate.getUTCDate() + 7);
    await new Promise(r => setTimeout(r, 1200));
  }

  fs.writeFileSync(outFile, JSON.stringify(dictionary, null, 2));
  console.log('\n=== ARTIHARI SCRAPING COMPLETE ===');
  for (const [k, v] of Object.entries(dictionary)) {
    console.log(`${k.padEnd(18)}: ${Object.keys(v).length} entries`);
  }
}

run().catch(console.error);

