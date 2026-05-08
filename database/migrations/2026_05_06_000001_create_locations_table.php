<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel locations — master titik sekolah beserta radius geofence.
 * Bisa ada lebih dari 1 lokasi (misal: gedung utama & lab/olahraga).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // "Gedung Utama SMAN 1 ..."
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8);              // Koordinat pusat geofence
            $table->decimal('longitude', 11, 8);
            $table->unsignedSmallInteger('radius_meters')->default(200); // Radius valid (meter)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
