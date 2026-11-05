# DATA_SOURCES.md — Rahina

Semua sumber data, status lisensi, dan cara pemakaiannya. Wajib diperbarui setiap kali menambah sumber.

## 1. Kebijakan

1. **Perhitungan kalender** (wuku, wewaran, sasih, purnama/tilem) berasal dari library open-source berlisensi jelas.
2. **Aturan dewasa** (nama, kondisi, efek) berasal dari **buku/lontar**, ditulis ulang dengan kata-kata sendiri, mencantumkan halaman, lalu **divalidasi** pakar.
3. **kalenderbali.org tidak di-scrape dan tidak disalin.** Situs ini mencantumkan pendaftaran hak cipta dan "All Rights Reserved". Penggunaan yang diizinkan: membandingkan hasil hitungan Rahina secara manual untuk validasi. Rencana: **meminta izin/menjadikan penyusunnya (I Wayan Nuarsa, Universitas Udayana) sebagai narasumber**.
4. Berita (misalnya detikBali) hanya dipakai sebagai **bukti kebutuhan** di proposal, bukan sebagai dataset.

## 2. Perangkat Lunak

| Nama | Fungsi | Lisensi | Status |
|---|---|---|---|
| `balinese-date-js` (rewrite modern dari `balinese-date-js-lib`) | Hitung pawukon, wewaran, sasih, rahinan | Apache-2.0 (algoritma asli oleh Ida Bagus Putu Peradnya Dinata) | Dipakai |
| `balinese-date-js-lib` | Rujukan algoritma + modul Dewasa | Apache-2.0 | Rujukan / pembanding |
| Rust crate `balinese-calendar` | Pembanding (punya modul `dewasa_ayu`, WASM) | **Cek lisensi** | Opsional |

## 3. Buku (sumber aturan & konsep)

| Judul | Penulis | Tahun / Penerbit | Dipakai untuk |
|---|---|---|---|
| Pokok-Pokok Wariga | I.B.S. Ardhana | 2005, Paramita | Konsep dasar wariga, sasih |
| Kunci Wariga Dewasa | Yayasan Satya Hindu Dharma | 1992 | Daftar dewasa |
| Wariga Dewasa Praktis | I.B.P. Bangli | 2010, Paramita | Dewasa praktis, kategori aktivitas |
| Aneka Tarikh | I.W. Gina | 1997 | Rujukan pendukung |
| Ala Ayuning Dewasa | Ketut Bangbang Gde Rawi | — | Dewasa & alahing |

Status pengadaan: [ ] perpustakaan Unud · [ ] Gedong Kirtya · [ ] beli (Paramita / toko online).

## 4. Lontar (sumber primer, akses terbuka)

| Sumber | Isi | Lisensi |
|---|---|---|
| Wikimedia Commons — kategori *Palm-leaf manuscripts in Bali* | Lontar *Wariga Palumbangan*, *Wariga Paméda Smara*, *Wariga Glagah Puwun*, naskah *Wariga* (WM ID 009 0032) | Cek per berkas (sebagian CC0) |
| Balinese Digital Library (archive.org) | ±3.000 lontar dari Dinas Kebudayaan Bali | Cek per item |
| WikiPustaka / Wikisource | ±2.000 lontar ter-transliterasi | CC-BY-SA (umumnya) |

## 5. Jurnal (latar belakang & validasi kebutuhan)

- Ariningsih dkk. (2018). Persepsi masyarakat tani penerapan dewasa ayu… *AGRIMETA* (Unmas).
- (2025). Transformasi Ilmu Wariga dalam Masyarakat Adat Buleleng. *Jurnal Penelitian Agama Hindu*, Jayapangus Press.
- Antara, Sukarsa, Buana (2022). Rancang Bangun Aplikasi Kalender Bali Menggunakan Framework Flutter. *JITTER* Unud.
- Suwintana, I.K. (2014). Penentuan Hari Baik Perkawinan di Bali Berbasis Logika Fuzzy. *Lontar Komputer*.
- Satwika Putra & Darma Putra (2016). Aplikasi Peramalan Watak dan Perjodohan Berdasarkan Wariga Bali… *Jurnal Ilmiah Merpati*.
- (2025). Menentukan Hari Baik dalam Membangun Rumah: Studi Kasus Bale Daja. *Jurnal Widya Teknik* UNHI.

## 6. Narasumber / Validator

| Peran | Nama | Status |
|---|---|---|
| Ahli wariga / akademisi | [isi] | [ ] dihubungi [ ] setuju |
| Pemangku / sulinggih | [isi] | [ ] |
| Petani / kelian subak (uji pengguna) | [isi] | [ ] |

Hasil validasi dicatat di `docs/CULTURAL_REVIEW.md` (aturan, tanggal validasi, catatan).

## 7. Dataset Validasi Internal

`tests/Fixtures/reference_days.json`: minimal 30 tanggal tersebar (termasuk Galungan, Kuningan, purnama, tilem, nampih sasih). Setiap tanggal dicocokkan manual dengan kalender cetak/kalenderbali.org. Yang dicatat hanya **hasil cocok/tidak**, bukan salinan isinya.
