# WHATSAPP.md - Rahina Pagi via WhatsApp (Fonnte)

Fitur: pengguna yang mendaftarkan nomor WhatsApp menerima **ringkasan harian pukul 06.00 WITA** (bagian umum + bagian pribadi berdasarkan otonan), dan bisa **membalas pesan untuk bertanya** ke Tanya Wariga.

Penyedia: **Fonnte** (gateway WhatsApp). Semua akses ke Fonnte dibungkus interface `MessagingGateway`, sehingga bisa diganti ke WhatsApp Cloud API resmi tanpa mengubah logika bisnis.

---

## 1. Keputusan Utama

| Keputusan        | Pilihan                                                                    | Alasan                                                                                                                                            |
| ---------------- | -------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Penyedia         | Fonnte (paket berbayar)                                                    | Setup cepat, tanpa verifikasi bisnis Meta, pesan bebas format. Paket gratis tidak dipakai karena ada pembatasan konten dan device sering terputus |
| Nomor pengirim   | **Nomor khusus Rahina** (bukan nomor pribadi anggota tim)                  | Jika terkena pembatasan, nomor pribadi tidak terdampak                                                                                            |
| Verifikasi nomor | Pengguna **mengirim pesan duluan** ("DAFTAR <kode>") lewat tautan `wa.me`  | Membuktikan kepemilikan nomor, dan percakapan diawali pengguna sehingga menurunkan risiko dianggap spam                                           |
| Bagian pribadi   | Dihitung deterministik, **tanpa LLM**                                      | Tanggal lahir tidak pernah dikirim ke AI                                                                                                          |
| Abstraksi        | `MessagingGateway` → `FonnteGateway` (sekarang), `CloudApiGateway` (nanti) | Modular; rencana migrasi untuk produksi                                                                                                           |

### Risiko yang diakui (masuk _Known Limitations_ & disclosure)

Fonnte bukan WhatsApp Business Platform resmi Meta. Nomor pengirim berisiko dibatasi atau diblokir oleh WhatsApp, terutama jika volume tinggi atau banyak dilaporkan sebagai spam. Mitigasi ada di §5. Untuk skala produksi direncanakan migrasi ke Cloud API resmi.

---

## 2. Integrasi Fonnte

Semua endpoint dibangun dari `FONNTE_API_URL` (default `https://api.fonnte.com`) dan memakai header `Authorization: <FONNTE_TOKEN>` (token **device**, bukan token akun).

| Endpoint                       | Fungsi di Rahina                                                                                                         |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| `POST {FONNTE_API_URL}/send`   | Kirim pesan pagi & jawaban                                                                                               |
| `POST {FONNTE_API_URL}/device` | Ambil nomor device, status (`connect`/`disconnect`), dan kuota. **Di-cache 1 jam** karena pemanggilannya dibatasi Fonnte |

### 2.1 Kirim pesan

```
POST {FONNTE_API_URL}/send
Header: Authorization: <FONNTE_TOKEN>
Body (form/JSON):
  target      = 62812xxxxxxx
  message     = teks (boleh multi-baris, format WA *tebal* _miring_)
  typing      = true            (indikator mengetik, opsional)
  inboxid     = <id>            (opsional, membalas pesan tertentu)
```

- Penjadwalan pengiriman dilakukan oleh **scheduler Laravel**, bukan fitur jadwal Fonnte, supaya waktu (WITA) dan retry terkendali di server.
- Simpan `id` dari respons sebagai `provider_message_id`.

### 2.2 Webhook pesan masuk

- Atur URL webhook di dashboard Fonnte (Device → Edit) dan **aktifkan autoread** (wajib agar webhook berjalan; bisa pakai _silent autoread_).
- Fonnte mengirim **POST** dengan field antara lain `device`, `sender`, `message`, timestamp, dan `inboxid`.
- Endpoint harus **membalas HTTP 200 secepatnya**. Proses berat (agent AI) dijalankan di **queue job**, bukan di request webhook. Fonnte melakukan retry bila gagal, jadi handler harus **idempoten**.

### 2.3 Nomor device (tanpa env tambahan)

Nomor WhatsApp Rahina **tidak ditulis di `.env`**. `FonnteGateway::deviceInfo()` memanggil `/device`, lalu hasilnya (`device`, `device_status`, `quota`) di-cache 1 jam (`cache key: fonnte:device`). Nomor ini dipakai untuk:

- tautan pendaftaran `wa.me/<device>`,
- validasi webhook (field `device` harus sama),
- health check (status `disconnect` → alert admin, pengiriman ditunda; `quota` rendah → alert).

### 2.4 Keamanan webhook

Fonnte tidak menyediakan tanda tangan (signature) webhook, jadi:

1. URL webhook memuat **secret turunan dari `APP_KEY`**: `hash_hmac('sha256', 'fonnte-webhook', APP_KEY)` (32 karakter pertama). Tidak perlu env baru.
   Jalankan `php artisan wa:device --webhook` untuk menampilkan URL lengkap yang ditempel di dashboard Fonnte (Device → Edit → Webhook).
2. Tolak (403) jika field `device` ≠ nomor device dari §2.3.
3. Aktifkan **IP whitelist** di Fonnte untuk request `send` (hanya IP server Rahina).
4. `FONNTE_TOKEN` hanya di `.env`, tidak pernah di repo.
5. Jika `APP_KEY` dirotasi, URL webhook berubah, jadi jalankan ulang `wa:device --webhook` dan perbarui di Fonnte.

---

## 3. Alur

### 3.1 Pendaftaran (opt-in)

```
Web (Profil, wajib masuk akun): (opsional) tanggal lahir, centang persetujuan:
  [ ] Saya setuju menerima ringkasan harian Rahina via WhatsApp pukul 06.00 WITA
  [ ] Saya setuju tanggal lahir dipakai untuk menghitung otonan (opsional)
→ Server membuat kode 6 karakter (15 menit)
→ Tombol "Hubungkan WhatsApp" → https://wa.me/<nomor device dari /device>?text=DAFTAR%20<KODE>
→ Webhook menerima "DAFTAR <KODE>" dari sender X → cocok → simpan langganan (nomor terenkripsi)
→ Balas: konfirmasi + cara berhenti + anjuran menyimpan nomor Rahina
```

### 3.2 Pengiriman pagi

```
05:30 WITA  brief:generate       → daily_briefs (satu ringkasan umum; jadwal aktual 00:05)
05:45 WITA  wa:prepare-morning   → susun pesan tiap langganan aktif
              umum   = daily_briefs[umum]
              pribadi = PersonalNoteBuilder (tanpa LLM)
            → dispatch SendMorningBrief dengan jeda bertahap mulai 06:00
06:00-06:30 kirim berurutan, jeda acak 4-12 detik antar pesan
```

Kapasitas kasar: 30 menit ÷ ±8 detik ≈ 225 pesan per nomor. Lebih dari itu → perpanjang jendela atau tambah device.

### 3.3 Tanya lewat balasan

```
Pesan bebas → webhook → simpan → 200 OK
→ Job AnswerWhatsAppQuestion → kirim "typing" → AskWarigaAction (agent yang sama dengan web) → OutputGuard
→ Balas maks 5 tanggal + tautan detail
```

Kata kunci: `DAFTAR <kode>`, `BERHENTI`/`STOP`, `MULAI`, `PROFIL`, `BANTUAN`, `HAPUS DATA`.

---

## 4. Format Pesan

```
*Rahina · {Hari}, {tanggal}*
{Saptawara} {Pancawara} · Wuku {x} · {Sasih}

*Hari ini secara umum*
- {poin 1}
- {poin 2}
- {poin 3}

*Untuk Anda*
- {catatan pribadi}

Detail: {url}/hari/{yyyy-mm-dd}
_Menurut perhitungan wariga. Untuk upacara besar, konsultasikan dengan sulinggih atau pemangku._
Balas pesan ini untuk bertanya hari baik. Ketik BERHENTI untuk berhenti.
```

- Tanpa emoji. Isi poin diambil dari data tervalidasi; teks di atas hanya format.
- Variasikan baris pembuka/penutup dari 3-4 versi agar pesan tidak identik 100% setiap hari.

### Bagian pribadi (`PersonalNoteBuilder`, tanpa LLM)

Ambil 1-2 yang berlaku: (1) hari ini otonan, (2) otonan ≤ 14 hari lagi, (3) rahinan besar ≤ 7 hari lagi (persona dihapus 2026-11-06). Tidak ada yang berlaku → bagian ini dihilangkan.
Tidak memuat ramalan watak/nasib kecuali aturannya bersumber dan tervalidasi.

---

## 5. Mitigasi Risiko Blokir

1. Kirim **hanya** ke nomor yang sudah opt-in lewat "DAFTAR" (mereka yang memulai percakapan).
2. Minta pengguna **menyimpan nomor Rahina** di pesan konfirmasi.
3. Jeda acak antar pesan, kirim bertahap, tanpa blast serentak.
4. **Pemanasan nomor**: minggu pertama hanya tim & penguji (≤ 20 nomor), naikkan bertahap.
5. Opt-out seketika; nomor yang 3× gagal beruntun dinonaktifkan.
6. Satu tautan per pesan, tanpa tautan pendek (shortlink).
7. Pantau status device (cek berkala via API/dashboard); jika terputus → alert ke admin dan pengiriman ditunda, bukan dibuang.
8. Fitur web tetap berfungsi penuh tanpa WhatsApp.

---

## 6. Data & Privasi (UU PDP No. 27/2022)

- Nomor WA disimpan terenkripsi (`encrypted` cast) + `phone_hash` (HMAC) untuk pencarian.
- Tanggal lahir opsional & terenkripsi; sistem cukup memakai `otonan_*`.
- Nomor, nama, dan tanggal lahir **tidak** dikirim ke Gemini. Pertanyaan balasan dikirim tanpa identitas.
- **Pihak ketiga:** isi pesan melewati server Fonnte. Ini dijelaskan di halaman `/privasi` dan di formulir persetujuan.
- `HAPUS DATA` → hapus langganan & log terkait.

---

## 7. Komponen Kode

```
app/Messaging/
  MessagingGateway.php        # interface: send(Outgoing): SendResult; typing(to, seconds); parseInbound(Request): Inbound
  Gateways/FonnteGateway.php  # implementasi Fonnte (satu-satunya tempat HTTP ke api.fonnte.com)
  Gateways/FakeGateway.php    # untuk test & lokal
  InboundRouter.php           # DAFTAR / BERHENTI / MULAI / BANTUAN / HAPUS DATA / pertanyaan
  MessageFormatter.php        # format pesan pagi & jawaban
  PersonalNoteBuilder.php
app/Http/Controllers/Webhooks/FonnteWebhookController.php   # validasi secret turunan APP_KEY & device, simpan, 200 OK, dispatch job
app/Console/Commands/WaDevice.php, WaWebhookUrl.php
app/Jobs/SendMorningBrief.php        # retry 3× backoff
app/Jobs/AnswerWhatsAppQuestion.php
app/Console/Commands/WaPrepareMorning.php
config/messaging.php                 # driver: fonnte|cloud_api|fake
```

Env (hanya ini):

```
MESSAGING_DRIVER=fonnte        # fake untuk lokal/test
FONNTE_TOKEN=                  # token device dari dashboard Fonnte
FONNTE_API_URL=https://api.fonnte.com
```

Pengaturan pengiriman ada di `config/messaging.php` (bukan env), dengan nilai default:

```php
'send_time' => '06:00',            // WITA
'send_window_minutes' => 30,
'delay_seconds' => [4, 12],        // jeda acak antar pesan
'device_cache_minutes' => 60,
```

Command pendukung:

```
php artisan wa:device          # cek nomor, status, kuota device (dari /device)
php artisan wa:device --webhook # tampilkan URL webhook untuk ditempel di Fonnte
```

---

## 8. Penanganan Error

| Kejadian                                       | Perilaku                                                                                                            |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| Brief LLM gagal                                | Fallback template non-AI, tetap terkirim                                                                            |
| Fonnte error / device terputus                 | Cek `/device` sebelum batch pagi; `disconnect` → tunda batch & alert admin. Error kirim → retry 3× (1, 5, 15 menit) |
| Token salah (`status: false`, "token invalid") | Hentikan batch, catat error, alert admin                                                                            |
| Webhook dobel (retry Fonnte)                   | Idempoten via hash (`sender` + timestamp + isi) / `inboxid`                                                         |
| Agent lambat                                   | Kirim indikator typing; balasan dari queue                                                                          |
| Pertanyaan di luar topik                       | Balasan sopan + menu bantuan                                                                                        |

---

## 9. Testing

- `FakeGateway` untuk semua test; tidak ada panggilan ke Fonnte di CI.
- Test: router kata kunci, idempotensi webhook, secret/device salah → 403, cache `/device`, formatter (panjang, tanpa emoji), PersonalNoteBuilder (otonan hari ini / 14 hari lagi / tidak ada).

---

## 10. Status Implementasi (2026-11-06)

Sudah dibangun & diuji (`tests/Feature/WhatsApp/WhatsAppTest.php`, driver `fake`):

| Bagian            | File                                                                                                                                                                |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Gateway           | `app/Messaging/MessagingGateway.php`, `Gateways/FonnteGateway.php` (satu-satunya HTTP ke Fonnte), `Gateways/FakeGateway.php`                                        |
| Opt-in web        | `/profil` (wajib masuk akun) → `RahinaProfileController`: persetujuan, tanggal lahir opsional + persetujuan terpisah, kode DAFTAR 15 menit, jeda/lanjut, hapus data |
| Webhook           | `POST /webhooks/fonnte/{secret}` → `Webhooks/FonnteWebhookController` (secret turunan APP_KEY, cek device, idempoten, 200 OK, queue)                                |
| Router kata kunci | `app/Messaging/InboundRouter.php`: DAFTAR, BERHENTI/STOP, MULAI, BANTUAN, PROFIL, HAPUS DATA, selain itu → Tanya Wariga                                             |
| Pesan pagi        | `wa:prepare-morning` (05:45, `routes/console.php`) → `Jobs/SendMorningBrief` bertahap mulai 06:00, jeda acak 4-12 s, idempoten per tanggal, retry 1/5/15 menit      |
| Catatan pribadi   | `PersonalNoteBuilder` + `App\Wariga\Otonan` (siklus 210 hari, tanpa LLM)                                                                                            |
| Perintah          | `php artisan wa:device` (status device), `php artisan wa:device --webhook` (URL webhook)                                                                            |

Perubahan dari rencana: persona dihapus (satu ringkasan umum); nomor tidak diketik di web, tetapi diambil dari pesan DAFTAR.

### Langkah mengaktifkan di server (go-live)

1. Aplikasi harus bisa diakses publik (Fonnte tidak bisa memanggil `localhost`; untuk uji lokal pakai tunnel, mis. Cloudflare Tunnel/ngrok).
2. `.env`: `MESSAGING_DRIVER=fonnte`, `FONNTE_TOKEN=<token device>`, `APP_URL=<URL publik>`.
3. `php artisan wa:device --fresh` → pastikan status **terhubung**.
4. `php artisan wa:device --webhook` → tempel URL di dashboard Fonnte (Device → Edit → Webhook) dan aktifkan autoread.
5. Jalankan `php artisan queue:work` (proses balasan & pesan pagi) dan scheduler (`php artisan schedule:work`, atau cron `* * * * * php artisan schedule:run`).
6. Pemanasan nomor (§5.4): minggu pertama hanya tim & penguji.
