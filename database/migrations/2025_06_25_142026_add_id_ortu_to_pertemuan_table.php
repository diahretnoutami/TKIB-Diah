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
        Schema::table('pertemuan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_ortu')->nullable()->after('id_guru');
            $table->foreign('id_ortu')->references('id_ortu')->on('orangtua')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pertemuan', function (Blueprint $table) {
            $table->dropForeign(['id_ortu']);
            $table->dropColumn('id_ortu');
        });
    }
};