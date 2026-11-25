<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login', ['googleSiap' => $this->googleSiap()]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi tidak cocok.',
        ])->onlyInput('email');
    }

    public function quickLogin()
    {
        $user = User::first();
        if ($user) {
            Auth::login($user);
            return redirect()->route('dashboard')->with('success', 'Login otomatis ke akun demo (' . $user->name . ')');
        }
        return redirect()->route('login');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register', ['googleSiap' => $this->googleSiap()]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'diet_preferences' => ['Halal', 'Seimbang'],
            'cooking_skill' => 'Menengah',
        ]);

        Auth::login($user);
        return redirect()->route('dashboard')->with('success', 'Pendaftaran berhasil! Selamat datang di Resepify.');
    }

    // ===== Google OAuth =====

    /**
     * Kredensial boleh disimpan lewat halaman Pengaturan (tabel system_settings);
     * nilai itu mengalahkan isi .env tanpa perlu menulis ulang berkas .env.
     */
    private function muatKredensialGoogle(): void
    {
        $idLokal = (string) config('services.google.client_id', '');
        $secretLokal = (string) config('services.google.client_secret', '');
        $idDatabase = trim((string) SystemSetting::get('google_client_id', ''));
        $secretDatabase = trim((string) SystemSetting::get('google_client_secret', ''));

        // Nilai database dipakai hanya kalau bentuknya memang kredensial OAuth Google.
        // Selain itu (mis. kolom Client ID terisi alamat email) sistem kembali ke .env
        // supaya tombol Google tidak mengirim client_id yang ditolak Google.
        config([
            'services.google.client_id' => self::kredensialGoogleSah('id', $idDatabase) ? $idDatabase : $idLokal,
            'services.google.client_secret' => self::kredensialGoogleSah('secret', $secretDatabase) ? $secretDatabase : $secretLokal,
        ]);
    }

    /**
     * Bentuk baku kredensial OAuth client tipe Web application.
     */
    public static function kredensialGoogleSah(string $jenis, string $nilai): bool
    {
        $nilai = trim($nilai);

        if ($nilai === '' || strlen($nilai) > 200 || preg_match('/\s/', $nilai)) {
            return false;
        }

        return $jenis === 'id'
            ? preg_match('/^\d{6,20}-[a-z0-9]{10,60}\.apps\.googleusercontent\.com$/', $nilai) === 1
            : preg_match('/^GOCSPX-[A-Za-z0-9_\-]{16,80}$/', $nilai) === 1;
    }

    /**
     * Kredensial di database yang perlu diperbaiki (dipakai halaman Pengaturan).
     */
    public function kredensialGooglePerluDiperbaiki(): array
    {
        $lengkap = [];

        foreach (['google_client_id' => 'id', 'google_client_secret' => 'secret'] as $key => $jenis) {
            $nilai = trim((string) SystemSetting::get($key, ''));

            if ($nilai !== '' && !self::kredensialGoogleSah($jenis, $nilai)) {
                $lengkap[] = $key === 'google_client_id' ? 'OAuth Client ID' : 'OAuth Client Secret';
            }
        }

        return $lengkap;
    }

    /**
     * Kredensial Google dianggap siap bila Client ID dan Secret sudah diisi.
     */
    public function googleSiap(): bool
    {
        $this->muatKredensialGoogle();

        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    /**
     * URI callback dipakai otomatis bila GOOGLE_REDIRECT_URI belum diisi,
     * selama masih sama dengan yang didaftarkan di Google Cloud Console.
     */
    private function callbackGoogle(): string
    {
        return (string) (config('services.google.redirect') ?: url('/auth/google/callback'));
    }

    /**
     * Pesan galat Google diterjemahkan ke langkah perbaikan yang konkret,
     * supaya pengguna awam tahu bagian mana yang salah isi.
     */
    private function pesanErrorGoogle(\Throwable $e): string
    {
        $pesan = strtolower($e->getMessage());

        if (str_contains($pesan, 'redirect_uri_mismatch') || str_contains($pesan, 'redirect uri')) {
            return 'Authorized redirect URI di Google Cloud tidak sama persis dengan alamat ini: '
                . $this->callbackGoogle() . '. Salin ulang alamat tersebut pada kolom Authorized redirect URIs lalu simpan.';
        }

        if (str_contains($pesan, 'invalid_client') || str_contains($pesan, 'unauthorized_client')) {
            return 'Google tidak mengenali Client ID yang dipakai. Client ID OAuth selalu berbentuk '
                . 'angka-karakter.apps.googleusercontent.com (bukan alamat email) dan Client Secret selalu diawali GOCSPX-. '
                . 'Tempel ulang keduanya pada Pengaturan > Login Google, pastikan berasal dari client tipe Web application di project yang sama.'
                . (str_contains($pesan, 'project id') ? ' Google juga menandai client ini tidak tertaut ke project, jadi buat client baru di project yang sama lalu tempel kredensialnya ulang.' : '');
        }

        if (str_contains($pesan, 'access_denied') || str_contains($pesan, 'invalid_grant') || str_contains($pesan, 'consent')) {
            return 'Google menolak akses. Bila status aplikasi masih Testing, tambahkan alamat email Anda pada '
                . 'Google Auth Platform > Audience > Test users, lalu coba masuk lagi.';
        }

        if ($e instanceof \Laravel\Socialite\Two\InvalidStateException) {
            return 'Sesi verifikasi Google berakhir (halaman dibuka terlalu lama atau tab diganti). Silakan klik tombol Google sekali lagi.';
        }

        return 'Google sedang tidak dapat dihubungi. Silakan masuk dengan email atau ulangi beberapa saat lagi.';
    }

    /**
     * Arahkan pengguna ke halaman autentikasi Google.
     */
    public function googleRedirect()
    {
        if (!$this->googleSiap()) {
            return redirect()->route('login')->with('error', 'Login Google belum aktif. Buat OAuth Client ID di Google Cloud Console, lalu isi Client ID dan Secret pada Pengaturan > Login Google.');
        }

        try {
            return Socialite::driver('google')
                ->scopes(['openid', 'email', 'profile'])
                ->redirectUrl($this->callbackGoogle())
                ->redirect();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', $this->pesanErrorGoogle($e));
        }
    }

    /**
     * Tangani callback dari Google setelah pengguna menyetujui akses.
     */
    public function googleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->scopes(['openid', 'email', 'profile'])
                ->redirectUrl($this->callbackGoogle())
                ->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', $this->pesanErrorGoogle($e));
        }

        $email = $googleUser->getEmail();

        if (!$email) {
            return redirect()->route('login')->with('error', 'Akun Google tersebut tidak membagikan alamat email, jadi sistem tidak dapat mencocokkan datanya.');
        }

        // Cari user berdasarkan google_id atau email
        $user = User::where('google_id', $googleUser->getId())
                    ->orWhere('email', $email)
                    ->first();

        if ($user) {
            $perbarui = [];

            // Tautkan google_id pada akun yang sebelumnya mendaftar manual
            if (!$user->google_id) {
                $perbarui['google_id'] = $googleUser->getId();
            }

            if (!$user->avatar && $googleUser->getAvatar()) {
                $perbarui['avatar'] = $googleUser->getAvatar();
            }

            if ($perbarui !== []) {
                $user->update($perbarui);
            }
        } else {
            // Buat akun baru dari data Google
            $user = User::create([
                'name'             => $googleUser->getName() ?: $email,
                'email'            => $email,
                'google_id'        => $googleUser->getId(),
                'avatar'           => $googleUser->getAvatar(),
                'password'         => null,
                'diet_preferences' => ['Halal', 'Seimbang'],
                'cooking_skill'    => 'Menengah',
            ]);
        }

        Auth::login($user, true);
        return redirect()->route('dashboard')->with('success', 'Selamat datang, ' . $user->name . '! Anda berhasil masuk dengan akun Google.');
    }

    // ===== Logout =====

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar.');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cooking_skill' => 'required|string',
            'diet_preferences' => 'nullable|array',
            'allergies' => 'nullable|string',
        ]);

        $user->update([
            'name' => $validated['name'],
            'cooking_skill' => $validated['cooking_skill'],
            'diet_preferences' => $validated['diet_preferences'] ?? [],
            'allergies' => $validated['allergies'],
        ]);

        return back()->with('success', 'Profil dan preferensi kuliner berhasil diperbarui.');
    }
}

