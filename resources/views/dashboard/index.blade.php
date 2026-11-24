@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="stack-24">

    {{-- Sapaan Pengguna & Aksi Cepat --}}
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-main">
                <span class="eyebrow"><i data-lucide="chef-hat"></i> Manajemen Dapur &amp; Resep</span>
                <h1>Halo, {{ $studentName }} 👋</h1>
                <p class="sub">
                    Pantau persediaan bahan makanan di kulkas Anda dan racik hidangan lezat secara otomatis tanpa ada makanan yang terbuang.
                </p>
            </div>
            <div class="page-head-actions">
                <a href="{{ route('recipes.generator') }}" class="btn btn-primary">
                    <i data-lucide="sparkles"></i> Buat Resep Baru
                </a>
                <a href="{{ route('vision.index') }}" class="btn btn-secondary">
                    <i data-lucide="camera"></i> Deteksi Foto Kulkas
                </a>
            </div>
        </div>
    </div>

    {{-- 3 Ringkasan Angka Utama (Sederhana, Bersih, Fokus pada Makanan) --}}
    <div class="grid grid-3">
        <div class="stat accent-brand">
            <div class="label"><i data-lucide="layers"></i> Total Stok Bahan</div>
            <div class="value">{{ $stats['total_items'] }}<span class="unit">item</span></div>
            <div class="foot">{{ $stats['safe_count'] }} bahan kondisi aman &middot; {{ $stats['expiring_count'] }} perlu segera dimasak</div>
        </div>

        <div class="stat accent-warn">
            <div class="label"><i data-lucide="alarm-clock"></i> Perlu Dimasak Segera</div>
            <div class="value">{{ $stats['expiring_count'] }}<span class="unit">bahan (&le; 3 hari)</span></div>
            <div class="foot">Bahan yang mendekati batas kadaluarsa untuk diselamatkan</div>
        </div>

        <div class="stat accent-info">
            <div class="label"><i data-lucide="book-open"></i> Koleksi Resep Masakan</div>
            <div class="value">{{ $recentRecipes->count() }}<span class="unit">resep siap saji</span></div>
            <div class="foot">Rekomendasi hidangan Nusantara praktis untuk Anda</div>
        </div>
    </div>

    {{-- Peringatan Kedaluwarsa (Hanya tampil jika ada bahan darurat) --}}
    @if($expiringItems->isNotEmpty())
    <div class="card" style="border-left: 4px solid var(--warn);">
        <div class="card-head">
            <div>
                <h2><i data-lucide="alert-triangle" style="width:18px;height:18px;vertical-align:-2px;color:var(--warn)"></i> Peringatan Batas Konsumsi</h2>
                <span class="hint">{{ $expiringItems->count() }} bahan sebaiknya segera diolah agar tidak terbuang</span>
            </div>
            <form action="{{ route('recipes.rescue') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="zap"></i> Selamatkan Bahan Ini
                </button>
            </form>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap gap-8">
                @foreach($expiringItems as $expItem)
                    @php
                        $badge = $expItem->status_badge;
                        $badgeMap = ['danger' => 'chip-danger', 'warning' => 'chip-warn', 'success' => 'chip-ok'];
                        $chipClass = $badgeMap[$badge['severity']] ?? 'chip';
                    @endphp
                    <span class="chip {{ $chipClass }}" title="{{ $badge['label'] }}">
                        <span style="font-size:15px; margin-right:4px">{{ $expItem->display_icon }}</span>
                        <strong>{{ $expItem->item_name }}</strong>
                        &middot; {{ $expItem->quantity }} {{ $expItem->unit }}
                        &middot; {{ $expItem->expiry_label }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Rekomendasi Resep Masakan (Ringkas: 3 Kartu Elegan) --}}
    <div class="stack-16">
        <div class="flex-between">
            <div>
                <h2 class="mb-0">Rekomendasi Resep Pilihan</h2>
                <span class="hint text-sm text-muted">Inspirasi menu hidangan lezat dan hemat dari bahan yang ada</span>
            </div>
            <a href="{{ route('recipes.index') }}" class="btn btn-ghost btn-sm">
                Lihat Semua Resep <i data-lucide="arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-3">
            @forelse($recentRecipes as $recipe)
                <div class="card">
                    <div class="card-head">
                        <span class="chip chip-brand truncate" style="max-width:70%">{{ $recipe->cuisine_type }}</span>
                        <span class="hint flex-center gap-4">
                            <i data-lucide="clock" style="width:13px;height:13px"></i>
                            <span>{{ $recipe->cooking_time_minutes }} mnt</span>
                        </span>
                    </div>
                    <div class="card-body tight col">
                        <div class="stack-8">
                            <h3 class="mb-0">{{ $recipe->title }}</h3>
                            <p class="mb-0 text-sm text-muted" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                                {{ $recipe->description }}
                            </p>
                        </div>

                        @if($recipe->nutrition_facts)
                            <div class="flex flex-wrap gap-6 mt-8" style="margin-top:auto">
                                <span class="chip text-xs">{{ $recipe->nutrition_facts['calories_kcal'] ?? 300 }} kkal</span>
                                <span class="chip text-xs">Prot: {{ $recipe->nutrition_facts['protein_g'] ?? 20 }}g</span>
                                <span class="chip text-xs">Karbo: {{ $recipe->nutrition_facts['carbs_g'] ?? 30 }}g</span>
                            </div>
                        @endif
                    </div>
                    <div class="card-foot flex-between">
                        <span class="text-xs text-muted flex-center gap-4">
                            <i data-lucide="users" style="width:13px;height:13px"></i>
                            <span>{{ $recipe->servings }} porsi</span>
                        </span>
                        <a href="{{ route('recipes.show', $recipe->id) }}" class="text-sm strong flex-center gap-4" style="color:var(--brand-dark)">
                            <span>Buka resep</span> <i data-lucide="arrow-right" style="width:14px;height:14px"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="card" style="grid-column: 1 / -1; padding: 24px; text-align: center;">
                    <p class="text-muted mb-8">Belum ada resep tersimpan.</p>
                    <a href="{{ route('recipes.generator') }}" class="btn btn-primary btn-sm">Buat Resep Pertama Sekarang</a>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
