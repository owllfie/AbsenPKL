<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name')->nullable();
                $table->string('role_name')->nullable();
                $table->string('module_key', 100)->nullable()->index();
                $table->string('action', 100);
                $table->string('description');
                $table->string('route_name')->nullable()->index();
                $table->string('http_method', 10)->nullable();
                $table->string('path')->nullable();
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('location_label')->nullable();
                $table->string('subject_type')->nullable();
                $table->string('subject_id')->nullable();
                $table->json('properties')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
