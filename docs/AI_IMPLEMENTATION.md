# AI_IMPLEMENTATION.md — Rahina

Dokumen ini menjelaskan **di mana AI dipakai, kenapa diperlukan, input-output, dan pengamannya**. Isinya juga menjadi bahan bagian _AI Implementation_ di proposal dan jawaban saat verifikasi teknis.

## 1. Ringkasan

| #   | Fitur              | Teknik AI                                                                      | Input → Output                                                     | Kenapa perlu AI                                                       |
| --- | ------------------ | ------------------------------------------------------------------------------ | ------------------------------------------------------------------ | --------------------------------------------------------------------- |
| A   | Rahina Ini         | LLM summarization (structured output)                                          | data dewasa hari itu (semua domain) → 3–4 poin JSON                | Data mentah 10–20 istilah, saling bertentangan, berbahasa teknis      |
| B   | Pemetaan aktivitas | Pencarian trigram (PHP; pg_trgm bila Postgres) + klasifikasi LLM (output enum) | "nanam singkong" → `pertanian.umbi`                                | Kategori wariga spesifik; user memakai bahasa sehari-hari             |
| C   | Tanya Wariga       | LLM agent + function calling                                                   | pertanyaan bebas → tanggal + alasan + sumber                       | Pertanyaan alami harus diubah menjadi kueri terstruktur multi-langkah |
| D   | Penjelas konflik   | LLM + retrieval full-text (`knowledge_notes`)                                  | daftar dewasa pendukung/penghalang + aturan prioritas → penjelasan | Butuh penalaran dan bahasa awam; aturan prioritas dari buku           |

**Prinsip:** LLM = penerjemah & penjelas. **Perhitungan = kode deterministik.**

## 2. Model

- **Satu model untuk semua fitur: `gemini-3.1-flash-lite`** (dari `GEMINI_MODEL`). `temperature` 0.2, `responseMimeType: application/json` + schema.
- Model ini mendukung function calling dan structured output JSON, cocok untuk tugas klasifikasi, ekstraksi, dan agent ringan.
- **Tanpa model embedding.** Pencarian alias aktivitas & catatan pengetahuan memakai `pg_trgm` + full-text search Postgres. Korpusnya kecil (ratusan–ribuan baris), sehingga pencarian leksikal cukup, dan kekurangannya ditutup oleh klasifikasi LLM.
- Alasan memilih: latensi rendah, biaya rendah, satu API key, function calling native.
- Karena model "lite", tugas dibuat **sempit dan terstruktur**: prompt pendek, schema ketat, maksimal 4 langkah agent, dan semua perhitungan tetap di kode.

## 3. Fitur A — Rahina Ini

**Alur**

```
Scheduler 00:05 WITA → BuildDailyBriefAction(date) — satu ringkasan umum
  → CalendarRepository::day(date)       (wuku, wewaran, sasih, rahinan, dewasa + effects)
  → semua dewasa yang boleh tampil beserta efek per domain (persona dihapus 2026-11-06)
  → prompt daily_brief.v1 + data JSON
  → Gemini → OutputGuard → simpan daily_briefs
```

**Schema output**

```json
{
    "headline": "string ≤ 90 karakter",
    "points": [
        {
            "verdict": "ayu|ala|campur|info",
            "text": "string ≤ 140 karakter",
            "rule_ids": [12, 31]
        }
    ],
    "rahinan_note": "string|null"
}
```

**Guard**

- `points` 2–4 item; setiap `rule_ids` ⊆ id dewasa yang dikirim; `verdict` konsisten dengan `effect` aturan yang dirujuk.
- Gagal validasi → retry 1× → fallback **template non-AI** ("Hari ini terdapat 3 dewasa baik untuk pertanian: …").

## 4. Fitur B — Pemetaan Aktivitas

1. Normalisasi (lowercase, hapus kata umum "hari baik", "untuk", "kapan").
2. Pencarian trigram `activity_aliases` (lihat `DATABASE_SCHEMA.md` §5).
3. Skor tertinggi ≥ 0.60 dan selisih dengan peringkat 2 ≥ 0.10 → langsung dipakai.
4. Selain itu → **LLM klasifikasi**: kirim teks + daftar `code` + nama kategori (enum di schema). Output `{code, confidence}`.
5. `confidence` < 0.7 atau kategori upacara besar yang ambigu → **tanya balik** dengan 2–3 pilihan kategori.
6. Frasa baru yang dikonfirmasi user disimpan sebagai kandidat alias (perlu disetujui admin).

## 5. Fitur C — Tanya Wariga (agent)

### 5.1 Tools (function declarations)

| Tool              | Argumen                                                                   | Mengembalikan                                                         |
| ----------------- | ------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| `map_activity`    | `text: string`                                                            | `[{code, name, score}]`                                               |
| `find_good_days`  | `category_codes: string[]`, `start: date`, `end: date`, `limit: int ≤ 10` | `[{date, wewaran, wuku, sasih, score, support:[rule], block:[rule]}]` |
| `get_day`         | `date: date`                                                              | detail hari lengkap                                                   |
| `explain_dewasa`  | `code: string`                                                            | `{name, summary, effects, source}`                                    |
| `explain_concept` | `topic: string`                                                           | potongan `knowledge_notes` + sumber                                   |

Rentang tanggal maksimum 92 hari dan hanya dalam 2020–2035. Di luar itu tool mengembalikan error yang dijelaskan agent.

### 5.2 Loop

```
system: agent_system.v1  (tanggal hari ini, zona WITA, aturan)
user: pertanyaan
loop (maks RAHINA_AGENT_MAX_STEPS = 4):
  Gemini → function_call? → jalankan tool (PHP) → kirim function_response
  Gemini → jawaban final (JSON)
OutputGuard → render
```

### 5.3 Schema jawaban final

```json
{
    "answer": "string, ≤ 3 kalimat",
    "dates": [
        {
            "date": "YYYY-MM-DD",
            "reason": "string",
            "support_rule_ids": [1],
            "block_rule_ids": []
        }
    ],
    "needs_clarification": false,
    "clarification_options": [],
    "cultural_notice": false
}
```

### 5.3b Implementasi (M4, 2026-11-06)

| Bagian                | File                                                                                                                                              |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Loop function calling | `app/AI/AgentRunner.php` (maks 4 langkah; langkah ke-5 memakai `functionCallingConfig.mode = NONE` agar model wajib menjawab dari hasil yang ada) |
| Tool                  | `app/AI/Tools/{MapActivity,FindGoodDays,GetDay,ExplainDewasa}Tool.php` → domain `app/Wariga/{ActivityMatcher,DateRanker,CalendarRepository}.php`  |
| Bukti sesi            | `app/AI/AgentContext.php` (tanggal & aturan yang benar-benar dikembalikan tool)                                                                   |
| Guard                 | `OutputGuard::answer()`                                                                                                                           |
| Fallback tanpa LLM    | `app/AI/AnswerTemplate.php`                                                                                                                       |
| Orkestrasi + log      | `app/Actions/AskWarigaAction.php`, endpoint `POST /tanya` (`AskController`, batas 20/menit)                                                       |
| UI                    | `resources/js/pages/ask.tsx`, `components/rahina/date-ref-card.tsx`                                                                               |

Catatan:

- Schema final menambah `out_of_scope: boolean` untuk penolakan topik (status log `refused`).
- `explain_concept` **belum** diaktifkan: tabel `knowledge_notes` butuh catatan kurasi bersumber (tidak boleh dikarang, CLAUDE.md §8).
- `thinkingConfig.thinkingLevel = minimal` (`RAHINA_AGENT_THINKING`): diukur 2026-11-06, ±1,7 s per langkah vs ±10,7 s default, dengan tool call yang sama.
- Panggilan tool identik dalam satu sesi tidak dijalankan ulang.
- Klarifikasi kategori **ditegakkan di kode**: bila `ActivityMatcher` ragu (skor < 0,60 atau selisih dengan pesaing dari keluarga lain < 0,10) dan pengguna belum memilih kategori, `find_good_days` menolak dengan error dan `OutputGuard` memaksa `needs_clarification`. Bila model melewati `map_activity`, `find_good_days` mencocokkan sendiri pertanyaan pengguna. Induk & anak kategori yang seri (mis. `pertanian` vs `pertanian.padi`) dianggap yakin, dan yang lebih spesifik dipilih.
- Kuota Free Tier `gemini-3.1-flash-lite`: **500 request/hari** (teramati 2026-11-06). Satu pertanyaan Tanya Wariga ≈ 2–4 request, sehingga ±150 pertanyaan/hari dikurangi ringkasan harian. Untuk demo & penjurian disarankan mengaktifkan billing (Paid Tier).
- Data tampilan kartu tanggal (wewaran, wuku, dewasa pendukung/penghalang, verdict) diambil dari hasil tool di server, bukan dari teks model; `rule_ids` dari model hanya dipakai sebagai petunjuk.

### 5.4 Aturan system prompt (ringkas)

- Jangan pernah menyebut tanggal yang tidak dikembalikan tool.
- Jika aktivitas ambigu → `needs_clarification = true`.
- Jika kategori termasuk upacara besar → `cultural_notice = true`.
- Pertanyaan di luar wariga/kalender Bali → tolak sopan dan arahkan.
- Bahasa Indonesia, istilah wariga tetap dipakai.

## 6. OutputGuard (dijalankan untuk semua fitur)

1. Validasi JSON terhadap schema.
2. **Validasi tanggal**: setiap `dates[].date` harus ada di gabungan hasil `find_good_days`/`get_day` pada sesi ini. Yang tidak ada → **dibuang**, lalu dicatat di `ai_interactions.dropped_dates`.
3. **Validasi aturan**: setiap `*_rule_ids` harus termasuk aturan yang dikembalikan tool untuk tanggal tersebut.
4. Jika setelah penyaringan `dates` kosong padahal tool mengembalikan hasil → bangun jawaban **template** dari hasil tool (tanpa LLM).
5. Sanitasi teks (strip HTML/markdown berbahaya).

Kode guard harus mudah ditunjukkan ke juri: `app/AI/OutputGuard.php`.

## 7. Fitur D — Penjelas Konflik

Input: tanggal + kategori → `support[]`, `block[]`, `dewasa_precedence`, `knowledge_notes` relevan (top-3 via full-text / topik).
Output: `{ "conclusion": "ayu|ala|campur", "explanation": "≤ 3 kalimat", "precedence_source_id": 4 }`.
Jika `dewasa_precedence` belum terisi dari sumber tervalidasi → `conclusion = "campur"` + kalimat tetap "Perhitungan menunjukkan pertimbangan yang berbeda; konsultasikan dengan pemangku".

## 8. Penanganan Error

| Kejadian                                  | Perilaku                                                                                                                                                                                        |
| ----------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Gemini 429/5xx                            | Retry 2× (500ms, 1500ms), lalu fallback template                                                                                                                                                |
| Timeout > 15s                             | Fallback template + data mentah tetap tampil                                                                                                                                                    |
| JSON tidak valid                          | Retry 1× dengan pesan koreksi, lalu fallback                                                                                                                                                    |
| Tool melempar error                       | Dikirim ke LLM sebagai `function_response.error` agar dijelaskan                                                                                                                                |
| Rate limit user                           | 20 req/menit, pesan ramah                                                                                                                                                                       |
| Kuota harian Gemini habis (429 `per day`) | Tidak di-retry; circuit breaker menyimpan waktu reset dari `RetryInfo` di cache, panggilan berikutnya langsung fallback dengan pesan "batas pemakaian harian" (`GeminiClient::QUOTA_CACHE_KEY`) |
| Gemini gagal setelah tool berjalan        | Hasil tool di `AgentContext` tetap dipakai untuk jawaban template (tanggal tetap tampil)                                                                                                        |

Semua halaman tetap berguna **tanpa AI** (kalender + daftar dewasa mentah). AI adalah lapisan pemahaman.

## 9. Privasi

- Data yang dikirim ke Gemini: pertanyaan dan data kalender publik.
- **Tidak dikirim**: nama, email, tanggal lahir, lokasi.
- Log menyimpan hash input; teks mentah hanya jika user setuju.

## 10. Evaluasi (bahan untuk proposal & juri)

`tests/Eval/questions.jsonl`, 50 pertanyaan dalam 5 tipe: rentang bulan, aktivitas ambigu, tanggal spesifik, konsep, dan di luar topik.

| Metrik                                                               | Target      |
| -------------------------------------------------------------------- | ----------- |
| Akurasi tanggal (semua tanggal di jawaban ada di hasil tool & benar) | 100%        |
| Akurasi pemetaan kategori                                            | ≥ 90%       |
| Tingkat klarifikasi yang tepat (ambigu → bertanya)                   | ≥ 85%       |
| Penolakan topik di luar cakupan                                      | 100%        |
| Latensi p50 / p95                                                    | < 4s / < 9s |

Hasil eval disimpan di `docs/EVAL_RESULTS.md` setiap tag versi.

Cara menjalankan (memanggil Gemini sungguhan ke database dev, **tidak** jalan di CI):

```bash
php artisan wariga:eval --sleep=1            # semua 50 pertanyaan → docs/EVAL_RESULTS.md
php artisan wariga:eval --only=1,26,44       # sebagian
```

Akurasi tanggal diukur dengan menghitung ulang `DateRanker` memakai argumen tool yang sama, ditambah cek bahwa tanggal ada di rentang yang diminta pertanyaan.

## 11. Prompt

Disimpan di `prompts/*.md` dengan header:

```
---
id: agent_system
version: 1
updated: 2026-11-xx
---
```

Mengubah prompt = menaikkan versi + commit tersendiri + menjalankan ulang eval.
