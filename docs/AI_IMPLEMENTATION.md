# AI_IMPLEMENTATION.md — Rahina

Dokumen ini menjelaskan **di mana AI dipakai, kenapa diperlukan, input-output, dan pengamannya**. Isinya juga menjadi bahan bagian *AI Implementation* di proposal dan jawaban saat verifikasi teknis.

## 1. Ringkasan

| # | Fitur | Teknik AI | Input → Output | Kenapa perlu AI |
|---|---|---|---|---|
| A | Rahina Ini | LLM summarization (structured output) | data dewasa hari itu + persona → 3–4 poin JSON | Data mentah 10–20 istilah, saling bertentangan, berbahasa teknis |
| B | Pemetaan aktivitas | Embedding + pencarian hybrid; LLM sebagai fallback klasifikasi | "nanam singkong" → `pertanian.umbi` | Kategori wariga spesifik; user memakai bahasa sehari-hari |
| C | Tanya Wariga | LLM agent + function calling | pertanyaan bebas → tanggal + alasan + sumber | Pertanyaan alami harus diubah menjadi kueri terstruktur multi-langkah |
| D | Penjelas konflik | LLM + RAG (`knowledge_notes`) | daftar dewasa pendukung/penghalang + aturan prioritas → penjelasan | Butuh penalaran dan bahasa awam; aturan prioritas dari buku |

**Prinsip:** LLM = penerjemah & penjelas. **Perhitungan = kode deterministik.**

## 2. Model

- Teks & agent: Gemini Flash (nama di `GEMINI_TEXT_MODEL`), `temperature` 0.2, `responseMimeType: application/json` + `responseSchema`.
- Embedding: Gemini embedding (`GEMINI_EMBED_MODEL`), `outputDimensionality: 768`.
- Alasan memilih: dukungan bahasa Indonesia baik, function calling native, ada free tier untuk masa penjurian, latensi rendah.

## 3. Fitur A — Rahina Ini

**Alur**
```
Scheduler 00:05 WITA → BuildDailyBriefAction(date, persona) ×4
  → CalendarRepository::day(date)       (wuku, wewaran, sasih, rahinan, dewasa + effects)
  → filter dewasa relevan untuk persona  (petani: pertanian & peternakan; pedagang: aneka/dagang; ...)
  → prompt daily_brief.v1 + data JSON
  → Gemini → OutputGuard → simpan daily_briefs
```

**Schema output**
```json
{
  "headline": "string ≤ 90 karakter",
  "points": [
    { "verdict": "ayu|ala|campur|info", "text": "string ≤ 140 karakter", "rule_ids": [12, 31] }
  ],
  "rahinan_note": "string|null"
}
```

**Guard**
- `points` 2–4 item; setiap `rule_ids` ⊆ id dewasa yang dikirim; `verdict` konsisten dengan `effect` aturan yang dirujuk.
- Gagal validasi → retry 1× → fallback **template non-AI** ("Hari ini terdapat 3 dewasa baik untuk pertanian: …").

## 4. Fitur B — Pemetaan Aktivitas

1. Normalisasi (lowercase, hapus kata umum "hari baik", "untuk").
2. Pencarian hybrid `activity_aliases` (lihat `DATABASE_SCHEMA.md` §5).
3. Skor tertinggi ≥ 0.80 → langsung dipakai. Skor 0.55–0.80 atau selisih top-2 < 0.05 → **tanya balik** dengan pilihan kategori. Skor < 0.55 → LLM klasifikasi ke daftar `code` yang tersedia (enum di schema), lalu tetap tanya balik bila `confidence` < 0.7.
4. Frasa baru yang dikonfirmasi user disimpan sebagai kandidat alias (perlu disetujui admin).

## 5. Fitur C — Tanya Wariga (agent)

### 5.1 Tools (function declarations)
| Tool | Argumen | Mengembalikan |
|---|---|---|
| `map_activity` | `text: string` | `[{code, name, score}]` |
| `find_good_days` | `category_codes: string[]`, `start: date`, `end: date`, `limit: int ≤ 10` | `[{date, wewaran, wuku, sasih, score, support:[rule], block:[rule]}]` |
| `get_day` | `date: date` | detail hari lengkap |
| `explain_dewasa` | `code: string` | `{name, summary, effects, source}` |
| `explain_concept` | `topic: string` | potongan `knowledge_notes` + sumber |

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
  "dates": [{ "date": "YYYY-MM-DD", "reason": "string", "support_rule_ids": [1], "block_rule_ids": [] }],
  "needs_clarification": false,
  "clarification_options": [],
  "cultural_notice": false
}
```

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

Input: tanggal + kategori → `support[]`, `block[]`, `dewasa_precedence`, `knowledge_notes` relevan (top-3 via vector).
Output: `{ "conclusion": "ayu|ala|campur", "explanation": "≤ 3 kalimat", "precedence_source_id": 4 }`.
Jika `dewasa_precedence` belum terisi dari sumber tervalidasi → `conclusion = "campur"` + kalimat tetap "Perhitungan menunjukkan pertimbangan yang berbeda; konsultasikan dengan pemangku".

## 8. Penanganan Error

| Kejadian | Perilaku |
|---|---|
| Gemini 429/5xx | Retry 2× (500ms, 1500ms), lalu fallback template |
| Timeout > 15s | Fallback template + data mentah tetap tampil |
| JSON tidak valid | Retry 1× dengan pesan koreksi, lalu fallback |
| Tool melempar error | Dikirim ke LLM sebagai `function_response.error` agar dijelaskan |
| Rate limit user | 20 req/menit, pesan ramah |

Semua halaman tetap berguna **tanpa AI** (kalender + daftar dewasa mentah). AI adalah lapisan pemahaman.

## 9. Privasi

- Data yang dikirim ke Gemini: pertanyaan, persona, dan data kalender publik.
- **Tidak dikirim**: nama, email, tanggal lahir, lokasi.
- Log menyimpan hash input; teks mentah hanya jika user setuju.

## 10. Evaluasi (bahan untuk proposal & juri)

`tests/Eval/questions.jsonl`, 50 pertanyaan dalam 5 tipe: rentang bulan, aktivitas ambigu, tanggal spesifik, konsep, dan di luar topik.

| Metrik | Target |
|---|---|
| Akurasi tanggal (semua tanggal di jawaban ada di hasil tool & benar) | 100% |
| Akurasi pemetaan kategori | ≥ 90% |
| Tingkat klarifikasi yang tepat (ambigu → bertanya) | ≥ 85% |
| Penolakan topik di luar cakupan | 100% |
| Latensi p50 / p95 | < 4s / < 9s |

Hasil eval disimpan di `docs/EVAL_RESULTS.md` setiap tag versi.

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
