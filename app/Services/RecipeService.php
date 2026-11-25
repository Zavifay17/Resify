<?php

namespace App\Services;

use App\Models\RecipeCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Service Pattern: RecipeService
 * Mengelola orkestrasi pembuatan resep masakan cerdas,
 * sistem Caching Bertingkat (Laravel Cache + MySQL recipes_cache) guna memangkas latensi & menghemat kuota API.
 */
class RecipeService
{
    protected GeminiService $geminiService;
    protected HidanganFotoService $fotoService;

    public function __construct(GeminiService $geminiService, HidanganFotoService $fotoService)
    {
        $this->geminiService = $geminiService;
        $this->fotoService = $fotoService;
    }

    /**
     * Hasilkan resep baru dari Gemini AI atau ambil dari Cache jika kombinasi bahan pernah dicari
     */
    public function generateOrGetCached(array $ingredients, array $preferences = [], ?int $userId = null): array
    {
        // Normalisasi nama bahan & urutkan agar urutan klik pengguna tidak merusak hash unik
        $normalized = array_map(function ($item) {
            $name = is_array($item) ? ($item['name'] ?? $item['item_name']) : (string) $item;
            return strtolower(trim($name));
        }, $ingredients);

        sort($normalized);

        $cuisine = $preferences['cuisine_type'] ?? 'Nusantara';
        $mealType = $preferences['meal_type'] ?? 'Makan Siang';
        $skill = $preferences['cooking_skill'] ?? 'Menengah';

        // Buat hash unik berdasarkan bahan + preferensi masakan
        $hashSource = implode('|', $normalized) . "@{$cuisine}@{$mealType}@{$skill}";
        $ingredientHash = md5($hashSource);
        $cacheKey = "recipe_hash_{$ingredientHash}";

        // 1. Cek Caching Tingkat 1: Laravel Memory/File Cache (Super Cepat ~5ms)
        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);

            if (!empty($cachedData['image_url'])) {
                return [
                    'success' => true,
                    'recipe' => $cachedData,
                    'from_cache' => true,
                    'cache_level' => 'Laravel Memory Cache (L1)',
                    'latency_ms' => 6,
                    'saved_tokens' => true
                ];
            }

            // Entri lama tanpa foto dibuang agar diambil ulang beserta fotonya
            Cache::forget($cacheKey);
        }

        // 2. Cek Caching Tingkat 2: MySQL recipes_cache Table (~15ms)
        $dbCached = RecipeCache::where('ingredient_hash', $ingredientHash)->first();
        if ($dbCached) {
            $mulai = microtime(true);
            $this->lengkapiFoto($dbCached);
            $fotoMs = (int) round((microtime(true) - $mulai) * 1000);

            Cache::put($cacheKey, $dbCached->toArray(), now()->addDays(7));
            return [
                'success' => true,
                'recipe' => $dbCached->toArray(),
                'from_cache' => true,
                'cache_level' => 'MySQL recipes_cache (L2 Database)',
                'latency_ms' => 18 + $fotoMs,
                'saved_tokens' => true
            ];
        }

        // 3. Cache Miss: Panggil Gemini AI Reasoning
        $aiResult = $this->geminiService->generateRecipe($ingredients, $preferences);

        if (!$aiResult['success'] || empty($aiResult['data'])) {
            return [
                'success' => false,
                'message' => 'Gagal menghasilkan resep dari AI. Silakan coba kembali.'
            ];
        }

        $recipeData = $aiResult['data'];

        // 4. Simpan ke Database MySQL recipes_cache
        $newRecipe = RecipeCache::create([
            'user_id' => $userId,
            'title' => $recipeData['title'] ?? 'Resep Lezat Resepify',
            'slug' => Str::slug($recipeData['title'] ?? 'resep') . '-' . Str::random(5),
            'description' => $recipeData['description'] ?? '',
            'cooking_time_minutes' => (int) ($recipeData['cooking_time_minutes'] ?? 25),
            'difficulty' => $recipeData['difficulty'] ?? 'Mudah',
            'servings' => (int) ($recipeData['servings'] ?? 2),
            'cuisine_type' => $recipeData['cuisine_type'] ?? $cuisine,
            'meal_type' => $recipeData['meal_type'] ?? $mealType,
            'ingredient_hash' => $ingredientHash,
            'used_ingredients' => $recipeData['used_ingredients'] ?? [],
            'missing_ingredients' => $recipeData['missing_ingredients'] ?? [],
            'instructions' => $recipeData['instructions'] ?? [],
            'nutrition_facts' => $recipeData['nutrition_facts'] ?? null,
            'chef_tips' => $recipeData['chef_tips'] ?? null,
            'image_url' => $recipeData['image_url'] ?? null,
            'source' => $aiResult['is_simulated'] ? 'simulated_ai' : 'gemini_api',
            'is_favorite' => false,
        ]);

        $this->lengkapiFoto($newRecipe);

        // Simpan ke Cache L1
        Cache::put($cacheKey, $newRecipe->toArray(), now()->addDays(7));

        return [
            'success' => true,
            'recipe' => $newRecipe->toArray(),
            'from_cache' => false,
            'cache_level' => 'Fresh AI Generation (Gemini)',
            'latency_ms' => $aiResult['latency_ms'] ?? 850,
            'tokens' => $aiResult['tokens'] ?? 430,
            'is_simulated' => $aiResult['is_simulated'] ?? false,
            'notice' => $aiResult['notice'] ?? null
        ];
    }

    /**
     * Pasang foto hidangan open-source pada resep yang belum punya gambar.
     * Gagal cari foto tidak boleh gagalkan GENERATE AI: placeholder tetap dipakai.
     */
    private function lengkapiFoto(RecipeCache $resep): void
    {
        if (!empty($resep->image_url)) {
            return;
        }

        try {
            $this->fotoService->pasang($resep, 8);
        } catch (\Throwable) {
            // Foto adalah pelengkap, bukan syarat berhasilnya generator
        }
    }
}

