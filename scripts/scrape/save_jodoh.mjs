import fs from 'node:fs';

const rawJodoh = JSON.parse(fs.readFileSync('scripts/scrape/jodoh_results.json', 'utf8'));

const uripPancawara = {
  "Umanis": 5,
  "Paing": 9,
  "Pon": 7,
  "Wage": 4,
  "Kliwon": 8
};

const uripSadwara = {
  "Tungleh": 7,
  "Aryang": 6,
  "Urukung": 5,
  "Paniron": 8,
  "Was": 9,
  "Maulu": 3
};

const uripSaptawara = {
  "Redite": 5,
  "Soma": 4,
  "Anggara": 3,
  "Buda": 7,
  "Wraspati": 8,
  "Sukra": 6,
  "Saniscara": 9
};

const results = {
  meta: {
    source: "Lontar Tri Pramana",
    url: "https://www.kalenderbali.org/jodoh.php",
    description: "Perhitungan ramalan perjodohan menurut Lontar Tri Pramana. Berdasarkan penjumlahan urip Panca Wara, Sad Wara, dan Sapta Wara kedua calon pasangan, kemudian dibagi 16 (sisa 1 sampai 16, di mana 0 sama dengan sisa 16).",
    cultural_notice: "Ramalan perjodohan dari Lontar Tri Pramana ini adalah warisan budaya tradisional Bali sebagai bahan pertimbangan dan kehati-hatian (bukan takdir mutlak)."
  },
  urip_reference: {
    pancawara: uripPancawara,
    sadwara: uripSadwara,
    saptawara: uripSaptawara
  },
  rules: []
};

for (let sisa = 0; sisa < 16; sisa++) {
  const text = rawJodoh[String(sisa)] || rawJodoh[sisa] || '';
  let verdict = 'campur';
  if (text.includes('amat baik') || text.includes('rukun') || text.includes('bahagia') || text.includes('senang') || text.includes('berwibawa')) {
    verdict = 'ayu';
  } else if (text.includes('buruk') || text.includes('kesusahan') || text.includes('menderita') || text.includes('mandul') || text.includes('kurang') || text.includes('kecewa')) {
    verdict = 'ala';
  }

  results.rules.push({
    sisa: sisa,
    label: sisa === 0 ? "Sisa 0 (atau 16)" : `Sisa ${sisa}`,
    ramalan: text,
    verdict: verdict
  });
}

// Sort by sisa 1..15 then 0
results.rules.sort((a, b) => (a.sisa === 0 ? 16 : a.sisa) - (b.sisa === 0 ? 16 : b.sisa));

fs.writeFileSync('database/data/knowledge_jodoh.json', JSON.stringify(results, null, 2));
console.log('Saved 16 Jodoh rules to database/data/knowledge_jodoh.json');

