<?php

use App\Modules\FundingImpact\Services\ScholarshipService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class FundingController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService,
        private ScholarshipService $scholarshipService,
    ) {}

    public function indexDonors(): JsonResponse
    {
        return response()->json(DB::table('donors')->orderBy('full_name')->get());
    }

    public function storeDonor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'type' => 'required|in:individual,organization,ngo,government',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $id = Str::uuid()->toString();
        DB::table('donors')->insert(['id' => $id, ...$validated, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(DB::table('donors')->where('id', $id)->first(), 201);
    }

    public function indexCampaigns(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));
        $query = DB::table('funding_campaigns')->orderByDesc('created_at');
        if (!$scope['isAll']) $query->where('branch_id', $scope['branchId']);
        return response()->json($query->get());
    }

    public function storeDonation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donor_id' => 'required|uuid|exists:donors,id',
            'campaign_id' => 'nullable|uuid',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'restricted' => 'boolean',
            'restriction_note' => 'nullable|string',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();

        DB::transaction(function () use ($id, $validated, $request) {
            DB::table('donations')->insert([
                'id' => $id,
                ...$validated,
                'restricted' => $validated['restricted'] ?? false,
                'receipt_no' => 'DON-' . strtoupper(substr(md5($id), 0, 6)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Shared income-recording + 5% savings sweep
            DB::table('financial_transactions')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'income',
                'category' => 'donation',
                'amount' => $validated['amount'],
                'date' => $validated['date'],
                'description' => "Donation received",
                'reference_id' => $id,
                'operator_name' => $request->user()->full_name,
                'branch_id' => $validated['branch_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!empty($validated['campaign_id'])) {
                DB::table('funding_campaigns')
                    ->where('id', $validated['campaign_id'])
                    ->increment('raised_amount', $validated['amount']);
            }
        });

        return response()->json(DB::table('donations')->where('id', $id)->first(), 201);
    }

    public function awardScholarship(Request $request, string $scholarshipId): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'amount' => 'required|numeric|min:0.01',
            'semester' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->scholarshipService->awardScholarship(
                scholarshipId: $scholarshipId,
                studentId: $validated['student_id'],
                amount: $validated['amount'],
                semester: $validated['semester'] ?? null,
                notes: $validated['notes'] ?? null,
                branchId: $request->user()->branch_id,
            );

            return response()->json($result, 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('donors')->group(function () {
        Route::get('/', [FundingController::class, 'indexDonors']);
        Route::post('/', [FundingController::class, 'storeDonor']);
    });

    Route::get('/funding-campaigns', [FundingController::class, 'indexCampaigns']);
    Route::post('/donations', [FundingController::class, 'storeDonation']);
    Route::post('/scholarships/{scholarshipId}/awards', [FundingController::class, 'awardScholarship']);
});
