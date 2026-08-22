# 11 — Funding & Impact Module
### TOEFL House ERP v3 — Build Order

> **Status:** Locked (2026-08-20) — see §5 for the resolved decision log.
> **Depends on:** `01`–`10`, specifically Finance & Payroll's income-recording pipeline (confirmed reused here, §6) and Academic's `scholarshipPercent` handling (`02` §6 — see §5 below for a real gap between the two).
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** schema, a newly-found gap, routes, acceptance tests. No PHP or TypeScript source. **This is the eighth and last of `01_TARGET_ARCHITECTURE.md` §5's core modules.**

---

## 1. Objective

Build Funding & Impact: donors, campaigns, donations, scholarships, sponsorships, and impact reporting.

## 2. Scope / Non-Goals

**In scope:** everything in §4.

## 3. Preconditions

Finance & Payroll and Academic complete.

---

## 4. Part A — Database Schema

**`donors`**: id, full_name, type(enum: `individual`,`organization`,`ngo`,`government`), phone, email, country, notes, created_at.

**`funding_campaigns`**: id, name, description, donor_id(FK, nullable), target_amount, raised_amount, start_date, end_date(nullable), status(`active`/`completed`/`cancelled`), branch_id, created_at.

**`donations`**: id, campaign_id(FK, nullable), donor_id(FK, not null), amount, date, restricted(bool), restriction_note(nullable), receipt_no, branch_id, created_at.

**`scholarships`**: id, name, donor_id(FK, nullable), campaign_id(FK, nullable), total_budget, allocated_amount, criteria(nullable), status(`active`/`exhausted`/`closed`), branch_id, created_at. A fund, not an individual award.

**`scholarship_awards`**: id, scholarship_id(FK, cascade), student_id(FK→students, cascade), amount, award_date, semester(nullable), notes, branch_id. An individual grant against a `scholarships` fund. **Connects automatically to the tuition calculation — see §5.**

**`sponsorship_agreements`**: id, donor_id(FK, not null), student_id(FK, nullable), program_id(FK, nullable), monthly_amount, start_date, end_date, status(`active`/`completed`/`terminated`), branch_id, created_at.

**`impact_metrics`**: id, name, category(enum: `academic`,`social`,`economic`,`demographic`), target_value, current_value, period, branch_id, created_at.

**`impact_reports`**: id, title, donor_id(FK, nullable), campaign_id(FK, nullable), period, generated_at, metrics(json), narrative(nullable), status(`draft`/`published`/`sent`), branch_id.

**`success_stories`**: id, student_id(FK→students, cascade), title, content, photo_url(nullable), published_at(nullable), tags(json, default `[]`), branch_id, created_at.

---

## 5. Scholarship Awards → Tuition Calculation — RESOLVED

Checked directly (not assumed, after `10_INVENTORY_MODULE.md` §5 turned up a false alarm from assuming too quickly): `funding.routes.ts` creates `scholarship_awards` rows but never updates `student_semesters`, `enrollments`, or anything feeding `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §6's `scholarshipPercent`. That pipeline gets its scholarship figure from the **rule engine's `scholarship` category** (`02` §6, step 3) — a completely separate mechanism from this module's donor-funded award tracking.

**Decision (2026-08-20): connect them automatically.** On every `scholarship_awards` creation:

```
1. Resolve the target enrollment: match (student_id, semester) against that student's
   enrollments/student_semesters — reject with 400 if no match (a scholarship must
   attach to a real enrollment, not float unattached).
2. grossTuition = the target enrollment's fee_snapshot_json gross tuition.
3. awardPercent = min(100, (award.amount / grossTuition) × 100).
4. newScholarshipPercent = min(100, enrollment's current scholarshipPercent + awardPercent)
   — additive, so multiple awards across the same enrollment stack sensibly, same
   capping convention as the rule engine's add_discount action (`02` §7.2).
5. Re-run `02` §6's resolveStudentFinanceAmounts for that enrollment with the updated
   scholarshipPercent; persist the recomputed netTuition/remainingDebt/scholarshipPercent
   onto that enrollment's own record.
6. Increment scholarships.allocated_amount by award.amount (existing column, §4).
```

**This does not violate the Program Versioning copy-on-write principle (`05_ACADEMIC_MODULE.md` §6).** That principle protects an enrollment's pinned terms from *catalog-wide* changes (a program version or fee rule changing elsewhere). A scholarship awarded to *this* student for *this* enrollment is a deliberate, direct update to that one record's own state — the same category of change a discount or a payment already is, not a change bleeding in from the catalog.

If `total_budget` would be exceeded by `allocated_amount + award.amount`, reject the award (400) rather than silently over-allocating a fund.

---

## 6. Part B — Income Recording (Confirmed Consistent)

Donations correctly call the shared income-recording service (same `recordIncome` pattern verified for payments and book sales, `10_INVENTORY_MODULE.md` §5) — the 5%-savings sweep applies to donation income exactly as it does to tuition and book revenue. No gap found here.

---

## 7. Part C — Services

`app/Modules/FundingImpact/Services/`: `DonorService`, `CampaignService`, `DonationService` (calls Finance & Payroll's income-recording service, §6), `ScholarshipService` (implements §5's award-to-`scholarshipPercent` algorithm, calling Academic's `EnrollmentService` to persist the recomputed figures), `SponsorshipService`, `ImpactReportingService`.

## 8. Part D — HTTP API

`GET/POST /api/donors`, `GET/POST /api/funding-campaigns`, `GET/POST /api/donations`, `GET/POST /api/scholarships`, `POST /api/scholarships/{id}/awards` (implements §5's connection — may return 400 for no matching enrollment or budget exceeded), `GET/POST /api/sponsorship-agreements`, `GET/POST /api/impact-metrics`, `GET/POST /api/impact-reports`, `GET/POST /api/success-stories`.

## 9. Part E — Frontend Funding & Impact Module

Per `01` §7 / `03`: replaces the `funding/`/`impact/` view components (`01_TARGET_ARCHITECTURE.md` §6) under `client/modules/funding-impact/components/`.

---

## 10. Acceptance Criteria

- [x] §5's decision is recorded (resolved 2026-08-20: connected automatically) — verified end-to-end: an award changes what the student is actually billed.
- [ ] Every donation produces exactly one `financial_transactions` row via the shared income-recording service.
- [ ] `scholarships.allocated_amount` never exceeds `total_budget` (enforced at the service layer, not just displayed as a warning).

## 11. Definition of Done

**Locked (2026-08-20)** — §5 answered, §10's other criteria hold at implementation time. **With this module, all eight modules in `01_TARGET_ARCHITECTURE.md` §5 have a build-order document, and no open cross-document decisions remain.**

## 12. Rollback

New repository, no live dependents (`01` §3a).

## 13. Next Document

`12_REPORTING_DASHBOARD_AND_LAUNCH_READINESS.md` — the presentation-only composition layer (`01` §5's "Dashboard & Reports own no data") that replaces the audit's single largest offender, `DashboardView.tsx` at 1,723 lines — followed by what "done" means for a pre-launch system with no complex cutover to manage (`01` §3a).
