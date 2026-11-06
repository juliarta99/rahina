import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

async function run() {
  const pages = ['bintang.php', 'garishidup.php', 'artihari.php', 'jodoh.php'];
  for (const page of pages) {
    console.log(`\n=================== ${page} ===================`);
    const html = await fetchPage(`https://www.kalenderbali.org/${page}`);
    console.log(`Length: ${html.length}`);
    
    // Check forms
    const formMatches = html.match(/<form[\s\S]*?<\/form>/gi) || [];
    console.log(`Forms found: ${formMatches.length}`);
    for (let i = 0; i < formMatches.length; i++) {
      console.log(`--- Form ${i} ---`);
      console.log(formMatches[i].substring(0, 500));
    }

    // Check tables or select options
    const selects = html.match(/<select[\s\S]*?<\/select>/gi) || [];
    console.log(`Select elements found: ${selects.length}`);
    for (let i = 0; i < Math.min(3, selects.length); i++) {
      console.log(`--- Select ${i} ---`);
      console.log(selects[i].substring(0, 200));
    }
  }
}

run().catch(console.error);

