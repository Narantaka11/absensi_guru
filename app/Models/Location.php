<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'radius_meters'  => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Hitung jarak (meter) antara koordinat input ke pusat geofence ini.
     * Menggunakan formula Haversine.
     */
    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($this->latitude - $lat);
        $dLng = deg2rad($this->longitude - $lng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat)) * cos(deg2rad($this->latitude)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Apakah koordinat input berada dalam radius geofence?
     */
    public function isWithinRadius(float $lat, float $lng): bool
    {
        return $this->distanceTo($lat, $lng) <= $this->radius_meters;
    }

    /**
     * Scope — hanya lokasi yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
