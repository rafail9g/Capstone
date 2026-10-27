<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rkms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_mbkm_id')->unique()->constrained('pendaftaran_mbkms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Mahasiswa
            $table->foreignId('paket_konversi_id')->nullable()->constrained('paket_konversis')->nullOnDelete();
            $table->unsignedTinyInteger('total_sks')->default(0); // Validasi: Maksimal 20 SKS aktif

            // Status Verifikasi RKM
            $table->enum('status', ['Belum Mengajukan', 'Diajukan', 'Disetujui', 'Perbaikan RKM'])
                ->default('Belum Mengajukan');
            $table->text('catatan_pic')->nullable(); // Catatan perbaikan dari PIC Verifikator
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); // PIC Verifikator
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkms');
    }
};
