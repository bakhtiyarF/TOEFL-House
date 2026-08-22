# 13 — Infrastructure & Deployment
### TOEFL House ERP v3 — Hosting, CI/CD, and Operations

> **Status:** Locked once §11 is confirmed.
> **Depends on:** `01` (stack), `04` (env var foundations), `12` (what "launch" means for this system).
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **Why this document exists:** none of the twelve prior documents specify where any of this actually runs. Discovered while checking v2's `.env.example` for an unrelated reason — v2 was built with zero CI, zero deployment config, zero documented hosting target (`00_CURRENT_STATE_AUDIT.md` §7's "no enforcement loop" finding, restated as an infrastructure gap rather than a process one). This document closes it.

---

## 1. Objective

Specify hosting, CI/CD, environment configuration, backups, and process management — sized to this institute's actual scale, not a hypothetical large one, consistent with every prior document's stance on right-sizing.

## 2. Scope / Non-Goals

**In scope:** everything in §4–§9.
**Explicitly not recommended:** Kubernetes, multi-region deployment, a managed container orchestration platform, or any infrastructure whose complexity is justified by scale this system doesn't have. A single-region VPS setup, done properly, is the correct choice here — the same reasoning `01_TARGET_ARCHITECTURE.md` §9 already applied to the rule engine and event bus applies to infrastructure too.

## 3. Preconditions

`04` through `12` complete, or at least locked, since deployment targets a finished build.

---

## 4. Part A — Environments

**Two environments: staging and production.** Not more (no need at this scale), not fewer (shipping straight to production with no rehearsal space is how the "hope this works" pattern `00_CURRENT_STATE_AUDIT.md` §7 described happens again).

| | Staging | Production |
|---|---|---|
| Purpose | Verify a release before real use | The real system |
| Data | Seed/test data only | Real data, once launched |
| Deploy trigger | Every merge to `main` | Manual, tagged release (`12_REPORTING_DASHBOARD_AND_LAUNCH_READINESS.md` §4's `v3.0.0-launch` and every tag after) |

**Hosting shape, both environments:** a single VPS per environment (2–4 vCPU / 4–8 GB RAM is a reasonable starting size for this institute's scale — resize later against real measurements, don't provision for imagined peak load) running Nginx (reverse proxy + serving the built Vite frontend as static files), PHP-FPM (Laravel), MySQL, Redis, and a queue worker process (§7). Any competent VPS provider works; the choice of provider isn't architecturally significant here.

**Local development:** Docker Compose is a reasonable *optional* convenience for keeping MySQL/Redis versions consistent across contributors' machines — not required, and not the production deployment mechanism. Don't build a production container pipeline just because local dev used Docker; that's infrastructure complexity justified by convenience, not need.

---

## 5. Part B — CI/CD: the Missing Enforcement Loop

This is the concrete fix for `00_CURRENT_STATE_AUDIT.md` §7's root-cause finding — "nothing in the repo checks [the architecture rules]." A CI pipeline is what turns `01_TARGET_ARCHITECTURE.md` §3's file-size budget and tests-before-done rule from a document into something that actually blocks a bad merge.

**On every push and pull request:**
1. Run the full Pest suite (backend).
2. Run the full Vitest suite (frontend).
3. Lint/typecheck both (PHP static analysis, TypeScript `tsc --noEmit`).
4. **Fail the build — block the merge — if any of the above fail.** This is the one line item in this whole document that matters most: a CI check that can be bypassed isn't an enforcement loop, it's a suggestion.

**On merge to `main`:** auto-deploy to staging.
**On a tagged release:** deploy to production — a deliberate human action (tag + trigger), never automatic, even though staging deploys automatically. Production changes for a financial/academic system should never be a side effect of someone merging a branch.

**Deploy mechanism, kept simple:** pull the tagged commit, `composer install --no-dev`, `npm run build`, run pending migrations, restart PHP-FPM and the queue worker. A short maintenance window (`php artisan down` / `up`) around the migration step is enough — no blue-green or zero-downtime infrastructure is justified at this scale.

---

## 6. Part C — Environment Variables

Extends `04_REPO_BOOTSTRAP_AND_IAM_MODULE.md` §4's setup with the full production set:

| Variable | Notes |
|---|---|
| `APP_ENV` | `production` / `staging` / `local` |
| `APP_DEBUG` | **`false` in staging and production, always** — v2's audit found no equivalent issue, but this is the standard place a rushed deploy leaks stack traces to end users |
| `APP_KEY` | Laravel's encryption key — generated once per environment, never shared between staging and production, never committed |
| `APP_TIMEZONE` | **`Asia/Kabul`** — matches the institute's real locale (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §3); affects log timestamps, any scheduled task, and date-boundary logic like `05_ACADEMIC_MODULE.md`'s period calculations |
| `DB_*` | MySQL connection — production credentials never reused in staging |
| `REDIS_*` | |
| `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS` | The actual frontend origin per environment (`04` §4) |
| `ANTHROPIC_API_KEY` | **Optional** (`12_REPORTING_DASHBOARD_AND_LAUNCH_READINESS.md` §3a) — its absence is a supported, normal state, not a deployment blocker |

Secrets (DB password, `APP_KEY`, `ANTHROPIC_API_KEY`) live in the CI/CD platform's secret store and the server's `.env`, never in git — v2's `.gitignore` already had this right (`00_CURRENT_STATE_AUDIT.md` §6); v3 keeps it.

---

## 7. Part D — Process Management

Laravel's Redis-backed queue (`01_TARGET_ARCHITECTURE.md` §4 — payroll runs, notifications, report generation) needs a persistent worker, not a one-off command. Run `php artisan queue:work` under systemd (or Supervisor) with automatic restart on crash and on deploy. If any module later needs scheduled work (none of the twelve prior documents required one, but Laravel's scheduler is the standard mechanism if one arises), a single cron entry running `php artisan schedule:run` every minute is the whole setup — don't add a second scheduling system.

---

## 8. Part E — Backups

Automated daily MySQL dump, stored off the primary server (any object storage is fine), retained on a simple tiered schedule (e.g. daily for 30 days, monthly for a year). Set this up **before** any real data exists, not after — it's far cheaper to have running from day one than to retrofit once it actually matters. This is a direct, concrete answer to the same instinct that made version control non-negotiable in `00_CURRENT_STATE_AUDIT.md` §7: a system holding real tuition and payroll data needs a way to undo a mistake that isn't "hope it doesn't happen."

## 9. Part F — Monitoring & SSL

Nginx + Certbot (Let's Encrypt) for SSL — free, standard, auto-renewing. Laravel's built-in daily-rotated file logging is sufficient at this scale; a lightweight uptime check (any free/cheap external ping service) is enough operational visibility for a system this size — an enterprise APM platform would be infrastructure complexity this document's own scope doesn't justify.

---

## 10. Acceptance Criteria

- [ ] A pull request with a failing test cannot be merged — verified by actually breaking a test and confirming CI blocks it, not just reading the YAML.
- [ ] Staging deploys automatically on merge to `main`; production deploys only on an explicit tagged release.
- [ ] `APP_DEBUG=false` is confirmed in both staging and production — not just set in `.env.example`.
- [ ] A backup restore has been tested at least once before launch — an untested backup is not a backup.
- [ ] Server timezone and `APP_TIMEZONE` are both `Asia/Kabul`, confirmed with a real timestamp check, not assumed from configuration alone.

## 11. Definition of Done

Locked once §10 passes on both environments.

## 12. Rollback

A failed production deploy rolls back by redeploying the previous tag — this is why deploys are tag-based, not branch-based: there is always a previous known-good tag to fall back to.

## 13. Next Document

None currently planned — this closes the gap found while writing it, and with `00` through `13`, the series covers diagnosis, architecture, business logic, design, every module's build order, composition/launch, and now operations. Further documents follow the same process as before: something real surfaces during implementation, it gets one focused file, not a scope creep into this one.
