<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai akun yang terdaftar/masuk lewat Google: google_id disimpan agar
     * akun Google yang sama selalu dikenali sistem, dan password dibuat nullable
     * karena pengguna Google tidak memiliki kata sandi lokal.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 64)->nullable()->unique()->after('email');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->string('password')->change();
        });
    }
};
