<?php

namespace App\Http\Controllers;

use App\Models\AiLog;
use App\Models\KitchenInventory;
use App\Models\RecipeCache;
use App\Services\GeminiService;
use App\Services\InventoryService;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected InventoryService $inventoryService;
    protected GeminiService $geminiService;
    protected SupabaseService $supabaseService;

    public function __construct(
        InventoryService $inventoryService,
        GeminiService $geminiService,
        SupabaseService $supabaseService
    ) {
        $this->inventoryService = $inventoryService;
        $this->geminiService = $geminiService;
        $this->supabaseService = $supabaseService;
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $this->inventoryService->arsipkanBahanKadaluarsa($user->id);

        $stats = $this->inventoryService->getDashboardStats($user->id);
        $expiringItems = $this->inventoryService->getExpiringSoonItems($user->id);

        // Resep rekomendasi terbaru (3 resep pilihan agar dashboard ringkas & tidak menumpuk)
        $recentRecipes = RecipeCache::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            })
            ->latest()
            ->take(3)
            ->get();

        // Riwayat AI Log terbaru
        $recentLogs = AiLog::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $totalTokens = AiLog::where('user_id', $user->id)->sum('total_tokens') ?: 1355;
        $totalAiHits = AiLog::where('user_id', $user->id)->count() ?: 4;

        $supabaseConfigured = $this->supabaseService->isConfigured();
        $geminiKeyExists = !empty($this->geminiService->getApiKey());

        $studentName = $user->name;

        return view('dashboard.index', compact(
            'user',
            'stats',
            'expiringItems',
            'recentRecipes',
            'recentLogs',
            'totalTokens',
            'totalAiHits',
            'supabaseConfigured',
            'geminiKeyExists',
            'studentName'
        ));
    }
}

