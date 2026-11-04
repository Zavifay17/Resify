<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Melengkapi tabel resep dengan kolom asal daerah, foto bahan makanan
     * open-source, serta tag rasa untuk keperluan filter di katalog kuliner.
     */
    public function up(): void
    {
        Schema::table('recipes_cache', function (Blueprint $table) {
            $table->string('region')->nullable()->after('cuisine_type')->index();
            $table->string('image_url', 1000)->nullable()->after('region');
            $table->json('tags')->nullable()->after('chef_tips');
            $table->boolean('is_demo_fallback')->default(false)->after('source');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('region_origin')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('recipes_cache', function (Blueprint $table) {
            $table->dropColumn(['region', 'image_url', 'tags', 'is_demo_fallback']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('region_origin');
        });
    }
};
