# TOEFL House ERP v3

A comprehensive Management Information System for TOEFL House educational institute, rebuilt from scratch with modern technologies and architecture.

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | Laravel 11 (PHP 8.3+), MySQL 8, Redis |
| **Frontend** | React 19, TypeScript, Vite, Tailwind CSS v4 |
| **UI Components** | shadcn/ui (Radix primitives) |
| **Server State** | TanStack Query |
| **Client State** | Zustand |
| **Forms** | React Hook Form + Zod |
| **Auth** | Laravel Sanctum (SPA/cookie mode) |
| **Routing** | React Router |
| **Testing** | Pest (backend), Vitest + RTL (frontend) |

## Architecture

### Module Map (8 modules + reporting layer)

| # | Module | Backend | Frontend |
|---|---|---|---|
| 1 | **Identity & Access (IAM)** | `server/app/Modules/Iam/` | `client/modules/iam/` |
| 2 | **Academic** | `server/app/Modules/Academic/` | `client/modules/academic/` |
| 3 | **People & HR** | `server/app/Modules/PeopleHr/` | `client/modules/people-hr/` |
| 4 | **Finance & Payroll** | `server/app/Modules/FinancePayroll/` | `client/modules/finance-payroll/` |
| 5 | **Platform Services** | `server/app/Modules/PlatformServices/` | `client/modules/platform-services/` |
| 6 | **CRM & Enrollment** | `server/app/Modules/CrmEnrollment/` | `client/modules/crm-enrollment/` |
| 7 | **Inventory** | `server/app/Modules/Inventory/` | `client/modules/inventory/` |
| 8 | **Funding & Impact** | `server/app/Modules/FundingImpact/` | `client/modules/funding-impact/` |
| — | **Reporting & Dashboard** | *reads module services* | `client/reporting/` |

### Module Internal Structure

**Backend (each module):**
```
app/Modules/{Module}/
  Http/Controllers/    # Thin — validate, call Service, return Resource
  Http/Requests/       # Form Request validation classes
  Http/Resources/      # API Resource response shaping
  Services/            # Business logic (the ONLY cross-module entry point)
  Models/              # Eloquent models (private to module)
  Policies/            # Laravel authorization policies
  routes.php           # Module routes (included from routes/api.php)
```

**Frontend (each module):**
```
modules/{module}/
  components/          # Views (<400 lines each)
  hooks/               # TanStack Query hooks
  store.ts             # Zustand UI-only state (optional)
  schemas.ts           # Zod schemas (validator + type source)
  api.ts               # Typed fetch calls
  index.ts             # Public interface (only exports imported elsewhere)
```

## Project Structure

```
toefl-house-v3/
├── client/                        # React app (Vite)
│   ├── modules/                   # 8 domain modules
│   ├── reporting/                 # Dashboard composition layer
│   ├── shared/                    # UI components, navigation, utils
│   ├── app/                       # Thin shell: routing + auth gate
│   └── src/                       # Entry point, CSS
├── server/                        # Laravel app
│   ├── app/Modules/               # 8 domain modules
│   ├── database/migrations/       # Flat, standard Laravel location
│   └── routes/api.php             # Includes each module's routes
└── docs/                          # Specification documents (01–13)
```

## Key Design Principles

1. **File size budget:** Soft warning at 200 lines, hard ceiling at 400 lines
2. **Module boundaries:** Each module exposes one public entry point; everything else is private
3. **No shared global store:** One small Zustand store per module, never one app-wide store
4. **Cross-module communication:** Service-to-Service calls only; never direct model access across modules
5. **Tests before done:** Every module requires tests before marking complete
6. **RBAC:** Scope-aware custom tables with 7-level hierarchy (org → campus → branch → dept → program → class → own)
7. **RTL-first:** Dari/Persian locale with logical CSS properties throughout
8. **Program Versioning:** Copy-on-write — enrollment snapshots terms at creation time

## Getting Started

### Frontend

```bash
cd client
npm install
npm run dev       # Start dev server at localhost:5173
npm run build     # Production build
```

### Backend (requires PHP 8.3+, Composer, MySQL 8, Redis)

```bash
cd server
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve    # Start at localhost:8000
```

## Locale

- **Currency:** Afghan Afghani (AFN)
- **Dates:** Gregorian ISO (YYYY-MM-DD)
- **Primary Language:** Dari/Persian (RTL)
- **Timezone:** Asia/Kabul

## Specification Documents

All specification documents are in `/docs/`:

| # | Document | Status |
|---|---|---|
| 01 | Target Architecture | Locked |
| 02 | Business Logic & Domain Contract | Locked |
| 03 | Design System & UX Standards | Locked |
| 04 | Repo Bootstrap & IAM Module | Locked |
| 05 | Academic Module | Locked |
| 06 | People & HR Module | Locked |
| 07 | Finance & Payroll Module | Locked |
| 08 | Platform Services Module | Locked |
| 09 | CRM & Enrollment Module | Locked |
| 10 | Inventory Module | Locked |
| 11 | Funding & Impact Module | Locked |
| 12 | Reporting, Dashboard & Launch Readiness | Locked |
| 13 | Infrastructure & Deployment | Locked |

## Roles

10 system roles (sorted by seniority):

1. **Owner** — Organization-wide access with 4 specific exclusions
2. **General Manager** — Branch-level with cross-branch access
3. **Head of Department** — Department-scoped academic access
4. **Finance Manager** — Branch finance operations (no Budget.Allocate)
5. **Receptionist** — Front desk: leads, students, payments, book sales
6. **Counselor** — CRM follow-up only
7. **Teacher** — Narrowest role: own classes, sessions, attendance, grades
8. **Data Entry** — Entry only, no delete, no finance
9. **Designer** — Templates and print only
10. **Donor Manager** — Funding/Impact + dashboard access
