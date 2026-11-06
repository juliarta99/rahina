# DATA_SOURCES.md — Rahina

Semua sumber data, status lisensi, dan cara pemakaiannya. Wajib diperbarui setiap kali menambah sumber.

## 1. Kebijakan

1. **Perhitungan kalender** (wuku, wewaran, sasih, purnama/tilem) berasal dari library open-source berlisensi jelas.
2. **Aturan dewasa** (nama, kondisi, efek) berasal dari **buku/lontar**, ditulis ulang dengan kata-kata sendiri, mencantumkan halaman, lalu **divalidasi** pakar.
3. **Situs pihak ketiga hanya dipakai dengan izin tertulis pemilik** (lihat §2.3). kalenderbali.org mencantumkan "All Rights Reserved"; datanya dipakai karena tim menyatakan telah memperoleh izin pemilik (2026-11-05). Bukti izin wajib disimpan tim dan dicantumkan di `AI_DISCLOSURE.md`.
4. Berita (misalnya detikBali) hanya dipakai sebagai **bukti kebutuhan** di proposal, bukan sebagai dataset.

## 2. Perangkat Lunak

| Nama                                                                                 | Fungsi                                                                              | Lisensi                                                                                         | Status                                                                                                                                                                      |
| ------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `balinese-date-js@1.0.1` (rewrite modern dari `balinese-date-js-lib` oleh Putu Andy) | Hitung pawukon, wewaran, sasih, rahinan                                             | **MIT** (paket npm); algoritma di-_port_ dari karya Ida Bagus Putu Peradnya Dinata (Apache-2.0) | Dipakai (devDependency, hanya di script Node)                                                                                                                               |
| `balinese-date-js-lib@0.4.3`                                                         | Rujukan algoritma asli. Modul Dewasa hanya ada di roadmap v0.5.0, **belum dirilis** | Apache-2.0                                                                                      | Pembanding (lihat §2.1)                                                                                                                                                     |
| Rust crate `balinese-calendar@0.3.0`                                                 | Kalender + modul `dewasa_ayu`                                                       | Apache-2.0 (docs.rs; lib.rs menyebut MIT OR Apache-2.0)                                         | **Tidak dipakai untuk aturan**: `dewasa_ayu` berupa mesin fuzzy Sugeno khusus pawiwahan, bobotnya disebut _provisional_, tanpa daftar dewasa bernama dan tanpa rujukan buku |
| `yama/kalender-api` (Packagist)                                                      | API JSON kalender                                                                   | —                                                                                               | Tidak dipakai (scraper pihak ketiga; Rahina mengambil langsung dengan izin, §2.3)                                                                                           |

Per 2026-11-05 belum ditemukan API/dataset berlisensi terbuka yang berisi **aturan dewasa bernama** (nama + kondisi + efek + sumber). Aturan tetap harus disusun dari buku/lontar (§3–§4).

### 2.1 Validasi silang engine (2026-11-05)

`balinese-date-js@1.0.1` dibandingkan dengan `balinese-date-js-lib@0.4.3` untuk **5.844 hari (2020–2035)**: wuku, 10 wewaran, ingkel, sasih, hari sasih (termasuk ngunaratri), tahun Saka, rahinan. Hasil: **0 perbedaan**. Ini membuktikan rewrite setia pada algoritma asli, tetapi **bukan** validasi independen terhadap kalender cetak (tetap perlu CR-004).

### 2.3 Data dewasa dari kalenderbali.org (dengan izin, 2026-11-05)

| Hal                | Isi                                                                                                                                                                                                       |
| ------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Izin               | Dinyatakan tim Rahina (pemilik proyek) telah diperoleh dari pemilik kalenderbali.org. Rincian bukti: `AI_DISCLOSURE.md` § Bukti izin data pihak ketiga (nama pemberi, kanal, cakupan **wajib diisi tim**) |
| Halaman            | `referensialaayu.php` (1 halaman: 220 dewasa, penjelasan, 1.270 kombinasi wewaran penyusun) dan `alaayu.php` per tanggal (210 hari, 2026-01-01 s.d. 2026-07-29: tingkat alahing + validasi)               |
| Cara ambil         | User-Agent `RahinaBot/0.1`, patuh `robots.txt` & `Crawl-delay: 10`, satu permintaan tiap 10 detik                                                                                                         |
| Penyimpanan mentah | `storage/app/scrape/kalenderbali-org/` (tidak di-commit)                                                                                                                                                  |
| Konversi           | `node scripts/dewasa/convert-kalenderbali-org.mjs` → `database/data/dewasa_rules.json` (sumber `kalenderbali-org`)                                                                                        |
| Status tinjauan    | `validated_at` kosong (belum ditinjau narasumber); tetap tampil karena `config('rahina.dewasa.display_unreviewed_sources')`, UI menandai "belum ditinjau"                                                 |
| Teks               | Penjelasan dewasa disalin apa adanya dari situs (dengan izin). Efek per kategori diturunkan dari teks itu oleh kamus kata kunci di converter (kalimat asli di `note`)                                     |

**Hasil pengambilan & konversi (2026-11-06):** 1 halaman referensi + 210 halaman per tanggal (2026-01-01 s.d. 2026-07-29), satu permintaan per 10 detik. Konverter menghasilkan 220 aturan, 1.270 kombinasi wewaran, tingkat alahing terisi 202/220. Validasi silang engine Rahina vs daftar dewasa situs pada 210 hari: **2.045 cocok, 21 hanya di engine, 5 hanya di situs** (presisi ±99,0%, recall ±99,8%). Dimuat ke DB: 430 efek kategori, 52.647 kecocokan tanggal 2020–2035. Catatan: sebagian dewasa memiliki efek baik **dan** tidak baik untuk domain yang sama (kalimat aslinya menyebut kegiatan berbeda dalam satu domain), sehingga tampil di kolom Mendukung sekaligus Penghalang (lihat CR-012).

**kalenderbali.id tidak di-crawl.** `robots.txt` situs itu memblokir crawler Anthropic (`ClaudeBot`, `anthropic-ai`, `Claude-Web`) dan menyatakan `ai-train=no`. Data dari situs ini hanya boleh masuk lewat ekspor yang diserahkan langsung oleh pemiliknya.

### 2.2 Pembanding ekspor kalenderbali.id (2026-11-05)

Tim mengunduh ekspor `.ics` 2025–2027 dari kalenderbali.id (disimpan lokal di `data_dan_contoh/`, **di-.gitignore, tidak masuk repo/DB**). Dipakai hanya untuk mencocokkan tanggal secara otomatis; yang dicatat hanya hasilnya:

| Kategori                 | Cocok | Tidak cocok | Penjelasan                                                                                                                                         |
| ------------------------ | ----- | ----------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Purnama (tanggal)        | 35    | 0           |                                                                                                                                                    |
| Tilem (tanggal)          | 36    | 1           | 2026-02-03: `.ics` mencantumkan dua "Tilem Kawolu" (03-02 dan 16-02), sehari setelah Purnama 02-02; engine menandai 16-02. Kesalahan ada di `.ics` |
| Nama sasih purnama/tilem | 66    | 5           | Semua hanya beda ejaan Jyestha/Destha                                                                                                              |
| Rahinan                  | 151   | 10          | Bukan salah tanggal: library tidak memuat _Tumpek Krulut_ dan _Manis Kuningan_ (CR-009)                                                            |

Kesimpulan: **tidak ditemukan tanggal yang salah** dari engine pada 2025–2027. Situs ini tidak di-scrape; isinya (deskripsi dewasa, pewatekan, banten, dll.) **tidak disalin** karena tidak berlisensi terbuka.

## 3. Buku (sumber aturan & konsep)

| Judul                 | Penulis                    | Tahun / Penerbit | Dipakai untuk                      |
| --------------------- | -------------------------- | ---------------- | ---------------------------------- |
| Pokok-Pokok Wariga    | I.B.S. Ardhana             | 2005, Paramita   | Konsep dasar wariga, sasih         |
| Kunci Wariga Dewasa   | Yayasan Satya Hindu Dharma | 1992             | Daftar dewasa                      |
| Wariga Dewasa Praktis | I.B.P. Bangli              | 2010, Paramita   | Dewasa praktis, kategori aktivitas |
| Aneka Tarikh          | I.W. Gina                  | 1997             | Rujukan pendukung                  |
| Ala Ayuning Dewasa    | Ketut Bangbang Gde Rawi    | —                | Dewasa & alahing                   |

Status pengadaan: [ ] perpustakaan Unud · [ ] Gedong Kirtya · [ ] beli (Paramita / toko online).

## 4. Lontar (sumber primer, akses terbuka)

| Sumber                                                       | Isi                                                                                                         | Lisensi                       |
| ------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------- | ----------------------------- |
| Wikimedia Commons — kategori _Palm-leaf manuscripts in Bali_ | Lontar _Wariga Palumbangan_, _Wariga Paméda Smara_, _Wariga Glagah Puwun_, naskah _Wariga_ (WM ID 009 0032) | Cek per berkas (sebagian CC0) |
| Balinese Digital Library (archive.org)                       | ±3.000 lontar dari Dinas Kebudayaan Bali                                                                    | Cek per item                  |
| WikiPustaka / Wikisource                                     | ±2.000 lontar ter-transliterasi                                                                             | CC-BY-SA (umumnya)            |

## 5. Jurnal (latar belakang & validasi kebutuhan)

- Ariningsih dkk. (2018). Persepsi masyarakat tani penerapan dewasa ayu… _AGRIMETA_ (Unmas).
- (2025). Transformasi Ilmu Wariga dalam Masyarakat Adat Buleleng. _Jurnal Penelitian Agama Hindu_, Jayapangus Press.
- Antara, Sukarsa, Buana (2022). Rancang Bangun Aplikasi Kalender Bali Menggunakan Framework Flutter. _JITTER_ Unud.
- Suwintana, I.K. (2014). Penentuan Hari Baik Perkawinan di Bali Berbasis Logika Fuzzy. _Lontar Komputer_.
- Satwika Putra & Darma Putra (2016). Aplikasi Peramalan Watak dan Perjodohan Berdasarkan Wariga Bali… _Jurnal Ilmiah Merpati_.
- (2025). Menentukan Hari Baik dalam Membangun Rumah: Studi Kasus Bale Daja. _Jurnal Widya Teknik_ UNHI.

## 6. Narasumber / Validator

| Peran                                | Nama  | Status                   |
| ------------------------------------ | ----- | ------------------------ |
| Ahli wariga / akademisi              | [isi] | [ ] dihubungi [ ] setuju |
| Pemangku / sulinggih                 | [isi] | [ ]                      |
| Petani / kelian subak (uji pengguna) | [isi] | [ ]                      |

Hasil validasi dicatat di `docs/CULTURAL_REVIEW.md` (aturan, tanggal validasi, catatan).

## 7. Dataset Validasi Internal

`tests/Fixtures/reference_days.json`: minimal 30 tanggal tersebar (termasuk Galungan, Kuningan, purnama, tilem, nampih sasih). Setiap tanggal dicocokkan manual dengan kalender cetak/kalenderbali.org. Yang dicatat hanya **hasil cocok/tidak**, bukan salinan isinya.
