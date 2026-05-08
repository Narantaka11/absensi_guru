<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presence extends Model
{
    protected $fillable = [
        'user_id',
        'location_id',
        'presence_date',
        'check_in_time',
        'check_out_time',
        'check_in_photo',
        'check_out_photo',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_distance_meters',
        'check_in_is_within_radius',
        'check_in_server_time',
        'check_in_device_info',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance_meters',
        'check_out_is_within_radius',
        'check_out_server_time',
        'check_out_device_info',
        'status',
        'notes',
        'late_minutes',
    ];

    protected $casts = [
        'presence_date'             => 'date',
        'check_in_time'             => 'datetime:H:i:s',
        'check_out_time'            => 'datetime:H:i:s',
        'check_in_server_time'      => 'datetime',
        'check_out_server_time'     => 'datetime',
        'check_in_is_within_radius' => 'boolean',
        'check_out_is_within_radius'=> 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Cek apakah lokasi check-in valid terhadap sebuah titik geofence.
     * Kalau sudah ada kolom `check_in_is_within_radius`, gunakan itu;
     * kalau tidak, fallback ke kalkulasi on-the-fly dengan radius default.
     */
    public function isWithinSchoolRadius(
        float $schoolLat = -6.2088,
        float $schoolLng = 106.8456,
        float $radiusMeters = 200
    ): bool {
        // Gunakan hasil yang sudah tersimpan dari validasi saat absen
        if (!is_null($this->check_in_is_within_radius)) {
            return $this->check_in_is_within_radius;
        }

        if (!$this->check_in_latitude || !$this->check_in_longitude) {
            return false;
        }

        $distance = $this->calculateDistanceMeters(
            $this->check_in_latitude,
            $this->check_in_longitude,
            $schoolLat,
            $schoolLng
        );

        return $distance <= $radiusMeters;
    }

    /**
     * Hitung jam kerja dari check-in ke check-out (dalam jam).
     */
    public function getWorkHours(): ?float
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }

        $checkIn  = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);

        return round($checkOut->diffInMinutes($checkIn) / 60, 2);
    }

    /**
     * Cek apakah guru terlambat (jam masuk setelah 07:00).
     */
    public function isLate(): bool
    {
        if (!$this->check_in_time) {
            return false;
        }

        $checkIn  = Carbon::parse($this->check_in_time);
        $deadline = Carbon::createFromTimeString('07:00:00');

        return $checkIn->isAfter($deadline);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Haversine formula — mengembalikan jarak dalam meter.
     */
    private function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
