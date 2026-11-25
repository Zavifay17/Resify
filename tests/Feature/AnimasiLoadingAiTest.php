<?php

namespace Tests\Feature;

use App\Models\RecipeCache;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AnimasiLoadingAiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_setiap_halaman_generate_ai_punya_animator_tunggu(): void
    {
        foreach (['/vision', '/recipes/generator'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('data-ai-wait', false)
                ->assertSee('ai-langkah', false)
                ->assertSee('ai-orbit', false);
        }
    }

    public function test_animator_tidak_lagi_memakai_spinner_polos(): void
    {
        $this->get('/vision')
            ->assertOk()
            ->assertDontSee('AI Sedang Mengenali Bahan Makanan...', false);

        $this->get('/recipes/generator')
            ->assertOk()
            ->assertDontSee('class="spinner" style="display:inline-block; border-color:rgba(234,88,12', false);
    }

    public function test_langkah_ai_tampil_berurutan_dengan_jeda_waktu(): void
    {
        $halaman = $this->get('/recipes/generator')->assertOk();

        $this->assertMatchesRegularExpression(
            '/--d: 0\.15s.*--d: 1\.65s.*--d: 3\.15s/s',
            $halaman->getContent()
        );
    }

    public function test_detail_resep_ikut_menampilkan_animator_bahan_pengganti(): void
    {
        $resep = RecipeCache::query()->firstOrFail();

        $this->get(route('recipes.show', $resep->id))
            ->assertOk()
            ->assertSee('Gemini AI Menalar Bahan Pengganti')
            ->assertSee('ai-wait ringkas', false);
    }

    public function test_pengatur_progres_realtime_dimuat_dan_terikat_ke_setiap_permintaan_ai(): void
    {
        $this->get('/vision')
            ->assertOk()
            ->assertSee('js/ai-wait.js', false)
            ->assertSee('data-ai-wait="deteksi"', false)
            ->assertSee('data-ai-titik="deteksi"', false)
            ->assertSee("AiTunggu?.jalankan('deteksi'", false)
            ->assertSee("AiTunggu?.geser('deteksi', 0.82", false)
            ->assertSee("AiTunggu?.selesai('deteksi')", false)
            ->assertSee("AiTunggu?.gagal('deteksi')", false);

        $this->get('/recipes/generator')
            ->assertOk()
            ->assertSee('data-ai-wait="racik"', false)
            ->assertSee('data-ai-wait="ganti"', false)
            ->assertSee("AiTunggu?.jalankan('racik'", false)
            ->assertSee("AiTunggu?.jalankan('ganti'", false);

        $resep = RecipeCache::query()->firstOrFail();
        $this->get(route('recipes.show', $resep->id))
            ->assertOk()
            ->assertSee("AiTunggu?.jalankan('ganti'", false);

        $this->get('/settings')
            ->assertOk()
            ->assertSee('data-ai-titik="kunci"', false)
            ->assertSee("AiTunggu?.jalankan('kunci'", false);
    }

    public function test_animator_mempunyai_kait_progres_realtime(): void
    {
        $isi = file_get_contents(base_path('public/js/ai-wait.js'));

        $this->assertNotFalse($isi);
        foreach (['window.AiTunggu', 'data-ai-bar', 'data-ai-jam', 'data-ai-fase', 'data-ai-langkah', 'is-nyala'] as $pengait) {
            $this->assertStringContainsString($pengait, $isi);
        }
    }
}
