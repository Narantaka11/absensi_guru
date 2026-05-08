<?php

namespace App;

/**
 * @OA\Info(
 *   title="Absensi Guru API",
 *   version="1.0.0",
 *   description="API untuk sistem absensi guru dengan GPS geofencing dan payroll",
 *   contact=@OA\Contact(name="Support", email="support@absensi-guru.com"),
 *   license=@OA\License(name="MIT", url="https://opensource.org/licenses/MIT")
 * )
 * @OA\Server(url="http://localhost:8000", description="Development Server")
 * @OA\SecurityScheme(type="http", name="bearerAuth", in="header", scheme="bearer", bearerFormat="token")
 * @OA\Tag(name="Auth", description="Authentication endpoints")
 * @OA\Tag(name="Presence", description="Teacher attendance endpoints")
 * @OA\Tag(name="Payroll", description="Teacher payroll endpoints")
 * @OA\Tag(name="Locations", description="School location endpoints")
 * @OA\Tag(name="Admin - Presence", description="Admin presence endpoints")
 * @OA\Tag(name="Admin - Recap", description="Admin recap endpoints")
 * @OA\Tag(name="Admin - Payroll", description="Admin payroll endpoints")
 */
class OpenApi {}
