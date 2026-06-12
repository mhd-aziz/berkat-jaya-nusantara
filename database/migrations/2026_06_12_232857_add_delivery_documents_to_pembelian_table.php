<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pembelian', 'nomor_delivery_order')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->string('nomor_delivery_order', 100)
                    ->nullable()
                    ->unique()
                    ->after('nomor_pembelian');
            });
        }

        if (!Schema::hasColumn('pembelian', 'nomor_surat_jalan')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->string('nomor_surat_jalan', 100)
                    ->nullable()
                    ->unique()
                    ->after('nomor_delivery_order');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembelian', 'nomor_surat_jalan')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->dropUnique(['nomor_surat_jalan']);
                $table->dropColumn('nomor_surat_jalan');
            });
        }

        if (Schema::hasColumn('pembelian', 'nomor_delivery_order')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->dropUnique(['nomor_delivery_order']);
                $table->dropColumn('nomor_delivery_order');
            });
        }
    }
};
