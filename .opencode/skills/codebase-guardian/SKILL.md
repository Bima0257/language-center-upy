---
name: codebase-guardian
description: "ALWAYS use at session start and every code-change request. Enforces full codebase read-before-touch, strict convention adherence, minimal-diff changes, zero tolerance for slop, and a no-broken-build guarantee."
risk: safe
source: community
date_added: "2026-05-12"
---

# Codebase Guardian

You are operating under **strict codebase discipline**. Before writing or changing a single line of code, you must complete the full protocol below. No shortcuts. No assumptions. No "I'll just quickly fix this." Token cost does not matter — correctness and consistency do.

---

## Phase 1 — Full Codebase Read (mandatory, every session)

**Read the entire codebase before touching anything.** This is non-negotiable.

### Step 1.1 — Map the project tree
```bash
find . -type f | grep -v -E '(node_modules|\.git|dist|build|__pycache__|\.next|vendor|\.cache)' | sort
```
Read this output completely. Understand every directory, every file, what each layer of the project is doing.

### Step 1.2 — Read ALL source files
For every source file found in Step 1.1 (not just files related to the task), read it in full. Use whatever read/view tool is available. Do NOT skip files because they seem unrelated. You must understand the complete picture before you can make safe changes.

**Prioritised reading order:**
1. Config / manifest files first (`package.json`, `pyproject.toml`, `Cargo.toml`, `go.mod`, `.eslintrc`, `.prettierrc`, `tsconfig.json`, `setup.cfg`, `Makefile`, etc.)
2. Entry points and root files (`main.*`, `index.*`, `app.*`, `server.*`)
3. Shared / core utilities and base classes
4. Domain modules, features, services
5. Tests (to understand expected behaviour and patterns)
6. Documentation (`README.md`, `CONTRIBUTING.md`, `ARCHITECTURE.md`, `CONVENTIONS.md`, `STYLE.md`, etc.) — these are **law**

### Step 1.3 — Build your Convention Map
After reading everything, write down (internally, in your working context) the answers to each of these before proceeding:

| Convention | What the project uses |
|---|---|
| Language & runtime version | |
| Indentation (tabs vs spaces, width) | |
| Quote style (single / double / backtick) | |
| Semicolons (yes/no) | |
| Line ending style | |
| Max line length | |
| File naming convention (kebab-case / PascalCase / snake_case) | |
| Folder / module naming convention | |
| Variable naming convention | |
| Function naming convention | |
| Class / type / interface naming convention | |
| Constant naming convention | |
| Export style (named / default / barrel `index.*`) | |
| Import ordering rules | |
| Comment style (JSDoc / inline / docstring / none) | |
| Error handling pattern (try/catch / Result type / error-first callbacks / exceptions) | |
| Async pattern (async/await / Promise chains / callbacks / coroutines) | |
| State management pattern (if applicable) | |
| Test framework & test file naming convention | |
| Linter / formatter in use (ESLint, Prettier, Black, Ruff, Clippy, gofmt, etc.) | |
| Any other project-specific patterns observed | |

**If any of the above is ambiguous** — look at 3+ existing examples in the codebase and infer the pattern. Do not invent your own.

---

## Phase 2 — Change Planning (mandatory before writing code)

Once you fully understand the codebase, plan the change with these constraints:

### 2.1 — Minimal diff principle
Make the **smallest possible change** that satisfies the requirement. Do not:
- Refactor unrelated code while you're in a file
- Rename things that weren't asked to be renamed
- Reformat code blocks you're not changing
- Add abstractions "while you're at it"
- Remove comments or dead code unless that is the explicit task

If you notice other problems while reading, **note them** but do not fix them silently. Report them to the user instead.

### 2.2 — Convention checklist (run mentally on every planned change)
Before writing any code, check each planned addition against the Convention Map from Phase 1:

- [ ] Names follow the project's exact conventions (file, variable, function, class, constant)
- [ ] Indentation, spacing, and formatting match existing files exactly
- [ ] Import/export style matches
- [ ] Error handling matches the project pattern
- [ ] Async/await or promise style matches
- [ ] Comment style matches (or no comment where project uses none)
- [ ] No new dependencies introduced without explicit user approval
- [ ] No new files created in a location that breaks the existing folder structure
- [ ] No new patterns introduced that don't already exist in the codebase
- [ ] Tests (if any exist) are updated or added in the same style as existing tests

### 2.3 — Risk scan
For every file you plan to touch, ask:
- What else imports or depends on this file?
- Does my change alter any exported interface, type signature, or function signature?
- Could my change cause a runtime error, type error, or test failure elsewhere?
- Am I changing shared utilities that affect the whole app?

If any answer is "yes" or "maybe", trace the dependency chain and check all affected call sites before writing.

---

## Phase 3 — Write the Code

Now write the code. Rules:

1. **Mirror the style of the surrounding code exactly.** If the file uses 2-space indentation, so does your addition. If the file uses `const` for everything, so do you. If functions have JSDoc comments, yours does too.

2. **No slop.** Slop is:
   - Generic variable names (`data`, `result`, `temp`, `item`) where the codebase uses descriptive names
   - Inconsistent casing (e.g., `userId` in a project that uses `user_id`)
   - Copy-pasted boilerplate that doesn't fit the project style
   - Inline magic numbers/strings where the project uses constants
   - Missing error handling where the project always handles errors
   - Overly clever one-liners where the project uses readable multi-line code (or vice versa)

3. **No drive-by changes.** Every line you write must be directly caused by the task. If a line wasn't in your plan from Phase 2, don't write it.

4. **Preserve existing comments and documentation.** Do not delete, shorten, or rewrite existing comments unless the task explicitly requires it.

5. **Keep types strict.** If the project uses TypeScript, Go, Rust, or any typed language, do not introduce `any`, `interface{}`, or unsafe casts. Match the type discipline of the surrounding code.

---

## Phase 4 — Self-Review (mandatory before presenting output)

Before returning any code to the user, do a full self-review pass:

### Diff review
- Re-read every line you wrote
- Compare every identifier, every pattern against your Convention Map
- Check every import added is consistent with the project's import style
- Verify no unintended lines changed (whitespace, formatting, etc.)

### Correctness review
- Trace the logic path through your change end-to-end
- Check edge cases: null/undefined/empty, error paths, boundary conditions
- Confirm you haven't broken any existing call sites
- If tests exist: mentally run the relevant tests against your change

### Convention compliance final check
- Read your change as if you were a senior developer on this project seeing it for the first time
- Would they immediately know it was written by someone who read the whole codebase? It must look native.
- Would they raise a PR comment about style, naming, or pattern inconsistency? If yes, fix it before presenting.

---

## Absolute rules (never violate these)

1. **Never write code before completing Phase 1.** If the codebase is large, that's fine — read it all.
2. **Never introduce a new convention** that doesn't already exist in the codebase without explicit user approval.
3. **Never silently change unrelated code.** If you spot a bug elsewhere, report it — don't fix it without asking.
4. **Never guess at a naming convention.** Look at 3+ examples. Use what already exists.
5. **Never break the build.** If you're unsure whether a change will compile/lint/type-check, say so explicitly.
6. **Never skip the self-review phase**, even for "trivial" changes. Most bugs are introduced in "trivial" changes.
7. **Never import a new external library** without asking the user first.
8. **If in doubt, ask.** A clarifying question costs nothing. A production bug costs everything.

---

## How to handle large codebases

If the codebase is very large (hundreds of files), do the following:

1. **Always read all config and manifest files** — no exceptions.
2. **Always read all documentation** (`README`, `CONTRIBUTING`, `ARCHITECTURE`, `CONVENTIONS`, etc.) — no exceptions.
3. **For source files**: Read all files in directories that are relevant to the task. Then read all shared/utility/base directories. Then read all entry points.
4. **For files that seem unrelated to the task**: Still read their names and folder structure. If you can determine they are truly isolated modules (e.g., a separate CLI tool in a monorepo that has nothing to do with the change), you may skip their internals — but note that you skipped them.
5. **Never assume a file is unrelated** without checking its imports and exports first.

The user has explicitly stated they do not care about token cost. Read everything.

---

## Reporting format

When presenting your work, always include:

```
## Convention Map (what I found)
<brief summary of the key conventions you discovered>

## What I changed and why
<list of every file touched, and the specific reason for each change>

## What I intentionally did NOT change
<any related things you noticed but left alone, and why>

## Risks / things to verify
<any concerns about the change that the user should double-check>
```

This reporting format is mandatory. It proves you read the codebase and didn't just hack something in.