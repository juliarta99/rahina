# [DRAF KASAR] Proposal Solusi & MVP AI Web

**AIWIC2026\_[NamaTim]\_Rahina**
Maks 5 halaman isi (di luar cover & lampiran). Bagian bertanda **[TODO]** wajib diisi data nyata.

---

## Cover

- **Nama Produk:** Rahina - Asisten Kalender Wariga Bali
- **Nama Tim:** [TODO]
- **Ketua / Anggota:** [TODO]
- **Instansi:** Universitas Udayana
- **Kontak Ketua:** [TODO]
- **Kategori:** Mahasiswa/Umum · Ruang lingkup: _Bali Smart Society_ (budaya) + _Education & Smart Information Systems_

---

## 1. Problem & Background

Kalender Bali (wariga) masih menjadi pedoman sehari-hari masyarakat Hindu Bali untuk menentukan hari baik (_dewasa ayu_) dan hari yang sebaiknya dihindari (_ala dewasa_). Kalender ini dipakai untuk bertani, berdagang, membangun, hingga melaksanakan yadnya. Media lokal bahkan menerbitkan ulasan _ala ayuning dewasa_ setiap hari, yang menunjukkan kebutuhan informasi ini tinggi.

Masalahnya, informasi harian tersebut:

1. **Berupa daftar 10-20 istilah teknis** (nama dewasa) yang tidak dipahami sebagian besar generasi muda.
2. **Sering tampak bertentangan.** Pada satu hari yang sama bisa muncul dewasa yang "baik untuk menanam padi" sekaligus dewasa yang "tidak baik bercocok tanam". Tanpa pemahaman wariga, pengguna tidak tahu harus mengikuti yang mana.
3. **Sulit dicari untuk kebutuhan spesifik.** Untuk menjawab "kapan hari baik menanam cabai bulan depan?", pengguna harus membuka kalender hari per hari dan menafsirkan sendiri.
4. Pengetahuan wariga terkonsentrasi pada pemangku dan tetua, sehingga ada **risiko putus pewarisan pengetahuan**.

**[TODO] Bukti pendukung:** survei singkat 30-50 responden (pemahaman istilah dewasa, cara mencari hari baik, kebutuhan), + kutipan wawancara 2-3 petani/pemangku.

## 2. Target User

| Pengguna                            | Kebutuhan / pain point                                                         |
| ----------------------------------- | ------------------------------------------------------------------------------ |
| Petani & kelian subak               | Menentukan hari mulai membajak, menabur benih, menanam; info tersebar & teknis |
| Pedagang / UMKM                     | Hari baik membuka usaha, membuat tempat berdagang                              |
| Keluarga & generasi muda Hindu Bali | Memahami rahinan, otonan, dan arti dewasa tanpa harus bertanya terus ke tetua  |
| Warga Bali di luar daerah           | Tidak punya kalender cetak dan tidak dekat dengan pemangku                     |

## 3. Proposed Solution

**Rahina** adalah aplikasi web (PWA) yang menghitung kalender wariga secara deterministik, lalu memakai AI untuk **menerjemahkan hasil perhitungan menjadi keputusan yang dipahami**, lengkap dengan alasan dan sumber.

**User journey utama**

1. Buka Rahina → halaman **Rahina Ini**: tanggal Bali + 3-4 poin ringkas sesuai persona (misalnya _Petani_).
2. Tap "lihat sumber" → daftar dewasa lengkap beserta rujukan buku.
3. Buka **Tanya Wariga** → ketik "hari baik menanam cabai bulan Desember" → muncul 3-5 tanggal terbaik, masing-masing dengan wuku/wewaran, dewasa pendukung & penghalang, dan tautan ke detail hari.
4. Simpan tanggal ke rencana / ekspor ke Google Calendar.
5. **Tanpa membuka aplikasi:** pengguna yang mendaftarkan WhatsApp menerima ringkasan setiap pukul 06.00 WITA (umum + catatan pribadi seperti hitung mundur otonan), dan cukup membalas pesan untuk bertanya hari baik.

## 4. AI Implementation

| Bagian             | Model / metode                                                                           | Input → Output                                              | Alasan AI diperlukan                                                        |
| ------------------ | ---------------------------------------------------------------------------------------- | ----------------------------------------------------------- | --------------------------------------------------------------------------- |
| Ringkasan harian   | `gemini-3.1-flash-lite`, structured output                                               | data dewasa hari itu + persona → 3-4 poin JSON              | Mengubah daftar istilah yang bertentangan menjadi bahasa awam per kebutuhan |
| Pemetaan aktivitas | Pencarian trigram/full-text Postgres + klasifikasi `gemini-3.1-flash-lite` (output enum) | "nanam singkong" → kategori `pertanian.umbi`                | Kategori wariga sangat spesifik; pengguna memakai bahasa sehari-hari        |
| Tanya Wariga       | `gemini-3.1-flash-lite` agent dengan **function calling** (5 tools)                      | pertanyaan bebas → daftar tanggal + alasan + sumber         | Pertanyaan alami perlu diubah menjadi kueri terstruktur multi-langkah       |
| Penjelas konflik   | `gemini-3.1-flash-lite` + retrieval catatan pengetahuan (full-text)                      | dewasa pendukung/penghalang + aturan prioritas → penjelasan | Butuh penalaran dan penjelasan berbasis sumber                              |

**Batasan & pengaman**

- LLM **tidak menghitung** tanggal. Semua tanggal berasal dari tools yang membaca database hasil perhitungan.
- **OutputGuard** membuang tanggal/aturan yang tidak dikembalikan tool (anti-halusinasi).
- Jika AI gagal → fallback template; kalender tetap berfungsi tanpa AI.
- Data yang dikirim ke AI: pertanyaan + data kalender publik, tanpa data pribadi.
- Konteks upacara besar selalu disertai anjuran konsultasi ke sulinggih/pemangku.

**[TODO] Hasil evaluasi:** 50 pertanyaan uji → akurasi tanggal [x]%, pemetaan kategori [x]%, latensi p50 [x]s.

## 5. MVP & Key Features

**Core (berfungsi saat submission)**

- [ ] Kalender wariga 2020-2035 (wuku, wewaran, sasih, purnama/tilem, rahinan)
- [ ] Rule engine dewasa ([x] aturan tervalidasi, 6 domain aktivitas)
- [ ] Rahina Ini (4 persona)
- [ ] Tanya Wariga (agent + referensi tanggal)
- [ ] Detail hari + daftar sumber

**Tambahan**

- [ ] Rahina Pagi via WhatsApp (Fonnte, nomor khusus Rahina; arsitektur siap migrasi ke Cloud API resmi)
- [ ] Perencana + ekspor `.ics`
- [ ] Penjelas konflik
- [ ] Profil & pengingat otonan

## 6. Technical Architecture

```
Pengguna → Frontend (Inertia + React) → Backend (Laravel 13)
   → Domain Wariga (deterministik) ↔ Supabase (PostgreSQL)
   → Lapisan AI: AgentRunner → Tools → OutputGuard → Gemini API
   → Hasil: ringkasan / tanggal + alasan + sumber
Pre-compute: Node (balinese-date-js) → JSONL → import ke DB → rule engine dewasa
```

_(Di PDF: ganti dengan diagram dari `docs/ARCHITECTURE.md`.)_

## 7. Innovation & Differentiation

| Aspek                                              | Kalender cetak / situs kalender Bali | Aplikasi kalender Bali (mobile) | **Rahina**                                                                       |
| -------------------------------------------------- | ------------------------------------ | ------------------------------- | -------------------------------------------------------------------------------- |
| Info dewasa harian                                 | Ya (daftar istilah)                  | Ya                              | Ya + **ringkasan bahasa awam per persona**                                       |
| Cari hari baik untuk aktivitas spesifik            | Manual, per kategori umum            | Terbatas                        | **Bahasa bebas → kategori → ranking tanggal**                                    |
| Menjelaskan dewasa yang bertentangan               | Tidak                                | Tidak                           | **Ya, dengan aturan prioritas & sumber**                                         |
| Sumber / rujukan per aturan                        | Tidak selalu                         | Tidak                           | **Selalu**                                                                       |
| Transparansi perhitungan ("kenapa hari ini baik?") | Tidak                                | Tidak                           | **Ya** (unsur pemicu tiap dewasa)                                                |
| Kanal                                              | Cetak / web                          | Aplikasi + push                 | **Web + WhatsApp** (ringkasan pagi & tanya lewat balasan, tanpa instal aplikasi) |

Inti inovasinya: **dari kalender yang dibaca menjadi asisten yang menjawab dan menjelaskan, tanpa mengorbankan ketepatan perhitungan.**

## 8. Impact & Development Potential

**Manfaat**

- Petani/pedagang mendapat jawaban cepat dan dapat dipertanggungjawabkan.
- Generasi muda belajar wariga lewat penjelasan kontekstual (fungsi edukasi & pelestarian budaya).
- Pemangku/penyuluh mendapat alat bantu menjelaskan ke masyarakat.

**Pengembangan**

- Validasi bersama akademisi & pemangku, cakupan aturan diperluas.
- Mode subak (jadwal tanam bersama), notifikasi WhatsApp/push, antarmuka bahasa Bali, API untuk desa adat.
- Model keberlanjutan: kemitraan dengan desa adat / dinas kebudayaan; fitur premium untuk penyelenggara upacara/event.

**Keterbatasan MVP**

- Cakupan aturan dewasa awal terbatas pada yang sudah divalidasi.
- Variasi tradisi antar-daerah belum dimodelkan.
- Kanal WhatsApp memakai gateway pihak ketiga (Fonnte) dengan risiko pembatasan nomor; untuk produksi direncanakan migrasi ke WhatsApp Cloud API resmi.
- Rekomendasi bersifat informatif, bukan pengganti petunjuk sulinggih.

## 9. Technology Disclosure

Ringkasan dari `docs/AI_DISCLOSURE.md`: Laravel 13, Inertia/React/TS, Tailwind, Supabase (PostgreSQL), balinese-date-js (Apache-2.0), Gemini API (`gemini-3.1-flash-lite`), hosting [TODO], AI coding assistant: Claude Code. Sumber pengetahuan: buku wariga (Ardhana 2005; Yayasan Satya Hindu Dharma 1992; Bangli 2010; Gina 1997), lontar digital (Wikimedia Commons, Balinese Digital Library), validasi narasumber [TODO].

## 10. Submission Links

- GitHub: [TODO]
- Deployment URL: [TODO]
- Demo account: via form panitia
- Commit / tag: `v1.0-submission` ([hash])

---

## Lampiran (di luar 5 halaman)

- A. Screenshot alur utama
- B. Hasil evaluasi agent
- C. Surat/keterangan validasi narasumber
- D. Daftar pustaka
