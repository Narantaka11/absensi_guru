<?php

namespace App\Http\Controllers\API;

/**
 * @OA\Post(
 *     path="/api/v1/auth/login",
 *     tags={"Auth"},
 *     summary="Login user",
 *     description="Login dengan email dan password, kembalikan token Sanctum",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Credentials untuk login",
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email", example="guru@sekolah.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123"),
 *             @OA\Property(property="device_name", type="string", example="Flutter Mobile App")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login berhasil",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Login berhasil"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="token", type="string", example="1|abcdefghijklmnop"),
 *                 @OA\Property(property="user", type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Budi Santoso"),
 *                     @OA\Property(property="email", type="string", example="guru@sekolah.com"),
 *                     @OA\Property(property="role", type="string", example="teacher")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/auth/logout",
 *     tags={"Auth"},
 *     summary="Logout user",
 *     description="Logout dan revoke token Sanctum",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Logout berhasil",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Logout berhasil")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/auth/me",
 *     tags={"Auth"},
 *     summary="Get current user profile",
 *     description="Ambil profil user yang sedang login",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Profil user berhasil diambil",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Profil berhasil diambil"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="user", type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Budi Santoso"),
 *                     @OA\Property(property="email", type="string", example="guru@sekolah.com"),
 *                     @OA\Property(property="role", type="string", example="teacher"),
 *                     @OA\Property(property="created_at", type="string", format="date-time")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/locations",
 *     tags={"Locations"},
 *     summary="List all school locations",
 *     description="Ambil daftar semua lokasi sekolah yang aktif",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="List lokasi berhasil",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Kampus Pusat"),
 *                     @OA\Property(property="latitude", type="number", format="float", example=-6.2088),
 *                     @OA\Property(property="longitude", type="number", format="float", example=106.8456),
 *                     @OA\Property(property="radius", type="integer", example=100)
 *                 )
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/locations/nearest",
 *     tags={"Locations"},
 *     summary="Find nearest location",
 *     description="Cari lokasi terdekat dari koordinat yang diberikan",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="latitude", in="query", required=true, schema={"type":"number", "format":"float"}),
 *     @OA\Parameter(name="longitude", in="query", required=true, schema={"type":"number", "format":"float"}),
 *     @OA\Response(response=200, description="Lokasi terdekat ditemukan"),
 *     @OA\Response(response=404, description="Lokasi tidak ditemukan")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/locations/{location}",
 *     tags={"Locations"},
 *     summary="Get location detail",
 *     description="Ambil detail satu lokasi sekolah",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="location", in="path", required=true, schema={"type":"integer"}),
 *     @OA\Response(response=200, description="Detail lokasi"),
 *     @OA\Response(response=404, description="Lokasi tidak ditemukan")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/presence/today",
 *     tags={"Presence"},
 *     summary="Get today attendance status",
 *     description="Ambil status absensi guru hari ini (check-in/check-out)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Status absensi hari ini",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="check_in_at", type="string", format="date-time", nullable=true),
 *                 @OA\Property(property="check_out_at", type="string", format="date-time", nullable=true),
 *                 @OA\Property(property="status", type="string", example="pending")
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1/presence/history",
 *     tags={"Presence"},
 *     summary="Get attendance history",
 *     description="Ambil histori absensi guru (paginated)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="month", in="query", schema={"type":"integer", "minimum":1, "maximum":12}),
 *     @OA\Parameter(name="year", in="query", schema={"type":"integer", "minimum":2020}),
 *     @OA\Parameter(name="per_page", in="query", schema={"type":"integer", "minimum":5, "maximum":100}),
 *     @OA\Response(response=200, description="Histori absensi berhasil diambil")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/presence/summary",
 *     tags={"Presence"},
 *     summary="Get monthly attendance summary",
 *     description="Ambil ringkasan absensi guru per bulan",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="month", in="query", schema={"type":"integer"}),
 *     @OA\Parameter(name="year", in="query", schema={"type":"integer"}),
 *     @OA\Response(response=200, description="Ringkasan absensi")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/presence/check-in",
 *     tags={"Presence"},
 *     summary="Check-in with GPS and photo",
 *     description="Absensi masuk dengan GPS location dan foto",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         content={
 *             "multipart/form-data"={
 *                 @OA\Schema(
 *                     @OA\Property(property="latitude", type="number", format="float", example=-6.2088),
 *                     @OA\Property(property="longitude", type="number", format="float", example=106.8456),
 *                     @OA\Property(property="photo", type="string", format="binary"),
 *                     @OA\Property(property="location_id", type="integer", nullable=true),
 *                     @OA\Property(property="device_info", type="string", nullable=true),
 *                     @OA\Property(property="notes", type="string", nullable=true)
 *                 )
 *             }
 *         }
 *     ),
 *     @OA\Response(response=201, description="Check-in berhasil"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/presence/check-out",
 *     tags={"Presence"},
 *     summary="Check-out with GPS and photo",
 *     description="Absensi keluar dengan GPS location dan foto",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         content={
 *             "multipart/form-data"={
 *                 @OA\Schema(
 *                     @OA\Property(property="latitude", type="number", format="float", example=-6.2088),
 *                     @OA\Property(property="longitude", type="number", format="float", example=106.8456),
 *                     @OA\Property(property="photo", type="string", format="binary"),
 *                     @OA\Property(property="device_info", type="string", nullable=true),
 *                     @OA\Property(property="notes", type="string", nullable=true)
 *                 )
 *             }
 *         }
 *     ),
 *     @OA\Response(response=201, description="Check-out berhasil"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/payroll/me",
 *     tags={"Payroll"},
 *     summary="Get my payroll slip (current month)",
 *     description="Ambil slip gaji saya bulan ini",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Slip gaji berhasil diambil")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/payroll/history",
 *     tags={"Payroll"},
 *     summary="Get my payroll history",
 *     description="Ambil histori gaji saya per tahun",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Histori gaji berhasil diambil")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/admin/presences",
 *     tags={"Admin - Presence"},
 *     summary="List all presences (admin)",
 *     description="Admin: Lihat daftar semua absensi dengan filter",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="teacher_id", in="query", schema={"type":"integer"}),
 *     @OA\Parameter(name="month", in="query", schema={"type":"integer"}),
 *     @OA\Parameter(name="year", in="query", schema={"type":"integer"}),
 *     @OA\Response(response=200, description="Daftar absensi"),
 *     @OA\Response(response=403, description="Forbidden - hanya admin")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/admin/presences/{presence}",
 *     tags={"Admin - Presence"},
 *     summary="Get presence detail (admin)",
 *     description="Admin: Lihat detail absensi dengan bukti foto & GPS",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="presence", in="path", required=true, schema={"type":"integer"}),
 *     @OA\Response(response=200, description="Detail absensi"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/admin/teachers",
 *     tags={"Admin - Presence"},
 *     summary="List all teachers (admin)",
 *     description="Admin: Lihat daftar semua guru dengan summary absensi",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Daftar guru"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/admin/recap/daily",
 *     tags={"Admin - Recap"},
 *     summary="Get daily recap (admin)",
 *     description="Admin: Rekap absensi harian semua guru",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="date", in="query", schema={"type":"string", "format":"date"}),
 *     @OA\Response(response=200, description="Rekap harian"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/admin/recap/weekly",
 *     tags={"Admin - Recap"},
 *     summary="Get weekly recap (admin)",
 *     description="Admin: Rekap absensi mingguan semua guru",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="week", in="query", schema={"type":"integer"}),
 *     @OA\Parameter(name="year", in="query", schema={"type":"integer"}),
 *     @OA\Response(response=200, description="Rekap mingguan"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/admin/recap/monthly",
 *     tags={"Admin - Recap"},
 *     summary="Get monthly recap (admin)",
 *     description="Admin: Rekap absensi bulanan semua guru",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="month", in="query", schema={"type":"integer"}),
 *     @OA\Parameter(name="year", in="query", schema={"type":"integer"}),
 *     @OA\Response(response=200, description="Rekap bulanan"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/admin/payroll",
 *     tags={"Admin - Payroll"},
 *     summary="List payroll slips (admin)",
 *     description="Admin: Lihat daftar slip gaji semua guru",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="month", in="query", schema={"type":"integer"}),
 *     @OA\Parameter(name="year", in="query", schema={"type":"integer"}),
 *     @OA\Parameter(name="status", in="query", schema={"type":"string", "enum":{"draft","approved","paid"}}),
 *     @OA\Response(response=200, description="Daftar slip gaji"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/admin/payroll/generate",
 *     tags={"Admin - Payroll"},
 *     summary="Generate payroll (admin)",
 *     description="Admin: Generate/hitung payroll satu atau semua guru",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="month", type="integer"),
 *             @OA\Property(property="year", type="integer"),
 *             @OA\Property(property="teacher_ids", type="array", items={"type":"integer"}, nullable=true, description="Jika kosong, generate semua")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Payroll berhasil di-generate"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/admin/payroll/{salary}/approve",
 *     tags={"Admin - Payroll"},
 *     summary="Approve payroll (admin)",
 *     description="Admin: Setujui slip gaji (draft → approved)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="salary", in="path", required=true, schema={"type":"integer"}),
 *     @OA\Response(response=200, description="Slip gaji disetujui"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/admin/payroll/{salary}/paid",
 *     tags={"Admin - Payroll"},
 *     summary="Mark payroll as paid (admin)",
 *     description="Admin: Tandai slip gaji sudah dibayar (approved → paid)",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="salary", in="path", required=true, schema={"type":"integer"}),
 *     @OA\Response(response=200, description="Slip gaji ditandai sudah dibayar"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
class SwaggerDocsController
{
    // This controller is only for Swagger documentation
}
