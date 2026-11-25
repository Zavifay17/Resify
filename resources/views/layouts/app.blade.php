<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Resepify') - Resepify</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/resepify.css') }}?v=20261118_v2">

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('styles')
</head>
<body x-data="{ navOpen: false }">

<header class="site-header">
    <div class="wrap">
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="brand" title="Resepify - Beranda">
            <span class="brand-mark"><i data-lucide="chef-hat"></i></span>
            <span class="brand-name">Resepify</span>
        </a>

        @auth
        <div class="header-divider" aria-hidden="true"></div>

        <nav class="nav" aria-label="Navigasi Utama">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <i data-lucide="home"></i><span>Dashboard</span>
            </a>
            <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'is-active' : '' }}">
                <i data-lucide="refrigerator"></i><span>Inventaris</span>
            </a>
            <a href="{{ route('vision.index') }}" class="{{ request()->routeIs('vision.*') ? 'is-active' : '' }}">
                <i data-lucide="camera"></i><span>Deteksi Foto</span>
            </a>
            <a href="{{ route('recipes.generator') }}" class="{{ request()->routeIs('recipes.generator') ? 'is-active' : '' }}">
                <i data-lucide="sparkles"></i><span>Generator Resep</span>
            </a>
            <a href="{{ route('recipes.index') }}" class="{{ request()->routeIs('recipes.index') || request()->routeIs('recipes.show') ? 'is-active' : '' }}">
                <i data-lucide="book-open"></i><span>Kuliner Nusantara</span>
            </a>
            <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs.*') ? 'is-active' : '' }}">
                <i data-lucide="activity"></i><span>Log AI</span>
            </a>
        </nav>
        @endauth

        <div class="header-right">
            @auth
                <a href="{{ route('settings.index') }}" class="icon-btn" title="Pengaturan API &amp; Sistem">
                    <i data-lucide="settings"></i>
                </a>

                <div class="dropdown" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="dropdown-toggle" @click="open = !open">
                        <span class="avatar">{{ strtoupper(substr(optional(auth()->user())->name ?? 'M', 0, 2)) }}</span>
                        <span class="truncate nama-user" style="max-width:115px">{{ Str::limit(optional(auth()->user())->name ?? 'Pengguna', 16) }}</span>
                        <i data-lucide="chevron-down" style="width:14px;height:14px;color:var(--muted);flex:none"></i>
                    </button>

                    <div class="dropdown-menu" x-show="open" x-cloak>
                        <div class="who">
                            <div class="strong">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-muted">{{ auth()->user()->email }}</div>
                        </div>
                        <a href="{{ route('settings.index') }}"><i data-lucide="sliders-horizontal"></i> Pengaturan Gemini &amp; Supabase</a>
                        <a href="{{ route('inventory.print') }}" target="_blank" rel="noopener"><i data-lucide="printer"></i> Cetak Laporan Inventaris</a>
                        <form action="{{ route('logout') }}" method="POST" style="margin:0">
                            @csrf
                            <button type="submit" class="is-danger"><i data-lucide="log-out"></i> Keluar</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('register') }}" class="btn btn-ghost btn-sm">Daftar</a>
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Masuk</a>
            @endauth

            <button type="button" class="burger" @click="navOpen = !navOpen" aria-label="Buka menu">
                <i data-lucide="menu"></i>
            </button>
        </div>
    </div>

    @auth
    <div class="nav-mobile" :class="navOpen ? 'is-open' : ''">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Dashboard</a>
        <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'is-active' : '' }}">Inventaris Dapur</a>
        <a href="{{ route('vision.index') }}" class="{{ request()->routeIs('vision.*') ? 'is-active' : '' }}">Deteksi Foto Bahan</a>
        <a href="{{ route('recipes.generator') }}" class="{{ request()->routeIs('recipes.generator') ? 'is-active' : '' }}">Generator Resep</a>
        <a href="{{ route('recipes.index') }}" class="{{ request()->routeIs('recipes.index') || request()->routeIs('recipes.show') ? 'is-active' : '' }}">Kuliner Nusantara</a>
        <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs.*') ? 'is-active' : '' }}">Log Pemakaian AI</a>
        <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'is-active' : '' }}">Pengaturan</a>
    </div>
    @endauth
</header>

<main class="main">
    <div class="wrap">
        @if (session('success'))
            <div class="alert alert-ok mb-16" role="alert">
                <i data-lucide="check-circle-2"></i>
                <div class="body">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warn mb-16" role="alert">
                <i data-lucide="alert-triangle"></i>
                <div class="body">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger mb-16" role="alert">
                <i data-lucide="alert-circle"></i>
                <div class="body">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any() && ! request()->routeIs('login', 'register'))
            <div class="alert alert-danger mb-16" role="alert">
                <i data-lucide="x-circle"></i>
                <div class="body">
                    <strong>Periksa kembali isian formulir</strong>
                    {{ $errors->first() }}
                </div>
            </div>
        @endif

        @yield('content')
    </div>
</main>

<footer class="footer">
    <div class="wrap">
        <div>
            <div class="flex-center mb-8">
                <span class="brand-mark" style="width:26px;height:26px;border-radius:7px"><i data-lucide="chef-hat" style="width:14px;height:14px"></i></span>
                <span class="strong">Resepify</span>
            </div>
            <p class="fine mb-0">
                Sistem Inventaris Dapur &amp; Generator Resep Berbasis AI<br>
                Bahan terpakai maksimal, sisa makanan minimal.
            </p>
        </div>

        <div>
            <h5>Fitur</h5>
            <a href="{{ route('inventory.index') }}">Inventaris dapur</a>
            <a href="{{ route('vision.index') }}">Deteksi foto bahan</a>
            <a href="{{ route('recipes.generator') }}">Generator resep AI</a>
        </div>

        <div>
            <h5>Teknologi</h5>
            <a href="{{ route('logs.index') }}">Laravel 12 &amp; MySQL</a>
            <a href="{{ route('settings.index') }}">Gemini API</a>
            <a href="{{ route('settings.index') }}">Supabase Storage</a>
        </div>

        <div style="max-width:280px">
            <h5>Tentang Proyek</h5>
            <p class="fine mb-0">
                Dibuat untuk mengurangi makanan terbuang: sistem membaca stok kulkas,
                lalu menyusun resep dari bahan yang hampir kedaluwarsa.
            </p>
        </div>
    </div>
</footer>

<style>[x-cloak] { display: none !important; }</style>

<script>
    // Ikon diambil dari atribut data-lucide, jadi digambar ulang tiap kali Alpine memperbarui DOM.
    const renderIcons = () => window.lucide && lucide.createIcons();
    document.addEventListener('DOMContentLoaded', renderIcons);
    document.addEventListener('alpine:updated', renderIcons);

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Helper bersama supaya halaman tidak mengulang pola fetch + Swal yang sama.
    window.Resepify = {
        post(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data || {})
            }).then(async (res) => {
                const body = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(body.message || 'Permintaan gagal (kode ' + res.status + ').');
                return body;
            });
        },
        toast(title, text, icon) {
            if (window.Swal) {
                Swal.fire({
                    toast: true, position: 'top-end', icon: icon || 'success',
                    title: title, text: text || '', showConfirmButton: false,
                    timer: 2600, timerProgressBar: true
                });
            }
        },
        confirm(title, text) {
            if (!window.Swal) return Promise.resolve(window.confirm(title));
            return Swal.fire({
                title: title, text: text || 'Lanjutkan?', icon: 'question',
                showCancelButton: true, confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal', reverseButtons: true
            }).then((r) => r.isConfirmed);
        }
    };
</script>
@stack('scripts')
</body>
</html>
