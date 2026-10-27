<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_pengantars', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat', 100)->unique(); // Contoh: 1245/UN25.1.15/SP/2026
            $table->date('tanggal_surat');
            $table->string('perihal', 200)->default('Permohonan Izin Magang MBKM');
            $table->string('tujuan_instansi', 200);        // Nama PT / Instansi
            $table->string('nama_penerima', 150)->nullable(); // Yth. HR Director / Pimpinan
            $table->text('alamat_instansi');
            $table->string('kota_instansi', 100);
            $table->date('periode_magang_mulai')->nullable();
            $table->date('periode_magang_selesai')->nullable();
            $table->foreignId('pejabat_ttd_id')->nullable()->constrained('users')->nullOnDelete(); // Dekan / Wadek 1
            $table->string('file_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_pengantars');
    }
};
