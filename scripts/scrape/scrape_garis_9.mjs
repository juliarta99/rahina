import fs from 'node:fs';

async function fetchPage(url, retries = 3) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0' } });
      if (res.ok) return await res.text();
    } catch (e) {
      if (i === retries - 1) throw e;
    }
    await new Promise(r => setTimeout(r, 2000));
  }
  throw new Error(`Failed to fetch ${url}`);
}

async function run() {
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
    console.log(`Fetching Garis Hidup ${tc.num}...`);
    const html = await fetchPage(url);
    const m = html.match(/<div id="left-sidebar"[^>]*>([\s\S]*?)<div id="right-sidebar"/i);
    let desc = '';
    if (m) {
      desc = m[1]
        .replace(/<p>/gi, '\n\n')
        .replace(/<\/p>/gi, '')
        .replace(/<[^>]+>/g, ' ')
        .replace(/[ \t]+/g, ' ')
        .replace(/\n\s*\n/g, '\n\n')
        .trim();
    }
    console.log(`Garis Hidup ${tc.num}: length ${desc.length}`);
    results.push({
      number: tc.num,
      title: `Garis Hidup ${tc.num}`,
      description: desc,
      source_url: url
    });
    await new Promise(r => setTimeout(r, 1500));
  }

  results.sort((a, b) => a.number - b.number);
  fs.writeFileSync('database/data/knowledge_garishidup.json', JSON.stringify(results, null, 2));
  console.log('Saved to database/data/knowledge_garishidup.json');
}

run().catch(console.error);

