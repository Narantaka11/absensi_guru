<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    public const STATUS_DRAFT    = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID     = 'paid';

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'base_salary',
        'total_present_days',
        'total_absent_days',
        'total_late_minutes',
        'deduction_for_absence',
        'deduction_for_late',
        'total_salary',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'paid_at'               => 'datetime',
        'base_salary'           => 'decimal:2',
        'deduction_for_absence' => 'decimal:2',
        'deduction_for_late'    => 'decimal:2',
        'total_salary'          => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isDraft(): bool    { return $this->status === self::STATUS_DRAFT; }
    public function isApproved(): bool { return $this->status === self::STATUS_APPROVED; }
    public function isPaid(): bool     { return $this->status === self::STATUS_PAID; }

    /** Label status dalam Bahasa Indonesia */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT    => 'Draft',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_PAID     => 'Sudah Dibayar',
            default               => 'Tidak Diketahui',
        };
    }

    /** Total potongan */
    public function getTotalDeductionAttribute(): float
    {
        return (float) $this->deduction_for_absence + (float) $this->deduction_for_late;
    }
}
