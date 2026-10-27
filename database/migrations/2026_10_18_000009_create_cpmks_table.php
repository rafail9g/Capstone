<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpmks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->foreignId('cpl_id')->constrained('cpls')->cascadeOnDelete();
            $table->string('kode', 20); // 'CPMK-1', 'CPMK-2'
            $table->text('deskripsi');
            $table->timestamps();

            $table->unique(['mata_kuliah_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpmks');
    }
};
