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
                Resepify mencatat isi dapur Anda, lalu menyusun resep dari bahan yang ada
                supaya tidak banyak yang terbuang.
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
            Tugas Akhir RPL &mdash; Universitas Bina Sarana Informatika
        </div>
    </aside>

    <div class="auth-main">
        <div class="auth-card">
            <h2>Masuk</h2>
            <p class="text-muted text-sm">Pakai email kampus Anda untuk lanjut ke dashboard.</p>

            <form action="{{ route('login.post') }}" method="POST" class="mt-24">
                @csrf

                <div class="field">
                    <label class="label" for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input" required autofocus
                        value="{{ old('email', 'mahasiswa@bsi.ac.id') }}" placeholder="nama@bsi.ac.id">
                    @error('email')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label class="label" for="password">Kata sandi <span class="req">*</span></label>
                    <input type="password" id="password" name="password" class="input" required
                        value="password123" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    <div class="hint">Akun demo: mahasiswa@bsi.ac.id / password123</div>
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

            <div class="divider-label">atau</div>

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
