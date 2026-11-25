<?php

namespace Tests\Feature;

use App\Models\RecipeCache;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\HidanganFotoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Setiap kartu resep harus memperlihatkan foto hidangan yang benar-benar
 * menggambarkan namanya; kalau tidak ada yang dipercaya, placeholder dipakai.
 */
class FotoHidanganResepTest extends TestCase
{
    use DatabaseTransactions;

    private const FOTO = 'https://thumb.wikimedia.org/wikipedia/commons/thumb/a/ae/Stir_fried_mixed_vegetables.jpg/960px-Stir_fried_mixed_vegetables.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::whereIn('key', ['gemini_api_key', 'gemini_model'])->delete();

        config([
            'services.gemini.api_key' => 'kunci-uji-gemini',
            'services.gemini.model' => 'gemini-uji',
            'services.gemini.fallback_models' => ['gemini-uji'],
        ]);

        $akun = User::create([
            'name' => 'Penguji Foto',
            'email' => 'penguji.foto@gmail.com',
            'password' => bcrypt('rahasia123'),
            'diet_preferences' => ['Halal'],
            'cooking_skill' => 'Pemula',
        ]);

        $this->actingAs($akun);

        $supabase = \Mockery::mock(\App\Services\SupabaseService::class);
        $supabase->shouldReceive('isConfigured')->andReturn(false);
        $this->app->instance(\App\Services\SupabaseService::class, $supabase);
    }

    private function hasilCommons(string $judulBerkas, string $mime = 'image/jpeg', ?string $url = null): void
    {
        Http::fake([
            'commons.wikimedia.org/*' => Http::response([
                'query' => ['pages' => [
                    '123' => [
                        'title' => 'File:' . $judulBerkas,
                        'imageinfo' => [[
                            'mime' => $mime,
                            'thumburl' => $url ?: self::FOTO,
                            'url' => $url ?: 'https://upload.wikimedia.org/wikipedia/commons/a/ae/Stir_fried_mixed_vegetables.jpg',
                        ]],
                    ],
                ]],
            ], 200),
        ]);
    }

    /**
     * Tiap frasa pencarian diberi hasil berbeda, seperti Commons sebenarnya.
     *
     * @param  array<string,string>  $perFrasa  frasa pencarian => nama berkas
     */
    private function hasilCommonsPerFrasa(array $perFrasa): void
    {
        Http::fake([
            'commons.wikimedia.org/*' => function ($request) use ($perFrasa) {
                $frasa = (string) data_get($request->data(), 'gsrsearch', '');
                $berkas = $perFrasa[$frasa] ?? null;

                if ($berkas === null) {
                    return Http::response(['query' => ['pages' => []]], 200);
                }

                $nama = 'https://upload.wikimedia.org/wikipedia/commons/1/11/' . str_replace(' ', '_', $berkas);

                return Http::response([
                    'query' => ['pages' => [
                        '123' => [
                            'title' => 'File:' . $berkas,
                            'imageinfo' => [['mime' => 'image/jpeg', 'thumburl' => $nama, 'url' => $nama]],
                        ],
                    ]],
                ], 200);
            },
        ]);
    }

    public function test_foto_dipakai_saat_nama_berkas_memuat_frasa_hidangannya(): void
    {
        $this->hasilCommons('Stir fried mixed vegetables.jpg');

        $url = app(HidanganFotoService::class)->cari(
            'Tumis Campur Spesial Kacang Polong Kapri Jagung Muda',
            null,
            ['Kacang polong', 'Jagung muda'],
            6
        );

        $this->assertSame(self::FOTO, $url);
    }

    public function test_hasil_ai_umum_tetap_mencocokkan_kategori_hidangannya(): void
    {
        $this->hasilCommons('Stir fried mixed vegetables.jpg');

        $url = app(HidanganFotoService::class)->cari('Tumis Sayur Campur Gurih Sederhana', null, [], 12);

        $this->assertSame(self::FOTO, $url);
        $this->assertStringContainsString('mixed', $url);
    }

    public function test_kata_es_tetap_menyebut_hidangannya(): void
    {
        $this->hasilCommons('Es Campur Banjar.jpg', 'image/jpeg', 'https://upload.wikimedia.org/wikipedia/commons/d/d8/Es_Campur.jpg');

        // "es" cuma dua huruf tetapi nama hidangan, jadi jangan dibuang sebagai noise.
        $this->assertSame(
            'https://upload.wikimedia.org/wikipedia/commons/d/d8/Es_Campur.jpg',
            app(HidanganFotoService::class)->cari('Es Campur', null, [], 8)
        );
    }

    public function test_foto_hidangan_lain_tetap_ditolak_oleh_judul_berkata_es(): void
    {
        $this->hasilCommons(
            'Deep fried potato wedges with cheese.jpg',
            'image/jpeg',
            'https://upload.wikimedia.org/wikipedia/commons/1/11/Deep_fried_potato_wedges.jpg'
        );

        // Es campur tidak boleh difoto kentang goreng.
        $this->assertNull(app(HidanganFotoService::class)->cari('Es Campur', null, [], 8));
    }

    public function test_nama_hidangan_di_awal_judul_menangi_cocokan_bahan(): void
    {
        $this->hasilCommonsPerFrasa([
            'combro' => 'Combro 2.jpg',
            'oncom' => 'Oncom merah.JPG',
        ]);

        // Combro difoto oncom merah juga, tetapi nama hidangannya yang menang.
        $url = app(HidanganFotoService::class)->cari(
            'Combro Oncom Di Jero',
            null,
            ['Oncom merah', 'Kentang parut'],
            12
        );

        $this->assertStringContainsString('Combro_2.jpg', (string) $url);
    }

    public function test_foto_kue_ditolak_untuk_resep_rujak(): void
    {
        $this->hasilCommonsPerFrasa([
            'gula merah' => 'Kue Jongkong (Gula Merah).jpg',
            'rujak' => 'Rujak Ambon.jpg',
        ]);

        // Rujak Natsepa memakai gula merah, tapi fotonya bukan kue jongkong.
        $url = app(HidanganFotoService::class)->cari(
            'Rujak Natsepa di Pantai Hitu',
            'Maluku',
            ['Gula merah', 'Cabai rawit'],
            12
        );

        $this->assertStringContainsString('Rujak_Ambon.jpg', (string) $url);
    }

    public function test_foto_bukan_hidangan_ditolak(): void
    {
        $this->hasilCommons('Logo Restoran Stir fried mixed vegetables.jpg');

        $this->assertNull(
            app(HidanganFotoService::class)->cari('Tumis Sayur Campur', null, [], 4)
        );
    }

    public function test_hasil_dengan_foto_lain_judul_ditolak(): void
    {
        // Nama berkas hanya memuat satu kata dari frasa dua kata -> tidak dipercaya.
        $this->hasilCommons('Stir fried salmon with lemon.jpg');

        $this->assertNull(
            app(HidanganFotoService::class)->cari('Tumis Kacang Polong', null, [], 3)
        );
    }

    public function test_foto_satu_kata_saja_tidak_dipercaya(): void
    {
        $this->hasilCommons('Sliced tempeh.jpg');

        // Foto tempe potong tanpa kata "krispi" tidak membuktikan ini Tempe Krispi Daun Jeruk.
        $this->assertNull(app(HidanganFotoService::class)->cari('Tempe Krispi Daun Jeruk', null, [], 8));
    }

    public function test_cara_masak_yang_bertentangan_ditolak(): void
    {
        $this->hasilCommons('Deep fried potato wedges with cheese.jpg');

        // Kentang rebus tidak boleh difoto kentang goreng.
        $this->assertNull(app(HidanganFotoService::class)->cari('Kentang Rebus Kuah Bening Keju', null, [], 8));
    }

    public function test_foto_dengan_lauk_yang_berbeda_ditolak(): void
    {
        $this->hasilCommons('Kwetiau Goreng Kepiting.jpg');

        // Kwetiau sapi tidak boleh difoto kwetiau kepiting.
        $this->assertNull(
            app(HidanganFotoService::class)->cari('Kwetiau Goreng Sapi Kekinian', null, ['Daging sapi has dalam'], 8)
        );
    }

    public function test_lauk_dari_daftar_bahan_membuka_nama_hidangannya(): void
    {
        $this->hasilCommons('Sate Sapi Madura.jpg');

        $url = app(HidanganFotoService::class)->cari(
            'Sate Klopo Bondowoso',
            null,
            ['Daging sapi potong kotak', 'Kelapa parut panjang'],
            8
        );

        $this->assertNotNull($url);
    }

    public function test_commons_yang_ganggu_tidak_menggagalkan_pencarian(): void
    {
        Http::fake([
            'commons.wikimedia.org/*' => Http::response(null, 503),
        ]);

        $this->assertNull(app(HidanganFotoService::class)->cari('Nasi Goreng Kampung', null, [], 4));
    }

    public function test_commons_yang_hilang_sama_sekali_ditolak_oleh_exception(): void
    {
        Http::fake([
            'commons.wikimedia.org/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('koneksi ke commons putus');
            },
        ]);

        $this->assertNull(app(HidanganFotoService::class)->cari('Pecel Lele Sambal Bawang', null, [], 4));
    }

    public function test_layanan_menyimpan_foto_pada_resep(): void
    {
        $this->hasilCommons('Stir fried mixed vegetables.jpg');

        $resep = RecipeCache::create([
            'title' => 'Tumis Campur Kacang Polong Kapri',
            'description' => 'Tumis sayur campur sederhana.',
            'cooking_time_minutes' => 15,
            'difficulty' => 'Mudah',
            'servings' => 2,
            'cuisine_type' => 'Nusantara',
            'meal_type' => 'Makan Malam',
            'ingredient_hash' => 'uji-' . uniqid(),
            'used_ingredients' => [['name' => 'Kacang polong', 'quantity' => 1, 'unit' => 'cup']],
            'missing_ingredients' => [],
            'instructions' => ['Tumis semua bahan.'],
        ]);

        $this->assertTrue(app(HidanganFotoService::class)->pasang($resep, 8));
        $this->assertSame(self::FOTO, $resep->fresh()->image_url);
    }

    public function test_hasil_generate_ai_langsung_memiliki_foto_hidangan(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'title' => 'Tumis Campur Spesial Kacang Polong Kapri',
                    'description' => 'Tumis sayur hijau gurih.',
                    'cooking_time_minutes' => 18,
                    'difficulty' => 'Mudah',
                    'servings' => 3,
                    'cuisine_type' => 'Nusantara',
                    'meal_type' => 'Makan Malam',
                    'used_ingredients' => [['name' => 'Kacang polong', 'quantity' => 1, 'unit' => 'cup']],
                    'missing_ingredients' => [],
                    'instructions' => ['Tumis bumbu lalu masukkan sayur.'],
                    'nutrition_facts' => ['calories_kcal' => 210, 'protein_g' => 9],
                ])]]]]],
                'usageMetadata' => ['totalTokenCount' => 40],
            ], 200),
            'commons.wikimedia.org/*' => Http::response([
                'query' => ['pages' => [
                    '1' => [
                        'title' => 'File:Stir fried mixed vegetables.jpg',
                        'imageinfo' => [['mime' => 'image/jpeg', 'thumburl' => self::FOTO]],
                    ],
                ]],
            ], 200),
        ]);

        $respons = $this->postJson(route('recipes.generate'), [
            'ingredients' => [['name' => 'Kacang polong Uji ' . Str::random(6), 'quantity' => 1]],
            'cuisine_type' => 'Nusantara',
            'meal_type' => 'Makan Malam',
            'cooking_skill' => 'Mudah',
        ]);

        $respons->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recipe.image_url', self::FOTO);

        $resep = RecipeCache::latest('id')->firstOrFail();
        $this->assertSame(self::FOTO, $resep->image_url);
        $this->assertSame(self::FOTO, $resep->photo);
    }

    public function test_katalog_dan_halaman_generator_menampilkan_foto_dengan_placeholder(): void
    {
        $resep = RecipeCache::create([
            'title' => 'Sate Lilit Bali Uji Foto',
            'description' => 'Sate bumbu khas Bali.',
            'cooking_time_minutes' => 25,
            'difficulty' => 'Menengah',
            'servings' => 2,
            'cuisine_type' => 'Tradisional Nusantara',
            'meal_type' => 'Makan Siang',
            'ingredient_hash' => 'katalog-' . uniqid(),
            'used_ingredients' => [],
            'missing_ingredients' => [],
            'instructions' => ['Bakar sate.'],
            'image_url' => 'https://thumb.wikimedia.org/wikipedia/commons/sate-lilit.jpg',
        ]);

        $katalog = $this->get(route('recipes.index', ['q' => 'Sate Lilit Bali Uji Foto']))->assertOk();
        $katalog->assertSee('sate-lilit.jpg', false);
        $katalog->assertSee("onerror=\"this.onerror=null;this.src='" . asset('images/katalog/default-nusantara.svg') . "'", false);

        $detail = $this->get(route('recipes.show', $resep->id))->assertOk();
        $detail->assertSee('sate-lilit.jpg', false);

        $generator = $this->get(route('recipes.generator'))->assertOk();
        $generator->assertSee('generatedRecipe?.image_url', false);
        $generator->assertSee(asset('images/katalog/default-nusantara.svg'), false);
    }
}
