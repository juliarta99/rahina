import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function run() {
  const g1 = await fetchPage('https://www.kalenderbali.org/garishidup.php?tanggal=1&bulan=1&tahun=2000');
  fs.writeFileSync('scripts/scrape/sample_garishidup_1.html', g1);
  console.log('Saved sample_garishidup_1.html');
}

run().catch(console.error);

