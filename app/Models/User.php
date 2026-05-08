<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN   = 'admin';
    public const ROLE_TEACHER = 'teacher';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /** Profil tambahan guru (1:1) */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /** Semua record absensi user */
    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    /** Jadwal mengajar */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /** Record gaji */
    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class);
    }

    // -------------------------------------------------------------------------
    // Role helpers
    // -------------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    /** Alias isTeacher() */
    public function isGuru(): bool
    {
        return $this->isTeacher();
    }

    // -------------------------------------------------------------------------
    // Attendance helpers
    // -------------------------------------------------------------------------

    /**
     * Rekap absensi user untuk bulan tertentu.
     */
    public function getAttendanceSummary(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? now()->month;
        $year  = $year  ?? now()->year;

        $presences = $this->presences()
            ->whereYear('presence_date', $year)
            ->whereMonth('presence_date', $month)
            ->get();

        return [
            'present'    => $presences->where('status', 'hadir')->count(),
            'late'       => $presences->where('status', 'terlambat')->count(),
            'absent'     => $presences->where('status', 'tidak_hadir')->count(),
            'sick'       => $presences->where('status', 'sakit')->count(),
            'permission' => $presences->where('status', 'izin')->count(),
            'total'      => $presences->count(),
        ];
    }

    /**
     * Persentase kehadiran user untuk bulan tertentu.
     */
    public function getAttendancePercentage(?int $month = null, ?int $year = null): float
    {
        $summary = $this->getAttendanceSummary($month, $year);

        if ($summary['total'] === 0) {
            return 0;
        }

        return round(($summary['present'] + $summary['late']) / $summary['total'] * 100, 2);
    }
}
