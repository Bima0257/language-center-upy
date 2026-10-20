---
name: functional-testing
description: "Load on demand when a feature/bugfix task is being finalized (after the work is done). Automatically runs targeted functional testing: identifies the touched module, runs the relevant tests, writes missing functional feature tests, runs scoped quality checks, and reports PASS/FAIL. Full lint, Larastan, and PHPUnit checks are deferred to CI when changes enter testing. Functional (HTTP) level only — the dev still does manual UI testing. Re-tests automatically after every fix until green."
---

# Functional Testing

Every feature or bugfix must be verified before it is considered done. This skill enforces that automatically: **a task is NOT finished until the targeted functional tests are green.** The dev still does manual testing afterward (UI, browser, edge cases) — this skill only covers functional/HTTP-level verification.

---

## Trigger

This skill is active in every session. It fires automatically when:

1. A feature or bugfix task is complete (or the user says the work is "selesai" / "done").
2. After an approved fix has been applied — the failing tests MUST be re-run (see Phase 7, re-test loop).

The skill never marks a task as finished with a red test suite.

---

## Phase 1 — Identify scope

Before testing, determine exactly what was touched:

1. `git status` and `git diff` (staged + unstaged) — which files/modules changed.
2. Map changed files to modules: `app/Modules/{Module}/...` → module name; shared files (`app/Models/`, `app/Http/`, `routes/`) → all features that use them.
3. From the task context, list the key user flows: page render, form submit, permission gate, DB effects.

### Module map (proyek ini)

| Module | Domain |
|---|---|
| `app/Modules/Exam` | Content library, question bank, passages, sections |
| `app/Modules/Schedule` | Exam schedules (admin CRUD) |
| `app/Modules/Session` | Exam session runtime (start, answers, submit, heartbeat) |
| `app/Modules/Scoring` | Auto-score listening/reading, total score conversion |
| `app/Modules/Security` | Violations, strikes, session termination |
| `app/Modules/Proctor` | Session review & decisions |
| `app/Modules/Report` | Reports & exports |
| `app/Modules/MasterData` | Master data CRUD (faculties, departments, exam types, skills, certificates, score interpretations) |
| `app/Modules/Users` | User management (verification, onboarding, Google auth) |

---

## Phase 2 — Run relevant tests, write missing ones

1. Run the relevant subset first: `php artisan test --filter=<Module|Feature|TestClass>`.
2. **If no functional test covers the changed flows, WRITE one.** This is mandatory — a task without test coverage is not done. Write minimal functional feature tests at `tests/Feature/{Module}/{Feature}Test.php`, mirroring the existing conventions:

### Conventions (dari `tests/Feature/Auth/AuthenticationTest.php` dan `tests/Feature/ProfileTest.php` — never deviate)

| Convention | Value |
|---|---|
| Namespace | `Tests\Feature\{Module}` |
| Base class | extends `Tests\TestCase` |
| Trait | `use RefreshDatabase;` |
| Setup | `setUp()` → `parent::setUp();` (opsional `$this->seed()` bila butuh role/master data) |
| Method naming | `test_*` snake_case, `: void` return type |
| Style | 4 spaces, single quotes, typed methods, fully qualified `use` imports |
| User factory | `User::factory()->create()` (Breeze default factory) |
| Login | `POST /login` dengan `email` + `password` (`password` = default factory password) |
| Role | `$user->assignRole('admin')` — roles: `superadmin`, `admin`, `instructor`, `proctor`, `student` (dari `RoleSeeder`) |

### Seeded users (dari `DatabaseSeeder` — bila memakai `$this->seed()`)

| Email | Role | Password |
|---|---|---|
| `superadmin@toefl.test` | superadmin | `password` |
| `admin@toefl.test` | admin | `password` |
| `instructor@toefl.test` | instructor | `password` |
| `proctor@toefl.test` | proctor | `password` |
| `student@toefl.test` | student | `password` |

### Role gate behavior (middleware spatie `role:` — lihat `routes/web.php`)

| Scenario | Assertion |
|---|---|
| Guest hits a protected route | `assertRedirect('/login')` |
| Wrong role hits a protected route (mis. `role:admin,superadmin`) | `assertForbidden()` (403) |
| Correct role | `assertOk()` |
| Invalid form data | `assertSessionHasErrors('field')` |
| Successful mutation | `assertRedirect(...)` + `assertDatabaseHas(...)` |

### Template

```php
<?php

namespace Tests\Feature\Exam;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function loginAs(string $role): User
    {
        $user = User::where('email', $role.'@toefl.test')->first();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/exams')->assertRedirect('/login');
    }

    public function test_wrong_role_is_forbidden(): void
    {
        $this->loginAs('student');

        $this->get('/admin/exams')->assertForbidden();
    }

    public function test_admin_can_access_page(): void
    {
        $this->loginAs('admin');

        $this->get('/admin/exams')->assertOk();
    }
}
```

Cover per changed flow, at minimum: happy path, validation (if forms change), and the role gate (correct role + wrong role). Do NOT write unit tests here — functional/HTTP only.

---

## Phase 3 — Full suite is deferred to merge CI

Do not run the full suite during normal task finalization. Full `lint`, Larastan, and PHPUnit checks run in CI when changes are pushed or merged into `testing`.

1. Do NOT run `composer test` or `composer check` here.
2. The task may be finished when the targeted functional tests and scoped quality checks pass.
3. Report the full merge gate as `DITUNDA KE CI MERGE TESTING`, not as a local pass or failure.

---

## Phase 4 — Scoped Pint, ESLint & static analysis

Run quality checks only for changed files or the touched module. Skip a check when its file type is not part of the change:

1. `./vendor/bin/pint --test <changed PHP files>`. If it fails, run Pint on those files, then repeat the scoped checks.
2. `npx eslint <changed JS/Vue files>`. If it fails, run `npx eslint <changed JS/Vue files> --fix`, then repeat. Remember the recurring anti-patterns: no `Boolean(...)` in ternary conditions, no unused `const props =`, keep the `vue/*` formatting rules.
3. `vendor/bin/phpstan analyse <changed PHP paths> --memory-limit=512M` (Larastan). Recurring failures: models without `@property` docblocks, `QueryException` "dead catch" around `delete()` (use an explicit `exists()` pre-check instead).

Do not replace these scoped commands with the full `composer check` or `npm run lint`; those full commands belong to merge CI.

---

## Phase 5 — Report and failure policy

Always report:

```
## Hasil Testing Fungsional
- Test targeted dijalankan: <list>
- Test ditulis baru: <list>
- PASS: <n> | FAIL: <n>
- Pint scoped: LULUS / GAGAL / TIDAK RELEVAN
- ESLint scoped: LULUS / GAGAL / TIDAK RELEVAN
- PHPStan scoped: LULUS / GAGAL / TIDAK RELEVAN
- Full merge gate: DITUNDA KE CI `testing`
```

**On failure: STOP. Report the failure with analysis (cause, affected code, suggested fix). Do NOT fix application code without the user's approval** (AGENTS.md: jangan ubah kode tak terkait tanpa izin). Trivial test-file fixes (wrong assertion, typo in test data) may be fixed directly — report them in the summary.

---

## Phase 6 — Re-test loop (after fixes)

After the user applies a fix (or approves a fix), the skill MUST automatically:

1. Re-run the failing tests: `php artisan test --filter=<...>` → confirm the previously failing tests now pass.
2. Re-run the relevant scoped lint, Pint, or Larastan check when the fix touches that file type.
3. Do not run the full suite in this loop; CI runs it when changes enter `testing`.
4. Loop continues until the targeted checks are green; only then present the final report.

**Anti-loop protection:** if the same failure repeats with the same cause after a fix, STOP and escalate to the user — do not keep re-fixing silently.

---

## Boundaries

- This skill does NOT commit anything (commit conventions follow `AGENTS.md` — Konvensi commit).
- This skill does NOT run browser/manual UI testing — that stays with the dev.
- This skill does NOT change business logic on its own — only test files, plus reporting (and trivial test fixes).
