<?php

namespace App\Services;

use App\Models\Presence;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class RecapService
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    // =========================================================================
    // Daily recap
    // =========================================================================

    /**
     * Rekap harian — semua guru untuk tanggal tertentu.
     * Cocok untuk dashboard "hari ini" milik admin/kepsek.
     */
    public function getDailyRecap(string $date): array
    {
        $teachers = User::where('role', User::ROLE_TEACHER)
            ->with(['teacher', 'presences' => fn($q) => $q->whereDate('presence_date', $date)->with('location')])
            ->get();

        $records = $teachers->map(function (User $teacher) use ($date) {
            /** @var Presence|null $presence */
            $presence = $teacher->presences->first();

            return [
                'teacher' => [
                    'id'      => $teacher->id,
                    'name'    => $teacher->name,
                    'email'   => $teacher->email,
                    'nip'     => $teacher->teacher?->nip,
                    'subject' => $teacher->teacher?->subject,
                ],
                'date'   => $date,
                'status' => $presence?->status ?? 'tidak_hadir',
                'check_in' => [
                    'time'      => $presence?->check_in_time?->format('H:i:s'),
                    'late_minutes' => $presence?->late_minutes ?? 0,
                    'photo_url' => $presence?->check_in_photo
                        ? asset('storage/' . $presence->check_in_photo) : null,
                    'is_within_radius' => $presence?->check_in_is_within_radius,
                    'distance_meters'  => $presence?->check_in_distance_meters,
                ],
                'check_out' => [
                    'time'      => $presence?->check_out_time?->format('H:i:s'),
                    'photo_url' => $presence?->check_out_photo
                        ? asset('storage/' . $presence->check_out_photo) : null,
                    'is_within_radius' => $presence?->check_out_is_within_radius,
                ],
                'work_hours'     => $presence?->getWorkHours(),
                'location_name'  => $presence?->location?->name,
                'evidence_complete' => $presence
                    ? (!empty($presence->check_in_photo) && !empty($presence->check_out_photo))
                    : false,
            ];
        });

        // Statistik ringkas di atas rekap
        $statistics = [
            'total_teachers' => $teachers->count(),
            'present'        => $records->whereIn('status', ['hadir', 'terlambat'])->count(),
            'late'           => $records->where('status', 'terlambat')->count(),
            'absent'         => $records->where('status', 'tidak_hadir')->count(),
            'sick'           => $records->where('status', 'sakit')->count(),
            'permission'     => $records->where('status', 'izin')->count(),
        ];

        return [
            'date'       => $date,
            'day_name'   => Carbon::parse($date)->isoFormat('dddd, D MMMM YYYY'),
            'statistics' => $statistics,
            'records'    => $records->values(),
        ];
    }

    // =========================================================================
    // Weekly recap
    // =========================================================================

    /**
     * Rekap mingguan — semua guru untuk rentang tanggal tertentu.
     * Flutter kirim start_date + end_date (misal Senin–Minggu).
     */
    public function getWeeklyRecap(string $startDate, string $endDate): array
    {
        $start    = Carbon::parse($startDate)->startOfDay();
        $end      = Carbon::parse($endDate)->endOfDay();
        $dateList = collect(CarbonPeriod::create($start, $end))
            ->map(fn(Carbon $d) => $d->toDateString())
            ->values();

        $teachers = User::where('role', User::ROLE_TEACHER)
            ->with(['teacher', 'presences' => function ($q) use ($start, $end) {
                $q->whereBetween('presence_date', [$start->toDateString(), $end->toDateString()])
                    ->with('location');
            }])
            ->get();

        $records = $teachers->map(function (User $teacher) use ($dateList) {
            $presences   = $teacher->presences;
            $summary     = $this->attendanceService->buildSummary($presences);
            $workHours   = $this->attendanceService->calculateTotalWorkHours($presences);
            $percentage  = $this->attendanceService->calculatePercentage($presences);

            // Breakdown harian dalam minggu ini
            $daily = $dateList->map(function (string $date) use ($presences) {
                /** @var Presence|null $presence */
                $presence = $presences->firstWhere(
                    fn(Presence $p) => $p->presence_date->toDateString() === $date
                );
                return [
                    'date'   => $date,
                    'status' => $presence?->status ?? 'tidak_hadir',
                    'check_in_time'  => $presence?->check_in_time?->format('H:i'),
                    'check_out_time' => $presence?->check_out_time?->format('H:i'),
                    'late_minutes'   => $presence?->late_minutes ?? 0,
                    'work_hours'     => $presence?->getWorkHours(),
                    'check_in_radius_valid' => $presence?->check_in_is_within_radius,
                ];
            });

            return [
                'teacher' => [
                    'id'      => $teacher->id,
                    'name'    => $teacher->name,
                    'nip'     => $teacher->teacher?->nip,
                    'subject' => $teacher->teacher?->subject,
                ],
                'summary'    => $summary,
                'percentage' => $percentage,
                'work_hours' => round($workHours, 2),
                'daily'      => $daily,
            ];
        });

        // Statistik global minggu ini
        $allPresences   = Presence::whereBetween('presence_date', [$startDate, $endDate])->get();
        $globalSummary  = $this->attendanceService->buildSummary($allPresences);

        return [
            'period' => [
                'start'      => $startDate,
                'end'        => $endDate,
                'date_count' => $dateList->count(),
            ],
            'global_summary' => $globalSummary,
            'records'        => $records->values(),
        ];
    }

    // =========================================================================
    // Monthly recap
    // =========================================================================

    /**
     * Rekap bulanan — semua guru untuk bulan/tahun tertentu.
     * Berisi summary per guru + breakdown harian.
     */
    public function getMonthlyRecap(int $month, int $year): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        // Daftar hari kerja (Senin–Jumat) dalam bulan ini
        $workingDays = collect(CarbonPeriod::create($startDate, $endDate))
            ->filter(fn(Carbon $d) => $d->isWeekday())
            ->map(fn(Carbon $d) => $d->toDateString())
            ->values();

        $teachers = User::where('role', User::ROLE_TEACHER)
            ->with(['teacher', 'presences' => function ($q) use ($month, $year) {
                $q->whereYear('presence_date', $year)
                    ->whereMonth('presence_date', $month)
                    ->with('location');
            }])
            ->get();

        $records = $teachers->map(function (User $teacher) use ($month, $year, $workingDays) {
            $presences  = $teacher->presences;
            $summary    = $this->attendanceService->buildSummary($presences);
            $workHours  = $this->attendanceService->calculateTotalWorkHours($presences);
            $percentage = $this->attendanceService->calculatePercentage($presences);

            // Hitung rata-rata jam kerja per hari hadir
            $presentCount = $summary['present'] + $summary['late'];
            $avgWorkHours = $presentCount > 0 ? round($workHours / $presentCount, 2) : 0;

            // Status kelengkapan bukti
            $withPhoto  = $presences->filter(fn(Presence $p) => !empty($p->check_in_photo))->count();
            $radiusOk   = $presences->filter(fn(Presence $p) => $p->check_in_is_within_radius === true)->count();

            return [
                'teacher' => [
                    'id'          => $teacher->id,
                    'name'        => $teacher->name,
                    'email'       => $teacher->email,
                    'nip'         => $teacher->teacher?->nip,
                    'subject'     => $teacher->teacher?->subject,
                    'base_salary' => $teacher->teacher?->base_salary,
                ],
                'summary'           => $summary,
                'percentage'        => $percentage,
                'total_work_hours'  => round($workHours, 2),
                'avg_work_hours'    => $avgWorkHours,
                'working_days_in_month' => $workingDays->count(),
                'evidence' => [
                    'with_photo'        => $withPhoto,
                    'within_radius'     => $radiusOk,
                    'total_records'     => $presences->count(),
                ],
            ];
        });

        // Statistik global bulan ini
        $allPresences  = Presence::whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->get();
        $globalSummary = $this->attendanceService->buildSummary($allPresences);
        $avgAttendance = $this->attendanceService->calculatePercentage($allPresences);

        return [
            'period' => [
                'month'              => $month,
                'year'               => $year,
                'month_name'         => Carbon::createFromDate($year, $month)->isoFormat('MMMM YYYY'),
                'working_days'       => $workingDays->count(),
                'total_teachers'     => $teachers->count(),
            ],
            'global_summary'         => $globalSummary,
            'average_attendance_pct' => $avgAttendance,
            'records'                => $records->values(),
        ];
    }

    // =========================================================================
    // Teacher detail recap (Day 6 — guru lihat rekap sendiri)
    // =========================================================================

    /**
     * Rekap bulanan detail satu guru — breakdown per hari.
     * Dipakai oleh: guru (lihat milik sendiri) dan admin (lihat guru tertentu).
     */
    public function getTeacherMonthlyDetail(User $user, int $month, int $year): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $presences = $user->presences()
            ->with('location')
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->orderBy('presence_date')
            ->get()
            ->keyBy(fn(Presence $p) => $p->presence_date->toDateString());

        // Generate timeline seluruh hari dalam bulan (termasuk hari tanpa record)
        $timeline = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(function (Carbon $day) use ($presences) {
                $dateStr  = $day->toDateString();
                /** @var Presence|null $presence */
                $presence = $presences->get($dateStr);

                return [
                    'date'       => $dateStr,
                    'day_name'   => $day->isoFormat('dddd'),
                    'is_weekend' => $day->isWeekend(),
                    'status'     => $presence?->status ?? ($day->isWeekend() ? 'libur' : 'tidak_hadir'),
                    'check_in' => [
                        'time'             => $presence?->check_in_time?->format('H:i:s'),
                        'late_minutes'     => $presence?->late_minutes ?? 0,
                        'photo_url'        => $presence?->check_in_photo
                            ? asset('storage/' . $presence->check_in_photo) : null,
                        'latitude'         => $presence?->check_in_latitude,
                        'longitude'        => $presence?->check_in_longitude,
                        'distance_meters'  => $presence?->check_in_distance_meters,
                        'is_within_radius' => $presence?->check_in_is_within_radius,
                    ],
                    'check_out' => [
                        'time'             => $presence?->check_out_time?->format('H:i:s'),
                        'photo_url'        => $presence?->check_out_photo
                            ? asset('storage/' . $presence->check_out_photo) : null,
                        'latitude'         => $presence?->check_out_latitude,
                        'longitude'        => $presence?->check_out_longitude,
                        'is_within_radius' => $presence?->check_out_is_within_radius,
                    ],
                    'work_hours'    => $presence?->getWorkHours(),
                    'location_name' => $presence?->location?->name,
                    'notes'         => $presence?->notes,
                ];
            })
            ->values();

        // Ringkasan bulan
        $allPresences = $presences->values();
        $summary      = $this->attendanceService->buildSummary($allPresences);
        $summary['attendance_percentage'] = $this->attendanceService->calculatePercentage($allPresences);
        $summary['total_work_hours']      = round($this->attendanceService->calculateTotalWorkHours($allPresences), 2);
        $summary['total_late_minutes']    = $allPresences->sum('late_minutes');

        return [
            'teacher' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'period' => [
                'month'      => $month,
                'year'       => $year,
                'month_name' => Carbon::createFromDate($year, $month)->isoFormat('MMMM YYYY'),
            ],
            'summary'  => $summary,
            'timeline' => $timeline,
        ];
    }
}
