import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function run() {
  const jodohResults = {};
  
  // Let's test a sequence of dates to get all remainders 1 to 16
  // Year 2000, month 1, day 1 to 31
  for (let d1 = 1; d1 <= 20 && Object.keys(jodohResults).length < 16; d1++) {
    for (let d2 = 1; d2 <= 20 && Object.keys(jodohResults).length < 16; d2++) {
      const url = `https://www.kalenderbali.org/jodoh.php?tanggal1=${d1}&bulan1=1&tahun1=2000&tanggal2=${d2}&bulan2=2&tahun2=2000`;
      const html = await fetchPage(url);
      const m = html.match(/dibagi 16 sisa (\d+)[\s\S]*?Lontar Tri Pramana,?\s*([\s\S]*?)<\/div>/i);
      if (m) {
        const sisa = parseInt(m[1], 10);
        const ramalan = m[2].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        if (!jodohResults[sisa]) {
          jodohResults[sisa] = ramalan;
          console.log(`Found sisa ${sisa}: ${ramalan}`);
        }
      }
    }
  }

  console.log('Total jodoh results found:', Object.keys(jodohResults).length);
  fs.writeFileSync('scripts/scrape/jodoh_results.json', JSON.stringify(jodohResults, null, 2));
}

run().catch(console.error);

