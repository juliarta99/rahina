# DATABASE_SCHEMA.md — Rahina

PostgreSQL 16 + `pgvector` + `pg_trgm`. Zona waktu aplikasi `Asia/Makassar`; tanggal kalender disimpan sebagai `date` (tanggal sipil Masehi).

## 1. Gambaran Kelompok Tabel

| Kelompok | Tabel | Diisi oleh |
|---|---|---|
| Master wariga | `wuku`, `wewaran_values`, `sasih`, `rahinan` | Seeder (dari buku) |
| Kalender hasil hitung | `calendar_days`, `calendar_day_rahinan` | `wariga:import` (engine) |
| Aturan dewasa | `dewasa_rules`, `dewasa_rule_effects`, `dewasa_precedence`, `calendar_day_dewasa` | Seeder + `wariga:compute-dewasa` |
| Aktivitas | `activity_categories`, `activity_aliases` | Seeder + embedding |
| Pengetahuan & sumber | `sources`, `knowledge_notes` | Kurasi manual |
| Pengguna | `users`, `user_profiles`, `saved_plans`, `reminders` | Aplikasi |
| AI | `daily_briefs`, `ai_interactions` | Aplikasi |

## 2. ERD

```mermaid
erDiagram
  wuku ||--o{ calendar_days : "wuku_id"
  sasih ||--o{ calendar_days : "sasih_id"
  calendar_days ||--o{ calendar_day_dewasa : date
  dewasa_rules ||--o{ calendar_day_dewasa : rule_id
  dewasa_rules ||--o{ dewasa_rule_effects : rule_id
  activity_categories ||--o{ dewasa_rule_effects : category_id
  activity_categories ||--o{ activity_aliases : category_id
  activity_categories ||--o{ activity_categories : parent_id
  calendar_days ||--o{ calendar_day_rahinan : date
  rahinan ||--o{ calendar_day_rahinan : rahinan_id
  sources ||--o{ dewasa_rules : source_id
  sources ||--o{ knowledge_notes : source_id
  sources ||--o{ rahinan : source_id
  users ||--|| user_profiles : user_id
  users ||--o{ saved_plans : user_id
  users ||--o{ reminders : user_id
  calendar_days ||--o{ daily_briefs : date
```

## 3. Definisi Tabel

### 3.1 Master wariga

**`wuku`** (30 baris)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | smallint PK | 1–30 sesuai urutan |
| name | varchar(32) unique | |
| urip | smallint | |
| description | text null | ringkasan sendiri + sumber |
| source_id | bigint FK null | |

**`wewaran_values`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | smallint PK | |
| cycle | smallint | 1 = ekawara … 10 = dasawara |
| position | smallint | urutan dalam siklus |
| name | varchar(32) | |
| urip | smallint null | |
| UNIQUE(cycle, position) | | |

**`sasih`**: `id` smallint PK (1–12), `name`, `alt_name` null.

**`rahinan`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| slug | varchar unique | |
| name | varchar | |
| kind | enum(`pawukon`,`sasih`,`kajeng_kliwon`,`purnama_tilem`,`nasional`) | |
| description | text | |
| source_id | FK | |

### 3.2 Kalender hasil hitung

**`calendar_days`** (±5.800 baris untuk 2020–2035)
| Kolom | Tipe | Keterangan |
|---|---|---|
| date | date PK | |
| saka_year | smallint | |
| wuku_id | smallint FK | |
| ekawara_id … dasawara_id | smallint FK → `wewaran_values` | 10 kolom, nullable untuk ekawara |
| urip_total | smallint | saptawara + pancawara |
| sasih_id | smallint FK | |
| sasih_day | smallint | 1–15 |
| is_penanggal | boolean | false = panglong |
| is_purnama | boolean | |
| is_tilem | boolean | |
| is_nguna_ratri | boolean | |
| ingkel | varchar(16) null | |
| engine | varchar(32) | contoh `balinese-date-js@1.0.1` |
| computed_at | timestamptz | |

Index: `(wuku_id)`, `(sasih_id, sasih_day)`, `(is_purnama) WHERE is_purnama`, `(is_tilem) WHERE is_tilem`.

**`calendar_day_rahinan`**: `date` FK, `rahinan_id` FK, PK(date, rahinan_id).

### 3.3 Aturan dewasa

**`dewasa_rules`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| code | varchar(64) unique | slug, misal `sampi_gumarang_turun` |
| name | varchar(96) | |
| summary | text | **ditulis ulang sendiri**, 1–2 kalimat |
| verdict | enum(`ayu`,`ala`,`campur`) | sifat umum |
| alahing_level | smallint null | 1–5 (bobot / tingkat prioritas) |
| conditions | jsonb | lihat §4 |
| source_id | FK | wajib |
| source_page | varchar(32) null | |
| validated_by | varchar null | nama validator |
| validated_at | timestamptz null | hanya aturan tervalidasi yang tampil di produksi |
| is_active | boolean default true | |

Index: GIN `(conditions)`.

**`dewasa_rule_effects`**: aturan memengaruhi kategori aktivitas apa.
| Kolom | Tipe | Keterangan |
|---|---|---|
| rule_id | FK | |
| category_id | FK → `activity_categories` | |
| effect | enum(`baik`,`tidak_baik`) | |
| note | text null | |
| PK(rule_id, category_id, effect) | | |

**`dewasa_precedence`**: urutan prioritas antar-unsur, **hanya** dari sumber tertulis.
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | smallint PK | |
| layer | varchar(32) | contoh `wewaran`, `wuku`, `penanggal`, `sasih`, `dauh` |
| rank | smallint | makin besar makin kuat |
| source_id | FK | |
| source_page | varchar null | |

**`calendar_day_dewasa`** (hasil `wariga:compute-dewasa`)
| Kolom | Tipe |
|---|---|
| date | date FK |
| rule_id | bigint FK |
| matched_on | jsonb (unsur yang membuat aturan cocok, untuk penjelasan) |
| PK(date, rule_id) | |

### 3.4 Aktivitas

**`activity_categories`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| parent_id | FK null | hierarki: domain → sub-kategori |
| code | varchar unique | `pertanian.padi`, `pertanian.umbi`, `dagang.buka_usaha` |
| domain | enum(`upacara`,`pertanian`,`peternakan`,`peralatan`,`pembangunan`,`aneka`) | 6 domain umum kalender Bali |
| name | varchar | |
| icon | varchar | nama ikon Phosphor |
| requires_cultural_notice | boolean | true untuk upacara besar |

**`activity_aliases`**: bahan pemetaan bahasa bebas.
| Kolom | Tipe |
|---|---|
| id | bigint PK |
| category_id | FK |
| phrase | varchar (misal "nanam singkong", "tanam ubi") |
| embedding | vector(768) |

Index: GIN `(phrase gin_trgm_ops)`, HNSW `(embedding vector_cosine_ops)`.

### 3.5 Sumber & pengetahuan

**`sources`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| kind | enum(`book`,`lontar`,`journal`,`interview`,`software`) | |
| title, author, year, publisher | | |
| url | text null | |
| license | varchar | `copyright`, `CC0`, `CC-BY-4.0`, `Apache-2.0`, `izin-narasumber` |
| notes | text null | |

**`knowledge_notes`**: bahan RAG untuk penjelasan konsep (bukan untuk tanggal).
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| topic | varchar | `wewaran`, `pawukon`, `alahing_dewasa`, nama dewasa, dll. |
| content | text | **catatan ringkasan sendiri** (maks ±120 kata), bukan salinan buku |
| source_id | FK | |
| source_page | varchar null | |
| embedding | vector(768) | |

### 3.6 Pengguna

**`user_profiles`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| user_id | FK PK | |
| persona | enum(`umum`,`petani`,`pedagang`,`pemangku`) | |
| birth_date | date null | opsional, untuk otonan; **tidak dikirim ke LLM** |
| otonan_wuku_id, otonan_saptawara_id, otonan_pancawara_id | smallint null | diturunkan dari birth_date |
| notify_daily | boolean | |

**`saved_plans`**: `id`, `user_id`, `category_id`, `chosen_date`, `note`, timestamps.
**`reminders`**: `id`, `user_id`, `kind` (`otonan`,`rahinan`,`plan`), `ref_id`, `remind_on`, `sent_at` null.

### 3.6b WhatsApp (lihat `WHATSAPP.md`)

**`whatsapp_subscriptions`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK null | boleh tanpa akun web |
| phone_encrypted | text | nomor E.164, terenkripsi |
| phone_hash | char(64) unique | HMAC untuk pencarian |
| persona | enum | |
| birth_date_encrypted | text null | opsional |
| otonan_wuku_id, otonan_saptawara_id, otonan_pancawara_id | smallint null | |
| frequency | enum(`daily`,`important_only`) | |
| status | enum(`pending`,`active`,`paused`,`opted_out`,`failed`) | |
| consent_daily_at | timestamptz | bukti opt-in |
| consent_birthdate_at | timestamptz null | |
| verified_at | timestamptz null | |
| opted_out_at | timestamptz null | |
| last_inbound_at | timestamptz null | |
| consecutive_failures | smallint default 0 | |

**`whatsapp_link_codes`**: `code` char(6) PK, `pending_payload` jsonb (persona, consent), `expires_at`, `used_at`.

**`whatsapp_messages`**
| Kolom | Tipe |
|---|---|
| id | bigint PK |
| subscription_id | FK |
| direction | enum(`in`,`out`) |
| provider | varchar (`fonnte`, `cloud_api`) |
| provider_message_id | varchar null |
| inbound_hash | char(64) unique null (idempotensi webhook) |
| kind | enum(`morning_brief`,`answer`,`system`,`inbound`) |
| status | enum(`queued`,`sent`,`delivered`,`read`,`failed`) |
| error_code | varchar null |
| brief_id | FK → `daily_briefs` null |
| created_at, updated_at | timestamptz |

Isi teks pesan masuk **tidak** disimpan (hanya hash), sama seperti `ai_interactions`.

### 3.7 AI

**`daily_briefs`** (cache ringkasan; 4 persona × 1 hari)
| Kolom | Tipe |
|---|---|
| id | bigint PK |
| date | date FK |
| persona | enum |
| content | jsonb (lihat `AI_IMPLEMENTATION.md` §3) |
| rule_ids | bigint[] (aturan yang dirujuk) |
| model | varchar |
| prompt_version | varchar |
| created_at | timestamptz |
| UNIQUE(date, persona, prompt_version) | |

**`ai_interactions`** (log tanpa data pribadi)
| Kolom | Tipe |
|---|---|
| id | bigint PK |
| feature | enum(`brief`,`ask`,`map_activity`,`conflict`) |
| input_hash | char(64) |
| tool_calls | jsonb (nama tool + argumen) |
| returned_dates | date[] |
| dropped_dates | date[] (dibuang guard) |
| model, prompt_version | varchar |
| latency_ms | int |
| status | enum(`ok`,`fallback`,`error`,`refused`) |
| error | text null |
| created_at | timestamptz |

Teks pertanyaan mentah **tidak** disimpan kecuali user mengaktifkan "bantu tingkatkan Rahina".

## 4. Format `dewasa_rules.conditions`

Kondisi dicocokkan dengan kolom `calendar_days`. Struktur logika: `all` (AND) / `any` (OR) / `not`.

```json
{
  "all": [
    { "field": "saptawara", "in": ["<NAMA>"] },
    { "field": "pancawara", "in": ["<NAMA>"] },
    { "any": [
      { "field": "wuku", "in": ["<NAMA>", "<NAMA>"] },
      { "field": "sasih_day", "between": [1, 5], "with": { "is_penanggal": true } }
    ]}
  ]
}
```

> **Ini contoh FORMAT, bukan aturan asli.** Nilai `<NAMA>` diisi dari buku sumber dan wajib divalidasi (`validated_at`).

Field yang didukung: `wuku`, `ekawara`…`dasawara`, `urip_total`, `sasih`, `sasih_day`, `is_penanggal`, `is_purnama`, `is_tilem`, `ingkel`.
Operator: `in`, `not_in`, `eq`, `between`, `gte`, `lte`.

`DewasaEngine` mengevaluasi seluruh aturan aktif per tanggal → menulis `calendar_day_dewasa` + `matched_on`. Proses ini deterministik, bisa diuji, dan bisa dijelaskan ("muncul karena Saptawara = X dan Wuku = Y").

## 5. Query Penting

**Hari baik untuk kategori dalam rentang tanggal** (dipakai tool `find_good_days`):
```sql
WITH scored AS (
  SELECT d.date,
         SUM(CASE WHEN e.effect = 'baik' THEN COALESCE(r.alahing_level,1) ELSE 0 END) AS pos,
         SUM(CASE WHEN e.effect = 'tidak_baik' THEN COALESCE(r.alahing_level,1) ELSE 0 END) AS neg,
         ARRAY_AGG(r.id) FILTER (WHERE e.effect='baik') AS support_ids,
         ARRAY_AGG(r.id) FILTER (WHERE e.effect='tidak_baik') AS block_ids
  FROM calendar_days d
  JOIN calendar_day_dewasa cd ON cd.date = d.date
  JOIN dewasa_rules r ON r.id = cd.rule_id AND r.is_active AND r.validated_at IS NOT NULL
  JOIN dewasa_rule_effects e ON e.rule_id = r.id AND e.category_id = ANY(:category_ids)
  WHERE d.date BETWEEN :start AND :end
  GROUP BY d.date
)
SELECT * FROM scored WHERE pos > 0 ORDER BY (pos - neg) DESC, date ASC LIMIT :limit;
```
Skor di atas hanya skor awal. Keputusan akhir untuk hari yang konflik ditentukan `ConflictResolver` berdasarkan `dewasa_precedence`.

**Pemetaan aktivitas (hybrid):**
```sql
SELECT category_id, MAX(score) AS score FROM (
  SELECT category_id, similarity(phrase, :q) AS score FROM activity_aliases WHERE phrase % :q
  UNION ALL
  SELECT category_id, 1 - (embedding <=> :q_vec) AS score FROM activity_aliases
  ORDER BY embedding <=> :q_vec LIMIT 10
) s GROUP BY category_id ORDER BY score DESC LIMIT 3;
```

## 6. Migrasi (urutan)

1. `enable_extensions` (vector, pg_trgm)
2. `create_sources_table`
3. `create_wuku_wewaran_sasih_tables`
4. `create_calendar_days_table`
5. `create_rahinan_tables`
6. `create_activity_tables`
7. `create_dewasa_tables`
8. `create_knowledge_notes_table`
9. `create_user_profiles_plans_reminders`
10. `create_ai_tables`
11. `create_whatsapp_tables`
