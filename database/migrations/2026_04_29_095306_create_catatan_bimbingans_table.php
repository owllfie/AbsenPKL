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
        Schema::create('catatan_bimbingan', function (Blueprint $table) {
            $table->id('id_catatan');
            $table->integer('id_siswa')->index(); // FK ke siswa.nis
            $table->text('poin_perbaikan');
            $table->text('tindakan_lanjut')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->unsignedInteger('approved_by')->nullable(); // id_pembimbing
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catatan_bimbingan');
    }
};
