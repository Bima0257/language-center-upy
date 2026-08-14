---
name: laravel-modular-monolith
description: "ALWAYS use for every backend change in this Laravel project (controllers, models, services, routes, migrations, requests). Enforces the modular monolith architecture: consistent module structure (Controllers, Services, Repositories, DTOs, Actions), thin controllers, repository pattern, and inter-module communication ONLY through repository interfaces, services, and actions — never direct model imports from other modules."
---

# Laravel Modular Monolith — Backend Architecture Enforcement

This project is a **Laravel modular monolith** (solo project — but pattern consistency is still non-negotiable: it is what makes future changes predictable). Every backend change MUST follow the rules below. The pattern is the product — deviating from it creates drift that has to be undone later.

---

## Phase 1 — Architecture Map (read before touching anything)

Before writing any backend code, you MUST know the current state. Read:

1. **`bootstrap/providers.php`** — the module registration list. Every module has a `{Module}ModuleServiceProvider` here. A module not listed here is invisible: its bindings and routes will NOT be loaded.
2. **`routes/web.php`** — the routing chain (see below). Module routes are auto-loaded via `glob(app_path('Modules/*/routes.php'))` inside the `auth` + `verified` + `verified.user` group.
3. **At least 2 existing modules** under `app/Modules/` (e.g. `Exam`, `Session`) — read Controllers, Services, Repositories, DTOs, Actions, `routes.php` to lock the pattern.
4. **`app/Models/`** — all Eloquent models live here (global, not per-module).
5. **`app/Http/Requests/`** — FormRequests are global, grouped by feature subfolder (`Auth/`, `Exam/`, `Schedule/`).

### The routing chain (know it by heart)

```
routes/web.php
  ├── '/' → Welcome page (guest)
  ├── onboarding (auth)
  ├── dashboard (auth + verified + verified.user) — renders by role
  ├── admin/* (role:admin,superadmin) — portal admin
  ├── profile (auth)
  └── foreach glob(app_path('Modules/*/routes.php')) → require  ← MODULE ROUTES
        └── each module file defines its own groups with
            Route::middleware([...])->prefix(...)->name(...)->group(...)
```

Role gating uses the spatie middleware `role:` (see `config/permission.php`). Custom middleware in `app/Http/Middleware/`: `CheckRole` (helper variant), `EnsureVerified`, `EnsureExamReady`, `EnsureExamSessionActive`.

---

## Phase 2 — Module Structure (identical for every module)

Every module under `app/Modules/{ModuleName}/` uses this layout (only layers the module actually needs — do NOT create empty folders):

```
app/Modules/{ModuleName}/
├── Providers/
│   └── {ModuleName}ModuleServiceProvider.php   # binds Repository Interface → Repository
├── Controllers/
│   └── {ControllerName}.php                    # thin HTTP layer: validate → call service/action → respond
├── Services/
│   └── {ServiceName}.php                       # business logic / orchestration
├── Repositories/
│   ├── {EntityName}Repository.php              # data access (Eloquent queries live here)
│   └── Contracts/
│       └── {EntityName}RepositoryInterface.php # interface, bound in the provider
├── DTOs/
│   └── {DataName}.php                          # typed data carriers (read-only)
├── Actions/
│   └── {VerbAction}.php                        # single-use operations (e.g. StartExamSession, SaveAnswer)
└── routes.php                                  # route groups for this module
```

Real examples to mirror: `Exam` (Controllers + DTOs + Repositories + Services), `Session` (Controllers + DTOs + Repositories + Services + Actions), `Security` (Actions + DTOs + Repositories + Services), `Proctor` (Actions + DTOs + Services), `Scoring` (Actions + DTOs + Services), `Report` (Controllers + Services).

### Creation checklist — every new module or layer

- [ ] Register the module's ServiceProvider in `bootstrap/providers.php`.
- [ ] Controllers extend `App\Http\Controllers\Controller`.
- [ ] Business logic in Services/Actions — controllers stay thin.
- [ ] Data access in Repositories, accessed via their interface.
- [ ] FormRequests live in `app/Http/Requests/{Feature}/`, extend `Illuminate\Foundation\Http\FormRequest`.
- [ ] Models live in `app/Models/`, extend `Illuminate\Database\Eloquent\Model`.
- [ ] Module routes in the module's own `routes.php` — never in root `routes/web.php` (except shared portal groups).
- [ ] No new Blade view files — Inertia + Vue only.

### ServiceProvider template

```php
<?php

namespace App\Modules\Exam\Providers;

use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Exam\Repositories\ExamRepository;
use Illuminate\Support\ServiceProvider;

class ExamModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExamRepositoryInterface::class, ExamRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
```

### Module routes.php template

```php
<?php

use App\Modules\Exam\Controllers\ExamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin/exams')
    ->name('admin.exams.')
    ->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('index');
        // ...
    });
```

---

## Phase 3 — Layering (MANDATORY)

### Controller responsibilities (and nothing else)
- Receive the request, validate via FormRequest (injected in method signature)
- Call ONE service method or action (or at most a few that map 1:1 to the flow)
- Map the result to the HTTP response (Inertia render / redirect / JSON)

### Service responsibilities
- Orchestration of business rules and flows
- Cross-module coordination (via repository interfaces / actions — see Phase 4)

### Action responsibilities
- A single-use operation with one clear verb (`StartExamSession`, `SaveAnswer`, `LogViolation`, `SubmitExam`, `CalculateTotalScore`)
- May use repositories, services, and DTOs of the same or other modules
- Actions are the granular building blocks — services may compose them

### Repository responsibilities
- ALL Eloquent queries, scopes, and DB access
- Returns models/collections/DTOs — never leaks query builder chains into controllers

### DTO responsibilities
- Typed, read-only data carriers between layers (e.g. `SubmitResult`, `ViolationData`, `ScoreData`)
- Immutable — no business logic inside

### Rules
- A controller method containing more than ~3 lines of logic (beyond validation + response) must delegate to a service/action.
- Services and Actions are resolved via constructor injection.
- Repositories are accessed ONLY through their interface type (`Repositories/Contracts/...`) — never instantiate the concrete class.
- Services never extend anything; they are plain classes with type-hinted dependencies.

---

## Phase 4 — Inter-Module Communication (interfaces & actions only)

Modules MAY use each other — but ONLY through the channels described below. No other path is legal.

### Legal channels
1. **Repository interface from the target module** — resolved through the container:
   ```php
   use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

   class StartExamSession
   {
       public function __construct(private ExamSessionRepositoryInterface $examSessions) {}
   }
   ```
2. **Action from the target module** — e.g. `Session\SubmitExam` composes `Scoring\Actions\AutoScoreListening`, `AutoScoreReading`, `CalculateTotalScore`.
3. **Service from the target module** — e.g. `Session\StartExamSession` uses `Security\Services\SecurityService`.

### Resolution rule
```php
// ✅ LEGAL: constructor injection of an interface
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;

class StartExamSession
{
    public function __construct(private ScheduleRepositoryInterface $schedules) {}
}
```

### ILLEGAL patterns
```php
use App\Modules\Session\Models\ExamSession;              // VIOLATION — no per-module models exist
ExamSession::where(...)->get();                          // VIOLATION in a controller/service outside its repo
```
- Direct model usage in controllers: **Models are global** (`app/Models/`) and are the DOMAIN entities — but the DATA ACCESS still belongs to the owning module's repository. A controller for `Exam` must not run raw `Exam::where(...)` chains; it goes through `ExamRepositoryInterface`.
- `DB::table()` / `DB::raw()` in controllers = VIOLATION.
- Instantiating a concrete repository (`new ExamRepository()`) = VIOLATION — use the interface.

### If the needed capability is not exposed
1. Add the method to the owning module's repository interface.
2. Implement it in that module's repository.
3. Or add a small Action in the owning module and call it.
Only then consume it. Never bypass to "just get it done" — that is exactly how modular monoliths rot.

---

## Phase 5 — Convention Map (extracted from existing code — DO NOT deviate)

| Convention | Value |
|---|---|
| PHP / framework | PHP 8.3, Laravel 13 |
| Indentation | 4 spaces |
| Quote style | single quotes for PHP strings |
| Class/type naming | PascalCase |
| Method/variable naming | camelCase |
| DB tables/columns | snake_case, plural tables |
| Primary keys | `$table->id()` big-integer auto-increment (NOT UUID) |
| Foreign keys | `$table->foreignId(...)->constrained()` |
| File naming | PascalCase.php for classes |
| Route URIs | kebab-case (`/content-library/question-banks`) |
| Route names | `{area}.{resource}.{action}` — dotted kebab-case (`admin.exams.sections.store`) |
| Namespaces | `App\Modules\{Module}\{Layer}`; global layers `App\Models`, `App\Services`, `App\Http\...`, `App\Enums` |
| Models | `app/Models/`, extend `Illuminate\Database\Eloquent\Model` |
| Enums | `app/Enums/` (ExamMode, QuestionType, SessionStatus, ViolationSeverity, ViolationType) |
| Auth/RBAC | `spatie/laravel-permission`; roles `superadmin`, `admin`, `instructor`, `proctor`, `student`; middleware `role:` |
| Frontend | Inertia.js + Vue 3 — **NO Blade view files** (only `resources/views/app.blade.php` shell) |
| Validation | FormRequest classes with `rules()`, `messages()`, `attributes()` |
| Responses | Inertia render / redirect with `with()` flash; errors via `->withErrors()` |
| DI style | Constructor injection for all dependencies |
| Imports | `use` statements, fully qualified, alphabetized-ish, grouped by vendor then app |

---

## Phase 6 — Self-Review Checklist (run before presenting ANY backend change)

- [ ] Module ServiceProvider registered in `bootstrap/providers.php`?
- [ ] Controllers extend `App\Http\Controllers\Controller`?
- [ ] Business logic in Services/Actions, controller methods thin?
- [ ] All data access through Repository interfaces (no raw Eloquent chains in controllers)?
- [ ] Cross-module access only via repository interfaces, actions, or services (no direct bypass)?
- [ ] No new Blade view files?
- [ ] No new composer/npm package without explicit user approval?
- [ ] Naming matches the Convention Map?

---

## Absolute Rules (never violate)

1. Business logic in a controller = VIOLATION. Move it to the module's Service/Action.
2. Raw Eloquent query chains / `DB::` access in a controller = VIOLATION. Use the module's Repository interface.
3. Instantiating a concrete repository directly = VIOLATION. Resolve the interface.
4. Creating a Blade view = VIOLATION. The project is Inertia + Vue only.
5. Route outside the module's `routes.php` = VIOLATION (shared portal routes stay in `routes/web.php`).
6. New module not registered in `bootstrap/providers.php` = VIOLATION (it silently won't work).
7. New external package without asking the user = VIOLATION.
8. Introducing a new architectural pattern not described in this skill = VIOLATION. Ask first.
