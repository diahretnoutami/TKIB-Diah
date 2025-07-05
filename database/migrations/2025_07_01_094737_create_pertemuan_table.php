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
        Schema::create('pertemuan', function (Blueprint $table) {
             $table->id(column: 'id_p');
            $table->string('noinduk');
            $table->unsignedInteger('id_guru');
            $table->unsignedBigInteger('id_ortu');
            $table->date('tglpengajuan');
            $table->date('tglpertemuan');
            $table->time('jampertemuan');
            $table->text('deskripsi');
            $table->enum('status', ['Diproses', 'Diterima', 'Ditolak'])->default('Diproses');
            $table->text('alasan')->nullable();
            $table->timestamps();

            $table->foreign('noinduk')->references('noinduk')->on('siswa')->onDelete('cascade');
            $table->foreign('id_guru')->references('id_guru')->on('guru')->onDelete('cascade');
            $table->foreign('id_ortu')->references('id_ortu')->on('orangtua')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pertemuan');
    }
};