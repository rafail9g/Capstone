<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique(); // 'KSU1701', 'TI2201', 'IF3103', 'SI3205'
            $table->string('nama_mk', 150);
            $table->unsignedTinyInteger('sks');
            $table->enum('lingkup', ['Fakultas', 'Prodi'])->default('Prodi');
            $table->foreignId('program_studi_id')->nullable()->constrained('program_studis')->nullOnDelete(); // Null jika lingkup Fakultas
            $table->text('kompetensi');
            $table->text('job_description_default')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_kuliahs');
    }
};
