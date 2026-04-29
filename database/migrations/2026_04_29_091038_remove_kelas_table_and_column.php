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
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('id_kelas');
        });

        Schema::dropIfExists('kelas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->increments('id_kelas');
            $table->integer('kelas');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->unsignedInteger('id_kelas')->nullable()->after('nama_siswa');
        });
    }
};
