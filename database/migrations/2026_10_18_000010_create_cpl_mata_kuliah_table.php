<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpl_mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpl_id')->constrained('cpls')->cascadeOnDelete();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cpl_id', 'mata_kuliah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpl_mata_kuliah');
    }
};
