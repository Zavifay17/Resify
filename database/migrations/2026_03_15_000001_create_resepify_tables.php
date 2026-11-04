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
        // Master Bahan Makanan (MySQL)
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->default('Lainnya');
            $table->string('default_unit')->default('gram');
            $table->integer('typical_shelf_life_days')->default(7);
            $table->integer('calories_per_100g')->nullable();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Kitchen Inventories (Hybrid: MySQL + Supabase real-time sync)
        Schema::create('kitchen_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->nullable()->constrained('ingredients')->nullOnDelete();
            $table->string('item_name');
            $table->string('category')->default('Bahan Pokok');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('pcs');
            $table->string('storage_location')->default('Kulkas (Chiller)'); // Kulkas, Freezer, Lemari Kering / Pantry
            $table->date('purchase_date');
            $table->date('expiration_date');
            $table->string('status')->default('safe'); // safe, expiring_soon, expired, used
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('supabase_id')->nullable()->index();
            $table->timestamp('supabase_synced_at')->nullable();
            $table->timestamps();
        });

        // Recipe Cache & User Saved Recipes (MySQL Cache)
        Schema::create('recipes_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->integer('cooking_time_minutes')->default(25);
            $table->string('difficulty')->default('Mudah'); // Mudah, Sedang, Mahir
            $table->integer('servings')->default(2);
            $table->string('cuisine_type')->default('Nusantara');
            $table->string('meal_type')->default('Makan Siang'); // Sarapan, Makan Siang, Makan Malam, Cemilan
            $table->string('ingredient_hash', 64)->index();
            $table->json('used_ingredients');
            $table->json('missing_ingredients')->nullable();
            $table->json('instructions');
            $table->json('nutrition_facts')->nullable();
            $table->text('chef_tips')->nullable();
            $table->string('source')->default('gemini_ai');
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
        });

        // AI Ingredient Substitutions
        Schema::create('recipe_substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipe_id')->nullable()->constrained('recipes_cache')->nullOnDelete();
            $table->string('original_ingredient');
            $table->string('substitute_ingredient');
            $table->string('ratio_adjustment')->default('1:1');
            $table->text('culinary_notes')->nullable();
            $table->timestamps();
        });

        // AI Logs & API Monitoring (Hit Token, Latency, Status)
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type'); // recipe_generation, vision_detection, substitute_suggestion, nutrition_estimation, expiration_rescue
            $table->string('model_used')->default('gemini-2.0-flash');
            $table->text('prompt_summary')->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('response_tokens')->nullable();
            $table->integer('total_tokens')->nullable();
            $table->integer('execution_time_ms')->default(0);
            $table->string('status')->default('success'); // success, error, cached
            $table->text('error_message')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamps();
        });

        // System & API Settings (Gemini, Supabase, BSI Campus Metadata)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('ai_logs');
        Schema::dropIfExists('recipe_substitutions');
        Schema::dropIfExists('recipes_cache');
        Schema::dropIfExists('kitchen_inventories');
        Schema::dropIfExists('ingredients');
    }
};

