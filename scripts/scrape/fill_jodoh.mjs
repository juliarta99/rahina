import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function run() {
  const jodohMap = {};
  if (fs.existsSync('scripts/scrape/jodoh_results.json')) {
    const existing = JSON.parse(fs.readFileSync('scripts/scrape/jodoh_results.json', 'utf8'));
    Object.assign(jodohMap, existing);
  }

  // We need remainders 1 to 16 (0 is 16)
  // Let's loop days and months until all 16 are filled
  for (let m1 = 1; m1 <= 12 && Object.keys(jodohMap).length < 16; m1++) {
    for (let d1 = 1; d1 <= 28 && Object.keys(jodohMap).length < 16; d1++) {
      for (let d2 = 1; d2 <= 28 && Object.keys(jodohMap).length < 16; d2++) {
        const url = `https://www.kalenderbali.org/jodoh.php?tanggal1=${d1}&bulan1=${m1}&tahun1=2000&tanggal2=${d2}&bulan2=2&tahun2=2000`;
        const html = await fetchPage(url);
        const m = html.match(/dibagi 16 sisa (\d+)[\s\S]*?Lontar Tri Pramana,?\s*([\s\S]*?)<\/div>/i);
        if (m) {
          const sisa = parseInt(m[1], 10);
          const ramalan = m[2].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
          if (!jodohMap[sisa]) {
            jodohMap[sisa] = ramalan;
            console.log(`Found sisa ${sisa}: ${ramalan}`);
          }
        }
      }
    }
  }

  console.log('Final Jodoh results count:', Object.keys(jodohMap).length);
  fs.writeFileSync('scripts/scrape/jodoh_results.json', JSON.stringify(jodohMap, null, 2));
}

run().catch(console.error);

