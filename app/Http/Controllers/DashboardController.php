<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Presence;
use App\Models\Salary;
use Illuminate\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Admin Dashboard - Rekap Absensi
     */
    public function index(Request $request): View
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Ambil user dengan role teacher saja agar konsisten dengan enum role.
        $teachers = User::where('role', User::ROLE_TEACHER)->get();

        // Calculate attendance summary for each teacher
        $attendanceSummary = $teachers->map(function ($teacher) use ($month, $year) {
            $presences = $teacher->presences()
                ->whereYear('presence_date', $year)
                ->whereMonth('presence_date', $month)
                ->get();

            $present = $presences->where('status', 'hadir')->count();
            $late = $presences->where('status', 'terlambat')->count();
            $absent = $presences->where('status', 'tidak_hadir')->count();
            $sick = $presences->where('status', 'sakit')->count();
            $permission = $presences->where('status', 'izin')->count();
            $total = $presences->count();

            $percentage = $total > 0 ? round(($present + $late) / $total * 100, 2) : 0;

            return [
                'teacher' => $teacher,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'sick' => $sick,
                'permission' => $permission,
                'total' => $total,
                'percentage' => $percentage,
            ];
        });

        // Overall statistics
        $totalPresences = Presence::whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->count();

        $totalPresent = Presence::whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->where('status', 'hadir')
            ->count();

        $totalLate = Presence::whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->where('status', 'terlambat')
            ->count();

        $totalAbsent = Presence::whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->where('status', 'tidak_hadir')
            ->count();

        $statistics = [
            'total' => $totalPresences,
            'present' => $totalPresent,
            'late' => $totalLate,
            'absent' => $totalAbsent,
            'average_attendance' => $totalPresences > 0 ? round(($totalPresent + $totalLate) / $totalPresences * 100, 2) : 0,
        ];

        return view('admin.dashboard', [
            'attendanceSummary' => $attendanceSummary,
            'statistics' => $statistics,
            'currentMonth' => $month,
            'currentYear' => $year,
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ],
        ]);
    }

    /**
     * Detail absensi guru tertentu
     */
    public function teacherDetail(User $user, Request $request): View
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Get presences for the month
        $presences = $user->presences()
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->orderBy('presence_date', 'desc')
            ->paginate(20);

        // Calculate summary
        $allPresences = $user->presences()
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->get();

        $summary = [
            'present' => $allPresences->where('status', 'hadir')->count(),
            'late' => $allPresences->where('status', 'terlambat')->count(),
            'absent' => $allPresences->where('status', 'tidak_hadir')->count(),
            'sick' => $allPresences->where('status', 'sakit')->count(),
            'permission' => $allPresences->where('status', 'izin')->count(),
        ];

        // Get salary data if exists
        $salary = Salary::byUser($user->id)
            ->byMonth($month, $year)
            ->first();

        return view('admin.teacher-detail', [
            'user' => $user,
            'presences' => $presences,
            'summary' => $summary,
            'salary' => $salary,
            'currentMonth' => $month,
            'currentYear' => $year,
        ]);
    }

    /**
     * Detail single presence record
     */
    public function presenceDetail(Presence $presence): View
    {
        return view('admin.presence-detail', [
            'presence' => $presence,
            'isWithinRadius' => $presence->isWithinSchoolRadius(),
            'workHours' => $presence->getWorkHours(),
        ]);
    }

    /**
     * Salary/Payroll Page
     */
    public function salary(Request $request): View
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $salaries = Salary::byMonth($month, $year)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.salary', [
            'salaries' => $salaries,
            'currentMonth' => $month,
            'currentYear' => $year,
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ],
        ]);
    }
}
