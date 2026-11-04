<?php

namespace App\Console\Commands;

use App\Models\KitchenInventory;
use App\Models\RecipeCache;
use App\Models\User;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckExpirationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resepify:check-expirations {--user= : ID user spesifik yang ingin dicek}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deteksi otomatis bahan makanan mendekati kedaluwarsa dan picu AI untuk resep penyelamat dapur (Smart Expiration Alert)';

    /**
     * Execute the console command.
     */
    public function handle(GeminiService $geminiService): int
    {
        $this->info("=== [RESEPIFY SMART EXPIRATION SCHEDULER (UNIVERSITAS BSI)] ===");
        $this->info("Menjalankan pemindaian bahan makanan kulkas mendekati kedaluwarsa...");

        $userId = $this->option('user');
        $query = KitchenInventory::whereDate('expiration_date', '>=', Carbon::today())
            ->whereDate('expiration_date', '<=', Carbon::today()->addDays(3));

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $expiringItems = $query->with('user')->get();

        if ($expiringItems->isEmpty()) {
            $this->info("✓ Tidak ada bahan makanan yang mendekati kedaluwarsa hari ini. Semua stok dapur aman!");
            return Command::SUCCESS;
        }

        $this->warn("! Ditemukan {$expiringItems->count()} bahan makanan mendekati kedaluwarsa.");

        // Kelompokkan per user
        $groupedByUser = $expiringItems->groupBy('user_id');

        foreach ($groupedByUser as $uid => $items) {
            $user = User::find($uid);
            $userName = $user ? $user->name : "User #{$uid}";
            $this->line("Memproses rekomendasi resep penyelamat untuk: {$userName} ({$items->count()} bahan)");

            $itemsArray = $items->map(function ($item) {
                return [
                    'name' => $item->item_name,
                    'quantity' => $item->quantity . ' ' . $item->unit,
                    'days_until_expiration' => $item->days_until_expiration
                ];
            })->toArray();

            // Panggil Gemini Rescue Recipe
            $result = $geminiService->rescueExpiringIngredients($itemsArray);

            if ($result['success'] && !empty($result['data'])) {
                $recipe = $result['data'];
                $recipeTitle = $recipe['title'] ?? 'Resep Penyelamat Bahan Makanan Dapur';

                // Simpan ke Cache/Daftar Resep
                RecipeCache::create([
                    'user_id' => $uid,
                    'title' => $recipeTitle,
                    'slug' => Str::slug($recipeTitle) . '-' . Str::random(5),
                    'description' => ($recipe['rescue_rationale'] ?? '') . ' ' . ($recipe['storage_preservation_tips'] ?? ''),
                    'cooking_time_minutes' => (int) ($recipe['cooking_time_minutes'] ?? 20),
                    'difficulty' => $recipe['difficulty'] ?? 'Mudah',
                    'servings' => (int) ($recipe['servings'] ?? 2),
                    'cuisine_type' => $recipe['cuisine_type'] ?? 'Nusantara Praktis',
                    'meal_type' => $recipe['meal_type'] ?? 'Makan Darurat',
                    'ingredient_hash' => md5('rescue_' . $uid . '_' . now()->format('Ymd')),
                    'used_ingredients' => $recipe['used_ingredients'] ?? [],
                    'missing_ingredients' => $recipe['missing_ingredients'] ?? [],
                    'instructions' => $recipe['instructions'] ?? [],
                    'nutrition_facts' => $recipe['nutrition_facts'] ?? null,
                    'chef_tips' => $recipe['storage_preservation_tips'] ?? null,
                    'source' => 'scheduler_rescue_ai',
                    'is_favorite' => false,
                ]);

                $this->info("  ✓ Berhasil membuat resep penyelamat: \"{$recipeTitle}\"");
            }
        }

        $this->info("Pemindaian kedaluwarsa selesai dengan sukses!");
        return Command::SUCCESS;
    }
}

