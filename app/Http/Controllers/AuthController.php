<?php

namespace App\Http\Controllers;

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
            return redirect()->route('dashboard')->with('success', 'Login otomatis sebagai Mahasiswa BSI Tugas Akhir (' . $user->name . ')');
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
     * Kredensial Google dianggap siap bila Client ID dan Secret sudah diisi.
     */
    public function googleSiap(): bool
    {
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
     * Arahkan pengguna ke halaman autentikasi Google.
     */
    public function googleRedirect()
    {
        if (!$this->googleSiap()) {
            return redirect()->route('login')->with('error', 'Login Google belum aktif. Isi GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di berkas .env, lalu jalankan php artisan config:clear.');
        }

        try {
            return Socialite::driver('google')
                ->scopes(['openid', 'email', 'profile'])
                ->redirectUrl($this->callbackGoogle())
                ->redirect();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'Google sedang tidak dapat dihubungi. Silakan masuk dengan email atau ulangi beberapa saat lagi.');
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
            return redirect()->route('login')->with('error', 'Gagal masuk dengan Google. Silakan coba lagi.');
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
            'nim' => 'nullable|string|max:30',
            'cooking_skill' => 'required|string',
            'diet_preferences' => 'nullable|array',
            'allergies' => 'nullable|string',
        ]);

        $user->update([
            'name' => $validated['name'],
            'nim' => $validated['nim'],
            'cooking_skill' => $validated['cooking_skill'],
            'diet_preferences' => $validated['diet_preferences'] ?? [],
            'allergies' => $validated['allergies'],
        ]);

        return back()->with('success', 'Profil dan preferensi kuliner berhasil diperbarui.');
    }
}

