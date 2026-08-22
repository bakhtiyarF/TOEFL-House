# 06 — People & HR Module
### TOEFL House ERP v3 — Build Order

> **Status:** Locked once §10 is confirmed.
> **Depends on:** `01`–`05`. Resolves several items `05_ACADEMIC_MODULE.md` deliberately deferred.
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** schema, deferred-FK resolution, cross-module delegation rules, routes, acceptance tests. No PHP or TypeScript source.

---

## 1. Objective

Build the People & HR module — teacher and employee identity and employment records — and close out the foreign keys and constraints `05_ACADEMIC_MODULE.md` left open pending this module's existence.

## 2. Scope / Non-Goals

**In scope:** `teachers`, `employees`, `teacher_evaluations`.

**Two mapping corrections, found while extracting this module's exact schema:**
- **`teacher_evaluations` moves here from Academic** (`01_TARGET_ARCHITECTURE.md` §6 originally grouped it with Academic). Its actual schema (`teacher_id`, `evaluator_id`, `date`, `score`, `criteria`, `notes` — no `class_id`/`session_id`) is a general staff performance record, not tied to a specific class or session. That's an HR concern, not an academic-delivery one.
- **`partners` moves *out* to Finance & Payroll**, despite `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §12 originally listing it here. Its only real field beyond identity is `share_percent`, which exists solely to feed the Profit Withdrawal rules (`02` §7.4 #13–16). Keeping ownership-equity data inside the module that constantly needs it (Finance & Payroll) avoids a cross-module call on every withdrawal calculation.

**Not owned here:** payroll calculation, rates, or the ledger (`teacher_salary_ledger`, `teacher_level_skill_rates` — Finance & Payroll). This module owns *who* a teacher/employee is; Finance & Payroll owns *what they're paid*. See §5.

## 3. Preconditions

IAM (`04`) and Academic (`05`) complete.

---

## 4. Part A — Database Schema

**`teachers`**
| Column | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| full_name, phone, email | string, name required | |
| base_salary | decimal, default 0 | meaningful only for `fixed`/`hybrid` models (`02` §8.1) |
| salary_type | enum: `fixed`,`per_skill`,`per_session`,`hybrid`,`per_level` | **all five from day one** — v2's base schema only had three, widened by a later migration (`05` §5, discrepancy #5). v3 doesn't repeat that two-step history. |
| performance_score | decimal, default 0 | |
| status | enum: `active`,`inactive`,`on_leave` | |
| branch_id | uuid, FK → branches, not null | |
| joined_date | date, not null | |
| specialization, qualification | nullable string | |
| contract_type | enum: `monthly`,`hourly`,`per_session`, nullable | |
| user_id | uuid, FK → users, nullable | **the one stored direction of the teacher–user link** (`05` §5, discrepancy #4) — `users.linked_teacher_id` is derived from this at read time, not stored separately |

**`employees`**
| Column | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| full_name, phone, email | string | |
| role | string (free text, not the IAM role enum) | job title for staff who may not have system login — e.g. cleaner, security. Not to be confused with `users.role` |
| base_salary | decimal, default 0 | flat only — no salary-model complexity here, unlike teachers |
| status | enum: `active`,`inactive` | |
| branch_id | uuid, FK → branches, not null | |
| joined_date | date, not null | |
| user_id | uuid, FK → users, nullable | same pattern as teachers — some employees have login access, most don't |

**`teacher_evaluations`**
| Column | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| teacher_id | uuid, FK → teachers, cascade delete | |
| evaluator_id | uuid, not null | intentionally not FK-constrained in v2 (may reference a user who's since left) — preserve as a plain reference, not a hard FK, so historical evaluations survive an evaluator's record being removed |
| date | date, not null | |
| score | decimal, default 0 | |
| criteria | json, default `{}` | structured rubric, shape not fixed by v2 — treat as free-form |
| notes | text, nullable | |
| created_at | timestamp | |

**`skills`** — small lookup table (`id`, `name`, unique), owned by Academic (missed in `05`'s table list — noted here since it surfaced while cross-referencing `class_teacher_skills`, added to Academic's inventory retroactively, no schema change needed since `05` already referenced `skill_id`).

---

## 5. Part B — Resolving What Academic Deferred

`05_ACADEMIC_MODULE.md` §2 created `classes.teacher_id`, `class_teacher_skills.teacher_id`, and `class_teacher_skills.skill_id` as plain UUID columns without foreign key constraints, since `teachers` and `skills` didn't exist yet. Now that they do:

1. Add the FK constraint `classes.teacher_id → teachers.id` (nullable — a class can exist before a teacher is assigned).
2. Add the FK constraint `class_teacher_skills.teacher_id → teachers.id` and `class_teacher_skills.skill_id → skills.id` (both not null, matching `05`'s original column definitions).
3. Seed the `skills` table (the four TOEFL skill areas — Reading, Writing, Listening, Speaking — verify against v2's actual seed data before finalizing, since this document didn't independently confirm the exact seeded names).

This is why IAM (`04`) *also* used the deferred-FK pattern for `linked_teacher_id`/`linked_employee_id`/`linked_partner_id` — this document is where two of those three get resolved. `linked_partner_id`'s FK is added when Finance & Payroll builds `partners` (§2's correction).

---

## 6. Part C — Cross-Module Delegation: Payroll

Teachers' and employees' salary actions (`computed-salary`, `payroll-preview`, `level-rates`, `salary-status`, `pay-salary`, `salary-history`) stay under this module's URL namespace for frontend convenience — a teacher's profile page reasonably wants salary information nested under `/api/teachers/:id/...`. **But this module's controllers never compute payroll themselves.** Every one of those endpoints is a thin pass-through that calls Finance & Payroll's `PayrollService` public interface (`01_TARGET_ARCHITECTURE.md` §8's cross-module rule, applied concretely): People & HR knows *who* the teacher is and what `salary_type`/`base_salary` they're configured with; Finance & Payroll owns the actual formulas (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §8), the rate tables, and the ledger writes.

`POST /:id/transfer` (branch reassignment, present for both teachers and employees in v2) is owned entirely here — it only touches `branch_id`, no cross-module call needed.

---

## 7. Part D — HTTP API

| Method & Path | Notes |
|---|---|
| `GET/POST /api/teachers`, `PUT/DELETE /api/teachers/:id` | |
| `POST /api/teachers/:id/transfer` | branch reassignment |
| `GET /api/teachers/:id/computed-salary`, `/payroll-preview`, `/salary-status` | delegate to Finance & Payroll (§6) |
| `GET/POST /api/teachers/:id/level-rates` | delegates to Finance & Payroll's rate storage |
| `POST /api/teachers/:id/pay-salary` | delegates to Finance & Payroll — writes the ledger entry there, not here |
| `GET/POST /api/employees`, `POST /:id/transfer`, `PUT/DELETE /api/employees/:id` | |
| `GET /api/employees/:id/salary-history`, `POST /:id/pay-salary` | same delegation pattern as teachers |
| `GET/POST /api/teachers/:id/evaluations` | owned entirely here, no delegation |

---

## 8. Part E — Frontend People & HR Module

Per `01` §7 / `03`'s standards: replaces `TeachersView.tsx` and `TeachersModals.tsx` (`00_CURRENT_STATE_AUDIT.md` §4, 1,002 and 944 lines respectively) with `<400`-line components under `client/modules/people-hr/components/`. Salary-related widgets call Finance & Payroll's module (`index.ts` import, §6) rather than reimplementing payroll display logic locally.

---

## 9. Acceptance Criteria

- [ ] `classes.teacher_id` and `class_teacher_skills.teacher_id`/`skill_id` FK constraints are live (§5).
- [ ] `salary_type` accepts all five values from day one; no two-step migration history to replicate.
- [ ] No payroll formula exists in this module's code — every salary figure traces to a Finance & Payroll `PayrollService` call.
- [ ] `teacher_evaluations` survives an `evaluator_id` referencing a removed user (§4's intentional non-FK).
- [ ] Branch transfer updates `branch_id` and is immediately reflected in `BranchScopeService` results for that teacher/employee.

## 10. Definition of Done

Locked once §9 passes and §5's FK additions are confirmed against v2's actual seeded `skills` rows.

## 11. Rollback

New repository, no live dependents (`01` §3a).

## 12. Next Document

`07_FINANCE_AND_PAYROLL_MODULE.md` — the module this one's salary endpoints delegate to, and the one CRM & Enrollment's conversion-fee check (deferred from `05` §2) will need once built.
