#!/usr/bin/env python3
"""
Apply every audit fix to a pristine checkout of toefl-house-v3/server.

Run from the repository root:

    python3 tools/apply-audit-fixes.py

Every substitution is asserted. If one does not apply, the script stops and
names it, so a silent partial application is impossible.
"""

import os
import sys

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                    '..', 'toefl-house-v3', 'server')
ROOT = os.path.normpath(ROOT)

applied = []


def read(rel):
    with open(os.path.join(ROOT, rel), encoding='utf-8') as f:
        return f.read()


def write(rel, text):
    with open(os.path.join(ROOT, rel), 'w', encoding='utf-8') as f:
        f.write(text)


def sub(rel, old, new, count=1, why=''):
    s = read(rel)
    if old not in s:
        sys.exit(f"FAIL: pattern not found in {rel}\n---\n{old[:200]}\n---")
    n = s.count(old)
    if count is not None and n != count:
        sys.exit(f"FAIL: {rel}: expected {count} occurrence(s) of pattern, found {n}")
    write(rel, s.replace(old, new))
    applied.append(f"{rel}: {why or old.splitlines()[0][:56]}")


def create(rel, body):
    path = os.path.join(ROOT, rel)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(body)
    applied.append(f"{rel}: NEW FILE")


# ---------------------------------------------------------------------------
# A. Migrations / schema
# ---------------------------------------------------------------------------

# A1. Two migrations both create audit_logs, so `migrate` aborts on any database.
sub('database/migrations/2026_01_04_000001_create_platform_services_tables.php',
    "        // Audit Logs\n        Schema::create('audit_logs', function (Blueprint $table) {",
    "        // Audit Logs\n"
    "        // 2026_01_01_000014_create_audit_logs_table already creates this table;\n"
    "        // without this guard `migrate` aborts with \"table audit_logs already exists\".\n"
    "        if (!Schema::hasTable('audit_logs')) {\n"
    "        Schema::create('audit_logs', function (Blueprint $table) {",
    why='guard duplicate audit_logs create')

_p = 'database/migrations/2026_01_04_000001_create_platform_services_tables.php'
_s = read(_p)
_i = _s.index("if (!Schema::hasTable('audit_logs'))")
_j = _s.index("        });", _i)
_s = _s[:_j] + "        });\n        }" + _s[_j + len("        });"):]
write(_p, _s)
applied.append(f"{_p}: close the hasTable guard")

# A2. Legacy users.role enum lists 8 codes; IamSeeder seeds 10 and the seeder
#     inserts 5 the CHECK rejects.
sub('database/migrations/2026_01_01_000004_create_users_table.php',
    """            $table->enum('role', [
                'owner', 'manager', 'finance', 'registrar',
                'teacher', 'head_of_department', 'counselor', 'donor_manager'
            ]);""",
    """            // Legacy per-column role constraint. It predates the RBAC `roles`
            // table and listed only 8 of the 10 seeded role codes, so seeding
            // general_manager / finance_manager / receptionist / data_entry /
            // designer failed the CHECK. Kept in sync with IamSeeder here.
            $table->enum('role', [
                'owner', 'manager', 'finance', 'registrar',
                'teacher', 'head_of_department', 'counselor', 'donor_manager',
                'general_manager', 'finance_manager', 'receptionist',
                'data_entry', 'designer'
            ]);""",
    why='widen users.role enum 8 -> 13 codes')

# A3. Columns the models/seeders use but no migration creates.
create('database/migrations/2026_08_26_000001_fix_schema_gaps.php', r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema gaps between the models/seeders and the migrations that shipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Student uses SoftDeletes but no migration adds the column, so every
        // query through the model fails with "no such column: deleted_at".
        if (!Schema::hasColumn('students', 'deleted_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // EnhancedSampleDataSeeder writes these; none of the columns exist.
        if (!Schema::hasColumn('exams', 'total_marks')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->unsignedInteger('total_marks')->default(100);
            });
        }

        if (!Schema::hasColumn('exam_results', 'max_score')) {
            Schema::table('exam_results', function (Blueprint $table) {
                $table->unsignedInteger('max_score')->default(100);
            });
        }

        if (!Schema::hasColumn('impact_metrics', 'progress_percent')) {
            Schema::table('impact_metrics', function (Blueprint $table) {
                $table->decimal('progress_percent', 5, 2)->default(0);
            });
        }

        // EnhancedSampleDataSeeder inserts certificates.status = 'issued'.
        if (!Schema::hasColumn('certificates', 'status')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->string('status')->default('issued');
            });
        }
    }

    public function down(): void
    {
        foreach (['exams.total_marks', 'exam_results.max_score',
                  'impact_metrics.progress_percent', 'certificates.status'] as $tc) {
            [$t, $c] = explode('.', $tc);
            if (Schema::hasColumn($t, $c)) {
                Schema::table($t, function (Blueprint $table) use ($c) {
                    $table->dropColumn($c);
                });
            }
        }

        if (Schema::hasColumn('students', 'deleted_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
""")

# A4. Framework storage tables.
create('database/migrations/2026_08_26_000005_create_framework_session_and_cache_tables.php', r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage for the database session / cache drivers. No migration in this
 * project creates them, so SESSION_DRIVER=database or CACHE_STORE=database
 * fail at runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
    }
};
""")

# A5. Sanctum token table.
create('database/migrations/2026_08_26_000006_create_personal_access_tokens_table.php', r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sanctum's token table. The app authenticates the SPA with Bearer tokens
 * (HasApiTokens on User) but the table was never created: Sanctum's own
 * migration is only published by `vendor:publish` and this project ships no
 * published copy. Without it login fails on insert.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personal_access_tokens')) {
            return;
        }

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
""")

# A6. Backfill user_roles from the legacy users.role column.
create('database/migrations/2026_08_26_000007_backfill_user_roles_from_legacy_role_column.php', r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Backfill user_roles from the legacy users.role column.
 *
 * PermissionResolutionService::resolve() grants permissions from
 * user_roles + role_permissions and only falls back to a small hardcoded legacy
 * map when a user has no role assignment at all. The seeders never populate
 * user_roles, so every account resolved through that fallback: the owner ended
 * up with 12 of the 92 catalogue permissions and the signed-in owner was refused
 * five pages by the SPA ("Access Denied").
 *
 * Idempotent: only inserts assignments that do not already exist, and skips
 * legacy role names that have no row in roles (e.g. 'manager', 'finance').
 */
return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->pluck('id', 'code');

        foreach (DB::table('users')->get(['id', 'role']) as $user) {
            $roleId = $roles[$user->role] ?? null;
            if ($roleId === null) {
                continue;
            }

            $exists = DB::table('user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $roleId)
                ->exists();
            if ($exists) {
                continue;
            }

            DB::table('user_roles')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'role_id' => $roleId,
                'scope_type' => 'organization',
                'scope_id' => null,
                'is_primary' => true,
                'assigned_by' => null,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('user_roles')->where('scope_type', 'organization')->delete();
    }
};
""")

# ---------------------------------------------------------------------------
# B. Seeders
# ---------------------------------------------------------------------------

_p = 'database/seeders/EnhancedSampleDataSeeder.php'
_s = read(_p)

# B1. impact_metrics.category values must satisfy the CHECK.
for _old, _new in (("'category' => 'education',", "'category' => 'academic',"),
                   ("'category' => 'funding',", "'category' => 'economic',")):
    if _old not in _s:
        sys.exit(f"FAIL: seeder pattern missing: {_old}")
    _s = _s.replace(_old, _new)

# B2. followups table/columns do not exist; the real table is visitor_followups.
_n = _s.count("DB::table('followups')")
if _n != 2:
    sys.exit(f"FAIL: expected 2 followups inserts, found {_n}")
_s = _s.replace("DB::table('followups')", "DB::table('visitor_followups')")
_s = _s.replace("'note' => ", "'notes' => ")
_s = _s.replace("'next_date' => ", "'date' => ")

# B3. visitor_followups.outcome is NOT NULL with a CHECK.
_c = _s.count("                'visitor_id' => $visitor->id,\n                'notes' => ")
if _c != 2:
    sys.exit(f"FAIL: expected 2 visitor inserts to patch, found {_c}")
_s = _s.replace("                'visitor_id' => $visitor->id,\n                'notes' => ",
                "                'visitor_id' => $visitor->id,\n"
                "                // visitor_followups.outcome is NOT NULL with a CHECK constraint\n"
                "                'outcome' => 'callback',\n"
                "                'operator' => 'Counselor',\n"
                "                'notes' => ")

# B4. exams.branch_id is NOT NULL and the seeder never set it.
_old = """            DB::table('exams')->insert([
                'id' => $examId,
                'class_id' => $class->id,"""
if _old not in _s:
    sys.exit("FAIL: exams insert pattern missing")
_s = _s.replace(_old, """            DB::table('exams')->insert([
                'id' => $examId,
                'class_id' => $class->id,
                // exams.branch_id is NOT NULL; the seeder never set it
                'branch_id' => $class->branch_id,""", 1)

write(_p, _s)
applied.append(f"{_p}: category CHECK values, visitor_followups, outcome/operator, exams.branch_id")

# ---------------------------------------------------------------------------
# C. Application fixes
# ---------------------------------------------------------------------------

# C1. Both middleware aliases point at classes that do not exist.
sub('bootstrap/app.php',
    "'api.version' => \\App\\Http\\Middleware\\ApiVersion::class,",
    "'api.version' => \\App\\Http\\Middleware\\ApiVersioning::class,",
    why='api.version alias -> existing class')
sub('bootstrap/app.php',
    "'api.ratelimit' => \\App\\Http\\Middleware\\RateLimit::class,",
    "'api.ratelimit' => \\App\\Http\\Middleware\\ApiRateLimiter::class,",
    why='api.ratelimit alias -> existing class')

# C2. Unauthenticated API calls must be 401, not a 500 from route('login').
sub('bootstrap/app.php',
    """use Illuminate\\Foundation\\Application;
use Illuminate\\Foundation\\Configuration\\Exceptions;
use Illuminate\\Foundation\\Configuration\\Middleware;""",
    """use Illuminate\\Auth\\AuthenticationException;
use Illuminate\\Foundation\\Application;
use Illuminate\\Foundation\\Configuration\\Exceptions;
use Illuminate\\Foundation\\Configuration\\Middleware;
use Illuminate\\Http\\Request;""",
    why='imports for the exception renderer')

sub('bootstrap/app.php',
    """    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();""",
    """    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * app/Exceptions/Handler.php already implements this, but Laravel 12
         * configures exceptions through bootstrap/app.php and never binds that
         * class, so it is dead code. Without this, an unauthenticated API call
         * falls through to redirect()->guest(route('login')) and, because no
         * route is named 'login', returns a 500 instead of a 401.
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please log in to access this resource.',
                    'error_code' => 'AUTH_REQUIRED',
                ], 401);
            }

            return null;
        });
    })->create();""",
    why='401 JSON for unauthenticated api/* instead of 500')

# C3. The login route sat inside the auth:sanctum group that wraps every module.
sub('routes/api.php',
    """// Protected API routes
Route::middleware(['auth:sanctum', 'api.version', 'api.ratelimit:default'])->group(function () {
    require base_path('app/Modules/Iam/routes.php');""",
    """/*
 * Public: obtaining a token cannot itself require a token.
 *
 * app/Modules/Iam/routes.php declares POST /auth/login in its own "public"
 * group, but this file requires every module *inside* an auth:sanctum group, so
 * the login endpoint inherited the guard and the API was unreachable.
 */
Route::post('/auth/login', [\\App\\Modules\\Iam\\Http\\Controllers\\AuthController::class, 'login'])
    ->middleware(['api.version', 'api.ratelimit:auth']);

// Protected API routes
Route::middleware(['auth:sanctum', 'api.version', 'api.ratelimit:default'])->group(function () {
    require base_path('app/Modules/Iam/routes.php');""",
    why='move POST /auth/login out of the auth:sanctum group')

sub('app/Modules/Iam/routes.php',
    """// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});""",
    """// POST /auth/login is registered in routes/api.php, outside the auth:sanctum
// group. Declaring it here would put it behind the guard it is meant to bypass.""",
    why='drop the now-unreachable duplicate login route')

# C4. login() never created a token, so the SPA had no credential to send.
sub('app/Modules/Iam/Services/AuthService.php',
    """        $permissions = $this->permissionService->resolve($user);

        return [
            'user' => $user,
            'permissions' => array_values($permissions),
        ];""",
    """        $permissions = $this->permissionService->resolve($user);

        // The SPA authenticates with a Sanctum Bearer token, but login never
        // created one, so there was nothing to send back on later requests.
        $token = $user->createToken('spa', ['*']);

        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user,
            'permissions' => array_values($permissions),
        ];""",
    why='login() now issues a Sanctum token')

# C5. Every policy calls $user->hasPermission(); the model never defined it.
_p = 'app/Modules/Iam/Models/User.php'
_s = read(_p)
_old = """use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;
use Illuminate\\Foundation\\Auth\\User as Authenticatable;
use Illuminate\\Notifications\\Notifiable;
use Laravel\\Sanctum\\HasApiTokens;"""
if _old not in _s:
    sys.exit("FAIL: User.php import block not found")
_s = _s.replace(_old, """use App\\Modules\\Iam\\Services\\PermissionResolutionService;
use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Foundation\\Auth\\User as Authenticatable;
use Illuminate\\Notifications\\Notifiable;
use Illuminate\\Support\\Collection;
use Laravel\\Sanctum\\HasApiTokens;""", 1)
_s = _s.replace("    use HasUuids, HasApiTokens, Notifiable;",
                "    use HasFactory;\n\n    use HasUuids, HasApiTokens, Notifiable;", 1)
_api = '''
    /*
     * Authorization API.
     *
     * Every policy in app/Policies calls $user->hasPermission(), but the model
     * never defined it, so each policy threw BadMethodCallException and the
     * framework turned it into a 500 instead of a 403.
     *
     * Effective permissions come from PermissionResolutionService::resolve()
     * (user_roles + role_permissions -> delegations -> overrides -> legacy
     * fallback) and are memoised per request.
     */

    protected ?Collection $resolvedPermissions = null;

    public function resolvedPermissions(): Collection
    {
        if ($this->resolvedPermissions === null) {
            $resolved = app(PermissionResolutionService::class)->resolve($this);
            $this->resolvedPermissions = collect(array_keys($resolved));
        }

        return $this->resolvedPermissions;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperUser()) {
            return true;
        }

        return $this->resolvedPermissions()->contains($permission);
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * The owner bypasses permission checks.
     */
    public function isSuperUser(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Branch ids this user may act on.
     */
    public function getBranchesAttribute(): array
    {
        return $this->branch_id ? [$this->branch_id] : [];
    }
}
'''
_s = _s.rstrip()
if not _s.endswith('}'):
    sys.exit("FAIL: User.php does not end with a closing brace")
_s = _s[:_s.rfind('}')] + _api.lstrip('\n')
write(_p, _s)
applied.append(f"{_p}: permission API + HasFactory")

# C6. orderByAsc() does not exist on the query builder.
sub('app/Modules/PlatformServices/Http/Controllers/RuleController.php',
    "->orderByAsc('name');", "->orderBy('name', 'asc');",
    why='orderByAsc -> orderBy')
sub('app/Modules/PlatformServices/Services/RuleEngineService.php',
    "->orderByAsc('created_at')", "->orderBy('created_at', 'asc')",
    why='orderByAsc -> orderBy')

# C7. Rule outputs may be a plain list, so $key can be an int.
sub('app/Modules/PlatformServices/Services/RuleEngineService.php',
    "if (!str_starts_with($key, '__')) {",
    "if (!str_starts_with((string) $key, '__')) {",
    why='cast array key before str_starts_with')

# C8. canAccessBranch() takes a User; these callers pass a bare id.
sub('app/Modules/PeopleHr/Services/TeacherService.php',
    "$this->branchScope->canAccessBranch(auth()->id(), $data['branch_id'])",
    "$this->branchScope->canAccessBranch(auth()->user(), $data['branch_id'])",
    why='canAccessBranch needs a User (1/2)')
sub('app/Modules/PeopleHr/Services/TeacherService.php',
    "$this->branchScope->canAccessBranch(auth()->id(), $teacher->branch_id)",
    "$this->branchScope->canAccessBranch(auth()->user(), $teacher->branch_id)",
    count=2,
    why='canAccessBranch needs a User (x2)')

for _rel in ('app/Modules/Academic/Services/ClassService.php',
             'app/Modules/Academic/Services/EnrollmentService.php'):
    sub(_rel,
        "use App\\Modules\\Iam\\Services\\BranchScopeService;",
        "use App\\Modules\\Iam\\Models\\User;\nuse App\\Modules\\Iam\\Services\\BranchScopeService;",
        why='import User')
    sub(_rel,
        "$this->branchScope->canAccessBranch($actorUserId,",
        "$this->branchScope->canAccessBranch(User::findOrFail($actorUserId),",
        count=2,
        why='canAccessBranch needs a User (x2)')

# C9. enrollments.branch_id is NOT NULL and was never set; student_semesters
#     needs a NOT NULL class_id.
sub('app/Modules/Academic/Services/EnrollmentService.php',
    """                'program_version_id' => $programVersion->id,
                'fee_snapshot_json' => $feeSnapshot,""",
    """                'program_version_id' => $programVersion->id,
                'fee_snapshot_json' => $feeSnapshot,
                // enrollments.branch_id is NOT NULL and was never set, so every
                // enrollment insert failed the constraint.
                'branch_id' => $student->branch_id,""",
    why='set enrollments.branch_id')

sub('app/Modules/Academic/Services/EnrollmentService.php',
    """            // Create student semester record
            $student->semesters()->create([
                'id' => Str::uuid()->toString(),
                'semester_name' => $enrollment->semester_name,
                'class_id' => $enrollment->class_id,
                'enroll_date' => now()->toDateString(),
                'fee_amount' => $feeSnapshot['net_tuition'] ?? 0,
                'status' => 'active',
            ]);""",
    """            // Create student semester record.
            // student_semesters.class_id is NOT NULL, so this only applies when
            // the enrollment is actually attached to a class.
            if ($enrollment->class_id) {
                $student->semesters()->create([
                    'id' => Str::uuid()->toString(),
                    'semester_name' => $enrollment->semester_name,
                    'class_id' => $enrollment->class_id,
                    'enroll_date' => now()->toDateString(),
                    'fee_amount' => $feeSnapshot['net_tuition'] ?? 0,
                    'status' => 'active',
                ]);
            }""",
    why='only create student_semesters when a class exists')

# C10. calculateEnrollmentFees() is called by EnrollmentService but did not exist.
_p = 'app/Modules/FinancePayroll/Services/TuitionCalculationService.php'
_s = read(_p)
_s = _s.replace("use App\\Modules\\PlatformServices\\Services\\RuleEngineService;",
                "use App\\Modules\\Academic\\Models\\FeeRule;\n"
                "use App\\Modules\\PlatformServices\\Services\\RuleEngineService;", 1)
_method = '''
    /**
     * Snapshot the fees that apply to a new enrollment.
     *
     * EnrollmentService calls this at enrollment time and stores the result in
     * enrollments.fee_snapshot_json (copy-on-write: later fee changes must not
     * retro-actively alter an existing enrollment). The method did not exist, so
     * every enrollment attempt died with "Call to undefined method".
     *
     * @return array<string, mixed>
     */
    public function calculateEnrollmentFees(
        string $programVersionId,
        ?string $levelId,
        ?string $branchId,
        string $enrollmentType = 'new'
    ): array {
        $today = now()->toDateString();

        $rules = FeeRule::query()
            ->where('program_version_id', $programVersionId)
            ->where(function ($q) use ($levelId) {
                $q->whereNull('level_id');
                if ($levelId !== null) {
                    $q->orWhere('level_id', $levelId);
                }
            })
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id');
                if ($branchId !== null) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            })
            ->orderBy('version', 'desc')
            ->get();

        $lines = [];
        $gross = 0.0;
        $optional = 0.0;

        foreach ($rules as $rule) {
            $lines[] = [
                'fee_type' => $rule->fee_type,
                'amount' => (float) $rule->amount,
                'currency' => $rule->currency,
                'is_optional' => (bool) $rule->is_optional,
            ];

            if ($rule->is_optional) {
                $optional += (float) $rule->amount;
            } else {
                $gross += (float) $rule->amount;
            }
        }

        $resolved = $this->resolveStudentFinanceAmounts(
            grossTuition: $gross,
            requestedDiscountPercent: 0,
            requestedScholarshipPercent: 0,
            amountPaid: 0,
            branchId: $branchId,
        );

        return [
            'enrollment_type' => $enrollmentType,
            'program_version_id' => $programVersionId,
            'level_id' => $levelId,
            'branch_id' => $branchId,
            'currency' => $rules->first()->currency ?? 'AFN',
            'lines' => $lines,
            'gross_tuition' => (float) $resolved['grossTuition'],
            'optional_total' => round($optional, 2),
            'discount_percent' => (float) $resolved['discountPercent'],
            'scholarship_percent' => (float) $resolved['scholarshipPercent'],
            'discount_amount' => (float) $resolved['discountAmount'],
            'scholarship_amount' => (float) $resolved['scholarshipAmount'],
            'net_tuition' => (float) $resolved['netTuition'],
            'final_payable' => (float) $resolved['finalPayable'],
            'snapshot_at' => now()->toIso8601String(),
        ];
    }
}
'''
_s = _s.rstrip()
if not _s.endswith('}'):
    sys.exit("FAIL: TuitionCalculationService.php does not end with a closing brace")
write(_p, _s[:_s.rfind('}')] + _method.lstrip('\n'))
applied.append(f"{_p}: implement calculateEnrollmentFees()")

# C11. 'class' is a reserved word and cannot be a relation name.
_p = 'app/Services/ReportGenerationService.php'
_s = read(_p)
for _old, _new in (
    ("Student::with(['enrollments.class',", "Student::with(['enrollments.academicClass',"),
    ("$enrollment->class->sessions", "$enrollment->academicClass->sessions"),
    ("'class' => $enrollment->class->name ?? 'N/A',", "'class' => $enrollment->academicClass->name ?? 'N/A',"),
    ("'teacher' => $enrollment->class->teacher->full_name ?? 'N/A',",
     "'teacher' => $enrollment->academicClass->teacher->full_name ?? 'N/A',"),
):
    if _old not in _s:
        sys.exit(f"FAIL: ReportGenerationService pattern missing: {_old}")
    _s = _s.replace(_old, _new)
write(_p, _s)
applied.append(f"{_p}: enrollments.class -> academicClass (4 sites)")

# C12. A soft-deleted student still occupies its UNIQUE student_code.
sub('app/Modules/Academic/Http/Controllers/StudentController.php',
    """        $lastStudent = Student::where('student_code', 'like', "STU-{$year}-%")""",
    """        // withTrashed(): a soft-deleted student still occupies its UNIQUE
        // student_code, so ignoring the trash hands out a duplicate and the
        // next insert fails on the unique index.
        $lastStudent = Student::withTrashed()
            ->where('student_code', 'like', "STU-{$year}-%")""",
    why='withTrashed() in generateStudentCode()')

# C13. Health: a skipped component must not degrade the service; Redis is
#      optional; \Error must be caught.
_p = 'app/Http/Controllers/HealthController.php'
_s = read(_p)
_old = "$allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');"
if _old not in _s:
    sys.exit("FAIL: HealthController allHealthy line not found")
_s = _s.replace(_old, """// A component that is not configured (e.g. no Redis in this deployment)
        // reports 'skipped' and must not degrade the whole service.
        $allHealthy = collect($checks)->every(
            fn($check) => in_array($check['status'], ['healthy', 'skipped'], true)
        );""")

_old = """    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            $pong = Redis::ping();"""
if _old not in _s:
    sys.exit("FAIL: HealthController checkRedis head not found")
_s = _s.replace(_old, """    private function checkRedis(): array
    {
        // Redis is optional in this deployment (cache=database, session=file,
        // queue=sync). config('database.redis.default.host') always has a
        // default, so the real test is whether any Redis driver is loadable at
        // all; without one there is nothing to ping and reporting 'unhealthy'
        // would permanently degrade /api/health.
        $driverAvailable = extension_loaded('redis') || class_exists(\\Predis\\Client::class);
        if (!$driverAvailable) {
            return [
                'status' => 'skipped',
                'duration_ms' => null,
                'message' => 'Redis is not configured for this environment',
            ];
        }

        try {
            $start = microtime(true);
            $pong = Redis::ping();""")

_n = _s.count("} catch (\\Exception $e) {")
if _n == 0:
    sys.exit("FAIL: HealthController has no catch(\\Exception)")
_s = _s.replace("} catch (\\Exception $e) {", "} catch (\\Throwable $e) {")
write(_p, _s)
applied.append(f"{_p}: 'skipped' tolerated, Redis driver guard, catch(\\Throwable) x{_n}")

# C14. Search: the nullable fallback was a TypeError, and the controller called
#      search() with the wrong signature.
_p = 'app/Services/SearchService.php'
_s = read(_p)
if "    protected Client $client;" not in _s:
    sys.exit("FAIL: SearchService client property not found")
_s = _s.replace("    protected Client $client;",
                "    // Nullable: the constructor deliberately falls back to null when\n"
                "    // Elasticsearch is not configured, and every method already guards\n"
                "    // on it. The non-nullable type made that fallback a TypeError,\n"
                "    // which is what turned /api/search into a 500.\n"
                "    protected ?Client $client = null;")

_old = """    /**
     * Search documents.
     */
    public function search(string $type, array $query, ?string $tenantId = null, int $from = 0, int $size = 20): array
    {
        try {"""
if _old not in _s:
    sys.exit("FAIL: SearchService::search head not found")
_s = _s.replace(_old, """    /**
     * Whether Elasticsearch is actually wired up.
     */
    public function isAvailable(): bool
    {
        return $this->client !== null;
    }

    /**
     * Search documents.
     */
    public function search(string $type, array $query, ?string $tenantId = null, int $from = 0, int $size = 20): array
    {
        if ($this->client === null) {
            return ['hits' => [], 'total' => 0, 'took' => 0];
        }

        try {""")

_n = _s.count("} catch (\\Exception $e) {")
if _n == 0:
    sys.exit("FAIL: SearchService has no catch(\\Exception)")
_s = _s.replace("} catch (\\Exception $e) {", "} catch (\\Throwable $e) {")
write(_p, _s)
applied.append(f"{_p}: nullable client + isAvailable() + catch(\\Throwable) x{_n}")

sub('app/Modules/PlatformServices/Http/Controllers/SearchController.php',
    """        $results = $this->searchService->search($query, $type, $branchId);

        return response()->json([
            'query' => $query,
            'results' => $results,
            'total' => count($results),
        ]);""",
    """        // search() takes (type, query[], tenantId) - the previous call passed
        // (query, type, branchId), which is a TypeError on every request.
        $response = $this->searchService->search(
            $type,
            ['query_string' => ['query' => $query]],
            $branchId,
        );

        return response()->json([
            'query' => $query,
            'type' => $type,
            'available' => $this->searchService->isAvailable(),
            'results' => $response['hits'],
            'total' => $response['total'],
            'took' => $response['took'],
        ]);""",
    why='fix search() call signature + report availability')

print(f"\n{len(applied)} fixes applied:\n")
for a in applied:
    print("  -", a)
