<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_qr_tokens')) {
            Schema::create('attendance_qr_tokens', function (Blueprint $table): void {
                $table->id();
                $table->string('token', 64)->unique();
                $table->string('payload', 120)->unique();
                $table->date('active_on')->index();
                $table->timestamp('expires_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_qr_tokens');
    }
};
