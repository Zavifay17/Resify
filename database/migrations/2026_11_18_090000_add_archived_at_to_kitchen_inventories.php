<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom penanda arsip: bahan yang lewat kedaluwarsa lebih dari 1 hari
     * disimpan tetap terbaca tetapi tidak dipakai lagi untuk generator resep.
     */
    public function up(): void
    {
        Schema::table('kitchen_inventories', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_inventories', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
