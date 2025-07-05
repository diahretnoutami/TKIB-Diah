<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penilaian_mingguan', function (Blueprint $table) {
            $table->id('id_pm');
            $table->unsignedBigInteger('id_a');
            $table->string('minggu');
            $table->timestamps();

            $table->foreign('id_a')->references(columns: 'id_a')->on('alur')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_mingguan');
    }
};