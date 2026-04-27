<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('absensi')) {
            return;
        }

        Schema::table('absensi', function (Blueprint $table): void {
            if (! Schema::hasColumn('absensi', 'ip_address_datang')) {
                $table->string('ip_address_datang', 45)->nullable()->after('jam_datang');
            }

            if (! Schema::hasColumn('absensi', 'ip_address_pulang')) {
                $table->string('ip_address_pulang', 45)->nullable()->after('jam_pulang');
            }

            if (! Schema::hasColumn('absensi', 'lokasi_datang')) {
                $table->string('lokasi_datang')->nullable()->after('ip_address_datang');
            }

            if (! Schema::hasColumn('absensi', 'lokasi_pulang')) {
                $table->string('lokasi_pulang')->nullable()->after('ip_address_pulang');
            }

            if (! Schema::hasColumn('absensi', 'foto_bukti_pulang')) {
                $table->string('foto_bukti_pulang')->nullable()->after('foto_bukti');
            }

            if (! Schema::hasColumn('absensi', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->after('foto_bukti_pulang');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('absensi')) {
            return;
        }

        Schema::table('absensi', function (Blueprint $table): void {
            foreach (['ip_address_datang', 'ip_address_pulang', 'lokasi_datang', 'lokasi_pulang', 'foto_bukti_pulang', 'qr_token'] as $column) {
                if (Schema::hasColumn('absensi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
