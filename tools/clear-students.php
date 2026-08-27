<?php
// SampleDataSeeder and EnhancedSampleDataSeeder both generate students with the
// same student_code values (STU-2026-000N), so the second one fails on the
// unique index. This drops the first batch (and anything hanging off it) before
// the enhanced seeder runs.
require '/home/user/TOEFL-House/tools/bootstrap.php';

$childTables = [
    'rosters', 'student_semesters', 'student_journey_events', 'exam_results',
    'payments', 'certificates', 'attendances', 'student_payments',
];

foreach ($childTables as $table) {
    if (Schema::hasTable($table)) {
        $deleted = DB::table($table)->delete();
        if ($deleted) echo "cleared $deleted rows from $table\n";
    }
}

$deleted = DB::table('students')->delete();
echo "cleared $deleted students\n";
