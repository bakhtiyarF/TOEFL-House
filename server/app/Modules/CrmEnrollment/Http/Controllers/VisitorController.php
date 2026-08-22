<?php

namespace App\Modules\CrmEnrollment\Http\Controllers;

use App\Modules\CrmEnrollment\Services\ConversionService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VisitorController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService,
        private ConversionService $conversionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('visitors')
            ->when($request->query('search'), fn($q, $s) => $q->where('full_name', 'like', "%{$s}%"))
            ->when($request->query('stage'), fn($q, $s) => $q->where('stage', $s))
            ->when($request->query('source'), fn($q, $s) => $q->where('source', $s))
            ->orderByDesc('created_at');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $visitor = DB::table('visitors')->where('id', $id)->first();
        if (!$visitor) return response()->json(['message' => 'Not found'], 404);

        if (!$this->branchScopeService->canAccessBranch($request->user(), $visitor->branch_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $followups = DB::table('visitor_followups')
            ->where('visitor_id', $id)
            ->orderByDesc('date')
            ->get();

        return response()->json([
            'visitor' => $visitor,
            'followups' => $followups,
            'conversion_readiness' => $this->conversionService->getConversionReadiness($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female',
            'source' => 'nullable|string|max:50',
            'campaign_id' => 'nullable|uuid',
            'stage' => 'nullable|string',
            'visit_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'interested_course' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'address_region' => 'nullable|string|max:255',
            'tazkira_no' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();
        DB::table('visitors')->insert([
            'id' => $id,
            ...$validated,
            'stage' => $validated['stage'] ?? 'lead',
            'status' => 'visited',
            'serial_no' => 'V-' . strtoupper(substr(md5($id), 0, 6)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('visitors')->where('id', $id)->first(), 201);
    }

    public function addFollowup(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'outcome' => 'required|in:interested,not_interested,callback,registered',
        ]);

        $followupId = Str::uuid()->toString();
        DB::table('visitor_followups')->insert([
            'id' => $followupId,
            'visitor_id' => $id,
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
            'operator' => $request->user()->full_name,
            'outcome' => $validated['outcome'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(DB::table('visitor_followups')->where('id', $followupId)->first(), 201);
    }

    public function conversionReadiness(string $id): JsonResponse
    {
        return response()->json($this->conversionService->getConversionReadiness($id));
    }

    public function convert(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->conversionService->convert(
                visitorId: $id,
                enrollmentData: $request->only(['program_id', 'program_name', 'program_version_id']),
                actorUserId: $request->user()->id,
                actorName: $request->user()->full_name,
            );

            return response()->json($result, 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
