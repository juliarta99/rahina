# DEMO_SCRIPT.md — Rahina

Video maksimal 5 menit, 16:9, 1080p, ≥50% demo produk, **clean start** (buka app → input → AI memproses → output).

| Waktu     | Bagian          | Isi                                                                                                                                                            | Layar                                        |
| --------- | --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------- |
| 0:00–0:30 | Problem         | Kalender Bali harian berisi belasan istilah dewasa yang saling bertentangan. Contoh nyata: satu hari "baik menanam padi" sekaligus "tidak baik bercocok tanam" | Potongan daftar dewasa mentah (data sendiri) |
| 0:30–1:00 | Solution        | Rahina mengubah perhitungan wariga menjadi keputusan yang bisa dipahami, lengkap dengan alasan dan sumber                                                      | Halaman Rahina Ini                           |
| 1:00–1:45 | Demo 1          | Buka app → pilih persona _Petani_ → ringkasan berubah → tap baris sumber → daftar dewasa mentah + buku                                                         |                                              |
| 1:45–2:45 | Demo 2          | Tanya Wariga: "kapan hari baik menanam cabai bulan Desember?" → status langkah agent → 3–5 `DateRefCard` → tap satu → detail hari                              |                                              |
| 2:45–3:15 | Demo 3          | Pertanyaan ambigu: "hari baik untuk mulai usaha?" → agent bertanya balik (warung / bangun tempat / buka rekening)                                              |                                              |
| 3:15–3:30 | Demo 4          | Penjelas konflik pada tanggal yang konflik + `CulturalNotice`                                                                                                  |                                              |
| 3:30–4:30 | AI & Arsitektur | Diagram: engine deterministik → DB → agent function calling → OutputGuard. Tunjukkan potongan `OutputGuard.php` dan hasil eval (akurasi tanggal 100%)          | Diagram + kode                               |
| 4:30–5:00 | Impact & Future | Manfaat bagi petani/pedagang/generasi muda, validasi pakar, rencana notifikasi & mode subak                                                                    |                                              |

## Persiapan Verifikasi Teknis

- [ ] Tunjukkan `app/AI/GeminiClient.php` (AI call) dan `app/AI/AgentRunner.php`
- [ ] Jalankan pertanyaan baru yang diberikan juri
- [ ] Simulasikan API gagal (`GEMINI_API_KEY` salah di env staging) → fallback tampil
- [ ] Jelaskan data yang dikirim ke Gemini (`AI_IMPLEMENTATION.md` §9)
- [ ] Tunjukkan bagian pre-existing vs baru (`AI_DISCLOSURE.md`)
- [ ] Semua anggota bisa menjelaskan alur agent & guard
