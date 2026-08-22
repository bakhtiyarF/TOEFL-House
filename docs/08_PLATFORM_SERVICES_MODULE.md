# 08 — Platform Services Module
### TOEFL House ERP v3 — Build Order

> **Status:** Locked once §12 is confirmed.
> **Depends on:** `01`–`07`. **Resequenced ahead of CRM & Enrollment** — `05` (Academic) and `07` (Finance & Payroll) both already assume a `RuleEngineService` this document is what actually builds.
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** schema, the rule engine and event-bus algorithms (already locked in `02`, formally homed here), routes, acceptance tests. No PHP or TypeScript source.

---

## 1. Objective

Build the cross-cutting capabilities every other module calls rather than reimplements: the rule engine, the domain event bus, the workflow/approval engine, notifications, and the audit log.

## 2. Scope / Non-Goals

**In scope:** everything in §4, plus formally resolving `07_FINANCE_AND_PAYROLL_MODULE.md` §7's correction — the rule engine's algorithm is `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §7, unchanged; this document gives it a home and a schema.
**Not in scope:** the business content of any specific rule category (fee amounts, discount percentages, promotion thresholds) — those are seeded per `02` §7.4 and this module's own §5 additions; this module owns the *engine*, not the *policy*.
**Deliberately generic where v2 already proved the need, deliberately not generic beyond that** — per `01_TARGET_ARCHITECTURE.md` §9's standing rule: this module exists because the rule engine, event bus, and workflow engine are already real, multi-consumer capabilities (Academic *and* Finance & Payroll both need the rule engine; Finance & Payroll's expense approval already needs the workflow engine). That bar — a second real consumer — is what justifies building shared infrastructure here at all.

## 3. Preconditions

IAM, Academic, People & HR, Finance & Payroll complete (all three are already-waiting callers).

---

## 4. Part A — Database Schema

**`rule_definitions`**: id, name, description, category (enum: `fee`,`discount`,`promotion`,`attendance`,`payroll`,`scholarship`,`workflow`,`notification`,`finance`,`academic`), conditions(json), actions(json), priority(int, default 0), is_active(bool), scope_branch_id(FK, nullable — null means org-wide), version(int), last_modified_by, last_modified_at, created_at.

**`rule_versions`**: id, rule_id(FK, cascade), version, conditions(json), actions(json), priority, is_active, modified_by, modified_at. `UNIQUE(rule_id, version)` — a full history every time a rule is edited, not just an overwrite.

**`rule_evaluation_logs`**: id, rule_id(FK), category, branch_id(nullable), matched(bool), context_json, result_json, dry_run(bool), evaluated_at.

**`workflow_definitions`**: id, name, trigger(string — an event type, e.g. `expense.requested`), steps(json array), is_active, timestamps.

**`workflow_instances`**: id, definition_id(FK), entity_type, entity_id (the polymorphic target — e.g. an `expense_requests` row), current_step(int), status(`pending`/`in_progress`/`approved`/`rejected`/`completed`/`cancelled`), branch_id, initiated_by, started_at, completed_at(nullable), payload(json).

**`workflow_history`**: id, instance_id(FK, cascade), step_order, actor, action, notes, timestamp. Every step transition, append-only.

**`automations`**: id, name, trigger, conditions(json), actions(json), is_active, timestamps. Distinct from `rule_definitions` — automations react to triggers with side effects; rules compute values. Don't merge the two tables even though they look structurally similar; they're semantically different and v2 kept them separate deliberately.

**`domain_events`**: id, type, aggregate_type, aggregate_id, payload(json), occurred_at, operator_id(nullable), branch_id, correlation_id(nullable), causation_id(nullable), schema_version(default 1), published(bool), metadata(nullable). The event store — append-only, matches the same event-sourcing shape as Academic's `student_journey_events` (`05` §4.3), applied at the platform level instead of one entity's lifecycle.

**`event_handler_log`**: id, event_id(FK, cascade), handler, success(bool), duration_ms, error(nullable), executed_at. `UNIQUE(event_id, handler)` — a handler runs at most once per event, supporting safe replay/retry without double-processing.

**`event_subscriptions`**: id, event_type, handler(enum: `workflow`,`automation`,`notification`,`webhook`), config(json), is_active, created_at. This is the fan-out table — what happens when an event of type X occurs.

**`notifications`**: id, title, message, date, read(bool), type(`info`/`warning`/`critical`/`success`), user_id(FK, nullable), link(nullable), branch_id(FK, nullable).

**`audit_logs`**: id, operator_id, operator_name, action, date, time, old_value(nullable), new_value(nullable), ip, device, branch_id.

**`system_settings`**: key(PK), value. The generic store behind Finance & Payroll's `daily_saving_percent`/`saving_balance`/`main_account_balance` (`07` §4) — owned here since it's genuinely cross-module configuration, not Finance-specific machinery.

**`pipeline_metrics`**: pipeline, stage, count, conversion_rate, average_time_in_stage, branch_id, computed_at. Composite PK `(pipeline, stage, branch_id)`. Feeds the dashboards each owning module's pipeline reports from (`01` §5's "12 Business Pipelines are dissolved into their owning modules" — this table is how each of those still gets pipeline-shaped metrics without a separate architectural layer).

---

## 5. Part B — Rule Engine (Already Locked, Formally Homed Here)

The algorithm, action types, formula-parser safety guarantee, and complete default rule catalog (18 base rules plus rules 19–20 added in `02`'s decision log) are specified in full in `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §7 — **not repeated here.** What this document adds: the `RuleEngineService` (§7 below) is the single implementation every module calls; Academic's `PromotionService`/`AttendanceService` and Finance & Payroll's `TuitionCalculationService`/`PayrollService` all depend on it rather than each embedding their own copy of `evaluateRules`.

---

## 6. Part C — Event Bus & Workflow

**Publish/dispatch:** a module action writes a `domain_events` row (e.g. Finance & Payroll's `ExpenseService` on a new expense request writes `expense.requested`). Matching `event_subscriptions` rows determine what happens next; each handler invocation is logged exactly once in `event_handler_log` (the `UNIQUE(event_id, handler)` constraint makes replay safe).

**Confirmed event → effect mappings** (from `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §9's extracted test evidence):
| Event | Effect |
|---|---|
| `student.registered` | `notifications` row: title "New Student Registered", message includes the student's full name |
| `payment.received` | `notifications` row: title "Payment Received", message includes the formatted amount (thousands separator, `02` §3) |
| `expense.requested` | if a `workflow_definitions` row has `trigger = 'expense.requested'`, a `workflow_instances` row is created (`status = 'pending'`), linked via `entity_id` |

**Workflow execution:** each `workflow_definitions.steps` entry advances `workflow_instances.current_step`; every transition appends to `workflow_history`; terminal states are `approved`/`rejected`/`completed`/`cancelled`. Kept as one generic, config-driven engine here specifically *because* it already has more than one real consumer (expense approval today; promotion/enrollment approval chains are plausible near-term additions) — matching `01_TARGET_ARCHITECTURE.md` §9's bar for when shared infrastructure earns its place.

---

## 7. Part D — Services

`app/Modules/PlatformServices/Services/`: `RuleEngineService` (implements `02` §7 exactly — this is the corrected home from `07`'s amendment), `WorkflowService`, `EventBusService` (publish + dispatch + handler logging), `NotificationService`, `AuditService`, `SettingsService` (the `system_settings` key-value accessor Finance & Payroll's reserve-fund calculation depends on, `07` §5).

---

## 8. Part E — HTTP API (representative)

`GET/POST /api/rules` (+ version history), `GET /api/rules/{id}/evaluate` (dry-run), `GET/POST /api/workflow-definitions`, `GET /api/workflow-instances` (+ approve/reject step actions), `GET /api/notifications` (+ mark-read), `GET /api/audit-logs` (read-only, `Audit.View` permission), `GET/PATCH /api/settings/{key}`.

## 9. Part F — Frontend Platform Services Module

Per `01` §7 / `03`: the rule/workflow admin screens (replacing v2's `rules/`, `workflows/`, `events/`, `audit/` view folders — `01_TARGET_ARCHITECTURE.md` §6) live under `client/modules/platform-services/`. The notification bell/toast delivery (`03_DESIGN_SYSTEM_AND_UX_STANDARDS.md` §5's `Sonner` component) is wired here and consumed globally via `shared/`, not re-implemented per module.

---

## 10. Acceptance Criteria

- [ ] `RuleEngineService` reproduces every test case already verified in `02` §7 (discount cap, formula parser, per-skill payroll) — same inputs, same outputs, now in PHP.
- [ ] The three event → effect mappings in §6 fire exactly as specified, verified with tests mirroring `02` §9's extracted evidence.
- [ ] A replayed/duplicate event never double-fires a handler (`event_handler_log`'s uniqueness holds under test).
- [ ] Academic's and Finance & Payroll's rule-dependent services call this module's `RuleEngineService` — grep confirms no second `evaluateRules`-equivalent exists anywhere else in the codebase.

## 11. Definition of Done

Locked once §10 passes and Academic/Finance & Payroll are confirmed calling this module's `RuleEngineService` rather than an inline copy.

## 12. Rollback

New repository, no live dependents (`01` §3a).

## 13. Next Document

`09_CRM_AND_ENROLLMENT_MODULE.md` — visitors, lead pipeline, and the visitor-conversion readiness check deferred from `05_ACADEMIC_MODULE.md` §2, back on the original sequence now that this module exists.
