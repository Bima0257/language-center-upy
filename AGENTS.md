# Global Rules

## Skill loading

Load ONCE at the start of every session:

- `laravel-modular-monolith` — for every backend change (controllers, models, services, routes, migrations, requests); enforces module structure, service layer, contract-based inter-module communication, and global helper restrictions
- `senior-backend` — for every backend change (alongside laravel-modular-monolith); enforces senior-level Laravel engineering quality: query performance (N+1, eager loading, chunking), security (mass assignment, per-record authorization, validation), error handling & logging, PHP 8.3 idioms, and caching only with explicit dev approval (cache driver varies per server: database or Redis)
- `design-system-frontend` — for any UI, component, or page work in component-based frontend frameworks (Vue, React, Svelte, etc.)

Loaded skills stay in context — DO NOT reload them during the session; reload only after context compaction (when their content is no longer in context).

Load on demand, when the trigger hits:

- `senior-database-implementation` — when the user provides a database schema or requests schema changes/evolution
- `functional-testing` — when a feature/bugfix task is being finalized (after the work is done)

Follow their instructions exactly once loaded.

## Konvensi commit (ringkas)

- Branch: kerja langsung di branch aktif (`testing`); untuk task spesifik: `feat/<slug>` atau `fix/<slug>`
- Subject Bahasa Indonesia, imperative (tambah/perbaiki/hapus), ≤72 char, tanpa titik akhir
- type: feat|fix|docs|style|refactor|test|chore|perf|ci|build
- Body commit (jika ada): maksimal 2-3 bullet poin inti perubahan — jangan detail panjang

## Aturan Clean Code & Best Practice (wajib)

- **Minimal diff**: hanya ubah yang diminta task; jangan refactor/rename/reformat kode tak terkait (no drive-by changes).
- **Jangan ubah kode tak terkait secara diam-diam** — laporkan bug yang ditemukan, jangan perbaiki tanpa izin.
- **Penamaan deskriptif**: hindari variabel generik (`data`, `result`, `temp`, `item`); ikuti konvensi yang ada (snake_case DB, camelCase method/variable, PascalCase class).
- **KISS/YAGNI**: tanpa approval user, jangan tambah abstraksi, dependency, atau pola baru yang belum ada di codebase.
- **Self-review sebelum selesai**: trace logika end-to-end, cek edge case (null/empty/error), pastikan tidak ada call site yang rusak; "trivial change" pun wajib lolos lint/typecheck.
- **Laporkan risiko**: sebutkan perubahan yang berisiko / perlu dicek manual di laporan akhir.

## Gate kualitas sebelum push (wajib)

Pipeline CI memeriksa: `eslint` + `pint` → `larastan` → `phpunit` → `deploy_dev`. Semua cek itu HARUS hijau secara lokal sebelum push/merge. Siapkan sekali:

```bash
git config core.hooksPath .githooks   # sudah otomatis via composer setup
```

Lalu sebelum push jalankan:

```bash
npm run lint          # eslint resources/js/ (command identik CI)
npm run build         # build frontend — CI juga build sebelum phpunit
composer check        # pint --test + phpstan + phpunit (command identik CI)
```

`composer check` gagal di langkah pertama tanpa mengecek yang lain (urutan sama seperti CI) — perbaiki lalu ulangi.

### Pre-commit hook (`.githooks/pre-commit`)

Otomatis di setiap commit: `eslint --fix` untuk file JS/Vue yang di-stage, `pint` untuk file PHP yang di-stage, lalu re-stage. Commit di-blokir jika masih ada error lint/pint. Catatan: hook memperbaiki file yang di-stage, jadi periksa `git status` setelah commit untuk perubahan tak terduga.

### Pola anti-slop yang sering bikin pipeline merah

- Jangan `Boolean(...)` di kondisi ternary (`no-extra-boolean-cast`) — ternary sudah auto-coerce.
- Jangan `const props = defineProps(...)` jika `props` tidak dipakai — cukup `defineProps(...)`.
- Model Eloquent baru WAJIB punya docblock `@property` (kolom + relasi + agregat seperti `*_count`) sesuai migrasi, agar larastan lulus.
- Jangan `try/catch (QueryException)` mengelilingi `delete()` — larastan menandainya dead catch. Gunakan pre-check `$model->relasi()->exists()` untuk blokir hapus, atau langsung `delete()` jika tidak ada relasi yang mereferensikan.
- Halaman data table baru: tiru pola cell dari file yang sudah lint-clean (mis. `Pages/Admin/MasterData/ProgramStudi/Index.vue`), jangan salin dari commit lama yang error.

## Konvensi port dev (shared server admin-rde)

`php artisan serve` membaca `SERVER_PORT` dari `.env`; Vite membaca `VITE_PORT` dari `.env` (default 5180). Karena 1 mesin dipakai banyak dev, setiap dev WAJIB punya port unik di `.env` masing-masing (file lokal, tidak di-commit):

| Dev | SERVER_PORT | VITE_PORT |
|---|---|---|
| dev1 | 8001 | 5181 |
| dev2 | 8002 | 5182 |
| dev3 | 8003 | 5183 |

Cek port bebas sebelum menjalankan server: `ss -tlnp | grep -E ':800[0-9]|:518[0-9]'`. Jika port konvensi sedang dipakai instance lama yang belum di-restart, pakai port kosong lain dan laporkan.
