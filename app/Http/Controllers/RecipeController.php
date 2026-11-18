<?php

namespace App\Http\Controllers;

use App\Models\KitchenInventory;
use App\Models\RecipeCache;
use App\Services\GeminiService;
use App\Services\InventoryService;
use App\Services\RecipeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    protected RecipeService $recipeService;
    protected GeminiService $geminiService;
    protected InventoryService $inventoryService;

    public function __construct(
        RecipeService $recipeService,
        GeminiService $geminiService,
        InventoryService $inventoryService
    ) {
        $this->recipeService = $recipeService;
        $this->geminiService = $geminiService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Halaman Studio Generator Resep AI
     */
    public function generator(Request $request)
    {
        $user = Auth::user();
        $this->inventoryService->arsipkanBahanKadaluarsa($user->id);

        $inventoryItems = KitchenInventory::where('user_id', $user->id)
            ->active()
            ->orderBy('expiration_date', 'asc')
            ->get();

        $jumlahArsip = KitchenInventory::where('user_id', $user->id)->archived()->count();

        $preselected = $request->query('items', []);
        if (is_string($preselected)) {
            $preselected = explode(',', $preselected);
        }

        return view('recipes.generator', compact('inventoryItems', 'user', 'preselected', 'jumlahArsip'));
    }

    /**
     * AJAX Endpoint: Pemicu Generator Resep AI (Dengan Caching Layer)
     */
    public function generate(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'ingredients' => 'required|array|min:1',
            'cuisine_type' => 'nullable|string',
            'meal_type' => 'nullable|string',
            'max_time' => 'nullable|integer',
            'cooking_skill' => 'nullable|string',
            'diet' => 'nullable|array',
        ]);

        $ingredients = $this->saringBahanArsip($user->id, $request->ingredients);

        if ($ingredients === []) {
            return response()->json([
                'success' => false,
                'message' => 'Semua bahan terpilih sudah lewat kedaluwarsa lebih dari 1 hari dan otomatis diarsipkan. Pilih bahan lain atau perbarui tanggalnya di Inventaris Dapur.'
            ], 422);
        }

        $preferences = [
            'cuisine_type' => $request->cuisine_type ?? 'Nusantara / Masakan Rumahan Indonesia',
            'meal_type' => $request->meal_type ?? 'Makan Siang',
            'max_time' => (int) ($request->max_time ?? 30),
            'cooking_skill' => $request->cooking_skill ?? ($user->cooking_skill ?? 'Menengah'),
            'diet' => $request->diet ?? ($user->diet_preferences ?? ['Halal', 'Seimbang']),
        ];

        $result = $this->recipeService->generateOrGetCached($ingredients, $preferences, $user->id);

        return response()->json($result);
    }

    /**
     * Buang bahan yang sudah tidak dimiliki kulkas aktif (terarsip karena kedaluwarsa).
     * Nama yang masih ada pada bahan aktif tetap lolos agar stok ganda tidak ikut terblokir.
     */
    private function saringBahanArsip(int $userId, array $ingredients): array
    {
        $namaAktif = KitchenInventory::where('user_id', $userId)->active()
            ->pluck('item_name')
            ->map(fn($n) => strtolower(trim($n)))
            ->unique();

        $namaArsip = KitchenInventory::where('user_id', $userId)->archived()
            ->pluck('item_name')
            ->map(fn($n) => strtolower(trim($n)))
            ->unique()
            ->diff($namaAktif);

        $tersaring = array_filter($ingredients, function ($bahan) use ($namaArsip) {
            $nama = strtolower(trim(is_array($bahan)
                ? (string) ($bahan['name'] ?? $bahan['item_name'] ?? '')
                : (string) $bahan));

            return !$namaArsip->contains($nama);
        });

        return array_values($tersaring);
    }

    /**
     * Katalog / Perpustakaan Resep Kuliner Indonesia
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = RecipeCache::query();

        // Filter kategori masakan
        if ($request->filled('cuisine') && $request->cuisine !== 'all') {
            if ($request->cuisine === 'tradisional') {
                $query->where('cuisine_type', 'LIKE', '%Tradisional%');
            } elseif ($request->cuisine === 'modern') {
                $query->where('cuisine_type', 'LIKE', '%Modern%');
            } elseif ($request->cuisine === 'street_food') {
                $query->where('cuisine_type', 'LIKE', '%Street Food%');
            } elseif ($request->cuisine === 'rescue') {
                $query->where('source', 'LIKE', '%rescue%');
            }
        }

        // Filter favorit
        if ($request->boolean('favorite')) {
            $query->where('is_favorite', true);
        }

        // Pencarian judul / bahan
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->q . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->q . '%');
            });
        }

        $recipes = $query->latest()->paginate(9)->withQueryString();

        return view('recipes.index', compact('recipes', 'user'));
    }

    /**
     * Halaman Detail Resep Masakan
     */
    public function show($id)
    {
        $user = Auth::user();
        $recipe = RecipeCache::where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        // Ambil stok bahan aktif di kulkas user untuk pencocokan visual checklist
        $userPantryNames = KitchenInventory::where('user_id', $user->id)
            ->active()
            ->pluck('item_name')
            ->map(fn($n) => strtolower(trim($n)))
            ->toArray();

        return view('recipes.show', compact('recipe', 'user', 'userPantryNames'));
    }

    /**
     * Toggle status favorit resep
     */
    public function toggleFavorite($id)
    {
        $recipe = RecipeCache::findOrFail($id);
        $recipe->is_favorite = !$recipe->is_favorite;
        $recipe->save();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_favorite' => $recipe->is_favorite,
                'message' => $recipe->is_favorite ? 'Resep ditambahkan ke Favorit!' : 'Resep dihapus dari Favorit.'
            ]);
        }

        return back()->with('success', $recipe->is_favorite ? 'Resep disimpan ke koleksi favorit.' : 'Resep dihapus dari favorit.');
    }

    /**
     * Fitur 1-Klik: Selamatkan Bahan Makanan Mendekati Kedaluwarsa (Zero Waste AI)
     */
    public function rescueExpiring(Request $request)
    {
        $user = Auth::user();
        $this->inventoryService->arsipkanBahanKadaluarsa($user->id);

        // Bahan yang masih layak pakai dan mendekati kedaluwarsa (<= 3 hari)
        $expiringItems = KitchenInventory::where('user_id', $user->id)
            ->active()
            ->whereDate('expiration_date', '>=', Carbon::today())
            ->whereDate('expiration_date', '<=', Carbon::today()->addDays(3))
            ->get();

        if ($expiringItems->isEmpty()) {
            // Ambil 3 bahan aktif pertama jika tidak ada yang darurat
            $expiringItems = KitchenInventory::where('user_id', $user->id)->active()->take(3)->get();
        }

        if ($expiringItems->isEmpty()) {
            return redirect()->route('inventory.index')
                ->with('info', 'Inventaris dapur Anda masih kosong. Silakan tambahkan bahan terlebih dahulu untuk memanfaatkan fitur resep penyelamat.');
        }

        $itemsList = $expiringItems->map(fn($item) => [
            'name' => $item->item_name,
            'quantity' => $item->quantity . ' ' . $item->unit,
            'days_until_expiration' => $item->days_until_expiration
        ])->toArray();

        $rescueResult = $this->geminiService->rescueExpiringIngredients($itemsList);

        if (!$rescueResult['success'] || empty($rescueResult['data'])) {
            return back()->with('error', 'Gagal menghasilkan resep penyelamat AI.');
        }

        $recipeData = $rescueResult['data'];
        $title = $recipeData['title'] ?? 'Resep Penyelamat Dapur Cerdas Resepify';

        $savedRecipe = RecipeCache::create([
            'user_id' => $user->id,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'description' => ($recipeData['rescue_rationale'] ?? '') . ' ' . ($recipeData['storage_preservation_tips'] ?? ''),
            'cooking_time_minutes' => (int) ($recipeData['cooking_time_minutes'] ?? 20),
            'difficulty' => $recipeData['difficulty'] ?? 'Sangat Mudah',
            'servings' => (int) ($recipeData['servings'] ?? 2),
            'cuisine_type' => 'Nusantara Zero-Waste',
            'meal_type' => 'Penyelamat Bahan Makanan',
            'ingredient_hash' => md5('rescue_' . $user->id . '_' . now()->format('YmdHis')),
            'used_ingredients' => $recipeData['used_ingredients'] ?? [],
            'missing_ingredients' => $recipeData['missing_ingredients'] ?? [],
            'instructions' => $recipeData['instructions'] ?? [],
            'nutrition_facts' => $recipeData['nutrition_facts'] ?? null,
            'chef_tips' => $recipeData['storage_preservation_tips'] ?? null,
            'source' => 'zero_waste_rescue_ai',
            'is_favorite' => true,
        ]);

        return redirect()->route('recipes.show', $savedRecipe->id)
            ->with('success', "🎉 Berhasil menyusun resep penyelamat \"{$savedRecipe->title}\" untuk mencegah bahan makanan terbuang!");
    }
}

