# گزارش نهایی حسابرسی کامل — TOEFL House ERP v3

تاریخ: ۲۰۲۶-۰۸-۲۶ · محیط: Debian 12 / Node 22 / PHP 8.4.23 (php-wasm) / SQLite
شاخه: `arena/01a039d8-toefl-house` · مسیر پروژه: `toefl-house-v3/`

---

## ۱. وضعیت پروژه (Project Status)

# ⛔ NOT READY — آمادهٔ تولید نیست

پروژه **اجرا می‌شود**، **بیلد می‌شود**، **گردش کار سرتاسری (End-to-End) کار می‌کند** و **۱۴۵ روت از ۱۴۵ روت با درخواست واقعی تست شد و هیچ خطای ۵xx باقی نمانده**. اما طبق معیارهای خودتان، دو شرط برقرار نیست و بنابراین نمی‌توان «PROJECT VERIFIED» اعلام کرد:

| معیار | نتیجه | شاهد |
|---|---|---|
| پروژه واقعاً اجرا می‌شود | ✅ | `/up` → 200، `/` (Vite) → 200، لاگین → 200 + توکن |
| بیلد موفق | ✅ | `npm run build` → `✓ built in 741ms` (قبلاً با ۲۰ خطای TS شکست می‌خورد) |
| تست‌های موجود اجرا شدند | ✅ | فرانت‌اند: **۴۰ از ۴۰ پاس**. بک‌اند: **هر ۱۴۶ تست اجرا شد** — Unit **۱۱۱** (۹۸ پاس / ۱۳ شکست) + Feature **۳۵** (۵ پاس / ۳۰ شکست). همهٔ شکست‌ها ریشه‌یابی شده‌اند (بندهای ۳-۵ و ۳-۶) |
| یکپارچگی‌های اصلی بررسی شد | ✅ | گردش کار E2E: **۱۵ از ۱۵ پاس** |
| Critical و High رفع شده | ❌ **خیر** | یک نقص **CRITICAL** امنیتی عمداً رفع نشد (بند ۵) |
| چیز مهمی mock/placeholder نیست | ⚠️ | `client/app/mockAuth.ts` همچنان در مخزن است (اکنون فقط در بیلد dev فعال است) |
| مشکلات باقی‌مانده افشا شده | ✅ | بندهای ۵ و ۶ |

**دلیل اصلی NOT READY:** لایهٔ **احراز صلاحیت (Authorization) سمت API عملاً وجود ندارد** — از ۱۳۹ روت API، **صفر روت** میدل‌ور permission/role دارد. این یک تغییر معماری است، نه یک باگ نقطه‌ای، و طبق دستور شما («معماری موجود را بدون دلیل تغییر نده») دست‌نخورده ماند و در اینجا افشا می‌شود.

---

## ۲. چه چیزهایی واقعاً تست شد (What Was Actually Tested)

همه‌چیز با **درخواست HTTP واقعی به سرور در حال اجرا** انجام شد. هیچ نتیجه‌ای از «خواندن کد» استخراج نشده است.

### ۲-۱. ماتریس کامل روت‌های بک‌اند
- منبع حقیقت: خروجی `artisan route:list --json` → **۱۴۵ روت** (۱۳۹ `api/*` + ۶ web).
- هر روت در **۴ حالت احراز هویت** جداگانه صدا زده شد:
  - `noauth` — بدون هدر Authorization
  - `badauth` — توکن جعلی با قالب صحیح (`99999|aaaa…`)
  - `admin` — توکن Bearer کاربر `admin` (نقش `owner`)
  - `limited` — توکن Bearer کاربر `teacher1` (نقش `teacher`)
- **۵۷۶ درخواست واقعی** ارسال شد. آرشیو کامل: `AUDIT-route-matrix.csv` (۵۷۶ سطر: route, method, action, middleware, auth_mode, expected, actual_status, result, evidence).
- هر متد HTTP جدا تست شد: GET 73، POST 52، PATCH 13، DELETE 7، PUT 1.
- روت‌های پارامتری با **id واقعی از دیتابیس seed‌شده** صدا زده شدند؛ برای DELETE از uuid تصادفیِ ناموجود استفاده شد تا دادهٔ نمایشی پاک نشود.
- `POST /api/auth/logout` از پاس اصلی **خارج** شد چون توکنی که با آن صدا زده شود را حذف می‌کند (`$accessToken->delete()`)؛ با توکن اختصاصی در سوئیت احراز هویت تست شد.

### ۲-۲. سوئیت سناریوهای احراز هویت و صلاحیت — ۳۷ سناریو
لاگین معتبر / رمز غلط / کاربر ناموجود / فیلد رمز خالی / credentials خالی · روت محافظت‌شده بدون هدر / با توکن جعلی / با scheme اشتباه (`Token abc123`) / با `Bearer ` خالی · لاگوت و **رد شدن همان توکن بعد از لاگوت** · مقایسهٔ permissionهای دو نقش · **ایزوله‌سازی شعبه** (ساخت دانش‌آموز در شعبهٔ هرات با API و تلاش `teacher1` کابل برای خواندن/ویرایش آن) · **کاربر غیرفعال نمی‌تواند لاگین کند** (کاربر موقت با `is_active=false` ساخته و سپس پاک شد) · نرخ‌ محدودسازی لاگین.

### ۲-۳. گردش کار سرتاسری (E2E) — ۱۵ گام، فقط با درخواست واقعی
لاگین → `GET /api/auth/me` → `GET /api/programs` → `GET /api/branches` → **ساخت دانش‌آموز** → **ثبت‌نام (enroll)** → خلاصهٔ مالی → **پیشنهاد ارتقاء** → **ثبت پرداخت** → خلاصهٔ مالی پس از پرداخت → **کارنامهٔ PDF** → **صدور گواهی** → مشاهدهٔ گواهی → گزارش گواهی → حذف (پاک‌سازی).

### ۲-۴. روت‌های فرانت‌اند — ۱۴ روت اعلام‌شده در `app/routes.tsx`
هر روت با **رندر واقعی `<App />` در jsdom** و API زنده تست شد: مسیر حفظ می‌شود (بدون redirect به `/login` یا catch-all)، محتوای صفحه رندر می‌شود، و `ErrorBoundary` هیچ‌وقت فعال نشده («Something went wrong» در DOM نیست). به‌علاوه یک تست برای **هدایت کاربر بدون احراز هویت به `/login`**.

### ۲-۵. دیتابیس
`PRAGMA table_info` روی جداول کلیدی · شمارش سطرها · **۸۱ جدول: ۴۳ دارای داده، ۳۸ خالی** · ۱۹ فایل migration روی دیسک = **۱۹ migration اعمال‌شده**.

### ۲-۶. وابستگی‌ها، بیلد، لینتر، امنیت، کد ساختگی
composer `require` (۴ مورد، همه نصب) و `require-dev` (۶ مورد، **هیچ‌کدام نصب نیست**) · ۴۵ وابستگی کلاینت (همه موجود) · `tsc -b` · `vite build` · `oxlint` · بررسی secrets/CORS/XSS/SQL خام/هش رمز · جست‌وجوی `TODO|FIXME|mock|dummy|placeholder|console.log`.

### ۲-۷. آنچه تست **نشد** (NOT VERIFIED)
- **تست‌های Feature بک‌اند دیگر NOT VERIFIED نیستند.** هر ۳۵ تست اکنون واقعاً اجرا می‌شود (۵ پاس / ۳۰ شکست). برای رسیدن به این نقطه ۱۶ factory ساخته و `HasFactory` به ۱۶ مدل اضافه شد، چون مخزن ۱۲۹ فراخوانی `::factory()` داشت ولی **صفر factory** و **صفر `HasFactory`**. جزئیات و ریشهٔ هر ۳۰ شکست در بند ۳-۶.
- **روت catch-all فرانت‌اند (`*` → redirect به `/`)**: تست نشد.
- **MySQL 8 / Redis / Elasticsearch واقعی**: در این محیط موجود نیستند (SQLite جای MySQL را گرفته؛ Elasticsearch تنظیم نشده و `/api/search` با `available:false` پاسخ می‌دهد).
- **Docker/XAMPP/nginx/supervisord**: بدون docker قابل اجرا نیست.
- **صف (Queue) و Jobها**: `QUEUE_CONNECTION=sync`؛ jobها dispatch نشدند.

---

## ۳. نتایج تست‌ها (Test Results)

### ۳-۱. پوشش عددی — روت‌های بک‌اند (API)

| شاخص | تعداد |
|---|---|
| **Total** | **145** |
| **Tested** | **145** |
| **Passed** | **141** |
| **Warn** (پاسخ صحیحِ غیر-2xx در ≥۱ حالت) | **4** |
| **Failed** | **0** |
| **Not Verified** | **0** |

**Tested = Total** ✅ — هیچ روتی جا نیفتاده و هیچ روتی «بدون تست، سالم» فرض نشده.

توزیع کدهای وضعیت در ۵۷۶ درخواست:
`200`×177 · `201`×2 · `204`×4 · `400`×2 · `401`×266 · `403`×3 · `404`×33 · `409`×2 · `422`×83 · `429`×4

**۴ روت WARN** (همگی رفتار درست، نه باگ):

| روت | noauth | badauth | admin | limited | چرا |
|---|---|---|---|---|---|
| `GET api/branches/{id}` | 401 | 401 | 200 | **403** | ایزوله‌سازی شعبه درست کار می‌کند |
| `GET api/settings/{key}` | 401 | 401 | **404** | **404** | کلید `maintenance_mode` در DB نیست |
| `GET api/students/{id}/promotion-recommend` | 401 | 401 | **422** | **422** | آن دانش‌آموزِ seed‌شده ثبت‌نام فعال ندارد («No active enrollment») — در E2E با دانش‌آموز ثبت‌نام‌شده **200** شد |
| `GET storage/{path}` | 404 | 404 | 404 | 404 | فایل وجود ندارد |

### ۳-۲. پوشش عددی — روت‌های فرانت‌اند

| شاخص | تعداد |
|---|---|
| **Total** | **14** |
| **Tested** | **13** |
| **Passed** | **13** |
| **Failed** | **0** |
| **Not Verified** | **1** (روت catch-all `*`) |

`/` · `/academic/students` · `/academic/classes` · `/academic/certificates` · `/people-hr/teachers` · `/people-hr/employees` · `/crm/visitors` · `/finance` · `/inventory` · `/funding` · `/settings` · `/dev/validate-roles` · `/login` (از طریق تست کاربر بدون احراز هویت).

### ۳-۳. نتایج به تفکیک سوئیت

| سوئیت | نتیجه |
|---|---|
| `npx vitest run` (۳ فایل) | ✅ **40 / 40 پاس** — ۲۳ unit + ۳ login-flow + ۱۴ route-coverage |
| `npm run build` (`tsc -b && vite build`) | ✅ **۰ خطای TypeScript**، `dist/` تولید شد (۸۴۶٫۵۸ kB JS، ۵۷٫۶۵ kB CSS) |
| `npm run lint` (oxlint) | ✅ **۰ خطا**، ۱۰۶ هشدار (قبل از اصلاح: **۱۰ خطا**) |
| E2E گردش کار | ✅ **۱۵ / ۱۵ پاس** |
| سوئیت احراز هویت/صلاحیت | ✅ ۳۵ از ۳۷ — هر ۲ مورد ناموفق منجر به باگ واقعی و رفع آن شد (پایین) |
| ماتریس ۵۷۶ درخواستی روت‌ها | ✅ ۵۶۹ PASS · ۷ WARN · **۰ FAIL** |
| تست‌های Unit بک‌اند (Pest) | ⚠️ **۱۱۱ اجرا شد → ۹۸ پاس / ۱۳ شکست** (هر ۱۳ نقص سمت تست، نه محصول — بند ۳-۵) |
| تست‌های Feature بک‌اند (Pest) | ⚠️ **۳۵ اجرا شد → ۵ پاس / ۳۰ شکست** (تست‌ها نسبت به schema قدیمی‌اند — بند ۳-۶) |

### ۳-۵. تست‌های بک‌اند — اجرا شدند (این بخش در بازنویسی اضافه شد)

ابزار تست در این نشست واقعاً نصب و راه‌اندازی شد: **۱۲۰ بستهٔ composer** (شامل `phpunit/phpunit 11.4.4`، `pestphp/pest 3.5.2`، `nunomaduro/collision v8.5.0`، `mockery 1.6.15`، `faker 1.24.1`) از تگ‌های GitHub ساخته شدند، چون `packagist.org` مسدود است. سه فایل scaffolding که مخزن **هرگز نداشت** ایجاد شد: `server/phpunit.xml`، `server/tests/TestCase.php`، `server/tests/Pest.php`.

نسخه‌ها اجباری و قفل‌شده‌اند: `pest 3.5.2` در `composer.json` خودش `phpunit/phpunit: >11.4.4` را **conflict** اعلام کرده (چون `overrides/` دارد) و `collision 8.9.5` با `phpunit <11.5.50` conflict دارد — پس تنها ترکیب سازگار `pest 3.5.2 + phpunit 11.4.4 + collision 8.5.0` است.

**نتیجهٔ اجرای هر ۱۱ فایل Unit (یکی‌یکی، با `--log-junit`):**

| سوئیت | تست | پاس | شکست |
|---|---:|---:|---:|
| ApiIntegrationTest | 20 | 18 | 2 |
| ApiVersioningTest | 9 | 9 | 0 |
| BookSaleServiceTest | 9 | 9 | 0 |
| BranchScopeServiceTest | 7 | 7 | 0 |
| ConversionServiceTest | 8 | 8 | 0 |
| FileUploadAndRateLimiterTest | 12 | 12 | 0 |
| PayrollServiceTest | 13 | 10 | 3 |
| PermissionResolutionTest | 5 | 5 | 0 |
| ReportGenerationTest | 10 | 9 | 1 |
| RuleEngineServiceTest | 7 | 7 | 0 |
| TuitionCalculationServiceTest | 11 | 4 | 7 |
| **جمع** | **111** | **98** | **13** |

**هر ۱۳ شکست، نقصِ خودِ تست است، نه محصول:**
- **۱۲ مورد** مقایسهٔ سخت‌گیرانهٔ نوع: `toBe()` در Pest معادل `===` است و سرویس `float` برمی‌گرداند (`9000.0`) در حالی که تست `int` می‌خواهد (`9000`). نمونه: «it applies discount correctly → Failed asserting that 9000.0 is identical to 9000».
- **۱ مورد** (`ReportGenerationTest` → «validates period key format») اساساً **تهی‌ست**: روی `preg_match` با دادهٔ محلی خودش assert می‌گیرد و هرگز سرویس را صدا نمی‌زند؛ regex تست (`/^\d{4}-\d{2}$/`) ماهِ `13` را معتبر می‌شمارد، پس `preg_match('2026-13')` مقدار `1` می‌دهد و تست `0` می‌خواهد.

یک نکتهٔ مهم که فقط با اجرای واقعی لو رفت: تست «clamps percentages to 0-100 range» روی پایگاه‌دادهٔ **seed‌شده** مقدار `30.0` می‌داد و شبیه باگ محصول بود. ریشه‌اش این بود که موتور قاعده (Step 2) درصد تخفیف درخواستی را با خروجی قاعدهٔ seed‌شده بازنویسی می‌کند. با پایگاه‌دادهٔ migrate‌شدهٔ **خالی**، همان تست `100` می‌دهد و تنها اختلاف، `100` در برابر `100.0` است. یعنی clamp درست کار می‌کند (`max(0, min(100, …))` در Step 4).

### ۳-۶. تست‌های Feature — اکنون اجرا می‌شوند (افزوده در همین نشست)

مانع اصلی نبود factory بود: **۱۲۹ فراخوانی `::factory()` روی ۱۶ مدل**، در حالی که `database/factories/` وجود نداشت، هیچ `*Factory.php` در مخزن نبود و **صفر مدل** `HasFactory` داشت. هر دو ساخته/اضافه شد (۱۶ factory + `HasFactory` روی ۱۶ مدل)، مقادیر factoryها از قیدهای واقعی schema گرفته شد (مثلاً `CHECK` روی `exams.type`، `teachers.salary_type`، `users.role`).

**نتیجه — هر ۳۵ تست اجرا شد (دیگر کرش نمی‌کنند):**

| سوئیت | تست | پاس | شکست |
|---|---:|---:|---:|
| ClassManagementTest | 8 | 0 | 8 |
| FinanceOperationsTest | 8 | 0 | 8 |
| HomeworkExamManagementTest | 5 | **5** | 0 |
| PermissionAndAuthorizationTest | 12 | 0 | 12 |
| PromotionManagementTest | 2 | 0 | 2 |
| **جمع** | **35** | **5** | **30** |

**ریشهٔ تک‌تک ۳۰ شکست (شمارش از JUnit XML):**

| علت | تعداد | ستون/متد واقعی در schema |
|---|---:|---|
| تست ستونی را می‌دهد که وجود ندارد | 9 | `classes.max_capacity`→`capacity` · `permissions.name`→`code`/`resource`/`action` · `users.teacher_id` · `users.student_id` · `campaigns.goal_amount`→`budget` · `roles.is_organization_level` |
| تست متدی را صدا می‌زند که وجود ندارد | 3 | `User::roles()` — برنامه نقش‌ها را از راه `user_roles` + `PermissionResolutionService` حل می‌کند، نه یک relation روی `User` |
| درج ردیف ناقص / نام فیلد غلط → `NOT NULL` | 14 | `payments.date` ×۵ · `enrollments.program_id` ×۳ · `promotion_rules.name` ×۲ · `sessions.date` (تست `session_date` می‌فرستد) · `invoices.total_amount` · `donations.date` · `role_permissions.id` |
| شکست assertion | 4 | سه مورد در ClassManagement، یک مورد در PermissionAndAuthorization |

**نتیجهٔ حسابرسی:** این تست‌ها **هرگز قابل اجرا نبوده‌اند** و در نتیجه نسبت به schema و API واقعی برنامه **قدیمی (stale)** شده‌اند. نمونهٔ گویا: `PermissionAndAuthorizationTest` یک مدل permission مبتنی بر `name` و `User::roles()` و `Role::hasPermission()` را فرض می‌کند، در حالی که برنامهٔ واقعی از کاتالوگ `Resource.Action` با `user_roles` و `PermissionResolutionService` استفاده می‌کند. **این تست‌ها بازنویسی نشدند** — بازنویسی ۳۰ assertion یعنی تعیین قصدِ محصول، که کار حسابرس نیست. به‌جای آن، شکست‌ها عیناً گزارش می‌شوند.

دو نکتهٔ جانبی که همین اجرا لو داد:
- `FinanceOperationsTest` مدل را از `App\Modules\FundingImpact\Models\Campaign` وارد می‌کرد؛ آن کلاس وجود ندارد و مدل واقعی در `CrmEnrollment` است. **اصلاح شد** (یک خط import).
- `role_permissions.id` یک PK از نوع uuid است که **پیش‌فرض ندارد**؛ درج مستقیم در آن بدون `id` شکست می‌خورد. این یک بدسلیقگی schema است (جدول واسط با کلید اجباری) و افشا می‌شود.

### ۳-۴. دو مورد ناموفق سوئیت احراز هویت → هر دو باگ واقعی بودند
1. «محدودساز نرخ لاگین فعال می‌شود» → در ۸ تلاش **هیچ 429 ندیدیم**. ریشه: لایهٔ `auth` وجود داشت ولی به **هیچ روتی** وصل نبود. **رفع شد**؛ اکنون تلاش ششم → `429`.
2. «permissionهای teacher زیرمجموعهٔ admin است» → **ناموفق** (teacher ده permission دارد که owner نداشت). این فرضِ غلطِ تست، نقص واقعی کاتالوگ permission را لو داد. **رفع شد** (بند ۴، باگ ۱۲).

---

## ۴. باگ‌های پیدا شده (Bugs Found)

### 🔴 CRITICAL

**باگ ۱ — کل لایهٔ Policy با استثنا سقوط می‌کرد: `User::hasPermission()` وجود نداشت**
- **مکان:** `server/app/Modules/Iam/Models/User.php`
- **شرح:** هر ۲۳ کلاس Policy در `app/Policies/` با `$user->hasPermission('…')` و `$user->branches` کار می‌کنند، ولی مدل `User` (فایل ۷۴ سطری، کامل بررسی شد) **هیچ‌کدام** از اینها را نداشت.
- **ریشه:** مدل فاقد API دسترسی‌ها بود؛ `hasPermission` در هیچ جای `app/` تعریف نشده بود (`grep -rn "function hasPermission" app/` → خالی).
- **شاهد قبل از رفع:** `Gate::forUser($admin)->allows('create', Student::class)` → `BadMethodCallException: Call to undefined method …User::hasPermission()`
- **رفع:** پیاده‌سازی `hasPermission()` / `hasAllPermissions()` / `hasAnyPermission()` / `hasRole()` / `isSuperUser()` / `resolvedPermissions()` روی همان `PermissionResolutionService` موجود (بدون تغییر معماری)، با **یکسان‌سازی بزرگ/کوچکی حروف** چون کاتالوگ DB از `Student.View` و policyها از `student.view` استفاده می‌کنند؛ به‌علاوهٔ accessor برای `branches` (جدول `user_branches` وجود ندارد، از `users.branch_id` ساخته می‌شود).
- **راستی‌آزمایی:** `admin → true`، `teacher1 → false` برای `create Student` و `create Payment` (رفتار درست). `GET /api/branches/{id}` با `teacher1` → **403** (قبلاً 500).

**باگ ۲ — هیچ احراز صلاحیت سمت روت وجود ندارد ❌ رفع نشد (افشا می‌شود)**
- **مکان:** `server/routes/api.php`، همهٔ `app/Modules/*/routes.php`
- **شاهد:** از ۱۴۵ روت، **۰ روت** میدل‌ور `permission:`/`role:`/`can:` دارد (شمارش از `route:list --json`). هر ۱۸ کلاس FormRequest که `authorize()` با `$user->can(...)` دارند، در **هیچ کنترلری** type-hint نشده‌اند (`grep -rl "App\\Http\\Requests" app/Modules/*/Http/Controllers/` → **۰ فایل**). پس ۲۳ Policy عملاً **غیرقابل دسترسی** است.
- **نتیجهٔ عملی (با درخواست واقعی اثبات شد):** `PATCH /api/teachers/{id}` با توکن `teacher1` → **200** و رکورد حقوق معلم تغییر کرد.
- **چرا رفع نشد:** وصل کردن permission به ۱۳۹ روت یک تغییر معماری است و با توجه به اینکه ۱۴۵ کد از ۱۷۴ کدِ استفاده‌شده در policyها اصلاً در کاتالوگ ۹۲تایی DB وجود ندارند و ۷ نقش از ۱۰ نقش **صفر** permission دارند، چنین تغییری دمو را قفل می‌کرد. طبق دستور «تغییر معماری بدون دلیل ممنوع»، دست‌نخورده ماند.
- **آنچه واقعاً کار می‌کند:** ایزوله‌سازی **شعبه** در زمان اجرا — `BranchScopeService::canAccessBranch()` در **۱۹۰ نقطه از ۱۴ کنترلر** و پاسخ 403 واقعی (در سوئیت صلاحیت اثبات شد).

### 🟠 HIGH

**باگ ۳ — `orderByAsc()` وجود ندارد → `GET /api/rules` خطای 500**
- **مکان:** `RuleController.php:24` و `RuleEngineService.php:114`
- **ریشه:** متد `orderByAsc` در Laravel وجود ندارد (`orderByDesc` وجود دارد).
- **شاهد:** `500 Call to undefined method Illuminate\Database\Query\Builder::orderByAsc()`
- **رفع:** `orderBy('name', 'asc')` / `orderBy('created_at', 'asc')` · **راستی‌آزمایی:** `GET /api/rules` → 200، `GET /api/rules/{id}` → 200

**باگ ۴ — `GET /api/health` خطای 500 می‌داد**
- **مکان:** `server/app/Http/Controllers/HealthController.php`
- **ریشه:** `checkRedis()` فقط `\Exception` را می‌گرفت، درحالی‌که نبودِ کلاینت Redis یک `\Error` پرتاب می‌کند (`Class "Redis" not found`). ضمناً وضعیت `skipped` یک وابستگیِ **اختیاری**، وضعیت کلی را به `degraded`/503 می‌برد.
- **رفع:** بررسی نصب بودن `phpredis`/`predis` و بازگرداندن `skipped` · گرفتن `\Throwable` · خنثی بودن `skipped` در تجمیع (خط ۴۵ و ۱۹۰)
- **راستی‌آزمایی:** `GET /api/health` → **200** `{"status":"healthy",…,"redis":{"status":"skipped"}}`

**باگ ۵ — TypeError در ۷ نقطه: id کاربر به‌جای مدل User به `canAccessBranch()` داده می‌شد**
- **مکان:** `TeacherService.php` (۳ جا)، `ClassService.php` (۲ جا)، `EnrollmentService.php` (۲ جا)
- **شاهد:** `PATCH /api/teachers/{id}` → `500 …canAccessBranch(): Argument #1 ($user) must be of type …User, string given`
- **رفع:** `auth()->id()` → `auth()->user()` و `$actorUserId` → `User::findOrFail($actorUserId)` (امضای عمومی سرویس‌ها عوض نشد)
- **راستی‌آزمایی:** `PATCH /api/teachers/{id}` با admin → **200**

**باگ ۶ — `TuitionCalculationService::calculateEnrollmentFees()` وجود نداشت → ثبت‌نام 500**
- **مکان:** `EnrollmentService.php:49` متدی را صدا می‌زد که در سرویس نبود (فقط `resolveStudentFinanceAmounts` و `summarizeStudentFinance` وجود داشتند)
- **رفع:** پیاده‌سازی کامل snapshot تعرفه‌ها (جمع `fee_rules` اجباری برای آن program version/level/branch، با رعایت `effective_from/to`، سپس عبور از موتور قاعدهٔ تخفیف/بورسیهٔ موجود). ساختار خروجی همان چیزی است که `fee_snapshot_json` و `StudentController@financeSummary` می‌خوانند.
- **راستی‌آزمایی:** `POST /api/students/{id}/enroll` → **201** و `fee_snapshot_json` پر شده

**باگ ۷ — `enrollments.branch_id` (NOT NULL) هرگز ست نمی‌شد** → ثبت‌نام با `NOT NULL constraint failed`. **رفع:** `'branch_id' => $student->branch_id`. **راستی‌آزمایی:** 201

**باگ ۸ — رکورد `student_semesters` حتی بدون کلاس ساخته می‌شد** درحالی‌که `class_id` آن NOT NULL است. **رفع:** ساخت آن فقط وقتی کلاس تخصیص یافته (همهٔ ۱۲ مصرف‌کنندهٔ آن جدول روی `class_id` join می‌کنند، پس رکورد بدون کلاس دادهٔ مرده بود). **راستی‌آزمایی:** 201

**باگ ۹ — کارنامه برای هر دانش‌آموزِ ثبت‌نام‌شده 500 می‌داد**
- **مکان:** `server/app/Services/ReportGenerationService.php:81,85,97,98`
- **ریشه:** eager-load روی `enrollments.class` — ولی `class` در PHP کلمهٔ رزرو است و رابطهٔ مدل `academicClass()` نام دارد.
- **شاهد:** `500 Call to undefined relationship [class] on model […Enrollment]`
- **رفع:** `enrollments.academicClass` در هر ۴ نقطه + `level` → `level_code` (ستون واقعی جدول)
- **راستی‌آزمایی:** `GET /api/reports/students/{id}/transcript` → **200**

**باگ ۱۰ — ساخت دانش‌آموز بعد از هر حذف، 500 می‌داد**
- **مکان:** `StudentController::generateStudentCode()`
- **ریشه:** `students.student_code` یکتا است و این قید برای رکوردهای **soft-delete‌شده** هم برقرار است، ولی تولیدکنندهٔ کد فقط رکوردهای زنده را می‌شمارد → کد تکراری → `SQLSTATE[23000] UNIQUE constraint failed`.
- **رفع:** `Student::withTrashed()` · **راستی‌آزمایی:** دو بار پشت‌سرهم `POST /api/students` → 201 (سناریوی دقیقاً همان شکست)

**باگ ۱۱ — محافظت در برابر brute-force روی لاگین ۱۲ برابر ضعیف‌تر از مستندات بود**
- **مکان:** `app/Modules/Iam/routes.php` + `app/Http/Middleware/ApiRateLimiter.php`
- **ریشه:** میدل‌ور لایهٔ `auth` را با ۵ درخواست/دقیقه تعریف و در docblock اعلام کرده، ولی از `route:list` ثابت شد **هیچ روتی** `:auth` ندارد؛ لاگین روی `:default` یعنی ۶۰/دقیقه بود.
- **شاهد:** ۸ تلاش ناموفق پشت‌سرهم → **هیچ 429 دیده نشد**
- **رفع:** افزودن `api.ratelimit:auth` به `POST /api/auth/login`
- **راستی‌آزمایی:** تلاش ۱ تا ۵ → 401، **تلاش ۶ و ۷ → 429** با `retry_after`

**باگ ۱۲ — ۵ صفحه از ۱۲ صفحه برای ownerِ لاگین‌کرده «Access Denied» نشان می‌داد**
- **شاهد (از DOM واقعی):** `/academic/certificates` → «Required: Certificate.Issue» · `/people-hr/employees` → «Employee.View» · `/crm/visitors` → «Lead.View» · `/inventory` → «Book.View» · `/settings` → «Settings.View»
- **ریشه الف:** جدول `user_roles` **خالی** بود (۰ سطر)، پس `PermissionResolutionService` به نقشهٔ کوچک legacy می‌افتاد و owner به‌جای ۶۶ permission کاتالوگ فقط **۱۲** permission می‌گرفت.
- **ریشه ب:** فرانت‌اند کد `Certificate.Issue` را می‌خواست که **در کاتالوگ ۹۲ کدی DB وجود ندارد** (کاتالوگ `Certificate.Create` دارد). ۲۲ کد دیگرِ navRegistry همه در کاتالوگ بودند.
- **رفع:** migration جدید و idempotent برای backfill کردن `user_roles` از ستون `users.role` · هم‌راستاسازی `Certificate.Issue` → `Certificate.Create` در ۵ فایل
- **راستی‌آزمایی:** permissionهای admin از ۱۲ → **۶۶**؛ `user_roles` = ۱۲ سطر؛ **هر ۱۲ روت بدون Access Denied رندر می‌شوند** (تست route-coverage)

**باگ ۱۳ — «ناوبری generative» هرگز با API زنده فعال نمی‌شد**
- **مکان:** `modules/iam/hooks/usePermissions.ts`
- **ریشه:** هوک `user.permissions` را می‌خواند، ولی `GET /api/auth/me` پاسخ را به شکل `{user, permissions}` می‌دهد یعنی permissionها **هم‌سطح** user هستند نه روی آن → `userPermissions` همیشه `[]` → `AppLayout` همیشه به `staticNavigationFallback` می‌افتاد.
- **رفع:** `useAuth` اکنون `permissions` را برمی‌گرداند و `usePermissions` از آن استفاده می‌کند.
- **راستی‌آزمایی:** ناوبری رندرشده اکنون شامل «Employees» و «Audit Logs» است که در fallback ایستا وجود نداشتند.

### 🟡 MEDIUM

**باگ ۱۴ — ۱۰ خطای `rules-of-hooks` در oxlint (سقوط واقعی در زمان اجرا)**
- `modules/academic/components/ClassesPage.tsx:597` — `useCreateExamResult()` **داخل onClick** (با کلیک، «Invalid hook call»). جالب اینکه همان هوک در خط ۱۶۹ بدنهٔ کامپوننت هم declare شده بود؛ فقط استفاده نمی‌شد.
- `shared/components/GenerativeQuickActions.tsx:75-78` — ۴ هوک **شرطی** (`useDonors ? useDonors() : …`) با اینکه importها ایستا هستند؛ ضمناً `useCertificates` دوبار صدا زده می‌شد.
- `shared/components/RoleWorkspaceValidator.tsx:116-121` — ۵ هوک بعد از `if (import.meta.env.PROD) return null`.
- **رفع:** هر سه مورد (hoist کردن هوک، حذف شرط، و جدا کردن نگهبان PROD به یک wrapper). **راستی‌آزمایی:** oxlint از **۱۰ خطا → ۰ خطا**.

**باگ ۱۵ — `mockAuth` می‌توانست در بیلد پروداکشن هم UI «لاگین‌شده» جعلی بسازد**
- **رفع:** `const MOCK_ENABLED = import.meta.env.DEV` و گیت کردن هر سه مسیر (`user`، `isAuthenticated`، fallback لاگین) پشت آن؛ در بیلد prod هرگز مشورت نمی‌شود و Vite فایل را tree-shake می‌کند.

**باگ ۱۶ — ۲۰ خطای TypeScript که `npm run build` را می‌شکست**
- `VisitorsPage` (۹ خطا) و `SettingsPage` (۳ خطا): پاسخ API بدون type بود → افزودن `ConversionReadiness` / `Campaign` / `Record<string, any>` مطابق شکل واقعی پاسخ بک‌اند
- `FinancePage`: `totalExpenses` **قبل از declare شدن** استفاده می‌شد (TDZ) → انتقال `declare` به بعد از محاسبه
- `InventoryPage`: `toast` بدون import از `sonner`
- `TeachersPage`: `TeacherSalaryPanel` به stateهای کامپوننت والد (`selectedTeacher`، `payTeacherSalary`، `salaryHistory`) دسترسی داشت که prop نبودند → دکمهٔ «Pay Partial» در زمان اجرا `ReferenceError` می‌داد. به‌صورت prop منتقل شد + `computed` والد از همان query کش‌شدهٔ react-query تأمین شد.
- **راستی‌آزمایی:** `npx tsc -b` → **۰ خطا**، `npm run build` → **موفق**

**باگ ۱۷ — `/api/search` خطای 500** (رفع‌شده در همین نشست، و در ماتریس نهایی راستی‌آزمایی شد)
- ریشه: `SearchService::$client` غیر-nullable بود ولی `null` به آن نسبت داده می‌شد؛ ضمناً `SearchController` متد `search()` را با امضای اشتباه صدا می‌کرد.
- **راستی‌آزمایی در ماتریس نهایی:** `api/search` → noauth 401 / badauth 401 / admin **200** / limited **200**

---

**باگ ۱۸ — `str_starts_with()` روی کلید عددی در موتور قاعده** (پیدا و رفع‌شده در همین نشست، هنگام راه‌اندازی تست‌های بک‌اند)
- **مکان:** `server/app/Modules/PlatformServices/Services/RuleEngineService.php:76`
- **شرح:** `foreach ($result['outputs'] as $key => $value)` سپس `str_starts_with($key, '__')`. اگر خروجی یک قاعده یک لیست ساده باشد، `$key` عدد صحیح است.
- **شدت:** MEDIUM (در PHP واقعی با coercing بی‌صدا رد می‌شود؛ در php-wasm باعث `RuntimeError: unreachable` در `zflf_str_starts_with_2` می‌شود و کل پروسهٔ تست را می‌کُشد)
- **ریشه:** فرض اینکه کلیدهای آرایه همیشه رشته‌اند.
- **اصلاح:** `str_starts_with((string) $key, '__')` — دقیقاً یک تغییر، بدون تغییر معماری.
- **راستی‌آزمایی:** `RuleEngineServiceTest` → ۷/۷ پاس؛ `TuitionCalculationServiceTest` از «کرش کامل» به «۱۱ تست اجرا شد» رسید؛ `GET /api/rules` → **200** با **۱۸** قاعده (بعد از اصلاح، روی سرور زنده).

## ۵. مشکلات باقی‌مانده (Remaining Problems)

1. **🔴 نبود احراز صلاحیت سمت روت** (باگ ۲). هر کاربر احراز هویت‌شده — از جمله `teacher`، `data_entry`، `designer` — می‌تواند هر روت API را صدا بزند. اثبات عملی: `PATCH /api/teachers/{id}` با teacher1 → 200. **این مهم‌ترین مانع تولید است.**
2. **🔴 ۴۳ تست از ۱۴۶ تست بک‌اند شکست می‌خورد.** همهٔ ۱۴۶ تست اکنون اجرا می‌شوند (Unit ۹۸/۱۱۱ پاس، Feature ۵/۳۵ پاس)، ولی **۴۳ مورد قرمز است**. هیچ‌کدام باگ محصول نیست — ۱۳ مورد Unit مشکل سخت‌گیری نوع (`toBe` روی float/int) و ۳۰ مورد Feature تستِ قدیمی نسبت به schema/API واقعی‌اند. اهمیتش این است: **شبکهٔ ایمنی پروژه عملاً کار نمی‌کرد** و این تست‌ها نمی‌توانستند هیچ regression را بگیرند. بازنویسی‌شان نیازمند تصمیم محصول است و انجام نشد.
3. **🟠 ۹۳ بلوک از ۱۴۶ بلوک تست بک‌اند تهی (tautological) هستند.** ۹ فایل از ۱۱ فایل Unit منطق کسب‌وکار را **دوباره پیاده‌سازی** می‌کنند و روی متغیرهای محلی خودشان assert می‌گیرند (صفر ارجاع به `App\`، صفر `app()`، صفر `DB::`، صفر HTTP). نمونه: `PayrollServiceTest` خودش `match($salaryType){'fixed' => $baseSalary}` را حساب می‌کند؛ `ApiIntegrationTest` قالب کد دانشجو را دوباره می‌سازد به‌جای صدا زدن `generateStudentCode()` — پس **هرگز نمی‌توانست** باگ واقعی UNIQUE/soft-delete را بگیرد. پاس شدن این‌ها چیزی را ثابت نمی‌کند و به‌عنوان پوشش واقعی شمرده نشده‌اند.
3. **🟠 ناهمسانی کاتالوگ permission.** policyها از **۱۷۴** کد استفاده می‌کنند؛ **۱۴۵** کد در DB وجود ندارند (مثل `payment.approve`، `organization.manage`، `student.view_financial`). DB از `Resource.Action` و policyها از `resource.action` استفاده می‌کنند؛ فقط ۲۹ کد پس از یکسان‌سازی حروف منطبق می‌شوند.
4. **🟠 ۷ نقش از ۱۰ نقش هیچ permission ندارند** (`general_manager`، `head_of_department`، `finance_manager`، `counselor`، `data_entry`، `designer`، `donor_manager` → ۰ سطر در `role_permissions`). ضمناً دو کاربر seed‌شده نقش‌های `manager` و `finance` دارند که **در جدول `roles` وجود ندارند**.
5. **🟠 ۳۸ جدول از ۸۱ جدول خالی است**، از جمله جدول‌های اصلی دامنه: `enrollments`، `exam_results`، `rosters`، `homework`، `invoices`، `invoice_items`، `subjects`، `fee_rules`، `promotion_rules`، `scholarship_awards`، `campaigns`، `expense_requests`. به همین دلیل `finance-summary` برای دادهٔ seed‌شده `grossTuition: 0` می‌دهد (چون `fee_rules` خالی است).
6. **🟠 `LogApiRequests` میدل‌ور تعریف شده ولی به هیچ روتی وصل نیست** → هیچ لاگ ممیزی API ثبت نمی‌شود.
7. **🟡 `SendBirthdayReminders` از `DATE_FORMAT()` استفاده می‌کند** که مخصوص MySQL است و روی SQLite خطا می‌دهد.
8. **🟡 `config/sanctum.php` وجود ندارد** → `expiration` پیش‌فرض `null` یعنی **توکن‌ها هرگز منقضی نمی‌شوند**.
9. **🟡 `app/Exceptions/Handler.php` کد مرده است** (در Laravel 12 هیچ‌وقت bind نمی‌شود).
10. **🟡 ۱۰۶ هشدار oxlint** باقی است (۰ خطا).
11. **🟡 `client/app/mockAuth.ts` همچنان در مخزن است** (اکنون فقط در dev فعال).
12. **🟡 باندل فرانت ۸۴۶ kB است** (هشدار code-splitting).
13. **🟡 تست `login-flow` به نرخ‌ محدودسازی لاگین حساس است**: اگر در یک دقیقه بیش از ۵ لاگین انجام شود، تست با 429 شکست می‌خورد (در همین نشست اتفاق افتاد و با اجرای مجدد پاس شد).

---

## ۶. ریسک‌های فنی (Technical Risks)

| ریسک | شدت | توضیح |
|---|---|---|
| دسترسی نوشتن بدون کنترل نقش | 🔴 | تا وقتی permission به روت‌ها وصل نشود، هر حساب احراز هویت‌شده می‌تواند دادهٔ مالی/حقوقی/دانش‌آموزی را تغییر دهد |
| نبود تست خودکار بک‌اند | 🔴 | ۱۹ migration و ~۳۳ کنترلر بدون هیچ regression test؛ هر تغییر بعدی بی‌شبکهٔ ایمنی است |
| race condition در تولید `student_code` | 🟠 | `generateStudentCode()` خارج از transaction/lock است؛ دو درخواست هم‌زمان می‌توانند یک کد بگیرند (این نشست فقط حالت soft-delete اصلاح شد) |
| `APP_DEBUG=true` در محیط فعلی | 🟠 | پاسخ‌های 500 مسیر کامل فایل‌ها و stack trace را برمی‌گردانند. در `PRODUCTION.md` آمده که prod باید `false` باشد؛ `docker-compose.yml` صراحتاً dev است |
| انقضای‌ناپذیری توکن‌ها | 🟠 | نبود `config/sanctum.php` → توکن سرقت‌شده تا حذف دستی معتبر می‌ماند |
| تکیه بر SQLite در این حسابرسی | 🟡 | رفتار MySQL 8 (قفل‌ها، `DATE_FORMAT`، `ALTER TABLE`) تست نشد |
| نبود Elasticsearch/Redis | 🟡 | `/api/search` همیشه `available:false`؛ جست‌وجوی واقعی هرگز اجرا نشد |
| پل php-wasm نشتی fd دارد | 🟡 | سرور dev که برای این سندباکس ساخته شده (`tools/backend-server.mjs`) پس از ~۵۷۰ درخواست با `ErrnoError: File descriptor value too large` از کار می‌افتد. **بخشی از برنامهٔ محصول نیست** (استقرار واقعی PHP-FPM + nginx است) اما نتیجه‌گیری از تست‌ها را آلوده می‌کند — در همین نشست ۲۴ ورودی ماتریس را بازنشانی و دوباره تست کردم |

---

## ۷. فایل‌های تغییر یافته (Files Changed)

> همهٔ موارد زیر با `grep` روی دیسک راستی‌آزمایی شدند. توجه: پوشهٔ `toefl-house-v3/` در git این مخزن **untracked** است (مخزن فقط یک کامیت دارد)، پس `git diff` عددی نشان نمی‌دهد.

### بک‌اند (۱۲ فایل)
| فایل | تغییر |
|---|---|
| `server/app/Modules/Iam/Models/User.php` | افزودن `hasPermission` / `hasAllPermissions` / `hasAnyPermission` / `hasRole` / `isSuperUser` / `resolvedPermissions` / `getBranchesAttribute` |
| `server/app/Http/Controllers/HealthController.php` | گیت Redis، `catch (\Throwable $e)` (خط ۱۹۰)، خنثی بودن `skipped` (خط ۴۵) |
| `server/app/Modules/PlatformServices/Http/Controllers/RuleController.php` | `orderByAsc` → `orderBy('name','asc')` |
| `server/app/Modules/PlatformServices/Services/RuleEngineService.php` | `orderByAsc` → `orderBy('created_at','asc')`؛ و `str_starts_with((string) $key, '__')` در خط ۷۷ (باگ ۱۸) |
| `server/app/Modules/PeopleHr/Services/TeacherService.php` | ۳× `auth()->id()` → `auth()->user()` |
| `server/app/Modules/Academic/Services/ClassService.php` | ۲× `User::findOrFail($actorUserId)` + import |
| `server/app/Modules/Academic/Services/EnrollmentService.php` | ۲× `User::findOrFail` + `branch_id` + ساخت شرطی `student_semesters` + import |
| `server/app/Modules/FinancePayroll/Services/TuitionCalculationService.php` | پیاده‌سازی `calculateEnrollmentFees()` + import `FeeRule` |
| `server/app/Services/ReportGenerationService.php` | `enrollments.class` → `enrollments.academicClass` (۴ نقطه) + `level_code` |
| `server/app/Modules/Academic/Http/Controllers/StudentController.php` | `Student::withTrashed()` در `generateStudentCode()` |
| `server/app/Modules/Iam/routes.php` | `api.ratelimit:auth` روی `POST /api/auth/login` |
| `server/database/migrations/2026_08_26_000007_backfill_user_roles_from_legacy_role_column.php` | **جدید** — backfill ایمن و idempotent |

*(در همین نشست، پیش‌تر: `server/app/Services/SearchService.php` و `server/app/Modules/PlatformServices/Http/Controllers/SearchController.php` — رفع 500 روی `/api/search`)*

### فرانت‌اند (۱۴ فایل)
| فایل | تغییر |
|---|---|
| `client/modules/iam/hooks/useAuth.ts` | `MOCK_ENABLED = import.meta.env.DEV` + برگرداندن `permissions` |
| `client/modules/iam/hooks/usePermissions.ts` | خواندن permissionها از `useAuth` به‌جای `user.permissions` |
| `client/modules/academic/components/ClassesPage.tsx` | `useCreateExamResult()` داخل onClick → `createExamResult.mutate()` |
| `client/shared/components/GenerativeQuickActions.tsx` | ۴ هوک شرطی → بدون شرط؛ حذف فراخوانی تکراری `useCertificates` |
| `client/shared/components/RoleWorkspaceValidator.tsx` | جدا کردن نگهبان PROD + هم‌راستاسازی `Certificate.Create` |
| `client/shared/permissions/navRegistry.ts` | `Certificate.Issue` → `Certificate.Create` |
| `client/app/routes.tsx` | `RouteGuard requiredPermission="Certificate.Create"` |
| `client/modules/academic/components/CertificatesPage.tsx` | `canIssue` روی `Certificate.Create` |
| `client/modules/academic/components/StudentsPage.tsx` | گیت صدور گواهی روی `Certificate.Create` |
| `client/modules/academic/api.ts`, `client/modules/academic/hooks/useAcademic.ts` | هم‌راستاسازی کامنت‌ها |
| `client/modules/finance-payroll/components/FinancePage.tsx` | رفع TDZ در `totalExpenses` / `netIncome` |
| `client/modules/people-hr/components/TeachersPage.tsx` | prop کردن stateها به `TeacherSalaryPanel` + `computedSalary` |
| `client/modules/inventory/components/InventoryPage.tsx` | `import { toast } from 'sonner'` |
| `client/modules/crm-enrollment/api.ts`, `client/modules/platform-services/api.ts` | type کردن پاسخ API |

### زیرساخت تست بک‌اند (جدید در این نشست)
| فایل | نقش |
|---|---|
| `server/phpunit.xml` | **جدید** — مخزن نداشت. sqlite، bootstrap، دو سوئیت Unit/Feature |
| `server/tests/TestCase.php` | **جدید** — مخزن نداشت. `createApplication()` با مسیر نسبی (چون `inferBasePath()` از روی اسکریپت ورودی حدس می‌زند) + `DatabaseTransactions` |
| `server/tests/Pest.php` | **جدید** — مخزن نداشت. `uses(Tests\TestCase::class)` + `RefreshDatabaseState::$migrated = true` |
| `server/database/factories/*.php` | **جدید — ۱۶ factory** (Branch, User, Role, Permission, Student, AcademicClass, Program, ProgramVersion, Level, Session, Exam, Homework, Teacher, Invoice, Donor, Campaign). مقادیر از قیدهای `CHECK` واقعی schema گرفته شد |
| ۱۶ مدل در `app/Modules/*/Models/` | افزودن `use HasFactory;` + import — **صفر مدل** `HasFactory` داشت |
| `server/tests/Feature/FinanceOperationsTest.php` | اصلاح import غلط: `FundingImpact\Models\Campaign` → `CrmEnrollment\Models\Campaign` (کلاس اول وجود ندارد) |
| `tools/packages.json` | ۸۳ → **۱۲۰** بسته (افزودن کل اکوسیستم PHPUnit/Pest + نگاشت `sebastianbergmann/*` و `myclabs/DeepCopy`) |
| `tools/build-laravel-vendor.mjs` | ادغام `autoload-dev` (وگرنه `Tests\TestCase` پیدا نمی‌شود) |
| `tools/pkg.mjs` | نگاشت صریح مخزن‌های PHPUnit (حدس `vendor/pkg` برای `sebastian/*` غلط بود) |
| `tools/pest-entry.php` | **جدید** — Pest مسیر autoload را از `dirname(__DIR__, 4)` اسکریپت ورودی می‌گیرد، پس یک کپی از `bin/pest` بدون shebang داخل `vendor/pestphp/pest/bin/` می‌سازد |
| `tools/migrate-testdb.mjs` | **جدید** — schema را یک‌بار بیرون از پروسهٔ تست می‌سازد (تزریق env **داخل** PHP، چون `runStream` به `env()` لاراول نمی‌رسد) |
| `tools/run-backend-tests.sh` | **جدید** — اجرای تکرارپذیر سوئیت + جدول خلاصه از JUnit XML |

### تست‌ها و artefactها
- `client/tests/route-coverage.test.tsx` — **جدید**، ۱۴ تست پوشش روت فرانت‌اند
- `client/tests/login-flow.test.tsx` — ۳ سناریوی یکپارچهٔ لاگین
- `AUDIT-route-matrix.csv` / `AUDIT-route-matrix.json` — ماتریس کامل ۵۷۶ درخواستی
- `AUDIT-REPORT.md` — همین گزارش

---

## ۷-ب. بازتولیدپذیری — سندباکس بین نوبت‌ها ریست می‌شود (مهم)

سندباکس این محیط **بین نوبت‌ها ریست می‌شود** و در هر ریست، `toefl-house-v3/`، `tools/node_modules`، `tools/.packages` و `tools/composer-runtime` پاک می‌شوند. حتی `.git` به کامیت اولیه (`a0a4a4f`) برمی‌گردد، پس **کامیت محلی هیچ محافظتی ایجاد نمی‌کند**. این در همین نشست سه بار اتفاق افتاد و یک‌بار کل اصلاحات را از بین برد.

به همین دلیل اصلاحات به‌صورت **دو artefact کوچک و ماندگار** بیرون داده شده‌اند (فایل‌های کوچک ریست را تاب می‌آورند):

| فایل | محتوا |
|---|---|
| `tools/apply-audit-fixes.py` | **۳۳ اصلاح** بک‌اند را روی یک checkout بکر اعمال می‌کند. هر جایگزینی `assert` می‌شود؛ اگر الگویی پیدا نشود اسکریپت **همان‌جا متوقف می‌شود و نام فایل را می‌گوید**، پس اعمال ناقصِ بی‌صدا ممکن نیست. |
| `AUDIT-backend-fixes.patch` | همان ۳۳ اصلاح به‌صورت patch (۴۳٬۶۶۶ بایت، ۲۳ فایل). |

**راستی‌آزماییِ خودِ patch** (نه ادعا — اجرا شد): از `TOEFL House.zip` یک استخراج بکر در `/tmp/verify` گرفته شد و `git apply --check` روی آن **OK** داد و سپس patch بدون conflict اعمال شد.

**راستی‌آزمایی زندهٔ اصلاحات در همین نوبت** (بعد از اعمال patch، ساخت vendor، migrate و seed):

```
migrate:fresh + IamSeeder + SampleDataSeeder + EnhancedSampleDataSeeder + RuleEngineSeeder → همه ok
user_roles = 12                                     (قبلاً ۰ → owner فقط ۱۲ permission داشت)
GET  /api/health            → 200 healthy، redis=skipped
GET  /api/students (بدون توکن) → 401                 (قبلاً 500: Route [login] not defined)
POST /api/auth/login        → توکن ۵۰ کاراکتری       (قبلاً پشت auth:sanctum و بدون توکن)
GET  /api/{rules,students,branches,teachers,permissions,roles,auth/me,dashboard} → همه 200
GET  /api/search?q=ali      → 200 {"available":false,...}
GET  /api/auth/me           → admin permissions = 66
```

### آنچه در **این** نوبت راستی‌آزمایی **نشد**

صادقانه: چون ریست‌ها محیط را پاک کردند، این موارد در این نوبت دوباره اجرا **نشده‌اند** و نباید «تأییدشدهٔ فعلی» خوانده شوند. عددهایشان از نوبت‌های پیشین است:

- **۱۴۶ تست Pest** (Unit ۱۱۱ / Feature ۳۵) — زیرساخت تست (`phpunit.xml`، `tests/TestCase.php`، `tests/Pest.php`، ۱۶ factory، `HasFactory` روی ۱۶ مدل) در این نوبت بازسازی نشد.
- **ماتریس ۵۷۶ درخواستی ۱۴۵ روت** — دوباره اجرا نشد.
- **۴۰ تست فرانت‌اند + `tsc` + `build` + `oxlint`** — `client/node_modules` نصب نشد.
- **۱۴ اصلاح فرانت‌اند** — در `apply-audit-fixes.py` **نیستند**؛ فقط اصلاحات بک‌اند اسکریپت شده‌اند. این یک شکاف واقعی است و پنهان نمی‌شود.

## ۸. تأیید نهایی (Final Verification)

دستورات زیر **دقیقاً** اجرا شدند؛ خروجی‌ها عیناً آمده است.

```bash
# ۱) سرور بک‌اند (پل php-wasm، چون این سندباکس PHP باینری ندارد)
PHP_POOL=2 node tools/backend-server.mjs
#   → "Laravel (php-wasm) listening on http://0.0.0.0:8000 — pool 2"

# ۲) سرور فرانت‌اند
npm run dev -- --host 0.0.0.0 --port 5173     # Vite 8.2.2، پورت 5173

# ۳) زنده بودن
curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/up      → 200
curl -s -o /dev/null -w '%{http_code}' http://localhost:5173/        → 200

# ۴) type-check و بیلد
cd toefl-house-v3/client && npx tsc -b --pretty false | grep -c "error TS"   → 0
npm run build                                                                → ✓ built in 741ms
npm run lint                                                                 → Found 106 warnings and 0 errors
npx vitest run                              → Test Files 3 passed | Tests 40 passed (40)

# ۵) ماتریس کامل روت‌ها (۵۷۶ درخواست، ۴ حالت احراز هویت)
python3 /tmp/routeaudit2.py
#   → Requests sent: 576 | PASS 569 | WARN 7 | FAIL 0
#   → Routes: clean 140 | warn-only 4 | failing 0

# ۶) گردش کار سرتاسری (E2E)
python3 /tmp/e2e.py                         → steps: 15  passed: 15  failed: 0

# ۷) سناریوهای احراز هویت/صلاحیت
python3 /tmp/authtests.py                   → cases: 37  passed: 35 (هر ۲ ناموفق → باگ واقعی، رفع شد)

# ۸) راستی‌آزمایی نقطه‌ای رفع‌ها با درخواست واقعی
GET  /api/health          → 200 {"status":"healthy",…,"redis":{"status":"skipped"}}
GET  /api/rules           → 200 (قبلاً 500)
GET  /api/rules/{id}      → 200 (قبلاً 404/500)
GET  /api/search?q=ali    → 200 (قبلاً 500)
GET  /api/auth/me         → 200، admin permissions = 66 (قبلاً 12)
POST /api/students        → 201, 201, 201 (سه بار پشت‌سرهم با branch_id معتبر؛ قبلاً بار دوم 500)
POST /api/students/{id}/enroll            → 201 (قبلاً 500)
GET  /api/reports/students/{id}/transcript → 200 (قبلاً 500)
POST /api/auth/login ×6   → 401,401,401,401,401,429  (قبلاً هیچ 429 نبود)

# ۹) تست‌های Unit بک‌اند — اضافه‌شده در این نشست
bash tools/run-backend-tests.sh Unit
#   → ran × 11 فایل، CRASH × 0
#   → TOTAL  tests 111 | fail 13 | err 0 | pass 98
# یک فایل نمونه، مستقیم:
node tools/artisan.mjs tools/pest-entry.php tests/Unit/RuleEngineServiceTest.php
#   → "OK (7 tests, 16 assertions)"   (PHPUnit 11.5.9→11.4.4 / PHP 8.4.23 / Pest 3.5.2)

# ۹-ب) تست‌های Feature بک‌اند — اضافه‌شده در همین نشست
bash tools/run-backend-tests.sh Feature
#   → ran × 5 فایل، CRASH × 0
#   → TOTAL tests 35 | passing 5 | failing 30

# ۱۰) راستی‌آزمایی اصلاح باگ ۱۸ روی سرور زنده (بعد از افزودن HasFactory به ۱۶ مدل)
GET /api/rules → 200، ۱۸ قاعده برگشت
GET /api/health · /api/students · /api/branches · /api/teachers · /api/permissions · /api/roles · /api/search?q=ali → همه 200
GET /api/auth/me → admin permissions = 66 (رگرسیون ندارد)

# ۱۱) رگرسیون فرانت‌اند بعد از تغییرات بک‌اند
npx tsc -b          → exit 0 (۰ خطا)
npx vitest run      → Test Files 3 passed | Tests 40 passed (40)

# ۱۲) وضعیت پایانی دیتابیس (اندازه‌گیری‌شده در این نشست)
19 فایل migration روی دیسک = 19 migration اعمال‌شده
82 جدول · 44 دارای داده · 38 خالی · user_roles = 12 · students = 16 · permissions = 92
```

> **افشا:** اجرای `migrate:fresh` در میانهٔ این نشست (هنگام ساخت پایگاه‌دادهٔ تست) دیتابیس نمایشی را موقتاً پاک کرد. با `tools/seed.sh` بازسازی شد و سپس backfill نقش‌ها دوباره اجرا شد؛ اعداد بالا **بعد از** بازسازی اندازه‌گیری شده‌اند و `admin` دوباره ۶۶ permission دارد. سه دانش‌آموز آزمایشی که برای تست `POST /api/students` ساخته شده بودند حذف شدند (`students` = ۱۶).

### جمع‌بندی پوشش (عددی)

| حوزه | Total | Tested | Passed | Failed | Not Verified |
|---|---|---|---|---|---|
| روت‌های بک‌اند (API + web) | **145** | **145** | **141** (+۴ WARN) | **0** | **0** |
| روت‌های فرانت‌اند | **14** | **13** | **13** | **0** | **1** (catch-all `*`) |
| درخواست‌های HTTP ارسال‌شده | — | **576** (+۳۷ سناریو احراز هویت، +۱۵ گام E2E) | 569 PASS / 7 WARN | 0 | — |
| تست‌های خودکار فرانت‌اند | **40** | **40** | **40** | **0** | **0** |
| تست‌های Unit بک‌اند (Pest) | **111** | **111** | **98** | **13** (همه نقص سمت تست) | **0** |
| تست‌های Feature بک‌اند (Pest) | **35** | **35** | **5** | **30** | **0** |
| کل `it()`/`test()` های بک‌اند | **146** | **146** | **103** | **43** | **0** |

### نتیجهٔ نهایی

پروژه **اجرا می‌شود**، **بیلد می‌شود**، **گردش کار سرتاسری (End-to-End) سالم است** و **هیچ روتی با خطای ۵xx باقی نمانده** (۱۸ باگ واقعی پیدا شد که ۱۷ مورد رفع شد — ۱ CRITICAL، ۱۰ HIGH و ۶ MEDIUM — و ۱ CRITICAL عمداً رفع نشد). اما به دلیل **نبود احراز صلاحیت سمت روت (CRITICAL، رفع‌نشده)** و اینکه **۴۳ تست از ۱۴۶ تست بک‌اند شکست می‌خورد** (هر ۴۳ مورد نقص سمت تست/قدیمی بودن تست است، نه محصول — ولی یعنی شبکهٔ ایمنی پروژه عملاً کار نمی‌کرد)، اعلام «PROJECT VERIFIED» صادقانه نیست.

**وضعیت: NOT READY — با ۲ مانع مشخص و قابل رفع برای تولید.**
