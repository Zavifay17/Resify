<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
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
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'nim' => 'nullable|string|max:20',
            'campus' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'nim' => $validated['nim'] ?? '12210889',
            'campus' => $validated['campus'] ?? 'Universitas Bina Sarana Informatika',
            'diet_preferences' => ['Halal', 'Seimbang'],
            'cooking_skill' => 'Menengah',
        ]);

        Auth::login($user);
        return redirect()->route('dashboard')->with('success', 'Pendaftaran berhasil! Selamat datang di Resepify.');
    }

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

