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
            $table->unsignedBigInteger('id_kelas_siswa');
            $table->unsignedInteger('id_guru');
            $table->date('tglpengajuan');
            $table->date('tglpertemuan');
            $table->time('jampertemuan');
            $table->text('deskripsi');
            $table->enum('status', ['menunggu', 'diterima', 'ditolak'])->default('menunggu');
            $table->text('alasan')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('id_kelas_siswa')->references('id_kelas_siswa')->on('kelas_siswa')->onDelete('cascade');
            $table->foreign('id_guru')->references('id_guru')->on('guru')->onDelete('cascade');
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