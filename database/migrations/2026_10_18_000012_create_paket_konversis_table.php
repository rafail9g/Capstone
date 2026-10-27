<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_konversis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_paket', 150); // 'UI/UX DESIGN', 'FRONTEND DEVELOPER', 'DATA ANALYST'
            $table->foreignId('program_studi_id')->constrained('program_studis')->cascadeOnDelete();
            $table->string('target_posisi', 100); // Nama posisi magang terkait (misal 'UI/UX Designer')
            $table->unsignedTinyInteger('jumlah_mk')->default(0);
            $table->unsignedSmallInteger('total_sks_pool')->default(0); // Akumulasi total SKS seluruh MK dalam pool (> 20 SKS)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_konversis');
    }
};
