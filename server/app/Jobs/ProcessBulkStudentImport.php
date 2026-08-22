<?php

namespace App\Jobs;

use App\Modules\Academic\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Process Bulk Student Import Job
 *
 * Imports students from CSV/Excel file in the background.
 */
class ProcessBulkStudentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60];

    public function __construct(
        private string $filePath,
        private string $branchId,
        private string $requestedBy
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing bulk student import from {$this->filePath}");

            // Read CSV/Excel file
            $rows = $this->readFile($this->filePath);

            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    $this->importStudent($row, $index);
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row {$index}: " . $e->getMessage();
                    Log::warning("Failed to import student from row {$index}: " . $e->getMessage());
                }
            }

            Log::info("Bulk import completed: {$imported} imported, {$failed} failed");

            // Store import results
            // ImportResult::create([...]);

            // Optionally: Send notification to user who requested the import
            // Notification::send($this->requestedBy, new BulkImportCompletedNotification($imported, $failed, $errors));

            // Clean up uploaded file
            // Storage::delete($this->filePath);

        } catch (\Exception $e) {
            Log::error("Failed to process bulk import: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Read CSV/Excel file.
     */
    private function readFile(string $filePath): array
    {
        // Implementation depends on file format
        // For CSV: use fgetcsv
        // For Excel: use PhpSpreadsheet or Laravel Excel package

        $rows = [];
        $handle = fopen(storage_path('app/' . $filePath), 'r');

        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = [
                'full_name' => $data[0] ?? null,
                'phone' => $data[1] ?? null,
                'email' => $data[2] ?? null,
                'gender' => $data[3] ?? null,
                'father_name' => $data[4] ?? null,
                'address_region' => $data[5] ?? null,
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Import single student.
     */
    private function importStudent(array $data, int $rowIndex): void
    {
        // Validate data
        $validator = Validator::make($data, [
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'father_name' => 'nullable|string|max:255',
            'address_region' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        // Create student
        Student::create([
            'student_code' => Student::generateStudentCode(),
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'gender' => $data['gender'],
            'father_name' => $data['father_name'],
            'address_region' => $data['address_region'],
            'branch_id' => $this->branchId,
            'status' => 'active',
            'registration_date' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Bulk import failed permanently: " . $exception->getMessage());

        // Optionally: Send failure notification to user
        // Notification::send($this->requestedBy, new BulkImportFailedNotification($exception));
    }
}
