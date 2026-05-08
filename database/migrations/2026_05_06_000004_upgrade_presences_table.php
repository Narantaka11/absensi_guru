<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade tabel presences — tambah kolom untuk referensi lokasi,
 * device info, dan server timestamp. Additive (tidak memutus data lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            // Referensi ke master lokasi geofence yang dipakai saat absen
            $table->foreignId('location_id')
                ->nullable()
                ->after('user_id')
                ->constrained('locations')
                ->nullOnDelete();

            // Jarak aktual guru ke pusat geofence (meter) — disimpan untuk audit
            $table->unsignedInteger('check_in_distance_meters')
                ->nullable()
                ->after('check_in_longitude');

            $table->unsignedInteger('check_out_distance_meters')
                ->nullable()
                ->after('check_out_longitude');

            // Apakah lokasi dianggap valid (dalam radius geofence)?
            $table->boolean('check_in_is_within_radius')
                ->nullable()
                ->after('check_in_distance_meters');

            $table->boolean('check_out_is_within_radius')
                ->nullable()
                ->after('check_out_distance_meters');

            // Timestamp server-side (bukan jam device) — untuk audit
            $table->timestamp('check_in_server_time')
                ->nullable()
                ->after('check_in_is_within_radius');

            $table->timestamp('check_out_server_time')
                ->nullable()
                ->after('check_out_is_within_radius');

            // Info device untuk audit (model, OS, app version)
            $table->string('check_in_device_info', 255)
                ->nullable()
                ->after('check_in_server_time');

            $table->string('check_out_device_info', 255)
                ->nullable()
                ->after('check_out_server_time');
        });
    }

    public function down(): void
    {
        Schema::table('presences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn([
                'check_in_distance_meters',
                'check_out_distance_meters',
                'check_in_is_within_radius',
                'check_out_is_within_radius',
                'check_in_server_time',
                'check_out_server_time',
                'check_in_device_info',
                'check_out_device_info',
            ]);
        });
    }
};
