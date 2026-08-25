<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Models\Student;
use App\Modules\Academic\Models\StudentJourneyEvent;
use App\Modules\Academic\Services\EnrollmentService;
use App\Modules\Academic\Services\AssessmentService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService,
        private EnrollmentService $enrollmentService,
        private AssessmentService $assessmentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve(
            $request->user(),
            $request->query('branch_id', 'all')
        );

        $query = Student::with('branch')
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('full_name', 'like', "%{$search}%")
                       ->orWhere('student_code', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('tazkira_no', 'like', "%{$search}%");
                });
            })
            ->when($request->query('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->orderBy('created_at', 'desc');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        $students = $request->query('per_page')
            ? $query->paginate((int)$request->query('per_page', 20))
            : $query->get();

        return response()->json($students);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $student = Student::with(['branch', 'enrollments.programVersion', 'semesters.academicClass', 'certificates'])->findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($student);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'father_name' => 'nullable|string|max:255',
            'address_region' => 'nullable|string|max:255',
            'tazkira_no' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'school_or_university' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $validated['branch_id'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated['student_code'] = $this->generateStudentCode();
        $validated['registration_date'] = now()->toDateString();
        $validated['status'] = 'active';

        $student = Student::create($validated);

        StudentJourneyEvent::create([
            'id' => Str::uuid()->toString(),
            'student_id' => $student->id,
            'event_type' => 'student_registered',
            'occurred_at' => now(),
            'actor_user_id' => $request->user()->id,
            'actor_name' => $request->user()->full_name,
            'payload' => ['full_name' => $student->full_name],
        ]);

        return response()->json($student->load('branch'), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'full_name' => 'string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'father_name' => 'nullable|string|max:255',
            'tazkira_no' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'in:active,inactive,graduated,suspended',
        ]);

        $student->update($validated);
        return response()->json($student);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $student->delete();
        return response()->json(null, 204);
    }

    public function journey(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $events = StudentJourneyEvent::where('student_id', $id)
            ->orderByDesc('occurred_at')
            ->get();

        return response()->json($events);
    }

    private function generateStudentCode(): string
    {
        $year = now()->format('Y');
        $lastStudent = Student::where('student_code', 'like', "STU-{$year}-%")
            ->orderBy('student_code', 'desc')
            ->first();

        $next = $lastStudent
            ? (int)substr($lastStudent->student_code, -4) + 1
            : 1;

        return sprintf('STU-%s-%04d', $year, $next);
    }

    /**
     * Enroll via service (new endpoint)
     */
    public function enroll(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'program_id' => 'required|uuid',
            'class_id' => 'nullable|uuid',
            'program_version_id' => 'nullable|uuid',
            'enrollment_type' => 'in:new,repeat,partial_repeat,resume,jump',
            'semester_name' => 'nullable|string',
            'skills_focus' => 'nullable|array',
        ]);

        try {
            $enrollment = $this->enrollmentService->enrollStudent(
                $id,
                $validated,
                $request->user()->id
            );
            return response()->json($enrollment, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Student homework list (live assessments)
     */
    public function homework(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $homework = DB::table('homework as h')
            ->join('sessions as s', 'h.session_id', '=', 's.id')
            ->join('student_semesters as ss', 's.class_id', '=', 'ss.class_id')
            ->where('ss.student_id', $id)
            ->where('ss.status', 'active')
            ->select('h.*', 's.date as session_date', 's.topic')
            ->orderByDesc('h.due_date')
            ->get();

        return response()->json($homework);
    }

    /**
     * Student exam results (live)
     */
    public function exams(Request $request, string $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        if (!$this->branchScopeService->canAccessBranch($request->user(), $student->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $results = DB::table('exam_results as er')
            ->join('exams as e', 'er.exam_id', '=', 'e.id')
            ->where('er.student_id', $id)
            ->select('er.*', 'e.title', 'e.date', 'e.type', 'e.fee')
            ->orderByDesc('e.date')
            ->get();

        return response()->json($results);
    }
}
