<?php

namespace App\Services;

use App\Models\KitchenInventory;
use App\Models\Ingredient;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service Pattern: InventoryService
 * Mengelola kalkulasi inventaris dapur, masa simpan, dan statistik analitik
 */
class InventoryService
{
    /**
     * Toleransi hari setelah tanggal kedaluwarsa. Lewat batas ini bahan
     * diarsipkan: tetap terlihat di daftar tapi tidak bisa dipakai resep lagi.
     */
    public const TOLERANSI_HARI_KADALUARSA = 1;

    protected SupabaseService $supabaseService;

    public function __construct(SupabaseService $supabaseService)
    {
        $this->supabaseService = $supabaseService;
    }

    /**
     * Batas tanggal: bahan dengan expiration_date sebelum tanggal ini dianggap basi
     */
    public static function tanggalBatasArsip(): Carbon
    {
        return Carbon::today()->subDays(self::TOLERANSI_HARI_KADALUARSA);
    }

    /**
     * Arsipkan otomatis semua bahan yang sudah lewat kedaluwarsa lebih dari toleransi.
     * Tidak menghapus baris, hanya menandai archived_at supaya riwayat tetap terbaca.
     */
    public function arsipkanBahanKadaluarsa(?int $userId = null): int
    {
        $query = KitchenInventory::whereNull('archived_at')
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', static::tanggalBatasArsip());

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->update(['archived_at' => now()]);
    }

    /**
     * Dapatkan ringkasan statistik inventaris pengguna
     */
    public function getDashboardStats(int $userId): array
    {
        $items = KitchenInventory::where('user_id', $userId)->get();

        $safeCount = 0;
        $expiringCount = 0;
        $expiredCount = 0;

        foreach ($items as $item) {
            $status = $item->computed_status;
            if ($status === 'expired') {
                $expiredCount++;
            } elseif ($status === 'expiring_soon') {
                $expiringCount++;
            } else {
                $safeCount++;
            }
        }

        // Kategori breakdown
        $categories = $items->groupBy('category')->map->count();

        // Lokasi penyimpanan breakdown
        $locations = $items->groupBy('storage_location')->map->count();

        return [
            'total_items' => $items->count(),
            'safe_count' => $safeCount,
            'expiring_count' => $expiringCount,
            'expired_count' => $expiredCount,
            'archived_count' => $items->whereNotNull('archived_at')->count(),
            'categories' => $categories,
            'locations' => $locations,
            'supabase_synced_count' => $items->whereNotNull('supabase_id')->count()
        ];
    }

    /**
     * Dapatkan daftar bahan makanan yang mendekati kedaluwarsa (<= 3 hari)
     */
    public function getExpiringSoonItems(int $userId): Collection
    {
        return KitchenInventory::where('user_id', $userId)
            ->active()
            ->whereDate('expiration_date', '>=', Carbon::today())
            ->whereDate('expiration_date', '<=', Carbon::today()->addDays(3))
            ->orderBy('expiration_date', 'asc')
            ->get();
    }

    /**
     * Simpan batch hasil deteksi Computer Vision AI ke dalam inventaris
     */
    public function importVisionBatch(int $userId, array $detectedItems): int
    {
        $count = 0;
        $today = Carbon::today();

        foreach ($detectedItems as $item) {
            if (empty($item['item_name'])) continue;

            $shelfDays = (int) ($item['typical_shelf_life_days'] ?? 7);
            $expDate = $today->copy()->addDays(max(1, $shelfDays));

            // Coba cocokkan dengan Master Ingredient jika ada
            $master = Ingredient::where('name', 'LIKE', '%' . $item['item_name'] . '%')->first();

            $inventory = KitchenInventory::create([
                'user_id' => $userId,
                'ingredient_id' => $master?->id,
                'item_name' => $item['item_name'],
                'category' => $item['category'] ?? 'Bahan Pokok',
                'quantity' => (float) ($item['estimated_quantity'] ?? 1),
                'unit' => $item['unit'] ?? 'pcs',
                'storage_location' => $item['storage_location'] ?? 'Kulkas (Chiller)',
                'purchase_date' => $today->format('Y-m-d'),
                'expiration_date' => $expDate->format('Y-m-d'),
                'status' => 'safe',
                'notes' => $item['freshness_notes'] ?? 'Terdeteksi otomatis via AI Computer Vision Resepify',
            ]);

            // Sync ke Supabase di background jika aktif
            if ($this->supabaseService->isConfigured()) {
                $this->supabaseService->syncInventoryItem($inventory);
            }

            $count++;
        }

        return $count;
    }
}

