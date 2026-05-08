<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel schedules — jadwal mengajar guru per hari.
 * Dipakai untuk validasi jam kerja dan rekap otomatis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week');               // 1=Senin … 7=Minggu (ISO 8601)
            $table->time('start_time');                       // Jam mulai (misal 07:00)
            $table->time('end_time');                         // Jam selesai (misal 15:00)
            $table->string('subject')->nullable();            // Mapel pada jadwal ini
            $table->string('class_name')->nullable();         // Kelas (misal "XII IPA 1")
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
