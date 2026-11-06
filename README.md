# Rahina - Asisten Kalender Wariga Bali

Rahina mengubah informasi _ala ayuning dewasa_ kalender Bali yang panjang dan penuh istilah menjadi ringkasan harian dan jawaban yang mudah dipahami, lengkap dengan tanggal, alasan, dan sumbernya.

## Problem & Target User

Masyarakat Bali memakai kalender wariga untuk menentukan hari baik bertani, berdagang, membangun, dan beryadnya. Informasi hariannya berisi banyak istilah dewasa yang sering saling bertentangan, sehingga sulit dipahami terutama oleh generasi muda dan warga Bali di luar daerah.
**Target:** petani & kelian subak, pedagang/UMKM, keluarga Hindu Bali, generasi muda, pemangku.

## Key Features

- **Rahina Ini**: ringkasan harian per persona.
- **Tanya Wariga**: tanya hari baik dalam bahasa sehari-hari, jawaban berisi referensi tanggal dan sumber.
- **Pemetaan aktivitas**: kalimat bebas dipetakan ke kategori wariga.
- **Penjelas konflik** dewasa.
- Kalender bulanan, detail hari, rahinan, purnama/tilem, perencana + ekspor `.ics`.

## Tech Stack

Laravel 13 · Inertia + React + TypeScript · Tailwind v4 · Supabase (PostgreSQL) · Node (balinese-date-js) · Gemini API

## AI Implementation

Lihat [`docs/AI_IMPLEMENTATION.md`](docs/AI_IMPLEMENTATION.md). Ringkasnya: perhitungan kalender deterministik; Gemini (`gemini-3.1-flash-lite`) dipakai untuk ringkasan, pemetaan aktivitas, dan agent function calling. Setiap tanggal di jawaban AI divalidasi terhadap hasil tool (`app/AI/OutputGuard.php`).

## Architecture / Data Flow

Lihat [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Installation / Run Instructions

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# isi DB_* dengan Session pooler Supabase (lihat docs/SUPABASE.md)
php artisan migrate --seed
node scripts/calendar/compute.mjs 2020 2035
php artisan wariga:import storage/app/calendar
php artisan wariga:load-rules
php artisan wariga:compute-dewasa
composer run dev
```

## Deployment URL

[isi]

## Demo Account

Dikirim melalui form panitia (tidak dicantumkan di repo).

## Known Limitations

- Aturan dewasa yang tampil hanya yang sudah divalidasi; cakupan awal [x] aturan.
- Rentang kalender 2020-2035.
- Perbedaan tradisi antar-daerah belum dimodelkan.
- WhatsApp memakai gateway Fonnte (bukan API resmi Meta); nomor pengirim berisiko dibatasi. Arsitektur `MessagingGateway` siap dimigrasi ke Cloud API.
- Rekomendasi bersifat informatif; untuk upacara besar tetap konsultasikan dengan sulinggih/pemangku.

## Third-party Resources / Credits

Lihat [`docs/AI_DISCLOSURE.md`](docs/AI_DISCLOSURE.md) dan [`docs/DATA_SOURCES.md`](docs/DATA_SOURCES.md).

## Team Members

| Nama  | Peran                  |
| ----- | ---------------------- |
| [isi] | Ketua, backend & AI    |
| [isi] | Frontend & UI/UX       |
| [isi] | Data wariga & validasi |
