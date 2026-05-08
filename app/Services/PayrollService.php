<?php

namespace App\Services;

use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class PayrollService
{
    /**
     * Potongan per menit keterlambatan (Rp)
     * Bisa dijadikan configurable lewat tabel settings ke depannya.
     */
    public const DEDUCTION_PER_LATE_MINUTE = 1_000;

    // =========================================================================
    // Kalkulasi
    // =========================================================================

    /**
     * Hitung & simpan payroll satu guru untuk bulan tertentu.
     * Jika sudah ada record draft → update (recalculate).
     * Jika status approved/paid → lempar exception (tidak boleh diubah).
     *
     * @throws \Exception jika sudah approved atau paid
     */
    public function calculate(User $user, int $month, int $year): Salary
    {
        // Cek record yang sudah ada
        $existing = Salary::byUser($user->id)->byMonth($month, $year)->first();

        if ($existing && !$existing->isDraft()) {
            throw new \Exception(
                "Gaji bulan ini sudah berstatus '{$existing->status_label}' dan tidak dapat dikalkulasi ulang."
            );
        }

        // Ambil gaji pokok dari profil guru
        $baseSalary = (float) ($user->teacher?->base_salary ?? 0);

        // Ambil semua record absensi bulan ini
        $presences = $user->presences()
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->get();

        // Hitung hari kerja efektif (Senin–Jumat) dalam bulan ini
        $workingDays = $this->countWorkingDays($month, $year);

        // Rekap kehadiran
        $totalPresent   = $presences->whereIn('status', ['hadir', 'terlambat'])->count();
        $totalSick      = $presences->where('status', 'sakit')->count();
        $totalPermission= $presences->where('status', 'izin')->count();
        // Hari absen = hari kerja - hadir - sakit - izin (sisa = tidak_hadir tanpa keterangan)
        $totalAbsent    = max(0, $workingDays - $totalPresent - $totalSick - $totalPermission);
        $totalLateMinutes = (int) $presences->sum('late_minutes');

        // Potongan
        $dailyRate            = $workingDays > 0 ? $baseSalary / $workingDays : 0;
        $deductionForAbsence  = round($dailyRate * $totalAbsent, 2);
        $deductionForLate     = round($totalLateMinutes * self::DEDUCTION_PER_LATE_MINUTE, 2);
        $totalSalary          = max(0, $baseSalary - $deductionForAbsence - $deductionForLate);

        $data = [
            'base_salary'           => $baseSalary,
            'total_present_days'    => $totalPresent,
            'total_absent_days'     => $totalAbsent,
            'total_late_minutes'    => $totalLateMinutes,
            'deduction_for_absence' => $deductionForAbsence,
            'deduction_for_late'    => $deductionForLate,
            'total_salary'          => $totalSalary,
            'status'                => Salary::STATUS_DRAFT,
        ];

        if ($existing) {
            $existing->update($data);
            return $existing->fresh()->load('user.teacher');
        }

        $salary = Salary::create(array_merge($data, [
            'user_id' => $user->id,
            'year'    => $year,
            'month'   => $month,
        ]));

        return $salary->load('user.teacher');
    }

    /**
     * Generate payroll untuk SEMUA guru dalam satu bulan sekaligus.
     * Melewati guru yang sudah approved/paid.
     */
    public function generateAll(int $month, int $year): array
    {
        $teachers = User::where('role', User::ROLE_TEACHER)->with('teacher')->get();

        $results = ['generated' => [], 'skipped' => []];

        foreach ($teachers as $teacher) {
            $existing = Salary::byUser($teacher->id)->byMonth($month, $year)->first();

            if ($existing && !$existing->isDraft()) {
                $results['skipped'][] = [
                    'teacher' => $teacher->name,
                    'reason'  => "Status sudah {$existing->status_label}",
                ];
                continue;
            }

            $salary = $this->calculate($teacher, $month, $year);
            $results['generated'][] = $this->formatSalary($salary);
        }

        return $results;
    }

    // =========================================================================
    // Workflow: approve & paid
    // =========================================================================

    /**
     * Admin menyetujui slip gaji (draft → approved).
     *
     * @throws \Exception
     */
    public function approve(Salary $salary, ?string $notes = null): Salary
    {
        if (!$salary->isDraft()) {
            throw new \Exception("Hanya slip dengan status 'draft' yang bisa disetujui. Status saat ini: {$salary->status_label}.");
        }

        $salary->update([
            'status' => Salary::STATUS_APPROVED,
            'notes'  => $notes ?? $salary->notes,
        ]);

        return $salary->fresh()->load('user.teacher');
    }

    /**
     * Admin menandai slip gaji sebagai sudah dibayar (approved → paid).
     *
     * @throws \Exception
     */
    public function markAsPaid(Salary $salary, ?string $notes = null): Salary
    {
        if (!$salary->isApproved()) {
            throw new \Exception("Hanya slip dengan status 'disetujui' yang bisa ditandai lunas. Status saat ini: {$salary->status_label}.");
        }

        $salary->update([
            'status'  => Salary::STATUS_PAID,
            'paid_at' => now(),
            'notes'   => $notes ?? $salary->notes,
        ]);

        return $salary->fresh()->load('user.teacher');
    }

    /**
     * Batalkan approval — kembalikan ke draft untuk koreksi (approved → draft).
     *
     * @throws \Exception
     */
    public function revertToDraft(Salary $salary): Salary
    {
        if ($salary->isPaid()) {
            throw new \Exception('Gaji yang sudah dibayar tidak dapat dikembalikan ke draft.');
        }

        $salary->update(['status' => Salary::STATUS_DRAFT]);
        return $salary->fresh()->load('user.teacher');
    }

    // =========================================================================
    // Formatting
    // =========================================================================

    /**
     * Format Salary untuk response JSON yang konsisten.
     */
    public function formatSalary(Salary $salary, bool $withTeacher = true): array
    {
        $data = [
            'id'                    => $salary->id,
            'year'                  => $salary->year,
            'month'                 => $salary->month,
            'month_name'            => Carbon::createFromDate($salary->year, $salary->month)->isoFormat('MMMM YYYY'),
            'base_salary'           => (float) $salary->base_salary,
            'total_present_days'    => $salary->total_present_days,
            'total_absent_days'     => $salary->total_absent_days,
            'total_late_minutes'    => $salary->total_late_minutes,
            'deduction_for_absence' => (float) $salary->deduction_for_absence,
            'deduction_for_late'    => (float) $salary->deduction_for_late,
            'total_deduction'       => (float) $salary->total_deduction,
            'total_salary'          => (float) $salary->total_salary,
            'status'                => $salary->status,
            'status_label'          => $salary->status_label,
            'paid_at'               => $salary->paid_at?->toIso8601String(),
            'notes'                 => $salary->notes,
        ];

        if ($withTeacher && $salary->relationLoaded('user')) {
            $data['teacher'] = [
                'id'      => $salary->user->id,
                'name'    => $salary->user->name,
                'email'   => $salary->user->email,
                'nip'     => $salary->user->teacher?->nip,
                'subject' => $salary->user->teacher?->subject,
            ];
        }

        return $data;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Hitung jumlah hari kerja (Senin–Jumat) dalam satu bulan.
     */
    public function countWorkingDays(int $month, int $year): int
    {
        $start = Carbon::createFromDate($year, $month, 1);
        $end   = $start->copy()->endOfMonth();

        return collect(CarbonPeriod::create($start, $end))
            ->filter(fn(Carbon $day) => $day->isWeekday())
            ->count();
    }
}
