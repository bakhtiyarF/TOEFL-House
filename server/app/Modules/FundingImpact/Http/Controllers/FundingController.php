<?php

namespace App\Modules\FundingImpact\Http\Controllers;

use App\Modules\FundingImpact\Services\ScholarshipService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FundingController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService,
        private ScholarshipService $scholarshipService,
    ) {}

    // ── Donors ──

    public function indexDonors(): JsonResponse
    {
        $donors = DB::table('donors')
            ->select('donors.*')
            ->selectSub(
                DB::table('donations')->selectRaw('COALESCE(SUM(amount), 0)')->whereColumn('donor_id', 'donors.id'),
                'total_donated'
            )
            ->selectSub(
                DB::table('donations')->selectRaw('COUNT(*)')->whereColumn('donor_id', 'donors.id'),
                'donations_count'
            )
            ->orderBy('full_name')
            ->get();

        return response()->json($donors);
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

    // ── Campaigns ──

    public function indexCampaigns(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));
        $query = DB::table('funding_campaigns')->orderByDesc('created_at');
        if (!$scope['isAll']) $query->where('branch_id', $scope['branchId']);

        return response()->json($query->get()->map(function ($c) {
            return [
                ...((array)$c),
                'progress_percent' => $c->target_amount > 0
                    ? min(100, round(($c->raised_amount / $c->target_amount) * 100, 1))
                    : 0,
            ];
        }));
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'donor_id' => 'nullable|uuid|exists:donors,id',
            'target_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();
        DB::table('funding_campaigns')->insert([
            'id' => $id, ...$validated, 'raised_amount' => 0, 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(DB::table('funding_campaigns')->where('id', $id)->first(), 201);
    }

    // ── Donations ──

    public function indexDonations(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('donations')
            ->leftJoin('donors', 'donations.donor_id', '=', 'donors.id')
            ->leftJoin('funding_campaigns', 'donations.campaign_id', '=', 'funding_campaigns.id')
            ->select(
                'donations.*',
                'donors.full_name as donor_name',
                'funding_campaigns.name as campaign_name'
            )
            ->orderByDesc('donations.date');

        if (!$scope['isAll']) {
            $query->where('donations.branch_id', $scope['branchId']);
        }

        return response()->json($query->get());
    }

    public function storeDonation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donor_id' => 'required|uuid|exists:donors,id',
            'campaign_id' => 'nullable|uuid|exists:funding_campaigns,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'restricted' => 'boolean',
            'restriction_note' => 'nullable|string',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();

        DB::transaction(function () use ($id, $validated, $request) {
            DB::table('donations')->insert([
                'id' => $id, ...$validated,
                'restricted' => $validated['restricted'] ?? false,
                'receipt_no' => 'DON-' . strtoupper(substr(md5($id), 0, 6)),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Shared income-recording + 5% savings sweep
            DB::table('financial_transactions')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'income', 'category' => 'donation',
                'amount' => $validated['amount'], 'date' => $validated['date'],
                'description' => 'Donation received',
                'reference_id' => $id, 'operator_name' => $request->user()->full_name,
                'branch_id' => $validated['branch_id'],
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Apply savings sweep
            $savingPercent = (float)(DB::table('system_settings')->where('key', 'daily_saving_percent')->value('value') ?? 5);
            $savingAmount = $validated['amount'] * ($savingPercent / 100);
            if ($savingAmount > 0) {
                DB::table('system_settings')->where('key', 'saving_balance')->increment('value', $savingAmount);
            }

            if (!empty($validated['campaign_id'])) {
                DB::table('funding_campaigns')->where('id', $validated['campaign_id'])->increment('raised_amount', $validated['amount']);
            }
        });

        return response()->json(DB::table('donations')->where('id', $id)->first(), 201);
    }

    // ── Scholarships ──

    public function indexScholarships(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));
        $query = DB::table('scholarships')->orderBy('name');
        if (!$scope['isAll']) $query->where('branch_id', $scope['branchId']);

        return response()->json($query->get()->map(function ($s) {
            return [
                ...((array)$s),
                'remaining_budget' => $s->total_budget - $s->allocated_amount,
                'utilization_percent' => $s->total_budget > 0 ? round(($s->allocated_amount / $s->total_budget) * 100, 1) : 0,
            ];
        }));
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

    // ── Impact Metrics ──

    public function indexImpactMetrics(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));
        $query = DB::table('impact_metrics')->orderBy('category')->orderBy('name');
        if (!$scope['isAll']) $query->where('branch_id', $scope['branchId']);

        return response()->json($query->get()->map(function ($m) {
            return [
                ...((array)$m),
                'progress_percent' => $m->target_value > 0 ? round(($m->current_value / $m->target_value) * 100, 1) : 0,
            ];
        }));
    }
}
