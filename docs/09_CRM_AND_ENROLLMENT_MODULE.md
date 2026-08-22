# 09 — CRM & Enrollment Module
### TOEFL House ERP v3 — Build Order

> **Status:** Locked once §10 is confirmed.
> **Depends on:** `01`–`08`. Reads Academic (enrollment target), Finance & Payroll (payment verification), and Platform Services (rule-based placement fee lookup) — built last of the four for exactly that reason.
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** schema, the visitor-conversion algorithm (already locked in `02`, formally homed here), routes, acceptance tests. No PHP or TypeScript source.

---

## 1. Objective

Build the CRM & Enrollment module: campaigns, visitors, follow-ups, and the gate that decides when a visitor is ready to become a student.

## 2. Scope / Non-Goals

**In scope:** everything in §4, plus formally resolving the boundary correction opened in `05_ACADEMIC_MODULE.md` §2 — visitor-conversion readiness (`conversion-gates.ts`'s logic) lives here, not in Academic.
**On conversion:** this module *decides* readiness and *calls* Academic's `EnrollmentService` (`05` §7) to actually create the enrollment (the copy-on-write pin, `05` §6) — it does not create enrollment rows itself.

## 3. Preconditions

Academic, Finance & Payroll, and Platform Services complete.

---

## 4. Part A — Database Schema

**`campaigns`**: id, name, source(enum: `ads`,`social`,`referral`,`event`,`organic`,`other`), start_date, end_date(nullable), budget, status(`active`/`paused`/`completed`), branch_id, created_at.

**`visitors`**: id, serial_no, full_name, phone, email, gender, source, campaign_id(FK, nullable), stage(enum: `lead`,`inquiry`,`follow_up`,`placement_booking`,`placement_completed`,`registration`,`enrollment`,`active`,`graduated`,`alumni`,`lost`), assigned_to, visit_date, status (free text, default `visited` — a separate operational-status dimension from `stage`, not enumerated in v2; don't force it into an enum without confirming the real value set first), notes, branch_id, interested_course, follow_up_status, next_contact_date, father_name, address_region, **tazkira_no**, whatsapp, dob, school_or_university, emergency_contact_name/phone, **placement_score** (json-shaped text — carries `feePaid`/`feeCharged`/`feeWaived` flags per `02` §9.3), created_at.

**`visitor_followups`**: id, visitor_id(FK, cascade), date, notes, operator, outcome(enum: `interested`,`not_interested`,`callback`,`registered`).

---

## 5. Part B — Visitor → Student Conversion (Already Locked, Formally Homed Here)

The exact algorithm — already-registered short-circuit, placement-completion check, placement-fee resolution via Platform Services' `RuleEngineService` (the "Placement Test Fee — First Attempt" rule, `02` §7.4 #1), the three ways `placementFeePaid` can be satisfied, and the stage-based blocking reasons — is specified in full in `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §9.3. **Not re-derived here.** `assertVisitorReadyForConversion` throws (HTTP 400, joined reasons) exactly as documented there — conversion is blocked server-side, not just hidden in the UI (`03_DESIGN_SYSTEM_AND_UX_STANDARDS.md` §7's security-consistency rule applies here concretely).

**On successful conversion:** this module calls Academic's `EnrollmentService` to create the `students` row and the pinned `enrollments` row (`05` §6), then advances `visitors.stage` to `registration`/`enrollment` and appends the corresponding `student_journey_events` (via Academic's `JourneyService`, `05` §7) — `STUDENT_REGISTERED` at minimum.

---

## 6. Part C — Services

`app/Modules/CrmEnrollment/Services/`: `CampaignService`, `VisitorService`, `FollowUpService`, `ConversionService` (owns §5's algorithm and the cross-module calls into Academic).

## 7. Part D — HTTP API

`GET/POST /api/campaigns`, `GET/POST /api/visitors`, `POST /api/visitors/{id}/followups`, `GET /api/visitors/{id}/conversion-readiness` (read-only check, §5), `POST /api/visitors/{id}/convert` (throws 400 with reasons if not ready; otherwise delegates to Academic).

## 8. Part E — Frontend CRM & Enrollment Module

Per `01` §7 / `03`: replaces the `visitors/`/`pipelines/`-under-CRM view components (`01_TARGET_ARCHITECTURE.md` §6). The conversion action surfaces `assertVisitorReadyForConversion`'s reasons directly in the UI as the "no permission"/"blocked" state pattern from `03` §6 — never a generic failed-request error.

---

## 9. Acceptance Criteria

- [ ] A visitor with `status = 'registered'` cannot be converted again (§5, already-converted short-circuit).
- [ ] A visitor whose placement fee is required and unpaid cannot convert — verified against all three satisfaction paths in `02` §9.3.
- [ ] A successful conversion produces exactly one new `students` row, one pinned `enrollments` row, and at least one `STUDENT_REGISTERED` journey event — never a partial result if any step fails (wrap in a transaction).
- [ ] No discount/fee/payable math is reimplemented in this module — `ConversionService` calls Finance & Payroll's and Platform Services' existing services for anything financial.

## 10. Definition of Done

Locked once §9 passes.

## 11. Rollback

New repository, no live dependents (`01` §3a).

## 12. Next Document

`10_INVENTORY_MODULE.md` — books and sales, the smallest remaining module.
