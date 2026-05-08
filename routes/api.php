<?php

use App\Http\Controllers\API\AdminPresenceController;
use App\Http\Controllers\API\AdminRecapController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\PresenceController;
use App\Http\Controllers\API\PayrollController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // =========================================================================
    // Auth — publik (tidak perlu token)
    // =========================================================================
    Route::post('/auth/login', [AuthController::class, 'login']);

    // =========================================================================
    // Endpoint privat (semua butuh token Sanctum)
    // =========================================================================
    Route::middleware('auth:sanctum')->group(function (): void {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me',      [AuthController::class, 'me']);

        // =====================================================================
        // Lokasi sekolah — guru & admin
        // =====================================================================
        Route::prefix('locations')->group(function (): void {
            Route::get('/',         [LocationController::class, 'index']);    // list semua lokasi aktif
            Route::get('/nearest',  [LocationController::class, 'nearest']); // lokasi terdekat dari koordinat
            Route::get('/{location}', [LocationController::class, 'show']);  // detail satu lokasi
        });

        // =====================================================================
        // Absensi — guru (teacher)
        // Day 4: check-in/check-out GPS + foto wajib
        // Day 6: rekap sendiri
        // =====================================================================
        Route::prefix('presence')->group(function (): void {
            Route::get('/today',      [PresenceController::class, 'today']);    // status hari ini
            Route::get('/history',    [PresenceController::class, 'history']); // histori paginated
            Route::get('/summary',    [PresenceController::class, 'summary']); // ringkasan bulan
            Route::post('/check-in',  [PresenceController::class, 'checkIn']);  // Day 4 — foto wajib + geofence
            Route::post('/check-out', [PresenceController::class, 'checkOut']); // Day 4 — foto wajib + geofence
        });

        // =====================================================================
        // Penggajian — guru (teacher)
        // Day 7: guru lihat slip gaji sendiri
        // =====================================================================
        Route::prefix('payroll')->group(function (): void {
            Route::get('/me',       [PayrollController::class, 'mySlip']);    // slip bulan ini
            Route::get('/history',  [PayrollController::class, 'myHistory']); // histori per tahun
        });

        // =====================================================================
        // Admin / Kepala Sekolah — proteksi api.admin
        // Day 5: review bukti absensi (foto + GPS + geofence)
        // Day 6: rekap periodik
        // Day 7: kelola penggajian (generate, approve, paid, revert)
        // =====================================================================
        Route::middleware('api.admin')->prefix('admin')->group(function (): void {

            // Day 5 — Review absensi
            Route::prefix('presences')->group(function (): void {
                Route::get('/',             [AdminPresenceController::class, 'index']);  // list + filter
                Route::get('/{presence}',   [AdminPresenceController::class, 'show']);  // detail + audit
            });

            // Day 5 — Data guru
            Route::prefix('teachers')->group(function (): void {
                Route::get('/',                         [AdminPresenceController::class, 'teachers']);         // list guru + summary
                Route::get('/{user}',                   [AdminPresenceController::class, 'teacherShow']);      // profil + summary guru
                Route::get('/{user}/presences',         [AdminPresenceController::class, 'teacherPresences']); // absensi guru tertentu
            });

            // Day 6 — Rekap periodik
            Route::prefix('recap')->group(function (): void {
                Route::get('/daily',              [AdminRecapController::class, 'daily']);         // rekap harian
                Route::get('/weekly',             [AdminRecapController::class, 'weekly']);        // rekap mingguan
                Route::get('/monthly',            [AdminRecapController::class, 'monthly']);       // rekap bulanan
                Route::get('/teachers/{user}',    [AdminRecapController::class, 'teacherDetail']); // detail guru per bulan
            });

            // Day 7 — Manajemen penggajian
            Route::prefix('payroll')->group(function (): void {
                Route::get('/',                   [PayrollController::class, 'index']);        // daftar slip semua guru
                Route::get('/{salary}',           [PayrollController::class, 'show']);         // detail satu slip
                Route::post('/generate',          [PayrollController::class, 'generate']);     // kalkulasi payroll (satu/semua)
                Route::post('/{salary}/approve',  [PayrollController::class, 'approve']);      // setujui slip (draft→approved)
                Route::post('/{salary}/paid',     [PayrollController::class, 'markAsPaid']);   // tandai sudah dibayar (approved→paid)
                Route::post('/{salary}/revert',   [PayrollController::class, 'revert']);       // kembalikan ke draft (approved→draft)
            });
        });
    });
});
