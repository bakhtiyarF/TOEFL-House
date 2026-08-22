<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

/**
 * Import Service
 *
 * Provides import functionality for various data types from Excel and CSV files.
 */
class ImportService
{
    /**
     * Import data from file.
     */
    public function importFromFile(string $path): Collection
    {
        try {
            $import = new class implements ToCollection, WithHeadingRow
            {
                use Importable;

                public Collection $data;

                public function __construct()
                {
                    $this->data = collect();
                }

                public function collection(Collection $rows)
                {
                    $this->data = $rows;
                }
            };

            Excel::import($import, $path);

            Log::info("Data imported from file", [
                'path' => $path,
                'rows' => $import->data->count(),
            ]);

            return $import->data;
        } catch (\Exception $e) {
            Log::error("Failed to import data from file", [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Import students from file.
     */
    public function importStudents(string $path, string $branchId): array
    {
        $data = $this->importFromFile($path);
        
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($data, $branchId, &$imported, &$failed, &$errors) {
            foreach ($data as $index => $row) {
                try {
                    $validator = Validator::make($row->toArray(), [
                        'student_code' => 'required|string|max:50|unique:students,student_code',
                        'full_name' => 'required|string|max:255',
                        'email' => 'nullable|email|max:255|unique:students,email',
                        'phone' => 'nullable|string|max:20',
                        'date_of_birth' => 'nullable|date',
                        'gender' => 'nullable|in:male,female,other',
                        'status' => 'nullable|in:active,inactive,graduated,suspended',
                        'enrollment_date' => 'nullable|date',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = [
                            'row' => $index + 2, // +2 for header and 0-indexing
                            'errors' => $validator->errors()->all(),
                        ];
                        continue;
                    }

                    \App\Modules\Academic\Models\Student::create([
                        'id' => Str::uuid(),
                        'student_code' => $row['student_code'],
                        'full_name' => $row['full_name'],
                        'email' => $row['email'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'date_of_birth' => $row['date_of_birth'] ?? null,
                        'gender' => $row['gender'] ?? null,
                        'status' => $row['status'] ?? 'active',
                        'enrollment_date' => $row['enrollment_date'] ?? now(),
                        'branch_id' => $branchId,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 2,
                        'errors' => [$e->getMessage()],
                    ];
                }
            }
        });

        Log::info("Students imported", [
            'imported' => $imported,
            'failed' => $failed,
        ]);

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Import teachers from file.
     */
    public function importTeachers(string $path, string $branchId): array
    {
        $data = $this->importFromFile($path);
        
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($data, $branchId, &$imported, &$failed, &$errors) {
            foreach ($data as $index => $row) {
                try {
                    $validator = Validator::make($row->toArray(), [
                        'full_name' => 'required|string|max:255',
                        'email' => 'required|email|max:255|unique:teachers,email',
                        'phone' => 'nullable|string|max:20',
                        'specialization' => 'nullable|string|max:255',
                        'qualification' => 'nullable|string|max:255',
                        'experience_years' => 'nullable|integer|min:0|max:50',
                        'status' => 'nullable|in:active,inactive,on_leave',
                        'hire_date' => 'nullable|date',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = [
                            'row' => $index + 2,
                            'errors' => $validator->errors()->all(),
                        ];
                        continue;
                    }

                    \App\Modules\PeopleHr\Models\Teacher::create([
                        'id' => Str::uuid(),
                        'full_name' => $row['full_name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'] ?? null,
                        'specialization' => $row['specialization'] ?? null,
                        'qualification' => $row['qualification'] ?? null,
                        'experience_years' => $row['experience_years'] ?? 0,
                        'status' => $row['status'] ?? 'active',
                        'hire_date' => $row['hire_date'] ?? now(),
                        'branch_id' => $branchId,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 2,
                        'errors' => [$e->getMessage()],
                    ];
                }
            }
        });

        Log::info("Teachers imported", [
            'imported' => $imported,
            'failed' => $failed,
        ]);

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Import donors from file.
     */
    public function importDonors(string $path, string $branchId): array
    {
        $data = $this->importFromFile($path);
        
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($data, $branchId, &$imported, &$failed, &$errors) {
            foreach ($data as $index => $row) {
                try {
                    $validator = Validator::make($row->toArray(), [
                        'full_name' => 'required|string|max:255',
                        'email' => 'nullable|email|max:255',
                        'phone' => 'nullable|string|max:20',
                        'donor_type' => 'nullable|in:individual,organization,foundation,corporation,government',
                        'organization_name' => 'nullable|string|max:255',
                    ]);

                    if ($validator->fails()) {
                        $failed++;
                        $errors[] = [
                            'row' => $index + 2,
                            'errors' => $validator->errors()->all(),
                        ];
                        continue;
                    }

                    \App\Modules\FundingImpact\Models\Donor::create([
                        'id' => Str::uuid(),
                        'full_name' => $row['full_name'],
                        'email' => $row['email'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'donor_type' => $row['donor_type'] ?? 'individual',
                        'organization_name' => $row['organization_name'] ?? null,
                        'branch_id' => $branchId,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 2,
                        'errors' => [$e->getMessage()],
                    ];
                }
            }
        });

        Log::info("Donors imported", [
            'imported' => $imported,
            'failed' => $failed,
        ]);

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Validate import file structure.
     */
    public function validateFileStructure(string $path, array $requiredColumns): array
    {
        try {
            $data = $this->importFromFile($path);
            
            if ($data->isEmpty()) {
                return [
                    'valid' => false,
                    'errors' => ['File is empty'],
                ];
            }

            $firstRow = $data->first();
            $availableColumns = array_keys($firstRow->toArray());
            $missingColumns = array_diff($requiredColumns, $availableColumns);

            if (!empty($missingColumns)) {
                return [
                    'valid' => false,
                    'errors' => ['Missing required columns: ' . implode(', ', $missingColumns)],
                ];
            }

            return [
                'valid' => true,
                'rows' => $data->count(),
                'columns' => $availableColumns,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Get import template for students.
     */
    public function getStudentTemplate(): string
    {
        $path = storage_path('app/templates/student_import_template.xlsx');
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $headings = [
            'student_code',
            'full_name',
            'email',
            'phone',
            'date_of_birth',
            'gender',
            'status',
            'enrollment_date',
        ];

        $exportService = app(ExportService::class);
        $exportService->exportToExcel(collect(), $headings, 'templates/student_import_template.xlsx');

        return $path;
    }

    /**
     * Get import template for teachers.
     */
    public function getTeacherTemplate(): string
    {
        $path = storage_path('app/templates/teacher_import_template.xlsx');
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $headings = [
            'full_name',
            'email',
            'phone',
            'specialization',
            'qualification',
            'experience_years',
            'status',
            'hire_date',
        ];

        $exportService = app(ExportService::class);
        $exportService->exportToExcel(collect(), $headings, 'templates/teacher_import_template.xlsx');

        return $path;
    }

    /**
     * Get import template for donors.
     */
    public function getDonorTemplate(): string
    {
        $path = storage_path('app/templates/donor_import_template.xlsx');
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $headings = [
            'full_name',
            'email',
            'phone',
            'donor_type',
            'organization_name',
        ];

        $exportService = app(ExportService::class);
        $exportService->exportToExcel(collect(), $headings, 'templates/donor_import_template.xlsx');

        return $path;
    }
}
