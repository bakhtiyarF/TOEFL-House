# 03 — Design System & UX Standards
### TOEFL House ERP v3 — UX Architecture & Component Standard

> **Status:** Locked once §16 is confirmed.
> **Depends on:** `01_TARGET_ARCHITECTURE.md` (modules, stack), `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` (roles, permissions, locale)
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **Source note:** the old `Role_Based_Workspace_UX_UI_Design_Specification.md` (38 sections) contained genuinely good principles — the role/permission/scope distinction, "frontend is never the security boundary," progressive disclosure. Those are preserved here. What it didn't have — actual tokens, actual component choices, actual breakpoints, a stated locale — is what this document adds. It moves to `docs/_archive/2026-08-legacy/` alongside the other 37 files per `00_CURRENT_STATE_AUDIT.md` §3.

---

## 1. Objective

Define the design tokens, component conventions, state contracts, and accessibility/responsive/RTL rules that every module in `01_TARGET_ARCHITECTURE.md`'s module map builds against — concretely enough that two different modules built by two different sessions still look and behave like one product.

## 2. Scope / Non-Goals

**In scope:** tokens, component inventory, state contracts, navigation-generation rules, density/responsive/accessibility/RTL/motion standards.
**Not in scope:** pixel-level mockups or full page designs for any specific role's dashboard — those are designed during each module's own build-order document, against the standard set here.

---

## 3. Core UX Principle

Every screen answers five questions immediately, without the user hunting:

1. Where am I?
2. What's relevant to me?
3. What needs my attention?
4. What can I do here?
5. What should I do next?

**Design for the user's job, not for how much data exists in the database.** A dashboard is not "everything the schema allows me to show" — it's the smallest set of information and actions that let this specific role finish their actual task. This directly serves the "fast, clear, minimal-error, no-training-required" standard: less to parse means less to get wrong.

Corollary rules:
- Every important widget ends in an action, not just a number (`[Mark Attendance]`, `[Record Payment]`, `[Register Student]` — not just a count with no next step).
- Progressive disclosure over information overload: default view is minimal; density and depth are opt-in (§8), not forced on everyone.
- Consistency over novelty — a returning user should never have to relearn a pattern a different module already taught them.

---

## 4. Design Tokens

Built on Tailwind v4 + shadcn/ui defaults — not a custom system. Reinventing a token scale here would repeat exactly the over-engineering pattern flagged in `00_CURRENT_STATE_AUDIT.md` §5.

| Token category | Source | Rule |
|---|---|---|
| Spacing | Tailwind's default 4px-based scale | No arbitrary pixel values in component code (no `mt-[13px]`) — scale values only |
| Breakpoints | Tailwind defaults: `sm` 640px, `md` 768px, `lg` 1024px, `xl` 1280px, `2xl` 1536px | See §9 |
| Color | shadcn/ui CSS variables (`--background`, `--foreground`, `--primary`, `--secondary`, `--muted`, `--accent`, `--destructive`, `--border`, `--ring`, one pair per token for light/dark) | Components reference the variable (`bg-background`, `text-foreground`), never a raw hex value |
| Typography | One sans-serif variable font stack for both Latin and Persian/Dari script (system UI stack, e.g. `ui-sans-serif` fallback chain) — verify the chosen font actually renders Persian glyphs correctly, since most of this system's text is Dari | A single type scale (`text-xs` … `text-3xl`), no bespoke font sizes |
| Radius | shadcn's `--radius` variable | One radius value per component family, not per instance |

---

## 5. Component Library

shadcn/ui components are copied into the codebase (not an npm black box), so each is customized once, centrally, in `client/shared/components/ui/`. Every module imports from there — never a second copy of `Button` or `DataTable` inside a module folder.

| Need | Component | Notes |
|---|---|---|
| Actions | `Button` | One primary action per view maximum — matches §3's "obvious next action" |
| Tabular data | `Table` + TanStack Table | Search, filter, sort, pagination, column visibility, row actions — see §8 for density |
| Forms | `Form` (shadcn's React Hook Form + Zod wrapper) | Every field uses the module's Zod schema (`01_TARGET_ARCHITECTURE.md` §7) as both validator and type source |
| Modals | `Dialog` | Short, single-purpose confirmations and quick-create only |
| Side panels | `Sheet` | Record detail/edit that needs more room than a Dialog — e.g. a student profile drawer |
| KPI summaries | `Card` | Dashboard/reporting layer only — Reporting owns no data, only composes it (§01 §5) |
| Grouped views | `Tabs` | |
| Global search | `Command` (cmdk) | One command palette, keyboard-accessible, permission-filtered (only shows what the user could actually open) |
| Notifications | `Sonner` (toast) | Replaces the ad-hoc toast system patched into `App.tsx` per the audit — one implementation, in `shared/`, not the app shell |
| Loading placeholders | `Skeleton` | See §6 |
| Status/labels | `Badge` | |

---

## 6. State Contracts

Every data-bearing view implements all of these — not as a one-off per screen, but as shared components every module reuses:

| State | Rule |
|---|---|
| Loading | `Skeleton`, shaped like the real content — not a generic spinner that jumps on load |
| Empty | Explains *why* it's empty and what to do about it (e.g. "No classes yet — [Create a class]"), never a bare "No data" |
| No permission | A dedicated state, not a blank page or a silent redirect — tells the user what's missing, not just that they're blocked |
| Error | Human, specific, actionable. **Never surface a raw `500`, `fetch failed`, stack trace, or `undefined` to a user.** |
| No results (filtered) | Distinct from Empty — "no matches for this filter," with a way to clear the filter |
| Offline / network failure | Only where the module genuinely needs it (not every screen) |

---

## 7. Role-Based Workspace & Navigation

**Generated, not hand-built per role.** Navigation items and dashboard widgets are filtered by the current user's *effective* permission set (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5) against a static `TAB_PERMISSION_MAP`-style table (feature → required permission code) that already exists in v2's design and is preserved. This is what makes "support a new custom role without redesigning the app" (a real principle from the old spec, worth keeping) actually true — a new role just needs a new row in the permission tables, not new frontend code.

**Frontend hiding is convenience, not security.** A nav item or button being hidden is a UX nicety for the roles who can't use it — it is never the actual access boundary. That boundary is enforced identically at every layer:

```
UI            → hides what the user can't act on (convenience)
Backend       → rejects unauthorized requests regardless of what the UI sent (§02 §5.6 ABAC)
Database/Query → every query is scoped, never trusts a client-supplied branch_id alone
```

If any layer disagrees with the others, that's a bug — not a design trade-off.

---

## 8. Information Density

This is a data-heavy ERP; a single fixed row height is wrong for every table. Tables in Academic (student/class lists), Finance & Payroll (ledgers), and Reporting support three density levels — comfortable / compact / dense — controlled by a per-user preference in a `shared` Zustand store (`01_TARGET_ARCHITECTURE.md` §4), not a per-module setting duplicated everywhere.

---

## 9. Responsive Design

Designed for desktop, tablet, and mobile from the start — not a desktop layout that gets compressed. Responsive behavior follows **task priority**: on a small screen, the primary action for that role's job stays reachable in one tap; secondary information collapses first.

| Breakpoint | Primary use |
|---|---|
| `< 640px` | Single-column; teacher/receptionist quick actions (mark attendance, record a payment) must work fully here — these roles are the most likely to be on a phone |
| `640–1024px` | Tablet — condensed multi-column, table density defaults to `compact` |
| `> 1024px` | Full desktop layout, all density options available |

---

## 10. Accessibility

**Target: WCAG 2.2 AA.**

Because shadcn/ui components are built on Radix primitives, keyboard navigation, focus management, and ARIA roles are correct **by default** for anything built from the component library in §5 — this is a direct, concrete reason the stack in `01_TARGET_ARCHITECTURE.md` was a good choice for this requirement, not just a styling preference. That said, "using the library" doesn't automatically satisfy the standard; these remain required and checked per component:

- Every form field has a real, associated label (not a placeholder standing in for one)
- Color is never the only signal (status also gets an icon or text, not just a color swatch)
- Visible focus indicator on every interactive element
- Color contrast meets AA against both the light and dark token sets in §4
- Touch targets are large enough on the mobile breakpoint (§9)
- Errors are announced to assistive tech, not just shown visually (§6)

---

## 11. RTL & Localization

The primary user-facing locale is **Dari/Persian, right-to-left** (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §3 — AFN currency, Tazkira ID field, Gregorian-ISO dates already established there).

- **Use logical CSS properties, not physical ones.** `ms-4`/`me-4` (margin-inline-start/end), not `ml-4`/`mr-4`. This is a hard rule, not a style preference — v2's RTL support was patched into `App.tsx` reactively (audit §4, the "Sprint 11" changelog comment); logical properties from the start prevent that class of bug entirely rather than fixing it after the fact.
- `dir="rtl"` is set at the document root when the active locale is Persian; components must not assume LTR anywhere (icons that imply direction — e.g. a "next" arrow — flip with `dir`).
- Numbers, currency (AFN), and dates stay in the format already locked in `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §3, regardless of text direction.

---

## 12. Motion

Animation only where it communicates state, hierarchy, or continuity — never decoration. Good uses: drawer/dialog transitions, loading-to-loaded continuity, success feedback. `prefers-reduced-motion` is respected everywhere; nothing essential to understanding a screen depends on an animation actually playing.

---

## 13. Design Quality Criteria

| Criterion | What "done" looks like |
|---|---|
| Learnability | A new user completes their first core task without training |
| Task completion | Common tasks (mark attendance, record payment, register a student) take minimal steps |
| Consistency | The same component behaves the same way in every module |
| Accessibility | WCAG 2.2 AA, verified per §10 |
| Responsiveness | Works fully on the breakpoints in §9, not just "doesn't break" |
| Error recovery | A user hitting an error understands what happened and what to do next |
| Security awareness | The UI never implies access it doesn't actually have (§7) |
| Scalability | A new role or module doesn't require redesigning existing ones |

---

## 14. What This Document Does Not Cover

Full page layouts for each of the 10 roles in `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5.1 — those are designed inside each module's own build-order document, against the tokens, components, and rules locked here.

---

## 15. Acceptance Criteria

- [ ] Every component in §5 is implemented once in `shared/`, never duplicated inside a module.
- [ ] No component uses a raw hex color, arbitrary pixel spacing, or a physical (`ml-`/`mr-`) directional class.
- [ ] Every data view has all six states from §6 before it's considered done.
- [ ] Navigation/dashboard visibility is driven by the permission table (§7), not an `if (role === ...)` check anywhere in module code.

## 16. Definition of Done

Locked once confirmed — no module build-order document may introduce a new token, a new state pattern, or a new cross-module component without a dated amendment here.

## 17. Next Document

`04_REPO_BOOTSTRAP_AND_IAM_MODULE.md` — the first document with actual build instructions: scaffold the new Laravel + React repository per `01_TARGET_ARCHITECTURE.md` §10, then build the IAM module first, since every other module depends on it.
