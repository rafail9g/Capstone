<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Profil mahasiswa (1 user = maksimal 1 profil mahasiswa)
        Schema::create('mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nim', 30)->unique(); // NIM (12 digit)
            $table->foreignId('program_studi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->unsignedSmallInteger('angkatan'); // contoh: 2024
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswas');
    }
};
