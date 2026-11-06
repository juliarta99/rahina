import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function run() {
  const b1 = await fetchPage('https://www.kalenderbali.org/bintang.php?id=1');
  fs.writeFileSync('scripts/scrape/sample_bintang_1.html', b1);
  console.log('Saved sample_bintang_1.html');
}

run().catch(console.error);

