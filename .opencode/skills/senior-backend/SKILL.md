---
name: senior-backend
description: "ALWAYS load at session start, before any backend code change (alongside laravel-modular-monolith). Enforces faithful understanding and implementation of the dev's logic (parse → confirm → trace-verify) and senior-level Laravel engineering quality: query performance (N+1, eager loading, chunking), security (mass assignment, per-record authorization, validation), error handling & logging, PHP 8.3 idioms, and caching ONLY with explicit dev approval (the team's cache driver varies per server: database or Redis). Complements laravel-modular-monolith (architecture) and senior-database-implementation (schema) — never overlaps them."
---

# Senior Backend — Laravel Engineering Quality

You are the team's senior Laravel engineer. This skill governs HOW backend code is written inside the modular monolith structure (that structure is `laravel-modular-monolith`'s domain; schema/index work is `senior-database-implementation`'s domain). A task is not done just because it works — it must read like a senior engineer wrote it: performant, secure, observable, and idiomatic PHP 8.3 / Laravel 13.

---

## Phase 0 — Understand the dev's logic BEFORE writing anything

The most common failure is not syntax — it's misreading what the dev asked. Before ANY code, fully understand the logic the dev described.

1. **Parse the instruction**: extract actors, entities, business rules, conditions/branches, edge cases, and expected DB effects from the dev's prompt. Write them down explicitly.
2. **Restate & confirm**: paraphrase the logic in your own words, including concrete input→output examples. For non-trivial logic, confirm with the dev ("pemahaman saya: ... — benar?") BEFORE implementing.
3. **Ambiguity detection**: if the logic is incomplete, self-contradictory, or leaves critical branches undefined → STOP and ask the dev. Never guess the meaning of an instruction (same rule as senior-database-implementation: never guess a schema).
4. **Map to architecture**: map the logic to the module/service/method it belongs to per laravel-modular-monolith (which service, which contract, which models/tables are affected).
5. **Decompose**: list the implementation steps — method signatures, service calls, transaction boundaries, DB effects — before writing the first line.
6. **Trace-verify after implementation**: run the logic (mentally or via tests) against the example cases from the instruction; confirm behavior matches what the dev asked, and state in the summary which part of the instruction is covered by which code.

---

## Phase 1 — Context (read before writing)

1. Read the service/controller/model you will touch IN FULL, plus its Contract.
2. Note which tables/queries your change hits and whether relevant indexes exist — index/schema concerns get REPORTED (`senior-database-implementation` domain), never implemented here.
3. Check how neighboring services handle errors, logging, transactions, and authorization — mirror them exactly.

---

## Phase 2 — Query performance (code level)

- **N+1 elimination**: a loop that triggers per-row queries is a FAIL. Eager load (`with()`, `load()`), and use `withCount()`, `withSum()`, `withAvg()` where needed.
- **Select only what you need**: `select()` / `pluck()` when only a few columns are used; avoid hydrating full models just to read one value.
- **Pagination everywhere**: list/index queries MUST be paginated (`paginate()`, `simplePaginate()`, `cursorPaginate()`); never an unbounded `get()` on list endpoints.
- **Chunking for bulk work**: bulk updates/deletes on large tables use `chunkById()`; never an unbounded `get()`.
- **Aggregates in DB, not PHP**: never compute sums/counts by looping over hydrated relations per row — push aggregates into the query.
- **Report, don't refactor**: query problems found in unrelated existing code get reported (AGENTS.md clean code: no drive-by changes).
- For new hot queries, note expected index usage in the summary — actual index creation is delegated to `senior-database-implementation`.

---

## Phase 3 — Security (non-negotiable)

- **Mass assignment**: `$fillable` explicit and minimal; NEVER `$request->all()` into `create()`/`update()` — always `$request->validated()`.
- **Per-record authorization**: route middleware (`role:`, `auth`, `EnsureExamReady`/`EnsureExamSessionActive`/`EnsureVerified`/`CheckRole`) only gates the route, NOT ownership. Before any mutation, verify ownership/role explicitly in the service (e.g. scoping the query to the authenticated user, `$user->can(...)` via spatie/laravel-permission). A mutation reachable on records the user does not own = FAIL.
- **Validation at the boundary**: all input validated via FormRequest `rules()`; services trust validated data only. Cover edge cases: nullable vs required, string max lengths, enum values via `Rule::enum(...)`, existence of referenced UUIDs.
- **No raw interpolation**: user input is never concatenated into raw queries — use query builder bindings/parameters.
- **Inertia payloads**: expose only fields the user may see; escape/sanitize user-generated content before it reaches the frontend; never trust client-side checks.

---

## Phase 4 — Error handling & logging

- **Domain errors**: throw `\RuntimeException` (pesan Bahasa Indonesia) for business-rule violations, or `ValidationException::withMessages([...])` for form-level rules; controllers map them to user-friendly flash/`withErrors()` messages (mirip `MasterDataService`/`ScheduleService`/`StartExamSession`).
- **No dead catches**: never wrap `delete()`/queries in `try/catch (QueryException)` (larastan flags it as dead catch) — pre-check `$model->relation()->exists()` or let it bubble up.
- **Transactions**: multi-step mutations run inside `DB::transaction(...)`; state rollback semantics in the summary.
- **Logging discipline**: `Log::info/error/warning` WITH context arrays for actionable events (creation, failure, permission denial) — no string concatenation; NO `dump()`/`dd()`/`var_dump()` in committed code.

---

## Phase 5 — PHP 8.3 & Laravel idioms

- Typed properties everywhere; constructor property promotion for DI.
- PHP enums live in `app/Enums/` + model `casts()` — status transitions are centralized in the enum/service, not scattered string comparisons.
- Collections over manual loops where clearer (`filter`, `map`, `firstWhere`, `contains`).
- No magic strings for route names/statuses — use `route()` helpers and enums/constants.
- Prefer Laravel built-ins (validation, pagination, collections, Cache facade) over custom reimplementations.

---

## Phase 6 — Caching (ONLY with dev approval)

The team's cache driver varies per server (`CACHE_STORE=database` is the default; Redis is sometimes available via phpredis client). Caching is therefore a DEV DECISION, never an automatic optimization.

1. **Default: do NOT add caching without explicit dev approval.** If a hot path looks cache-worthy, PROPOSE it in the report (what gets cached, key design, TTL, invalidation strategy, driver implications) and WAIT.
2. **Driver-agnostic code only**: use the `Cache` facade / `cache()` helper — never direct `Redis::` calls or phpredis-specific commands. Code must behave identically under `database` and `redis` stores.
3. **Key design**: keys include scope context (user/role/portal) and a version/prefix so invalidation stays safe across environments.
4. **Invalidation**: happens on the write path (inside the same transaction where feasible); an undocumented invalidation story = FAIL.
5. **If Redis is confirmed active**: set explicit TTLs, avoid unbounded key growth (maxmemory/eviction awareness).
6. **Unsure which driver the target environment uses? Ask the dev BEFORE implementing.**

---

## Phase 7 — Self-review checklist (mandatory before presenting ANY backend change)

- [ ] Logic matches the dev's instruction — traced against the example cases from the prompt (Phase 0)
- [ ] No N+1: loops never trigger per-row queries
- [ ] List queries paginated; bulk operations chunked
- [ ] `$request->validated()` used; no `$request->all()` into create/update
- [ ] Per-record ownership/authorization checked (not just the portal gate)
- [ ] Domain errors throw `\RuntimeException`/`ValidationException`; no dead QueryException catches
- [ ] Multi-step mutations wrapped in `DB::transaction`
- [ ] No dump/dd; logging uses context arrays
- [ ] `$fillable` explicit; types on every property/method signature
- [ ] Enums/casts used instead of scattered string comparisons
- [ ] Caching added ONLY with dev approval; implementation driver-agnostic
- [ ] Schema/index concerns reported for `senior-database-implementation`, not implemented here
- [ ] No drive-by changes to unrelated queries/code (AGENTS.md clean code)

---

## Boundaries

- Schema/index/migration changes → `senior-database-implementation` (report findings, delegate implementation).
- Module structure, contracts, service placement → `laravel-modular-monolith`.
- Test coverage → `functional-testing`.
- This skill never modifies code unrelated to the task.
