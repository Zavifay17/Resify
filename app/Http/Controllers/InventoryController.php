<?php

namespace App\Http\Controllers;

use App\Jobs\SyncInventoryToSupabaseJob;
use App\Models\Ingredient;
use App\Models\KitchenInventory;
use App\Services\InventoryService;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;
    protected SupabaseService $supabaseService;

    public function __construct(InventoryService $inventoryService, SupabaseService $supabaseService)
    {
        $this->inventoryService = $inventoryService;
        $this->supabaseService = $supabaseService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = KitchenInventory::where('user_id', $user->id);

        // Search
        if ($request->filled('q')) {
            $query->where('item_name', 'LIKE', '%' . $request->q . '%');
        }

        // Kategori filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Lokasi simpan filter
        if ($request->filled('location') && $request->location !== 'all') {
            $query->where('storage_location', $request->location);
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'expiring_soon') {
                $query->whereDate('expiration_date', '>=', Carbon::today())
                      ->whereDate('expiration_date', '<=', Carbon::today()->addDays(3));
            } elseif ($request->status === 'expired') {
                $query->whereDate('expiration_date', '<', Carbon::today());
            } elseif ($request->status === 'safe') {
                $query->whereDate('expiration_date', '>', Carbon::today()->addDays(3));
            }
        }

        $items = $query->orderBy('expiration_date', 'asc')->paginate(12)->withQueryString();
        $masterIngredients = Ingredient::orderBy('name', 'asc')->get();
        $stats = $this->inventoryService->getDashboardStats($user->id);

        return view('inventory.index', compact('items', 'masterIngredients', 'stats'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|string',
            'quantity' => 'required|numeric|min:0.1',
            'unit' => 'required|string|max:50',
            'storage_location' => 'required|string',
            'purchase_date' => 'required|date',
            'expiration_date' => 'required|date|after_or_equal:purchase_date',
            'notes' => 'nullable|string|max:500',
            'ingredient_id' => 'nullable|exists:ingredients,id',
        ]);

        $item = KitchenInventory::create([
            'user_id' => $user->id,
            'ingredient_id' => $validated['ingredient_id'] ?? null,
            'item_name' => $validated['item_name'],
            'category' => $validated['category'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'storage_location' => $validated['storage_location'],
            'purchase_date' => $validated['purchase_date'],
            'expiration_date' => $validated['expiration_date'],
            'status' => 'safe',
            'notes' => $validated['notes'],
        ]);

        // Sinkronisasi cloud dikerjakan lewat antrean agar halaman tidak menunggu Supabase
        if ($this->supabaseService->isConfigured()) {
            SyncInventoryToSupabaseJob::dispatch($item);
        }

        return redirect()->route('inventory.index')->with('success', "Bahan \"{$item->item_name}\" berhasil ditambahkan ke inventaris dapur.");
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $item = KitchenInventory::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|string',
            'quantity' => 'required|numeric|min:0.1',
            'unit' => 'required|string|max:50',
            'storage_location' => 'required|string',
            'purchase_date' => 'required|date',
            'expiration_date' => 'required|date|after_or_equal:purchase_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $item->update($validated);

        if ($this->supabaseService->isConfigured()) {
            SyncInventoryToSupabaseJob::dispatch($item);
        }

        return redirect()->route('inventory.index')->with('success', "Bahan \"{$item->item_name}\" berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $item = KitchenInventory::where('user_id', $user->id)->findOrFail($id);
        $name = $item->item_name;
        $item->delete();

        return redirect()->route('inventory.index')->with('success', "Bahan \"{$name}\" telah dihapus dari inventaris.");
    }

    public function syncSupabase()
    {
        $user = Auth::user();
        $result = $this->supabaseService->syncAllUserItems($user->id);

        if (!$result['configured']) {
            return back()->with('warning', 'Supabase Cloud API belum dikonfigurasi. Anda dapat melengkapinya di Pengaturan API untuk sinkronisasi cloud real-time.');
        }

        return back()->with('success', "Sinkronisasi Supabase selesai: {$result['synced']} dari {$result['total']} bahan berhasil disinkronkan ke Cloud.");
    }

    public function printReport()
    {
        $user = Auth::user();
        $items = KitchenInventory::where('user_id', $user->id)->orderBy('expiration_date', 'asc')->get();
        $stats = $this->inventoryService->getDashboardStats($user->id);

        return view('inventory.print', compact('items', 'stats', 'user'));
    }
}

