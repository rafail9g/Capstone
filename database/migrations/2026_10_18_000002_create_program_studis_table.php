<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_studis', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique(); // 'PSTI', 'PSSI', 'PSIF'
            $table->string('nama', 100);          // 'Teknologi Informasi', 'Sistem Informasi', 'Informatika'
            $table->string('cpl_prefix', 10);     // 'PSTI_', 'PSSI_', 'PSIF_'
            $table->string('jenjang', 10)->default('S1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studis');
    }
};
