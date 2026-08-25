<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="TOEFL House ERP v3 API",
 *     version="3.0.0",
 *     description="Management Information System API for TOEFL House Educational Institute",
 *     @OA\Contact(
 *         email="api@toeflhouse.af",
 *         name="TOEFL House API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Use Sanctum token authentication",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API endpoints for user authentication"
 * )
 * @OA\Tag(
 *     name="Students",
 *     description="API endpoints for student management"
 * )
 * @OA\Tag(
 *     name="Classes",
 *     description="API endpoints for class management"
 * )
 * @OA\Tag(
 *     name="Teachers",
 *     description="API endpoints for teacher management"
 * )
 * @OA\Tag(
 *     name="Payments",
 *     description="API endpoints for payment processing"
 * )
 * @OA\Tag(
 *     name="Enrollments",
 *     description="API endpoints for enrollment management"
 * )
 * @OA\Tag(
 *     name="Visitors",
 *     description="API endpoints for visitor/lead management"
 * )
 * @OA\Tag(
 *     name="Donations",
 *     description="API endpoints for donation management"
 * )
 * @OA\Tag(
 *     name="Scholarships",
 *     description="API endpoints for scholarship management"
 * )
 * @OA\Tag(
 *     name="Campaigns",
 *     description="API endpoints for campaign management"
 * )
 * @OA\Tag(
 *     name="Reports",
 *     description="API endpoints for report generation"
 * )
 * @OA\Tag(
 *     name="Health",
 *     description="API endpoints for health checks"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
