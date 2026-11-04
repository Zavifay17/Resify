<?php

namespace Tests\Feature;

use App\Models\KitchenInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AlurPenggunaanTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::query()->firstOrFail();
        $this->actingAs($this->user);
    }

    public function test_halaman_utama_dapat_dibuka(): void
    {
        foreach (['/dashboard', '/inventory', '/vision', '/recipes', '/recipes/generator', '/logs', '/settings'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_menambahkan_memperbarui_dan_menghapus_bahan(): void
    {
        $buat = [
            'item_name' => 'Terong Untuk Uji Coba',
            'category' => 'Sayuran',
            'quantity' => 3,
            'unit' => 'buah',
            'storage_location' => 'Chiller Bawah',
            'purchase_date' => now()->toDateString(),
            'expiration_date' => now()->addDays(4)->toDateString(),
            'notes' => 'Barang uji otomatis',
        ];

        $this->post(route('inventory.store'), $buat)->assertRedirect(route('inventory.index'));

        $item = KitchenInventory::where('user_id', $this->user->id)
            ->where('item_name', $buat['item_name'])
            ->firstOrFail();

        $this->put(route('inventory.update', $item->id), array_merge($buat, [
            'quantity' => 5,
            'expiration_date' => now()->addDays(9)->toDateString(),
        ]))->assertRedirect(route('inventory.index'));

        $this->assertSame(5.0, (float) $item->fresh()->quantity);

        $this->delete(route('inventory.destroy', $item->id))->assertRedirect(route('inventory.index'));
        $this->assertDatabaseMissing('kitchen_inventories', ['id' => $item->id]);
    }

    public function test_generator_resep_menghasilkan_rancangan_masakan(): void
    {
        $response = $this->postJson(route('recipes.generate'), [
            'ingredients' => ['Telur Ayam', 'Tomat', 'Bawang Merah'],
            'cuisine_type' => 'Nusantara / Masakan Rumahan Indonesia',
            'meal_type' => 'Makan Malam',
            'max_time' => 30,
            'cooking_skill' => 'Pemula',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('recipe.title'));
        $this->assertNotEmpty($response->json('recipe.instructions'));
    }

    public function test_saran_pengganti_bahan_berhasil_dikembalikan(): void
    {
        $response = $this->postJson(route('substitute.suggest'), [
            'ingredient' => 'Santan Kelapa',
            'recipe_context' => 'Sayur Lodeh',
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.substitutes'));
    }

    public function test_tes_koneksi_api_dari_halaman_pengaturan(): void
    {
        $this->postJson(route('settings.test-gemini'))->assertOk()->assertJsonStructure(['success', 'message']);
        $this->postJson(route('settings.test-supabase'))->assertOk()->assertJsonStructure(['success', 'message']);
    }

    public function test_tombol_selamatkan_bahan_mengarah_ke_generator(): void
    {
        $this->post(route('recipes.rescue'))->assertRedirect();
    }

    public function test_laporan_cetak_dapat_dibuka(): void
    {
        $this->get(route('inventory.print'))->assertOk()->assertSee('Laporan', false);
    }
}
