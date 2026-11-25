@extends('layouts.app')

@section('title', 'AI Recipe Generator - Olah Bahan Sisa')

@section('content')
<div class="stack-24" x-data="{
    selectedIngredients: {{ json_encode($preselected ?? []) }},
    cuisineType: 'Nusantara / Masakan Rumahan Indonesia',
    mealType: 'Makan Siang',
    maxTime: 30,
    cookingSkill: '{{ Auth::user()->cooking_skill ?? 'Menengah' }}',
    dietPreferences: {{ json_encode(Auth::user()->diet_preferences ?? ['Halal', 'Seimbang']) }},
    loading: false,
    generatedRecipe: null,
    cacheInfo: null,
    substituteModal: false,
    selectedMissingIngredient: '',
    substituteResult: null,
    substituteLoading: false,

    toggleIngredient(name) {
        const index = this.selectedIngredients.indexOf(name);
        if (index === -1) {
            this.selectedIngredients.push(name);
        } else {
            this.selectedIngredients.splice(index, 1);
        }
    },

    selectAllExpiring() {
        const expiringBadges = document.querySelectorAll('[data-expiring=true]');
        expiringBadges.forEach(el => {
            const name = el.dataset.name;
            if (name && !this.selectedIngredients.includes(name)) {
                this.selectedIngredients.push(name);
            }
        });
    },

    clearSelection() {
        this.selectedIngredients = [];
    },

    submitGenerator() {
        if (this.selectedIngredients.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Bahan Terlebih Dahulu',
                text: 'Silakan centang minimal 1 bahan makanan dari inventaris kulkas Anda.',
                background: '#111C38',
                color: '#fff'
            });
            return;
        }

        this.loading = true;
        this.generatedRecipe = null;
        this.cacheInfo = null;

        fetch('{{ route('recipes.generate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({
                ingredients: this.selectedIngredients,
                cuisine_type: this.cuisineType,
                meal_type: this.mealType,
                max_time: this.maxTime,
                cooking_skill: this.cookingSkill,
                diet: this.dietPreferences
            })
        })
        .then(res => res.json())
        .then(res => {
            this.loading = false;
            if (res.success && res.recipe) {
                this.generatedRecipe = res.recipe;
                this.cacheInfo = {
                    from_cache: res.from_cache,
                    cache_level: res.cache_level,
                    latency: res.latency_ms,
                    tokens: res.tokens || 0
                };
                lucide.createIcons();
                // Smooth scroll to recipe result
                this.$nextTick(() => {
                    document.getElementById('recipe_result_section')?.scrollIntoView({ behavior: 'smooth' });
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyusun Resep',
                    text: res.message || 'Terjadi kesalahan pada respon Gemini AI.',
                    background: '#111C38',
                    color: '#fff'
                });
            }
        })
        .catch(err => {
            this.loading = false;
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Koneksi Error',
                text: 'Gagal menghubungi server.',
                background: '#111C38',
                color: '#fff'
            });
        });
    },

    openSubstitute(ingredientName) {
        this.selectedMissingIngredient = ingredientName;
        this.substituteResult = null;
        this.substituteLoading = true;
        this.substituteModal = true;

        fetch('{{ route('substitute.suggest') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({
                ingredient: ingredientName,
                recipe_context: this.generatedRecipe?.title || 'Masakan Rumahan'
            })
        })
        .then(res => res.json())
        .then(res => {
            this.substituteLoading = false;
            if (res.success && res.data) {
                this.substituteResult = res.data;
            }
        })
        .catch(err => {
            this.substituteLoading = false;
            console.error(err);
        });
    }
}">

    <!-- Page Header -->
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-main">
                <span class="eyebrow"><i data-lucide="sparkles"></i> Peracik Resep AI</span>
                <h1>Generator Resep Berbasis Bahan Sisa</h1>
                <p class="sub">Pilih bahan yang tersedia di kulkas Anda, lalu AI akan menyusun resep masakan lezat dan estimasi gizinya secara instan.</p>
            </div>

            <div class="page-head-actions">
                <a href="{{ route('recipes.index') }}" class="btn btn-secondary">
                    <i data-lucide="book-open"></i> Katalog Kuliner
                </a>
            </div>
        </div>
    </div>

    <!-- Panel kiri: bahan + preferensi -->
    <div class="grid grid-side-right gap-20">
        <div class="stack-20">
            <!-- Step 1: Ingredient Selection from User Pantry -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3><span class="chip chip-brand" style="margin-right:8px">1</span>Pilih Bahan dari Inventaris Dapur Anda</h3>
                        <p class="hint mb-0">Centang bahan makanan yang ingin Anda gunakan dalam masakan ini.</p>
                        @if(($jumlahArsip ?? 0) > 0)
                            <p class="hint mb-0">
                                {{ $jumlahArsip }} bahan sudah diarsipkan karena lewat kedaluwarsa lebih dari 1 hari dan
                                tidak ditampilkan di sini.
                                <a href="{{ route('inventory.index', ['status' => 'archived']) }}" class="strong">Lihat arsip</a>.
                            </p>
                        @endif
                    </div>
                    <div class="flex-center gap-8">
                        <button type="button" @click="selectAllExpiring()" class="btn btn-secondary btn-sm">
                            <i data-lucide="zap"></i>
                            Pilih Semua Bahan Kedaluwarsa
                        </button>
                        <button type="button" @click="clearSelection()" x-show="selectedIngredients.length > 0" class="btn btn-ghost btn-sm">
                            Bersihkan
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Inventory Multi-Select Grid -->
                    <div class="pick-list">
                        @forelse($inventoryItems as $item)
                            @php 
                                $isExpiring = $item->days_until_expiration <= 3; 
                            @endphp
                            <div @click="toggleIngredient('{{ $item->item_name }}')"
                                 data-name="{{ $item->item_name }}"
                                 data-expiring="{{ $isExpiring ? 'true' : 'false' }}"
                                 class="check"
                                 style="cursor:pointer; user-select:none; align-items:center;"
                                 :class="selectedIngredients.includes('{{ $item->item_name }}') ? 'is-on' : ''">

                                <span style="font-size:18px; line-height:1; flex:none">{{ $item->display_icon }}</span>

                                <div class="flex-1">
                                    <span class="strong text-sm truncate" style="display:block">{{ $item->item_name }}</span>
                                    <span class="text-xs text-muted mono" style="display:block">{{ $item->quantity }} {{ $item->unit }}</span>
                                    @if($isExpiring)
                                        <span class="chip {{ $item->days_until_expiration < 0 ? 'chip-danger' : 'chip-warn' }} text-xs" style="margin-top:4px">
                                            {{ $item->expiry_label }}
                                        </span>
                                    @endif
                                </div>

                                <svg x-show="selectedIngredients.includes('{{ $item->item_name }}')"
                                     style="width:18px;height:18px;flex:none;fill:var(--brand-dark)"
                                     viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                            </div>
                        @empty
                            <div class="empty" style="grid-column: 1 / -1; padding: 24px 12px;">
                                Inventaris Anda kosong. <a href="{{ route('inventory.index') }}" class="strong">Tambahkan bahan makanan terlebih dahulu</a>.
                                @if(($jumlahArsip ?? 0) > 0)
                                    <div class="mt-8">Atau periksa {{ $jumlahArsip }} bahan berarsip kedaluwarsa
                                        <a href="{{ route('inventory.index', ['status' => 'archived']) }}" class="strong">di halaman arsip</a>.</div>
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card-foot flex-between text-xs text-muted">
                    <span>Bahan Terpilih: <strong style="color:var(--brand-dark)" x-text="selectedIngredients.length"></strong> item</span>
                    <span>*Bumbu dasar seperti garam, minyak, dan gula diasumsikan tersedia.</span>
                </div>
            </div>

            <!-- Step 2: Culinary Preferences & Reasoning Parameters -->
            <div class="card">
                <div class="card-head">
                    <h3><span class="chip chip-navy" style="margin-right:8px">2</span>Preferensi Cita Rasa &amp; Kebutuhan Memasak</h3>
                </div>

                <div class="card-body">
                    <div class="grid grid-2 gap-16">
                        <!-- Gaya Kuliner -->
                        <div class="field mb-0">
                            <label class="label">Gaya Kuliner</label>
                            <select x-model="cuisineType" class="select full">
                                <option value="Nusantara / Masakan Rumahan Indonesia">Masakan Rumahan Nusantara</option>
                                <option value="Street Food Kaki Lima">Street Food / Kaki Lima Favorit</option>
                                <option value="Modern Kafe & Kekinian">Modern Kafe &amp; Kekinian</option>
                                <option value="Oriental & Asian Fusion">Oriental &amp; Asian Fusion</option>
                                <option value="Western Praktis">Western Praktis (Pasta &amp; Grill)</option>
                            </select>
                        </div>

                        <!-- Jenis Hidangan -->
                        <div class="field mb-0">
                            <label class="label">Waktu Santap</label>
                            <select x-model="mealType" class="select full">
                                <option value="Makan Siang">Makan Siang (Utama)</option>
                                <option value="Makan Malam">Makan Malam (Hangat)</option>
                                <option value="Sarapan Cepat">Sarapan Cepat (Praktis)</option>
                                <option value="Cemilan Sore / Kudapan">Cemilan Sore / Kudapan</option>
                            </select>
                        </div>

                        <!-- Maksimal Waktu Masak -->
                        <div class="field mb-0">
                            <div class="flex-between mb-8">
                                <label class="label mb-0">Waktu Masak:</label>
                                <span class="mono strong text-sm" style="color:var(--brand-dark)" x-text="maxTime + ' Menit'"></span>
                            </div>
                            <input type="range" min="10" max="60" step="5" x-model="maxTime" class="full" style="accent-color:var(--brand);cursor:pointer">
                        </div>

                        <!-- Keahlian Memasak -->
                        <div class="field mb-0">
                            <label class="label">Tingkat Keahlian</label>
                            <select x-model="cookingSkill" class="select full">
                                <option value="Pemula">Pemula (Langkah Sederhana)</option>
                                <option value="Menengah">Menengah (Standar)</option>
                                <option value="Mahir">Mahir (Teknik Kuliner Lengkap)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-foot">
                    <button type="button" @click="submitGenerator()" :disabled="loading" class="btn btn-primary btn-lg btn-block">
                        <template x-if="!loading">
                            <span class="flex-center" style="justify-content:center">
                                <i data-lucide="wand-2"></i>
                                <span>Rancang Resep Masakan dengan Gemini AI</span>
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex-center" style="justify-content:center">
                                <span class="ai-titik"><i></i><i></i><i></i></span>
                                <span>Gemini AI Sedang Meracik Resep &amp; Mengestimasi Nutrisi...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2b: Panel saat Gemini AI sedang meracik -->
    <div x-show="loading" x-cloak class="card" style="margin-top:18px">
        @include('partials.ai-wait', [
            'judul' => 'Gemini AI Sedang Meracik Resepmu',
            'langkah' => [
                'Memilih bahan terbaik dari kulkas',
                'Menyesuaikan dengan selera & pantanganmu',
                'Menakar bumbu dan urutan memasak',
                'Menghitung estimasi gizi per porsi',
                'Memoles tips dapur dari chef',
            ],
            'catatan' => 'Semua angka takaran, waktu masak, dan kalori dihitung langsung oleh AI, bukan contoh jadi.',
        ])
        <div class="ai-bayang" aria-hidden="true">
            <i class="sedang"></i>
            <i></i>
            <i class="pendek"></i>
            <i class="sedang"></i>
        </div>
    </div>

    <!-- Step 3: Result Section (Appears after AI generation) -->
    <div id="recipe_result_section" x-show="generatedRecipe" x-cloak>
        <div class="card">
            <div class="card-body">
                <!-- Recipe Header Card -->
                <div class="recipe-hero">
                    <div class="stack-12">
                        <div class="flex-wrap gap-8">
                            <span class="chip chip-brand" x-text="generatedRecipe?.cuisine_type"></span>
                            <span class="chip chip-warn" x-text="generatedRecipe?.meal_type"></span>
                            <span class="chip chip-navy" x-text="'Tingkat: ' + (generatedRecipe?.difficulty || 'Mudah')"></span>
                        </div>

                        <h2 style="font-size:26px; margin:0" x-text="generatedRecipe?.title"></h2>
                        <p class="text-sm text-muted mb-0" x-text="generatedRecipe?.description"></p>
                    </div>

                    <!-- Info Saji & Key Metrics -->
                    <div class="stack-12">
                        <div class="flex-between" style="padding:6px 12px; background:var(--bg-subtle); border-radius:8px; border:1px solid var(--border)">
                            <span class="flex-center gap-6 text-xs text-muted">
                                <i data-lucide="sparkles" style="width:13px;height:13px;color:var(--brand)"></i>
                                <span>Resep Masakan Berhasil Diracik</span>
                            </span>
                            <span class="text-xs text-muted mono" x-text="cacheInfo?.latency + ' ms'"></span>
                        </div>

                        <div class="grid grid-2 gap-12">
                            <div class="stat">
                                <span class="label"><i data-lucide="clock"></i> Waktu Masak</span>
                                <span class="value" style="font-size:19px" x-text="(generatedRecipe?.cooking_time_minutes || 25) + ' Menit'"></span>
                            </div>
                            <div class="stat">
                                <span class="label"><i data-lucide="users"></i> Porsi</span>
                                <span class="value" style="font-size:19px" x-text="(generatedRecipe?.servings || 2) + ' Porsi'"></span>
                            </div>
                            <div class="stat">
                                <span class="label"><i data-lucide="flame"></i> Kalori Porsi</span>
                                <span class="value" style="font-size:19px" x-text="(generatedRecipe?.nutrition_facts?.calories_kcal || 350) + ' kkal'"></span>
                            </div>
                            <div class="stat">
                                <span class="label"><i data-lucide="dumbbell"></i> Protein</span>
                                <span class="value" style="font-size:19px" x-text="(generatedRecipe?.nutrition_facts?.protein_g || 22) + ' g'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ingredients & Instructions Grid -->
                <div class="grid grid-2 gap-20 mt-24">

                    <!-- Left: Ingredients List & AI Substitute Actions -->
                    <div class="stack-20">
                        <div>
                            <h4 class="flex-center mb-12" style="font-size:15px">
                                <i data-lucide="check-circle" style="width:16px;height:16px;color:var(--ok)"></i>
                                Bahan yang Digunakan (Dari Kulkas)
                            </h4>
                            <ul class="ing-list">
                                <template x-for="(ing, idx) in generatedRecipe?.used_ingredients" :key="'used_'+idx">
                                    <li>
                                        <span class="name strong" x-text="ing.name"></span>
                                        <span class="qty mono text-sm" x-text="ing.quantity + ' ' + (ing.unit || '')"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Missing / Staples Ingredients with Substitute Finder Button -->
                        <div x-show="generatedRecipe?.missing_ingredients && generatedRecipe?.missing_ingredients.length > 0">
                            <div class="flex-between mb-8">
                                <h4 class="flex-center mb-0" style="font-size:15px">
                                    <i data-lucide="alert-circle" style="width:16px;height:16px;color:var(--warn)"></i>
                                    Bumbu Dasar Tambahan
                                </h4>
                                <span class="text-xs text-muted">Klik bahan untuk cari pengganti</span>
                            </div>

                            <ul class="ing-list">
                                <template x-for="(mis, idx) in generatedRecipe?.missing_ingredients" :key="'mis_'+idx">
                                    <li>
                                        <div class="flex-1">
                                            <span class="name" x-text="mis.name"></span>
                                            <span class="text-xs text-muted mono" style="display:block" x-text="mis.quantity + ' ' + (mis.unit || '')"></span>
                                        </div>
                                        <button type="button" @click="openSubstitute(mis.name)" class="btn btn-secondary btn-sm">
                                            <i data-lucide="repeat"></i>
                                            <span>Cari Pengganti</span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Nutrition Macros -->
                        @if(true)
                        <div class="stack-8">
                            <h4 class="text-xs strong text-muted" style="letter-spacing:.04em; text-transform:uppercase">Estimasi Nutrisi AI per Porsi</h4>
                            <div class="nutri" style="grid-template-columns:repeat(3,1fr)">
                                <div>
                                    <span class="v" x-text="(generatedRecipe?.nutrition_facts?.carbs_g || 30) + ' g'"></span>
                                    <span class="k">Karbohidrat</span>
                                </div>
                                <div>
                                    <span class="v" x-text="(generatedRecipe?.nutrition_facts?.fat_g || 12) + ' g'"></span>
                                    <span class="k">Lemak</span>
                                </div>
                                <div>
                                    <span class="v" x-text="(generatedRecipe?.nutrition_facts?.fiber_g || 4) + ' g'"></span>
                                    <span class="k">Serat Pangan</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Right: Step-by-Step Instructions with Timers -->
                    <div class="stack-20">
                        <h4 class="flex-center mb-12" style="font-size:15px">
                            <i data-lucide="list-ordered" style="width:16px;height:16px;color:var(--brand-dark)"></i>
                            Langkah Pembuatan Masakan
                        </h4>

                        <ol class="steps">
                            <template x-for="(step, idx) in generatedRecipe?.instructions" :key="'step_'+idx">
                                <li>
                                    <span x-text="step.step_number || (idx + 1)"
                                          style="position:absolute; left:0; top:0; width:27px; height:27px; border-radius:50%;
                                                 background:var(--brand-soft); border:1px solid var(--brand-line); color:var(--brand-dark);
                                                 font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center;"></span>
                                    <div x-text="step.action"></div>
                                    <span x-show="step.estimated_minutes" class="minute mono flex-center" style="margin-top:4px">
                                        <i data-lucide="timer" style="width:13px;height:13px;color:var(--warn)"></i>
                                        <span x-text="'Estimasi ' + step.estimated_minutes + ' menit'"></span>
                                    </span>
                                </li>
                            </template>
                        </ol>

                        <!-- Chef's Secret Tips Card -->
                        <div x-show="generatedRecipe?.chef_tips" class="alert alert-warn">
                            <i data-lucide="sparkles"></i>
                            <div class="body">
                                <strong>Tips Rahasia Chef Resepify:</strong>
                                <span class="text-sm" x-text="generatedRecipe?.chef_tips"></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- AI SUBSTITUTE MODAL (Bahan Pengganti Terdekat & Rasio Takaran) -->
    <div class="modal-mask" x-show="substituteModal" x-cloak>
        <div @click.outside="substituteModal = false" class="modal">
            <div class="modal-head">
                <div class="flex-center" style="gap:12px">
                    <span class="icon-btn" style="pointer-events:none"><i data-lucide="repeat"></i></span>
                    <div>
                        <h3>Rekomendasi Bahan Pengganti AI</h3>
                        <p class="text-xs text-muted mb-0">Alternatif terdekat untuk: <strong style="color:var(--brand-dark)" x-text="selectedMissingIngredient"></strong></p>
                    </div>
                </div>
                <button type="button" @click="substituteModal = false" class="icon-btn"><i data-lucide="x"></i></button>
            </div>

            <div class="modal-body">
                <!-- Loading State -->
                <div x-show="substituteLoading">
                    @include('partials.ai-wait', [
                        'ringkas' => true,
                        'judul' => 'Gemini AI Mencari Bahan Pengganti',
                        'langkah' => [
                            'Membandingkan tekstur dan aroma',
                            'Menghitung ulang rasio takaran',
                            'Menilai rasa akhir masakan',
                        ],
                        'catatan' => 'Alternatif dipilih dari bahan yang paling mudah ditemukan di pasar.',
                    ])
                </div>

                <!-- Results State -->
                <div x-show="!substituteLoading && substituteResult" class="stack-12">
                    <template x-for="(sub, idx) in substituteResult?.substitutes" :key="'sub_'+idx">
                        <div class="card is-flat">
                            <div class="card-body tight">
                                <div class="flex-between" style="align-items:flex-start">
                                    <h4 class="mb-0" x-text="sub.substitute_name"></h4>
                                    <span class="chip chip-warn mono" x-text="'Rasio: ' + sub.ratio"></span>
                                </div>
                                <p class="text-sm text-muted mb-0 mt-8" x-text="sub.culinary_notes"></p>
                            </div>
                        </div>
                    </template>

                    <div x-show="substituteResult?.chef_verdict" class="alert">
                        <div class="body">
                            <strong style="color:var(--brand-dark)">Catatan Chef:</strong>
                            <span x-text="substituteResult?.chef_verdict"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" @click="substituteModal = false" class="btn btn-secondary">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
