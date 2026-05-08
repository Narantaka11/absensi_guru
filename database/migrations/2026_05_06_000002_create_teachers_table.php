<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel teachers — profil tambahan guru (1:1 dengan users).
 * Memisahkan data domain guru dari tabel users yang lebih umum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nip')->nullable()->unique();      // Nomor Induk Pegawai
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('subject')->nullable();            // Mata pelajaran yang diampu
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->foreignId('location_id')                 // Lokasi sekolah default guru
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
