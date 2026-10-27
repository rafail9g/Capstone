<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_pengantar_mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_pengantar_id')->constrained('surat_pengantars')->cascadeOnDelete();
            $table->foreignId('pendaftaran_mbkm_id')->nullable()->constrained('pendaftaran_mbkms')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nim', 30);
            $table->string('nama_lengkap', 150);
            $table->string('program_studi', 100);
            $table->string('posisi_magang', 100);
            $table->timestamps();

            $table->unique(['surat_pengantar_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_pengantar_mahasiswas');
    }
};
