# 10 — Inventory Module
### TOEFL House ERP v3 — Build Order

> **Status:** Locked once §9 is confirmed.
> **Depends on:** `01`–`09`, specifically Finance & Payroll's income-recording pipeline.
> **Audience:** AI coding agent or human developer, zero prior conversation context.
> **What this document is:** schema, the sale/refund flow, routes, acceptance tests. No PHP or TypeScript source.

---

## 1. Objective

Build Inventory: books, restock history, and sales — the smallest remaining module.

## 2. Scope / Non-Goals

**In scope:** everything in §4.

## 3. Preconditions

Finance & Payroll complete (this module calls its income-recording service, §5).

---

## 4. Part A — Database Schema

**`books`**: id, title, price, purchase_price(nullable), stock(int), is_chapter(bool — distinguishes a whole book from an individually-sold chapter), branch_id, entry_date, created_at.

**`book_restock_history`**: id, book_id(FK, cascade), date, quantity, price, purchase_price(nullable).

**`book_sales`**: id, book_id(FK), quantity, total_amount, discount_amount(default 0), net_amount, payment_method(enum: `cash`,`card`,`transfer` — **note this enum spells the third option differently from every other payment_method column in the system**, which uses `bank_transfer`; normalize to `bank_transfer` in v3 for consistency, it's the same real-world method), status(`completed`/`refunded`), date, customer_name, student_id(FK, nullable), branch_id, created_at.

---

## 5. Part B — Sale & Refund Flow (Verified, Not Assumed)

Checked directly against v2's route implementation rather than inferred from schema alone, since an earlier pass through this data suggested a possible gap that turned out not to exist:

**On sale:** in one transaction — decrement `books.stock`, insert the `book_sales` row, then call the **same shared income-recording function Finance & Payroll's payment flow uses** (`recordIncome`, `07_FINANCE_AND_PAYROLL_MODULE.md`'s `PaymentService`-equivalent territory) with `category` = `book` or `chapter` (from `is_chapter`). This writes the matching `financial_transactions` row *and* applies the 5%-of-income savings sweep (`02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §7.4 #12) automatically — book revenue is not a separate, unswept income stream. Preserve this: v3's `InventoryService.sellBook()` calls Finance & Payroll's income-recording service, it does not write its own parallel `financial_transactions` insert.

**On refund:** in one transaction — restock `books.stock` by the sold quantity, mark the sale `refunded`, decrement the `main_account_balance` setting directly, and insert a `financial_transactions` row (`type = 'expense'`) referencing the original book. Refunds are blocked if the sale is already `refunded` (`409`).

**Stock guard:** a sale is rejected (`409`) if requested quantity exceeds current stock — checked before the transaction starts, not after.

**Modernization, not a flagged decision:** v2's refund endpoint authorizes via a hardcoded legacy-role check (`manager`/`finance` only, bypassing the granular permission system entirely). v3 uses a real permission code (`Book.Refund` — add it to `02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md` §5.2's catalog if not already covered by an existing `Book.*` entry) through the standard `PermissionResolutionService`, like every other authorization check in this system. This is a straightforward consistency fix, not a behavior change worth a decision log entry.

---

## 6. Part C — Services

`app/Modules/Inventory/Services/`: `BookService` (CRUD, restock), `BookSaleService` (§5's sale/refund flow, calling into Finance & Payroll for income recording).

## 7. Part D — HTTP API

`GET/POST /api/books`, `POST /api/books/{id}/restock`, `GET /api/book-sales`, `POST /api/books/{id}/sell`, `POST /api/book-sales/{id}/refund`.

## 8. Part E — Frontend Inventory Module

Per `01` §7 / `03`: replaces `BooksView.tsx` (`00_CURRENT_STATE_AUDIT.md` §4, 1,279 lines) with `<400`-line components under `client/modules/inventory/components/`.

---

## 9. Acceptance Criteria

- [ ] A sale writes exactly one `financial_transactions` row via Finance & Payroll's shared income-recording service — not a second, module-local implementation.
- [ ] The 5%-savings sweep fires on book/chapter income exactly as it does on tuition income.
- [ ] Selling more than current stock is rejected before any row is written.
- [ ] A second refund on an already-refunded sale is rejected.
- [ ] `payment_method` accepts `bank_transfer`, matching every other payment-method field in the system (§4's normalization).

## 10. Definition of Done

Locked once §9 passes.

## 11. Rollback

New repository, no live dependents (`01` §3a).

## 12. Next Document

`11_FUNDING_AND_IMPACT_MODULE.md` — donors, campaigns, donations, scholarships, and impact reporting. The last of the eight modules.
