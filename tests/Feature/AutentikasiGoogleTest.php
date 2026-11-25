<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as PenggunaSocialite;
use Tests\TestCase;

class AutentikasiGoogleTest extends TestCase
{
    use DatabaseTransactions;

    private function isiKredensialGoogle(): void
    {
        $this->hapusKredensialTersimpan();

        config([
            'services.google.client_id' => 'uji-uji-uji.apps.googleusercontent.com',
            'services.google.client_secret' => 'rahasia-uji',
            'services.google.redirect' => 'http://localhost:8000/auth/google/callback',
        ]);
    }

    private function kosongkanKredensialGoogle(): void
    {
        $this->hapusKredensialTersimpan();

        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);
    }

    private function hapusKredensialTersimpan(): void
    {
        SystemSetting::whereIn('key', ['google_client_id', 'google_client_secret'])->delete();
    }

    private function akunGoogle(string $id, string $email, string $nama): PenggunaSocialite
    {
        return (new PenggunaSocialite())->map([
            'id' => $id,
            'name' => $nama,
            'email' => $email,
            'avatar' => 'https://lh3.googleusercontent.com/a/' . $id,
        ])->setRaw(['id' => $id, 'email' => $email]);
    }

    public function test_halaman_daftar_memakai_wording_umum_tanpa_identitas_kampus(): void
    {
        $this->get(route('register'))->assertOk()
            ->assertDontSee('name="nim"', false)
            ->assertDontSee('name="campus"', false)
            ->assertDontSee('Universitas Bina Sarana Informatika', false)
            ->assertDontSee('Tugas Akhir Program Studi', false)
            ->assertDontSee('GOOGLE_CLIENT_ID', false)
            ->assertSee('Sistem Inventaris Dapur &amp; Generator Resep Berbasis AI', false)
            ->assertSee('Mencatat stok dapur dan mendapat resep yang cocok dengan bahan yang Anda punya.', false);
    }

    public function test_halaman_masuk_tidak_menampilkan_identitas_kampus(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertDontSee('email kampus', false)
            ->assertDontSee('bsi.ac.id', false)
            ->assertDontSee('Universitas Bina Sarana Informatika', false)
            ->assertSee('Pakai email Anda untuk lanjut ke dashboard.', false);
    }

    public function test_pendaftaran_manual_tidak_lagi_memakai_nim(): void
    {
        $data = [
            'name' => 'Mahasiswa Uji Google',
            'email' => 'uji.google.' . now()->timestamp . '@bsi.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ];

        $this->post(route('register.post'), $data)->assertRedirect(route('dashboard'));

        $user = User::where('email', $data['email'])->firstOrFail();

        $this->assertNull($user->nim);
        $this->assertNull($user->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_tombol_google_disembunyikan_saat_kredensial_kosong(): void
    {
        $this->kosongkanKredensialGoogle();

        $this->get(route('login'))->assertOk()->assertDontSee('btn-google-oauth', false);
        $this->get(route('register'))->assertOk()->assertDontSee('btn-google-oauth', false);
    }

    public function test_tombol_google_muncul_saat_kredensial_diisi(): void
    {
        $this->isiKredensialGoogle();

        $this->get(route('register'))->assertOk()->assertSee('btn-google-oauth', false);
        $this->get(route('login'))->assertOk()->assertSee('btn-google-oauth', false);
    }

    public function test_kredensial_google_yang_disimpan_lewat_pengaturan_langsung_aktif(): void
    {
        $this->kosongkanKredensialGoogle();

        $this->actingAs(User::firstOrFail())->post(route('settings.update'), [
            'gemini_model' => 'gemini-3.5-flash',
            'google_client_id' => 'klien-pengaturan.apps.googleusercontent.com',
            'google_client_secret' => 'rahasia-pengaturan',
        ])->assertRedirect(route('settings.index'));

        $this->assertSame('klien-pengaturan.apps.googleusercontent.com', SystemSetting::get('google_client_id'));

        // tanpa menyentuh .env, tombol Google sudah muncul dan redirect memakai klien ini
        $this->post(route('logout'));

        $this->get(route('login'))->assertOk()->assertSee('btn-google-oauth', false);
        $this->get(route('google.redirect'))
            ->assertRedirectContains('client_id=klien-pengaturan.apps.googleusercontent.com');
    }

    public function test_alur_google_tidak_error_saat_belum_dikonfigurasi(): void
    {
        $this->kosongkanKredensialGoogle();

        $this->get(route('google.redirect'))->assertRedirect(route('login'));
        $this->get(route('google.callback'))->assertRedirect(route('login'));
    }

    public function test_tautan_google_mengarah_ke_halaman_konsent(): void
    {
        $this->isiKredensialGoogle();

        $this->get(route('google.redirect'))
            ->assertRedirectContains('accounts.google.com')
            ->assertRedirectContains('client_id=uji-uji-uji.apps.googleusercontent.com');
    }

    public function test_akun_google_dicatat_dan_bisa_masuk_lagi(): void
    {
        $this->isiKredensialGoogle();
        $email = 'dosen.uji@gmail.com';

        Socialite::shouldReceive('driver->scopes->redirectUrl->user')
            ->andReturn($this->akunGoogle('google-id-8899', $email, 'Dosen Uji'));

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $user = User::where('email', $email)->firstOrFail();
        $this->assertSame('google-id-8899', $user->google_id);
        $this->assertNull($user->password);
        $this->assertStringContainsString('googleusercontent.com', (string) $user->avatar);
        $this->assertAuthenticatedAs($user);

        // Masuk kedua kali memakai akun Google yang sama tidak membuat akun baru
        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame(1, User::where('google_id', 'google-id-8899')->count());
    }

    public function test_akun_lama_ditautkan_ke_google_berdasarkan_email(): void
    {
        $this->isiKredensialGoogle();

        $lama = User::create([
            'name' => 'Akun Lama',
            'email' => 'sudah.punya@gmail.com',
            'password' => bcrypt('rahasia123'),
            'diet_preferences' => ['Halal'],
            'cooking_skill' => 'Pemula',
        ]);

        Socialite::shouldReceive('driver->scopes->redirectUrl->user')
            ->andReturn($this->akunGoogle('google-id-5566', 'sudah.punya@gmail.com', 'Akun Lama'));

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame('google-id-5566', $lama->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'sudah.punya@gmail.com')->count());
    }
}
