---
name: functional-testing
description: "ALWAYS load at session start. Automatically runs functional testing whenever a feature or bugfix task is finished: identifies the touched module, runs the relevant tests, writes missing functional feature tests, forces the full suite green (phpunit + pint + eslint + phpstan/larastan), and reports PASS/FAIL. Functional (HTTP) level only — the dev still does manual UI testing. Re-tests automatically after every fix until green."
---

# Functional Testing

Every feature or bugfix must be verified before it is considered done. This skill enforces that automatically: **a task is NOT finished until the functional tests are green.** The dev still does manual testing afterward (UI, browser, edge cases) — this skill only covers functional/HTTP-level verification.

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

## Phase 3 — Full suite must be green

Run `composer test` (clears config, then runs the whole suite). **The full suite must pass before the task is finished.** No exceptions, no "it only failed because of X" — fix X.

---

## Phase 4 — Pint, ESLint & static analysis

The local quality gate checks the exact same gates: `eslint` + `pint` → `larastan` → `phpunit`. A task is not done unless all four pass:

1. `./vendor/bin/pint --test` on the whole repo (or at least on any changed/new files; if it fails, run `./vendor/bin/pint` on those files, then re-run the tests).
2. `npm run lint` (eslint on `resources/js/`). If it fails, run `npx eslint resources/js --fix`, then re-run. Remember the recurring anti-patterns: no `Boolean(...)` in ternary conditions, no unused `const props =`, keep the `vue/*` formatting rules.
3. `vendor/bin/phpstan analyse --memory-limit=512M` (larastan). Recurring failures: models without `@property` docblocks, `QueryException` "dead catch" around `delete()` (use an explicit `exists()` pre-check instead).

For a single-command local gate use `composer check` (pint + phpstan + phpunit) plus `npm run lint`.

---

## Phase 5 — Report and failure policy

Always report:

```
## Hasil Testing Fungsional
- Test dijalankan: <list>
- Test ditulis baru: <list>
- PASS: <n> | FAIL: <n>
- Pint: LULUS / GAGAL
- ESLint: LULUS / GAGAL
- PHPStan: LULUS / GAGAL
- Full suite: HIJAU / MERAH
```

**On failure: STOP. Report the failure with analysis (cause, affected code, suggested fix). Do NOT fix application code without the user's approval** (codebase-guardian: never silently change code). Trivial test-file fixes (wrong assertion, typo in test data) may be fixed directly — report them in the summary.

---

## Phase 6 — Session coordination integration

Update `SESSION-STATUS.md` (see `session-coordination` skill): note `testing fungsional selesai` with timestamp once the full suite is green. If tests are red, note what is blocked.

---

## Phase 7 — Re-test loop (after fixes)

After the user applies a fix (or approves a fix), the skill MUST automatically:

1. Re-run the failing tests: `php artisan test --filter=<...>` → confirm the previously failing tests now pass.
2. Re-run the full suite: `composer test` → confirm nothing else broke.
3. Loop continues until green; only then present the final report.

**Anti-loop protection:** if the same failure repeats with the same cause after a fix, STOP and escalate to the user — do not keep re-fixing silently.

---

## Boundaries

- This skill does NOT commit anything (commit discipline belongs to `commit-message`).
- This skill does NOT run browser/manual UI testing — that stays with the dev.
- This skill does NOT change business logic on its own — only test files, plus reporting (and trivial test fixes).
