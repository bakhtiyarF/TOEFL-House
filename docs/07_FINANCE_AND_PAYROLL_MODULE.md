# 07 — Finance & Payroll Module
### TOEFL House ERP v3 — Build Order

> **Status:** Locked (2026-08-20) — see §5 for the resolved decision log.
> **Depends on:** `01`–`06`. Receives the payroll delegation calls from `06_PEOPLE_HR_MODULE.md` §6 and owns `partners` (moved here per that document's §2 correction).
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** schema, a newly-found discrepancy, algorithm pointers back to `02` (not re-derived), routes, acceptance tests. No PHP or TypeScript source.

---

## 1. Objective

Build Finance & Payroll: budget, expenses, the transaction ledger, invoices, payments, payroll disbursement, and ownership/profit distribution.

## 2. Scope / Non-Goals

**In scope:** everything in §4.
**Algorithms not re-specified here:** the discount/scholarship/payable pipeline (`02` §6), the full rule catalog (`02` §7), and the five payroll models (`02` §8, as amended by that document's locked decisions) are already authoritative — this document is schema and services, not a re-derivation of logic already locked.
**Not owned here:** teacher/employee identity (People & HR); student identity (Academic).

## 3. Preconditions

IAM, Academic, and People & HR complete.

---

## 4. Part A — Database Schema

**`budget_lines`**: id, name, current_amount, allocated_amount, icon, cost_type(`fixed`/`variable`), is_marketing(bool), purpose(string — the semantic lookup key from `02` §8.5), branch_id.

**`expense_requests`**: id, title, amount, budget_line_id(FK, nullable), requester, status(`pending`/`approved`/`rejected`), date, approved_by, reject_reason, branch_id, **workflow_instance_id** (nullable — populated when Platform Services' `expense.requested` event creates an approval workflow, `02` §9), expense_kind(`recurring_bill`/`one_time_purchase`/`maintenance`/`other`), bill_period, payment_method(`cash`/`card`/`bank_transfer`), notes, auto_approved(bool).

**`saving_accounts`**: **dropped — not built in v3.** Present in v2's schema, zeroed on every branch create/delete, never incremented anywhere. Confirmed dead; resolved in §5 rather than carried forward.

**`financial_transactions`**: id, type(`income`/`expense`/`saving_transfer`/`budget_charge`), category, amount, date, description, reference_id (links back to the originating payment/expense/etc.), operator_name, branch_id.

**`invoices`**: id, student_id(FK→students), total_amount, discount_amount, net_amount, status(`draft`/`issued`/`paid`/`partial`/`overdue`/`cancelled`), issue_date, due_date, branch_id, notes, invoice_number, issued_by.

**`invoice_items`**: id, invoice_id(FK, cascade), description, quantity(default 1), unit_price, amount.

**`payments`**: id, student_id(FK, nullable), invoice_id(FK, nullable), amount, date, payment_method(`cash`/`card`/`bank_transfer`), status(`completed`/`pending`/`failed`/`refunded`), category(`fee`/`book`/`chapter`/`exam`/`card`/`placement`/`diploma`/`other`), notes, receipt_number, branch_id, semester. **Recording a payment always writes a matching `financial_transactions` row in the same transaction** (`02` §9's test evidence — `type='income'`, `reference_id` = the payment's id), never one without the other.

**`teacher_salary_ledger`**: id, teacher_id(FK→teachers, cascade), period_key, period_label, due_amount, paid_amount, payment_type(`full`/`partial`/`advance`), transaction_id, notes, branch_id, paid_at, operator_name. **Business rule the schema doesn't enforce structurally (no unique constraint) but the service layer must: at most one `payment_type='full'` row per `(teacher_id, period_key)`** — v2's own migration comment is explicit: "no double full pay." Check before insert, don't rely on the index alone.

**`teacher_level_skill_rates`**: id, teacher_id(FK, cascade), level_id(loosely referenced, not FK-enforced in v2), level_code(not null — this is the actual matching key, per `02` §8.3), skill_id(FK→skills, nullable — null means a level-wide rate), rate_per_skill, branch_id. `UNIQUE(teacher_id, level_code, skill_id)`.

**`partners`** (moved from People & HR, `06` §2): id, full_name, phone, email, share_percent, role_description. No `branch_id` — ownership is organization-level, not per-branch.

**Global financial settings** (a `system_settings`-style key-value store, not a dedicated table per metric): `daily_saving_percent` (default 5 — the configurable rate behind `02` §7.4 #12's "5% of income" rule), `saving_balance` (incremented by that rule — see §5), `main_account_balance`. These are organization-wide, not per-branch.

---

## 5. Flagged Discrepancy: Two Untied Reserve-Fund Numbers — RESOLVED

Found while tracing where the Profit Withdrawal Block rule's `reserveFundMet` input (`02` §7.4 #13) actually comes from:

- The **`saving_accounts` table is per-branch**, created and zeroed automatically whenever a branch is created or deleted — but nothing in v2 was found that *increments* it. It appears to exist in the schema without a live write path.
- The **actual reserve-fund check is organization-wide**, computed as `reserveFundTarget = (sum of fixed-cost budget_lines) × 6` months, compared against the `saving_balance` **global setting** (incremented by the 5%-of-income savings rule, `02` §7.4 #12) — not against `saving_accounts` at all. This global check is also enforced with a hard `409` at the route level, independent of the rule engine's `block` action — belt-and-suspenders on the one gate that stops real money leaving the business.

**Decision (2026-08-20): organization-wide, matching what's actually live and enforced today.** `saving_accounts` is confirmed dead schema — **dropped, not built in v3.** The reserve fund and profit-withdrawal gate stay a single organization-wide figure via `system_settings`, exactly as §4 below already specifies.

---

## 6. Part B — Algorithms (Pointers, Not Re-Derivations)

| Concern | Authoritative source |
|---|---|
| Discount / scholarship / payable resolution | `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §6 |
| Full rule catalog (fee, discount, promotion*, attendance, finance, payroll) | `02` §7, including the locked decisions in its §10 |
| Payroll — five salary models, class-linked lines, period/ledger | `02` §8, with rules 19–20 (payroll multiplier tiers) and the harmonized `per_level` activation filter already locked |
| Reserve fund / break-even / profit withdrawal eligibility | §5 above (new — not previously documented, pending its decision) |
| Budget lookup | `02` §8.5 — by `purpose` + `branch_id`, never a hardcoded line ID |

\* Promotion is Academic's rule set, not Finance's — listed here only because it lives in the same rule-engine table.

---

## 7. Part C — Services

`app/Modules/FinancePayroll/Services/`: `TuitionCalculationService` (the one authorized caller of the `02` §6 pipeline — no other module or controller reimplements this math, per that document's own instruction), `PayrollService` (the target of every delegated call from `06` §6 — `computeTeacherClassPayroll`, `computeTeacherDueAmount`, ledger writes with the no-double-full-pay guard), `PaymentService` (payment + matching ledger transaction, atomic), `BudgetService`, `ExpenseService` (fires the `expense.requested` event that Platform Services turns into a workflow instance), `ReserveFundService` (pending §5).

**Correction:** the rule engine itself (`rule_definitions`, `rule_versions`, `rule_evaluation_logs`, the `evaluateRules` algorithm, `02` §7) is **not** a Finance & Payroll service, despite that module being its heaviest caller — it evaluates categories owned by other modules too (Academic's `promotion`/`attendance` rules). It belongs to **Platform Services** (`01_TARGET_ARCHITECTURE.md` §5, module 8) as a shared capability every module calls, including this one. Finance & Payroll's `TuitionCalculationService` and `PayrollService` call Platform Services' `RuleEngineService` — they don't own it.

---

## 8. Part D — HTTP API (representative)

`GET/POST /api/budget-lines`, `GET/POST /api/expense-requests` (+ approve/reject actions), `GET/POST /api/invoices`, `GET/POST /api/payments`, `GET /api/students/{id}/finance-summary` (wraps `02` §6's `summarizeStudentFinance`), the delegated teacher endpoints from `06` §7 (`computed-salary`, `payroll-preview`, `level-rates`, `salary-status`, `pay-salary`), `GET /api/bos/overview` (break-even, reserve fund progress, withdrawable amount — pending §5).

## 9. Part E — Frontend Finance & Payroll Module

Per `01` §7 / `03`: replaces the `finance/` view components (`00_CURRENT_STATE_AUDIT.md`'s `FinancialAnalyticsDashboard.tsx`, 917 lines) with `<400`-line components under `client/modules/finance-payroll/components/`. People & HR's teacher salary widgets (`06` §8) import this module's `index.ts` — never reimplement a payroll number locally.

---

## 10. Acceptance Criteria

- [ ] Every payment write produces exactly one matching `financial_transactions` row, in the same DB transaction (never one without the other).
- [ ] A second `payment_type='full'` ledger row for the same teacher+period is rejected by the service layer.
- [x] §5's decision is recorded (resolved 2026-08-20: organization-wide; `saving_accounts` dropped) — `ReserveFundService` implements accordingly.
- [ ] `02` §6's payable-resolution pipeline exists in exactly one place in this module; nothing outside `TuitionCalculationService` computes a discount, scholarship, or net tuition figure independently.
- [ ] Every `02` §7.4 rule (including rules 19–20 added in that document) is present as seeded `rule_definitions` data, not inline conditionals.

## 11. Definition of Done

**Locked (2026-08-20)** — §5 answered, §10's other criteria hold at implementation time.

## 12. Rollback

New repository, no live dependents (`01` §3a).

## 13. Next Document

`08_PLATFORM_SERVICES_MODULE.md` — resequenced ahead of CRM & Enrollment. The rule-engine correction above means Academic's promotion/attendance rules *and* this module's fee/discount/finance/payroll rules are both already waiting on a `RuleEngineService` that doesn't formally exist as its own module yet. CRM & Enrollment (visitor conversion, reading this module's payment data) follows after.
