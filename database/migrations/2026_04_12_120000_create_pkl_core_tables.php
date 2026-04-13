<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->increments('id_kelas');
            $table->integer('kelas');
        });

        Schema::create('kajur', function (Blueprint $table) {
            $table->increments('id_kajur');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('nama_kajur', 50);

            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();
        });

        Schema::create('jurusan', function (Blueprint $table) {
            $table->unsignedInteger('id_jurusan')->primary();
            $table->string('nama_jurusan', 50);
            $table->unsignedInteger('id_kajur');

            $table->foreign('id_kajur')->references('id_kajur')->on('kajur');
        });

        Schema::create('tempat_pkl', function (Blueprint $table) {
            $table->increments('id_tempat');
            $table->string('nama_perusahaan', 50);
            $table->string('alamat', 255);
        });

        Schema::create('pembimbing', function (Blueprint $table) {
            $table->increments('id_pembimbing');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('nama_pembimbing', 50);

            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();
        });

        Schema::create('instruktur', function (Blueprint $table) {
            $table->increments('id_instruktur');
            $table->string('nama_instruktur', 50);
            $table->unsignedInteger('id_tempat');

            $table->foreign('id_tempat')->references('id_tempat')->on('tempat_pkl');
        });

        Schema::create('rombel', function (Blueprint $table) {
            $table->increments('id_rombel');
            $table->string('nama_rombel', 50);
            $table->unsignedBigInteger('id_wali');

            $table->foreign('id_wali')->references('id_user')->on('users');
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->increments('nis');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('nama_siswa', 50);
            $table->unsignedInteger('id_kelas');
            $table->unsignedInteger('id_jurusan');
            $table->unsignedInteger('id_rombel');
            $table->string('tahun_ajaran', 50);
            $table->unsignedInteger('id_tempat')->nullable();
            $table->unsignedInteger('id_pembimbing')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas');
            $table->foreign('id_jurusan')->references('id_jurusan')->on('jurusan');
            $table->foreign('id_rombel')->references('id_rombel')->on('rombel');
            $table->foreign('id_tempat')->references('id_tempat')->on('tempat_pkl')->nullOnDelete();
            $table->foreign('id_pembimbing')->references('id_pembimbing')->on('pembimbing')->nullOnDelete();
        });

        Schema::create('absensi', function (Blueprint $table) {
            $table->increments('id_absensi');
            $table->unsignedInteger('id_siswa');
            $table->date('tanggal');
            $table->dateTime('jam_datang');
            $table->dateTime('jam_pulang');
            $table->integer('status');
            $table->string('keterangan', 255)->nullable();
            $table->string('foto_bukti', 255)->nullable();

            $table->foreign('id_siswa')->references('nis')->on('siswa');
        });

        Schema::create('agenda', function (Blueprint $table) {
            $table->increments('id_agenda');
            $table->unsignedInteger('id_siswa');
            $table->date('tanggal');
            $table->string('rencana_pekerjaan', 255)->nullable();
            $table->string('realisasi_pekerjaan', 255)->nullable();
            $table->string('penugasan_khusus_dari_atasan', 255)->nullable();
            $table->string('penemuan_masalah', 255)->nullable();
            $table->string('catatan', 255)->nullable();
            $table->unsignedInteger('id_instruktur')->nullable();
            $table->unsignedInteger('id_pembimbing')->nullable();

            $table->foreign('id_siswa')->references('nis')->on('siswa');
            $table->foreign('id_instruktur')->references('id_instruktur')->on('instruktur')->nullOnDelete();
            $table->foreign('id_pembimbing')->references('id_pembimbing')->on('pembimbing')->nullOnDelete();
        });

        Schema::create('penilaian', function (Blueprint $table) {
            $table->increments('id_penilaian');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_agenda');
            $table->integer('senyum')->nullable();
            $table->integer('keramahan')->nullable();
            $table->integer('penampilan')->nullable();
            $table->integer('komunikasi')->nullable();
            $table->integer('realisasi_kerja')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedInteger('updated_by')->nullable();

            $table->foreign('id_siswa')->references('nis')->on('siswa');
            $table->foreign('id_agenda')->references('id_agenda')->on('agenda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaian');
        Schema::dropIfExists('agenda');
        Schema::dropIfExists('absensi');
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('rombel');
        Schema::dropIfExists('instruktur');
        Schema::dropIfExists('pembimbing');
        Schema::dropIfExists('tempat_pkl');
        Schema::dropIfExists('jurusan');
        Schema::dropIfExists('kajur');
        Schema::dropIfExists('kelas');
    }
};
