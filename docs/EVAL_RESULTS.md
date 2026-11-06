# EVAL_RESULTS.md — Tanya Wariga

_Dihasilkan `php artisan wariga:eval` pada 2026-11-06 08:50:00 WITA · model `gemini-3.1-flash-lite` · prompt `agent_system.v1` · "hari ini" agent = 2026-11-06._

Data dewasa: kalenderbali.org (dengan izin, belum ditinjau narasumber). Akurasi tanggal diukur terhadap hasil hitung ulang `DateRanker` dengan argumen tool yang sama, dan rentang tanggal yang diminta pertanyaan.

| Metrik                                      | Hasil         | Target      |
| ------------------------------------------- | ------------- | ----------- |
| Akurasi tanggal (125/125 tanggal)           | 100.0%        | 100%        |
| Pemetaan kategori (25/25)                   | 100.0%        | ≥ 90%       |
| Klarifikasi tepat (7/8)                     | 87.5%         | ≥ 85%       |
| Penolakan di luar topik (0/7)               | 0.0%          | 100%        |
| Catatan budaya upacara (5/5)                | 100.0%        | 100%        |
| Latensi total p50 / p95 (termasuk query DB) | 12.3s / 31.4s | < 4s / < 9s |
| Latensi Gemini saja p50 / p95               | 11.6s / 26.2s | —           |
| Error API / agent                           | 10            | 0           |

> **Belum final:** 10 pertanyaan tidak terjawab karena error API (mis. kuota Gemini habis), sehingga metrik tipe terkait belum mencerminkan perilaku agent. Ulangi setelah kuota pulih: `php artisan wariga:eval --resume --sleep=12`.

Kriteria: _range_ = kategori benar + semua tanggal terbukti dari hasil hitung ulang & di rentang yang diminta; _ambiguous_ = bertanya balik tanpa tanggal; _specific_ = tanggal sesuai yang ditanya; _concept_ = memanggil `explain_dewasa`, tanpa tanggal; _offtopic_ = ditolak, tanpa tanggal, tanpa pencarian tanggal yang berhasil.

| #   | Tipe      | Lulus | ms    | Catatan                                                                                     |
| --- | --------- | ----- | ----- | ------------------------------------------------------------------------------------------- |
| 1   | range     | ya    | 9685  | kategori=pertanian.padi tanggal=2026-12-24,2026-12-15,2026-12-30,2026-12-16,2026-12-17      |
| 2   | range     | ya    | 12319 | kategori=pertanian.umbi tanggal=2027-01-10,2027-01-17,2027-01-22,2027-01-28,2027-01-03      |
| 3   | range     | ya    | 8081  | kategori=pertanian tanggal=2026-12-24,2026-12-16,2026-12-17,2026-12-31,2026-12-11           |
| 4   | range     | ya    | 13163 | kategori=dagang.buka_usaha tanggal=2027-02-12,2027-02-22,2027-02-11,2027-02-14,2027-02-08   |
| 5   | range     | ya    | 27102 | kategori=pembangunan tanggal=2027-03-30,2027-03-05,2027-03-07,2027-03-12,2027-03-04         |
| 6   | range     | ya    | 18481 | kategori=upacara.pawiwahan tanggal=2027-04-29                                               |
| 7   | range     | ya    | 21860 | kategori=upacara.ngaben tanggal=2027-05-20,2027-05-15,2027-05-19,2027-05-26,2027-05-10      |
| 8   | range     | ya    | 6031  | kategori=upacara.melaspas tanggal=2027-06-14,2027-06-22,2027-06-10,2027-06-16,2027-06-13    |
| 9   | range     | ya    | 23361 | kategori=peternakan tanggal=2027-07-14,2027-07-03,2027-07-08,2027-07-09,2027-07-20          |
| 10  | range     | ya    | 12404 | kategori=peternakan tanggal=2027-08-02,2027-08-01,2027-08-07,2027-08-25,2027-08-31          |
| 11  | range     | ya    | 10406 | kategori=pertanian tanggal=2027-09-30,2027-09-03,2027-09-20,2027-09-10,2027-09-02           |
| 12  | range     | ya    | 32588 | kategori=pertanian.padi tanggal=2027-10-26,2027-10-03,2027-10-10,2027-10-24                 |
| 13  | range     | ya    | 6026  | kategori=pertanian tanggal=2027-11-15,2027-11-19,2027-11-04,2027-11-30,2027-11-02           |
| 14  | range     | ya    | 24974 | kategori=dagang.buka_usaha tanggal=2027-12-01,2027-12-16,2027-12-17,2027-12-02,2027-12-28   |
| 15  | range     | ya    | 16861 | kategori=pembangunan tanggal=2027-01-10,2027-01-02,2027-01-29,2027-01-14,2027-01-08         |
| 16  | range     | ya    | 14189 | kategori=pembangunan tanggal=2027-02-09,2027-02-13,2027-02-11,2027-02-05,2027-02-08         |
| 17  | range     | ya    | 25270 | kategori=upacara.pawiwahan tanggal=2027-03-10,2027-03-27,2027-03-24,2027-03-29,2027-03-30   |
| 18  | range     | ya    | 31086 | kategori=peralatan tanggal=2027-04-29,2027-04-18                                            |
| 19  | range     | ya    | 8079  | kategori=peternakan tanggal=2027-05-20,2027-05-03,2027-05-06,2027-05-09,2027-05-13          |
| 20  | range     | ya    | 31364 | kategori=pembangunan tanggal=2027-06-25,2027-06-05,2027-06-09,2027-06-13,2027-06-28         |
| 21  | range     | ya    | 21735 | kategori=dagang.buka_usaha tanggal=2027-07-31,2027-07-05,2027-07-09,2027-07-08,2027-07-04   |
| 22  | range     | ya    | 5705  | kategori=pertanian.umbi tanggal=2027-08-08,2027-08-26,2027-08-01,2027-08-07,2027-08-15      |
| 23  | range     | ya    | 16900 | kategori=upacara tanggal=2027-09-20,2027-09-02,2027-09-07,2027-09-08,2027-09-23             |
| 24  | range     | ya    | 8147  | kategori=pembangunan tanggal=2027-10-01,2027-10-26,2027-10-31,2027-10-03,2027-10-13         |
| 25  | range     | ya    | 27302 | kategori=peternakan tanggal=2027-11-29,2027-11-16,2027-11-05,2027-11-23,2027-11-30          |
| 26  | ambiguous | ya    | 22438 | klarifikasi: aneka,peralatan                                                                |
| 27  | ambiguous | ya    | 13708 | klarifikasi: aneka,pembangunan,upacara                                                      |
| 28  | ambiguous | ya    | 6867  | klarifikasi: aneka,dagang.buka_usaha,pembangunan                                            |
| 29  | ambiguous | ya    | 11642 | klarifikasi: aneka,upacara,dagang.buka_usaha                                                |
| 30  | ambiguous | ya    | 25018 | klarifikasi: aneka,pembangunan,peralatan                                                    |
| 31  | ambiguous | tidak | 23013 | tidak bertanya balik: Menurut perhitungan wariga, terdapat beberapa hari baik untuk memulai |
| 32  | ambiguous | ya    | 10093 | klarifikasi: aneka,peralatan,upacara                                                        |
| 33  | ambiguous | ya    | 10929 | klarifikasi: aneka,dagang.buka_usaha,pembangunan                                            |
| 34  | specific  | ya    | 8426  | tanggal= Pada tanggal 15 Desember 2026, wukunya adalah Wariga dengan                        |
| 35  | specific  | ya    | 5676  | tanggal=2027-01-01 Menurut kalender Bali, tanggal 1 Januari 2027 jatuh pada har             |
| 36  | specific  | ya    | 14080 | tanggal=2027-03-10 Tanggal tersebut memiliki perpaduan dewasa baik dan kurang b             |
| 37  | specific  | ya    | 23871 | tanggal=2027-08-17 Pada 17 Agustus 2027, bertepatan dengan Purnama Karo, terdap             |
| 38  | specific  | ya    | 7686  | tanggal=2026-12-25 Menurut perhitungan wariga, tanggal 25 Desember 2026 jatuh p             |
| 39  | specific  | ya    | 13738 | tanggal=2026-11-20 Pada tanggal 20 November 2026 tidak terdapat rahinan khusus.             |
| 40  | specific  | ya    | 31994 | tanggal=2027-02-14 Pada tanggal 14 Februari 2027, terdapat perpaduan dewasa bai             |
| 41  | concept   | tidak | 3636  | error: quota                                                                                |
| 42  | concept   | tidak | 0     | error: quota                                                                                |
| 43  | concept   | tidak | 0     | error: quota                                                                                |
| 44  | offtopic  | tidak | 0     | error: quota                                                                                |
| 45  | offtopic  | tidak | 0     | error: quota                                                                                |
| 46  | offtopic  | tidak | 0     | error: quota                                                                                |
| 47  | offtopic  | tidak | 0     | error: quota                                                                                |
| 48  | offtopic  | tidak | 0     | error: quota                                                                                |
| 49  | offtopic  | tidak | 0     | error: quota                                                                                |
| 50  | offtopic  | tidak | 0     | error: quota                                                                                |
