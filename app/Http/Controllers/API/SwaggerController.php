<?php

namespace App\Http\Controllers\API;

/**
 * @OA\Info(
 *     title="Absensi Guru API",
 *     version="1.0.0",
 *     description="API untuk sistem absensi guru dengan GPS geofencing dan payroll",
 *     contact=@OA\Contact(
 *         name="Support",
 *         email="support@absensi-guru.com"
 *     ),
 *     license=@OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Login with username and password to get the authentication token",
 *     name="Token based based security",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth",
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Presence",
 *     description="Teacher attendance/presence endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Payroll",
 *     description="Teacher payroll endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Locations",
 *     description="School location endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Presence",
 *     description="Admin presence management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Recap",
 *     description="Admin attendance recap endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Admin - Payroll",
 *     description="Admin payroll management endpoints"
 * )
 */
class SwaggerController
{
    // This controller is only for Swagger documentation
}
