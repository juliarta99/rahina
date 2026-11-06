import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function run() {
  // 1. bintang links
  const bintangHtml = await fetchPage('https://www.kalenderbali.org/bintang.php');
  const bintangLinks = [...bintangHtml.matchAll(/href=[\"'](bintang\.php\?id=\d+)[\"'][^>]*>(.*?)<\/a>/gi)].map(m => ({ link: m[1], text: m[2].replace(/<[^>]+>/g, '').trim() }));
  console.log('Bintang links:', bintangLinks);

  // 2. garishidup inputs
  const garisHtml = await fetchPage('https://www.kalenderbali.org/garishidup.php');
  const garisInputs = [...garisHtml.matchAll(/<(?:input|select)[^>]*name=[\"']?([a-zA-Z0-9_-]+)[\"']?[^>]*>/gi)].map(m => m[1]);
  console.log('Garishidup form inputs:', [...new Set(garisInputs)]);

  // 3. artihari inputs
  const artihariHtml = await fetchPage('https://www.kalenderbali.org/artihari.php');
  const artihariInputs = [...artihariHtml.matchAll(/<(?:input|select)[^>]*name=[\"']?([a-zA-Z0-9_-]+)[\"']?[^>]*>/gi)].map(m => m[1]);
  console.log('Artihari form inputs:', [...new Set(artihariInputs)]);

  // 4. jodoh inputs
  const jodohHtml = await fetchPage('https://www.kalenderbali.org/jodoh.php');
  const jodohInputs = [...jodohHtml.matchAll(/<(?:input|select)[^>]*name=[\"']?([a-zA-Z0-9_-]+)[\"']?[^>]*>/gi)].map(m => m[1]);
  console.log('Jodoh form inputs:', [...new Set(jodohInputs)]);
}

run().catch(console.error);

