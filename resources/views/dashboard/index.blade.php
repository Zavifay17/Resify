@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')
<div class="stack-20">

    {{-- Pembuka halaman: sapaan, status sistem, dan aksi cepat --}}
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-main">
                <div class="page-head-meta">
                    <span class="eyebrow"><i data-lucide="chef-hat"></i> Dashboard Inventaris Dapur</span>
                    <span class="chip chip-brand">
                        <i data-lucide="circle-check-big"></i> Sistem inventaris aktif
                    </span>
                    <span class="chip chip-navy">
                        <i data-lucide="graduation-cap"></i> {{ $campusName }}
                    </span>
                </div>
                <h1>Halo, {{ $studentName }}</h1>
                <p class="sub">
                    Resepify memantau stok kulkas Anda dan menyusun resep Nusantara dari bahan
                    yang tersedia, supaya tidak ada makanan yang terbuang.
                </p>
            </div>
            <div class="page-head-actions">
                <a href="{{ route('recipes.generator') }}" class="btn btn-primary">
                    <i data-lucide="sparkles"></i> Buka Generator Resep
                </a>
                <a href="{{ route('vision.index') }}" class="btn btn-secondary">
                    <i data-lucide="camera"></i> Pindai Foto Kulkas
                </a>
            </div>
        </div>
    </div>

    {{-- Ringkasan angka utama --}}
    <div class="grid grid-4">
        <div class="stat accent-brand">
            <div class="label"><i data-lucide="layers"></i> Stok bahan</div>
            <div class="value">{{ $stats['total_items'] }}<span class="unit">item</span></div>
            <div class="foot">{{ $stats['safe_count'] }} masih segar &middot; {{ $stats['expiring_count'] }} perlu dimasak</div>
        </div>

        <div class="stat accent-warn">
            <div class="label"><i data-lucide="alarm-clock"></i> Perlu dimasak segera</div>
            <div class="value">{{ $stats['expiring_count'] }}<span class="unit">sisa &le; 3 hari</span></div>
            <div class="foot">Diprioritaskan lebih dulu oleh generator resep</div>
        </div>

        <div class="stat accent-info">
            <div class="label"><i data-lucide="book-open"></i> Resep tersimpan</div>
            <div class="value">{{ $recentRecipes->count() }}<span class="unit">resep</span></div>
            <div class="foot">Tersimpan di cache MySQL &amp; cache Laravel</div>
        </div>

        <div class="stat">
            <div class="label"><i data-lucide="cpu"></i> Total token AI</div>
            <div class="value">{{ number_format($totalTokens) }}<span class="unit">token</span></div>
            <div class="foot">{{ $totalAiHits }} panggilan API &middot; model Gemini Flash</div>
        </div>
    </div>

    {{-- Peringatan bahan mendekati kedaluwarsa --}}
    @if($expiringItems->isNotEmpty())
    <div class="card">
        <div class="card-head">
            <h2>Peringatan Kedaluwarsa</h2>
            <span class="hint">{{ $expiringItems->count() }} bahan harus diolah dalam 3 hari ke depan</span>
        </div>
        <div class="card-body">
            <div class="flex-between">
                <div class="flex-1 stack-12">
                    <p class="mb-0 text-sm text-muted">
                        Bahan di bawah ini mendekati tanggal batas konsumsi. Tekan tombol
                        penyelamat agar generator resep menyusun masakan dari bahan-bahan
                        ini supaya tidak terbuang.
                    </p>
                    <div class="flex flex-wrap gap-8">
                        @foreach($expiringItems as $expItem)
                            @php
                                $badge = $expItem->status_badge;
                                $badgeMap = ['danger' => 'chip-danger', 'warning' => 'chip-warn', 'success' => 'chip-ok'];
                                $chipClass = $badgeMap[$badge['severity']] ?? 'chip';
                            @endphp
                            <span class="chip {{ $chipClass }}" title="{{ $badge['label'] }}">
                                <span style="font-size:14px; margin-right:4px">{{ $expItem->display_icon }}</span>
                                {{ $expItem->item_name }}
                                &middot; {{ $expItem->quantity }} {{ $expItem->unit }}
                                &middot; {{ $expItem->expiry_label }}
                            </span>
                        @endforeach
                    </div>
                </div>
                <form action="{{ route('recipes.rescue') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i data-lucide="zap"></i> Selamatkan Bahan
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Pintu masuk fitur utama --}}
    <div class="grid grid-3">
        <div class="card">
            <div class="card-head">
                <h3><i data-lucide="camera"></i> Deteksi Foto Bahan</h3>
                <span class="chip">Vision AI</span>
            </div>
            <div class="card-body tight col">
                <p class="mb-0 text-sm text-muted">
                    Foto isi kulkas atau meja dapur, lalu Gemini Vision mengenali sayur,
                    bumbu, dan protein untuk dimasukkan ke inventaris secara otomatis.
                </p>
                <a href="{{ route('vision.index') }}" class="btn btn-secondary btn-sm btn-block" style="margin-top:auto">
                    <i data-lucide="scan-line"></i> Mulai Pemindaian
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3><i data-lucide="utensils-crossed"></i> Generator Resep</h3>
                <span class="chip">Zero-Waste</span>
            </div>
            <div class="card-body tight col">
                <p class="mb-0 text-sm text-muted">
                    Pilih bahan yang tersisa di kulkas. Sistem menyusun langkah masak,
                    takaran bumbu, dan perkiraan nutrisi untuk setiap resep.
                </p>
                <a href="{{ route('recipes.generator') }}" class="btn btn-secondary btn-sm btn-block" style="margin-top:auto">
                    <i data-lucide="arrow-right"></i> Susun Resep
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3><i data-lucide="cloud"></i> Sinkronisasi Supabase</h3>
                @if($supabaseConfigured)
                    <span class="chip chip-ok">Terhubung</span>
                @else
                    <span class="chip">Mode lokal</span>
                @endif
            </div>
            <div class="card-body tight col">
                <p class="mb-0 text-sm text-muted">
                    Menyelaraskan data stok dapur dan menyimpan foto bahan ke cloud
                    Supabase (PostgREST + Storage).
                </p>
                <form action="{{ route('inventory.sync-supabase') }}" method="POST" style="margin-top:auto">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm btn-block">
                        <i data-lucide="refresh-cw"></i> Sinkronkan Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Resep rekomendasi dari koleksi --}}
    <div class="stack-16">
        <div class="flex-between">
            <div>
                <h2 class="mb-0">Rekomendasi Kuliner Nusantara</h2>
                <span class="hint text-sm text-muted">Masakan tradisional, menu kafe, dan street food yang terakhir masuk ke koleksi</span>
            </div>
            <a href="{{ route('recipes.index') }}" class="btn btn-ghost btn-sm">
                Lihat Semua Resep <i data-lucide="arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-3">
            @foreach($recentRecipes as $recipe)
                <div class="card">
                    <div class="card-head">
                        <span class="chip chip-brand truncate" style="max-width:72%" title="{{ $recipe->cuisine_type }}">{{ $recipe->cuisine_type }}</span>
                        <span class="hint">
                            <i data-lucide="clock" style="width:13px;height:13px"></i>
                            <span>{{ $recipe->cooking_time_minutes }} menit</span>
                        </span>
                    </div>
                    <div class="card-body tight col">
                        <div class="stack-8">
                            <h3 class="mb-0">{{ $recipe->title }}</h3>
                            <p class="mb-0 text-sm text-muted">{{ $recipe->description }}</p>
                        </div>

                        @if($recipe->nutrition_facts)
                            <div class="flex flex-wrap gap-8" style="margin-top:auto;padding-top:4px">
                                <span class="chip">Kalori: {{ $recipe->nutrition_facts['calories_kcal'] ?? 300 }} kkal</span>
                                <span class="chip">Protein: {{ $recipe->nutrition_facts['protein_g'] ?? 20 }} g</span>
                                <span class="chip">Karbo: {{ $recipe->nutrition_facts['carbs_g'] ?? 30 }} g</span>
                            </div>
                        @endif
                    </div>
                    <div class="card-foot flex-between">
                        <span class="text-xs text-muted flex-center gap-4">
                            <i data-lucide="users" style="width:13px;height:13px"></i>
                            <span>{{ $recipe->servings }} porsi</span>
                        </span>
                        <a href="{{ route('recipes.show', $recipe->id) }}" class="text-sm strong flex-center gap-4">
                            <span>Buka resep</span> <i data-lucide="chevron-right" style="width:14px;height:14px"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Riwayat eksekusi AI --}}
    <div class="card">
        <div class="card-head">
            <div>
                <h2>Riwayat Eksekusi AI</h2>
                <span class="hint">Catatan pemakaian API Gemini (token dan latensi) untuk evaluasi pengujian RPL</span>
            </div>
            <a href="{{ route('logs.index') }}" class="btn btn-ghost btn-sm">Lihat Log Penuh</a>
        </div>
        <div class="card-body flush">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fitur AI</th>
                            <th>Model Digunakan</th>
                            <th>Ringkasan Prompt</th>
                            <th class="num">Token</th>
                            <th class="num">Latensi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td>
                                    <span class="dot dot-warn" style="margin-right:8px"></span>
                                    {{ $log->action_label }}
                                </td>
                                <td class="text-muted">{{ $log->model_used }}</td>
                                <td class="text-muted" style="max-width:280px"><div class="truncate">{{ $log->prompt_summary }}</div></td>
                                <td class="num strong">{{ $log->total_tokens ?: '-' }}</td>
                                <td class="num">{{ $log->execution_time_ms }} ms</td>
                                <td>
                                    <span class="chip {{ $log->status === 'success' ? 'chip-ok' : ($log->status === 'fallback' ? 'chip-warn' : 'chip-danger') }}">
                                        {{ strtoupper($log->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Belum ada riwayat eksekusi AI. Mulai buat resep untuk mencatat pemakaian token.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
