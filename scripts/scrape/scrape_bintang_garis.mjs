import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function scrapeBintang() {
  console.log('--- Scraping 12 Bintang / Zodiak ---');
  const results = [];
  
  for (let id = 1; id <= 12; id++) {
    const url = `https://www.kalenderbali.org/bintang.php?id=${id}`;
    const html = await fetchPage(url);

    // Extract title & date range
    const titleMatch = html.match(/<h1[^>]*>Ramalan Bintang\s*([^<]+)<\/h1>/i);
    const fullName = titleMatch ? titleMatch[1].trim() : `Bintang ${id}`;
    
    // Extract fields
    const sidebarMatch = html.match(/<div id="left-sidebar"[^>]*>([\s\S]*?)<\/div>\s*<\/div>/i);
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

    console.log(`Scraped Bintang ${id}: ${entry.name}`);
    results.push(entry);
  }

  fs.writeFileSync('database/data/knowledge_bintang.json', JSON.stringify(results, null, 2));
  console.log('Saved to database/data/knowledge_bintang.json');
}

async function scrapeGarisHidup() {
  console.log('\n--- Scraping 9 Garis Hidup ---');
  const results = [];
  
  // Years 1998 to 2006 give life path numbers 2, 3, 4, 5, 6, 7, 8, 9, 1
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

  for (const tc of testCases) {
    const url = `https://www.kalenderbali.org/garishidup.php?tanggal=${tc.tanggal}&bulan=${tc.bulan}&tahun=${tc.tahun}`;
    const html = await fetchPage(url);
    
    const h1Match = html.match(/<h1[^>]*>([^<]+)<\/h1>/i);
    const title = h1Match ? h1Match[1].trim() : `Garis Hidup ${tc.num}`;

    const sidebarMatch = html.match(/<div id="left-sidebar"[^>]*>([\s\S]*?)<\/div>\s*<\/div>/i);
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
      title,
      description: bodyText,
      source_url: url
    };

    console.log(`Scraped Garis Hidup ${tc.num}: ${entry.title}`);
    results.push(entry);
  }

  // Sort by number 1-9
  results.sort((a, b) => a.number - b.number);

  fs.writeFileSync('database/data/knowledge_garishidup.json', JSON.stringify(results, null, 2));
  console.log('Saved to database/data/knowledge_garishidup.json');
}

async function main() {
  await scrapeBintang();
  await scrapeGarisHidup();
}

main().catch(console.error);

