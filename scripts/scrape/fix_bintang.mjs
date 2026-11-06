import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function scrapeAllBintang() {
  console.log('--- Scraping 12 Bintang / Zodiak ---');
  const results = [];
  
  for (let id = 1; id <= 12; id++) {
    const url = `https://www.kalenderbali.org/bintang.php?id=${id}`;
    const html = await fetchPage(url);

    // Extract title & date range
    const titleMatch = html.match(/<h1[^>]*>Ramalan Bintang\s*([^<]+)<\/h1>/i);
    const fullName = titleMatch ? titleMatch[1].trim() : `Bintang ${id}`;
    
    // Extract fields between left-sidebar and right-sidebar
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

    console.log(`Scraped Bintang ${id}: ${entry.name} (${entry.simbul})`);
    results.push(entry);
  }

  fs.writeFileSync('database/data/knowledge_bintang.json', JSON.stringify(results, null, 2));
  console.log('Saved 12 entries to database/data/knowledge_bintang.json');
}

scrapeAllBintang().catch(console.error);

