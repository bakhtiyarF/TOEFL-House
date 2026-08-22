<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Export Service
 *
 * Provides export functionality for various data types in Excel and CSV formats.
 */
class ExportService
{
    /**
     * Export data to Excel.
     */
    public function exportToExcel(Collection $data, array $headings, string $filename, ?callable $mapFunction = null): string
    {
        try {
            $export = new class($data, $headings, $mapFunction) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
            {
                use Exportable;

                protected Collection $data;
                protected array $headings;
                protected ?callable $mapFunction;

                public function __construct(Collection $data, array $headings, ?callable $mapFunction = null)
                {
                    $this->data = $data;
                    $this->headings = $headings;
                    $this->mapFunction = $mapFunction;
                }

                public function collection()
                {
                    return $this->data;
                }

                public function headings(): array
                {
                    return $this->headings;
                }

                public function map($row): array
                {
                    if ($this->mapFunction) {
                        return ($this->mapFunction)($row);
                    }

                    return $row instanceof \Illuminate\Database\Eloquent\Model
                        ? $row->toArray()
                        : (array)$row;
                }
            };

            $path = storage_path('app/exports/' . $filename);
            
            // Ensure directory exists
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            Excel::store($export, 'exports/' . $filename, 'local');

            Log::info("Data exported to Excel", [
                'filename' => $filename,
                'rows' => $data->count(),
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error("Failed to export data to Excel", [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Export data to CSV.
     */
    public function exportToCsv(Collection $data, array $headings, string $filename, ?callable $mapFunction = null): string
    {
        try {
            $path = storage_path('app/exports/' . $filename);
            
            // Ensure directory exists
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $file = fopen($path, 'w');

            // Write BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write headings
            fputcsv($file, $headings);

            // Write data
            foreach ($data as $row) {
                $mappedRow = $mapFunction
                    ? ($mapFunction)($row)
                    : ($row instanceof \Illuminate\Database\Eloquent\Model ? $row->toArray() : (array)$row);
                
                fputcsv($file, $mappedRow);
            }

            fclose($file);

            Log::info("Data exported to CSV", [
                'filename' => $filename,
                'rows' => $data->count(),
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error("Failed to export data to CSV", [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Export students to Excel.
     */
    public function exportStudents(Collection $students, string $format = 'xlsx'): string
    {
        $headings = [
            'ID',
            'Student Code',
            'Full Name',
            'Email',
            'Phone',
            'Date of Birth',
            'Gender',
            'Status',
            'Enrollment Date',
            'Branch',
        ];

        $mapFunction = function ($student) {
            return [
                $student->id,
                $student->student_code,
                $student->full_name,
                $student->email ?? '',
                $student->phone ?? '',
                $student->date_of_birth?->format('Y-m-d') ?? '',
                ucfirst($student->gender ?? ''),
                ucfirst($student->status ?? ''),
                $student->enrollment_date?->format('Y-m-d') ?? '',
                $student->branch->name ?? '',
            ];
        };

        $filename = 'students_' . now()->format('Y-m-d_His') . '.' . $format;

        return $format === 'csv'
            ? $this->exportToCsv($students, $headings, $filename, $mapFunction)
            : $this->exportToExcel($students, $headings, $filename, $mapFunction);
    }

    /**
     * Export teachers to Excel.
     */
    public function exportTeachers(Collection $teachers, string $format = 'xlsx'): string
    {
        $headings = [
            'ID',
            'Full Name',
            'Email',
            'Phone',
            'Specialization',
            'Qualification',
            'Experience Years',
            'Status',
            'Hire Date',
            'Branch',
        ];

        $mapFunction = function ($teacher) {
            return [
                $teacher->id,
                $teacher->full_name,
                $teacher->email ?? '',
                $teacher->phone ?? '',
                $teacher->specialization ?? '',
                $teacher->qualification ?? '',
                $teacher->experience_years ?? 0,
                ucfirst($teacher->status ?? ''),
                $teacher->hire_date?->format('Y-m-d') ?? '',
                $teacher->branch->name ?? '',
            ];
        };

        $filename = 'teachers_' . now()->format('Y-m-d_His') . '.' . $format;

        return $format === 'csv'
            ? $this->exportToCsv($teachers, $headings, $filename, $mapFunction)
            : $this->exportToExcel($teachers, $headings, $filename, $mapFunction);
    }

    /**
     * Export payments to Excel.
     */
    public function exportPayments(Collection $payments, string $format = 'xlsx'): string
    {
        $headings = [
            'ID',
            'Invoice Number',
            'Student Name',
            'Amount',
            'Payment Method',
            'Status',
            'Payment Date',
            'Branch',
        ];

        $mapFunction = function ($payment) {
            return [
                $payment->id,
                $payment->invoice_number ?? '',
                $payment->student->full_name ?? '',
                number_format($payment->amount, 2),
                ucfirst($payment->payment_method ?? ''),
                ucfirst($payment->status ?? ''),
                $payment->payment_date?->format('Y-m-d') ?? '',
                $payment->branch->name ?? '',
            ];
        };

        $filename = 'payments_' . now()->format('Y-m-d_His') . '.' . $format;

        return $format === 'csv'
            ? $this->exportToCsv($payments, $headings, $filename, $mapFunction)
            : $this->exportToExcel($payments, $headings, $filename, $mapFunction);
    }

    /**
     * Export attendance to Excel.
     */
    public function exportAttendance(Collection $attendance, string $format = 'xlsx'): string
    {
        $headings = [
            'Date',
            'Class Name',
            'Student Name',
            'Status',
            'Marked At',
        ];

        $mapFunction = function ($record) {
            return [
                $record->session->session_date?->format('Y-m-d') ?? '',
                $record->session->class->name ?? '',
                $record->student->full_name ?? '',
                ucfirst($record->attendance_status ?? ''),
                $record->marked_at?->format('Y-m-d H:i:s') ?? '',
            ];
        };

        $filename = 'attendance_' . now()->format('Y-m-d_His') . '.' . $format;

        return $format === 'csv'
            ? $this->exportToCsv($attendance, $headings, $filename, $mapFunction)
            : $this->exportToExcel($attendance, $headings, $filename, $mapFunction);
    }

    /**
     * Export grades to Excel.
     */
    public function exportGrades(Collection $grades, string $format = 'xlsx'): string
    {
        $headings = [
            'Student Name',
            'Exam Title',
            'Score',
            'Max Score',
            'Percentage',
            'Grade',
            'Graded At',
        ];

        $mapFunction = function ($grade) {
            return [
                $grade->student->full_name ?? '',
                $grade->exam->title ?? '',
                number_format($grade->score, 2),
                number_format($grade->max_score, 2),
                number_format($grade->percentage, 2) . '%',
                $grade->grade ?? '',
                $grade->graded_at?->format('Y-m-d H:i:s') ?? '',
            ];
        };

        $filename = 'grades_' . now()->format('Y-m-d_His') . '.' . $format;

        return $format === 'csv'
            ? $this->exportToCsv($grades, $headings, $filename, $mapFunction)
            : $this->exportToExcel($grades, $headings, $filename, $mapFunction);
    }

    /**
     * Export donations to Excel.
     */
    public function exportDonations(Collection $donations, string $format = 'xlsx'): string
    {
        $headings = [
            'ID',
            'Donor Name',
            'Amount',
            'Donation Date',
            'Campaign',
            'Branch',
        ];

        $mapFunction = function ($donation) {
            return [
                $donation->id,
                $donation->donor->full_name ?? '',
                number_format($donation->amount, 2),
                $donation->donation_date?->format('Y-m-d') ?? '',
                $donation->campaign->name ?? '',
                $donation->branch->name ?? '',
            ];
        };

        $filename = 'donations_' . now()->format('Y-m-d_His') . '.' . $format;

        return $format === 'csv'
            ? $this->exportToCsv($donations, $headings, $filename, $mapFunction)
            : $this->exportToExcel($donations, $headings, $filename, $mapFunction);
    }
}
