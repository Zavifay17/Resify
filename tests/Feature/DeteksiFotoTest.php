<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeteksiFotoTest extends TestCase
{
    use DatabaseTransactions;

    private User $akun;

    protected function setUp(): void
    {
        parent::setUp();

        // Kunci asli dipinjam dari database; dihapus sementara supaya config uji yang dipakai.
        SystemSetting::whereIn('key', ['gemini_api_key', 'gemini_model'])->delete();

        config([
            'services.gemini.api_key' => 'kunci-uji-gemini',
            'services.gemini.model' => 'gemini-uji',
            'services.gemini.fallback_models' => ['gemini-uji'],
        ]);

        $this->akun = User::create([
            'name' => 'Penguji Dapur',
            'email' => 'penguji.deteksi@gmail.com',
            'password' => bcrypt('rahasia123'),
            'diet_preferences' => ['Halal'],
            'cooking_skill' => 'Pemula',
        ]);

        $this->actingAs($this->akun);

        // Storage cloud tidak dipakai saat uji supaya URL preview selalu milik server sendiri.
        $supabase = \Mockery::mock(\App\Services\SupabaseService::class);
        $supabase->shouldReceive('isConfigured')->andReturn(false);
        $this->app->instance(\App\Services\SupabaseService::class, $supabase);
    }

    private function jawabanGemini(array $items, string $summary = 'AI mendeteksi 2 bahan.'): void
    {
        $jawaban = json_encode(['summary' => $summary, 'detected_items' => $items]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => $jawaban]]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 20, 'totalTokenCount' => 30],
            ], 200),
        ]);
    }

    /**
     * Isi inlineData yang benar-benar dikirim ke Google untuk permintaan terakhir.
     */
    private function gambarTerkirim(): array
    {
        $pakai = collect(Http::recorded())->last();
        $bagian = $pakai[0]->data()['contents'][0]['parts'];

        $data = collect($bagian)->firstWhere('inlineData') ?? [];

        return [
            'base64' => $data['inlineData']['data'] ?? '',
            'mime' => $data['inlineData']['mimeType'] ?? '',
            'prompt' => $bagian[0]['text'] ?? '',
        ];
    }

    public function test_file_hasil_unggahan_dikirim_ke_ai_dan_previewnya_bisa_dibuka(): void
    {
        $sumber = public_path('images/samples/bumbu-dapur.jpg');
        $this->assertFileExists($sumber, 'Foto contoh bumbu dapur belum terpasang.');

        $this->jawabanGemini([[
            'item_name' => 'Bawang Merah',
            'category' => 'Bumbu Dapur',
            'estimated_quantity' => 8,
            'unit' => 'siung',
            'typical_shelf_life_days' => 20,
        ]]);

        $respons = $this->post(route('vision.analyze'), [
            'image' => new UploadedFile($sumber, 'bumbu.jpg', 'image/jpeg', null, true),
        ]);

        $respons->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_simulated', false)
            ->assertJsonPath('data.detected_items.0.item_name', 'Bawang Merah');

        // Bug lama: path salah dikirim apa adanya sebagai base64 sehingga Google menolak.
        $terkirim = $this->gambarTerkirim();
        $this->assertSame(base64_encode(file_get_contents($sumber)), $terkirim['base64']);
        $this->assertStringStartsWith('image/', $terkirim['mime']);

        // Preview harus menunjuk berkas yang benar-benar ada di folder publik.
        $url = $respons->json('image_url');
        $this->assertNotEmpty($url);

        $relatif = str_replace('storage/', '', ltrim(parse_url($url, PHP_URL_PATH), '/'));
        $this->assertFileExists(public_path('storage/' . $relatif));
        Storage::disk('public')->delete($relatif);
    }

    public function test_hasil_deteksi_dibersihkan_sebelum_dipakai_form_inventaris(): void
    {
        $this->jawabanGemini([
            [
                'item_name' => '  Cabai Rawit Merah  ',
                'category' => '',
                'estimated_quantity' => 0,
                'unit' => '',
                'typical_shelf_life_days' => -5,
            ],
            ['item_name' => ''],
        ]);

        $items = $this->post(route('vision.analyze'), ['sample' => 'pasar'])
            ->assertOk()
            ->json('data.detected_items');

        $this->assertCount(1, $items);
        $this->assertSame('Cabai Rawit Merah', $items[0]['item_name']);
        $this->assertSame('Bahan Pokok', $items[0]['category']);
        $this->assertEquals(1.0, $items[0]['estimated_quantity']);
        $this->assertSame('buah', $items[0]['unit']);
        $this->assertSame(1, $items[0]['typical_shelf_life_days']);
    }

    public function test_sampel_foto_dipakai_langsung_dari_folder_publik(): void
    {
        $this->jawabanGemini([['item_name' => 'Timun', 'category' => 'Sayuran', 'estimated_quantity' => 5, 'unit' => 'buah']]);

        $sumber = public_path('images/samples/belanja-pasar.jpg');

        $this->post(route('vision.analyze'), ['sample' => 'pasar'])
            ->assertOk()
            ->assertJsonPath('is_simulated', false)
            ->assertJsonPath('image_url', asset('images/samples/belanja-pasar.jpg'));

        $this->assertSame(base64_encode(file_get_contents($sumber)), $this->gambarTerkirim()['base64']);
    }

    public function test_sampel_yang_tidak_dikenal_ditolak_dengan_pesan(): void
    {
        $this->post(route('vision.analyze'), ['sample' => 'tidak-ada'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_kegagalan_api_tidak_lagi_ditutupi_dengan_hasil_palsu(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'API key not valid. Please pass a valid API key.'],
            ], 400),
        ]);

        $this->post(route('vision.analyze'), ['sample' => 'bumbu'])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('is_simulated', false)
            ->assertJsonPath('message', 'Gemini API Key ditolak Google. Perbarui kunci di Pengaturan > Gemini, lalu pindai ulang.');
    }

    public function test_mode_tanpa_kunci_api_diberi_label_contoh(): void
    {
        config(['services.gemini.api_key' => '']);

        $respons = $this->post(route('vision.analyze'), ['sample' => 'kulkas']);

        $respons->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_simulated', true);

        $this->assertStringContainsString('Mode demo', $respons->json('notice'));
    }

    public function test_berkas_foto_hilang_dilaporkan_jelas_ke_pengguna(): void
    {
        $hasil = app(\App\Services\GeminiService::class)
            ->analyzeFridgeVision(storage_path('app/public/vision_uploads/fridge_tidak_ada.jpg'));

        $this->assertFalse($hasil['success']);
        $this->assertStringContainsString('tidak ditemukan', $hasil['message']);
    }

    public function test_foto_dari_kamera_tersimpan_di_folder_public_yang_benar(): void
    {
        $this->jawabanGemini([['item_name' => 'Tomat', 'category' => 'Sayuran', 'estimated_quantity' => 2, 'unit' => 'buah']]);

        $dataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(public_path('images/samples/kulkas-sayur.jpg')));

        $url = $this->post(route('vision.analyze'), ['image_base64' => $dataUri])
            ->assertOk()
            ->json('image_url');

        $relatif = ltrim(parse_url($url, PHP_URL_PATH), '/');
        $relatif = str_replace('storage/', '', $relatif);

        $this->assertFileExists(public_path('storage/' . $relatif));
        Storage::disk('public')->delete($relatif);
    }
}
