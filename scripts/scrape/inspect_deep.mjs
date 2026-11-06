import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function run() {
  // 1. Inspect bintang id=1
  const b1 = await fetchPage('https://www.kalenderbali.org/bintang.php?id=1');
  const bContentMatch = b1.match(/<div id="left-sidebar"[\s\S]*?<\/div>\s*<\/div>/i);
  console.log('=== BINTANG ID 1 CONTENT ===');
  if (bContentMatch) {
    console.log(bContentMatch[0].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 1000));
  } else {
    console.log(b1.substring(b1.indexOf('Capricorn'), b1.indexOf('Capricorn') + 1500));
  }

  // 2. Inspect Jodoh text
  const j1 = await fetchPage('https://www.kalenderbali.org/jodoh.php?tanggal1=1&bulan1=1&tahun1=2000&tanggal2=2&bulan2=2&tahun2=2000');
  const jContentMatch = j1.match(/<div id="left-sidebar"[\s\S]*?<\/div>\s*<\/div>/i);
  console.log('\n=== JODOH CONTENT ===');
  if (jContentMatch) {
    console.log(jContentMatch[0].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim());
  } else {
    console.log(j1.substring(j1.indexOf('Tri Pramana'), j1.indexOf('Tri Pramana') + 1500));
  }

  // 3. Inspect Artihari full sections
  const a1 = await fetchPage('https://www.kalenderbali.org/artihari.php?tanggal=6&bulan=11&tahun=2026');
  const aMatches = [...a1.matchAll(/<strong>([^<]+)<\/strong><\/div><div[^>]*>([\s\S]*?)<\/div>/gi)];
  console.log('\n=== ARTIHARI SECTIONS ===');
  console.log('Found sections:', aMatches.length);
  for (const m of aMatches) {
    console.log(`- ${m[1]}: ${m[2].replace(/<[^>]+>/g, '').trim()}`);
  }
}

run().catch(console.error);

