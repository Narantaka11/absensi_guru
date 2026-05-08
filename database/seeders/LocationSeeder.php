<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Sesuaikan koordinat & radius dengan lokasi sekolah yang sebenarnya
        Location::firstOrCreate(
            ['name' => 'Gedung Utama Sekolah'],
            [
                'address'       => 'Jl. Sekolah No. 1, Jakarta',
                'latitude'      => -6.2088,   // ← ganti dengan koordinat GPS sekolah
                'longitude'     => 106.8456,  // ← ganti dengan koordinat GPS sekolah
                'radius_meters' => 200,        // radius valid 200 meter
                'is_active'     => true,
            ]
        );
    }
}
