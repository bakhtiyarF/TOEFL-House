# 12 — Reporting, Dashboard & Launch Readiness
### TOEFL House ERP v3 — Final Document in the Initial Build Sequence

> **Status:** Locked once §11 is confirmed.
> **Depends on:** `00`–`11` — every module's public read interface.
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** the composition-layer rules, and a launch checklist scoped to a system with no live users (`01_TARGET_ARCHITECTURE.md` §3a). No PHP or TypeScript source.

---

## 1. Objective

Build the Reporting & Dashboard layer — replacing the audit's single largest offender, `DashboardView.tsx` at 1,723 lines (`00_CURRENT_STATE_AUDIT.md` §4) — then define what "ready to launch" means for this specific system.

## 2. Scope / Non-Goals

**In scope:** dashboard/report composition rules, the launch checklist.
**Non-negotiable constraint carried from `01_TARGET_ARCHITECTURE.md` §5: this layer owns no data.** Every number shown here is read through an existing module's public Service interface. If a report needs a number no module currently exposes, the fix is adding a read method to that module's Service — never a new query against another module's tables from inside this layer.

---

## 3. Part A — Dashboard Composition Rules

**Generated per-role, not hand-built per role** — same principle as navigation (`03_DESIGN_SYSTEM_AND_UX_STANDARDS.md` §7). A `DashboardController` (backend) assembles widgets by checking the current user's resolved permissions against a widget registry (`{permission_code, module, query_method}`), calling each permitted module's read method, and returning only what that role can see. Adding a widget for a new role never requires touching every other role's dashboard code.

**Composition, not computation.** This layer calls things like Academic's `getActiveEnrollmentCount()`, Finance & Payroll's `getMonthlyRevenueSummary()`, Platform Services' `pipeline_metrics` reads (`08_PLATFORM_SERVICES_MODULE.md` §4) — it never runs its own aggregate SQL against another module's tables directly, and never re-derives a figure `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` already defines elsewhere.

**Frontend:** `client/reporting/` (not `client/modules/reporting/` — per `01_TARGET_ARCHITECTURE.md` §10, this sits alongside the modules, not inside the module folder convention, since it owns no domain of its own). Each role's dashboard is composed of `<400`-line widget components (`01` §3), each backed by its own TanStack Query hook calling the owning module's `index.ts` export — never another widget's data as a side channel.

**The "12 Business Pipelines" resurface here, not as a separate architectural layer** (`01` §5 already dissolved them into their owning modules) **but as dashboard content**, reading `pipeline_metrics` per pipeline/stage/branch.

### 3a. AI Executive Summary (found during infrastructure review, not previously captured)

v2's dashboard includes an optional AI-generated 2–3 sentence executive summary, built directly against real figures this layer already composes (current-vs-previous-month revenue/expense from Finance & Payroll, active student count from Academic, pending lead count from CRM & Enrollment). Preserve exactly:

- Calls the Anthropic Messages API directly (`model: claude-sonnet-5`) with a prompt built from those real numbers — 15-second timeout.
- Requires `ANTHROPIC_API_KEY` in the server environment. **Optional, not a hard dependency** — its absence is a normal, supported state, not a misconfiguration (`13_INFRASTRUCTURE_AND_DEPLOYMENT.md` §4).
- On a missing key, timeout, non-200 response, or any other failure: returns `{available: false}`, and the frontend's existing template-based summary is the permanent fallback. The dashboard must never look broken because this one optional call failed.
- **Not covered by the automated test suite** — it depends on a live external call. Its acceptance criterion is manual verification with a real key, not a Pest/Vitest assertion; don't try to force it into the automated suite by mocking the whole thing, that would test the mock, not the integration.

---

## 4. Part B — Launch Readiness

Because this system has no live users or data (`01` §3a), launch is a checklist, not a migration project:

1. **All 8 modules' acceptance criteria pass** (`04` §10 through `11` §10) — verified together, not just individually, since cross-module calls (People & HR → Finance & Payroll delegation, CRM & Enrollment → Academic enrollment creation, every module → Platform Services' rule engine) only prove out when exercised end-to-end.
2. **The three open decisions — resolved 2026-08-20:**
   - Promotion mechanism (`05` §5): `promotion_rules` (score + attendance) is authoritative; rule-engine rules 8/9 deprecated (`02` §7.4's amendment).
   - Reserve fund scope (`07` §5): organization-wide; `saving_accounts` dropped.
   - Scholarship-award/tuition connection (`11` §5): connected automatically, algorithm specified there.
3. **Reference/seed data decision:** confirm whether any of v2's existing entered data (test students, sample classes, configured branches) is worth carrying into v3, or whether v3 launches with a clean seed (just the `04` §6 role/permission catalog). Given no real users depend on v2's data, this is a one-time choice, not a migration pipeline — there is no `04_DATA_MIGRATION_AND_PARITY.md`-style document in this series because there's nothing at stake to protect (`01` §3a already established this).
4. **Git tag `v3.0.0-launch`** at the commit where all of the above holds, so "what did we actually ship" is never ambiguous.
5. **Smoke test:** one full walkthrough per role (login → the role's primary task, e.g. teacher marks attendance, registrar records a payment, owner views the dashboard) against the deployed build, not just the test suite.

No parallel-run, no phased cutover, no rollback plan beyond "don't tag `v3.0.0-launch` until the above holds" — the elaborate safety machinery `01_TARGET_ARCHITECTURE.md`'s first revision specified was for a live system that never existed by the time this document was written.

---

## 5. Acceptance Criteria

- [ ] No dashboard widget queries another module's tables directly — every one traces to a public Service method.
- [ ] A new role added to the permission catalog gets a working (if sparse) dashboard automatically, with zero new widget code.
- [ ] §4's five launch-readiness items are all checked before the `v3.0.0-launch` tag is created.

## 6. Definition of Done

**Locked once §5 passes.** The three carried-forward decisions are resolved as of 2026-08-20 (§4, item 2) — every one of the twelve documents in this series is now internally consistent with the others.

## 7. Rollback

Not applicable in the traditional sense — see §4's closing note.

---

## 8. Where This Leaves the Series

Twelve documents, `00` through `12`, now cover: the honest diagnosis of what v2 actually was, the target architecture (twice-amended, once for stack, once for RBAC), the complete business-logic contract with its decisions locked, the design system, and a build order for every module plus the layer that ties them together. All three decisions the module documents surfaced along the way are answered and cross-referenced consistently — not lost in conversation, but written into the specific document each one's module lives in, with `02`'s rule catalog amended to match `05`'s promotion decision. Whatever comes after this (a real bug found during implementation, a scope change, a ninth module nobody anticipated) follows the same discipline every document in this series has followed: one focused file, dated amendments over forked copies, and nothing marked done until its own acceptance criteria actually pass.
