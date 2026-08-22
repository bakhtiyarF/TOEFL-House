<?php

namespace App\Modules\Iam\Http\Controllers;

use App\Modules\Iam\Models\Role;
use App\Modules\Iam\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RoleController extends Controller
{
    public function indexRoles(): JsonResponse
    {
        return response()->json(
            Role::orderBy('sort_order')->get()
        );
    }

    public function indexPermissions(): JsonResponse
    {
        return response()->json(
            Permission::orderBy('category')->orderBy('code')->get()
        );
    }
}
