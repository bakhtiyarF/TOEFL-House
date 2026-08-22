# 01 — Target Architecture
### TOEFL House ERP v3 — Greenfield Rewrite Blueprint

> **Status:** Revision 2 — 2026-08-19. Supersedes Revision 1 in full. Changed: backend stack (Node/Express/SQLite → Laravel/MySQL/Redis), frontend state/data/form libraries specified, pre-launch status confirmed. Locked once §14 is confirmed.
> **Depends on:** `00_CURRENT_STATE_AUDIT.md`
> **Audience:** AI coding agent or human developer, zero prior conversation context assumed.
> **Execution model:** v3 is built in a brand-new repository/directory. The system has not gone live — no production users or data exist yet. See §3a.

---

## 1. Objective

Define the target shape of TOEFL House v3: module boundaries, technology decisions, layering rules, and the concrete, checkable rules that stop the system from reaching the state described in `00_CURRENT_STATE_AUDIT.md` again.

## 2. Scope / Non-Goals

**In scope:** module map, tech stack decision, folder structure, layering rules, file-size budget, cross-module rules.
**Not in scope (later documents):** exact database schema, exact API contracts, business-rule specification, design tokens/component conventions, launch steps.

---

## 3. Non-Negotiable Ground Rules

| Rule | Concrete threshold |
|---|---|
| File size | Soft warning at 200 lines. **Hard ceiling: 400 lines.** Any file over 400 lines must be split before the work is considered done. |
| Function/method size | Soft target under 40 lines. Over ~80 lines is a mandatory split signal. |
| Component rule | One React component (default export) per file. |
| Module public surface | Each module exposes exactly one public entry point: on the backend, a **Service class's public methods**; on the frontend, an **`index.ts` re-export**. Everything else inside a module (Eloquent models, internal components, helpers) is private to that module. |
| Version control | Every module's work happens in git, in small commits. No module is "done" without a corresponding commit history. |
| Tests before done | A module cannot be marked done without tests covering its business logic. Financial and access-control logic (payroll, discounts, RBAC scoping) requires tests as a hard requirement. |
| New pattern introduction | No new cross-cutting mechanism (a new queue pattern, a new state layer, a new package category) is added beyond what's defined here without a written amendment to this file first (§14). |

### 3a. Pre-Launch Status — What This Changes

The system has no live users or production data. This removes the highest-stakes risk from Revision 1 and simplifies what follows:

- The elaborate parallel-run / parity-verification process planned for a live cutover is no longer the priority. If any current seed or reference data is worth carrying forward, that becomes a light, optional step (§15), not a high-ceremony migration project.
- "Cutover" becomes "launch" — deploy when the modules are ready, no dual-running requirement.
- What still applies at full strength, unchanged by launch status: git discipline, the file-size budget, module boundaries, and tests-before-done. Those exist to prevent the audit's root cause (§7 of `00_CURRENT_STATE_AUDIT.md`), not to protect live data — that reason doesn't go away just because nothing is live yet.

---

## 4. Technology Stack — Decision

**Frontend:**

| Layer | Technology | Role |
|---|---|---|
| UI framework | React 19 + TypeScript | |
| Styling | Tailwind v4 | |
| Component library | shadcn/ui (Radix primitives + Tailwind) | Accessible, themeable components — the concrete foundation `03_DESIGN_SYSTEM_AND_UX_STANDARDS.md` builds on |
| Server state | TanStack Query | Replaces `apiStore.ts` entirely for anything that comes from the API. Per-module query/mutation hooks with built-in caching, loading, and error state. |
| Client/UI state | Zustand | Only for state that isn't server data — sidebar open/closed, wizard step, filters before submit. One small store per module. Never one shared global store — that pattern is exactly how `apiStore.ts` happened. |
| Forms | React Hook Form + Zod | Form state plus schema validation. A module's Zod schema is also its runtime validation and the source of its TypeScript types. |

**Backend:**

| Layer | Technology | Role |
|---|---|---|
| Framework | Laravel (PHP 8.3+) | Replaces Node/Express. |
| Database | MySQL 8 | Replaces SQLite. |
| Cache / Queue / Session | Redis | Permission-check and dashboard-aggregate caching; queued jobs (payroll runs, notifications, report generation); session store. |
| Auth | Laravel Sanctum (SPA/cookie mode) | Replaces hand-rolled JWT. Framework-native, CSRF-protected, integrates directly with Laravel's Auth system. |
| RBAC | Custom tables (`roles`, `permissions`, `role_permissions`, `user_roles`, `permission_overrides`, `role_delegations`) | See amendment below — not a package. |

**Reasoning:** the frontend additions all reinforce decisions already locked in Revision 1 — TanStack Query in particular is close to a purpose-built fix for the `apiStore.ts` god-store finding in the audit. The backend switch to Laravel is sound now that §3a applies: no live data or users means no cost to changing the language, and Laravel's own conventions (thin controllers, Eloquent, migrations, Sanctum, policies) provide framework-level structure that works with several of the audit's root causes (§7) rather than requiring bespoke tooling to enforce them.

**The one real cost:** unlike Revision 1, there is no existing backend code to adapt — v2's logic was TypeScript, v3's is PHP. Every business rule is rebuilt from `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` with nothing to copy from. That makes Document 02 the single most safety-critical document in this series — see its own acceptance criteria when it's written.

---

## 5. Target Module Map

Unchanged from Revision 1 — module boundaries are a business-domain decision, independent of the stack change.

| # | Module | Responsibility | Consolidates (old naming) |
|---|---|---|---|
| 1 | **Identity & Access (IAM)** | Auth, users, roles, permissions, org → campus → branch hierarchy | Identity & Access |
| 2 | **CRM & Enrollment** | Visitors, lead pipeline, conversion to student | CRM / Lead |
| 3 | **Academic** | Students, classes, sessions, attendance, exams/assessment, academic rules, student journey/lifecycle | Academic (Session), Student, Class & Enrollment, Assessment |
| 4 | **People & HR** | Teachers, employees, staff records, positions, contracts | Teacher/HR, Employee/HR |
| 5 | **Finance & Payroll** | Payments, invoices, expenses, budget, financial transactions, payroll calculation & disbursement | Finance, Payroll pipeline |
| 6 | **Inventory** | Books, book sales, stock | Inventory |
| 7 | **Funding & Impact** | Donors, campaigns, donations, scholarships, sponsorships, impact metrics/reports | Funding/Donation, Impact/NGO |
| 8 | **Platform Services** | RBAC internals (via `spatie/laravel-permission`), business rules, approval workflows, notifications, audit log, cross-module events (only where genuinely needed — §9) | Workflow & Rules, Event Bus, Notification/Audit |

**Not a module — a presentation layer:** Dashboard & Reports own no data. They compose read-only views over the eight modules' public interfaces.

**Dissolved:** the "12 Business Pipelines" are not a separate layer — each lives entirely inside the module that owns it.

---

## 6. Coverage Mapping — Nothing Gets Silently Dropped

Unchanged from Revision 1 — this maps the *current* v2 feature set to the *target* module, independent of which stack builds it.

**Frontend (`src/components/`):**

| Old folder | New module |
|---|---|
| `auth/` | IAM |
| `academic/`, `classes/`, `sessions/`, `exams/`, `students/` | Academic |
| `teachers/` | People & HR |
| `visitors/` | CRM & Enrollment |
| `finance/` | Finance & Payroll |
| `books/` | Inventory |
| `funding/`, `impact/` | Funding & Impact |
| `workflows/`, `rules/`, `events/`, `audit/` | Platform Services |
| `dashboard/`, `reports/` | Reporting layer (presentation-only) |
| `pipelines/` | Dissolved into owning modules (§5) |
| `settings/` | Platform Services (system config) + IAM (org/branch/role settings) |
| `navigation/`, `sidebar/`, `common/` | `shared/` — presentational only, zero domain logic |

**Backend (`server/src/` in v2 → rebuilt in Laravel for v3):**

| Old v2 folder | New v3 module |
|---|---|
| `core/rbac` | IAM (via `spatie/laravel-permission`) |
| `core/academic` | Academic |
| `core/journey` | Academic (student lifecycle) |
| `core/finance`, `core/payroll` | Finance & Payroll |
| `core/configuration` (incl. `rule-engine.ts`) | Platform Services — rebuilt right-sized per §9 |
| `core/events` | Platform Services |
| `core/actions` | Business actions move into their owning module; generic audit-trail mechanics stay in Platform Services |
| `routes/*` | Split along the same eight-module lines |

---

## 7. Layering Rules

**Backend (Laravel), per module** — example: `academic`:

```
app/Modules/Academic/
  Http/
    Controllers/      # thin — validate via Form Request, call a Service method, return a Resource
    Requests/          # Form Request classes: input validation
    Resources/          # API Resource classes: response shaping
  Services/            # business logic. The ONLY layer another module may call into.
  Models/              # Eloquent models. Never accessed directly from another module.
  Policies/            # Laravel authorization policies for this module's models
  routes.php           # this module's routes, included from routes/api.php
```

Database migrations stay in Laravel's standard `database/migrations/` (flat, timestamp-ordered) — a deliberate exception to per-module folders, since Laravel's migration tooling expects that location.

**Frontend (React), per module** — example: `academic`:

```
client/modules/academic/
  components/     # views, <400 lines each (§3)
  hooks/           # TanStack Query hooks: useStudents(), useClasses() — server state only
  store.ts         # Zustand store for this module's UI-only state (optional — only if needed)
  schemas.ts       # Zod schemas — used by React Hook Form, also the module's runtime types
  api.ts           # typed fetch calls consumed by hooks/
  index.ts         # public interface
```

The app shell (`App.tsx`'s replacement) stays intentionally thin: routing between modules and the auth gate only. Toasts, RTL handling, and notification UI belong in Platform Services / `shared/`, not in the shell.

---

## 8. Cross-Module Communication Rules

- A module's Service class **may** call another module's Service public methods, or the frontend's `index.ts` exports.
- A module's Service **must not** query another module's Eloquent models directly — no cross-module `Model::query()` calls, no direct DB access outside the owning module.
- **No shared global store**, backend or frontend. On the frontend this applies to Zustand exactly as it applied to the old `apiStore.ts`: one small store per module, never one store for the whole app.

---

## 9. Rules, Workflows & Events — Right-Sized

- **No generic declarative rule engine.** Configurable business logic (discount caps, payroll formulas) is typed, tested Service methods inside Finance & Payroll. Configuration values may live in the database; the rule *logic* stays as reviewable code, not a string/DSL interpreted generically.
- **No generic workflow engine.** A specific process (e.g. expense approval) is an explicit, named state machine scoped to that one workflow — not a generic engine for workflows that don't exist yet.
- **No event bus by default.** Cross-module side effects start as a direct Service-to-Service call. If a genuine need for decoupling or fan-out appears later, use Laravel's native Events & Listeners — already part of the framework, not new infrastructure — introduced via a written amendment to this document (§14), not as day-one scaffolding.
- **RBAC is not reimplemented.** `spatie/laravel-permission` plus Laravel Policies cover roles, permissions, and per-model authorization. Same principle as above, applied to access control: use the tested tool instead of rebuilding a generic engine.

---

## 10. Folder Structure

```
toefl-house-v3/
├── client/                        # React app (Vite)
│   ├── modules/
│   │   ├── iam/
│   │   ├── crm-enrollment/
│   │   ├── academic/
│   │   ├── people-hr/
│   │   ├── finance-payroll/
│   │   ├── inventory/
│   │   ├── funding-impact/
│   │   └── platform-services/
│   ├── reporting/                 # presentation-only, composes module public APIs
│   ├── shared/                    # shadcn/ui components, navigation, sidebar, common UI
│   └── app/                       # thin shell: routing + auth gate only
└── server/                        # Laravel app
    ├── app/
    │   └── Modules/
    │       ├── Iam/
    │       ├── CrmEnrollment/
    │       ├── Academic/
    │       ├── PeopleHr/
    │       ├── FinancePayroll/
    │       ├── Inventory/
    │       ├── FundingImpact/
    │       └── PlatformServices/
    ├── database/
    │   └── migrations/            # flat, standard Laravel location
    └── routes/
        └── api.php                # includes each module's routes.php
```

Each `modules/<name>/` folder on both sides follows exactly the layering in §7 — no module gets a "special" internal structure.

---

## 11. What This Document Does Not Decide Yet

- Exact database schema → defined inside `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` and the migration files it specifies
- Exact API contracts per endpoint → defined inside each module's build-order document (`04+`)
- What business logic must be preserved from v2 → `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md`
- Design tokens, component conventions, accessibility rules → `03_DESIGN_SYSTEM_AND_UX_STANDARDS.md`
- Launch steps → the final document in this series

---

## 12. Acceptance Criteria

- [ ] Every folder listed in §6 has an explicit new-module destination — none unmapped.
- [ ] Every module in §5 has a one-sentence responsibility that does not overlap another module's.
- [ ] Stack decision (§4) reflects Laravel/MySQL/Redis and the specified frontend libraries — not the original Node/Express/SQLite decision.
- [ ] File-size budget and layering rules (§3, §7) are concrete and checkable, not descriptive adjectives.

## 13. Definition of Done

This document is locked once the module map (§5) and stack decision (§4) are confirmed. Document `02` is written against whatever is locked here.

## 14. Rollback / Amendment Policy

Small corrections (a feature belongs in a different module than mapped) are fixed by appending a dated note to this file. A change of this document's magnitude — as just happened with the stack — is instead issued as a new, fully consistent revision with a changelog line at the top, replacing the prior revision outright. Either way: one file, one current truth, never a second competing document.

**Amendment (2026-08-20, via `04_REPO_BOOTSTRAP_AND_IAM_MODULE.md`):** §4's RBAC row originally specified `spatie/laravel-permission`. Extracting the exact v2 schema for `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` showed the real permission model is scope-aware per assignment (`user_roles.scope_type`/`scope_id`, a 7-level hierarchy), plus time-boxed delegations and per-user grant/deny overrides — none of which `spatie/laravel-permission`'s tables represent. Bending that package to fit would mean forcing a general tool onto a shape it isn't built for — the same mistake §9 already warns against, just pointed in the other direction. RBAC is custom tables instead; §4 above and `04` reflect this.

---

## 15. Next Documents

- **`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md`** — the precise specification of what v2 currently does (payroll formulas, discount caps, RBAC scoping rules, academic domain rules, current data shape) that v3 must preserve. With no backend code to carry over, this is what lets a developer or AI agent build v3 correctly.
- **`03_DESIGN_SYSTEM_AND_UX_STANDARDS.md`** — component conventions on top of shadcn/ui, accessibility rules, responsive breakpoints, and the "fast, clear, low-error for expert and non-expert users alike" standard applied concretely.
- **`04` onward** — repo bootstrap, then one module at a time, starting with IAM.
