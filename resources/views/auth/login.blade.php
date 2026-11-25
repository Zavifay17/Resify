@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<div class="auth-shell">
    <aside class="auth-aside">
        <div>
            <div class="flex-center mb-16">
                <span class="brand-mark"><i data-lucide="chef-hat"></i></span>
                <span class="brand-name" style="color:#fff">Resepify</span>
            </div>

            <h1>Resep dari bahan yang sudah ada di kulkas</h1>
            <p class="mt-16">
                Mencatat stok dapur dan mendapat resep yang cocok dengan bahan yang Anda punya.
            </p>

            <ul class="mt-24">
                <li>
                    <i data-lucide="refrigerator"></i>
                    <span>Inventaris bahan dapur dengan tanggal kedaluwarsa.</span>
                </li>
                <li>
                    <i data-lucide="camera"></i>
                    <span>Cukup foto bahan, sistem mengenali isinya.</span>
                </li>
                <li>
                    <i data-lucide="utensils-crossed"></i>
                    <span>Resep disarankan dari bahan yang hampir habis.</span>
                </li>
                <li>
                    <i data-lucide="book-open"></i>
                    <span>Referensi kuliner Nusantara untuk bahan lokal.</span>
                </li>
            </ul>
        </div>

        <div class="foot">
            Sistem Inventaris Dapur &amp; Generator Resep Berbasis AI<br>
            Bahan terpakai maksimal, sisa makanan minimal.
            <div class="credit">
                Latar foto bahan masakan:
                <a href="https://unsplash.com/@trapnation" target="_blank" rel="noopener">Andre Benz</a> / Unsplash, lisensi bebas.
            </div>
        </div>
    </aside>

    <div class="auth-main">
        <div class="auth-card">
            <h2>Masuk</h2>
            <p class="text-muted text-sm">Pakai email Anda untuk lanjut ke dashboard.</p>

            <form action="{{ route('login.post') }}" method="POST" class="mt-24">
                @csrf

                <div class="field">
                    <label class="label" for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input" required autofocus
                        value="{{ old('email') }}" placeholder="nama@email.com">
                    @error('email')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label class="label" for="password">Kata sandi <span class="req">*</span></label>
                    <input type="password" id="password" name="password" class="input" required
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    <div class="hint">Belum punya akun? Pakai <strong>Login cepat</strong> untuk mencoba demo.</div>
                    @error('password')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex-between mb-16">
                    <label class="check">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i data-lucide="log-in"></i> Masuk
                </button>
            </form>

            @if($googleSiap)
                <div class="divider-label">atau masuk dengan</div>

                <a href="{{ route('google.redirect') }}" class="btn-google-oauth">
                    <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Masuk dengan Google</span>
                </a>
            @endif

            <div class="divider-label">atau login demo</div>

            <a href="{{ route('quick-login') }}" class="btn btn-secondary btn-block">
                <i data-lucide="zap"></i> Login cepat (demo)
            </a>

            <p class="text-center text-sm text-muted mt-24 mb-0">
                Belum punya akun? <a href="{{ route('register') }}">Daftar</a>
            </p>
        </div>
    </div>
</div>
@endsection
