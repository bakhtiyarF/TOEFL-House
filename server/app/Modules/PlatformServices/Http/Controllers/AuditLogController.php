<?php

namespace App\Modules\PlatformServices\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = $this->branchScopeService->resolve($request->user(), $request->query('branch_id', 'all'));

        $query = DB::table('audit_logs')
            ->when($request->query('action'), fn($q, $a) => $q->where('action', 'like', "%{$a}%"))
            ->when($request->query('operator'), fn($q, $o) => $q->where('operator_name', 'like', "%{$o}%"))
            ->when($request->query('from'), fn($q, $d) => $q->where('date', '>=', $d))
            ->when($request->query('to'), fn($q, $d) => $q->where('date', '<=', $d))
            ->orderByDesc('date')
            ->orderByDesc('time');

        if (!$scope['isAll']) {
            $query->where('branch_id', $scope['branchId']);
        }

        $logs = $request->query('per_page')
            ? $query->paginate((int)$request->query('per_page', 50))
            : $query->limit(200)->get();

        return response()->json($logs);
    }
}
