@extends('layouts.app')

@section('title', 'Daftar akun baru')

@section('content')
<div class="auth-shell">
    <aside class="auth-aside">
        <div>
            <div class="flex-center mb-16">
                <span class="brand-mark"><i data-lucide="chef-hat"></i></span>
                <span class="brand-name" style="color:#fff">Resepify</span>
            </div>

            <h1>Kelola dapur tanpa bahan terbuang</h1>
            <p class="mt-16">
                Daftar untuk mulai mencatat stok dapur dan mendapat resep yang cocok
                dengan bahan yang Anda punya.
            </p>

            <ul class="mt-24">
                <li>
                    <i data-lucide="refrigerator"></i>
                    <span>Pantau stok dan masa kedaluwarsa bahan.</span>
                </li>
                <li>
                    <i data-lucide="camera"></i>
                    <span>Deteksi bahan lewat foto, tanpa input manual.</span>
                </li>
                <li>
                    <i data-lucide="utensils-crossed"></i>
                    <span>Resep disesuaikan selera dan tingkat memasak Anda.</span>
                </li>
                <li>
                    <i data-lucide="clock"></i>
                    <span>Pengingat bahan yang perlu segera dihabiskan.</span>
                </li>
            </ul>
        </div>

        <div class="foot">
            Tugas Akhir RPL &mdash; Universitas Bina Sarana Informatika
        </div>
    </aside>

    <div class="auth-main">
        <div class="auth-card">
            <h2>Daftar akun baru</h2>
            <p class="text-muted text-sm">Isian NIM dan kampus boleh dikosongkan.</p>

            <form action="{{ route('register.post') }}" method="POST" class="mt-24">
                @csrf

                <div class="field">
                    <label class="label" for="name">Nama <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="input" required autofocus
                        value="{{ old('name') }}" placeholder="Contoh: Ahmad Fadhilah">
                    @error('name')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid grid-2">
                    <div class="field">
                        <label class="label" for="nim">NIM</label>
                        <input type="text" id="nim" name="nim" class="input"
                            value="{{ old('nim') }}" placeholder="12210889">
                        @error('nim')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="field">
                        <label class="label" for="campus">Kampus</label>
                        <input type="text" id="campus" name="campus" class="input"
                            value="{{ old('campus', 'Universitas BSI') }}" placeholder="Universitas BSI">
                        @error('campus')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input" required
                        value="{{ old('email') }}" placeholder="nama@bsi.ac.id">
                    @error('email')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label class="label" for="password">Kata sandi <span class="req">*</span></label>
                    <input type="password" id="password" name="password" class="input" required
                        placeholder="Minimal 6 karakter">
                    @error('password')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label class="label" for="password_confirmation">Ulangi kata sandi <span class="req">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="input" required
                        placeholder="Ketik ulang kata sandi">
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-16">
                    <i data-lucide="user-plus"></i> Daftar
                </button>
            </form>

            <p class="text-center text-sm text-muted mt-24 mb-0">
                Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
            </p>
        </div>
    </div>
</div>
@endsection
