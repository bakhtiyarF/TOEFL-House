# 04 — Repo Bootstrap & IAM Module
### TOEFL House ERP v3 — First Build Order

> **Status:** Locked once §11 is confirmed.
> **Depends on:** `00_CURRENT_STATE_AUDIT.md`, `01_TARGET_ARCHITECTURE.md`, `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md`, `03_DESIGN_SYSTEM_AND_UX_STANDARDS.md`
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** an exact specification — schema, algorithms, routes, acceptance tests. **It contains no PHP or TypeScript source.** Writing that code is the developer's job; this document exists so nothing in it needs to be guessed.
> **Why IAM first:** every other module has a `branch_id` and depends on auth + permission resolution. Nothing else can be meaningfully built or tested before this exists.

---

## 1. Objective

Stand up the v3 repository exactly as specified in `01_TARGET_ARCHITECTURE.md`, then build the IAM module completely: organizations/campuses/branches, users, the full scope-aware RBAC model, and Sanctum-based authentication.

## 2. Scope / Non-Goals

**In scope:** repo scaffolding, IAM database schema, seed data, auth, permission resolution, branch scoping, the IAM HTTP API, the IAM frontend module.
**Not in scope:** any other module's tables. `users.linked_teacher_id` / `linked_employee_id` / `linked_partner_id` are created as plain nullable UUID columns now, with the foreign key constraint added later, in the document that builds the module owning that table (People & HR, Finance & Payroll) — do not block IAM on tables that don't exist yet.

## 3. Preconditions

PHP 8.3+, Composer, Node 20+, MySQL 8 reachable, Redis reachable. Exact CLI flags for scaffolding tools shift between tool versions — verify current syntax when running; what's below states what must be true when you're done, not a rigid transcript to paste blindly.

---

## 4. Part A — Repository Bootstrap

1. Create `toefl-house-v3/` — a new, empty directory. Do not touch v2 (`00_CURRENT_STATE_AUDIT.md`'s "Execution model" note still applies).
2. `git init` at the repo root immediately — before any other step. First commit is the empty repo with a `.gitignore` (adapt v2's, which the audit found already correct: `node_modules/`, `vendor/`, `.env*` except `.env.example`, build output, logs).
3. Scaffold `server/` as a new Laravel application (latest stable). Configure `.env`: `DB_CONNECTION=mysql` plus host/port/database/credentials; `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`, plus Redis host/port.
4. Install and configure Sanctum for SPA (cookie/session) authentication, not bearer tokens — the frontend and API are treated as first-party. Set `SANCTUM_STATEFUL_DOMAINS` to the frontend's dev and production origins; add Sanctum's stateful middleware to the API middleware stack; set `SESSION_DOMAIN` to match.
5. Scaffold `client/` as a Vite + React 19 + TypeScript project. Install: Tailwind v4, shadcn/ui (initialize it — this generates the `client/shared/components/ui/` base from `03_DESIGN_SYSTEM_AND_UX_STANDARDS.md` §5), `@tanstack/react-query`, `zustand`, `react-hook-form`, `zod`, `@hookform/resolvers`.
6. **Router gap-fill:** `01_TARGET_ARCHITECTURE.md` didn't specify a routing library. Add **React Router** (latest stable, library/SPA mode — not its full-stack framework mode, since Laravel is the backend). Reasoning: the most standard, widely-supported choice for an SPA with role-gated routes; no reason to introduce a second, less common option for a decision this low-risk.
7. Testing: **Pest** for the backend (Laravel's modern default; PHPUnit underneath, cleaner syntax). **Vitest + React Testing Library** for the frontend — the audit's single biggest frontend gap (`00_CURRENT_STATE_AUDIT.md` §2) was zero frontend tests; this repository starts with the tooling in place from commit one.
8. Folder layout matches `01_TARGET_ARCHITECTURE.md` §10 exactly: `server/app/Modules/Iam/{Http/Controllers,Http/Requests,Http/Resources,Services,Models,Policies,routes.php}`, `client/modules/iam/{components,hooks,store.ts,schemas.ts,api.ts,index.ts}`.
9. Commit after each numbered step above lands and runs — not one giant first commit. This is `01_TARGET_ARCHITECTURE.md` §3's "small commits" rule applied to the very first thing built.

---

## 5. Part B — Database Schema

Every table below is translated from v2's verified schema (`server/src/db/schema.sql`), not re-derived from memory. All primary keys are UUIDs (matching v2's `TEXT` string keys) via Laravel's native UUID support, not auto-increment integers.

**`organizations`**
| Column | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| name | string, not null | |
| timestamps | created_at, updated_at | v2 only had `created_at` — adding `updated_at` is a harmless Laravel-idiomatic addition, noted rather than silent |

**`campuses`**
| Column | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| organization_id | uuid, FK → organizations, not null | |
| name | string, not null | |
| code | string, unique, not null | |
| address, postal_code, phone, email, description | nullable strings | |
| is_active | boolean, default true | |
| timestamps | | |

**`branches`**
| Column | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| campus_id | uuid, FK → campuses, **nullable** | matches v2 exactly — a branch can exist unassigned to a campus |
| name | string, not null | |
| code | string, unique, nullable | |
| location | string, not null, default `''` | |
| address, postal_code, phone, email, description | nullable | |
| is_active | boolean, default true | |
| timestamps | | |

**`users`**
| Column | Type | Notes |
|---|---|---|
| id | uuid, PK | |
| username | string, unique, not null | login identifier — **not email**; v2's `email` column is nullable and not unique |
| password | string, not null | Laravel `Hash` (bcrypt), matching v2's `password_hash` |
| full_name | string, not null | |
| employee_id, email, phone, address, national_id, emergency_contact, department, employment_type, employee_status, profile_photo_path, account_status | nullable strings, matching v2's optionality exactly | `national_id` here is the **staff** ID field — do not confuse with `tazkira_no`, which belongs to visitors/students (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §3) |
| date_of_birth, joining_date | nullable date | |
| gender | enum(`male`,`female`,`other`), nullable | |
| manager_user_id | uuid, nullable, self-referential FK → users | org-chart reporting line |
| role | enum(`owner`,`manager`,`finance`,`registrar`,`teacher`,`head_of_department`,`counselor`,`donor_manager`), not null | the legacy role column — exact 8 values from v2's CHECK constraint, preserved verbatim (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5.1's legacy map) |
| branch_id | uuid, FK → branches, **not null** | every user has exactly one home branch |
| linked_teacher_id, linked_employee_id, linked_partner_id | uuid, nullable | FK constraint added when People & HR / Finance & Payroll build the target tables (§2) |
| two_factor_enabled, must_change_password | boolean, defaults `false`/`true` | |
| is_active | boolean, default true | |
| created_at, last_login_at, last_activity_at | timestamps, latter two nullable | |

**`roles`**: id(uuid,PK), code(string,unique), name(string), description(text,nullable), is_system(bool,default false), is_active(bool,default true), sort_order(int,default 100), timestamps.

**`permissions`**: id(uuid,PK), code(string,unique — the `Resource.Action` form, e.g. `Payment.Create`), resource(string), action(string), description(text,nullable), category(string,default `general`), is_system(bool,default true), created_at.

**`role_permissions`**: id(uuid,PK), role_id(FK→roles,cascade), permission_id(FK→permissions,cascade), default_scope(enum: `organization`,`campus`,`branch`,`department`,`program`,`class`,`own` — default `branch`), unique(role_id,permission_id).

**`user_roles`**: id(uuid,PK), user_id(FK→users,cascade), role_id(FK→roles,cascade), scope_type(same 7-value enum, default `branch`), scope_id(uuid,nullable), is_primary(bool,default false), assigned_by(uuid,nullable), assigned_at, expires_at(nullable), unique(user_id,role_id,scope_type,scope_id).

**`permission_overrides`**: id(uuid,PK), user_id(FK,cascade), permission_id(FK,cascade), effect(enum `grant`/`deny`), scope_type(same 7-value enum,default `branch`), scope_id(nullable), reason(nullable), granted_by(nullable), created_at, expires_at(nullable).

**`role_delegations`**: id(uuid,PK), from_user_id(FK→users,cascade), to_user_id(FK→users,cascade), role_id(FK→roles,cascade), scope_type(default `branch`), scope_id(nullable), reason(nullable), starts_at(not null), ends_at(**not null** — a delegation must always have an end), created_by(nullable), is_active(bool,default true).

---

## 6. Part C — Seed Data

One seeder, run in this order:

1. **Permissions** — the full catalog from `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5.2 (~90 codes across `dashboard`, `academic`, `admissions`, `hr`, `finance`, `inventory`, `funding`, `automation`, `reporting`, `security`). Source that document, not a re-derivation.
2. **Roles** — the 10 roles from §5.1 of the same document, exact `sort_order` values.
3. **`role_permissions`** — each role's exact permission set and `default_scope`, from §5.1, including the documented carve-outs (owner's four exclusions, teacher's per-permission mixed scopes, finance_manager's missing `Budget.Allocate`).
4. Do **not** seed any `users` rows beyond what's needed for local development/testing — this system has no production data to migrate (`01_TARGET_ARCHITECTURE.md` §3a).

---

## 7. Part D — Services (Exact Algorithms)

Each of these is a Service class per `01_TARGET_ARCHITECTURE.md` §7 — the algorithm must match exactly, the implementation language is PHP instead of TypeScript.

**`AuthService`:** login validates `username` + `password` (Hash::check) against `users`; on success, Sanctum issues the SPA session (cookie, CSRF-protected — not a bearer token). `/api/auth/me` returns the current user plus their fully-resolved permission set (below). Logout invalidates the session.

**`PermissionResolutionService`:** implements `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5.4 exactly — base grant from `user_roles`+`role_permissions` (scope = narrower of the role's `default_scope` and the assignment's `scope_type`, using the rank order in §5.3), then `role_delegations` additively (never overriding an existing grant, respecting `starts_at`/`ends_at`), then `permission_overrides` (`grant` adds/overrides, `deny` removes, respecting `expires_at`), then — only if the result is empty — the legacy `LEGACY_ROLE_MAP` fallback from §5.1.

**`BranchScopeService`:** implements `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5.5 exactly, **including the `role === 'manager'` hardcoded carve-out** that bypasses the normal scope check. This is not a bug to clean up — it's a documented, decided behavior (§10 of that document didn't flag it as open; it was recorded as something to preserve).

**Object-level authorization:** every IAM-adjacent read/write (users, branches) checks the resolved branch scope against the record's `branch_id` before returning data — a direct fetch by ID is checked exactly like a list query. A mismatch returns `403`, not a filtered empty result (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5.6).

---

## 8. Part E — HTTP API

| Method & Path | Requires | Notes |
|---|---|---|
| `POST /api/auth/login` | — | `{username, password}` → session cookie |
| `POST /api/auth/logout` | authenticated | |
| `GET /api/auth/me` | authenticated | user + resolved permissions |
| `GET/POST/PATCH/DELETE /api/organizations` | `organization` scope | |
| `GET/POST/PATCH/DELETE /api/campuses` | `Branch.*`-family permission, `campus` scope or higher | |
| `GET/POST/PATCH/DELETE /api/branches` | `Branch.*`-family permission | scoped per §7's `BranchScopeService` |
| `GET/POST/PATCH/DELETE /api/users` | `User.*`-family permission | scoped per §7 |
| `GET /api/roles`, `GET /api/permissions` | `Role.View` / `Permission.View` | read-only catalog endpoints — the frontend's role/permission management UI (`03_DESIGN_SYSTEM_AND_UX_STANDARDS.md` §7) reads these |

---

## 9. Part F — Frontend IAM Module

Per `01_TARGET_ARCHITECTURE.md` §7's frontend layering and `03_DESIGN_SYSTEM_AND_UX_STANDARDS.md`'s component/state standards:

- `hooks/`: `useAuth()` (TanStack Query — login mutation, `me` query, logout mutation; session state lives in the query cache, not duplicated into Zustand)
- `store.ts`: only for local UI state that isn't server data (e.g. password-visibility toggle) — if nothing qualifies, this file is legitimately empty or omitted, not padded out
- `schemas.ts`: `LoginSchema` (Zod) — used by both the login form (React Hook Form) and as the request type
- `components/`: `LoginPage` (shadcn `Form`), a route-guard component that checks `useAuth()` and the required permission for the target route, redirecting rather than rendering a broken page
- `index.ts`: exports `useAuth` and the route-guard component — nothing else in this module is imported elsewhere

---

## 10. Acceptance Criteria

- [ ] Fresh clone + setup produces a running app connected to MySQL and Redis, with git history from step 1 of §4 onward.
- [ ] Seed data matches `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5.1–5.2 exactly: 10 roles, full permission catalog, correct `role_permissions` per role including every documented carve-out.
- [ ] A `registrar`-equivalent and `teacher`-equivalent user requesting another branch's data (by query param or by direct ID) get their own branch's data or a `403` — never the other branch's data.
- [ ] An `owner`-equivalent user requesting `branchId=all` gets all branches; a `general_manager`-equivalent user (legacy role `manager`) can access a specific other branch by ID — both per the exact algorithm in §7.
- [ ] `/api/auth/me` returns the correct resolved permission set for a user with a role-based grant, a role delegation, and a permission override active simultaneously.
- [ ] Multiple concurrent sessions for different users work independently (mirrors v2's multi-user auth test).
- [ ] Every new file respects `01_TARGET_ARCHITECTURE.md` §3's file-size budget; every Service method has a Pest/Vitest test before the module is marked done.

## 11. Definition of Done

Locked once all of §10 passes. No later module's build-order document may bypass `PermissionResolutionService` or `BranchScopeService` and re-implement access logic inline — every other module calls these, per `01_TARGET_ARCHITECTURE.md` §8's cross-module rule.

## 12. Rollback

This is a new repository with no live dependents — rollback is simply not merging/deploying an increment that fails §10. No data migration risk exists at this stage (`01_TARGET_ARCHITECTURE.md` §3a).

## 13. Next Document

`05_ACADEMIC_MODULE.md` — the largest module (students, classes, sessions, exams, journey), built next because CRM & Enrollment and Finance & Payroll both depend on it.
