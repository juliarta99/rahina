import fs from 'node:fs';

const html = fs.readFileSync('scripts/scrape/sample_bintang_9.html', 'utf8');
const titleMatch = html.match(/<h1[^>]*>Ramalan Bintang\s*([^<]+)<\/h1>/i);
console.log('Title match:', titleMatch ? titleMatch[1] : 'NOT FOUND');

const sidebarMatch = html.match(/<div id="left-sidebar"[^>]*>([\s\S]*?)<div id="right-sidebar"/i);
console.log('Sidebar match found:', !!sidebarMatch);
if (sidebarMatch) {
  const content = sidebarMatch[1];
  const extractField = (fieldName) => {
    const regex = new RegExp(`<strong>${fieldName}:?<\\/strong><\\/div><div[^>]*>([\\s\\S]*?)<\\/div>`, 'i');
    const m = content.match(regex);
    return m ? m[1].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() : null;
  };
  console.log('Simbul:', extractField('Simbul'));
  console.log('Karakter:', extractField('Karakter')?.substring(0, 100));
}

