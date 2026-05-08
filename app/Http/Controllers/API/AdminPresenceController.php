<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Presence;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint khusus admin/kepsek untuk mereview bukti absensi guru.
 * Semua route di sini sudah diproteksi middleware auth:sanctum + api.admin.
 */
class AdminPresenceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    // =========================================================================
    // Daftar Absensi (dengan filter)
    // =========================================================================

    /**
     * GET /api/v1/admin/presences
     * Daftar semua absensi dengan filter fleksibel.
     *
     * Query params:
     *   teacher_id        int
     *   date              string  YYYY-MM-DD
     *   month             int
     *   year              int
     *   status            string  hadir|terlambat|tidak_hadir|sakit|izin
     *   has_photo         bool    1|0  — filter yang ada/tidak ada foto
     *   radius_invalid    bool    1    — filter yang lokasi tidak valid
     *   per_page          int     default 20
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id'    => ['nullable', 'integer', 'exists:users,id'],
            'date'          => ['nullable', 'date'],
            'month'         => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'          => ['nullable', 'integer', 'min:2020', 'max:2099'],
            'status'        => ['nullable', 'string', 'in:hadir,terlambat,tidak_hadir,sakit,izin'],
            'has_photo'     => ['nullable', 'boolean'],
            'radius_invalid'=> ['nullable', 'boolean'],
            'per_page'      => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = Presence::with(['user', 'location'])
            ->orderBy('presence_date', 'desc')
            ->orderBy('check_in_time', 'desc');

        // Filter guru
        if (!empty($validated['teacher_id'])) {
            $query->where('user_id', $validated['teacher_id']);
        }

        // Filter tanggal spesifik
        if (!empty($validated['date'])) {
            $query->whereDate('presence_date', $validated['date']);
        } else {
            // Filter bulan/tahun
            if (!empty($validated['month'])) {
                $query->whereMonth('presence_date', $validated['month']);
            }
            if (!empty($validated['year'])) {
                $query->whereYear('presence_date', $validated['year']);
            }
        }

        // Filter status
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filter ada/tidak foto check-in
        if (isset($validated['has_photo'])) {
            if ($validated['has_photo']) {
                $query->whereNotNull('check_in_photo');
            } else {
                $query->whereNull('check_in_photo');
            }
        }

        // Filter lokasi tidak valid (untuk investigasi fraud)
        if (!empty($validated['radius_invalid'])) {
            $query->where('check_in_is_within_radius', false);
        }

        $paginator = $query->paginate($validated['per_page'] ?? 20);

        $items = collect($paginator->items())
            ->map(fn(Presence $p) => $this->formatPresenceListItem($p))
            ->values();

        return $this->success('Daftar absensi berhasil diambil.', [
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
     * GET /api/v1/admin/presences/{id}
     * Detail lengkap satu record absensi — foto, GPS, audit trail.
     */
    public function show(Presence $presence): JsonResponse
    {
        $presence->load(['user.teacher', 'location']);

        return $this->success('Detail absensi berhasil diambil.', [
            'presence' => $this->attendanceService->formatPresence($presence, detailed: true),
        ]);
    }

    // =========================================================================
    // Daftar Guru + Summary
    // =========================================================================

    /**
     * GET /api/v1/admin/teachers?month=5&year=2026
     * Semua guru dengan rekap kehadiran bulan tertentu.
     */
    public function teachers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'  => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ]);

        $month = $validated['month'] ?? now()->month;
        $year  = $validated['year']  ?? now()->year;

        $summary = $this->attendanceService->getAllTeachersSummary($month, $year);

        return $this->success('Rekap guru berhasil diambil.', [
            'month'    => $month,
            'year'     => $year,
            'teachers' => $summary->values(),
        ]);
    }

    /**
     * GET /api/v1/admin/teachers/{user}?month=5&year=2026
     * Detail guru tertentu: profil + ringkasan bulan ini.
     */
    public function teacherShow(User $user, Request $request): JsonResponse
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

        $user->load('teacher.location');
        $summary = $this->attendanceService->getMonthlySummary($user, $month, $year);

        return $this->success('Detail guru berhasil diambil.', [
            'teacher' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'nip'         => $user->teacher?->nip,
                'phone'       => $user->teacher?->phone,
                'subject'     => $user->teacher?->subject,
                'base_salary' => $user->teacher?->base_salary,
                'location'    => $user->teacher?->location ? [
                    'id'   => $user->teacher->location->id,
                    'name' => $user->teacher->location->name,
                ] : null,
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * GET /api/v1/admin/teachers/{user}/presences?month=5&year=2026&per_page=20
     * Daftar absensi guru tertentu, per bulan, dengan detail bukti.
     */
    public function teacherPresences(User $user, Request $request): JsonResponse
    {
        if (!$user->isTeacher()) {
            return $this->error('User ini bukan guru.', null, 422);
        }

        $validated = $request->validate([
            'month'    => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'     => ['nullable', 'integer', 'min:2020', 'max:2099'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $month   = $validated['month']    ?? now()->month;
        $year    = $validated['year']     ?? now()->year;
        $perPage = $validated['per_page'] ?? 20;

        $paginator = $user->presences()
            ->with('location')
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->orderBy('presence_date', 'desc')
            ->paginate($perPage);

        $items = collect($paginator->items())
            ->map(fn(Presence $p) => $this->attendanceService->formatPresence($p, detailed: true))
            ->values();

        return $this->success('Absensi guru berhasil diambil.', [
            'teacher'    => ['id' => $user->id, 'name' => $user->name],
            'month'      => $month,
            'year'       => $year,
            'items'      => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Format ringkas untuk daftar — cukup untuk tabel admin.
     * Berbeda dari formatPresence (detailed) yang dipakai di halaman detail.
     */
    private function formatPresenceListItem(Presence $presence): array
    {
        return [
            'id'             => $presence->id,
            'presence_date'  => $presence->presence_date?->toDateString(),
            'status'         => $presence->status,
            'late_minutes'   => $presence->late_minutes,
            'work_hours'     => $presence->getWorkHours(),
            'teacher'        => $presence->user ? [
                'id'   => $presence->user->id,
                'name' => $presence->user->name,
            ] : null,
            'location_name'  => $presence->location?->name,
            'check_in_time'  => $presence->check_in_time?->format('H:i:s'),
            'check_out_time' => $presence->check_out_time?->format('H:i:s'),
            'evidence' => [
                'has_check_in_photo'       => !empty($presence->check_in_photo),
                'has_check_out_photo'      => !empty($presence->check_out_photo),
                'check_in_radius_valid'    => $presence->check_in_is_within_radius,
                'check_in_distance_m'      => $presence->check_in_distance_meters,
            ],
        ];
    }

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
