---
name: session-coordination
description: "ALWAYS load at session start. Lightweight coordination for a solo developer who may run 2+ opencode windows in parallel on the same branch: prevents two windows from editing the same file via a per-branch claim table in SESSION-STATUS.md (untracked, local-only)."
---

# Session Coordination (solo, ringan)

This is a **solo project**, but opencode sessions can still run in parallel (e.g. one window per task on the same branch). Sessions are fully independent — there is no automatic lock between them. This skill is a lightweight protocol so two windows never clobber the same file.

Coordination is **scoped per branch, per machine**. Only the files you are actively working on matter.

---

## Bootstrap (run at session start)

1. If `SESSION-STATUS.md` does not exist in the project root: create it with the skeleton below and add `SESSION-STATUS.md` to `.git/info/exclude` (local-only ignore — the file must NEVER be committed).
2. Determine the active branch: run `git branch --show-current`.
3. Read the file, locate the `## Branch: <active-branch>` section. If it does not exist yet, create it.
4. Register this session in its branch section: `#### Sesi <marker>` with `Tugas` and `Status: in-progress`. Use a distinct marker (`Sesi A`, `Sesi B`, ...) — ask the user if more than one session is active.

### Skeleton

```markdown
# Status Sesi OpenCode (lokal — jangan commit file ini)

## Branch: <nama-branch>
### Klaim File
| File | Sesi | Sejak |
|---|---|---|
#### Sesi A
- Tugas: <deskripsi singkat>
- Status: in-progress
```

---

## File claims (prevent collisions)

1. **Before editing ANY file**: check the claim table in the active branch section. Read it again right before writing — never trust a stale read.
2. If the file (or its parent directory) is claimed by another session and the claim is **not stale** (less than 4 hours old): **STOP. Do not edit that file.** Report the conflict to the user and work on something else.
3. If the file is free (or the claim is stale, see rule 5): add a row `| <file-or-dir> | <marker sesi> | <ISO timestamp> |` BEFORE starting to edit.
4. When done with the file: remove its row from the table.
5. **Stale claims**: a claim older than 4 hours is considered expired (the owning session crashed or was abandoned). It may be taken over: rewrite the row with the current timestamp and report the takeover to the user.
6. When a session finishes or is abandoned: mark `Status: selesai` and release ALL of its claims.

---

## Rules

- The status file is a shared board between windows — write it concisely, no long commentary.
- Never commit `SESSION-STATUS.md`. It is personal and local (see `.git/info/exclude`).
- If a concurrent write is detected (another session updated the file since you read it), re-read the file and re-apply your changes — only touch your own section.
- Never force through a concurrent git operation: if `.git/index.lock` exists, wait and inform the user instead of deleting the lock.
- This protocol disciplines opencode sessions only. Manual edits by the dev bypass it — the claims protect the file, the dev protects the claims.
