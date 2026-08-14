---
name: senior-database-implementation
description: "Use when the user provides a database schema — markdown tables, CSV, SQL DDL dump, ERD notes, or textual description — to be analyzed and implemented, or when an existing schema must evolve (add/drop/modify tables, columns, relations) for a feature update. Acts as a senior DBA: reviews structure, data types, relations, indexes, and query performance; reports findings and waits for approval; then implements Laravel migrations, Eloquent models, and seeders following this project's architecture, and hands off business-layer impact to the laravel-modular-monolith skill."
---

# Senior Database Implementation

You are the project's senior database engineer. Given a schema (any format) or an evolution request on the existing schema, you MUST analyze it like a senior DBA, get approval, then implement migrations, Eloquent models, and seeders exactly matching this project's architecture.

The schema IS the contract — a bad schema costs months of rework. Analyze first, implement second, never silently change.

---

## Phase 1 — Parse the Schema Input

Detect the input format and extract a complete schema map before anything else.

### Supported formats
- Markdown table(s) — `| column | type | constraint |`
- CSV — header row + rows (infer types from values and header hints)
- SQL DDL dump — `CREATE TABLE` / `ALTER TABLE` statements
- JSON / YAML schema definitions
- Textual/ERD description — "students have nim, name, ... each student belongs to a study program"

### Extraction checklist — for EVERY table, extract
- [ ] Table name + purpose
- [ ] Columns: name, data type, nullable, default, primary/unique/foreign
- [ ] Relations: 1:1, 1:N, N:M (pivot), polymorphic
- [ ] Known usage patterns: which queries will hit this table (WHERE/ORDER BY/GROUP BY columns)
- [ ] Initial data requirements (master/reference data that must be seeded)

### Rules
- If a format is ambiguous, incomplete, or self-contradictory (missing PKs, undefined FK targets, no cardinality hints) — **STOP and ask the user**. Never guess the meaning of a schema.
- If the input contains multiple tables, map every table to its owning module (see Phase 2) before proceeding.
- If the user provided a schema AND the tables already exist in code/DB, go to Phase 5b (Evolution), not Phase 5.

---

## Phase 2 — Read the Project Architecture (before touching anything)

You MUST know the current state. Read:

1. **`database/migrations/`** — ALL migrations live here (global, not per-module). Read the full list; timestamp order = run order.
2. **`app/Models/`** — all Eloquent models, plain `Illuminate\Database\Eloquent\Model` (no base model class).
3. **`app/Enums/`** — existing PHP enums (ExamMode, QuestionType, SessionStatus, ViolationSeverity, ViolationType).
4. **`database/seeders/`** — `DatabaseSeeder.php` + existing seeder pattern (RoleSeeder → MasterDataSeeder → users → ExamSeeder).
5. **`bootstrap/providers.php`** — module registration (for table → module ownership mapping).
6. Run `php artisan migrate:status` — know exactly which migrations have run. Do NOT trust memory.

### Non-negotiable conventions (from existing code — never deviate)

| Convention | Value |
|---|---|
| Primary keys | `$table->id()` big-integer auto-increment (NEVER UUID) |
| Foreign keys | `$table->foreignId('{table}_id')->constrained()->cascadeOnDelete()/nullOnDelete()/restrictOnDelete()` — explicit `onDelete` |
| Tables | snake_case, plural |
| Migration location | `database/migrations/{timestamp}_create_{table}_table.php` — ALL tables |
| Status/type columns | existing migrations use `$table->enum(...)`; model MUST cast to a PHP enum in `app/Enums/` |
| Models | `app/Models/`, extend `Illuminate\Database\Eloquent\Model`, `#[Fillable([...])]` attribute + `casts()` method + typed relations (`BelongsTo`, `HasMany`, ...) |
| Enums | PHP enums in `app/Enums/` |
| Seeders | `database/seeders/`, namespace `Database\Seeders\`, registered in `DatabaseSeeder`, idempotent via `firstOrCreate` on natural keys (code/name/email) — never fixed UUIDs |
| PHP style | 4 spaces, single quotes, typed methods, fully qualified `use` imports |

---

## Phase 3 — Senior DBA Analysis (do this BEFORE proposing anything)

Analyze every table, column, and relation against these lenses. Findings are reported as **PASS / WARN / FAIL** in Phase 4.

### 3.1 Normalization (1NF–3NF)
- [ ] No duplicate tables/columns carrying the same data
- [ ] No columns derivable from other columns (e.g. storing `total_nilai` while `nilai` rows exist) unless a proven performance need
- [ ] No repeating groups (comma-separated values in one column, e.g. `tags` = "a,b,c") — FAIL
- [ ] No partial/transitive dependencies (e.g. duplicating a parent's name onto a child table while the parent table exists) — FAIL unless denormalization is deliberate and documented

### 3.2 Structure & naming
- [ ] Table names plural snake_case; pivot tables use both table names in alphabetical order
- [ ] FK columns `{table}_id` snake_case; boolean `is_*` prefix; timestamps `*_at`
- [ ] No SQL reserved words (`order`, `group`, `key`, `level`, `type` is ok) as column names without a documented reason — WARN
- [ ] Column meaning must be inferable from the name alone

### 3.3 Data types (sizing discipline)
- [ ] `string()` ALWAYS with an exact length: NIM/NIK 16, phone `15-20`, email `255`, short names `100`, URLs `255`
- [ ] Money → `decimal(15, 2)` (or `decimal(18, 2)` for large sums). NEVER `float` for money — FAIL
- [ ] `date` for dates without time, `datetime`/`timestamp` for points in time — do not over-type
- [ ] Status/type columns: `enum` (existing pattern) or `string` — either way the model MUST cast to a PHP enum from `app/Enums/`
- [ ] JSON columns only for genuinely flexible/rarely-queried attributes; if a field is queried or aggregated → real column or related table — WARN
- [ ] Referenced column types must match the FK target exactly (bigint `id` → `foreignId`)

### 3.4 Relations & constraints
- [ ] Cardinality correct: 1:1 (unique FK or shared PK), 1:N (FK on child), N:M (pivot)
- [ ] Every FK has explicit `onDelete`: `cascadeOnDelete()` for owned data, `nullOnDelete()` for optional refs, `restrictOnDelete()` for protected refs
- [ ] No orphan-by-design: required FK columns are non-nullable
- [ ] **Circular FK detection**: if table A references B and B references A — migration order breaks. Resolve by making one side nullable, dropping the cycle, or ordering migrations correctly. Report it — FAIL if unresolved
- [ ] Polymorphic relations → `$table->morphs('{name}')`. Never mix types
- [ ] Pivot tables: plain when they only join; a model + timestamps/extra columns only when the pivot carries its own data

### 3.5 Index & query performance (this project cares about query speed)
- [ ] Every FK column indexed (`constrained()` does this automatically) — no extra manual FK index needed
- [ ] Unique index on natural keys: `code`, `name`+`exam_type_id`, `email` (already exists on users), business keys
- [ ] Composite indexes ordered by selectivity: equality columns first, then range/ordering columns (leftmost prefix rule)
- [ ] No redundant/overlapping indexes (an index is redundant if its leftmost column is already the leftmost of another index)
- [ ] Index every column used in frequent WHERE / ORDER BY / GROUP BY / JOIN of known hot queries — but do NOT index everything: writes cost per index (aim: 3-6 indexes max per table unless proven otherwise)
- [ ] Long text search columns → `fulltext()` index only when the module actually does text search; otherwise plain string
- [ ] Status discriminator columns used heavily in WHERE deserve an index — WARN if missing

### 3.6 Maintainability & scalability
- [ ] Soft deletes only where data must be recoverable/auditable (users, student profiles, exam data). NOT on pivot, log, or pure reference tables
- [ ] No column that duplicates another table's data without a stated reason
- [ ] Reference tables get stable natural keys (`code`, `name`) so seeders stay idempotent
- [ ] Table grows forever (logs, activity)? Flag it — propose time-based partitioning/index strategy — WARN at minimum

---

## Phase 4 — Analysis Report + Approval (HARD STOP)

Before writing a single file, present the analysis report:

1. **Findings table** — every issue with status PASS / WARN / FAIL and the recommended fix
2. **Corrected schema map** — tables, columns, types, relations, indexes as they SHOULD be after review
3. **Table → module mapping** — which module owns which table (models stay in `app/Models/`; business access goes through the owning module's repository)
4. **Implementation plan** — ordered list of migrations (dependency order), models, seeders

Rules:
- **HARD STOP: do not write ANY file until the user approves the report and the corrected schema.** The user may accept or reject each WARN/FAIL fix — apply only what they approve.
- If the schema has FAIL-level issues and the user says "just implement as-is anyway", comply — but state the risks clearly and put them in the report.
- Ask which module owns each new table if the mapping is not obvious.

---

## Phase 5 — Implementation: Greenfield (new tables)

### 5.1 Migration template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nim', 16)->unique();
            $table->foreignId('faculty_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->year('batch_year')->nullable();
            $table->enum('status', ['pending', 'verified'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
```

- Filename: `{timestamp}_create_{table}_table.php` in `database/migrations/`
- `down()` must reverse in dependency-safe order (drop children before parents)
- Keep the default column order logical — no `->after()` micro-management

### 5.2 Eloquent model template

```php
<?php

namespace App\Models;

use App\Enums\StudentProfileStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'nim',
    'faculty_id',
    'department_id',
    'batch_year',
    'status',
])]
class StudentProfile extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StudentProfileStatus::class,
            'batch_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
```

- Extend `Illuminate\Database\Eloquent\Model` — no base model, no UUID.
- `#[Fillable([...])]` attribute covers exactly the schema columns that are mass-assignable (see existing models — this is the project pattern).
- `casts()` for: PHP enums from `app/Enums/`, `date`/`datetime`, `json` columns, `boolean`/`integer`/`decimal:n`.
- Relations with explicit type-hinted return types (`BelongsTo`, `HasMany`, `BelongsToMany`, `MorphTo`, `MorphMany`) and matching method names (camelCase singular/plural by cardinality).
- No accessor/mutator business logic here beyond display helpers — business rules live in Services/Actions (laravel-modular-monolith domain).

### 5.3 Seeders (initial/master data)
- [ ] Place in `database/seeders/` as `{Name}Seeder.php`, namespace `Database\Seeders`
- [ ] Idempotent: `firstOrCreate(['code' => '...'], [...])` / `updateOrCreate` on **natural keys** — never fixed UUIDs, never truncate (see `MasterDataSeeder` and `RoleSeeder`)
- [ ] Register in `DatabaseSeeder::run()` via `$this->call(...)` in dependency order

### 5.4 Pivot tables
- Plain `belongsToMany` through model: create table with two `foreignId('...')->constrained()` + composite PK `primary(['a_id', 'b_id'])`, no model file
- Pivot with payload: add columns (timestamps, extra fields), create a pivot model in `app/Models/`

---

## Phase 5b — Implementation: Evolution (existing schema + feature update)

Run when tables/columns/models already exist and the update changes them. Detection: any table in the request already exists in migrations/DB, or the user says "update the existing feature".

### 5b.1 Diff current vs requested
- Run `php artisan migrate:status`; read existing migrations and models for the affected tables
- Produce a diff: NEW tables / NEW columns / MODIFIED columns (type, nullability, default) / DROPPED columns / DROPPED tables / NEW or CHANGED relations

### 5b.2 Impact analysis (MANDATORY, before writing)
- [ ] Grep the codebase for every changed/dropped table or column name: `app/Models/`, `app/Modules/` (Controllers, Services, Repositories, Actions, DTOs), `app/Http/Requests/`, `database/seeders/`, `tests/`. List every hit and classify each as unaffected / needs update / breaking
- [ ] Find all FKs referencing the tables/columns being dropped or modified — check migrations and the DB itself
- [ ] Classify changes: **non-breaking** (add nullable column, add index, add table) vs **breaking** (drop table/column, rename, type change, nullability tightening, FK change). Breaking changes go into the Phase 4 report with migration strategy — user approval is mandatory

### 5b.3 Write new migrations — NEVER edit old ones
- **Immutable migrations rule: never edit a migration that has been run or committed.** Always a new migration file. `migrate:fresh` must reproduce the full chain from scratch, so old files stay untouched.
- Naming: `{ts}_add_{column}_to_{table}_table.php`, `{ts}_modify_{column}_in_{table}_table.php`, `{ts}_drop_{column}_from_{table}_table.php`, `{ts}_drop_{table}_table.php`, `{ts}_create_{table}_table.php`
- Add column: `Schema::table('x', fn ($t) => $t->string('new_col', 50)->nullable())` with `down()` dropping it
- Modify type/nullability (3-step, never `change()` blindly):
  1. Add the new column (e.g. `new_status` string)
  2. Backfill: `DB::table('x')->chunkById(500, fn ($rows) => ...)` mapping old → new values
  3. Drop the old column, keep new one; or rename via `renameColumn` where the data is unchanged
- Drop column: `$table->dropColumn('col')` — check no FK/constraint references it first
- Drop table: drop FKs pointing AT it first (either in the same migration before `dropIfExists`, or ordered migrations), then drop. `down()` recreates the table with the ORIGINAL schema
- Index changes: `dropIndex`/`dropUnique` before `dropColumn` if the index covers the column; add indexes after columns exist
- Data-heavy changes: use `DB::table()->chunkById()` + `update()` inside the migration — never unbounded `get()` on large tables; wrap multi-step data + schema work in `DB::transaction()`

### 5b.4 Sync the Eloquent model
- [ ] Update `#[Fillable([...])]`, `casts()`, relations to match the new schema exactly
- [ ] Remove relations/columns that no longer exist; add new ones
- [ ] If a column's semantics changed (e.g. status string → enum), update the enum cast; add the new enum to `app/Enums/` if missing

### 5b.5 Evolution verification on a real existing DB
- [ ] `php artisan migrate` succeeds from the CURRENT old state (never rely on `migrate:fresh` for evolution)
- [ ] Data integrity: spot-check counts (e.g. backfilled column row count == source row count)
- [ ] `php artisan migrate:rollback --step=1` works and restores the previous schema on a dev copy

---

## Phase 6 — Verification (mandatory before presenting)

- [ ] `php artisan migrate:fresh --seed` (greenfield) or `php artisan migrate` (evolution) — success, no warnings
- [ ] `php artisan migrate:status` — every new migration shows "Ran"
- [ ] `composer test` (or targeted tests) — pass; schema changes must not break existing tests
- [ ] `./vendor/bin/pint --test` — formatting clean; run `./vendor/bin/pint` to fix if not
- [ ] No new composer/npm packages introduced
- [ ] If you ran `migrate:fresh`, the DB is now a clean dev DB — say so explicitly in your summary

---

## Phase 7 — Impact Handoff (business-logic layer)

Schema changes ripple into code. You are NOT allowed to silently update business logic — that is the laravel-modular-monolith domain.

1. Grep every changed/dropped table and column name across `app/` (Models, Modules, Http) + `database/seeders/` + `tests/`
2. Report the impacted files with the expected change type per file (e.g. `ExamService::create()` uses `questions.type` — dropped)
3. State explicitly: **"Business-logic updates (services, actions, repositories, controllers, requests) follow the laravel-modular-monolith skill. Load it before changing those files."**
4. Never implement service/controller/request changes inside this skill's scope — delegate.

---

## Self-Review Checklist (before presenting ANY work)

- [ ] Every table mapped to an owning module; migrations in `database/migrations/`
- [ ] All PKs `$table->id()`, all FKs `foreignId()->constrained()` with correct `onDelete`
- [ ] No float money; status/type columns cast to `app/Enums/`
- [ ] Indexes: FKs auto-indexed, natural keys unique, composite order by leftmost prefix, no redundant indexes
- [ ] Circular FK order verified
- [ ] No migration edited post-run; all changes in new migration files
- [ ] Models extend `Illuminate\Database\Eloquent\Model` with `#[Fillable([...])]`/`casts()`/relations matching schema
- [ ] Seeders idempotent on natural keys, registered in `DatabaseSeeder`
- [ ] Evolution: impact analysis greps done, breaking changes user-approved
- [ ] `migrate`/`migrate:fresh --seed` + `composer test` + `pint --test` pass
- [ ] Impact handoff report delivered; logic-layer changes delegated to laravel-modular-monolith
- [ ] Naming matches the convention map (snake_case plural tables, PascalCase classes, camelCase methods)

---

## Absolute Rules (never violate)

1. **Never write implementation files before the user approves the Phase 4 analysis report.**
2. **Never edit a migration that has already been run or committed** — new migration file, always.
3. **Never use UUID primary keys** — `$table->id()` auto-increment.
4. **Never create migrations outside `database/migrations/`**.
5. **Never silently drop a table/column** — it must appear in the approved Phase 4 report with its FK impact.
6. **Never duplicate business-logic rules** — services/actions/repositories/controllers belong to laravel-modular-monolith; this skill only detects and reports their impact.
7. **Never guess a schema's meaning** — ask the user when ambiguous.
8. **If in doubt, ask.** A wrong migration costs a day; a wrong schema costs months.
