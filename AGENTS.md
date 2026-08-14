# Global Rules

## Mandatory skill loading

At the start of every session, and before any code-change request, use the `skill` tool to load:

- `codebase-guardian` — full codebase read-before-touch discipline, convention adherence, minimal-diff changes
- `laravel-modular-monolith` — for every backend change (controllers, models, services, routes, migrations, requests); enforces module structure, thin controllers, repository pattern, and contract-based inter-module communication
- `design-system-frontend` — for any UI, component, or page work in component-based frontend frameworks (Vue, React, Svelte, etc.)
- `commit-message` — before any git commit/push; enforces conventional commit format `<type>(<scope>): <subject>` in Bahasa Indonesia (solo project — commits go directly on `testing`)
- `session-coordination` — at every session start, on every branch; lightweight guard for parallel opencode windows on the same branch via per-branch file claims (SESSION-STATUS.md)
- `functional-testing` — at the end of every feature/bugfix task; runs functional (HTTP-level) tests, writes missing feature tests, forces the full suite green (phpunit + pint + eslint + phpstan) with a re-test loop, and reports PASS/FAIL (manual UI testing stays with the dev)

Follow their instructions exactly once loaded.

## Gate kualitas sebelum push (wajib)

Semua cek HARUS hijau secara lokal sebelum push/merge. Siapkan sekali:

```bash
git config core.hooksPath .githooks   # sudah otomatis via composer setup
```

Lalu sebelum push jalankan:

```bash
npm run lint          # eslint resources/js/
composer check        # pint --test + phpstan + phpunit
```

`composer check` gagal di langkah pertama tanpa mengecek yang lain (urutan sama seperti CI) — perbaiki lalu ulangi.

### Pre-commit hook (`.githooks/pre-commit`)

Otomatis di setiap commit: `eslint --fix` untuk file JS/Vue yang di-stage, `pint` untuk file PHP yang di-stage, lalu re-stage. Commit di-blokir jika masih ada error lint/pint. Catatan: hook memperbaiki file yang di-stage, jadi periksa `git status` setelah commit untuk perubahan tak terduga.

### Pola anti-slop yang sering bikin pipeline merah

- Jangan `Boolean(...)` di kondisi ternary (`no-extra-boolean-cast`) — ternary sudah auto-coerce.
- Jangan `const props = defineProps(...)` jika `props` tidak dipakai — cukup `defineProps(...)`.
- Model Eloquent baru WAJIB punya docblock `@property` (kolom + relasi + agregat seperti `*_count`) sesuai migrasi, agar larastan lulus.
- Jangan `try/catch (QueryException)` mengelilingi `delete()` — larastan menandainya dead catch. Gunakan pre-check `$model->relasi()->exists()` untuk blokir hapus, atau langsung `delete()` jika tidak ada relasi yang mereferensikan.
- Halaman data table baru: tiru pola cell dari file yang sudah lint-clean (mis. `Pages/Admin/MasterData/Skills.vue`), jangan salin dari commit lama yang error.
- Controller WAJIB tipis — logika bisnis di `Services`/`Actions`, akses data lewat Repository Interface (lihat `laravel-modular-monolith`).

## Branch & commit (solo project)

- Kerja langsung di `testing` — tidak ada pola penamaan branch wajib.
- `main` dilindungi: jangan commit/push langsung tanpa konfirmasi eksplisit dari user.
- Format commit: `<type>(<scope>): <subject>` — Bahasa Indonesia, imperative mood, tanpa suffix ticket (lihat skill `commit-message`).

## Konvensi port dev (lokal)

`php artisan serve` membaca `SERVER_PORT` dari `.env`; Vite membaca `VITE_PORT` dari `.env` (default 5180). Jika port 8000/5180 sedang dipakai proses lain, ganti dengan port kosong lain di `.env` (file lokal, tidak di-commit).
