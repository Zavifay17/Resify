<?php

namespace App\Services;

use App\Models\KitchenInventory;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

            // Cloud dengan skema ERD memakai foreign key: baris induk dikirim
            // lebih dulu supaya insert item ini tidak ditolak.
            $this->pastikanIndukInventaris($item);

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

    // ==================== Skema Cloud & ERD ====================

    /**
     * Tabel cloud yang membentuk ERD Resepify (dibuat oleh supabase/sql/setup.sql).
     */
    public const TABEL_CLOUD = [
        'users',
        'ingredients',
        'ingredient_requests',
        'kitchen_inventories',
        'recipes_cache',
        'recipes',
        'recipe_substitutions',
        'ai_logs',
        'system_settings',
    ];

    /** Kunci SystemSetting yang tidak boleh ikut terkirim ke cloud. */
    protected const KUNCI_RAHASIA = [
        'gemini_api_key', 'supabase_key', 'supabase_service_key', 'supabase_access_token', 'app_key',
        'google_client_id', 'google_client_secret',
    ];

    /** Kolom yang di MySQL tersimpan sebagai teks JSON, di cloud sebagai jsonb. */
    protected const KOLOM_JSON = [
        'diet_preferences', 'detected_ingredients', 'used_ingredients', 'missing_ingredients',
        'instructions', 'nutrition_facts', 'tags', 'ingredient_needed', 'substitute_suggestions',
    ];

    /** Kolom tinyint(1) yang di Postgres harus boolean. */
    protected const KOLOM_BOOL = ['is_demo_fallback', 'is_favorite', 'is_saved'];

    /** Pengecekan keberadaan tabel cukup sekali per request. */
    protected static array $cacheTabelAda = [];

    /** Baris induk (user/bahan) yang sudah dipastikan ada di cloud. */
    protected static array $cacheIndukSiap = [];

    /** Lupakan hasil pengecekan (dipakai pengujian, agar tiap tes bersih). */
    public static function kosongkanCacheStatus(): void
    {
        static::$cacheTabelAda = [];
        static::$cacheIndukSiap = [];
    }

    public function projectRef(): string
    {
        if (preg_match('#https://([a-z0-9_-]+)\.supabase\.co#i', $this->url, $m)) {
            return $m[1];
        }

        return '';
    }

    /**
     * Token personal (Supabase CLI -> Access Tokens) dipakai untuk menjalankan
     * SQL lewat API kelolaan. Hanya dibaca dari .env/config, tidak pernah
     * disimpan ke SystemSetting supaya tidak ikut tersinkronisasi.
     */
    public function tokenKelola(): string
    {
        return (string) (config('services.supabase.dashboard_token', env('SUPABASE_ACCESS_TOKEN', '')));
    }

    public function punyaTokenKelola(): bool
    {
        return $this->tokenKelola() !== '';
    }

    /**
     * Jalankan satu skrip SQL di project Supabase memakai API kelolaan.
     */
    public function jalankanSql(string $sql): array
    {
        $ref = $this->projectRef();

        if ($ref === '') {
            return ['success' => false, 'message' => 'Project ref Supabase tidak dikenali dari URL. Format URL harus https://<ref>.supabase.co.'];
        }

        if (!$this->punyaTokenKelola()) {
            return [
                'success' => false,
                'perlu_token' => true,
                'message' => 'SUPABASE_ACCESS_TOKEN belum diisi di berkas .env, jadi skrip tidak bisa dijalankan otomatis. '
                    . 'Salin script SQL Setup lalu jalankan sendiri di SQL Editor Supabase.',
            ];
        }

        try {
            $respon = Http::timeout(90)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->tokenKelola(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("https://api.supabase.com/v1/projects/{$ref}/database/query", ['query' => $sql]);

            if ($respon->successful()) {
                return [
                    'success' => true,
                    'message' => 'Skrip SQL berhasil dijalankan di project ' . $ref . '.',
                    'hasil' => $respon->json(),
                    'status_code' => $respon->status(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Supabase menolak skrip (HTTP ' . $respon->status() . '): ' . Str::limit($respon->body(), 400),
                'status_code' => $respon->status(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Supabase SQL exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menghubungi API kelolaan Supabase: ' . $e->getMessage()];
        }
    }

    /**
     * Bangun seluruh tabel cloud + relasi dari berkas supabase/sql/setup.sql.
     */
    public function bangunSkema(): array
    {
        $path = base_path('supabase/sql/setup.sql');

        if (!is_readable($path)) {
            return ['success' => false, 'message' => 'Berkas supabase/sql/setup.sql tidak ditemukan di project ini.'];
        }

        $hasil = $this->jalankanSql((string) file_get_contents($path));

        if ($hasil['success']) {
            $hasil['tabel'] = $this->daftarTabelCloud();
            $belum = collect($hasil['tabel'])->where('ada', false)->pluck('tabel')->all();
            $hasil['message'] = $belum === []
                ? 'Semua tabel cloud siap: ERD Supabase akan menampilkan ' . count($hasil['tabel']) . ' tabel beserta relasinya.'
                : 'Skrip jalan, tapi masih ada tabel yang belum terbentuk: ' . implode(', ', $belum) . '.';
        }

        return $hasil;
    }

    /**
     * Status tiap tabel cloud: sudah ada atau belum, dan berapa barisnya.
     */
    public function daftarTabelCloud(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $key = $this->keyUntukTulis();
        $daftar = [];

        foreach (static::TABEL_CLOUD as $tabel) {
            $item = ['tabel' => $tabel, 'ada' => false, 'baris' => null, 'kode' => null];

            try {
                $respon = Http::timeout(10)
                    ->withHeaders(['apikey' => $key, 'Authorization' => 'Bearer ' . $key, 'Prefer' => 'count=exact'])
                    ->get("{$this->url}/rest/v1/{$tabel}?select=id&limit=1");

                $item['kode'] = $respon->status();

                if ($respon->successful()) {
                    $item['ada'] = true;
                    if (preg_match('#/\d+$#', (string) $respon->header('Content-Range'), $m)) {
                        $item['baris'] = (int) ltrim($m[0], '/');
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Cek tabel Supabase ' . $tabel . ' gagal: ' . $e->getMessage());
            }

            $daftar[] = $item;
        }

        return $daftar;
    }

    /**
     * Apakah tabel sudah tersedia di cloud? Satu permintaan per tabel,
     * hasilnya disimpan selama request berjalan.
     */
    protected function tabelAda(string $tabel): bool
    {
        if (array_key_exists($tabel, static::$cacheTabelAda)) {
            return static::$cacheTabelAda[$tabel];
        }

        $ada = false;

        if ($this->isConfigured()) {
            $key = $this->keyUntukTulis();

            try {
                $respon = Http::timeout(10)
                    ->withHeaders(['apikey' => $key, 'Authorization' => 'Bearer ' . $key])
                    ->get("{$this->url}/rest/v1/{$tabel}?select=id&limit=0");

                $ada = $respon->successful();
            } catch (\Throwable $e) {
                Log::warning('Cek tabel Supabase ' . $tabel . ' gagal: ' . $e->getMessage());
            }
        }

        static::$cacheTabelAda[$tabel] = $ada;

        return $ada;
    }

    /**
     * Kirim seluruh data lokal ke cloud memakai urutan aman-relasi:
     * tabel induk (users, ingredients) lebih dulu, baru tabel anak.
     */
    public function sinkronkanSemuaData(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Supabase belum dikonfigurasi, jadi data tidak bisa dikirim.', 'tabel' => []];
        }

        $laporan = [];

        foreach ($this->definisiSinkronisasi() as $tabel => $kolom) {
            $baris = $this->siapkanBaris($tabel, $kolom);
            $hasil = $this->upsertBatch($tabel, $baris);

            $laporan[] = [
                'tabel' => $tabel,
                'total' => count($baris),
                'terkirim' => $hasil['terkirim'],
                'ok' => $hasil['ok'],
                'pesan' => $hasil['pesan'],
            ];

            static::$cacheTabelAda[$tabel] = $hasil['ok'];
        }

        $gagal = collect($laporan)->where('ok', false)->pluck('tabel')->all();

        return [
            'success' => $gagal === [],
            'message' => $gagal === []
                ? 'Seluruh data lokal sudah terkirim ke cloud. ERD Supabase kini berisi tabel dan isinya.'
                : 'Sebagian tabel belum terisi: ' . implode(', ', $gagal) . '. Jalankan supabase/sql/setup.sql lebih dulu, lalu ulangi.',
            'tabel' => $laporan,
        ];
    }

    /**
     * Peta kolom yang dicerminkan ke cloud. Kolom rahasia (password,
     * remember_token) sengaja tidak ada di daftar ini.
     */
    protected function definisiSinkronisasi(): array
    {
        return [
            'users' => ['id', 'name', 'email', 'google_id', 'avatar', 'diet_preferences', 'cooking_skill', 'allergies', 'email_verified_at', 'created_at', 'updated_at'],
            'ingredients' => ['id', 'name', 'category', 'region_origin', 'default_unit', 'typical_shelf_life_days', 'calories_per_100g', 'icon', 'image_url', 'description', 'created_at', 'updated_at'],
            'ingredient_requests' => ['id', 'user_id', 'raw_input', 'image_path', 'supabase_image_url', 'source_type', 'detected_ingredients', 'created_at', 'updated_at'],
            'recipes_cache' => ['id', 'user_id', 'title', 'slug', 'description', 'cooking_time_minutes', 'difficulty', 'servings', 'cuisine_type', 'region', 'image_url', 'meal_type', 'ingredient_hash', 'used_ingredients', 'missing_ingredients', 'instructions', 'nutrition_facts', 'chef_tips', 'tags', 'source', 'is_demo_fallback', 'is_favorite', 'created_at', 'updated_at'],
            'recipes' => ['id', 'user_id', 'request_id', 'title', 'slug', 'variant_type', 'cuisine_type', 'description', 'ingredient_needed', 'missing_ingredients', 'instructions', 'estimated_time', 'difficulty', 'servings', 'nutrition_facts', 'substitute_suggestions', 'chef_tips', 'image_url', 'is_saved', 'created_at', 'updated_at'],
            'recipe_substitutions' => ['id', 'user_id', 'recipe_id', 'original_ingredient', 'substitute_ingredient', 'ratio_adjustment', 'culinary_notes', 'created_at', 'updated_at'],
            'ai_logs' => ['id', 'user_id', 'action_type', 'model_used', 'prompt_summary', 'prompt_tokens', 'response_tokens', 'total_tokens', 'execution_time_ms', 'status', 'error_message', 'raw_response', 'created_at', 'updated_at'],
            'kitchen_inventories' => ['id', 'user_id', 'ingredient_id', 'item_name', 'category', 'quantity', 'unit', 'storage_location', 'purchase_date', 'expiration_date', 'status', 'archived_at', 'notes', 'photo_path', 'created_at', 'updated_at'],
            'system_settings' => ['id', 'key', 'value', 'description', 'created_at', 'updated_at'],
        ];
    }

    /**
     * Baca seluruh tabel lokal lalu bersihkan tipenya agar cocok dengan Postgres.
     */
    protected function siapkanBaris(string $tabel, array $kolom): array
    {
        $query = DB::table($tabel);

        if ($tabel === 'ai_logs') {
            $query->orderByDesc('id')->limit(500);
        }

        $baris = [];

        foreach ($query->get() as $row) {
            $row = (array) $row;

            if ($tabel === 'system_settings' && in_array($row['key'] ?? '', static::KUNCI_RAHASIA, true)) {
                continue;
            }

            $baris[] = $this->petakanBaris($tabel, $kolom, $row);
        }

        return $baris;
    }

    /**
     * Ubah satu baris MySQL menjadi payload PostgREST: kolom json diurai,
     * tinyint jadi boolean, dan blob log Gemini dipotong agar request tetap ringan.
     */
    protected function petakanBaris(string $tabel, array $kolom, array $row): array
    {
        $siap = [];

        foreach ($kolom as $k) {
            $nilai = $row[$k] ?? null;

            if (in_array($k, static::KOLOM_JSON, true)) {
                $siap[$k] = $this->keJson($nilai);
            } elseif (in_array($k, static::KOLOM_BOOL, true)) {
                $siap[$k] = (bool) $nilai;
            } elseif ($k === 'raw_response') {
                $siap[$k] = $nilai === null ? null : Str::limit((string) $nilai, 50000, '...[dipotong]');
            } else {
                $siap[$k] = $nilai;
            }
        }

        return $siap;
    }

    protected function keJson($nilai, array $default = []): array
    {
        if (is_array($nilai)) {
            return $nilai;
        }

        if ($nilai === null || $nilai === '') {
            return $default;
        }

        $decoded = json_decode((string) $nilai, true);

        return json_last_error() === JSON_ERROR_NONE ? (array) $decoded : $default;
    }

    /**
     * Unggah banyak baris sekaligus (upsert). Tidak pernah melempar exception.
     */
    protected function upsertBatch(string $tabel, array $baris, int $ukuranChunk = 100): array
    {
        if ($baris === []) {
            return ['ok' => true, 'terkirim' => 0, 'pesan' => 'Tidak ada baris untuk dikirim.'];
        }

        $chunks = array_chunk($baris, $ukuranChunk);
        $terkirim = 0;
        $dilewati = [];

        try {
            foreach ($chunks as $index => $chunk) {
                $respon = $this->kirimChunk($tabel, $chunk);
                $percobaan = 0;

                // Skema cloud bisa tertinggal dari MySQL (mis. kolom baru belum dibuat).
                // Kolom yang tidak dikenal Supabase menolak seluruh batch, jadi kolom itu
                // dilewati dari semua baris lalu permintaan diulang.
                while (!$respon->successful() && $percobaan < 4) {
                    $asing = $this->kolomAsing($respon->body());

                    if ($asing === []) {
                        break;
                    }

                    foreach ($chunks as &$sisa) {
                        foreach ($sisa as &$barisSisa) {
                            foreach ($asing as $kolom) {
                                unset($barisSisa[$kolom]);
                            }
                        }
                        unset($barisSisa);
                    }
                    unset($sisa);

                    $dilewati = array_values(array_unique(array_merge($dilewati, $asing)));
                    $percobaan++;
                    $respon = $this->kirimChunk($tabel, $chunks[$index]);
                }

                if (!$respon->successful()) {
                    return [
                        'ok' => false,
                        'terkirim' => $terkirim,
                        'pesan' => 'HTTP ' . $respon->status() . ': ' . Str::limit($respon->body(), 220),
                    ];
                }

                $terkirim += count($chunks[$index]);
            }

            $pesan = 'Terkirim ' . $terkirim . ' baris.';
            if ($dilewati !== []) {
                $pesan .= ' Kolom ' . implode(', ', $dilewati) . ' belum ada di cloud (jalankan setup.sql) jadi dilewati.';
            }

            return ['ok' => true, 'terkirim' => $terkirim, 'pesan' => $pesan];
        } catch (\Throwable $e) {
            Log::warning('Sinkronisasi massal ' . $tabel . ' gagal: ' . $e->getMessage());
            return ['ok' => false, 'terkirim' => $terkirim, 'pesan' => $e->getMessage()];
        }
    }

    protected function kirimChunk(string $tabel, array $chunk)
    {
        $key = $this->keyUntukTulis();

        return Http::timeout(60)
            ->withHeaders([
                'apikey' => $key,
                'Authorization' => 'Bearer ' . $key,
                'Content-Type' => 'application/json',
                'Prefer' => 'resolution=merge-duplicates,return=minimal',
            ])
            ->post("{$this->url}/rest/v1/{$tabel}", $chunk);
    }

    /**
     * Nama kolom yang tidak dikenal Supabase, bila memang itu penyebab gagalnya request.
     */
    protected function kolomAsing(string $body): array
    {
        $pesan = (string) (json_decode($body, true)['message'] ?? '');

        preg_match_all("/Could not find the '([a-z0-9_]+)' column/i", $pesan, $cocok);
        preg_match_all('/column "?([a-z0-9_]+)"? does not exist/i', $pesan, $cocok2);

        return array_values(array_unique(array_merge($cocok[1] ?? [], $cocok2[1] ?? [])));
    }

    /**
     * Pastikan baris induk (pemilik & bahan) sudah ada di cloud sebelum item
     * inventaris dikirim, supaya foreign key tidak menolak insert.
     */
    protected function pastikanIndukInventaris(KitchenInventory $item): void
    {
        $definisi = $this->definisiSinkronisasi();

        foreach (['users' => $item->user_id, 'ingredients' => $item->ingredient_id] as $tabel => $idInduk) {
            if (!$idInduk || !$this->tabelAda($tabel)) {
                continue;
            }

            $kunci = $tabel . ':' . $idInduk;

            if (isset(static::$cacheIndukSiap[$kunci])) {
                continue;
            }

            $induk = DB::table($tabel)->where('id', $idInduk)->first();

            if ($induk) {
                $this->upsertBatch($tabel, [$this->petakanBaris($tabel, $definisi[$tabel], (array) $induk)]);
            }

            static::$cacheIndukSiap[$kunci] = true;
        }
    }
}

