<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Presence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceService
{
    /**
     * Batas waktu masuk (tidak terlambat) — 07:00
     */
    public const CHECK_IN_DEADLINE = '07:00:00';

    // =========================================================================
    // Teacher-facing methods
    // =========================================================================

    /**
     * Status absensi guru hari ini.
     */
    public function getTodayStatus(User $user): array
    {
        $today = now()->toDateString();

        /** @var Presence|null $presence */
        $presence = $user->presences()
            ->with('location')
            ->whereDate('presence_date', $today)
            ->first();

        return [
            'date'            => $today,
            'has_checked_in'  => !is_null($presence?->check_in_time),
            'has_checked_out' => !is_null($presence?->check_out_time),
            'presence'        => $presence ? $this->formatPresence($presence) : null,
        ];
    }

    /**
     * Histori absensi guru — paginated, filter bulan/tahun.
     */
    public function getHistory(User $user, int $month, int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $user->presences()
            ->with('location')
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->orderBy('presence_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Rekap ringkasan absensi guru satu bulan.
     */
    public function getMonthlySummary(User $user, int $month, int $year): array
    {
        $presences = $user->presences()
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->get();

        $summary = $this->buildSummary($presences);
        $summary['attendance_percentage'] = $this->calculatePercentage($presences);
        $summary['total_work_hours']       = $this->calculateTotalWorkHours($presences);
        $summary['month']                  = $month;
        $summary['year']                   = $year;

        return $summary;
    }

    /**
     * Rekap semua guru untuk satu bulan (untuk admin).
     */
    public function getAllTeachersSummary(int $month, int $year): Collection
    {
        $teachers = User::where('role', User::ROLE_TEACHER)
            ->with('teacher')
            ->get();

        return $teachers->map(function (User $teacher) use ($month, $year) {
            $presences = $teacher->presences()
                ->whereYear('presence_date', $year)
                ->whereMonth('presence_date', $month)
                ->get();

            return [
                'teacher'    => [
                    'id'      => $teacher->id,
                    'name'    => $teacher->name,
                    'email'   => $teacher->email,
                    'nip'     => $teacher->teacher?->nip,
                    'subject' => $teacher->teacher?->subject,
                ],
                'summary'    => $this->buildSummary($presences),
                'percentage' => $this->calculatePercentage($presences),
                'work_hours' => $this->calculateTotalWorkHours($presences),
            ];
        });
    }

    // =========================================================================
    // Check-in / Check-out
    // =========================================================================

    /**
     * Proses check-in guru dengan validasi geofence + audit trail.
     *
     * @throws \Exception jika duplikasi, atau di luar radius geofence
     */
    public function checkIn(
        User    $user,
        float   $latitude,
        float   $longitude,
        string  $photoPath,          // WAJIB — foto selfie dokumentasi
        ?string $notes      = null,
        ?int    $locationId = null,  // opsional: ID lokasi spesifik dari Flutter
        ?string $deviceInfo = null,  // opsional: info perangkat untuk audit
    ): Presence {
        $today = now()->toDateString();

        // 1. Cek duplikasi check-in
        $existing = $user->presences()->whereDate('presence_date', $today)->first();
        if ($existing?->check_in_time) {
            throw new \Exception('Kamu sudah melakukan check-in hari ini.');
        }

        // 2. Validasi geofence
        $geofence = $this->resolveGeofence($latitude, $longitude, $locationId);

        // 3. Hitung keterlambatan menggunakan timestamp server
        $serverTime  = now();
        $lateMinutes = $this->calculateLateMinutes($serverTime);
        $status      = $lateMinutes > 0 ? 'terlambat' : 'hadir';

        $data = [
            'user_id'                    => $user->id,
            'location_id'                => $geofence['location_id'],
            'presence_date'              => $today,
            'check_in_time'              => $serverTime,
            'check_in_server_time'       => $serverTime,
            'check_in_latitude'          => $latitude,
            'check_in_longitude'         => $longitude,
            'check_in_photo'             => $photoPath,
            'check_in_distance_meters'   => $geofence['distance_meters'],
            'check_in_is_within_radius'  => $geofence['is_within_radius'],
            'check_in_device_info'       => $deviceInfo,
            'status'                     => $status,
            'late_minutes'               => $lateMinutes,
            'notes'                      => $notes,
        ];

        if ($existing) {
            $existing->update($data);
            return $existing->fresh()->load('location');
        }

        return Presence::create($data)->load('location');
    }

    /**
     * Proses check-out guru dengan validasi + audit trail.
     *
     * @throws \Exception jika belum check-in atau sudah check-out
     */
    public function checkOut(
        User    $user,
        float   $latitude,
        float   $longitude,
        string  $photoPath,          // WAJIB — foto dokumentasi check-out
        ?string $notes      = null,
        ?string $deviceInfo = null,
    ): Presence {
        $today = now()->toDateString();

        /** @var Presence|null $presence */
        $presence = $user->presences()
            ->with('location')
            ->whereDate('presence_date', $today)
            ->first();

        if (!$presence || !$presence->check_in_time) {
            throw new \Exception('Kamu belum melakukan check-in hari ini.');
        }

        if ($presence->check_out_time) {
            throw new \Exception('Kamu sudah melakukan check-out hari ini.');
        }

        // Validasi geofence untuk check-out (pakai lokasi yang sama saat check-in)
        $geofence = $this->resolveGeofence($latitude, $longitude, $presence->location_id);

        $serverTime = now();

        $presence->update([
            'check_out_time'              => $serverTime,
            'check_out_server_time'       => $serverTime,
            'check_out_latitude'          => $latitude,
            'check_out_longitude'         => $longitude,
            'check_out_photo'             => $photoPath,
            'check_out_distance_meters'   => $geofence['distance_meters'],
            'check_out_is_within_radius'  => $geofence['is_within_radius'],
            'check_out_device_info'       => $deviceInfo,
            'notes'                       => $notes ?? $presence->notes,
        ]);

        return $presence->fresh()->load('location');
    }

    // =========================================================================
    // Formatting
    // =========================================================================

    /**
     * Format satu Presence untuk response JSON — dipakai guru maupun admin.
     * $detailed = true: sertakan info audit (server time, device, raw GPS).
     */
    public function formatPresence(Presence $presence, bool $detailed = false): array
    {
        $data = [
            'id'             => $presence->id,
            'presence_date'  => $presence->presence_date?->toDateString(),
            'status'         => $presence->status,
            'late_minutes'   => $presence->late_minutes,
            'notes'          => $presence->notes,
            'work_hours'     => $presence->getWorkHours(),

            'check_in' => [
                'time'      => $presence->check_in_time?->format('H:i:s'),
                'photo_url' => $presence->check_in_photo
                    ? asset('storage/' . $presence->check_in_photo)
                    : null,
                'latitude'  => $presence->check_in_latitude,
                'longitude' => $presence->check_in_longitude,
            ],

            'check_out' => [
                'time'      => $presence->check_out_time?->format('H:i:s'),
                'photo_url' => $presence->check_out_photo
                    ? asset('storage/' . $presence->check_out_photo)
                    : null,
                'latitude'  => $presence->check_out_latitude,
                'longitude' => $presence->check_out_longitude,
            ],

            'geofence' => [
                'location'                    => $presence->location ? [
                    'id'             => $presence->location->id,
                    'name'           => $presence->location->name,
                    'address'        => $presence->location->address,
                    'radius_meters'  => $presence->location->radius_meters,
                    'center_lat'     => $presence->location->latitude,
                    'center_lng'     => $presence->location->longitude,
                ] : null,
                'check_in_distance_meters'    => $presence->check_in_distance_meters,
                'check_in_is_within_radius'   => $presence->check_in_is_within_radius,
                'check_out_distance_meters'   => $presence->check_out_distance_meters,
                'check_out_is_within_radius'  => $presence->check_out_is_within_radius,
            ],
        ];

        // Info tambahan untuk audit — hanya dikirim ke admin
        if ($detailed) {
            $data['audit'] = [
                'check_in_server_time'  => $presence->check_in_server_time?->toIso8601String(),
                'check_out_server_time' => $presence->check_out_server_time?->toIso8601String(),
                'check_in_device_info'  => $presence->check_in_device_info,
                'check_out_device_info' => $presence->check_out_device_info,
                'teacher'               => $presence->user ? [
                    'id'    => $presence->user->id,
                    'name'  => $presence->user->name,
                    'email' => $presence->user->email,
                ] : null,
            ];

            $data['evidence_status'] = $this->buildEvidenceStatus($presence);
        }

        return $data;
    }

    // =========================================================================
    // Geofence helpers
    // =========================================================================

    /**
     * Hitung menit keterlambatan dari jam check-in (server time).
     */
    public function calculateLateMinutes(Carbon $checkInTime): int
    {
        $deadline = Carbon::createFromTimeString(self::CHECK_IN_DEADLINE);
        $deadline->setDate($checkInTime->year, $checkInTime->month, $checkInTime->day);

        if ($checkInTime->lessThanOrEqualTo($deadline)) {
            return 0;
        }

        return (int) $deadline->diffInMinutes($checkInTime);
    }

    /**
     * Resolusi geofence: cari lokasi aktif terdekat atau gunakan locationId yg diberikan.
     * Jika ada lokasi aktif tapi koordinat di luar radius → lempar exception.
     * Jika tidak ada lokasi aktif sama sekali → lewati validasi (mode dev-friendly).
     *
     * @throws \Exception jika lokasi ditemukan tapi di luar radius
     */
    public function resolveGeofence(float $lat, float $lng, ?int $locationId = null): array
    {
        // Cari lokasi yang dimaksud
        $location = $locationId
            ? Location::active()->find($locationId)
            : $this->findNearestActiveLocation($lat, $lng);

        // Tidak ada lokasi aktif sama sekali — lewati validasi (mode dev / sekolah belum setup)
        if (!$location) {
            return [
                'location_id'      => null,
                'distance_meters'  => null,
                'is_within_radius' => null,
            ];
        }

        $distanceMeters = (int) round($location->distanceTo($lat, $lng));
        $isWithin       = $location->isWithinRadius($lat, $lng);

        if (!$isWithin) {
            throw new \Exception(
                "Lokasi tidak valid. Kamu berada {$distanceMeters} meter dari {$location->name}, " .
                "sedangkan batas radius adalah {$location->radius_meters} meter. " .
                "Pastikan kamu berada di area sekolah saat absen."
            );
        }

        return [
            'location_id'      => $location->id,
            'distance_meters'  => $distanceMeters,
            'is_within_radius' => true,
        ];
    }

    /**
     * Temukan lokasi aktif terdekat dari koordinat yang diberikan.
     */
    private function findNearestActiveLocation(float $lat, float $lng): ?Location
    {
        $locations = Location::active()->get();

        if ($locations->isEmpty()) {
            return null;
        }

        return $locations
            ->sortBy(fn(Location $l) => $l->distanceTo($lat, $lng))
            ->first();
    }

    // =========================================================================
    // Summary helpers
    // =========================================================================

    public function buildSummary(\Illuminate\Support\Collection $presences): array
    {
        return [
            'present'    => $presences->where('status', 'hadir')->count(),
            'late'       => $presences->where('status', 'terlambat')->count(),
            'absent'     => $presences->where('status', 'tidak_hadir')->count(),
            'sick'       => $presences->where('status', 'sakit')->count(),
            'permission' => $presences->where('status', 'izin')->count(),
            'total'      => $presences->count(),
        ];
    }

    public function calculatePercentage(\Illuminate\Support\Collection $presences): float
    {
        $total   = $presences->count();
        $present = $presences->whereIn('status', ['hadir', 'terlambat'])->count();

        return $total > 0 ? round($present / $total * 100, 2) : 0.0;
    }

    public function calculateTotalWorkHours(\Illuminate\Support\Collection $presences): float
    {
        return $presences->sum(fn(Presence $p) => $p->getWorkHours() ?? 0);
    }

    // =========================================================================
    // Evidence helpers
    // =========================================================================

    /**
     * Status kelengkapan bukti absensi — untuk indikator di dashboard admin.
     */
    private function buildEvidenceStatus(Presence $presence): array
    {
        $hasCheckInPhoto   = !empty($presence->check_in_photo);
        $hasCheckOutPhoto  = !empty($presence->check_out_photo);
        $hasCheckInGps     = !is_null($presence->check_in_latitude);
        $hasCheckOutGps    = !is_null($presence->check_out_latitude);

        return [
            'check_in_photo'          => $hasCheckInPhoto,
            'check_out_photo'         => $hasCheckOutPhoto,
            'check_in_gps'            => $hasCheckInGps,
            'check_out_gps'           => $hasCheckOutGps,
            'check_in_radius_valid'   => $presence->check_in_is_within_radius,
            'check_out_radius_valid'  => $presence->check_out_is_within_radius,
            'is_complete'             => $hasCheckInPhoto && $hasCheckOutPhoto
                && $hasCheckInGps && $hasCheckOutGps,
        ];
    }
}
