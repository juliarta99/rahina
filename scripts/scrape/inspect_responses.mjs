import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function run() {
  // 1. Check bintang id=1
  console.log('--- BINTANG ID 1 ---');
  const b1 = await fetchPage('https://www.kalenderbali.org/bintang.php?id=1');
  // find main content
  console.log(b1.substring(b1.indexOf('<h1') !== -1 ? b1.indexOf('<h1') : 5000, 10000));

  // 2. Check garishidup
  console.log('\n--- GARIS HIDUP (1-1-2000) ---');
  const g1 = await fetchPage('https://www.kalenderbali.org/garishidup.php?tanggal=1&bulan=1&tahun=2000');
  console.log(g1.substring(g1.indexOf('Garis Hidup') !== -1 ? g1.indexOf('Garis Hidup') : 5000, 12000));

  // 3. Check artihari
  console.log('\n--- ARTI HARI (6-11-2026) ---');
  const a1 = await fetchPage('https://www.kalenderbali.org/artihari.php?tanggal=6&bulan=11&tahun=2026');
  console.log(a1.substring(a1.indexOf('Kelahiran') !== -1 ? a1.indexOf('Kelahiran') : 5000, 15000));

  // 4. Check jodoh
  console.log('\n--- JODOH ---');
  const j1 = await fetchPage('https://www.kalenderbali.org/jodoh.php?tanggal1=1&bulan1=1&tahun2000&tanggal2=2&bulan2=2&tahun2=2000');
  console.log(j1.substring(j1.indexOf('Tri Pramana') !== -1 || j1.indexOf('Hasil') !== -1 ? 5000 : 7000, 12000));
}

run().catch(console.error);

