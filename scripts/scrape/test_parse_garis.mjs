import fs from 'node:fs';

const html = fs.readFileSync('scripts/scrape/sample_garishidup_1.html', 'utf8');
const m = html.match(/<div id="left-sidebar"[^>]*>([\s\S]*?)<div id="right-sidebar"/i);
console.log('Match found:', !!m);
if (m) {
  const text = m[1]
    .replace(/<p>/gi, '\n\n')
    .replace(/<\/p>/gi, '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/[ \t]+/g, ' ')
    .replace(/\n\s*\n/g, '\n\n')
    .trim();
  console.log('Length:', text.length);
  console.log('Sample:', text.substring(0, 300));
}

