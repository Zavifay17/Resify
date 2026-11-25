@extends('layouts.app')

@section('title', 'Katalog Kuliner Nusantara')

@section('content')
@php
    // Nilai filter sama dengan yang dibaca RecipeController::index (q, cuisine, favorite).
    $cuisines = [
        'all' => 'Semua Resep',
        'tradisional' => 'Tradisional Nusantara',
        'modern' => 'Modern Kekinian',
        'street_food' => 'Street Food Kaki Lima',
        'rescue' => 'Zero-Waste Rescue'
    ];
    $activeCuisine = request('cuisine', 'all');
@endphp

<div class="stack-20">

    <!-- Judul katalog -->
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-main">
                <div class="page-head-meta">
                    <span class="eyebrow"><i data-lucide="book-open"></i> Katalog Resep Nusantara</span>
                    <span class="chip chip-brand">Koleksi Resep Cerdas</span>
                    <span class="chip">Total: {{ $recipes->total() }} Resep Tersimpan</span>
                </div>
                <h1>Katalog Kuliner Indonesia &amp; Kreasi AI</h1>
                <p class="sub">Jelajahi kelezatan masakan tradisional, modern kafe, hingga jajanan kaki lima yang telah disesuaikan dengan bahan dapur.</p>
            </div>

            <div class="page-head-actions">
                <a href="{{ route('recipes.generator') }}" class="btn btn-primary">
                    <i data-lucide="sparkles"></i>
                    Rancang Resep Baru
                </a>
            </div>
        </div>

        <!-- Form pencarian -->
        <form action="{{ route('recipes.index') }}" method="GET" class="search mt-16" style="max-width:620px">
            <i data-lucide="search"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari masakan (Ayam Geprek, Rendang, Soto, Seblak, Rawon)...">

            <button type="submit" class="btn btn-secondary btn-sm">Cari Resep</button>
            @if(request()->anyFilled(['q', 'cuisine', 'favorite']))
                <a href="{{ route('recipes.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="grid grid-side">

        <!-- Filter kategori & favorit -->
        <aside class="card filter-box">
            <div class="filter-group">
                <h4>Kategori masakan</h4>
                <div class="stack-4">
                    @foreach($cuisines as $key => $label)
                        <a href="{{ route('recipes.index', array_merge(request()->query(), ['cuisine' => $key])) }}"
                           class="check {{ $activeCuisine === $key && !request('favorite') ? 'strong' : '' }}"
                           style="{{ $activeCuisine === $key && !request('favorite') ? 'color:var(--brand-dark)' : 'color:var(--muted)' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="filter-group">
                <h4>Koleksi</h4>
                <a href="{{ route('recipes.index', ['favorite' => 1]) }}"
                   class="check {{ request()->boolean('favorite') ? 'strong' : '' }}"
                   style="{{ request()->boolean('favorite') ? 'color:var(--brand-dark)' : 'color:var(--muted)' }}">
                    <i data-lucide="heart" style="width:15px;height:15px;fill:{{ request()->boolean('favorite') ? 'currentColor' : 'none' }}"></i>
                    Favorit Saya
                </a>
            </div>
        </aside>

        <!-- Daftar hidangan -->
        <div class="card">
            <div class="card-body">
                <div class="dish-grid">
                    @forelse($recipes as $recipe)
                        <article class="dish">
                            <div class="dish-photo">
                                <img src="{{ $recipe->photo }}" alt="{{ $recipe->title }}" loading="lazy"
                                     onerror="this.onerror=null;this.src='{{ asset('images/katalog/default-nusantara.svg') }}'">
                                <span class="chip chip-brand tag-corner">{{ $recipe->cuisine_type }}</span>
                            </div>

                            <div class="dish-body">
                                <div class="flex-between gap-8">
                                    <h3 class="truncate" title="{{ $recipe->title }}">
                                        <a href="{{ route('recipes.show', $recipe->id) }}">{{ $recipe->title }}</a>
                                    </h3>

                                    <form action="{{ route('recipes.favorite', $recipe->id) }}" method="POST" style="margin:0">
                                        @csrf
                                        <button type="submit" class="icon-btn" title="Simpan ke Favorit" aria-label="Simpan ke Favorit">
                                            <i data-lucide="heart" style="{{ $recipe->is_favorite ? 'fill:currentColor;color:var(--danger)' : '' }}"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="flex-wrap gap-4">
                                    @if($recipe->region)
                                        <span class="chip chip-navy">{{ $recipe->region }}</span>
                                    @endif
                                    <span class="chip">{{ $recipe->difficulty }}</span>
                                    @foreach(array_slice($recipe->tags ?? [], 0, 2) as $tag)
                                        <span class="chip">{{ $tag }}</span>
                                    @endforeach
                                </div>

                                <p class="dish-desc mb-0">{{ $recipe->description }}</p>

                                <div class="dish-meta">
                                    <span><i data-lucide="clock"></i>{{ $recipe->cooking_time_minutes }}m</span>
                                    <span><i data-lucide="users"></i>{{ $recipe->servings }} Porsi</span>
                                    <span><i data-lucide="leaf"></i>{{ count($recipe->used_ingredients ?? []) }} bahan</span>
                                </div>

                                @if($recipe->nutrition_facts)
                                    <div class="stack-4 text-xs">
                                        <div class="flex-between">
                                            <span class="text-muted">Kalori</span>
                                            <strong class="mono">{{ $recipe->nutrition_facts['calories_kcal'] ?? 300 }} kkal</strong>
                                        </div>
                                        <div class="flex-between">
                                            <span class="text-muted">Protein</span>
                                            <strong class="mono">{{ $recipe->nutrition_facts['protein_g'] ?? 20 }}g</strong>
                                        </div>
                                        <div class="flex-between">
                                            <span class="text-muted">Karbo</span>
                                            <strong class="mono">{{ $recipe->nutrition_facts['carbs_g'] ?? 30 }}g</strong>
                                        </div>
                                    </div>
                                @endif

                                <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-secondary btn-sm mt-8">
                                    <span>Buka Resep</span>
                                    <i data-lucide="arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="empty" style="grid-column:1 / -1">
                            <i data-lucide="book-open"></i>
                            <h3>Tidak Ada Resep Ditemukan</h3>
                            <p>Coba ganti filter pencarian atau gunakan generator resep AI untuk membuat resep baru dari bahan kulkas Anda.</p>
                            <a href="{{ route('recipes.generator') }}" class="btn btn-primary btn-sm">
                                <i data-lucide="sparkles"></i>
                                Buka Generator Resep AI
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Pembagian halaman -->
            @if($recipes->hasPages())
                @php
                    $dari = max(1, $recipes->currentPage() - 2);
                    $sampai = min($recipes->lastPage(), $recipes->currentPage() + 2);
                @endphp
                <div class="pagination">
                    <div>Halaman {{ $recipes->currentPage() }} dari {{ $recipes->lastPage() }} &mdash; {{ $recipes->firstItem() }}&ndash;{{ $recipes->lastItem() }} dari {{ $recipes->total() }} resep</div>
                    <div class="links">
                        @if($recipes->onFirstPage())
                            <span class="is-disabled" aria-hidden="true">&lsaquo;</span>
                        @else
                            <a href="{{ $recipes->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a>
                        @endif

                        @for($p = $dari; $p <= $sampai; $p++)
                            @if($p === $recipes->currentPage())
                                <span class="is-active" aria-current="page">{{ $p }}</span>
                            @else
                                <a href="{{ $recipes->url($p) }}">{{ $p }}</a>
                            @endif
                        @endfor

                        @if($recipes->hasMorePages())
                            <a href="{{ $recipes->nextPageUrl() }}" rel="next" aria-label="Berikutnya">&rsaquo;</a>
                        @else
                            <span class="is-disabled" aria-hidden="true">&rsaquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
