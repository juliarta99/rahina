import fs from 'node:fs';

const parseBintangHtml = (html, id) => {
  const titleMatch = html.match(/<h1[^>]*>Ramalan Bintang\s*([^<]+)<\/h1>/i);
  const fullName = titleMatch ? titleMatch[1].trim() : `Bintang ${id}`;
  const sidebarMatch = html.match(/<div id="left-sidebar"[^>]*>([\s\S]*?)<div id="right-sidebar"/i);
  const content = sidebarMatch ? sidebarMatch[1] : html;

  const extractField = (fieldName) => {
    const regex = new RegExp(`<strong>${fieldName}:?<\\/strong><\\/div><div[^>]*>([\\s\\S]*?)<\\/div>`, 'i');
    const m = content.match(regex);
    return m ? m[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() : null;
  };

  return {
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
    source_url: `https://www.kalenderbali.org/bintang.php?id=${id}`
  };
};

async function run() {
  const existing = JSON.parse(fs.readFileSync('database/data/knowledge_bintang.json', 'utf8'));
  
  // Re-parse 9 from sample_bintang_9.html
  if (fs.existsSync('scripts/scrape/sample_bintang_9.html')) {
    const html9 = fs.readFileSync('scripts/scrape/sample_bintang_9.html', 'utf8');
    const parsed9 = parseBintangHtml(html9, 9);
    const idx9 = existing.findIndex(b => b.id === 9);
    if (idx9 !== -1) existing[idx9] = parsed9;
    else existing.push(parsed9);
    console.log('Re-parsed Virgo (9):', parsed9.name, parsed9.simbul);
  }

  // Re-parse 11 from sample_bintang_11.html
  if (fs.existsSync('scripts/scrape/sample_bintang_11.html')) {
    const html11 = fs.readFileSync('scripts/scrape/sample_bintang_11.html', 'utf8');
    const parsed11 = parseBintangHtml(html11, 11);
    const idx11 = existing.findIndex(b => b.id === 11);
    if (idx11 !== -1) existing[idx11] = parsed11;
    else existing.push(parsed11);
    console.log('Re-parsed Scorpio (11):', parsed11.name, parsed11.simbul);
  }

  existing.sort((a, b) => a.id - b.id);
  fs.writeFileSync('database/data/knowledge_bintang.json', JSON.stringify(existing, null, 2));
  console.log('Successfully updated database/data/knowledge_bintang.json with all 12 items!');
}

run().catch(console.error);

