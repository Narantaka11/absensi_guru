<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint rekap periodik untuk admin/kepsek (Day 6).
 * Route group: /api/v1/admin/recap/* — proteksi auth:sanctum + api.admin
 */
class AdminRecapController extends Controller
{
    public function __construct(private readonly RecapService $recapService) {}

    /**
     * GET /api/v1/admin/recap/daily?date=2026-05-06
     * Rekap harian semua guru.
     * Jika date tidak diisi, default: hari ini.
     */
    public function daily(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $data = $this->recapService->getDailyRecap($date);

        return $this->success('Rekap harian berhasil diambil.', $data);
    }

    /**
     * GET /api/v1/admin/recap/weekly?start_date=2026-05-05&end_date=2026-05-11
     * Rekap mingguan semua guru.
     * Jika tidak diisi: default Senin–Minggu minggu ini.
     */
    public function weekly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfWeek()->toDateString();
        $endDate   = $validated['end_date']   ?? now()->endOfWeek()->toDateString();

        $data = $this->recapService->getWeeklyRecap($startDate, $endDate);

        return $this->success('Rekap mingguan berhasil diambil.', $data);
    }

    /**
     * GET /api/v1/admin/recap/monthly?month=5&year=2026
     * Rekap bulanan semua guru — summary + statistik bukti absensi.
     */
    public function monthly(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'  => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ]);

        $month = $validated['month'] ?? now()->month;
        $year  = $validated['year']  ?? now()->year;

        $data = $this->recapService->getMonthlyRecap($month, $year);

        return $this->success('Rekap bulanan berhasil diambil.', $data);
    }

    /**
     * GET /api/v1/admin/recap/teachers/{user}?month=5&year=2026
     * Rekap bulanan detail satu guru — timeline harian lengkap.
     * Admin gunakan ini untuk halaman detail guru.
     */
    public function teacherDetail(User $user, Request $request): JsonResponse
    {
        if (!$user->isTeacher()) {
            return $this->error('User ini bukan guru.', null, 422);
        }

        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'  => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ]);

        $month = $validated['month'] ?? now()->month;
        $year  = $validated['year']  ?? now()->year;

        $data = $this->recapService->getTeacherMonthlyDetail($user, $month, $year);

        return $this->success('Rekap detail guru berhasil diambil.', $data);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
        ], $status);
    }

    private function error(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => [],
            'errors'  => $errors,
        ], $status);
    }
}
