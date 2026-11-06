# Retail Inventory Platform

Laravel + Livewire + Alpine + Bootstrap + PostgreSQL. Modular monolith; domain code lives in
`app/Domain/{Catalog,Inventory,Pos,Reconciliation,Reporting,Identity}`.

## Requirements
- `docs/PRD-retail-inventory.md`: user stories US-1.xx with acceptance criteria, NFR targets (§7), RBAC matrix (§4.3). Every feature test maps to an AC.
- `docs/Architecture.md`: layering rules (AR-xx), integration rules (IN-xx), and the task plan T{m}.{nn}. Before starting a task, read its "Selesai jika" checklist; a task is done only when every item and the global DoD (§12) are met.

## Workflow (rules of engagement)
- Follow `WORKFLOW.md` in every session: the green/yellow/red permission zones, the stop conditions, the definition of done, and the report format. Only Salman edits that file.
- When unsure which zone an action belongs to, treat it as yellow and ask. Never mark a task `done` yourself; `review` is the highest status you set.

## Model and effort
- Classify each task on two separate axes using `MODEL-POLICY.md` and state both in the plan: difficulty (Easy / Medium / Hard, picks model and effort) and risk (R1–R4, picks verification depth and minimum effort).
- Verify with deterministic tools (tests, Pint, Larastan, `inventory:verify`) at the depth the risk requires; R4 also needs an independent `/review-security`. Paste the results.
- On failure: diagnose from the exact error, make one targeted fix, then escalate one tier. Limits: 2 attempts per tier, 2 escalations, 6 attempts total, then stop and ask.
- Delegate to `tier-easy` / `tier-medium` / `tier-hard` only when the benefit exceeds the overhead. You cannot switch your own session model: if the session does not match, suggest the exact `/model` and `/effort` command and wait.
- Never assume a model is available (check `/model`), never enable paid usage without approval, never invent costs, token counts or test results. Record each finished task in the "Log model" of `TODO.md`.

## Progress (project memory)
- `TODO.md` is the single record of progress: task status, issues, client questions, decisions, handoff. Read its sections 2–4 and the open issues at the start of every session.
- Work only on tasks in the "Siap dikerjakan" list unless told otherwise. Set the task to `doing` before coding.
- Mark a task `done` only when every "Selesai jika" item and the global DoD are met; then update `Kriteria`, `Aktual`, `Selesai` and run `php scripts/todo-summary.php`. Update `TODO.md` in the same PR as the code.
- Never delete rows or change IDs/estimates; new scope or estimate changes become an issue of type `change`. Rewrite the Handoff section and add a Log line at the end of each session.
- `TODO.md` lives in the client's repo: no rates, contract values, secrets or client data in it.

## Commands
- Test: `php artisan test` (Pest)
- Lint: `./vendor/bin/pint` · Static analysis: `./vendor/bin/phpstan analyse`
- Ledger integrity check: `php artisan inventory:verify`
- Progress summary: `php scripts/todo-summary.php` (`--check` to validate)

## Non-negotiables
- Stock never changes except through the `PostStockMovement` action. See the `inventory-ledger` skill.
- Every transactional table has `location_id`; every query on it is location-scoped. New transactional table: model uses `BelongsToLocation`, migration calls `LocationGuard::attach()` (ADR-003). Write location data only through the model or `Model::query()->insert()`, never raw `DB::table()->insert()`.
- Quantities `numeric(14,3)`, money `numeric(14,4)`, timestamps `timestamptz`. Never float.
- Business logic lives in Action classes, not controllers or Livewire components.
- UI uses Bootstrap classes and shared Blade components in `resources/views/components/`; no Tailwind. Bootstrap JS widgets (modal, dropdown, toast, tooltip) are driven by Alpine or wrapped in `wire:ignore`; Livewire pagination uses the `bootstrap` theme.
- Demo data comes from seeders only. Never connect to a live store or live POS.
- Repo and infrastructure belong to the client; never commit secrets or client data.

## UI reference (`basalt/`)
- `basalt/` is the Basalt admin template, a local visual reference only. It is not part of the app and must never be committed to the client repo (add `/basalt/` to `.gitignore` when the repo is created). Its license has not been checked yet; do not copy assets or styles into the app until it is.
- Rebuild screens as Blade/Livewire components following the rules above; do not copy whole pages or its page-level JS. Its `AGENTS.md` does not apply to this project.
- Never edit its compiled CSS (`assets/basalt/css/`); edit the SCSS sources if anything.
