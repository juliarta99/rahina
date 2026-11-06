import fs from 'node:fs';

async function fetchPage(url, retries = 5) {
  for (let i = 0; i < retries; i++) {
    try {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 12000);
      const res = await fetch(url, {
        headers: {
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        },
        signal: controller.signal
      });
      clearTimeout(timeout);
      if (res.ok) {
        return await res.text();
      }
      console.warn(`[HTTP ${res.status}] ${url}, retry ${i + 1}/${retries}`);
    } catch (e) {
      console.warn(`[Fetch error: ${e.message}] ${url}, retry ${i + 1}/${retries}`);
    }
    await new Promise(r => setTimeout(r, 2000 * (i + 1)));
  }
  throw new Error(`Failed to fetch ${url} after ${retries} attempts`);
}

// 1. SCRAPE BINTANG
async function scrapeBintang() {
  console.log('=== 1. Scraping 12 Bintang / Zodiak ===');
  const results = [];
  for (let id = 1; id <= 12; id++) {
    const url = `https://www.kalenderbali.org/bintang.php?id=${id}`;
    const html = await fetchPage(url);
    const titleMatch = html.match(/<h1[^>]*>Ramalan Bintang\s*([^<]+)<\/h1>/i);
    const fullName = titleMatch ? titleMatch[1].trim() : `Bintang ${id}`;
    const sidebarMatch = html.match(/<div id="left-sidebar"[^>]*>([\s\S]*?)<div id="right-sidebar"/i);
    const content = sidebarMatch ? sidebarMatch[1] : html;

    const extractField = (fieldName) => {
      const regex = new RegExp(`<strong>${fieldName}:?<\\/strong><\\/div><div[^>]*>([\\s\\S]*?)<\\/div>`, 'i');
      const m = content.match(regex);
      return m ? m[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() : null;
    };

    const entry = {
      id,
      name_with_dates: fullName,
      name: fullName.split('(')[0].trim(),
      dates: fullName.includes('(') ? fullName.match(/\(([^)]+)\)/)[1].trim() : '',
      simbul: extractField('Simbul'),
      kesehatan: extractField('Kesehatan'),
      karakter: extractField('Karakter'),
      pekerjaan: extractField('Pekerjaan'),
      jodoh: extractField('Jodoh'),
      hari: extractField('Hari'),
      warna: extractField('Warna'),
      batu: extractField('Batu'),
      angka: extractField('Angka'),
      source_url: url
    };
    console.log(`- Bintang ${id}: ${entry.name} (${entry.simbul})`);
    results.push(entry);
    await new Promise(r => setTimeout(r, 600));
  }
  fs.writeFileSync('database/data/knowledge_bintang.json', JSON.stringify(results, null, 2));
  console.log('Saved 12 Bintang to database/data/knowledge_bintang.json\n');
}

// 2. SCRAPE GARIS HIDUP
async function scrapeGarisHidup() {
  console.log('=== 2. Scraping 9 Garis Hidup (Life Path 1-9) ===');
  const testCases = [
    { tanggal: 1, bulan: 1, tahun: 2006, num: 1 },
    { tanggal: 1, bulan: 1, tahun: 1998, num: 2 },
    { tanggal: 1, bulan: 1, tahun: 1999, num: 3 },
    { tanggal: 1, bulan: 1, tahun: 2000, num: 4 },
    { tanggal: 1, bulan: 1, tahun: 2001, num: 5 },
    { tanggal: 1, bulan: 1, tahun: 2002, num: 6 },
    { tanggal: 1, bulan: 1, tahun: 2003, num: 7 },
    { tanggal: 1, bulan: 1, tahun: 2004, num: 8 },
    { tanggal: 1, bulan: 1, tahun: 2005, num: 9 },
  ];

  const results = [];
  for (const tc of testCases) {
    const url = `https://www.kalenderbali.org/garishidup.php?tanggal=${tc.tanggal}&bulan=${tc.bulan}&tahun=${tc.tahun}`;
    const html = await fetchPage(url);
    const h1Match = html.match(/<h1[^>]*>([^<]+)<\/h1>/i);
    const title = h1Match ? h1Match[1].trim() : `Garis Hidup ${tc.num}`;

    const sidebarMatch = html.match(/<div id="left-sidebar"[^>]*>([\s\S]*?)<div id="right-sidebar"/i);
    let bodyText = '';
    if (sidebarMatch) {
      bodyText = sidebarMatch[1]
        .replace(/<p>/gi, '\n\n')
        .replace(/<\/p>/gi, '')
        .replace(/<[^>]+>/g, ' ')
        .replace(/[ \t]+/g, ' ')
        .replace(/\n\s*\n/g, '\n\n')
        .trim();
    }

    const entry = {
      number: tc.num,
      title: `Garis Hidup ${tc.num}`,
      description: bodyText,
      source_url: url
    };
    console.log(`- Garis Hidup ${tc.num}: OK (${bodyText.length} chars)`);
    results.push(entry);
    await new Promise(r => setTimeout(r, 600));
  }
  results.sort((a, b) => a.number - b.number);
  fs.writeFileSync('database/data/knowledge_garishidup.json', JSON.stringify(results, null, 2));
  console.log('Saved 9 Garis Hidup to database/data/knowledge_garishidup.json\n');
}

// 3. JODOH LONTAR TRI PRAMANA
async function saveJodoh() {
  console.log('=== 3. Saving Jodoh Lontar Tri Pramana ===');
  const rawJodoh = JSON.parse(fs.readFileSync('scripts/scrape/jodoh_results.json', 'utf8'));
  const uripPanca = {
    "Umanis": 5, "Paing": 9, "Pon": 7, "Wage": 4, "Kliwon": 8
  };
  const uripSad = {
    "Tungleh": 7, "Aryang": 6, "Urukung": 5, "Paniron": 8, "Was": 9, "Maulu": 3
  };
  const uripSapta = {
    "Redite": 5, "Soma": 4, "Anggara": 3, "Buda": 7, "Wraspati": 8, "Sukra": 6, "Saniscara": 9
  };

  const results = {
    meta: {
      source: "Lontar Tri Pramana (Kalender Bali)",
      description: "Perhitungan kecocokan perjodohan menurut Lontar Tri Pramana berdasarkan jumlah urip Panca Wara, Sad Wara, dan Sapta Wara kedua pasangan, dibagi 16.",
      formula: "Total = (Urip Pasangan 1 + Urip Pasangan 2); Sisa = Total % 16 (0 dihitung 16 atau sisa 0)."
    },
    urip_reference: {
      pancawara: uripPanca,
      sadwara: uripSad,
      saptawara: uripSapta
    },
    results: {}
  };

  for (let sisa = 0; sisa < 16; sisa++) {
    const text = rawJodoh[String(sisa)] || rawJodoh[sisa] || '';
    results.results[String(sisa)] = {
      sisa: sisa,
      alias: sisa === 0 ? "Sisa 0 / 16" : `Sisa ${sisa}`,
      ramalan: text,
      kategori: text.includes('amat baik') || text.includes('rukun') || text.includes('bahagia') ? 'ayu' : (text.includes('buruk') || text.includes('kesusahan') || text.includes('mandul') ? 'ala' : 'campur')
    };
  }

  fs.writeFileSync('database/data/knowledge_jodoh.json', JSON.stringify(results, null, 2));
  console.log('Saved 16 Jodoh results to database/data/knowledge_jodoh.json\n');
}

// 4. SCRAPE ARTI HARI (WEWARAN, WUKU, LINTANG, PRATITI)
async function scrapeArtihari() {
  console.log('=== 4. Scraping Artihari (Karakteristik Kelahiran) ===');
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

  if (fs.existsSync('database/data/knowledge_artihari.json')) {
    try {
      dictionary = JSON.parse(fs.readFileSync('database/data/knowledge_artihari.json', 'utf8'));
    } catch {}
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

  // Phase A: 35 days consecutive
  console.log('Crawling 35 consecutive days for Lintang, Pratiti & Wewaran...');
  let current = new Date(Date.UTC(2026, 0, 1));
  for (let i = 0; i < 35; i++) {
    const d = current.getUTCDate();
    const m = current.getUTCMonth() + 1;
    const y = current.getUTCFullYear();
    const url = `https://www.kalenderbali.org/artihari.php?tanggal=${d}&bulan=${m}&tahun=${y}`;
    try {
      const html = await fetchPage(url);
      processHtml(html);
    } catch (e) {
      console.warn(`Skipping ${y}-${m}-${d}: ${e.message}`);
    }
    current.setUTCDate(current.getUTCDate() + 1);
    await new Promise(r => setTimeout(r, 600));
  }
  console.log(`Phase A done. Lintang: ${Object.keys(dictionary.lintang).length}/35, Wuku: ${Object.keys(dictionary.wuku).length}/30`);

  // Phase B: 30 dates spaced by 7 days for 30 Wuku
  console.log('Crawling 30 weekly dates for all 30 Wuku...');
  let weekDate = new Date(Date.UTC(2026, 0, 1));
  for (let w = 0; w < 30; w++) {
    const d = weekDate.getUTCDate();
    const m = weekDate.getUTCMonth() + 1;
    const y = weekDate.getUTCFullYear();
    const url = `https://www.kalenderbali.org/artihari.php?tanggal=${d}&bulan=${m}&tahun=${y}`;
    try {
      const html = await fetchPage(url);
      processHtml(html);
    } catch (e) {
      console.warn(`Skipping week ${w} (${y}-${m}-${d}): ${e.message}`);
    }
    weekDate.setUTCDate(weekDate.getUTCDate() + 7);
    await new Promise(r => setTimeout(r, 600));
  }
  console.log(`Phase B done. Wuku: ${Object.keys(dictionary.wuku).length}/30`);

  fs.writeFileSync('database/data/knowledge_artihari.json', JSON.stringify(dictionary, null, 2));
  console.log('Saved to database/data/knowledge_artihari.json\n');
}

async function main() {
  await scrapeBintang();
  await scrapeGarisHidup();
  await saveJodoh();
  await scrapeArtihari();
  console.log('=== ALL KNOWLEDGE SCRAPING COMPLETED SUCCESSFULLY ===');
}

main().catch(console.error);

