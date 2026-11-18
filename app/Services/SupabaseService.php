<?php

namespace App\Services;

use App\Models\KitchenInventory;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service Pattern: SupabaseService
 * Menangani integrasi Cloud Database & Supabase Storage (REST PostgREST & Object Storage)
 * Pendekatan Hybrid Database: Sinkronisasi Cloud Real-time & MySQL Lokal
 */
class SupabaseService
{
    protected string $url;
    protected string $key;
    protected string $serviceKey;
    protected string $bucket;

    public function __construct()
    {
        $this->url = rtrim(SystemSetting::get('supabase_url', config('services.supabase.url', env('SUPABASE_URL', ''))), '/');
        $this->key = SystemSetting::get('supabase_key', config('services.supabase.key', env('SUPABASE_KEY', '')));
        $this->serviceKey = (string) (SystemSetting::get('supabase_service_key', config('services.supabase.service_key', env('SUPABASE_SERVICE_KEY', ''))));
        $this->bucket = SystemSetting::get('supabase_bucket', config('services.supabase.bucket', env('SUPABASE_BUCKET', 'ingredients')));
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Menulis data cloud memakai service key (hanya di server). Key publik
     * tidak akan tembus Row Level Security untuk insert/update.
     */
    protected function keyUntukTulis(): string
    {
        return $this->serviceKey !== '' ? $this->serviceKey : $this->key;
    }

    public function isConfigured(): bool
    {
        return !empty($this->url) && !empty($this->key);
    }

    /**
     * Uji Koneksi REST API ke Supabase Cloud
     */
    public function testConnection(?string $customUrl = null, ?string $customKey = null): array
    {
        $url = rtrim($customUrl ?: $this->url, '/');
        $key = $customKey ?: $this->key;

        if (empty($url) || empty($key)) {
            return [
                'success' => false,
                'message' => 'URL atau API Key Supabase belum diisi. Silakan lengkapi di pengaturan.'
            ];
        }

        $startTime = microtime(true);
        try {
            $tabel = Http::timeout(10)
                ->withHeaders([
                    'apikey' => $key,
                    'Authorization' => 'Bearer ' . $key,
                ])
                ->get("{$url}/rest/v1/kitchen_inventories?select=id&limit=1");

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if ($tabel->status() === 404) {
                return [
                    'success' => false,
                    'message' => 'Terhubung ke Supabase, tapi tabel kitchen_inventories belum ada. Jalankan berkas supabase/sql/setup.sql di SQL Editor project Supabase Anda.',
                    'url' => $url,
                    'latency_ms' => $latency,
                    'status_code' => $tabel->status()
                ];
            }

            if ($tabel->successful()) {
                // Bucket hanya bisa dilihat memakai kunci service role; anon key
                // selalu dibalas "Bucket not found" walau bucket-nya tersedia.
                $probeKey = $this->keyUntukTulis();
                $bucket = Http::timeout(10)
                    ->withHeaders([
                        'apikey' => $probeKey,
                        'Authorization' => 'Bearer ' . $probeKey,
                    ])
                    ->get("{$url}/storage/v1/bucket/{$this->bucket}");

                if ($bucket->successful()) {
                    $pesan = 'Supabase Cloud terhubung: tabel inventaris dan bucket foto siap dipakai.';
                } elseif ($probeKey === $this->key) {
                    $pesan = 'Tabel inventaris siap. Bucket foto belum dapat dipastikan karena Service Key belum diisi; '
                        . 'lengkapi SUPABASE_SERVICE_KEY di pengaturan bila unggah foto gagal.';
                } else {
                    $pesan = 'Tabel inventaris siap, tapi bucket ' . $this->bucket . ' belum ada. Jalankan supabase/sql/setup.sql.';
                }

                return [
                    'success' => true,
                    'message' => $pesan,
                    'url' => $url,
                    'latency_ms' => $latency,
                    'status_code' => $tabel->status(),
                    'bucket_siap' => $bucket->successful(),
                    'jumlah_baris_terlihat' => count($tabel->json() ?? [])
                ];
            }

            return [
                'success' => false,
                'message' => 'Respon Supabase HTTP ' . $tabel->status() . ': ' . $tabel->body()
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke Supabase: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sinkronisasi SATU item inventaris ke tabel Supabase Cloud
     */
    public function syncInventoryItem(KitchenInventory $item): array
    {
        if (!$this->isConfigured()) {
            return [
                'synced' => false,
                'message' => 'Supabase belum dikonfigurasi. Menggunakan penyimpanan MySQL lokal.'
            ];
        }

        try {
            $key = $this->keyUntukTulis();

            // id baris dipakai id MySQL lokal supaya sinkronisasi ulang
            // memperbarui baris yang sama (lihat supabase/sql/setup.sql).
            $payload = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'ingredient_id' => $item->ingredient_id,
                'item_name' => $item->item_name,
                'category' => $item->category,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'storage_location' => $item->storage_location,
                'purchase_date' => $item->purchase_date?->format('Y-m-d'),
                'expiration_date' => $item->expiration_date?->format('Y-m-d'),
                'status' => $item->status,
                'notes' => $item->notes,
                'photo_path' => $item->photo_path,
                'synced_at' => now()->toIso8601String()
            ];

            $response = Http::timeout(10)
                ->withHeaders([
                    'apikey' => $key,
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation,resolution=merge-duplicates'
                ])
                ->post("{$this->url}/rest/v1/kitchen_inventories", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $supabaseId = $data[0]['id'] ?? $item->id;

                $item->updateQuietly([
                    'supabase_id' => (string) $supabaseId,
                    'supabase_synced_at' => now(),
                ]);

                return [
                    'synced' => true,
                    'supabase_id' => $supabaseId,
                    'message' => 'Berhasil disinkronisasi ke Supabase Cloud!'
                ];
            }

            Log::warning('Supabase sync status: ' . $response->status() . ' - ' . $response->body());
            return [
                'synced' => false,
                'message' => 'Gagal sync ke tabel Supabase (HTTP ' . $response->status() . ')'
            ];
        } catch (\Throwable $e) {
            Log::warning('Supabase sync exception: ' . $e->getMessage());
            return [
                'synced' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload Foto Bahan ke Supabase Storage Bucket
     */
    public function uploadPhotoToStorage(string $filePath, string $filename, string $mimeType = 'image/jpeg'): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'url' => asset('storage/' . $filename),
                'message' => 'Supabase Storage belum aktif, tersimpan di storage lokal.'
            ];
        }

        try {
            $content = file_get_contents($filePath);
            $cleanName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

            $response = Http::timeout(20)
                ->withHeaders([
                    'apikey' => $this->keyUntukTulis(),
                    'Authorization' => 'Bearer ' . $this->keyUntukTulis(),
                    'Content-Type' => $mimeType,
                ])
                ->withBody($content, $mimeType)
                ->post("{$this->url}/storage/v1/object/{$this->bucket}/{$cleanName}");

            if ($response->successful()) {
                $publicUrl = "{$this->url}/storage/v1/object/public/{$this->bucket}/{$cleanName}";
                return [
                    'success' => true,
                    'url' => $publicUrl,
                    'path' => $cleanName,
                    'message' => 'Foto berhasil diunggah ke Supabase Cloud Storage!'
                ];
            }

            return [
                'success' => false,
                'url' => asset('storage/' . $filename),
                'message' => 'Respon Supabase Storage: ' . $response->body()
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'url' => asset('storage/' . $filename),
                'message' => 'Exception upload Supabase: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sinkronisasi Batch Semua Item Milik User ke Supabase
     */
    public function syncAllUserItems(int $userId): array
    {
        $items = KitchenInventory::where('user_id', $userId)->get();
        $syncedCount = 0;

        foreach ($items as $item) {
            $res = $this->syncInventoryItem($item);
            if ($res['synced'] ?? false) {
                $syncedCount++;
            }
        }

        return [
            'total' => $items->count(),
            'synced' => $syncedCount,
            'configured' => $this->isConfigured()
        ];
    }
}

