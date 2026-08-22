<?php

namespace App\Modules\CrmEnrollment\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('campaigns')->orderByDesc('start_date');
        if (!$scope['isAll']) $query->where('branch_id', $scope['branchId']);

        $campaigns = $query->get()->map(function ($c) {
            $visitorCount = DB::table('visitors')->where('campaign_id', $c->id)->count();
            $conversions = DB::table('visitors')->where('campaign_id', $c->id)->where('status', 'registered')->count();
            return [
                ...((array)$c),
                'visitor_count' => $visitorCount,
                'conversions' => $conversions,
                'conversion_rate' => $visitorCount > 0 ? round(($conversions / $visitorCount) * 100, 1) : 0,
            ];
        });

        return response()->json($campaigns);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'source' => 'required|in:ads,social,referral,event,organic,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'branch_id' => 'required|uuid|exists:branches,id',
        ]);

        $id = Str::uuid()->toString();
        DB::table('campaigns')->insert([
            'id' => $id, ...$validated,
            'budget' => $validated['budget'] ?? 0,
            'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(DB::table('campaigns')->where('id', $id)->first(), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'status' => 'in:active,paused,completed',
            'budget' => 'numeric|min:0',
        ]);

        DB::table('campaigns')->where('id', $id)->update([...$validated, 'updated_at' => now()]);
        return response()->json(DB::table('campaigns')->where('id', $id)->first());
    }
}
