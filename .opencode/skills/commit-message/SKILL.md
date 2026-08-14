---
name: commit-message
description: "ALWAYS use before any git commit or git push, or when the user asks to commit, stage, write a commit message, or push. Enforces this project's solo convention: conventional commit format <type>(<scope>): <subject> with subjects in Bahasa Indonesia. Solo project — commits are made directly on the working branch (testing)."
---

# Commit Message & Branch Convention

This is a **solo project**. Work happens directly on the working branch (currently `testing`), and the deployed/production branch (`main`) is protected. There is no ticket system — commit messages use plain conventional commits. Consistency still matters: it is the only history a solo developer has to trace back decisions.

## Rule 1 — Working branch

1. Run `git branch --show-current` before committing.
2. Committing directly on the current working branch is normal here. There is no required branch naming pattern.
3. **Protected branches** — `main` (production): never commit or push directly without explicit user confirmation. The user decides when to merge `testing` → `main`.
4. If the user asks to create a branch for a task, use a descriptive slug: `feat/<slug>`, `fix/<slug>` (e.g. `feat/skor-listening`).

## Rule 2 — Commit message format

```
<type>(<scope>): <subject>

<body>

<footer>
```

- **type** — from the table below. Never invent new types.
- **scope** — the module/area touched (`exam`, `schedule`, `session`, `scoring`, `security`, `proctor`, `report`, `auth`, `ui`, `master-data`, `deps`, ...). Omit if the change is genuinely global, but prefer including one.
- **subject** — Bahasa Indonesia, imperative mood ("tambah", "perbaiki", "hapus", "pindah" — NOT "menambahkan", "memperbaiki"), max ~72 characters, no trailing period, lowercase start.
- **body** (optional) — bullet points explaining what was done and why.
- **footer** (optional) — e.g. `BREAKING CHANGE: ...`, `Closes #42`.

### Type table (do not invent new types)

| Type | Kapan digunakan | Contoh |
| --- | --- | --- |
| `feat` | Menambah fitur atau kemampuan baru yang bisa dirasakan pengguna. | Tambah halaman manajemen jadwal, tambah auto-score reading, tambah menu baru. |
| `fix` | Memperbaiki bug atau perilaku yang salah. | Perbaiki validasi login, perbaiki timer yang tidak stop, perbaiki typo yang menyebabkan error. |
| `docs` | Perubahan dokumentasi saja. | Update README, API docs, ERD, komentar dokumentasi. |
| `style` | Perubahan gaya kode yang tidak mengubah perilaku program. | Format kode dengan Pint, ubah indentasi, hapus whitespace, urutkan import (jika tidak mengubah logika). |
| `refactor` | Mengubah struktur kode tanpa mengubah perilaku. | Pecah service menjadi beberapa class, rename method internal, pindah folder, menerapkan Repository Pattern. |
| `test` | Menambah atau mengubah test. | Tambah unit test, update feature test, perbaiki mock data. |
| `chore` | Pekerjaan maintenance yang bukan fitur maupun bug fix. | Update dependency, ubah `.gitignore`, konfigurasi editor, update package minor, bersihkan file. |
| `perf` | Optimasi performa tanpa mengubah fitur. | Optimasi query, cache data, lazy loading, mengurangi waktu render. |
| `ci` | Perubahan CI/CD. | Update GitHub Actions workflow, Jenkins pipeline. |
| `build` | Perubahan yang memengaruhi proses build atau dependency. | Update Vite config, Composer/npm package, Docker build. |

**Revert** is not in the type table: when reverting, use the type of the change being reverted (e.g. `fix`) and explain the rollback in the body.

## Rule 3 — Workflow before every commit

1. Run `git branch --show-current` — confirm the branch is safe to commit on (Rule 1).
2. Run `git status` and `git diff --cached` (plus `git diff` for unstaged changes) to fully understand what changed.
3. Map the changes to a type and scope. If the changes span multiple purposes, **split them into separate commits** — never one giant commit.
4. Verify there are no unrelated files staged and no secrets (`*.env`, keys, tokens, credentials JSON) in the commit.
5. Write the message following Rule 2 and run the commit.

## Rule 4 — Push rules

- Re-check the protected-branch rule before pushing.
- Never force-push (`git push --force`) without explicit user confirmation.
- Never push to `main` without explicit user confirmation.
- Push only the current branch to its own origin branch.

## Prohibitions

- Never write generic messages ("fix bug", "update", "wip", "progress").
- Never write commit messages in English — subjects are Bahasa Indonesia.
- Never `git commit --amend` or rebase without explicit user confirmation.
- Never commit secrets or unrelated files.
- Never add new types beyond the table above.

## Examples

### Good

```
feat(exam): tambah auto-score untuk section listening
```

```
fix(session): perbaiki timer yang terus berjalan setelah submit
```

```
chore(deps): update composer dependencies minor
```

### Bad

```
update style ui component
```
❌ No type, no scope, not imperative.

```
feat: tambah button
```
❌ Missing scope — tambahkan scope (mis. `feat(ui)`).

```
feat(ui): Menambahkan komponen Button
```
❌ Not imperative mood ("Menambahkan" → "tambah").
