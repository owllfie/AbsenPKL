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
        Schema::dropIfExists('attendance_qr_tokens');

        Schema::table('absensi', function (Blueprint $table) {
            if (Schema::hasColumn('absensi', 'qr_token')) {
                $table->dropColumn('qr_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('attendance_qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('payload', 120)->unique();
            $table->date('active_on')->index();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamps();
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable();
        });
    }
};
