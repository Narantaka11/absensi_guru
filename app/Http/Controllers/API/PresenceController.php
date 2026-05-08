<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    // =========================================================================
    // Teacher endpoints
    // =========================================================================

    /**
     * GET /api/v1/presence/today
     * Status absensi guru hari ini.
     */
    public function today(Request $request): JsonResponse
    {
        $data = $this->attendanceService->getTodayStatus($request->user());
        return $this->success('Status absensi hari ini.', $data);
    }

    /**
     * GET /api/v1/presence/history?month=5&year=2026&per_page=15
     * Histori absensi guru bulan tertentu (paginated).
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month'    => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'     => ['nullable', 'integer', 'min:2020', 'max:2099'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $paginator = $this->attendanceService->getHistory(
            user:    $request->user(),
            month:   $validated['month']    ?? now()->month,
            year:    $validated['year']     ?? now()->year,
            perPage: $validated['per_page'] ?? 15,
        );

        // Format tiap item
        $items = collect($paginator->items())
            ->map(fn($p) => $this->attendanceService->formatPresence($p))
            ->values();

        return $this->success('Histori absensi berhasil diambil.', [
            'items'      => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/presence/summary?month=5&year=2026
     * Rekap ringkasan absensi guru bulan tertentu.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'  => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ]);

        $summary = $this->attendanceService->getMonthlySummary(
            user:  $request->user(),
            month: $validated['month'] ?? now()->month,
            year:  $validated['year']  ?? now()->year,
        );

        return $this->success('Rekap absensi berhasil diambil.', $summary);
    }

    /**
     * POST /api/v1/presence/check-in  (multipart/form-data)
     *
     * Body:
     *   latitude      required  float
     *   longitude     required  float
     *   photo         required  image (jpg/jpeg/png/webp, max 5 MB)
     *   location_id   optional  int   — ID lokasi spesifik (jika Flutter kirim)
     *   device_info   optional  string — "Samsung Galaxy S23 / Android 14"
     *   notes         optional  string
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'photo'       => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'device_info' => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        try {
            // Simpan foto ke disk public: presences/check-in/YYYY/MM/
            $photoPath = $request->file('photo')->store(
                'presences/check-in/' . now()->format('Y/m'),
                'public'
            );

            $presence = $this->attendanceService->checkIn(
                user:       $request->user(),
                latitude:   (float) $validated['latitude'],
                longitude:  (float) $validated['longitude'],
                photoPath:  $photoPath,
                notes:      $validated['notes']       ?? null,
                locationId: $validated['location_id'] ?? null,
                deviceInfo: $validated['device_info'] ?? null,
            );

            return $this->success(
                'Check-in berhasil.',
                ['presence' => $this->attendanceService->formatPresence($presence)],
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 422);
        }
    }

    /**
     * POST /api/v1/presence/check-out  (multipart/form-data)
     *
     * Body:
     *   latitude    required  float
     *   longitude   required  float
     *   photo       required  image (jpg/jpeg/png/webp, max 5 MB)
     *   device_info optional  string
     *   notes       optional  string
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'photo'       => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'device_info' => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $photoPath = $request->file('photo')->store(
                'presences/check-out/' . now()->format('Y/m'),
                'public'
            );

            $presence = $this->attendanceService->checkOut(
                user:       $request->user(),
                latitude:   (float) $validated['latitude'],
                longitude:  (float) $validated['longitude'],
                photoPath:  $photoPath,
                notes:      $validated['notes']       ?? null,
                deviceInfo: $validated['device_info'] ?? null,
            );

            return $this->success(
                'Check-out berhasil.',
                ['presence' => $this->attendanceService->formatPresence($presence)]
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), null, 422);
        }
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
