# DESIGN_SYSTEM.md — Rahina

Sumber kebenaran untuk seluruh UI. Setiap komponen baru harus memakai token di sini.

---

## 1. Arah Desain

**Rahina terasa seperti almanak lontar yang dicetak rapi**: tenang, tepercaya, mudah dibaca orang tua maupun anak muda. Rahina tidak terasa seperti "produk AI".

| Kata kunci    | Wujud visual                                                                                 |
| ------------- | -------------------------------------------------------------------------------------------- |
| **Tepercaya** | Tipografi serif untuk tanggal & judul, grid kalender yang tegas, sumber selalu terlihat      |
| **Tenang**    | Latar krem kertas, satu warna utama, banyak ruang kosong                                     |
| **Jelas**     | Status _ayu / ala / campuran_ selalu berupa **teks + ikon + warna**, tidak pernah warna saja |
| **Membumi**   | Warna dari bahan tradisional (nila, kunyit, kesumba, daun), ornamen minimal                  |

### 1.1 Larangan "AI slop"

- Tidak ada gradient ungu/biru/pink, teks gradient, mesh, atau glow.
- Tidak ada glassmorphism / `backdrop-blur` sebagai gaya.
- **Tidak ada badge "AI", "AI-powered", "Smart", "Generated", atau ikon sparkle/bintang/tongkat sihir.**
- **Tidak ada emoji** di UI, copy, notifikasi, atau placeholder. Semua simbol memakai **ikon**.
- Tidak memakai font Inter / default sistem sebagai identitas.
- Tidak ada kartu generik `rounded-2xl shadow-lg` di semua tempat.
- Tidak ada animasi "thinking" berkilau. Loading = skeleton bergaris + teks status yang konkret ("Mencari hari baik di Desember 2026…").

**Cara menyebut fitur AI:** sebut fungsinya, bukan teknologinya. Pakai "Ringkasan hari ini", "Tanya Wariga", "Disusun dari perhitungan wariga · 3 sumber", bukan "AI Summary".

---

## 2. Warna

### 2.1 Warna utama (deklarasi)

> **Warna utama: `nila` `#23466B`**, biru nila dari pewarna alam tenun Bali.
> Dipakai untuk: tombol utama, tautan, tanggal terpilih, tab aktif, focus ring, header kalender.
> Bukan ungu, bukan biru elektrik.

**Aksen: `kunyit` `#D99A1E`** untuk penanda **hari ini**, purnama, dan highlight. Hanya untuk latar/garis, **tidak pernah** untuk teks.

### 2.2 Token lengkap

| Token        | Hex           | Peran                                         |
| ------------ | ------------- | --------------------------------------------- |
| `mangsi`     | `#1C1B19`     | Teks utama, garis grid tegas                  |
| `kertas`     | `#FFFDF8`     | Permukaan kartu                               |
| `lontar`     | `#F6F1E7`     | Latar aplikasi                                |
| `abu-600`    | `#66615A`     | Teks sekunder, metadata                       |
| `abu-400`    | `#A39D93`     | Disabled, ikon dekoratif (bukan teks penting) |
| `abu-200`    | `#DDD6C9`     | Border, divider, garis grid lembut            |
| **`nila`**   | **`#23466B`** | **Primer**                                    |
| `nila-700`   | `#183451`     | Hover/pressed primer                          |
| `nila-100`   | `#DCE5EF`     | Latar terpilih, tint info                     |
| `kunyit`     | `#D99A1E`     | Aksen: hari ini, purnama                      |
| `kunyit-800` | `#7A520A`     | Teks di atas tint kunyit                      |
| `kunyit-100` | `#FAEBC8`     | Latar highlight                               |
| `ayu`        | `#2E7049`     | Status **baik**                               |
| `ayu-100`    | `#DDEDE2`     | Latar status baik                             |
| `ala`        | `#A8352B`     | Status **tidak baik**, error                  |
| `ala-100`    | `#F6DCD8`     | Latar status tidak baik                       |
| `campur`     | `#8A5A14`     | Status **campuran** (ada baik & tidak baik)   |

### 2.3 Kontras (WCAG 2.1, sudah dihitung)

| Kombinasi                   | Rasio | Status                          |
| --------------------------- | ----- | ------------------------------- |
| `mangsi` di `lontar`        | 15.3  | Lolos                           |
| Putih di `nila` (tombol)    | 9.7   | Lolos                           |
| `nila` di `lontar` (tautan) | 8.6   | Lolos                           |
| `ayu` di `ayu-100`          | 4.9   | Lolos                           |
| `ala` di `ala-100`          | 5.0   | Lolos                           |
| `campur` di `kunyit-100`    | 5.0   | Lolos                           |
| `abu-600` di `lontar`       | 5.5   | Lolos                           |
| `kunyit` di `lontar`        | 2.2   | **Dilarang untuk teks**         |
| `abu-400` di `lontar`       | 2.4   | **Dilarang untuk teks penting** |

### 2.4 Status verdict (inti produk)

| Verdict       | Warna                   | Ikon Phosphor | Label              |
| ------------- | ----------------------- | ------------- | ------------------ |
| Baik          | `ayu` / `ayu-100`       | `CheckCircle` | "Baik untuk…"      |
| Tidak baik    | `ala` / `ala-100`       | `XCircle`     | "Hindari…"         |
| Campuran      | `campur` / `kunyit-100` | `Scales`      | "Ada pertimbangan" |
| Netral / info | `nila` / `nila-100`     | `Info`        | —                  |

---

## 3. Tipografi

| Peran                       | Font                            | Alasan                                                 |
| --------------------------- | ------------------------------- | ------------------------------------------------------ |
| Tanggal, judul, angka besar | **Spectral** (600, 700)         | Serif hangat ala almanak; angka tanggal terasa "resmi" |
| UI & body                   | **Public Sans** (400, 500, 700) | Netral, sangat terbaca, cocok untuk informasi publik   |
| Aksara Bali                 | **Noto Sans Balinese**          | Dukungan Unicode lengkap                               |

Semua dari Google Fonts (OFL). Angka di grid kalender memakai `font-variant-numeric: tabular-nums`.

| Token     | Ukuran/LH (mobile → desktop) | Font                                               |
| --------- | ---------------------------- | -------------------------------------------------- |
| `date-xl` | 56/56 → 72/72                | Spectral 700 (tanggal besar di "Rahina Ini")       |
| `h1`      | 28/34 → 34/40                | Spectral 700                                       |
| `h2`      | 22/28                        | Spectral 600                                       |
| `h3`      | 18/24                        | Public Sans 700                                    |
| `body-lg` | 18/28                        | Public Sans 400 (ringkasan harian)                 |
| `body`    | 16/24                        | Public Sans 400                                    |
| `label`   | 14/20                        | Public Sans 500, tracking `.01em`                  |
| `meta`    | 13/18                        | Public Sans 400, `abu-600` (wuku, wewaran, sumber) |
| `term`    | sama dengan konteks          | _italic_ untuk istilah wariga pertama kali muncul  |

Istilah wariga (`Sampi Gumarang Turun`, `Kala Sor`) ditulis **tebal**, dan bisa di-tap untuk menampilkan penjelasan singkat.

---

## 4. Bentuk & Ruang

```css
--radius-sm: 6px; /* chip, tag */
--radius-md: 10px; /* tombol, input */
--radius-lg: 16px; /* kartu, sheet */
--border: 1px solid var(--abu-200);
--border-strong: 1.5px solid var(--mangsi);
--shadow-1: 0 1px 0 var(--abu-200), 0 1px 2px rgb(28 27 25 / 0.06);
--shadow-2: 0 2px 0 var(--abu-200), 0 6px 16px rgb(28 27 25 / 0.08);
```

- Skala spasi 4pt: `4 8 12 16 20 24 32 40 56`.
- Kartu memakai **border, bukan bayangan tebal**. `shadow-2` hanya untuk sheet/popover.
- Grid kalender memakai garis 1px `abu-200`. Garis antar-minggu boleh `mangsi` 1.5px pada header.

### Ornamen

- Satu motif: **garis tepi patra sederhana** (stroke 1.5px `abu-200`) untuk header halaman "Rahina Ini" dan kartu bagikan. Maksimal satu ornamen per layar.
- **Fase bulan** (purnama/tilem/penanggal/panglong) digambar sebagai SVG custom geometris sederhana.

---

## 5. Ikon

- Pustaka: **Phosphor Icons**, weight `regular` (UI umum) dan `bold` (status/verdict), ukuran 20px (inline) / 24px (navigasi).
- Warna ikon mengikuti teks di sekitarnya. Ikon tidak boleh berwarna sendiri tanpa label.
- Ikon custom (SVG, stroke 1.5px, sudut membulat): `MoonFull` (purnama), `MoonNew` (tilem), `MoonWaxing`, `MoonWaning`. Rahina tidak memakai gamifikasi (poin/koin/streak).
- Pemetaan kategori aktivitas:

| Kategori               | Ikon Phosphor |
| ---------------------- | ------------- |
| Upacara / Yadnya       | `FlowerLotus` |
| Pertanian / Perkebunan | `Plant`       |
| Peternakan / Perikanan | `Fish`        |
| Peralatan / Senjata    | `Wrench`      |
| Pembangunan            | `HouseLine`   |
| Aneka kegiatan         | `SquaresFour` |
| Berdagang              | `Storefront`  |

- Ikon-only button wajib memiliki `aria-label`.

---

## 6. Komponen

### 6.1 Button

| Varian      | Gaya                                                 |
| ----------- | ---------------------------------------------------- |
| `primary`   | latar `nila`, teks putih, hover `nila-700`           |
| `secondary` | latar `kertas`, border `mangsi` 1.5px, teks `mangsi` |
| `ghost`     | teks `nila`, underline saat hover                    |
| `danger`    | latar `ala`, teks putih                              |

Tinggi 44px (mobile 48px), radius `md`, focus ring `3px nila-100` + outline `2px nila`.

### 6.2 DayCell (sel kalender bulanan)

```
┌────────────┐
│ 12      ◐  │  ← tanggal (Spectral, tabular) + ikon fase bulan bila relevan
│ Soma Pon   │  ← meta: saptawara + pancawara
│ ▬▬▬ ▬      │  ← strip verdict untuk kategori aktif: ayu/ala/campur (+ tooltip teks)
│ • Rahinan  │  ← titik nila + nama rahinan disingkat
└────────────┘
```

- Hari ini: border atas 3px `kunyit` + latar `kunyit-100`.
- Terpilih: border 1.5px `nila` + latar `nila-100`.
- Di luar bulan berjalan: teks `abu-400`.
- Strip verdict selalu memiliki teks alternatif (`aria-label="Baik untuk pertanian"`).

### 6.3 TodayBrief (kartu "Rahina Ini")

- Atas: `date-xl` + nama hari Bali lengkap (`meta`) + wuku + sasih.
- Isi: 3–4 poin `body-lg`, masing-masing diawali **VerdictTag**.
- Bawah: baris sumber `meta`: ikon `BookOpen` + "Disusun dari perhitungan wariga · 3 sumber". Tap → sheet daftar dewasa mentah + sumber.
- Pemilih profil (chip): Umum · Petani · Pedagang · Pemangku.

### 6.4 DewasaRow

```
[CheckCircle] Sampi Gumarang Turun                 Alahing 4  ▮▮▮▮▯
              Baik untuk menanam padi, jagung…
              Sumber: [Judul buku], hlm. [x]
```

- Indikator _alahing dewasa_ = 5 segmen (isi = tingkat), disertai angka.

### 6.5 VerdictTag

Chip radius `sm`, tinggi 24, ikon 16 + label. Contoh: `[CheckCircle] Baik`, `[XCircle] Hindari`, `[Scales] Pertimbangkan`.

### 6.6 DateRefCard (referensi tanggal di jawaban Tanya Wariga)

```
┌──────────────────────────────────────────────┐
│ Sel, 15 Des 2026            [VerdictTag Baik]│
│ Anggara Kliwon · Wuku [x] · Sasih [x]        │
│ Mendukung: [Dewasa A], [Dewasa B]            │
│ Penghalang: —                                │
│ [Lihat detail hari →]   [Tambah ke kalender] │
└──────────────────────────────────────────────┘
```

Selalu bisa di-tap menuju halaman detail hari. Ini bukti bahwa jawaban berasal dari data.

### 6.7 Chat "Tanya Wariga"

- Pesan user: rata kanan, latar `nila-100`, radius 16/16/4/16.
- Jawaban: rata kiri, **tanpa avatar robot**, latar `kertas` + border. Isinya paragraf singkat + daftar `DateRefCard` + baris sumber.
- Saran pertanyaan berupa chip dengan ikon kategori, misalnya `[Plant] Hari baik menanam padi bulan ini`.
- Saat memproses: skeleton 2 kartu + teks status konkret dari langkah agent ("Mencocokkan aktivitas: menanam cabai → pertanian").
- Jika aktivitas ambigu: tampilkan pilihan kategori (chip), bukan menebak.

### 6.8 ConflictExplainer

Dua kolom (mobile: bertumpuk) **Mendukung** vs **Menghalangi**, masing-masing berisi `DewasaRow`. Di bawahnya ada kesimpulan berlabel `Scales` + alasan prioritas + sumber aturan prioritas.

### 6.9 CulturalNotice

Banner `nila-100`, ikon `Info`: "Untuk upacara besar, mohon konsultasikan dewasa dengan sulinggih atau pemangku setempat." Wajib tampil di kategori Upacara/Yadnya dan di jawaban yang menyangkut pawiwahan, ngaben, atau melaspas.

### 6.10 Empty / Error state

Ikon 32px `abu-600` + satu kalimat + satu aksi. Contoh error AI: ikon `CloudSlash` + "Ringkasan belum bisa disusun. Data perhitungan hari ini tetap tersedia di bawah."

---

## 7. Layout

- Mobile-first 360–430px; konten maks 640px; desktop ≥1024: nav kiri 232px + konten + panel kanan 320px (detail hari terpilih).
- Bottom nav mobile (ikon + label): **Hari Ini** (`Sun`) · **Kalender** (`CalendarBlank`) · **Tanya** (`ChatCircleText`) · **Rencana** (`ListChecks`) · **Profil** (`User`).

---

## 8. Motion

- Durasi 120–200ms, easing `cubic-bezier(.2,.7,.2,1)`.
- Pindah bulan: geser horizontal 160ms. Sheet: slide-up 180ms.
- Tanpa animasi dekoratif loop. `prefers-reduced-motion` → fade saja.

---

## 9. Bahasa & Copy

- Bahasa Indonesia santun, kalimat pendek, istilah wariga dipertahankan + dijelaskan.
- Pola kalimat: **aksi dulu, alasan kemudian.** "Baik untuk menanam padi, karena hari ini jatuh _[Dewasa]_."
- Hindari kata mutlak seperti "pasti", "dijamin". Pakai "menurut perhitungan wariga".
- Tanpa emoji, tanpa tanda seru beruntun.

---

## 10. Aksesibilitas

- Kontras sesuai §2.3; target sentuh ≥44px; focus ring selalu terlihat.
- Grid kalender: `role="grid"`, navigasi panah, `aria-selected`, `aria-current="date"` untuk hari ini.
- Status tidak pernah hanya warna (ikon + teks).
- `lang="ban"` untuk istilah Bali, `lang="ban-Bali"` untuk aksara.
- Ukuran teks bisa diperbesar sampai 200% tanpa layout rusak (penting untuk pengguna lansia).

---

## 11. Token Tailwind v4

```css
@import 'tailwindcss';

@theme {
    --color-mangsi: #1c1b19;
    --color-kertas: #fffdf8;
    --color-lontar: #f6f1e7;
    --color-abu-600: #66615a;
    --color-abu-400: #a39d93;
    --color-abu-200: #ddd6c9;
    --color-nila: #23466b;
    --color-nila-700: #183451;
    --color-nila-100: #dce5ef;
    --color-kunyit: #d99a1e;
    --color-kunyit-800: #7a520a;
    --color-kunyit-100: #faebc8;
    --color-ayu: #2e7049;
    --color-ayu-100: #ddede2;
    --color-ala: #a8352b;
    --color-ala-100: #f6dcd8;
    --color-campur: #8a5a14;

    --font-display: 'Spectral', Georgia, serif;
    --font-body: 'Public Sans', ui-sans-serif, sans-serif;
    --font-aksara: 'Noto Sans Balinese', serif;

    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 16px;

    --ease-rahina: cubic-bezier(0.2, 0.7, 0.2, 1);
}
```

---

## 12. Checklist Review UI

- [ ] Hanya memakai token (tanpa hex liar)
- [ ] Tidak ada emoji, badge AI, ikon sparkle, gradient
- [ ] Verdict = ikon + teks + warna
- [ ] Jawaban AI menampilkan `DateRefCard` dan baris sumber
- [ ] `CulturalNotice` muncul untuk konteks upacara
- [ ] Kontras sesuai §2.3, keyboard & screen reader berfungsi
- [ ] Dicek di 390px dan 1280px, serta zoom 200%
