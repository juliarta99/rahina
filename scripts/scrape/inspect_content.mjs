import fs from 'node:fs';

async function fetchPage(url) {
  const res = await fetch(url);
  return await res.text();
}

function extractMain(html) {
  // Usually inside <div id="content"> or after </form> or inside <table ...>
  // Let's find after </form>
  const formIdx = html.indexOf('</form>');
  if (formIdx !== -1) {
    const afterForm = html.substring(formIdx + 7);
    const promoIdx = afterForm.indexOf('kbd-promo-apps');
    const cut = promoIdx !== -1 ? afterForm.substring(0, promoIdx) : afterForm;
    return cut.replace(/<script[\s\S]*?<\/script>/gi, '').replace(/<style[\s\S]*?<\/style>/gi, '').trim();
  }
  return html;
}

async function run() {
  console.log('=== 1. BINTANG ID 1 ===');
  const b1 = await fetchPage('https://www.kalenderbali.org/bintang.php?id=1');
  const b1Clean = extractMain(b1);
  console.log(b1Clean.substring(0, 1500));

  console.log('\n=== 2. GARIS HIDUP (1-1-2000) ===');
  const g1 = await fetchPage('https://www.kalenderbali.org/garishidup.php?tanggal=1&bulan=1&tahun=2000');
  const g1Clean = extractMain(g1);
  console.log(g1Clean.substring(0, 1500));

  console.log('\n=== 3. ARTI HARI (6-11-2026) ===');
  const a1 = await fetchPage('https://www.kalenderbali.org/artihari.php?tanggal=6&bulan=11&tahun=2026');
  const a1Clean = extractMain(a1);
  console.log(a1Clean.substring(0, 2000));

  console.log('\n=== 4. JODOH ===');
  const j1 = await fetchPage('https://www.kalenderbali.org/jodoh.php?tanggal1=1&bulan1=1&tahun1=2000&tanggal2=2&bulan2=2&tahun2=2000');
  const j1Clean = extractMain(j1);
  console.log(j1Clean.substring(0, 2000));
}

run().catch(console.error);

