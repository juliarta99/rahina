# AI & Technology Disclosure — Rahina

Mengikuti Lampiran C Panduan Peserta AI Web Innovation Challenge Bali DigiFest 2026. **Perbarui setiap ada perubahan.**

| Komponen | Isi |
|---|---|
| Nama Produk | Rahina — Asisten Kalender Wariga Bali |
| Framework / Frontend | Inertia.js, React, TypeScript, Tailwind CSS v4 |
| Backend / Runtime | Laravel 12 (PHP 8.3), Node.js (script perhitungan kalender) |
| Database / Storage | Supabase (PostgreSQL terkelola, region Singapore) + pg_trgm. Hanya fitur database yang dipakai |
| Hosting / Deployment | Database: Supabase (Free). Aplikasi Laravel: [isi hosting yang mendukung queue worker + scheduler] |
| AI Model / API / Method | `gemini-3.1-flash-lite` (Gemini API) — ringkasan, klasifikasi aktivitas, agent function calling, penjelasan konflik. Pencarian teks memakai Postgres (pg_trgm + full-text), tanpa model embedding |
| Peran AI dalam Produk | (1) Ringkasan harian per persona, (2) memetakan aktivitas bahasa bebas ke kategori wariga, (3) agent tanya-jawab hari baik dengan referensi tanggal, (4) penjelasan konflik dewasa. Perhitungan kalender **tidak** memakai AI. |
| Dataset / Data Source | Hasil perhitungan library `balinese-date-js`; aturan dewasa disusun dari buku & lontar (lihat `docs/DATA_SOURCES.md`); tidak menggunakan data pribadi |
| Open-source Libraries | balinese-date-js (Apache-2.0), Laravel (MIT), React (MIT), Inertia (MIT), Tailwind (MIT), Phosphor Icons (MIT), [lainnya] |
| Layanan pihak ketiga | Fonnte (gateway WhatsApp, paket berbayar) untuk ringkasan pagi & tanya-jawab. Bukan API resmi Meta; diakses lewat interface `MessagingGateway` agar dapat dimigrasi ke WhatsApp Cloud API. Nomor & tanggal lahir pengguna tidak dikirim ke model AI |
| Template / Boilerplate | Laravel Breeze (MIT) |
| AI Coding Assistant | Claude Code (Anthropic) — membantu scaffolding, penulisan test, dan dokumentasi. Seluruh kode dipahami dan diuji tim. [tambahkan lainnya bila ada] |
| Pre-existing Components | [isi: misalnya pipeline RAG dari proyek sebelumnya, atau "tidak ada"] |
| Third-party Assets / Credits | Font Spectral, Public Sans, Noto Sans Balinese (SIL OFL); ikon Phosphor (MIT) |

**Pernyataan:** Kami menyatakan bahwa informasi di atas benar dan lengkap. Kami bertanggung jawab atas penggunaan teknologi, source code, data, library, API, model AI, dan aset yang digunakan pada produk.
