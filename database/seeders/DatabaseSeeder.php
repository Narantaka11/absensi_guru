<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Presence;
use App\Models\Salary;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@sekolah.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        // Create teacher users
        $teachers = [
            ['name' => 'Budi Santoso', 'email' => 'budi@sekolah.com'],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti@sekolah.com'],
            ['name' => 'Ahmad Wijaya', 'email' => 'ahmad@sekolah.com'],
            ['name' => 'Rini Pratiwi', 'email' => 'rini@sekolah.com'],
            ['name' => 'Eka Putra', 'email' => 'eka@sekolah.com'],
        ];

        foreach ($teachers as $teacherData) {
            $teacher = User::create([
                'name' => $teacherData['name'],
                'email' => $teacherData['email'],
                'password' => bcrypt('password'),
                'role' => User::ROLE_TEACHER,
            ]);

            // Create presence records for this month
            $this->createPresences($teacher);

            // Create salary record for this month
            $this->createSalary($teacher);
        }
    }

    /**
     * Create presence records for the month
     */
    private function createPresences(User $teacher): void
    {
        $now = now();
        $month = $now->month;
        $year = $now->year;

        // Get number of working days in the month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);

            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Random presence status
            $rand = rand(1, 100);

            if ($rand > 90) {
                // 10% absent
                $status = 'tidak_hadir';
                $checkInTime = null;
                $checkOutTime = null;
                $lateMinutes = 0;
            } else if ($rand > 75) {
                // 15% sick
                $status = 'sakit';
                $checkInTime = null;
                $checkOutTime = null;
                $lateMinutes = 0;
            } else if ($rand > 15) {
                // 60% present on time
                $status = 'hadir';
                $hour = rand(6, 7);
                $minute = rand(0, 59);
                $checkInTime = Carbon::create(null, null, null, $hour, $minute, 0);
                $checkOutTime = Carbon::create(null, null, null, 16 + rand(0, 1), rand(0, 59), 0);
                $lateMinutes = 0;
            } else {
                // 25% late
                $status = 'terlambat';
                $hour = rand(7, 8);
                $minute = rand(1, 59);
                $checkInTime = Carbon::create(null, null, null, $hour, $minute, 0);
                $checkOutTime = Carbon::create(null, null, null, 16 + rand(0, 1), rand(0, 59), 0);
                // Calculate late minutes
                $expectedTime = Carbon::create(null, null, null, 7, 0, 0);
                $lateMinutes = $checkInTime->diffInMinutes($expectedTime);
            }

            Presence::create([
                'user_id' => $teacher->id,
                'presence_date' => $date,
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'check_in_latitude' => -6.2088 + (rand(-100, 100) / 10000), // Jakarta area
                'check_in_longitude' => 106.8456 + (rand(-100, 100) / 10000),
                'check_out_latitude' => -6.2088 + (rand(-100, 100) / 10000),
                'check_out_longitude' => 106.8456 + (rand(-100, 100) / 10000),
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'notes' => null,
            ]);
        }
    }

    /**
     * Create salary record for the month
     */
    private function createSalary(User $teacher): void
    {
        $now = now();
        $month = $now->month;
        $year = $now->year;

        // Get attendance data
        $presences = Presence::where('user_id', $teacher->id)
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->get();

        $presentDays = $presences->whereIn('status', ['hadir', 'terlambat'])->count();
        $absentDays = $presences->where('status', 'tidak_hadir')->count();
        $totalLateMinutes = $presences->sum('late_minutes');

        $baseSalary = 3000000; // Rp 3 juta base salary

        // Calculate deductions
        $deductionPerAbsentDay = 100000; // Rp 100k per hari absen
        $deductionForAbsence = $absentDays * $deductionPerAbsentDay;

        $deductionPerLateMinute = 1000; // Rp 1k per menit terlambat
        $deductionForLate = $totalLateMinutes * $deductionPerLateMinute;

        $totalSalary = $baseSalary - $deductionForAbsence - $deductionForLate;

        Salary::create([
            'user_id' => $teacher->id,
            'year' => $year,
            'month' => $month,
            'base_salary' => $baseSalary,
            'total_present_days' => $presentDays,
            'total_absent_days' => $absentDays,
            'total_late_minutes' => $totalLateMinutes,
            'deduction_for_absence' => $deductionForAbsence,
            'deduction_for_late' => $deductionForLate,
            'total_salary' => max($totalSalary, 0), // Ensure not negative
            'status' => 'draft',
            'notes' => 'Seeder generated salary data',
        ]);
    }
}

