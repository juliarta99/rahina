# AI & Technology Disclosure — Rahina

Mengikuti Lampiran C Panduan Peserta AI Web Innovation Challenge Bali DigiFest 2026. **Perbarui setiap ada perubahan.**

| Komponen                     | Isi                                                                                                                                                                                                                                                                                                                                        |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Nama Produk                  | Rahina — Asisten Kalender Wariga Bali                                                                                                                                                                                                                                                                                                      |
| Framework / Frontend         | Inertia.js, React, TypeScript, Tailwind CSS v4                                                                                                                                                                                                                                                                                             |
| Backend / Runtime            | Laravel 13 (PHP 8.3+), Node.js (script perhitungan kalender)                                                                                                                                                                                                                                                                               |
| Database / Storage           | MySQL 8 (sebelumnya Supabase/PostgreSQL, ditinggalkan 2026-11-06 karena latensi). Pencarian alias aktivitas memakai trigram di PHP                                                                                                                                                                                                         |
| Hosting / Deployment         | Database: MySQL 8. Aplikasi Laravel: [isi hosting yang mendukung queue worker + scheduler]                                                                                                                                                                                                                                                 |
| AI Model / API / Method      | `gemini-3.1-flash-lite` (Gemini API) — ringkasan, klasifikasi aktivitas, agent function calling, penjelasan konflik. Pencarian teks memakai Postgres (pg_trgm + full-text), tanpa model embedding                                                                                                                                          |
| Peran AI dalam Produk        | (1) Ringkasan harian per persona, (2) memetakan aktivitas bahasa bebas ke kategori wariga, (3) agent tanya-jawab hari baik dengan referensi tanggal, (4) penjelasan konflik dewasa. Perhitungan kalender **tidak** memakai AI.                                                                                                             |
| Dataset / Data Source        | Hasil perhitungan library `balinese-date-js`; 220 aturan ala-ayuning dewasa dari **kalenderbali.org, dipakai dengan izin pemilik** (lihat "Bukti izin data pihak ketiga" di bawah dan `docs/DATA_SOURCES.md` §2.3), belum ditinjau narasumber; tidak menggunakan data pribadi                                                              |
| Open-source Libraries        | balinese-date-js (MIT; algoritma dari balinese-date-js-lib, Apache-2.0), Laravel (MIT), Laravel Fortify (MIT), Laravel Wayfinder (MIT), React (MIT), Inertia (MIT), Tailwind (MIT), Radix UI (MIT), @phosphor-icons/react (MIT), Pest (MIT), Larastan (MIT)                                                                                |
| Layanan pihak ketiga         | Fonnte (gateway WhatsApp; device saat ini paket Free per 2026-11-06, disarankan berbayar sebelum produksi, lihat WHATSAPP.md §1) untuk ringkasan pagi & tanya-jawab. Bukan API resmi Meta; diakses lewat interface `MessagingGateway` agar dapat dimigrasi ke WhatsApp Cloud API. Nomor & tanggal lahir pengguna tidak dikirim ke model AI |
| Template / Boilerplate       | Laravel React Starter Kit (MIT): Fortify, Wayfinder, komponen shadcn/ui (MIT), Radix UI (MIT)                                                                                                                                                                                                                                              |
| AI Coding Assistant          | Claude Code (Anthropic) — membantu scaffolding, penulisan test, dan dokumentasi. Seluruh kode dipahami dan diuji tim. [tambahkan lainnya bila ada]                                                                                                                                                                                         |
| Pre-existing Components      | [isi: misalnya pipeline RAG dari proyek sebelumnya, atau "tidak ada"]                                                                                                                                                                                                                                                                      |
| Third-party Assets / Credits | Font Spectral, Public Sans, Noto Sans Balinese (SIL OFL); ikon Phosphor (MIT)                                                                                                                                                                                                                                                              |

## Bukti izin data pihak ketiga

### kalenderbali.org

Isian bertanda **[WAJIB DIISI TIM]** hanya boleh diisi tim dari bukti asli. Isian itu tidak boleh dikarang atau diisi asisten AI.

| Hal                                                   | Isi                                                                                                                                                                            |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Situs                                                 | https://www.kalenderbali.org (halaman footer: "All Rights Reserved")                                                                                                           |
| Status izin                                           | Tim Rahina menyatakan izin tertulis dari pemilik **telah diperoleh pada 2026-11-05** (dicatat di `CLAUDE.md` §2.4 dan `DATA_SOURCES.md` §2.3)                                  |
| Nama pemberi izin & perannya                          | **[WAJIB DIISI TIM]**                                                                                                                                                          |
| Kanal izin (email / pesan / surat)                    | **[WAJIB DIISI TIM]**                                                                                                                                                          |
| Tanggal & jam pesan izin                              | **[WAJIB DIISI TIM]**, tanggal yang tercatat: 2026-11-05                                                                                                                       |
| Cakupan izin yang tertulis                            | **[WAJIB DIISI TIM]**, misalnya: mengambil & menampilkan daftar ala-ayuning dewasa, penjelasan, kombinasi wewaran, tingkat alahing, untuk Rahina pada lomba Bali DigiFest 2026 |
| Syarat dari pemilik (atribusi, batas waktu, larangan) | **[WAJIB DIISI TIM]**                                                                                                                                                          |
| Lokasi arsip bukti                                    | **[WAJIB DIISI TIM]**, mis. tangkapan layar/PDF di folder tim (bukan di repo publik bila memuat data pribadi pemberi izin)                                                     |

**Yang diambil & cara pengambilannya (fakta teknis, 2026-11-06):**

| Hal                | Isi                                                                                                                                                                               |
| ------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Halaman            | `referensialaayu.php` (1 halaman) dan `alaayu.php?bulan=M&tanggal=D&tahun=2026` untuk 210 tanggal (2026-01-01 s.d. 2026-07-29)                                                    |
| Kepatuhan          | `robots.txt` situs: `Allow: /`, `Crawl-delay: 10`. Diambil berurutan, satu permintaan tiap ≥ 10 detik, User-Agent `RahinaBot/0.1`, tanpa login, tanpa melewati pembatasan apa pun |
| Penyimpanan mentah | `storage/app/scrape/kalenderbali-org/` (tidak di-commit)                                                                                                                          |
| Hasil di aplikasi  | `database/data/dewasa_rules.json` (sumber `kalenderbali-org`): 220 dewasa, ringkasan penjelasan, kondisi wewaran, tingkat alahing, efek per kategori                              |
| Atribusi di UI     | Setiap dewasa menampilkan sumbernya dan label "Belum ditinjau narasumber Rahina"                                                                                                  |
| Pencabutan         | Bila pemilik mencabut izin: hapus/kosongkan aturan bersumber `kalenderbali-org`, jalankan `php artisan wariga:load-rules && php artisan wariga:compute-dewasa`                    |

### kalenderbali.id

Tidak di-crawl. `robots.txt` memblokir crawler Anthropic dan menyatakan `ai-train=no`. Ekspor `.ics` yang diunduh tim hanya dipakai lokal untuk mencocokkan tanggal dan tidak masuk repo maupun DB (`DATA_SOURCES.md` §2.2).

**Pernyataan:** Kami menyatakan bahwa informasi di atas benar dan lengkap. Kami bertanggung jawab atas penggunaan teknologi, source code, data, library, API, model AI, dan aset yang digunakan pada produk.
