<?php

namespace App\Modules\PlatformServices\Http\Controllers;

use App\Services\SearchService;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService,
        private BranchScopeService $branchScope
    ) {}

    public function globalSearch(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $type = $request->query('type', 'all');
        $branchId = $request->query('branch_id', $request->user()->branch_id ?? null);

        if (strlen($query) < 2) {
            return response()->json(['results' => [], 'total' => 0]);
        }

        $results = $this->searchService->search($query, $type, $branchId);

        return response()->json([
            'query' => $query,
            'results' => $results,
            'total' => count($results),
        ]);
    }
}
