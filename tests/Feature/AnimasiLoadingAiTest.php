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
}
