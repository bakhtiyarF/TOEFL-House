<?php

namespace App\Http\Controllers;

use App\Services\ReportGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportGenerationService $reportService
    ) {}

    /**
     * Generate financial report
     */
    public function financialReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|uuid|exists:branches,id',
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $report = $this->reportService->generateFinancialReport(
                $validated['branch_id'],
                $validated['year'],
                str_pad($validated['month'], 2, '0', STR_PAD_LEFT)
            );

            return response()->json([
                'success' => true,
                'message' => 'Financial report generated successfully',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate student transcript
     */
    public function studentTranscript(string $studentId): JsonResponse
    {
        try {
            $report = $this->reportService->generateStudentTranscript($studentId);

            return response()->json([
                'success' => true,
                'message' => 'Student transcript generated successfully',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate transcript: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate class roster
     */
    public function classRoster(string $classId): JsonResponse
    {
        try {
            $report = $this->reportService->generateClassRoster($classId);

            return response()->json([
                'success' => true,
                'message' => 'Class roster generated successfully',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate roster: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate certificate (supports template for designer flow)
     */
    public function certificate(Request $request, string $certificateId): JsonResponse
    {
        try {
            $template = $request->query('template', 'classic');
            $report = $this->reportService->generateCertificate($certificateId, $template);

            return response()->json([
                'success' => true,
                'message' => 'Certificate generated successfully',
                'data' => $report,
                'template' => $template,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate certificate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate payroll report
     */
    public function payrollReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|uuid|exists:branches,id',
            'period_key' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        try {
            $report = $this->reportService->generatePayrollReport(
                $validated['branch_id'],
                $validated['period_key']
            );

            return response()->json([
                'success' => true,
                'message' => 'Payroll report generated successfully',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payroll report: ' . $e->getMessage(),
            ], 500);
        }
    }
}
