<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_konversi_mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_konversi_id')->constrained('paket_konversis')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(['paket_konversi_id', 'mata_kuliah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paket_konversi_mata_kuliah');
    }
};
