# 05 — Academic Module
### TOEFL House ERP v3 — Build Order

> **Status:** Locked (2026-08-20) — see §5 for the resolved decision log.
> **Depends on:** `01`–`04`. IAM must be built first — every table below carries `branch_id` and is scoped through `04`'s `BranchScopeService`.
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** schema, algorithms, flagged discrepancies, routes, acceptance tests. No PHP or TypeScript source.

---

## 1. Objective

Build the Academic module: the program/curriculum catalog (with Program Versioning), classes, sessions, students, enrollment, attendance, exams, and the post-enrollment student journey.

## 2. Scope / Non-Goals

**In scope:** everything below.
**Boundary correction from `01_TARGET_ARCHITECTURE.md` §6:** that table mapped v2's whole `core/journey` folder to Academic. Building it out precisely, the folder actually splits: **visitor → student conversion readiness** (`conversion-gates.ts`'s logic, `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §9.3) is a CRM & Enrollment concern — visitors aren't Academic's data — and moves to that module's future document. **Post-enrollment journey events** (`journey-engine.ts`, `student_journey_events`) stay here, since they're about students. This document does not build visitor conversion.
**Not owned here:** `teachers` (People & HR — but see §5 for two things noticed while building this module that affect it), `payments`/`financial_transactions` (Finance & Payroll — read via that module's public interface where needed, e.g. exam fee payment status).

## 3. Preconditions

IAM module (`04`) complete and passing its acceptance criteria.

---

## 4. Part A — Database Schema

Grouped by sub-area. All tables carry `branch_id` (FK → `branches`, scoped per `04`'s `BranchScopeService`) unless noted. UUID primary keys throughout, matching IAM's convention.

### 4.1 Curriculum Catalog

| Table | Key columns | Notes |
|---|---|---|
| `programs` | name, description, duration_months, code, is_active | org-level catalog identity |
| `program_versions` | program_id, version_label, version_number, status(`draft`/`published`/`archived`), effective_from/to, is_default, published_at | **`UNIQUE(program_id, version_number)`.** This table is the non-negotiable copy-on-write anchor (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §4) — nothing below bypasses it |
| `levels` | program_id, name, order, program_version_id, code, duration_months, default_fee, pass_mark(default 60), min_viable_size(default 5) | |
| `subjects` | program_version_id (not program_id directly — versioned), level_id, code, name, hours, sort_order | `UNIQUE(program_version_id, code)` |
| `modules` | subject_id, code, name, hours, sort_order, assessment_type | `UNIQUE(subject_id, code)` |
| `promotion_rules` | program_version_id, from_level_id, to_level_id, name, min_score(default 60), min_attendance_pct(default 75), require_all_subjects, auto_promote, branch_id(nullable override), version | See §5 — a second, conflicting promotion mechanism exists |
| `placement_rules` | program_version_id, name, min_score, max_score, recommended_level_id, branch_id(nullable override), sort_order, version | Score band → recommended level |
| `fee_rules` | program_version_id(nullable), level_id(nullable), branch_id(nullable), fee_type(`registration`/`placement`/`semester`/`book`/`retake`/`diploma`/`card`/`exam`/`other`), amount, currency(default `AFN`), is_optional, effective_from/to, version | Distinct from the rule-engine's fee rules (`02` §7.4 #1–4) — see §5 |
| `branch_academic_profiles` | branch_id (PK), default_program_version_id, placement_test_fee, registration_fee, card_fee, diploma_fee, default_pass_mark, default_min_attendance, academic_year_label | One row per branch — the "independence knobs" a branch can override |

### 4.2 Scheduling & Operations

| Table | Key columns | Notes |
|---|---|---|
| `classes` | name, teacher_id, program_id, level_id, level(text), capacity, min_viable_size, schedule_time, start_date, end_date, status(`active`/`completed`/`cancelled`), fee, gender_policy(`female`/`male`/`mixed`), room_id, time_slot_id, academic_term_id, activation_date, merged_into_id, notes | `teacher_id` FK constraint added when People & HR builds `teachers` (same deferred-FK pattern as `04` §2). `merged_into_id` self-references `classes` — supports the merge workflow the Minimum Class Size Warning rule (`02` §7.4 #17) points toward. `room_id`/`time_slot_id`/`academic_term_id` reference tables not fully extracted in this pass (`academic_terms`, `time_slots`, `rooms`) — verify their exact shape in v2's migrations before building class generation specifically |
| `class_teacher_skills` | class_id, teacher_id, skill_id, monthly_rate | `UNIQUE(class_id, teacher_id, skill_id)`. `teacher_id`/`skill_id` FKs deferred to People & HR's document |
| `sessions` | class_id, date, start_time, end_time, topic, status(`scheduled`/`completed`/`cancelled`), teacher_id | |
| `rosters` | session_id, student_id, attendance_status(`present`/`absent`/`sick`/`leave`/`not_marked`), marked_at | `UNIQUE(session_id, student_id)`. **This is the current attendance mechanism** — see §5 |
| `homework` | session_id, title, description, due_date, assigned_by | |
| `class_generation_runs` / `class_generation_items` | run-level: branch_id, academic_term_id, program_version_id, status(`draft`/`preview`/`published`/`cancelled`), params_json, result_json — item-level: level_id, time_slot_id, room_id, teacher_id, capacity, min_viable_size, fee, proposed_name, class_id, status(`pending`/`created`/`skipped`/`error`) | The class-generation wizard flagged as unbuilt UI in `02` §11 — this is the data model it already has, worth keeping even though the wizard itself is future work |

### 4.3 Students, Enrollment & Assessment

| Table | Key columns | Notes |
|---|---|---|
| `students` | student_code(unique), full_name, phone, email, qr_code, status(`active`/`inactive`/`graduated`/`suspended`), registration_date, discount_percent, lead_id(→visitors), gender, father_name, address_region, tazkira_no, whatsapp, dob, school_or_university, emergency_contact_name/phone, placement_score, installment_plan, card_design | `tazkira_no`/`father_name`/`whatsapp` per `02` §3 — first-class, not optional |
| `student_semesters` | student_id, semester_name, class_id, enroll_date, fee_amount, status(`active`/`completed`/`deferred`) | The table `resolvePayrollMultiplier`'s `enrolledCount` counts against (`02` §8.2) |
| `enrollments` | student_id, program_id, program_name, semester_name, level_code, class_id, enrollment_type(`new`/`repeat`/`partial_repeat`/`resume`/`jump`), status(`active`/`paused`/`suspended`/`dropped`/`completed`/`graduated`), skills_focus, started_at, ended_at, **program_version_id, fee_snapshot_json** | The last two columns are the copy-on-write pin — every enrollment freezes its terms at creation time, full stop |
| `exams` | title, date, fee, class_id, type(`placement`/`midterm`/`final`/`mock`/`certification`) | |
| `exam_results` | exam_id, student_id, score, status, exam_fee_paid, certificate_issued, certificate_no | |
| `certificates` | student_id, program_id, level_id, issue_date, certificate_no(unique), grade | |
| `student_journey_events` | student_id, event_type, occurred_at, enrollment_id, payload(json), actor_user_id, actor_name, correlation_id, causation_id, schema_version | Append-only. Event vocabulary is `02` §9.1 minus the visitor-side stages (§2 boundary correction above) |
| `registrations` | student_id, class_id, date, amount_paid, receipt_number, discount_applied, source, semester | Appears to predate `student_semesters`/`enrollments` — verify with whoever knows the product history whether this is still written to, or purely historical |
| `attendance` (legacy) | date, target_id, target_type(`student`/`teacher`), status, class_id, session_id | Explicitly commented "kept for backward compatibility" in v2's own schema — see §5 |

---

## 5. Flagged Discrepancies

Found while extracting the schema above — none of these were resolved in v2, and none should be silently resolved here either.

1. **Promotion mechanism — RESOLVED.** `promotion_rules` (§4.1: `min_score` default 60, `min_attendance_pct` default 75, versioned per program, branch-overridable) is authoritative for level-promotion decisions. The rule engine's seeded "Promotion — Passing Grade"/"Promotion — Failing Grade" rules (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §7.4 #8/#9, flat `examScore >= 90`) are **deprecated — do not seed them in v3.** `02` §7.4 is amended accordingly (see that document's own dated note). Reasoning for the choice: `promotion_rules` is tied to the non-negotiable Program Versioning system (§4), already accounts for attendance alongside score, and is branch-overridable — the rule-engine version was a flatter, parallel mechanism with no evidence of being the one actually driving real promotion decisions.
2. **Two attendance mechanisms.** `rosters` (session + student, current) versus `attendance` (legacy, polymorphic student/teacher, v2's own comment marks it backward-compatibility only). The Attendance Warning rules (`02` §7.4 #10/#11) need one authoritative `attendanceRate` source. **Recommendation: `rosters` is authoritative; treat `attendance` as read-only historical data, not written to going forward** — flagged here rather than assumed, because if teacher attendance is tracked *only* through the legacy table, dropping writes to it silently loses that capability.
3. **`classes.level` (free text) vs `classes.level_id` (FK to `levels`)** — both exist. Treat `level_id` as authoritative; `level` (text) is derived for display, never written independently once `level_id` is set.
4. **Teacher–user link is bidirectional** (`teachers.user_id` and `users.linked_teacher_id` both exist, found while cross-referencing `04`). Recommend collapsing to one direction (`teachers.user_id` as the stored column; `users.linked_teacher_id` becomes a derived value, not separately stored) to remove the risk of the two disagreeing. This belongs to People & HR's document but is noted here since it surfaced while building this module.
5. **`teachers.salary_type`'s base schema only allows `fixed`/`per_skill`/`per_session`** — migration `025_expand_teacher_salary_models.sql` widens it to include `hybrid`/`per_level`, matching `02` §8.1's five models exactly. v3's constraint must reflect the *post-migration* set of five, not the base three — noted here since `class_teacher_skills` sits in this module even though `teachers` itself doesn't.

---

## 6. Part B — Core Algorithms

**Placement:** a visitor's/student's placement exam score is matched against `placement_rules` for the relevant `program_version_id` (branch-specific override rows take precedence over branch-null rows) by `min_score <= score <= max_score` → `recommended_level_id`.

**Enrollment creation (the copy-on-write pin, non-negotiable):** at the moment an enrollment is created, resolve the *current* `program_version_id` (branch's `default_program_version_id`, or an explicitly chosen one), snapshot the applicable `fee_rules` for that version/level/branch into `fee_snapshot_json`, and store both on the `enrollments` row. **After this point, changing the program version or fee rules must never alter this enrollment's stored snapshot.** Any read of "what does this student owe" for an existing enrollment reads the snapshot, not the live catalog.

**Class generation (data model only — wizard UI is out of scope per `02` §11):** a `class_generation_run` against a `program_version_id` + `branch_id` produces `class_generation_items` (one proposed class per level/time-slot/room combination the run considers), each independently `created`/`skipped`/`error`, only becoming a real `classes` row on publish.

**Payroll-relevant enrollment counting:** unchanged from `02` §8.2 — `student_semesters` rows with `status = 'active'` for a class, feeding `resolvePayrollMultiplier`.

---

## 7. Part C — Services

Per `01_TARGET_ARCHITECTURE.md` §7 layering, `app/Modules/Academic/Services/`: `CatalogService` (programs/versions/levels/subjects/modules), `PlacementService`, `PromotionService` (implements `promotion_rules`-based evaluation per §5's resolution — score AND attendance against the version-specific rule, branch override applied first), `ClassService` (incl. generation runs), `SessionService`/`AttendanceService` (rosters, per §5), `EnrollmentService` (owns the copy-on-write pin), `ExamService`, `JourneyService` (append/query `student_journey_events`).

**Cross-module reads, not writes:** Academic reads Finance & Payroll's payment status (e.g. `exam_results.exam_fee_paid`) through that module's public Service interface once it exists (`01` §8) — never a direct query against Finance's tables.

---

## 8. Part D — HTTP API (representative, not exhaustive)

`GET/POST /api/programs`, `/api/program-versions` (incl. `POST .../publish`), `/api/levels`, `/api/classes` (incl. merge action), `/api/sessions/{id}/roster`, `/api/students`, `/api/enrollments` (creation triggers the copy-on-write pin in §6), `/api/exams`, `/api/exam-results`, `/api/students/{id}/journey`. All scoped through `BranchScopeService` (`04` §7); object-level checks per `02` §5.6 apply to every by-ID read exactly as they did in IAM.

## 9. Part E — Frontend Academic Module

Per `01` §7 / `03`'s component standards: this module owns the largest share of the old `DashboardView.tsx`/`ClassesView.tsx`/`SessionsView.tsx`/`StudentsView.tsx` (`00_CURRENT_STATE_AUDIT.md` §4's largest offenders) — each of those becomes several `<400`-line components under `client/modules/academic/components/`, not one large file re-created under a new name. TanStack Query hooks per entity (`useStudents`, `useClasses`, `useEnrollments`, etc.), density-aware tables per `03` §8.

---

## 10. Acceptance Criteria

- [ ] Changing a `program_version`'s fee rules after an enrollment exists does not alter that enrollment's `fee_snapshot_json` or `finalPayable` (§6, non-negotiable).
- [x] §5's five discrepancies each have a recorded decision (resolved 2026-08-20).
- [ ] `resolvePayrollMultiplier`'s `enrolledCount` matches `02` §8.2 exactly against seeded test data.
- [ ] No `DashboardView`/`ClassesView`/`SessionsView`/`StudentsView`-equivalent file exceeds `01` §3's 400-line ceiling.
- [ ] Every academic entity read/write respects branch scope and object-level checks identically to IAM's pattern.

## 11. Definition of Done

**Locked (2026-08-20)** — §5's decisions are recorded and §10's other criteria hold at implementation time.

## 12. Rollback

New repository, no live dependents (`01` §3a) — rollback is not merging a failing increment.

## 13. Next Document

`06_PEOPLE_HR_MODULE.md` — teachers and employees, resolving §5's teacher–user link and `salary_type` notes, and supplying the `teacher_id`/`skill_id` foreign keys this document deferred.
