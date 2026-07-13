# Database Schema — UPY Language Test

**Platform ujian bahasa multi-language** (TOEFL iBT, JLPT, HSK, dll.)  
**Engine:** MySQL 8.0+ / InnoDB  
**Charset:** utf8mb4_unicode_ci  
**Prefix:** _(none)_

---

## Entity Relationship Diagram

```
exam_types ──┬── 1:N ── exams ──────────── 1:N ── exam_sections
             │                                      │
             ├── 1:N ── scoring_rules               ├── 1:N ── question_groups
             │                                      │           │
             ├── 1:N ── test_forms                  │           ├── N:1 ── passages
             │    │                                  │           └── 1:N ── questions
             │    └── M:N ── test_form_questions ───────────────┤
             │                                                   │
             └── (max_strikes config)                             │
                                                                 │
passages ──── 1:N ── question_groups                             │
                                                                 │
users ──┬── 1:1 ── student_profiles                              │
        │                                                         │
        ├── M:N ── roles ── M:N ── permissions                    │
        │                                                         │
        └── 1:N ── exam_sessions ──┬── 1:N ── answers ────── N:1 ── questions
                                   ├── 1:N ── flagged_questions ─ N:1 ── questions
                                   ├── 1:N ── violation_logs
                                   └── N:1 ── exam_schedules ──── N:1 ── exams

exam_sections ── M:N ── exam_section_question ── M:N ── questions
tags ── M:N ── question_tag ── M:N ── questions
```

---

## Tabel

### 1. users

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(255) | NOT NULL | |
| email | VARCHAR(255) | NOT NULL, UNIQUE | |
| email_verified_at | TIMESTAMP | NULL | |
| password | VARCHAR(255) | NOT NULL | |
| phone | VARCHAR(20) | NULL | |
| photo | VARCHAR(255) | NULL | |
| google_id | VARCHAR(255) | NULL | |
| google_avatar | VARCHAR(255) | NULL | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| remember_token | VARCHAR(100) | NULL | |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

### 2. student_profiles

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | UNIQUE, FK → users CASCADE | 1 user = 1 profile |
| nim | VARCHAR(20) | NULL | Nomor Induk Mahasiswa |
| faculty | VARCHAR(100) | NULL | Fakultas |
| department | VARCHAR(100) | NULL | Program Studi |
| batch_year | INT | NULL | Tahun angkatan |
| identity_photo | VARCHAR(255) | NULL | Foto KTP/KTM |
| is_verified | TINYINT(1) | NOT NULL, DEFAULT 0 | Status verifikasi identitas |
| verified_at | TIMESTAMP | NULL | |
| verified_by | BIGINT UNSIGNED | NULL, FK → users SET NULL | Admin yang verifikasi |
| verification_note | TEXT | NULL | Catatan verifikasi |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

### 3. permissions _(Spatie)_

| Column | Type | Constraint |
|--------|------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| name | VARCHAR(255) | NOT NULL |
| guard_name | VARCHAR(25) | NOT NULL, DEFAULT 'web' |

UNIQUE: `(name, guard_name)`

### 4. roles _(Spatie)_

| Column | Type | Constraint |
|--------|------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| name | VARCHAR(255) | NOT NULL |
| guard_name | VARCHAR(25) | NOT NULL, DEFAULT 'web' |

UNIQUE: `(name, guard_name)`

**Predefined roles:** `superadmin`, `admin`, `proctor`, `instructor`, `student`

### 5. model_has_permissions _(Spatie pivot)_

| Column | Type | Constraint |
|--------|------|------------|
| permission_id | BIGINT UNSIGNED | PK, FK → permissions CASCADE |
| model_type | VARCHAR(255) | PK |
| model_id | BIGINT UNSIGNED | PK |

### 6. model_has_roles _(Spatie pivot)_

| Column | Type | Constraint |
|--------|------|------------|
| role_id | BIGINT UNSIGNED | PK, FK → roles CASCADE |
| model_type | VARCHAR(255) | PK |
| model_id | BIGINT UNSIGNED | PK |

### 7. role_has_permissions _(Spatie pivot)_

| Column | Type | Constraint |
|--------|------|------------|
| permission_id | BIGINT UNSIGNED | PK, FK → permissions CASCADE |
| role_id | BIGINT UNSIGNED | PK, FK → roles CASCADE |

### 8. exam_types

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(100) | NOT NULL | "TOEFL iBT", "JLPT N3" |
| target_language | VARCHAR(50) | NOT NULL | "English", "Japanese" |
| section_config | JSON | NOT NULL | Definisi section bawaan |
| max_strikes | INT | NOT NULL, DEFAULT 3 | Batas strike sebelum terminate |
| description | TEXT | NULL | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

**Contoh `section_config`:**
```json
[
  { "skill": "reading",   "name": "Reading Section",   "default_duration": 35, "default_questions": 20 },
  { "skill": "listening", "name": "Listening Section",  "default_duration": 36, "default_questions": 28 },
  { "skill": "speaking",  "name": "Speaking Section",   "default_duration": 16, "default_questions": 4  },
  { "skill": "writing",   "name": "Writing Section",    "default_duration": 29, "default_questions": 2  }
]
```

### 9. passages

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| title | VARCHAR(255) | NOT NULL | |
| content_text | LONGTEXT | NULL | Isi bacaan reading |
| audio_url | VARCHAR(255) | NULL | Path audio listening |
| image_url | VARCHAR(255) | NULL | |
| language | VARCHAR(50) | NOT NULL | "en", "ja", "zh" |
| word_count | INT | NULL | |
| source | VARCHAR(255) | NULL | Sumber bacaan |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

### 10. exams

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_type_id | BIGINT UNSIGNED | NULL, FK → exam_types SET NULL | Tipe ujian |
| title | VARCHAR(255) | NOT NULL | |
| description | TEXT | NULL | |
| mode | ENUM('tryout','official') | NOT NULL, DEFAULT 'tryout' | Tryout / ujian resmi |
| duration_minutes | INT | NOT NULL, DEFAULT 160 | |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

### 11. exam_sections

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_id | BIGINT UNSIGNED | NOT NULL, FK → exams CASCADE | |
| skill | VARCHAR(50) | NOT NULL | "reading","listening","grammar","vocabulary","writing","speaking" |
| type | ENUM('reading','listening','speaking','writing') | NULL | **DEPRECATED** — gunakan `skill` |
| title | VARCHAR(255) | NOT NULL | |
| order | INT | NOT NULL | Urutan dalam exam |
| duration_minutes | INT | NULL | |
| instructions | TEXT | NULL | |
| total_questions | INT | NOT NULL, DEFAULT 0 | |
| navigation_enabled | TINYINT(1) | NOT NULL, DEFAULT 1 | Bisa bolak-balik soal |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

### 12. question_groups

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_section_id | BIGINT UNSIGNED | NOT NULL, FK → exam_sections CASCADE | |
| passage_id | BIGINT UNSIGNED | NULL, FK → passages SET NULL | Pasangan bacaan/audio |
| title | VARCHAR(255) | NULL | |
| group_type | ENUM('passage','conversation','lecture','standalone','prompt') | NOT NULL, DEFAULT 'passage' | |
| passage_text | LONGTEXT | NULL | **DEPRECATED** — pindah ke passages |
| audio_file | VARCHAR(255) | NULL | **DEPRECATED** — pindah ke passages |
| image | VARCHAR(255) | NULL | |
| topic | VARCHAR(255) | NULL | |
| word_count | INT | NULL | |
| order | INT | NOT NULL | |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

### 13. questions

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| question_group_id | BIGINT UNSIGNED | NOT NULL, FK → question_groups CASCADE | |
| type | ENUM(...10 types) | NOT NULL | Lihat QuestionType enum |
| question_text | TEXT | NOT NULL | |
| options | JSON | NULL | Array pilihan jawaban |
| correct_answer | TEXT | NULL | Kunci jawaban (single) |
| correct_answers | JSON | NULL | Kunci jawaban (multi-select/matching) |
| points | INT | NOT NULL, DEFAULT 1 | Bobot soal |
| order | INT | NOT NULL | |
| passage_reference | VARCHAR(255) | NULL | "Paragraph X" |
| difficulty | ENUM('easy','medium','hard') | NOT NULL, DEFAULT 'medium' | |
| status | ENUM('draft','active','archived') | NOT NULL, DEFAULT 'draft' | |
| created_by | BIGINT UNSIGNED | NULL, FK → users SET NULL | |
| updated_by | BIGINT UNSIGNED | NULL, FK → users SET NULL | |
| time_estimate | INT | NULL | Estimasi waktu (detik) |
| explanation | TEXT | NULL | Pembahasan jawaban |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

**Question types:** `multiple_choice`, `multi_select`, `order`, `matching`, `fill_blank`, `essay`, `speaking`, `true_false`, `dictation`, `error_id`

### 14. tags

| Column | Type | Constraint |
|--------|------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| name | VARCHAR(100) | NOT NULL |
| type | VARCHAR(50) | NOT NULL, DEFAULT 'general' |

UNIQUE: `(name, type)`

### 15. question_tag _(pivot)_

| Column | Type | Constraint |
|--------|------|------------|
| question_id | BIGINT UNSIGNED | PK, FK → questions CASCADE |
| tag_id | BIGINT UNSIGNED | PK, FK → tags CASCADE |

### 16. exam_section_question _(pivot)_

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_section_id | BIGINT UNSIGNED | NOT NULL, FK → exam_sections CASCADE | |
| question_id | BIGINT UNSIGNED | NOT NULL, FK → questions CASCADE | |
| order | INT | NOT NULL, DEFAULT 0 | Urutan soal dalam section |

UNIQUE: `(exam_section_id, question_id)`

### 17. test_forms

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_type_id | BIGINT UNSIGNED | NOT NULL, FK → exam_types CASCADE | |
| exam_id | BIGINT UNSIGNED | NULL, FK → exams SET NULL | |
| name | VARCHAR(255) | NOT NULL | Nama paket soal |
| assembly_mode | ENUM('manual','random_pool','rule_based') | NOT NULL, DEFAULT 'manual' | |
| status | ENUM('draft','published','archived') | NOT NULL, DEFAULT 'draft' | |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

### 18. test_form_questions _(pivot)_

| Column | Type | Constraint |
|--------|------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| test_form_id | BIGINT UNSIGNED | NOT NULL, FK → test_forms CASCADE |
| question_id | BIGINT UNSIGNED | NOT NULL, FK → questions CASCADE |
| order | INT | NOT NULL, DEFAULT 0 |

UNIQUE: `(test_form_id, question_id)`

### 19. scoring_rules

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_type_id | BIGINT UNSIGNED | NOT NULL, FK → exam_types CASCADE | |
| section_skill | VARCHAR(50) | NOT NULL | "reading","listening",dll |
| conversion_table | JSON | NOT NULL | Map raw → scaled score |
| scoring_method | ENUM('raw_to_scaled','rubric','weighted') | NOT NULL, DEFAULT 'raw_to_scaled' | |
| rubric | JSON | NULL | Kriteria penilaian manual |
| max_raw | INT | NOT NULL | Max raw score |
| max_scaled | INT | NOT NULL | Max scaled score |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

UNIQUE: `(exam_type_id, section_skill)` — 1 rule per skill per exam type

**Contoh rubric (TOEFL Writing):**
```json
{
  "max_score": 5,
  "descriptors": {
    "5": "Adequately addresses the task. Well organized and developed...",
    "4": "Addresses the task but may have minor omissions...",
    "3": "Addresses the task but development is limited...",
    "2": "Limited development in response to the task...",
    "1": "Little or no development...",
    "0": "Blank response or merely copies the prompt..."
  }
}
```

### 20. exam_schedules

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_id | BIGINT UNSIGNED | NOT NULL, FK → exams CASCADE | |
| title | VARCHAR(255) | NOT NULL | |
| scheduled_start | DATETIME | NOT NULL | |
| scheduled_end | DATETIME | NOT NULL | |
| late_tolerance_minutes | INT | NOT NULL, DEFAULT 15 | Toleransi keterlambatan |
| max_participants | INT | NOT NULL, DEFAULT 30 | Kuota |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 | |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

### 21. exam_sessions

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_schedule_id | BIGINT UNSIGNED | NOT NULL, FK → exam_schedules CASCADE | |
| user_id | BIGINT UNSIGNED | NOT NULL, FK → users CASCADE | |
| status | ENUM('pending','in_progress','submitted','terminated','reviewed') | NOT NULL, DEFAULT 'pending' | |
| started_at | DATETIME | NULL | |
| submitted_at | DATETIME | NULL | |
| terminated_at | DATETIME | NULL | |
| termination_reason | VARCHAR(255) | NULL | |
| current_section_id | BIGINT UNSIGNED | NULL, FK → exam_sections SET NULL | Section yang sedang dikerjakan |
| last_heartbeat_at | DATETIME | NULL | Server-side heartbeat tracking |
| review_status | ENUM('sah','ujian_ulang','dibatalkan') | NULL | Keputusan proctor |
| review_note | TEXT | NULL | Catatan proctor |
| reviewed_by | BIGINT UNSIGNED | NULL, FK → users SET NULL | Proctor yang review |
| reviewed_at | DATETIME | NULL | |
| score_reading | DECIMAL(4,1) | NULL | 0 – 30 |
| score_listening | DECIMAL(4,1) | NULL | 0 – 30 |
| score_speaking | DECIMAL(4,1) | NULL | 0 – 30 |
| score_writing | DECIMAL(4,1) | NULL | 0 – 30 |
| score_total | DECIMAL(4,1) | NULL | 0 – 120 |
| is_flagged | TINYINT(1) | NOT NULL, DEFAULT 0 | Flagged for proctor review |
| flag_reason | VARCHAR(255) | NULL | |
| violation_strikes | INT | NOT NULL, DEFAULT 0 | Akumulasi strike |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

UNIQUE: `(exam_schedule_id, user_id)` — 1 user hanya bisa daftar 1x per jadwal

### 22. answers

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_session_id | BIGINT UNSIGNED | NOT NULL, FK → exam_sessions CASCADE | |
| question_id | BIGINT UNSIGNED | NOT NULL, FK → questions CASCADE | |
| answer_text | TEXT | NULL | Jawaban teks |
| answer_json | JSON | NULL | Jawaban multi-select/matching |
| audio_file | VARCHAR(255) | NULL | Rekaman speaking |
| audio_duration | INT | NULL | Durasi rekaman (detik) |
| is_correct | TINYINT(1) | NULL | NULL = belum diskor |
| score | DECIMAL(5,2) | NULL | |
| scorer_id | BIGINT UNSIGNED | NULL, FK → users SET NULL | NULL = auto-scored |
| scored_at | DATETIME | NULL | |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

UNIQUE: `(exam_session_id, question_id)` — 1 jawaban per soal per sesi

### 23. flagged_questions

| Column | Type | Constraint |
|--------|------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| exam_session_id | BIGINT UNSIGNED | NOT NULL, FK → exam_sessions CASCADE |
| question_id | BIGINT UNSIGNED | NOT NULL, FK → questions CASCADE |
| created_at | TIMESTAMP | NULL |

UNIQUE: `(exam_session_id, question_id)`

### 24. violation_logs

| Column | Type | Constraint | Keterangan |
|--------|------|------------|------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| exam_session_id | BIGINT UNSIGNED | NOT NULL, FK → exam_sessions CASCADE | |
| type | VARCHAR(50) | NOT NULL | Jenis pelanggaran |
| severity | ENUM('warning','minor','major','critical') | NOT NULL, DEFAULT 'minor' | |
| description | TEXT | NULL | |
| metadata | JSON | NULL | Data tambahan (URL, timestamp) |
| strike_count | INT | NOT NULL | Akumulasi strike setelah violation ini |
| screenshot_path | VARCHAR(255) | NULL | Path screenshot (Phase 3) |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

**Violation types:** `tab_switch`, `fullscreen_exit`, `copy_paste`, `right_click`, `devtools`, `multiple_login`, `heartbeat_lost`, `window_blur`, `print_attempt`

**Severity → Strike mapping:**
| Severity | Strike | Contoh |
|----------|:------:|--------|
| warning | +0 | right_click |
| minor | +1 | tab_switch, fullscreen_exit, window_blur |
| major | +2 | copy_paste, devtools, print_attempt |
| critical | +3 | heartbeat_lost, multiple_login |

---

## Index Summary

| Tabel | Index | Tipe | Query |
|-------|-------|:----:|-------|
| `users` | `users_email_unique` | UNIQUE | Login |
| `student_profiles` | `student_profiles_user_id_unique` | UNIQUE | Lookup per user |
| `exam_sessions` | `exam_sessions_exam_schedule_id_user_id_unique` | UNIQUE | Dedup pendaftaran |
| `answers` | `answers_exam_session_id_question_id_unique` | UNIQUE | Upsert auto-save |
| `flagged_questions` | `flagged_questions_...unique` | UNIQUE | Toggle flag |
| `scoring_rules` | `scoring_rules_exam_type_id_section_skill_unique` | UNIQUE | 1 rule per skill |
| `exam_section_question` | `exam_section_question_...unique` | UNIQUE | Dedup soal di section |
| `test_form_questions` | `test_form_questions_...unique` | UNIQUE | Dedup soal di form |
| `question_tag` | PK | COMPOSITE | Dedup tag |
| `tags` | `tags_name_type_unique` | UNIQUE | Dedup tag name |

> Foreign key columns di MySQL otomatis mendapat index. Index composite tambahan (user_id+status, schedule_id+status, dll) ditambahkan via migration terpisah untuk optimasi query.

---

## Migration Order

```
[Core Laravel]
  0001_01_01_000000_create_users_table.php
  0001_01_01_000001_create_cache_table.php
  0001_01_01_000002_create_jobs_table.php

[Spatie]
  2026_07_08_020606_create_permission_tables.php
  2026_07_08_020607_create_activity_log_table.php
  2026_07_08_020608_add_event_column_to_activity_log_table.php
  2026_07_08_020609_add_batch_uuid_column_to_activity_log_table.php

[User Extensions]
  2026_07_08_060141_add_fields_to_users_table.php          ← phone, photo, is_active
  2026_07_09_035714_add_google_fields_to_users_table.php

[Student Profiles]
  2026_07_10_000000_create_student_profiles_table.php
  2026_07_10_000001_create_exam_types_table.php
  2026_07_10_000002_create_passages_table.php

[Exam Core]
  2026_07_10_000003_create_exams_table.php
  2026_07_10_000004_create_exam_sections_table.php
  2026_07_10_000005_create_question_groups_table.php
  2026_07_10_000006_create_questions_table.php

[Tags & Pivots]
  2026_07_10_000007_create_tags_table.php
  2026_07_10_000008_create_question_tag_table.php
  2026_07_10_000009_create_exam_section_question_table.php

[Test Forms]
  2026_07_10_000010_create_test_forms_table.php
  2026_07_10_000011_create_test_form_questions_table.php

[Scoring]
  2026_07_10_000012_create_scoring_rules_table.php

[Schedule & Session]
  2026_07_10_000013_create_exam_schedules_table.php
  2026_07_10_000014_create_exam_sessions_table.php
  2026_07_10_000015_create_answers_table.php
  2026_07_10_000016_create_flagged_questions_table.php
  2026_07_10_000017_create_violation_logs_table.php

[Data Migration]
  2026_07_10_000018_migrate_student_data_to_profiles.php
  2026_07_10_000019_remove_student_columns_from_users.php
```

---

## Catatan

1. **`exam_sections.type`** — kolom enum lama dipertahankan NULL untuk backward compatibility. Kode baru membaca dari `skill` (VARCHAR dinamis). Kolom `type` akan dihapus di masa depan.

2. **`question_groups.passage_text` & `audio_file`** — kolom lama dipertahankan untuk backward compatibility. Data bisa dimigrasi ke tabel `passages` secara bertahap.

3. **`answers.scorer_id`** — NULL = jawaban di-auto-score. IS NOT NULL = dinilai manual oleh instructor.

4. **Multi-user session prevention** — MySQL tidak support partial unique index. Pencegahan 2 sesi `in_progress` per user ditangani di level aplikasi via `DB::transaction()` + row-level lock.

5. **Redis direkomendasikan** untuk `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` di production — cukup ganti 3 env var, tidak perlu migration.

6. **All PKs** menggunakan `BIGINT UNSIGNED AUTO_INCREMENT` (Laravel `id()` / `bigIncrements`).
