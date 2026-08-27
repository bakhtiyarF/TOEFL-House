<?php

/**
 * Generate the model factories this project never shipped.
 *
 * `database/factories/` is empty in the repository, yet the Pest suite calls
 * ::factory() on 16 models, so every such test died with
 * "Class X does not have the HasFactory trait".
 *
 * Definitions are derived from the live schema rather than guessed, so NOT NULL
 * columns and enum CHECK constraints are satisfied -- the latter is what made
 * hand-written values fail on SQLite.
 *
 * Run: node tools/artisan.mjs tools/generate-factories.php
 */

$root = '/home/user/TOEFL-House/toefl-house-v3/server';

/*
 * Self-contained bootstrap. tools/bootstrap.php already boots the app, but in
 * the php-wasm runtime the SPL chain it leaves behind stops resolving classes
 * the framework boot never touched, so Schema/DB report "class not found".
 * Requiring the autoloader and booting here (the pattern the seeders use)
 * restores on-demand resolution. Tooling workaround only -- under a real
 * `composer dump-autoload` + `php artisan` this block is unnecessary.
 */
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** class => [fqcn, table] for the models the test suite calls ::factory() on */
$targets = [
    'Branch'         => ['App\Modules\Iam\Models\Branch', 'branches'],
    'Student'        => ['App\Modules\Academic\Models\Student', 'students'],
    'User'           => ['App\Modules\Iam\Models\User', 'users'],
    'Role'           => ['App\Modules\Iam\Models\Role', 'roles'],
    'AcademicClass'  => ['App\Modules\Academic\Models\AcademicClass', 'classes'],
    'Teacher'        => ['App\Modules\PeopleHr\Models\Teacher', 'teachers'],
    'Program'        => ['App\Modules\Academic\Models\Program', 'programs'],
    'Level'          => ['App\Modules\Academic\Models\Level', 'levels'],
    'Invoice'        => ['App\Modules\FinancePayroll\Models\Invoice', 'invoices'],
    'Permission'     => ['App\Modules\Iam\Models\Permission', 'permissions'],
    'Session'        => ['App\Modules\Academic\Models\Session', 'sessions'],
    'Exam'           => ['App\Modules\Academic\Models\Exam', 'exams'],
    'Donor'          => ['App\Modules\FundingImpact\Models\Donor', 'donors'],
    'ProgramVersion' => ['App\Modules\Academic\Models\ProgramVersion', 'program_versions'],
    'Homework'       => ['App\Modules\Academic\Models\Homework', 'homework'],
    'Campaign'       => ['App\Modules\CrmEnrollment\Models\Campaign', 'campaigns'],
];

/** Allowed values per column, scraped from the CREATE TABLE statement. */
function enumValues(string $sql, string $col): array
{
    $q = preg_quote($col, '/');
    $patterns = [
        '/' . $q . '"?\s+[a-zA-Z]+[^,]*check\s*\(\s*"?' . $q . '"?\s+in\s*\(([^)]*)\)/i',
        '/enum\s*\(([^)]*)\)/i',
    ];
    foreach ($patterns as $p) {
        if (preg_match_all($p, $sql, $m)) {
            foreach ($m[1] as $list) {
                preg_match_all("/'([^']*)'/", $list, $vals);
                if ($vals[1]) {
                    return $vals[1];
                }
            }
        }
    }
    return [];
}

function valueFor(string $col, string $type, array $enums, bool $required, array $fkFactory): string
{
    if ($enums) {
        return "'" . $enums[0] . "'";
    }

    $c = strtolower($col);

    // Foreign keys: a NOT NULL foreign key needs a real parent or the insert
    // fails the constraint, so point it at that model's factory. A nullable one
    // stays null so tests can opt in.
    if (Str::endsWith($c, '_id') && $c !== 'id') {
        return $fkFactory[$c] ?? 'null';
    }
    if (Str::endsWith($c, '_uuid')) {
        return 'null';
    }
    if (in_array($c, ['created_at', 'updated_at', 'deleted_at', 'assigned_at',
                      'snapshot_at', 'generated_at'], true)) {
        return 'null';
    }
    if (Str::endsWith($c, '_at') || Str::endsWith($c, '_date') || Str::endsWith($c, '_time')
        || $c === 'date' || Str::contains($c, ['date', 'time'])) {
        return 'now()';
    }
    if (Str::startsWith($c, 'is_') || Str::startsWith($c, 'has_') || Str::startsWith($c, 'can_')) {
        return 'false';
    }
    if ($c === 'password') {
        return '\Illuminate\Support\Facades\Hash::make(\'password\')';
    }
    if ($c === 'email') {
        return 'fake()->unique()->safeEmail()';
    }
    if ($c === 'phone' || $c === 'mobile') {
        return "\'+93\' . fake()->numerify(\'7########\')";
    }
    if (Str::contains($c, ['amount', 'price', 'fee', 'salary', 'total', 'paid',
                           'discount', 'scholarship', 'rate'])) {
        return '0';
    }
    if (Str::contains($c, ['count', 'capacity', 'seats', 'duration', 'credits',
                           'marks', 'score', 'percent', 'year', 'number'])) {
        return '0';
    }
    if (Str::endsWith($c, '_json')) {
        return "'[]'";
    }
    if ($c === 'code') {
        return 'strtoupper(fake()->unique()->bothify(\'??#####\'))';
    }
    if (in_array($c, ['name', 'title', 'label', 'name_en', 'name_ps',
                      'name_dr', 'name_fa'], true)) {
        return 'fake()->words(2, true)';
    }
    if (in_array($c, ['description', 'notes', 'note', 'summary', 'address'], true)) {
        return 'fake()->sentence()';
    }
    if (in_array($c, ['slug', 'key', 'identifier'], true)) {
        return 'fake()->unique()->slug(2)';
    }

    $t = strtolower($type);
    if (str_contains($t, 'bool')) {
        return 'false';
    }
    if (str_contains($t, 'int') || str_contains($t, 'real')
        || str_contains($t, 'float') || str_contains($t, 'double')
        || str_contains($t, 'numeric')) {
        return '0';
    }

    return 'fake()->word()';
}

$fkFactory = [
    'branch_id'         => '\\App\\Modules\\Iam\\Models\\Branch::factory()',
    'program_id'        => '\\App\\Modules\\Academic\\Models\\Program::factory()',
    'program_version_id' => '\\App\\Modules\\Academic\\Models\\ProgramVersion::factory()',
    'level_id'          => '\\App\\Modules\\Academic\\Models\\Level::factory()',
    'class_id'          => '\\App\\Modules\\Academic\\Models\\AcademicClass::factory()',
    'session_id'        => '\\App\\Modules\\Academic\\Models\\Session::factory()',
    'student_id'        => '\\App\\Modules\\Academic\\Models\\Student::factory()',
    'teacher_id'        => '\\App\\Modules\\PeopleHr\\Models\\Teacher::factory()',
    'exam_id'           => '\\App\\Modules\\Academic\\Models\\Exam::factory()',
    'user_id'           => '\\App\\Modules\\Iam\\Models\\User::factory()',
    'role_id'           => '\\App\\Modules\\Iam\\Models\\Role::factory()',
    'permission_id'     => '\\App\\Modules\\Iam\\Models\\Permission::factory()',
    'campaign_id'       => '\\App\\Modules\\CrmEnrollment\\Models\\Campaign::factory()',
    'invoice_id'        => '\\App\\Modules\\FinancePayroll\\Models\\Invoice::factory()',
    'donor_id'          => '\\App\\Modules\\FundingImpact\\Models\\Donor::factory()',
];

$made = 0;
foreach ($targets as $short => [$fqcn, $table]) {
    if (!Schema::hasTable($table)) {
        echo "MARK SKIP $short: no table $table\n";
        continue;
    }

    $modelPath = $root . '/app/' . str_replace('\\', '/', Str::after($fqcn, 'App\\')) . '.php';
    if (!file_exists($modelPath)) {
        echo "MARK SKIP $short: model file not found ($modelPath)\n";
        continue;
    }

    // The real column list, straight from the CREATE TABLE statement.
    $sql = DB::selectOne("select sql from sqlite_master where name = ?", [$table])->sql ?? '';
    $columns = Schema::getColumns($table);

    $lines = [];
    foreach ($columns as $col) {
        $name = $col['name'];
        if ($name === 'id') {
            continue; // HasUuids / autoincrement manages the key
        }
        // Nullable columns with no default are omitted: the factory stays
        // readable and tests opt in explicitly.
        if (!empty($col['nullable']) && empty($col['default'])) {
            continue;
        }
        $val = valueFor($name, (string) ($col['type'] ?? ''), enumValues($sql, $name),
                       empty($col['nullable']), $fkFactory);
        $lines[] = "            '$name' => $val,";
    }

    /*
     * Laravel resolves a factory from the model name: anything under App\ that
     * is not App\Models\ keeps its sub-namespace, so
     * App\Modules\Iam\Models\User -> Database\Factories\Modules\Iam\Models\UserFactory.
     */
    $sub = Str::after($fqcn, 'App\\');
    $sub = preg_replace('/\\\\[^\\\\]+$/', '', $sub);
    $ns = 'Database\Factories' . ($sub !== '' ? '\\' . $sub : '');

    $body = "<?php\n\nnamespace $ns;\n\nuse $fqcn;\nuse Illuminate\\Database\\Eloquent\\Factories\\Factory;\n\n"
        . "/**\n * Generated by tools/generate-factories.php from the live schema.\n *\n"
        . " * @extends Factory<$short>\n */\nclass {$short}Factory extends Factory\n{\n"
        . "    protected \$model = $short::class;\n\n    public function definition(): array\n"
        . "    {\n        return [\n" . implode("\n", $lines) . "\n        ];\n    }\n}\n";

    // psr-4 maps Database\Factories\ -> database/factories/ (lowercase), so the
    // directory case must not follow the namespace case.
    $dir = $root . '/database/factories/' . str_replace('\\', '/', Str::after($ns, 'Database\\Factories'));
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents("$dir/{$short}Factory.php", $body);

    // Add HasFactory to the model when missing.
    $src = file_get_contents($modelPath);
    $trait = '';
    if (!str_contains($src, 'HasFactory')) {
        $src = preg_replace(
            '/^(use [^\n]+;)$/m',
            "$1\nuse Illuminate\\Database\\Eloquent\\Factories\\HasFactory;",
            $src,
            1
        );
        if (preg_match('/(\n    use )(Has[A-Za-z, ]+;)/', $src, $m)) {
            $src = str_replace($m[0], $m[1] . "HasFactory;\n\n    use " . $m[2], $src);
        } else {
            $src = preg_replace('/(class\s+\w+[^\n]*\n\{)/', "$1\n    use HasFactory;\n", $src, 1);
        }
        file_put_contents($modelPath, $src);
        $trait = ' +HasFactory';
    }

    echo "MARK OK $ns\\{$short}Factory ($table, " . count($lines) . " fields)$trait\n";
    $made++;
}

echo "MARK generated=$made\n";
