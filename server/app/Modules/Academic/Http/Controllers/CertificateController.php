<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Models\Certificate;
use App\Modules\Academic\Models\Student;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    /**
     * List certificates (scoped)
     */
    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve(
            $request->user(),
            $request->query('branch_id', 'all')
        );

        $query = Certificate::with(['student:id,full_name,student_code'])
            ->when($request->query('student_id'), fn($q, $sid) => $q->where('student_id', $sid))
            ->orderByDesc('issue_date');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        $certs = $query->paginate($request->query('per_page', 20));

        return response()->json($certs);
    }

    /**
     * Issue a certificate (live)
     */
    public function issue(Request $request, string $studentId): JsonResponse
    {
        $student = Student::findOrFail($studentId);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'level_id' => 'nullable|uuid',
            'program_id' => 'nullable|uuid',
            'certificate_no' => 'nullable|string|max:50',
            'grade' => 'nullable|string|max:20',
            'issue_date' => 'nullable|date',
            'template' => 'nullable|string|in:classic,modern,minimal',
        ]);

        $certNo = $validated['certificate_no'] ?? ('CERT-' . now()->format('Y') . '-' . Str::upper(Str::random(6)));

        $certificate = Certificate::create([
            'id' => Str::uuid()->toString(),
            'student_id' => $student->id,
            'program_id' => $validated['program_id'] ?? null,
            'level_id' => $validated['level_id'] ?? null,
            'certificate_no' => $certNo,
            'grade' => $validated['grade'] ?? 'Pass',
            'issue_date' => $validated['issue_date'] ?? now()->toDateString(),
            'branch_id' => $student->branch_id,
            'template' => $validated['template'] ?? 'classic',
        ]);

        // Optional journey event
        $student->addJourneyEvent('CERTIFICATE_ISSUED', [
            'certificate_no' => $certNo,
            'grade' => $certificate->grade,
            'template' => $certificate->template,
        ], $request->user()->id, $request->user()->full_name);

        return response()->json($certificate->load('student'), 201);
    }

    public function show(string $id): JsonResponse
    {
        $cert = Certificate::with('student')->findOrFail($id);
        return response()->json($cert);
    }

    /**
     * Revoke (soft delete + mark)
     */
    public function revoke(Request $request, string $id): JsonResponse
    {
        $cert = Certificate::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $cert->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cert->update(['status' => 'revoked']);
        // In real would dispatch CertificateRevoked event

        return response()->json(['message' => 'Certificate revoked']);
    }
}
