<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request, string $classId): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('exams')
            ->where('class_id', $classId)
            ->orderByDesc('date');

        $class = DB::table('classes')->where('id', $classId)->first();
        if ($class && !$scope['isAll'] && !$this->branchScopeService->canAccessBranch($request->user(), $class->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $exams = $query->get()->map(function ($exam) {
            $resultsCount = DB::table('exam_results')->where('exam_id', $exam->id)->count();
            return [
                ...((array)$exam),
                'results_count' => $resultsCount,
            ];
        });

        return response()->json($exams);
    }

    public function store(Request $request, string $classId): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'fee' => 'nullable|numeric|min:0',
            'type' => 'required|in:placement,midterm,final,mock,certification',
        ]);

        $class = DB::table('classes')->where('id', $classId)->first();
        if (!$class || !$this->branchScopeService->canAccessBranch($request->user(), $class->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $id = Str::uuid()->toString();
        DB::table('exams')->insert([
            'id' => $id,
            'title' => $validated['title'],
            'date' => $validated['date'],
            'fee' => $validated['fee'] ?? 0,
            'class_id' => $classId,
            'type' => $validated['type'],
            'branch_id' => $class->branch_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('exams')->where('id', $id)->first(), 201);
    }

    public function results(Request $request, string $examId): JsonResponse
    {
        $exam = DB::table('exams')->where('id', $examId)->first();
        if (!$exam) return response()->json(['message' => 'Not found'], 404);

        $class = DB::table('classes')->where('id', $exam->class_id)->first();
        if ($class && !$this->branchScopeService->canAccessBranch($request->user(), $class->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $results = DB::table('exam_results as er')
            ->join('students as s', 'er.student_id', '=', 's.id')
            ->where('er.exam_id', $examId)
            ->select('er.*', 's.full_name', 's.student_code')
            ->get();

        return response()->json(['exam' => $exam, 'results' => $results]);
    }

    public function storeResult(Request $request, string $examId): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'score' => 'required|numeric|min:0|max:100',
            'status' => 'nullable|string',
            'exam_fee_paid' => 'boolean',
        ]);

        $exam = DB::table('exams')->where('id', $examId)->first();
        if (!$exam) return response()->json(['message' => 'Exam not found'], 404);

        $class = DB::table('classes')->where('id', $exam->class_id)->first();
        if ($class && !$this->branchScopeService->canAccessBranch($request->user(), $class->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $resultId = Str::uuid()->toString();
        DB::table('exam_results')->insert([
            'id' => $resultId,
            'exam_id' => $examId,
            'student_id' => $validated['student_id'],
            'score' => $validated['score'],
            'status' => $validated['status'] ?? 'graded',
            'exam_fee_paid' => $validated['exam_fee_paid'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('exam_results')->where('id', $resultId)->first(), 201);
    }
}
