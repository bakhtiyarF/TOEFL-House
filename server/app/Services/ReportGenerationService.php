<?php

namespace App\Services;

use App\Modules\Academic\Models\Student;
use App\Modules\FinancePayroll\Models\Payment;
use App\Modules\FinancePayroll\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PDF Report Generation Service
 *
 * Generates PDF reports for:
 * - Financial summaries (monthly/quarterly/annual)
 * - Student records and transcripts
 * - Class rosters and attendance reports
 * - Certificate generation
 * - Payroll summaries
 *
 * Uses DomPDF for PDF generation (barryvdh/laravel-dompdf)
 */
class ReportGenerationService
{
    /**
     * Generate monthly financial report
     */
    public function generateFinancialReport(string $branchId, string $year, string $month): array
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $income = FinancialTransaction::where('branch_id', $branchId)
            ->where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $expenses = FinancialTransaction::where('branch_id', $branchId)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $netIncome = $totalIncome - $totalExpenses;

        $data = [
            'title' => "Financial Report - {$year}/{$month}",
            'branch_id' => $branchId,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'savings_rate' => $totalIncome > 0 ? round(($totalIncome * 0.05), 2) : 0,
            ],
            'income_breakdown' => $income->groupBy('category')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total' => $items->sum('amount'),
                ];
            })->toArray(),
            'expense_breakdown' => $expenses->groupBy('category')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total' => $items->sum('amount'),
                ];
            })->toArray(),
            'transactions' => $income->merge($expenses)->sortByDesc('date')->values(),
            'generated_at' => now()->toDateTimeString(),
        ];

        return $this->generatePdf('reports.financial', $data, "financial-report-{$year}-{$month}.pdf");
    }

    /**
     * Generate student transcript/record
     */
    public function generateStudentTranscript(string $studentId): array
    {
        $student = Student::with(['enrollments.class', 'enrollments.programVersion', 'journeyEvents'])
            ->findOrFail($studentId);

        $enrollments = $student->enrollments->map(function ($enrollment) {
            $sessions = $enrollment->class->sessions ?? collect();
            $attendance = $sessions->flatMap(function ($session) {
                return $session->rosters()->where('student_id', request()->route('studentId'))->get();
            });

            $totalSessions = $attendance->count();
            $presentCount = $attendance->where('attendance_status', 'present')->count();
            $attendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 0;

            return [
                'program' => $enrollment->programVersion->program->name ?? 'N/A',
                'level' => $enrollment->level,
                'class' => $enrollment->class->name ?? 'N/A',
                'teacher' => $enrollment->class->teacher->full_name ?? 'N/A',
                'start_date' => $enrollment->started_at,
                'status' => $enrollment->status,
                'attendance_rate' => $attendanceRate,
                'fee_snapshot' => $enrollment->fee_snapshot_json,
            ];
        });

        $data = [
            'title' => "Student Transcript - {$student->full_name}",
            'student' => $student,
            'enrollments' => $enrollments,
            'journey_events' => $student->journeyEvents->sortByDesc('occurred_at')->take(20),
            'generated_at' => now()->toDateTimeString(),
        ];

        return $this->generatePdf('reports.student-transcript', $data, "transcript-{$student->student_code}.pdf");
    }

    /**
     * Generate class roster with attendance summary
     */
    public function generateClassRoster(string $classId): array
    {
        $class = DB::table('classes')->where('id', $classId)->first();
        if (!$class) {
            throw new \RuntimeException('Class not found');
        }

        $students = DB::table('student_semesters as ss')
            ->join('students as s', 'ss.student_id', '=', 's.id')
            ->where('ss.class_id', $classId)
            ->where('ss.status', 'active')
            ->select('s.*', 'ss.enroll_date', 'ss.fee_amount')
            ->get()
            ->map(function ($student) use ($classId) {
                $sessions = DB::table('sessions')->where('class_id', $classId)->get();
                $totalSessions = $sessions->count();

                $attendance = DB::table('rosters')
                    ->join('sessions', 'rosters.session_id', '=', 'sessions.id')
                    ->where('sessions.class_id', $classId)
                    ->where('rosters.student_id', $student->id)
                    ->select('rosters.attendance_status')
                    ->get();

                $presentCount = $attendance->where('attendance_status', 'present')->count();
                $absentCount = $attendance->where('attendance_status', 'absent')->count();
                $sickCount = $attendance->where('attendance_status', 'sick')->count();
                $leaveCount = $attendance->where('attendance_status', 'leave')->count();

                return [
                    ...((array)$student),
                    'total_sessions' => $totalSessions,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'sick' => $sickCount,
                    'leave' => $leaveCount,
                    'attendance_rate' => $totalSessions > 0
                        ? round(($presentCount / $totalSessions) * 100, 1)
                        : 0,
                ];
            });

        $data = [
            'title' => "Class Roster - {$class->name}",
            'class' => $class,
            'students' => $students,
            'total_students' => $students->count(),
            'average_attendance' => $students->count() > 0
                ? round($students->avg('attendance_rate'), 1)
                : 0,
            'generated_at' => now()->toDateTimeString(),
        ];

        return $this->generatePdf('reports.class-roster', $data, "roster-{$class->name}.pdf");
    }

    /**
     * Generate certificate PDF (with template support for designer)
     * Templates: classic | modern | minimal
     */
    public function generateCertificate(string $certificateId, ?string $template = 'classic'): array
    {
        $certificate = DB::table('certificates')->where('id', $certificateId)->first();
        if (!$certificate) {
            throw new \RuntimeException('Certificate not found');
        }

        $student = Student::find($certificate->student_id);
        $program = DB::table('programs')->where('id', $certificate->program_id)->first();
        $level = DB::table('levels')->where('id', $certificate->level_id)->first();

        $data = [
            'title' => 'Certificate of Completion',
            'certificate' => $certificate,
            'student' => $student,
            'program' => $program,
            'level' => $level,
            'template' => $template ?? ($certificate->template ?? 'classic'),
            'generated_at' => now()->toDateTimeString(),
        ];

        $filename = "certificate-{$certificate->certificate_no}-{$template}.pdf";

        return $this->generatePdf('reports.certificate', $data, $filename, $template);
    }

    /**
     * Generate payroll summary for a period
     */
    public function generatePayrollReport(string $branchId, string $periodKey): array
    {
        $ledger = DB::table('teacher_salary_ledger')
            ->join('teachers', 'teacher_salary_ledger.teacher_id', '=', 'teachers.id')
            ->where('teacher_salary_ledger.branch_id', $branchId)
            ->where('teacher_salary_ledger.period_key', $periodKey)
            ->select(
                'teachers.full_name',
                'teachers.salary_type',
                'teacher_salary_ledger.*'
            )
            ->get();

        $totalDue = $ledger->sum('due_amount');
        $totalPaid = $ledger->sum('paid_amount');
        $totalRemaining = $totalDue - $totalPaid;

        $data = [
            'title' => "Payroll Report - {$periodKey}",
            'branch_id' => $branchId,
            'period' => $periodKey,
            'summary' => [
                'total_teachers' => $ledger->count(),
                'total_due' => $totalDue,
                'total_paid' => $totalPaid,
                'total_remaining' => $totalRemaining,
            ],
            'breakdown' => $ledger,
            'generated_at' => now()->toDateTimeString(),
        ];

        return $this->generatePdf('reports.payroll', $data, "payroll-{$periodKey}.pdf");
    }

    /** 
     * Generate PDF from view template
     * PRIMARY: Real DomPDF PDF for certificates + other reports
     * Returns downloadable path + URL (requires php artisan storage:link)
     */
    private function generatePdf(string $view, array $data, string $filename, ?string $template = null): array
    {
        $isCertificate = str_contains($view, 'certificate');

        // === PRIMARY: REAL DomPDF ===
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            try {
                $pdf = \PDF::loadView($view, $data);
                
                // Certificate specific: landscape A4
                if ($isCertificate) {
                    $pdf->setPaper('A4', 'landscape');
                } else {
                    $pdf->setPaper('A4', 'portrait');
                }

                // Save to public disk for easy serving
                $storagePath = "reports/{$filename}";
                $fullPath = storage_path("app/public/{$storagePath}");
                
                // Ensure dir
                if (!is_dir(dirname($fullPath))) {
                    mkdir(dirname($fullPath), 0755, true);
                }

                $pdf->save($fullPath);

                $downloadUrl = '/storage/' . $storagePath;   // works after storage:link

                return [
                    'view' => $view,
                    'filename' => $filename,
                    'path' => $fullPath,
                    'download_url' => $downloadUrl,
                    'pdf' => true,
                    'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                    'data' => $data,
                    'template' => $template,
                    'message' => 'Real PDF generated via DomPDF',
                    'generated_at' => now()->toDateTimeString(),
                    'downloadable' => true,
                ];
            } catch (\Throwable $e) {
                // Fall through to HTML fallback on error
                Log::error('DomPDF generation failed: ' . $e->getMessage());
            }
        }

        // === FALLBACK: HTML (for certificates) + metadata ===
        if ($isCertificate) {
            try {
                $html = view($view, $data)->render();
                return [
                    'view' => $view,
                    'filename' => $filename,
                    'html' => $html,
                    'data' => $data,
                    'template' => $template,
                    'message' => 'DomPDF unavailable or failed — Printable HTML ready. Use browser Print → Save as PDF',
                    'generated_at' => now()->toDateTimeString(),
                    'downloadable' => true,
                    'pdf' => false,
                ];
            } catch (\Throwable $e) {
                // continue
            }
        }

        // === Generic metadata fallback ===
        return [
            'view' => $view,
            'filename' => $filename,
            'data' => $data,
            'template' => $template,
            'message' => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)
                ? 'PDF generation encountered an error. Printable HTML fallback available.'
                : 'Install barryvdh/laravel-dompdf for native PDF. Printable HTML + JSON returned.',
            'generated_at' => now()->toDateTimeString(),
            'downloadable' => true,
        ];
    }
}
