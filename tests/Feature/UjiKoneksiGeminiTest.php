<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UjiKoneksiGeminiTest extends TestCase
{
    use DatabaseTransactions;

    private const KUNCI_SAH = 'AIzaSyUjiKoneksiGeminiFormatBenar123456';

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::whereIn('key', ['gemini_api_key', 'gemini_model'])->delete();

        config([
            'services.gemini.api_key' => self::KUNCI_SAH,
            'services.gemini.model' => 'gemini-uji',
            'services.gemini.fallback_models' => ['gemini-uji'],
        ]);

        $this->actingAs(User::create([
            'name' => 'Penguji Setelan',
            'email' => 'penguji.setelan@gmail.com',
            'password' => bcrypt('rahasia123'),
            'diet_preferences' => ['Halal'],
            'cooking_skill' => 'Pemula',
        ]));
    }

    private function jawabanGeminiSiap(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Siap membantu dapur Anda.']]]]],
                'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 20, 'totalTokenCount' => 30],
            ], 200),
        ]);
    }

    public function test_kunci_singkat_ditolak_saat_disimpan(): void
    {
        $this->post(route('settings.update'), [
            'gemini_api_key' => 'Rezapahlevy17',
            'gemini_model' => 'gemini-3.1-flash-lite',
        ])->assertSessionHasErrors('gemini_api_key');

        $this->assertNotSame('Rezapahlevy17', (string) SystemSetting::where('key', 'gemini_api_key')->value('value'));
    }

    public function test_kunci_database_yang_bukan_api_key_diganti_kunci_env(): void
    {
        SystemSetting::set('gemini_api_key', 'Rezapahlevy17', 'Gemini API Key');

        $this->assertSame(self::KUNCI_SAH, app(GeminiService::class)->getApiKey());
        $this->assertSame('', app(GeminiService::class)->keyDatabase());
    }

    public function test_uji_koneksi_tetap_berhasil_saat_kunci_database_sampah(): void
    {
        SystemSetting::set('gemini_api_key', 'Rezapahlevy17', 'Gemini API Key');
        $this->jawabanGeminiSiap();

        $this->postJson(route('settings.test-gemini'), ['model' => 'gemini-uji'])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_uji_koneksi_menolak_format_kunci_yang_diketik_pengguna(): void
    {
        Http::fake();

        $this->postJson(route('settings.test-gemini'), [
            'api_key' => 'kata sandi saya',
            'model' => 'gemini-uji',
        ])->assertOk()->assertJson(['success' => false]);

        $this->assertStringContainsString(
            'Format API Key Gemini tidak sesuai',
            $this->postJson(route('settings.test-gemini'), ['api_key' => 'kata sandi saya'])->json('message')
        );
        Http::assertNothingSent();
    }

    public function test_uji_koneksi_memakai_kunci_baru_yang_ditempel(): void
    {
        $this->jawabanGeminiSiap();

        $this->postJson(route('settings.test-gemini'), [
            'api_key' => 'AIzaSyKunciBaruDariAiStudio1234567890abcdef',
            'model' => 'gemini-uji',
        ])->assertOk()->assertJson(['success' => true, 'reply' => 'Siap membantu dapur Anda.']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'key=AIzaSyKunciBaruDariAiStudio1234567890abcdef'));
    }

    public function test_halaman_pengaturan_tampilkan_peringatan_kunci_database_salah(): void
    {
        SystemSetting::set('gemini_api_key', 'Rezapahlevy17', 'Gemini API Key');

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Kunci di database tidak valid')
            ->assertDontSee('Rezapahlevy17');
    }
}
