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
            $table->dropColumn('id_jurusan');
        });

        Schema::dropIfExists('jurusan');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('jurusan', function (Blueprint $table) {
            $table->increments('id_jurusan');
            $table->string('nama_jurusan');
            $table->unsignedInteger('id_kajur')->nullable();
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->unsignedInteger('id_jurusan')->nullable()->after('nama_siswa');
        });
    }
};
