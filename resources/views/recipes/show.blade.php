@extends('layouts.app')

@section('title', $recipe->title)

@section('content')
<div class="stack-20" x-data="{
    activeTimer: null,
    timerSeconds: 0,
    timerInterval: null,
    startTimer(minutes) {
        clearInterval(this.timerInterval);
        this.timerSeconds = minutes * 60;
        this.activeTimer = minutes;
        this.timerInterval = setInterval(() => {
            if (this.timerSeconds > 0) {
                this.timerSeconds--;
            } else {
                clearInterval(this.timerInterval);
                this.activeTimer = null;
                Swal.fire({
                    icon: 'success',
                    title: 'Waktu Selesai!',
                    text: 'Langkah memasak ' + minutes + ' menit telah selesai. Lanjutkan ke langkah berikutnya!',
                    background: '#ffffff',
                    color: '#1f2a37'
                });
            }
        }, 1000);
    },
    stopTimer() {
        clearInterval(this.timerInterval);
        this.activeTimer = null;
        this.timerSeconds = 0;
    },
    formatTimer(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    },

    substituteModal: false,
    selectedIngredient: '',
    substituteResult: null,
    substituteLoading: false,

    openSubstitute(name) {
        this.selectedIngredient = name;
        this.substituteResult = null;
        this.substituteLoading = true;
        this.substituteModal = true;

        window.AiTunggu?.jalankan('ganti', {
            estimasi: 10000,
            fase: 'Memeriksa sifat bahan yang hilang...',
        });

        fetch('{{ route('substitute.suggest') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({
                ingredient: name,
                recipe_context: '{{ $recipe->title }}',
                recipe_id: {{ $recipe->id }}
            })
        })
        .then((res) => {
            window.AiTunggu?.geser('ganti', 0.8, 'AI menjawab, rasio takaran sedang dihitung...');
            return res.json();
        })
        .then(res => {
            this.substituteLoading = false;
            if (res.success && res.data) {
                window.AiTunggu?.selesai('ganti');
                this.substituteResult = res.data;
            } else {
                window.AiTunggu?.gagal('ganti');
            }
        })
        .catch(err => {
            this.substituteLoading = false;
            window.AiTunggu?.gagal('ganti');
            console.error(err);
        });
    }
}">

    <!-- Kontrol atas halaman -->
    <div class="flex-between flex-wrap gap-12">
        <a href="{{ route('recipes.index') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Kembali ke Katalog Resep</span>
        </a>

        <div class="flex-center flex-wrap gap-8">
            <!-- Simpan ke favorit -->
            <form action="{{ route('recipes.favorite', $recipe->id) }}" method="POST" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-sm {{ $recipe->is_favorite ? 'btn-primary' : 'btn-secondary' }}">
                    <i data-lucide="heart" style="{{ $recipe->is_favorite ? 'fill:currentColor' : '' }}"></i>
                    <span>{{ $recipe->is_favorite ? 'Tersimpan di Favorit' : 'Tambah ke Favorit' }}</span>
                </button>
            </form>

            <!-- Cetak resep -->
            <button type="button" onclick="window.print()" class="btn btn-sm btn-secondary no-print">
                <i data-lucide="printer"></i>
                <span>Cetak Resep</span>
            </button>
        </div>
    </div>

    <!-- Bar timer saat memasak berjalan -->
    <div class="toast-stack no-print" x-show="activeTimer !== null" x-cloak>
        <div class="toast warn">
            <i data-lucide="timer"></i>
            <div class="flex-1">
                <div class="text-xs text-muted">Timer Memasak Aktif:</div>
                <div class="mono strong" x-text="formatTimer(timerSeconds)"></div>
            </div>
            <button type="button" class="icon-btn" @click="stopTimer()" aria-label="Hentikan timer">
                <i data-lucide="x"></i>
            </button>
        </div>
    </div>

    <!-- Foto & identitas resep -->
    <div class="recipe-hero">
        <div class="stack-12">
            <div class="flex-wrap gap-4">
                <span class="chip chip-brand">{{ $recipe->cuisine_type }}</span>
                <span class="chip chip-navy">{{ $recipe->meal_type }}</span>
                <span class="chip">Tingkat: {{ $recipe->difficulty }}</span>
                <span class="chip">Sumber: {{ $recipe->source === 'gemini_api' ? 'Gemini AI' : 'Resep Terverifikasi' }}</span>
            </div>

            <h1 class="mb-0">{{ $recipe->title }}</h1>
            <p class="text-muted mb-0">{{ $recipe->description }}</p>
        </div>

        <div class="photo">
            <img src="{{ $recipe->photo }}" alt="{{ $recipe->title }}">
        </div>
    </div>

    <!-- Waktu, porsi, kalori & protein -->
    <div class="grid grid-4">
        <div class="stat">
            <div class="label"><i data-lucide="clock"></i> Waktu Masak</div>
            <div class="value">{{ $recipe->cooking_time_minutes }} <span class="unit">Menit</span></div>
        </div>
        <div class="stat">
            <div class="label"><i data-lucide="users"></i> Porsi</div>
            <div class="value">{{ $recipe->servings }} <span class="unit">Orang</span></div>
        </div>
        <div class="stat">
            <div class="label"><i data-lucide="flame"></i> Energi per Porsi</div>
            <div class="value">{{ $recipe->nutrition_facts['calories_kcal'] ?? 350 }} <span class="unit">kkal</span></div>
        </div>
        <div class="stat">
            <div class="label"><i data-lucide="dumbbell"></i> Kandungan Protein</div>
            <div class="value">{{ $recipe->nutrition_facts['protein_g'] ?? 24 }} <span class="unit">g</span></div>
        </div>
    </div>

    <div class="grid grid-side-right">

        <!-- Langkah memasak bertahap -->
        <div class="stack-20">
            <div class="card">
                <div class="card-head">
                    <h3 class="mb-0"><i data-lucide="list-ordered" style="width:16px;height:16px;color:var(--brand)"></i> Langkah Pembuatan Masakan</h3>
                    <span class="hint">{{ count($recipe->instructions ?? []) }} langkah</span>
                </div>
                <div class="card-body">
                    <ol class="steps">
                        @foreach($recipe->instructions ?? [] as $index => $step)
                            @php
                                $stepNumber = is_array($step) ? ($step['step_number'] ?? ($index + 1)) : ($index + 1);
                                $actionText = is_array($step) ? ($step['action'] ?? '') : (string) $step;
                                $stepMinutes = is_array($step) ? ($step['estimated_minutes'] ?? null) : null;
                            @endphp
                            <li id="langkah-{{ $stepNumber }}">
                                <div>{{ $actionText }}</div>

                                @if($stepMinutes)
                                    <button type="button" class="btn btn-secondary btn-sm mt-8 no-print" @click="startTimer({{ $stepMinutes }})">
                                        <i data-lucide="timer"></i>
                                        <span>Mulai Timer {{ $stepMinutes }} Menit</span>
                                    </button>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <!-- Tips chef -->
            @if($recipe->chef_tips)
                <div class="alert alert-warn">
                    <i data-lucide="sparkles"></i>
                    <div class="body">
                        <strong>Tips Rahasia Chef Resepify:</strong>
                        "{{ $recipe->chef_tips }}"
                    </div>
                </div>
            @endif
        </div>

        <!-- Daftar bahan -->
        <div class="stack-20">
            <div class="card">
                <div class="card-head">
                    <h3 class="mb-0"><i data-lucide="check-circle-2" style="width:16px;height:16px;color:var(--ok)"></i> Bahan Utama (Kombinasi Kulkas)</h3>
                    <span class="hint">{{ count($recipe->used_ingredients ?? []) }} bahan</span>
                </div>
                <div class="card-body tight">
                    <ul class="ing-list">
                        @foreach($recipe->used_ingredients ?? [] as $ing)
                            @php
                                $ingName = is_array($ing) ? ($ing['name'] ?? '') : (string) $ing;
                                $inPantry = in_array(strtolower(trim($ingName)), $userPantryNames ?? []);
                            @endphp
                            <li>
                                <span class="qty">{{ is_array($ing) ? ($ing['quantity'] ?? '') . ' ' . ($ing['unit'] ?? '') : '' }}</span>
                                <span class="name">
                                    <span class="dot {{ $inPantry ? 'dot-ok' : '' }}"></span>
                                    <span class="strong">{{ $ingName }}</span>
                                    @if($inPantry)
                                        <span class="chip chip-ok">Ada di Kulkas</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Bumbu tambahan, bisa dicari penggantiannya lewat AI -->
            @if(!empty($recipe->missing_ingredients))
                <div class="card">
                    <div class="card-head">
                        <h3 class="mb-0"><i data-lucide="plus-circle" style="width:16px;height:16px;color:var(--warn)"></i> Bumbu Tambahan / Pelengkap</h3>
                        <span class="hint">Klik untuk cari substitusi</span>
                    </div>
                    <div class="card-body tight">
                        <ul class="ing-list">
                            @foreach($recipe->missing_ingredients as $missing)
                                @php
                                    $mName = is_array($missing) ? ($missing['name'] ?? '') : (string) $missing;
                                    $mQty = is_array($missing) ? ($missing['quantity'] ?? '') . ' ' . ($missing['unit'] ?? '') : '';
                                @endphp
                                <li>
                                    <span class="qty">{{ $mQty }}</span>
                                    <span class="name">{{ $mName }}</span>
                                    <button type="button" class="btn btn-secondary btn-sm no-print" @click="openSubstitute('{{ addslashes($mName) }}')">
                                        <i data-lucide="repeat"></i>
                                        <span>Cari Alternatif</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Estimasi gizi satu porsi -->
    @if($recipe->nutrition_facts)
        <div class="card">
            <div class="card-head">
                <h3 class="mb-0"><i data-lucide="pie-chart" style="width:16px;height:16px;color:var(--navy)"></i> Estimasi Nutrisi AI per Porsi</h3>
            </div>
            <div class="card-body">
                <div class="nutri">
                    <div>
                        <div class="v">{{ $recipe->nutrition_facts['calories_kcal'] ?? 300 }}</div>
                        <div class="k">Energi Total (kkal)</div>
                    </div>
                    <div>
                        <div class="v">{{ $recipe->nutrition_facts['protein_g'] ?? 20 }} g</div>
                        <div class="k">Protein</div>
                    </div>
                    <div>
                        <div class="v">{{ $recipe->nutrition_facts['carbs_g'] ?? 30 }} g</div>
                        <div class="k">Karbohidrat</div>
                    </div>
                    <div>
                        <div class="v">{{ $recipe->nutrition_facts['fat_g'] ?? 10 }} g</div>
                        <div class="k">Lemak</div>
                    </div>
                    <div>
                        <div class="v">{{ $recipe->nutrition_facts['fiber_g'] ?? 4 }} g</div>
                        <div class="k">Serat Pangan</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal substitusi bahan AI -->
    <div class="modal-mask no-print" x-show="substituteModal" x-cloak>
        <div class="modal" role="dialog" aria-modal="true" aria-label="Rekomendasi Bahan Pengganti AI" @click.outside="substituteModal = false">
            <div class="modal-head">
                <div class="flex-center gap-12">
                    <i data-lucide="repeat" style="width:18px;height:18px;color:var(--brand)"></i>
                    <div>
                        <h3 class="mb-0">Rekomendasi Bahan Pengganti AI</h3>
                        <div class="text-xs text-muted">
                            Alternatif untuk: <strong x-text="selectedIngredient"></strong>
                        </div>
                    </div>
                </div>
                <button type="button" class="icon-btn" @click="substituteModal = false" aria-label="Tutup">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Jawaban AI masih dimuat -->
                <div x-show="substituteLoading">
                    @include('partials.ai-wait', [
                        'id' => 'ganti',
                        'ringkas' => true,
                        'judul' => 'Gemini AI Menalar Bahan Pengganti',
                        'langkah' => [
                            'Mengurai sifat bahan yang hilang',
                            'Memilih alternatif paling mirip',
                            'Menyesuaikan takarannya',
                        ],
                        'catatan' => 'Rasio dan catatan rasa dihitung dari karakter bahan, bukan tebakan umum.',
                    ])
                </div>

                <!-- Daftar bahan pengganti -->
                <div x-show="!substituteLoading && substituteResult" class="stack-12">
                    <template x-for="(sub, idx) in substituteResult?.substitutes" :key="'sub_show_'+idx">
                        <div class="card">
                            <div class="card-body tight">
                                <div class="flex-between gap-12">
                                    <h4 class="mb-0" x-text="sub.substitute_name"></h4>
                                    <span class="chip chip-brand mono" x-text="'Rasio: ' + sub.ratio"></span>
                                </div>
                                <p class="text-sm text-muted mt-8 mb-0" x-text="sub.culinary_notes"></p>
                            </div>
                        </div>
                    </template>

                    <div x-show="substituteResult?.chef_verdict" class="alert alert-info">
                        <i data-lucide="chef-hat"></i>
                        <div class="body">
                            <strong>Kesimpulan Chef:</strong>
                            <span x-text="substituteResult?.chef_verdict"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-secondary btn-sm" @click="substituteModal = false">Tutup</button>
            </div>
        </div>
    </div>

</div>
@endsection
