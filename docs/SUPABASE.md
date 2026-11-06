# SUPABASE.md — Database Rahina di Supabase

> **Arsip (2026-11-06).** Rahina pindah ke **MySQL 8** karena latensi: dari laptop pengembang di Bali, satu query ke pooler Supabase Tokyo ±360–400 ms (satu pertanyaan Tanya Wariga ±4,9 s hanya untuk database), sedangkan MySQL lokal ±2 ms per query (±0,1 s per pertanyaan). Hitung ulang dewasa 2020–2035: ±2 menit → ±18 detik. Kode tetap portabel (`ActivityMatcher` memakai `pg_trgm` bila driver pgsql). Dokumen ini disimpan bila suatu saat kembali ke Supabase.

Rahina memakai **Supabase sebagai PostgreSQL terkelola**. Laravel tetap terhubung langsung lewat driver `pgsql` biasa. Fitur Supabase lain (Auth, Storage, Edge Functions, Realtime) **tidak dipakai** agar arsitektur tetap sederhana dan mudah dipindah ke Postgres mana pun.

| Dipakai                                                            | Tidak dipakai                                                                                  |
| ------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| Postgres terkelola, ekstensi `pg_trgm`, dashboard SQL/Table editor | Supabase Auth (pakai Laravel Fortify), Storage, Edge Functions, Realtime, client `supabase-js` |

---

## 1. Setup Proyek

1. Proyek aktif: aplikasi terhubung ke pooler **`aws-0-ap-northeast-1` (Tokyo)** (teramati dari koneksi Laravel, 2026-11-06; catatan sebelumnya menyebut eu-north-1). Dari laptop pengembang di Bali, satu query ±360–400 ms. Singapore (ap-southeast-1) lebih dekat dari Bali; pertimbangkan pindah sebelum deploy jika latensi query terasa (setiap query ±250 ms RTT).
2. Simpan password database di password manager, jangan di repo.
3. Ekstensi diaktifkan lewat **migrasi Laravel** (bukan klik dashboard), supaya reproducible:
    ```php
    DB::statement('create extension if not exists pg_trgm with schema extensions');
    ```
    Karena ekstensi berada di schema `extensions`, set `search_path` koneksi ke `public,extensions` (lihat §3).
4. **Matikan Data API** (Project Settings → Data API) atau aktifkan RLS di semua tabel (lihat §4).

---

## 2. Memilih Connection String

Koneksi langsung (`db.<ref>.supabase.co`) di paket Free **hanya IPv6**. Banyak jaringan rumah/kampus dan hosting murah belum mendukung IPv6.

| Kebutuhan                        | Pakai                            | Host:Port                                                                                       |
| -------------------------------- | -------------------------------- | ----------------------------------------------------------------------------------------------- |
| **Aplikasi Laravel (default)**   | **Shared pooler – Session mode** | `aws-0-ap-southeast-1.pooler.supabase.com:5432`                                                 |
| Migrasi, seeder, `wariga:import` | Session mode (sama)              | sama                                                                                            |
| Jaringan sudah mendukung IPv6    | Direct connection (opsional)     | `db.<ref>.supabase.co:5432`                                                                     |
| Jangan dipakai                   | Transaction mode (`:6543`)       | Shared pooler tidak mendukung prepared statements di mode ini, sedangkan Laravel/PDO memakainya |

Username di pooler berformat **`postgres.<project-ref>`** (bukan `postgres` saja).

> Host persis (indeks `aws-0`/`aws-1`, region) **salin dari dashboard** (tombol _Connect_), jangan menebak.

---

## 3. Konfigurasi Laravel

`.env`

```
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=
DB_SSLMODE=require
DB_SEARCH_PATH=public,extensions
```

`config/database.php` → koneksi `pgsql`:

```php
'search_path' => env('DB_SEARCH_PATH', 'public'),
'sslmode' => env('DB_SSLMODE', 'prefer'),
```

Catatan:

- Queue driver tetap `database` (tabel `jobs` di Supabase). Jumlah job kecil, aman.
- Cache & session: `database` atau `file`. Hindari polling queue terlalu rapat (`--sleep=3`) supaya koneksi tidak boros.
- Untuk test lokal & CI pakai **Postgres lokal (Docker)** atau proyek Supabase terpisah. **Jangan menjalankan test ke database produksi.**

---

## 4. Keamanan

Supabase otomatis mengekspos schema `public` lewat REST (Data API) yang bisa diakses dengan _anon key_. Rahina tidak memakai Data API, jadi:

1. **Matikan Data API** di Project Settings, **atau**
2. Aktifkan **RLS di setiap tabel tanpa policy** (artinya tertutup untuk anon/authenticated). Laravel terhubung sebagai role `postgres` sehingga tidak terpengaruh RLS.

Tambahkan migrasi penutup yang menyalakan RLS untuk semua tabel di `public`:

```php
foreach (DB::select("select tablename from pg_tables where schemaname = 'public'") as $t) {
    DB::statement("alter table public.\"{$t->tablename}\" enable row level security");
}
```

dan **jalankan ulang setiap ada tabel baru** (atau buat command `db:lock-public`).

Lainnya:

- `anon key` dan `service_role key` **tidak dipakai** dan tidak disimpan di `.env` aplikasi.
- Data sensitif (nomor WA, tanggal lahir) tetap dienkripsi di level aplikasi (`encrypted` cast), tidak bergantung pada Supabase.
- Cek **Security Advisor** di dashboard sebelum submission.

---

## 5. Batas Paket Free & Mitigasi

Paket Free: database 500 MB per proyek, maksimal 2 proyek, dan **proyek di-pause setelah 7 hari tidak aktif**. Free juga tidak punya backup harian.

| Batas                            | Dampak ke Rahina                                                  | Mitigasi                                                                                                                                                                                         |
| -------------------------------- | ----------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 500 MB DB                        | Perkiraan pemakaian < 50 MB (kalender ±5.800 baris, tanpa vektor) | Pantau `pg_database_size`; batasi log `ai_interactions` & `whatsapp_messages` (hapus > 90 hari)                                                                                                  |
| Pause setelah 7 hari tidak aktif | Fatal saat penjurian                                              | Scheduler harian (brief 05.30, WA 06.00) sudah membuat aktivitas rutin. Tambahan: `health:ping` setiap 6 jam (`select 1`). **Cek dashboard H-3 sebelum penjurian & saat technical verification** |
| Tanpa backup                     | Data hilang jika salah migrasi                                    | Command `db:backup` mingguan (`pg_dump` → file terenkripsi di luar Supabase). Data kalender selalu bisa dibangun ulang dari script                                                               |
| Kuota egress                     | Kecil untuk aplikasi ini                                          | —                                                                                                                                                                                                |

Upgrade ke Pro dipertimbangkan jika jumlah pengguna WhatsApp aktif bertambah atau butuh backup otomatis.

---

## 6. Hosting Aplikasi Laravel

Supabase hanya menampung database. Laravel tetap butuh tempat berjalan yang mendukung:

- PHP 8.3+ + Node (untuk build & script kalender),
- **proses queue worker** yang terus hidup,
- **scheduler** (`php artisan schedule:run` setiap menit),
- koneksi keluar IPv4 ke pooler Supabase.

Isi pilihan hosting di `ARCHITECTURE.md` §4 dan `AI_DISCLOSURE.md`.

---

## 7. Checklist

- [x] Proyek Supabase aktif (pooler ap-northeast-1)
- [x] Koneksi via Session pooler berhasil (`php artisan migrate`)
- [x] Ekstensi `pg_trgm` aktif via migrasi
- [x] RLS aktif di semua tabel (`db:lock-public`, otomatis setelah migrate). Data API sebaiknya tetap dimatikan di dashboard
- [ ] Security Advisor bersih
- [ ] `health:ping` & `db:backup` terjadwal
- [ ] Database test terpisah dari produksi
