<?php

namespace Tests\Feature;

use App\Models\KitchenInventory;
use App\Models\SystemSetting;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupabaseSkemaCloudTest extends TestCase
{
    use DatabaseTransactions;

    protected SupabaseService $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        SupabaseService::kosongkanCacheStatus();

        // Nilai cloud disimpan di SystemSetting, jadi override di sana supaya
        // tes tidak pernah mengarah ke project Supabase asli.
        SystemSetting::set('supabase_url', 'https://uji-project.supabase.co', 'URL uji');
        SystemSetting::set('supabase_key', 'anon-kunci-uji', 'Key uji');
        SystemSetting::set('supabase_service_key', 'service-kunci-uji', 'Service key uji');

        config([
            'services.supabase.dashboard_token' => null,
            'services.supabase.url' => 'https://uji-project.supabase.co',
            'services.supabase.key' => 'anon-kunci-uji',
            'services.supabase.service_key' => 'service-kunci-uji',
        ]);

        $this->layanan = new SupabaseService();
    }

    public function test_token_kelola_hanya_dibaca_dari_config(): void
    {
        $this->assertFalse($this->layanan->punyaTokenKelola());

        config(['services.supabase.dashboard_token' => 'token-personal']);

        $this->assertTrue((new SupabaseService)->punyaTokenKelola());
    }

    public function test_bangun_skema_meminta_token_saat_belum_diisi(): void
    {
        Http::fake();

        $hasil = $this->layanan->bangunSkema();

        $this->assertFalse($hasil['success']);
        $this->assertTrue($hasil['perlu_token']);
        $this->assertStringContainsString('SQL Editor', $hasil['message']);
        $this->assertCount(0, Http::recorded());
    }

    public function test_bangun_skema_mengirim_isi_setup_sql_ke_api_kelolaan(): void
    {
        config(['services.supabase.dashboard_token' => 'token-personal']);

        Http::fake([
            'api.supabase.com/v1/projects/uji-project/database/query' => Http::response([['tabel' => 'users']], 201),
            '*uji-project.supabase.co/rest/v1/*' => Http::response('[]', 200, ['Content-Range' => '0-0/7']),
        ]);

        $hasil = $this->layanan->bangunSkema();

        $this->assertTrue($hasil['success']);
        $this->assertCount(9, $hasil['tabel']);
        $this->assertStringContainsString('9 tabel', $hasil['message']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.supabase.com/v1/projects/uji-project/database/query')
                && str_contains($request['query'], 'create table if not exists public.users')
                && str_contains($request['query'], 'fk_cloud_inventory_user');
        });
    }

    public function test_ref_project_diparsing_dari_url_supabase(): void
    {
        $this->assertSame('uji-project', $this->layanan->projectRef());
    }

    public function test_daftar_tabel_membedakan_yang_sudah_ada_dan_belum(): void
    {
        Http::fake([
            '*uji-project.supabase.co/rest/v1/kitchen_inventories*' => Http::response('[]', 200, ['Content-Range' => '0-0/74']),
            '*uji-project.supabase.co/rest/v1/*' => Http::response(['message' => 'relation does not exist'], 404),
        ]);

        $daftar = collect($this->layanan->daftarTabelCloud());

        $inventaris = $daftar->firstWhere('tabel', 'kitchen_inventories');
        $this->assertTrue($inventaris['ada']);
        $this->assertSame(74, $inventaris['baris']);

        $aiLogs = $daftar->firstWhere('tabel', 'ai_logs');
        $this->assertFalse($aiLogs['ada']);
        $this->assertNull($aiLogs['baris']);
        $this->assertCount(9, $daftar);
    }

    public function test_sinkronisasi_data_tidak_error_saat_tabel_belum_dibangun(): void
    {
        Http::fake(['*uji-project.supabase.co/*' => Http::response(['message' => 'table not found'], 404)]);

        $hasil = $this->layanan->sinkronkanSemuaData();

        $this->assertFalse($hasil['success']);
        $this->assertStringContainsString('setup.sql', $hasil['message']);
        $this->assertCount(9, $hasil['tabel']);
    }

    public function test_tabel_induk_dikirim_dahuluan_dan_tanpa_kolom_rahasia(): void
    {
        Http::fake(['*uji-project.supabase.co/*' => Http::response('[]', 201)]);

        $hasil = $this->layanan->sinkronkanSemuaData();

        $this->assertTrue($hasil['success'], $hasil['message']);

        $url = collect(Http::recorded())->map(fn ($catatan) => $catatan[0]->url())->values();
        $posisiUsers = $url->search(fn ($u) => str_contains($u, '/rest/v1/users'));
        $posisiInventaris = $url->search(fn ($u) => str_contains($u, '/rest/v1/kitchen_inventories'));

        $this->assertNotFalse($posisiUsers);
        $this->assertLessThan($posisiInventaris, $posisiUsers);

        $semuaIsi = collect(Http::recorded())
            ->map(fn ($catatan) => json_encode($catatan[0]->data()))
            ->implode(' ');

        $this->assertStringNotContainsString('"password"', $semuaIsi);
        $this->assertStringNotContainsString('"remember_token"', $semuaIsi);
    }

    public function test_kunci_rahasia_system_settings_tidak_dicerminkan_ke_cloud(): void
    {
        SystemSetting::set('supabase_service_key', 'jangan-sampai-bocor', 'Key uji');
        SystemSetting::set('campus_tes', 'kampus-terlihat', 'Pengaturan non rahasia');

        Http::fake(['*uji-project.supabase.co/*' => Http::response('[]', 201)]);

        (new SupabaseService)->sinkronkanSemuaData();

        $isiSettings = collect(Http::recorded())
            ->filter(fn ($catatan) => str_contains($catatan[0]->url(), '/rest/v1/system_settings'))
            ->map(fn ($catatan) => json_encode($catatan[0]->data()))
            ->implode(' ');

        $this->assertStringNotContainsString('jangan-sampai-bocor', $isiSettings);
        $this->assertStringContainsString('kampus-terlihat', $isiSettings);
    }

    public function test_sync_item_inventaris_mengirim_baris_induk_terlebih_dulu(): void
    {
        $userId = DB::table('users')->value('id');
        $ingredientId = DB::table('ingredients')->insertGetId([
            'name' => 'Terong Uji Cloud',
            'category' => 'Sayuran',
            'default_unit' => 'buah',
            'typical_shelf_life_days' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = KitchenInventory::create([
            'user_id' => $userId,
            'ingredient_id' => $ingredientId,
            'item_name' => 'Terong Uji Cloud',
            'category' => 'Sayuran',
            'quantity' => 2,
            'unit' => 'buah',
            'storage_location' => 'Kulkas (Chiller)',
            'purchase_date' => now()->subDay()->toDateString(),
            'expiration_date' => now()->addDays(3)->toDateString(),
            'status' => 'safe',
        ]);

        Http::fake([
            '*uji-project.supabase.co/rest/v1/kitchen_inventories*' => Http::response('[{"id":1}]', 200),
            '*uji-project.supabase.co/rest/v1/*' => Http::response('[]', 201),
        ]);

        $hasil = (new SupabaseService)->syncInventoryItem($item);

        $this->assertTrue($hasil['synced'], $hasil['message']);

        $kirim = collect(Http::recorded())
            ->map(fn ($catatan) => [$catatan[0]->url(), $catatan[0]->method()])
            ->filter(fn ($p) => $p[1] === 'POST')
            ->values();

        $posisiInduk = $kirim->search(fn ($p) => str_contains($p[0], '/rest/v1/users'));
        $posisiBahan = $kirim->search(fn ($p) => str_contains($p[0], '/rest/v1/ingredients'));
        $posisiItem = $kirim->search(fn ($p) => str_contains($p[0], '/rest/v1/kitchen_inventories'));

        $this->assertNotFalse($posisiItem);
        $this->assertLessThan($posisiItem, $posisiInduk);
        $this->assertLessThan($posisiItem, $posisiBahan);
    }
}
