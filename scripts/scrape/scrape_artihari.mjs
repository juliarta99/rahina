import fs from 'node:fs';

async function fetchPage(url, retries = 3) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' } });
      if (res.ok) return await res.text();
    } catch (e) {
      if (i === retries - 1) throw e;
      await new Promise(r => setTimeout(r, 1000));
    }
  }
}

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

  console.log('Starting Artihari crawl across 210 days (1 pawukon)...');
  // Start from 2026-01-01 and iterate 210 days
  let current = new Date(Date.UTC(2026, 0, 1));
  
  for (let step = 0; step < 210; step++) {
    const d = current.getUTCDate();
    const m = current.getUTCMonth() + 1;
    const y = current.getUTCFullYear();

    const url = `https://www.kalenderbali.org/artihari.php?tanggal=${d}&bulan=${m}&tahun=${y}`;
    try {
      const html = await fetchPage(url);
      const matches = [...html.matchAll(/<strong>([^<]+)<\/strong><\/div><div[^>]*>([\s\S]*?)<\/div>/gi)];
      
      for (const match of matches) {
        const fullHeader = match[1].replace(/<[^>]+>/g, '').trim();
        const value = match[2].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

        // format is typically "Ekawara: Luang" or "Pratiti: Awidya"
        const parts = fullHeader.split(':');
        if (parts.length >= 2) {
          const cat = mapCategory(parts[0]);
          const key = parts.slice(1).join(':').trim();
          if (cat && key && value) {
            dictionary[cat][key] = value;
          }
        }
      }

      if ((step + 1) % 30 === 0 || step === 209) {
        console.log(`Step ${step + 1}/210 (${y}-${m}-${d}): wuku=${Object.keys(dictionary.wuku).length}/30, lintang=${Object.keys(dictionary.lintang).length}/35, pratiti=${Object.keys(dictionary.pratiti).length}/12`);
      }
    } catch (err) {
      console.error(`Error on ${y}-${m}-${d}:`, err.message);
    }

    current.setUTCDate(current.getUTCDate() + 1);
    await new Promise(r => setTimeout(r, 60));
  }

  console.log('\n--- CRAWL FINISHED ---');
  for (const [cat, items] of Object.entries(dictionary)) {
    console.log(`${cat}: ${Object.keys(items).length} entries`);
  }

  fs.writeFileSync('database/data/knowledge_artihari.json', JSON.stringify(dictionary, null, 2));
  console.log('Saved to database/data/knowledge_artihari.json');
}

run().catch(console.error);

