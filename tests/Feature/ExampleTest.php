<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_halaman_utama_mengarahkan_tamu_ke_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_halaman_utama_mengarahkan_pengguna_masuk_ke_dashboard(): void
    {
        $this->actingAs(User::query()->firstOrFail())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }
}
