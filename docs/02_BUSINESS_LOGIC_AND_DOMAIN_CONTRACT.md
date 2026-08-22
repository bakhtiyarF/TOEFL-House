# 02 — Business Logic & Domain Contract
### TOEFL House ERP v3 — What Must Be Preserved

> **Status:** Locked (2026-08-20) — see §10 for the decision log. Authoritative: this document, not v2's source code, is the reference for building v3's business logic. Where this document and any code disagree, this document wins unless a dated amendment to §10 changes it.
> **Depends on:** `00_CURRENT_STATE_AUDIT.md`, `01_TARGET_ARCHITECTURE.md`
> **Audience:** AI coding agent or human developer, zero prior conversation context, **no need to read v2's TypeScript source.**
> **Why this document exists:** v3's backend language changed (TypeScript → PHP). Nothing carries over by copy-paste. Every number, formula, and rule below was extracted directly from v2's code and tests — not paraphrased from memory.

---

## 1. Objective

Specify, precisely enough to implement without guessing, every business rule, formula, and access-control behavior currently in v2 that v3 must reproduce.

## 2. Scope / Non-Goals

**In scope:** financial formulas, RBAC/ABAC rules, the rule engine's exact semantics, payroll calculation, student journey/conversion logic, and the data shapes these depend on.
**Not in scope:** UI behavior (→ `03_DESIGN_SYSTEM_AND_UX_STANDARDS.md`), API endpoint routing (→ module build-order documents), anything listed in §11 as not-yet-built in v2.

---

## 3. Locale, Currency & Regional Conventions

- **Currency: Afghan Afghani (AFN).** Appears directly in user-facing text (e.g. "Placement fee (300 AFN) not recorded"). All monetary fields and displays use AFN, not USD.
- **Dates: Gregorian ISO (`YYYY-MM-DD`) everywhere**, by explicit original design choice (v2 source comment: "Dates are always Gregorian ISO... never local fiscal calendar hard-code"). Preserve this — do not introduce a Solar Hijri/Jalali calendar path.
- **Identity fields specific to this context:** `tazkira_no` (Afghan national ID / Tazkira number), `father_name` (used as a standard identity field, not optional trivia), `whatsapp` (a distinct contact channel from `phone`). These are first-class fields on visitors and students, not generic "notes."
- **Number formatting:** user-facing amounts use thousands separators (e.g. a notification reads "5,000", not "5000").

---

## 4. Domain Hierarchy & Program Versioning

```
Organization → Campus → Branch (academic profile, fees, calendar, rooms, slots)
Program (catalog identity)
  └── Program Version (draft → published → archived)
        ├── Levels
        ├── Subjects → Modules
        ├── Placement Rules   (score band → level)
        ├── Promotion Rules   (score + attendance → next level)
        └── Fee Rules         (registration, semester, retake…)
```

**Non-negotiable principle: Program Version is copy-on-write.** Changing a program's structure (e.g. General English from 5 levels to 6) must never alter historical students' terms. Every `enrollments` row pins `program_version_id` and a `fee_snapshot_json` at enrollment time. v3 must preserve this — no enrollment ever silently re-reads a changed program definition.

**Enrollment flow:** Lead → Placement (rule) → Fee snapshot → Enrollment (pinned version) → Class assign → Sessions → Attendance → Exam → Promotion (rule) → Next level or Graduate, with journey events recorded throughout (§8).

---

## 5. Identity, Roles & Permissions

### 5.1 Roles

Ten roles, `sort_order` lowest = most senior. The single legacy `users.role` column and the granular RBAC tables (§9.4) both exist — see §5.4 for how they combine.

| Role code | Name | Scope pattern | Notable carve-outs |
|---|---|---|---|
| `owner` | Course Owner | `organization` on nearly everything | **Excluded even at owner level:** `Attendance.Edit`, `Grade.Edit`, `Student.Delete`, `Payment.Delete` — these require a more specific operational role |
| `general_manager` | General Manager | `branch` on most operational permissions | Full finance operations including `Budget.Allocate` |
| `head_of_department` | Head of Department | `department` | Academic-only: classes, sessions, attendance, exams, grades, promotion approval, certificates |
| `finance_manager` | Finance Manager | `branch` | Finance desk — payments, invoices, ledger, payroll. **No `Budget.Allocate`** (explicitly "no treasury allocation") |
| `receptionist` | Receptionist | `branch` | Front desk: leads, student registration, class assignment, payments, book sales |
| `counselor` | Counselor | `branch` | CRM follow-up only: leads + view students/classes |
| `teacher` | Teacher | `own` / `class` (mixed, see below) | Narrowest role — see exact list below |
| `data_entry` | Data Entry | `branch` | Entry only — no delete, no finance |
| `designer` | Designer | `branch` | Templates/print only: certificates, student print, settings view |
| `donor_manager` | Donor Manager | mixed `organization` (Funding/Impact) + `branch` (Dashboard/Student/Finance.Report) | |

**Teacher's exact permission set** (the only role with per-permission scope, not one scope for all):
`Dashboard.View`(own), `Student.View`(class), `Class.View`(own), `Session.View`(own), `Session.Edit`(own), `Attendance.View`(own), `Attendance.Edit`(own), `Exam.View`(own), `Grade.View`(own), `Grade.Edit`(own).

**Legacy role string → new role code** (`users.role` still uses the left column):
`owner→owner`, `manager→general_manager`, `finance→finance_manager`, `registrar→receptionist`, `teacher→teacher`, `head_of_department→head_of_department`, `counselor→counselor`, `donor_manager→donor_manager`.

### 5.2 Permission Catalog

~90 permission codes, `Resource.Action` format, grouped by category: `dashboard` (Dashboard.View/Executive, Analytics.View, Impact.View), `academic` (Student.*, Class.*, Session.*, Exam.*, Grade.*, Promotion.Approve, Certificate.*), `admissions` (Lead.*), `hr` (Teacher.*, Employee.*, Payroll.*), `finance` (Payment.*, Invoice.*, Refund.*, Discount.*, Budget.*, Expense.*, FeeStructure.Edit, Finance.Report, Ledger.View), `inventory` (Book.*), `funding` (Funding.*), `automation` (Workflow.*, Rule.*), `reporting` (Report.View), `security` (User.*, Role.*, Permission.*, Audit.View, Event.View, Settings.*, Branch.*, AcademicSetup.*).

Reproduce this catalog exactly in v3 (as a Laravel seeder for `permissions` via `spatie/laravel-permission`) — the `Resource.Action` naming convention is what the frontend's `can('Payment.Create')`-style checks depend on.

### 5.3 Scope Hierarchy

Numeric rank, narrower (lower number) wins when a role's default scope and a user's assignment scope combine:

```
organization(6) > campus(5) > branch(4) > department(3) > program(2) > class(1) > own(0)
```

### 5.4 Permission Resolution Order

1. `user_roles` + `role_permissions` (base grant, scope = narrower of role's default scope and the user's assignment scope)
2. `role_delegations` — adds a permission only if not already granted (does not override); time-boxed (`starts_at`/`ends_at`)
3. `permission_overrides` — per-user `grant` (adds/overrides) or `deny` (removes), with optional expiry
4. **If none of the above produce any permission** (user never migrated to granular RBAC), fall back to the legacy static role definition (§5.1) via `LEGACY_ROLE_MAP`

### 5.5 Branch Scoping — Exact Algorithm

This is security-critical; reproduce exactly, including the special case.

```
resolveBranchScope(user, requestedBranchId):
  hasOrgScope    = (user.role == 'owner') OR (any effective permission scope == 'organization')
  hasCampusScope = hasOrgScope OR (any effective permission scope == 'campus')

  IF requestedBranchId == 'all':
      IF hasOrgScope OR user.role == 'manager':   # note: 'manager' checked by legacy role string, not scope
          RETURN { branchId: null, isAll: true }
      ELSE:
          RETURN { branchId: user.branchId, isAll: false }   # request silently downgraded, not rejected

  ELSE IF requestedBranchId is set AND requestedBranchId != user.branchId:
      IF hasOrgScope OR hasCampusScope OR user.role == 'manager':
          RETURN { branchId: requestedBranchId, isAll: false }
      ELSE:
          RETURN { branchId: user.branchId, isAll: false }   # cross-branch request silently downgraded

  ELSE:
      RETURN { branchId: requestedBranchId OR user.branchId, isAll: false }
```

**Flag:** `general_manager`'s permissions are all `branch`-scoped (§5.1), which would normally fail both `hasOrgScope` and `hasCampusScope` — the `user.role == 'manager'` check is a deliberate hardcoded carve-out that grants cross-branch access anyway. Preserve this exactly; it is not a bug to "clean up" silently (see §10).

### 5.6 Object-Level Authorization (ABAC)

On top of RBAC, verified per-request:
- A user may only read/write records (students, payments, etc.) whose `branch_id` matches their resolved scope — direct reads by ID are checked, not just list filters. Cross-branch reads/writes return `403`, not a filtered/empty result.
- **`canAccessClass(user, classId)`:** `owner` or `manager` (legacy role) → always allowed. Else if effective permission scope on `Class.View` is `organization`/`campus`/`branch`/`department` → allowed. Else (scope is `own`/`class`, or legacy role is `teacher`) → allowed only if the class's `teacher_id` matches the user's linked teacher record (`users.linked_teacher_id`).

---

## 6. Student Finance — Discount, Scholarship & Payable Resolution

**Architectural rule carried over unchanged:** all tuition/discount/debt math goes through exactly one function. v2's own source comment: *"Student financial facade — delegates all policy and payable math to the Rule Engine. Do not implement discount/tuition/debt formulas here."* In v3, this means one Service method in Finance & Payroll — never duplicated inline in a controller or another module.

**Exact algorithm** (`resolveStudentFinanceAmounts`), given `grossTuition`, `requestedDiscountPercent`, `requestedScholarshipPercent`, `amountPaid`, `branchId`:

```
1. gross = max(0, grossTuition); paid = max(0, amountPaid)
   discountPercent = max(0, requestedDiscountPercent)
   scholarshipPercent = max(0, requestedScholarshipPercent)

2. IF discountPercent > 0:
     run 'discount' category rules (§7) with {discountPercent, grossTuition: gross, amountPaid: paid}
     IF a numeric discountPercent came back → replace discountPercent with it

3. ALWAYS run 'scholarship' category rules with {scholarshipPercent, discountPercent, grossTuition: gross, amountPaid: paid}
   IF a numeric scholarshipPercent came back → replace scholarshipPercent with it
   ELSE IF scholarshipPercent <= 0 AND a numeric discountPercent came back from the scholarship rules:
       # some scholarship rules reuse the discountPercent output key as their award rate
       IF that value > current discountPercent → scholarshipPercent = value − current discountPercent

4. Clamp both percents to [0,100]

5. discountAmount    = grossTuition × discountPercent / 100
   scholarshipAmount = grossTuition × scholarshipPercent / 100
   netTuition         = max(0, grossTuition − discountAmount − scholarshipAmount)
   remainingDebt       = max(0, netTuition − amountPaid)
   paidPercentage       = netTuition <= 0 ? 100 : min(100, round(amountPaid / netTuition × 100))

6. Run 'finance' category rules with all of the above plus {transactionType:'tuition', studentFinance:true}
   → any of discountAmount, scholarshipAmount, netTuition, remainingDebt, paidPercentage, discountPercent,
     scholarshipPercent present in the output OVERRIDES the value computed in step 5
     (a 'finalPayable' output specifically overrides netTuition)

7. Round discountAmount/scholarshipAmount; floor netTuition/remainingDebt at 0 and round.

Return: { grossTuition, discountPercent, scholarshipPercent, discountAmount, scholarshipAmount,
          netTuition, totalPaid, remainingDebt, paidPercentage, finalPayable: netTuition }
```

This exact step order (discount → scholarship → base formulas → finance-rule overrides) must be preserved — see §10 for a specific edge case this creates.

---

## 7. Rule Engine — Default Rule Catalog & Evaluation Semantics

### 7.1 Evaluation Algorithm

```
evaluateRules(category, branchId, data, dryRun):
  rules = active rules WHERE category = X AND (scope_branch_id IS NULL OR scope_branch_id = branchId)
          ORDER BY priority DESC, created_at ASC
  runningData = copy of data   # mutable, accumulates outputs — rules are chain-aware
  for each rule in order:
      matched = ALL conditions true (AND semantics) against runningData
      if matched:
          for each action in order:
              execute it (see §7.2); non-'__'-prefixed outputs merge into
              finalOutputs AND into runningData (so later rules see earlier rules' outputs)
              if action was 'block' → set isBlocked, stop evaluating further rules immediately
      if isBlocked: break
  return { finalOutputs, isBlocked, blockReason, warnings, evaluations[] }
```

Higher `priority` number runs first. **This is a single forward pass — a rule is never re-evaluated after a later rule changes the data it already checked.** (Relevant to §10.)

### 7.2 Action Types

| Type | Behavior |
|---|---|
| `set_value` | `outputs[targetKey] = value` (static) |
| `add_discount` | `outputs['discountPercent'] = min(100, currentDiscountPercent + value)` — always targets `discountPercent` specifically, additive |
| `calculate` | `outputs[targetKey] = evaluateFormula(formula, data)` |
| `block` | halts remaining rule evaluation for this call immediately |
| `warn` | appends a message to `warnings[]`, non-blocking |
| `notify` | (skipped in dry-run) queues a message on the given channel |
| `trigger_event` | (skipped in dry-run) fires a named domain event |

### 7.3 Formula Parser

A restricted recursive-descent arithmetic evaluator — **not `eval()` or any general-purpose expression engine.** Only numeric fields from the data context are available as variables (non-numeric fields are invisible to formulas). An unknown variable evaluates to `0`. Any parse error returns `0` — a malformed formula never throws or crashes the request. v3's PHP equivalent must offer the same safety guarantee: no arbitrary code execution path from a rule's formula string, ever.

### 7.4 Complete Default Rule Catalog

| # | Name | Category | Priority | Condition(s) | Action(s) |
|---|---|---|---|---|---|
| 1 | Placement Test Fee — First Attempt | fee | 100 | `isFirstPlacementTest == true` | set `placementTestFee = 300` |
| 2 | Placement Test Fee — Retake | fee | 100 | `isFirstPlacementTest == false` | set `placementTestFee = 0` |
| 3 | Diploma / Certificate Issuance Fee | fee | 90 | `isFirstCertificate == true` AND `examScore >= 90` | set `diplomaFee = 500` |
| 4 | Smart ID Card Issuance Fee | fee | 90 | `isFirstCardIssuance == true` | set `cardIssuanceFee = 200` |
| 5 | Friend Referral Discount | discount | 80 | `leadSource == 'friend'` | add_discount 10 |
| 6 | Early Registration Discount | discount | 70 | `daysBeforeClassStart >= 14` | add_discount 5 |
| 7 | Discount Cap Enforcement | discount | 200 | `discountPercent > 30` | set `discountPercent = 30`; warn "Discount capped at the maximum allowable 30%." |
| 8 | ~~Promotion — Passing Grade~~ | promotion | 100 | ~~`examScore >= 90`~~ | ~~set `promotionStatus = 'pass'`; trigger_event `student.promoted`~~ — **deprecated, see note below** |
| 9 | ~~Promotion — Failing Grade~~ | promotion | 100 | ~~`examScore < 90`~~ | ~~set `promotionStatus = 'fail'`~~ — **deprecated, see note below** |

**Amendment (2026-08-20, via `05_ACADEMIC_MODULE.md` §5):** rules 8 and 9 above were v2's actual seeded data, listed accurately as extracted. Building the Academic module surfaced a second, conflicting promotion mechanism — the dedicated `promotion_rules` table (score **and** attendance, versioned, branch-overridable) — that these two flat rules don't agree with. Resolved: `promotion_rules` is authoritative; rules 8 and 9 are **not seeded in v3**. Struck through above rather than deleted, so the discrepancy that was actually found stays visible.
| 10 | Attendance Warning — Below Threshold | attendance | 100 | `attendanceRate < 85` | warn; notify(sms) parent |
| 11 | Attendance Critical — Risk of Exclusion | attendance | 150 | `attendanceRate < 60` | warn (critical) |
| 12 | Automatic Savings — 5% of Income | finance | 100 | `transactionType == 'income'` AND `amount > 0` | calculate `savingAmount = amount * 0.05` |
| 13 | Profit Withdrawal Block — Reserve Fund Incomplete | finance | 200 | `reserveFundMet == false` | **block**, "…6-month target…" |
| 14 | Profit Withdrawal Tier — 10% | finance | 100 | `reserveFundMet == true` AND `profitMargin between 10–20` | set `withdrawablePercent = 10` |
| 15 | Profit Withdrawal Tier — 15% | finance | 100 | `reserveFundMet == true` AND `profitMargin between 20–30` | set `withdrawablePercent = 15` |
| 16 | Profit Withdrawal Tier — 20% | finance | 100 | `reserveFundMet == true` AND `profitMargin > 30` | set `withdrawablePercent = 20` |
| 17 | Minimum Class Size Warning | academic | 100 | `enrolledCount < 6` AND `classStatus == 'active'` | warn "…consider merging…" |
| 18 | Per-Skill Salary Calculation | payroll | 100 | `salaryType == 'per_skill'` | calculate `monthlySalary = totalSkillRates` |
| 19 | Class Payroll Multiplier — Below Minimum Size | payroll | 100 | `enrolledCount < 6` | set `payrollMultiplier = 0.75`, `payrollTier = 'below_minimum'` |
| 20 | Class Payroll Multiplier — Standard | payroll | 100 | `enrolledCount >= 6` | set `payrollMultiplier = 1.0`, `payrollTier = 'standard'` |

**Rules 19–20 are new, authored for v3 — not extracted from v2** (v2 never seeded this despite building the mechanism for it; see §10 decision log). The `6` boundary reuses the existing, already-established Minimum Class Size threshold (rule 17). The `0.75` multiplier is a proposed starting value with no v2 precedent — confirm with whoever owns compensation policy before it affects a real payroll run. Because both are ordinary `rule_definitions` rows, adjusting them later (including adding a higher-enrollment bonus tier) never requires a code change.

These are **seed data, not hardcoded logic** — they live in the `rule_definitions` table and are editable through `/api/security`-style admin screens. v3 must keep them as data (a Laravel seeder), not inline PHP conditionals, so branch admins retain the same configurability.

---

## 8. Payroll

### 8.1 Salary Models

`teachers.salary_type ∈ {fixed, per_skill, hybrid, per_level, per_session}`:

| Model | Due amount |
|---|---|
| `fixed` | `base_salary` only |
| `per_skill` / `per_session` | sum of adjusted class payroll lines (below) — `base_salary` is **not** included |
| `hybrid` | `base_salary` + sum of adjusted class payroll lines |
| `per_level` | sum over each distinct level the teacher teaches: `(count of skill-assignments at that level) × (rate for that level)` |

### 8.2 Class-Linked Payroll Lines (`per_skill`/`per_session`/`hybrid`)

For each of the teacher's `class_teacher_skills` rows joined to an **active** class:
1. **Skip the class** if its `activation_date` (or `start_date` if no activation date) is in the future — not-yet-operational classes never contribute to payroll.
2. `enrolledCount` = count of `student_semesters` rows for that class with `status = 'active'`.
3. Run `payroll`-category rules with `{enrolledCount, classStatus: 'active'}` → `payrollMultiplier` and `payrollTier`, resolved by rules 19–20 (§7.4): `0.75`/`'below_minimum'` under 6 enrolled, `1.0`/`'standard'` at 6 or above. (Falls back to `1`/`'default'` only if these rows are ever deactivated.)
4. `adjustedAmount = round(monthly_rate × payrollMultiplier)`.
5. Sum across all qualifying lines for the model's total.

### 8.3 `per_level` Model — Rate Resolution

For each level the teacher has assignments at: look up `teacher_level_skill_rates` for that teacher + level (a level-wide rate row, `skill_id IS NULL`). If none exists, fall back to the **average** `monthly_rate` across that teacher's `class_teacher_skills` at that level. If still nothing, rate = 0.

**Harmonized in v3 (§10 decision log):** apply the same activation-date filter as §8.2, step 1 — a class whose `activation_date` (or `start_date` if no activation date) is in the future is excluded from the `per_level` calculation too, and from the level's assignment count. v2 did not do this consistently; v3 does.

### 8.4 Periods & Ledger

- `toPeriodKey(label)` normalizes a month label to `YYYY-MM`: matches a leading `YYYY-MM` directly; else matches `YYYY/M` or `YYYY-M` and zero-pads the month; else falls back to a lowercased, underscored, 32-char-truncated slug of the raw label.
- `sumPaidForPeriod` = `SUM(paid_amount)` from `teacher_salary_ledger` for a teacher + period.
- `hasFullPayForPeriod` = a ledger row exists for that teacher + period with `payment_type = 'full'`.

### 8.5 Budget Lookup

Budget lines are found by **semantic `purpose`** (e.g. `'teacher_salary'`) + `branch_id` — never by a hardcoded budget line ID.

---

## 9. Student Journey & Visitor Conversion

### 9.1 Event Vocabulary (append-only; current state is always projected from history)

`STUDENT_REGISTERED`, `PLACEMENT_TEST_RECORDED`, `PLACEMENT_PASSED`, `PLACEMENT_FAILED`, `ENROLLMENT_CREATED`, `ENROLLMENT_STATUS_CHANGED`, `CLASS_ASSIGNED`, `INVOICE_ISSUED`, `PAYMENT_RECORDED`, `ID_CARD_ISSUED`, `ID_CARD_REPRINTED`, `BOOK_ISSUED`, `BOOK_RETURNED`, `BOOK_LOST`, `ATTENDANCE_RECORDED`, `EXAM_RESULT_RECORDED`, `PROMOTION_DECIDED`, `RETAKE_STARTED`, `STATUS_CHANGED`, `GRADUATED`, `ALUMNI_ENTERED`, `PROGRAM_STARTED`, `NOTE_ADDED`.

`INVOICE_ISSUED` and `PAYMENT_RECORDED` are flagged as financial events specifically (used for financial-history filtering).

### 9.2 Visitor Stages

`visitors.stage ∈ {lead, inquiry, follow_up, placement_booking, placement_completed, registration, enrollment, active, graduated, alumni, lost}`.

### 9.3 Visitor → Student Conversion Readiness

```
getConversionReadiness(visitor):
  IF visitor.status == 'registered' → already converted, canConvert = false, reason "Already converted."
  placementCompleted = visitor.placement_score is set
  placementFeeRequired = resolve the 'Placement Test Fee — First Attempt' fee rule (§7.4 #1) for this branch
  IF placementCompleted AND placementFeeRequired > 0:
      placementFeePaid = a financial_transactions row exists with
          reference_id = visitor.id AND category = 'placement' AND type = 'income'
          OR the visitor's placement_score JSON itself flags feePaid=true, or feeCharged>0,
             or (feeCharged==0 AND feeWaived==true)
  ELSE IF placementCompleted AND placementFeeRequired == 0: placementFeePaid = true
  ELSE: placementFeePaid = false
  IF stage IN {lead, inquiry, follow_up, placement_booking} AND NOT placementCompleted:
      add reason "Visitor stage is '<stage>'; complete placement first."
  canConvert = (no reasons) AND placementCompleted AND placementFeePaid
```

`assertVisitorReadyForConversion` throws (HTTP 400) with the joined reasons if `canConvert` is false — conversion is blocked server-side, not just hidden in the UI.

---

## 10. Decision Log

All three decisions below are **resolved and locked**. A later document may not silently contradict any of them — a contradiction requires a dated amendment appended here first.

| # | Decision | Resolution | Reflected in |
|---|---|---|---|
| 1 | Discount cap ordering — the 30% cap (priority 200) runs *before* the additive discount rules (80, 70) in each single pass, so a future rule set that stacks enough additive discounts could in principle exceed 30% without the cap re-checking. | **Preserve v2's exact single-pass behavior, unchanged.** Not a bug to fix in v3. If a new discount rule is added later that makes this a live risk, that is a fresh decision at that time — not something this document pre-solves. | §7.1, §7.4 #7 |
| 2 | Payroll multiplier was designed but never seeded — always defaulted to `1`/`'default'`. | **Seed real tiers now:** `enrolledCount < 6` → `0.75`/`'below_minimum'`; `enrolledCount >= 6` → `1.0`/`'standard'`. New rules 19–20. The `6` boundary matches the existing Minimum Class Size threshold; the `0.75` figure is a proposed default with no v2 precedent — confirm with whoever owns compensation policy before the first real payroll run. | §7.4 #19–20, §8.2 |
| 3 | `per_level` payroll didn't apply the activation-date filter that `per_skill`/`hybrid` do. | **Harmonize.** All salary models now skip not-yet-active classes the same way. | §8.3 |

---

## 11. Explicitly Out of Scope for v3's First Build

Per `docs/ACADEMIC_DOMAIN.md`'s own "honest backlog" — these were never built in v2 either, so there is no existing behavior to preserve. Do not treat their absence as something v3 broke:

Full UI for the program-version editor and class-generation wizard; automatic invoice generation from a fee snapshot; a KPI engine / dynamic report builder; a real-time notification center; an AI insights layer; full event sourcing for every academic mutation (today only the student journey is event-sourced).

---

## 12. Core Data Model — Module Inventory

73 tables in v2. Full exact column definitions were extracted for every table referenced by §5–§9 above (`users`, `visitors`, `teachers`, `classes`, `class_teacher_skills`, `students`, `student_semesters`, `budget_lines`, `financial_transactions`, `payments`, `rule_definitions`, `roles`, `permissions`, `role_permissions`, `user_roles`, `permission_overrides`, `role_delegations`, `enrollments`, `program_versions`) — use the definitions embedded in §5–§9, not a re-derivation. Every remaining table is listed below by target module; when a build-order document reaches that module, pull its exact v2 DDL at that time rather than guessing field names.

| Module | Tables |
|---|---|
| IAM | `organizations`, `campuses`, `branches`, `auth_sessions` |
| CRM & Enrollment | `campaigns`, `visitor_followups` |
| Academic | `programs`, `levels`, `skills`, `teacher_evaluations`, `sessions`, `rosters`, `homework`, `registrations`, `attendance`, `exams`, `exam_results`, `certificates`, `student_journey_events`, `subjects`, `modules`, `promotion_rules`, `placement_rules`, `fee_rules`, `class_generation_runs`, `class_generation_items`, `branch_academic_profiles` |
| People & HR | `employees`, `partners` |
| Finance & Payroll | `expense_requests`, `saving_accounts`, `invoices`, `invoice_items`, `teacher_salary_ledger`, `teacher_level_skill_rates` |
| Inventory | `books`, `book_restock_history`, `book_sales` |
| Funding & Impact | `donors`, `funding_campaigns`, `donations`, `scholarships`, `scholarship_awards`, `sponsorship_agreements`, `impact_metrics`, `impact_reports`, `success_stories` |
| Platform Services | `workflow_definitions`, `workflow_instances`, `workflow_history`, `automations`, `rule_versions`, `rule_evaluation_logs`, `domain_events`, `event_handler_log`, `event_subscriptions`, `notifications`, `audit_logs`, `system_settings`, `pipeline_metrics` |

---

## 13. Acceptance Criteria

- [ ] Every formula in §6, §7.4, and §8 matches v2's source exactly — spot-checked against the extracted test cases (payroll, discount-cap, formula-parser tests).
- [ ] The role/permission/scope model (§5) is reproduced with no permission silently dropped or added.
- [x] All three §10 decisions have an explicit answer on record before the modules that depend on them are built.
- [ ] §11's items are not accidentally scheduled as "missing features to restore."

## 14. Definition of Done

**Locked as of 2026-08-20** — all three §10 decisions are answered. Nothing in `04` onward (module build orders) may contradict a locked answer here without a dated amendment to this file.

## 15. Next Document

`03_DESIGN_SYSTEM_AND_UX_STANDARDS.md` — component conventions on shadcn/ui, accessibility rules, responsive breakpoints, and the "fast, clear, low-error for expert and non-expert users alike" standard, applied concretely.
