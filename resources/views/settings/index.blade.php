@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="page-head">
    <div class="eyebrow"><i data-lucide="sliders-horizontal"></i> Konfigurasi Sistem</div>
    <h1>Pengaturan Sistem</h1>
    <p class="sub">
        Atur profil dan preferensi memasak, lalu lengkapi kunci API Gemini serta koneksi Supabase Cloud.
        Preferensi profil dipakai generator resep, sedangkan kunci API disimpan ke database.
    </p>
</div>

<div class="grid grid-side" x-data="{
    testingGemini: false,
    geminiResult: null,
    testingSupabase: false,
    supabaseResult: null,

    testGemini() {
        const key = document.getElementById('setting_gemini_key').value;
        const model = document.getElementById('setting_gemini_model').value;

        this.testingGemini = true;
        this.geminiResult = null;

        fetch('{{ route('settings.test-gemini') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ api_key: key, model: model })
        })
        .then(res => res.json())
        .then(res => {
            this.testingGemini = false;
            this.geminiResult = res;
        })
        .catch(err => {
            this.testingGemini = false;
            this.geminiResult = { success: false, message: 'Gagal menghubungi server lokal.' };
        });
    },

    testSupabase() {
        const url = document.getElementById('setting_supabase_url').value;
        const key = document.getElementById('setting_supabase_key').value;

        this.testingSupabase = true;
        this.supabaseResult = null;

        fetch('{{ route('settings.test-supabase') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ url: url, key: key })
        })
        .then(res => res.json())
        .then(res => {
            this.testingSupabase = false;
            this.supabaseResult = res;
        })
        .catch(err => {
            this.testingSupabase = false;
            this.supabaseResult = { success: false, message: 'Gagal menghubungi server lokal.' };
        });
    }
}">

    {{-- Ringkasan status; nilai kunci tidak pernah ditampilkan di halaman ini --}}
    <aside class="col">
        <div class="card">
            <div class="card-head"><h3 class="mb-0">Status Koneksi</h3></div>
            <div class="card-body tight col gap-8">
                <div class="flex-between">
                    <span class="text-sm">Gemini API</span>
                    @if ($geminiApiKey)
                        <span class="chip chip-ok"><span class="dot dot-ok"></span> Terhubung</span>
                    @else
                        <span class="chip chip-warn"><span class="dot dot-warn"></span> Belum dikonfigurasi</span>
                    @endif
                </div>
                <div class="flex-between">
                    <span class="text-sm">Supabase Cloud</span>
                    @if ($supabaseUrl && $supabaseKey)
                        <span class="chip chip-ok"><span class="dot dot-ok"></span> Terhubung</span>
                    @else
                        <span class="chip chip-warn"><span class="dot dot-warn"></span> Belum dikonfigurasi</span>
                    @endif
                </div>
                <div class="flex-between">
                    <span class="text-sm">Model aktif</span>
                    <span class="mono text-xs">{{ $geminiModel }}</span>
                </div>
                <div class="flex-between">
                    <span class="text-sm">Bucket foto</span>
                    <span class="mono text-xs">{{ $supabaseBucket }}</span>
                </div>
                <div class="flex-between">
                    <span class="text-sm">NIM</span>
                    <span class="mono text-xs">{{ $studentNim }}</span>
                </div>
            </div>
            <div class="card-foot flex-center flex-wrap">
                <a href="#section-profil" class="text-sm">Profil</a>
                <span class="text-muted">&middot;</span>
                <a href="#section-gemini" class="text-sm">Gemini</a>
                <span class="text-muted">&middot;</span>
                <a href="#section-supabase" class="text-sm">Supabase</a>
            </div>
        </div>

        <div class="card is-flat">
            <div class="card-body tight">
                <div class="text-sm text-muted">
                    Isian kunci hanya bisa diisi ulang, tidak dibaca balik.
                    Biarkan kosong saat menyimpan supaya kunci lama tetap terpakai.
                </div>
            </div>
        </div>
    </aside>

    <div class="col gap-20">

    {{-- ===================== Profil & preferensi ===================== --}}
    <form action="{{ route('profile.update') }}" method="POST" class="card" id="section-profil">
        @csrf

        <div class="card-head">
            <div>
                <h2>Profil &amp; Preferensi</h2>
                <span class="hint">Generator resep memakai data ini supaya saran masak sesuai kebiasaan Anda.</span>
            </div>
            <span class="chip chip-brand">Akun mahasiswa</span>
        </div>

        <div class="card-body">
            <div class="grid grid-2">
                <div class="field">
                    <label class="label" for="profil_nama">Nama mahasiswa <span class="req">*</span></label>
                    <input class="input" type="text" id="profil_nama" name="name"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label class="label" for="profil_nim">NIM</label>
                    <input class="input mono" type="text" id="profil_nim" name="nim"
                           value="{{ old('nim', $user->nim ?? $studentNim) }}" placeholder="12210889">
                    <div class="hint">NIM ikut tercetak pada kop laporan inventaris.</div>
                    @error('nim')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label class="label" for="profil_email">Email</label>
                    <input class="input" type="email" id="profil_email" name="email"
                           value="{{ $user->email }}" autocomplete="email">
                    <div class="hint">Alamat yang dipakai untuk masuk ke Resepify.</div>
                </div>

                <div class="field">
                    <label class="label" for="profil_skill">Tingkat keahlian memasak</label>
                    <select class="select" id="profil_skill" name="cooking_skill">
                        @foreach (['Pemula', 'Menengah', 'Mahir'] as $level)
                            <option value="{{ $level }}"
                                {{ old('cooking_skill', $user->cooking_skill ?? 'Menengah') === $level ? 'selected' : '' }}>
                                {{ $level }}
                            </option>
                        @endforeach
                    </select>
                    <div class="hint">Pemula mendapat langkah yang lebih rinci dan resep yang cepat.</div>
                    @error('cooking_skill')
                        <div class="err">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            @php
                $pilihanDiet = ['Halal', 'Seimbang', 'Tinggi Protein', 'Rendah Kalori', 'Vegetarian', 'Vegan', 'Bebas Gluten', 'Hemat Waktu'];
                $dietTersimpan = old('diet_preferences', $user->diet_preferences ?? ['Halal', 'Seimbang']);
            @endphp

            <div class="field">
                <label class="label">Preferensi diet</label>
                <div class="pick-list">
                    @foreach ($pilihanDiet as $diet)
                        <label class="check {{ in_array($diet, $dietTersimpan) ? 'is-on' : '' }}">
                            <input type="checkbox" name="diet_preferences[]" value="{{ $diet }}"
                                   {{ in_array($diet, $dietTersimpan) ? 'checked' : '' }}>
                            <span>{{ $diet }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="hint">Boleh lebih dari satu; bahan yang bertentangan dengan pilihan ini tidak disarankan AI.</div>
                @error('diet_preferences')
                    <div class="err">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="profil_alergi">Alergi makanan (opsional)</label>
                <input class="input" type="text" id="profil_alergi" name="allergies"
                       value="{{ old('allergies', $user->allergies ?? '') }}"
                       placeholder="Udang, kacang tanah">
                @error('allergies')
                    <div class="err">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card-foot flex-between">
            <span class="text-xs text-muted">Perubahan tersimpan pada akun Anda.</span>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> Simpan perubahan
            </button>
        </div>
    </form>

    {{-- ===================== Konfigurasi API ===================== --}}
    <form action="{{ route('settings.update') }}" method="POST" class="col gap-20"
          onsubmit="['setting_gemini_key','setting_supabase_key'].forEach(function(id){var el=document.getElementById(id); if(!el.value) el.disabled=true;});">
        @csrf

        <section class="card" id="section-gemini">
            <div class="card-head">
                <div>
                    <h2>Google Gemini API</h2>
                    <span class="hint">Dipakai untuk deteksi foto bahan, penulisan resep, dan estimasi gizi.</span>
                </div>
                <span class="chip chip-navy">AI</span>
            </div>

            <div class="card-body">
                <div class="grid grid-2">
                    <div class="field">
                        <label class="label" for="setting_gemini_key">Gemini API Key</label>
                        <input class="input mono" type="password" id="setting_gemini_key" name="gemini_api_key"
                               value="" autocomplete="off" placeholder="AIzaSy...">
                        <div class="hint">
                            Disimpan pada tabel <span class="mono">system_settings</span>; bila dikosongkan, sistem mengambil
                            <span class="mono">GEMINI_API_KEY</span> dari file .env.
                            Kosongkan bila kunci tidak diganti.
                        </div>
                        @error('gemini_api_key')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="setting_gemini_model">Model Gemini</label>
                        <select class="select" id="setting_gemini_model" name="gemini_model">
                            <option value="gemini-3.5-flash" {{ $geminiModel === 'gemini-3.5-flash' ? 'selected' : '' }}>
                                Gemini 3.5 Flash (cepat, direkomendasikan)
                            </option>
                            <option value="gemini-pro-latest" {{ $geminiModel === 'gemini-pro-latest' ? 'selected' : '' }}>
                                Gemini Pro terbaru (penalaran kompleks)
                            </option>
                            <option value="gemini-flash-lite-latest" {{ $geminiModel === 'gemini-flash-lite-latest' ? 'selected' : '' }}>
                                Gemini Flash Lite terbaru (paling hemat kuota)
                            </option>
                        </select>
                        <div class="hint">Model ringan mempercepat respons dan menekan pemakaian token.</div>
                        @error('gemini_model')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="flex-between flex-wrap mt-8">
                    <button type="button" class="btn btn-secondary btn-sm" @click="testGemini()" :disabled="testingGemini">
                        <template x-if="!testingGemini">
                            <span class="flex-center gap-4"><i data-lucide="zap"></i> Uji koneksi Gemini</span>
                        </template>
                        <template x-if="testingGemini">
                            <span class="flex-center gap-4">
                                <span class="spinner" style="border-color:#cfd6dd;border-top-color:var(--brand)"></span>
                                Menghubungi Google AI Studio...
                            </span>
                        </template>
                    </button>
                    <span class="hint">Satu permintaan tes singkat dikirim ke Google AI Studio.</span>
                </div>

                <template x-if="geminiResult">
                    <div class="mt-12">
                        <div class="alert" :class="geminiResult.success ? 'alert-ok' : 'alert-danger'">
                            <i :data-lucide="geminiResult.success ? 'check-circle-2' : 'alert-circle'"></i>
                            <div class="body">
                                <strong x-text="geminiResult.message"></strong>
                                <span class="text-sm" x-show="geminiResult.reply" x-text="geminiResult.reply"></span>
                            </div>
                        </div>
                        <div class="flex-center flex-wrap gap-8 mt-8">
                            <span class="chip" x-show="geminiResult.model">
                                Model: <span class="strong" x-text="geminiResult.model"></span>
                            </span>
                            <span class="chip" x-show="geminiResult.latency_ms">
                                Waktu respons: <span class="strong" x-text="geminiResult.latency_ms"></span>&nbsp;ms
                            </span>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <section class="card" id="section-supabase">
            <div class="card-head">
                <div>
                    <h2>Supabase Cloud</h2>
                    <span class="hint">Sinkronisasi stok dan penyimpanan foto hasil deteksi kamera.</span>
                </div>
                <span class="chip chip-info">REST + Storage</span>
            </div>

            <div class="card-body">
                <div class="grid grid-2">
                    <div class="field">
                        <label class="label" for="setting_supabase_url">Project URL</label>
                        <input class="input mono" type="url" id="setting_supabase_url" name="supabase_url"
                               value="{{ $supabaseUrl }}" placeholder="https://xxxx.supabase.co">
                        @error('supabase_url')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="setting_supabase_key">Anon / Service Key</label>
                        <input class="input mono" type="password" id="setting_supabase_key" name="supabase_key"
                               value="" autocomplete="off" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...">
                        <div class="hint">
                            Cara simpannya sama seperti kunci Gemini: database lebih dulu, lalu .env sebagai cadangan.
                            Kosongkan bila kunci tidak diganti.
                        </div>
                        @error('supabase_key')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="setting_supabase_bucket">Nama bucket foto</label>
                        <input class="input mono" type="text" id="setting_supabase_bucket" name="supabase_bucket"
                               value="{{ $supabaseBucket }}" placeholder="ingredients">
                        <div class="hint">Folder di Supabase Storage tempat foto kulkas diunggah.</div>
                        @error('supabase_bucket')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="setting_campus">Nama kampus pada laporan</label>
                        <input class="input" type="text" id="setting_campus" name="campus_name"
                               value="{{ old('campus_name', $campusName) }}">
                        <div class="hint">Dipakai sebagai identitas pada dokumen laporan.</div>
                        @error('campus_name')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="flex-between flex-wrap mt-8">
                    <button type="button" class="btn btn-secondary btn-sm" @click="testSupabase()" :disabled="testingSupabase">
                        <template x-if="!testingSupabase">
                            <span class="flex-center gap-4"><i data-lucide="activity"></i> Uji koneksi Supabase</span>
                        </template>
                        <template x-if="testingSupabase">
                            <span class="flex-center gap-4">
                                <span class="spinner" style="border-color:#cfd6dd;border-top-color:var(--brand)"></span>
                                Menghubungi Supabase Cloud...
                            </span>
                        </template>
                    </button>
                    <span class="hint">Diuji dengan memanggil endpoint <span class="mono">/rest/v1/</span>.</span>
                </div>

                <template x-if="supabaseResult">
                    <div class="mt-12">
                        <div class="alert" :class="supabaseResult.success ? 'alert-ok' : 'alert-danger'">
                            <i :data-lucide="supabaseResult.success ? 'check-circle-2' : 'alert-circle'"></i>
                            <div class="body">
                                <strong x-text="supabaseResult.message"></strong>
                                <span class="text-sm mono" x-show="supabaseResult.url" x-text="supabaseResult.url"></span>
                            </div>
                        </div>
                        <div class="flex-center flex-wrap gap-8 mt-8">
                            <span class="chip" x-show="supabaseResult.status_code">
                                Status HTTP: <span class="strong" x-text="supabaseResult.status_code"></span>
                            </span>
                            <span class="chip" x-show="supabaseResult.latency_ms">
                                Waktu respons: <span class="strong" x-text="supabaseResult.latency_ms"></span>&nbsp;ms
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="card-foot flex-between">
                <span class="text-xs text-muted">Key ini dipakai semua fitur AI dan sinkronisasi cloud.</span>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> Simpan konfigurasi API
                </button>
            </div>
        </section>
    </form>

    <div class="card is-flat">
        <div class="card-body tight flex-between flex-wrap">
            <div>
                <div class="strong">Perlu lampiran untuk laporan?</div>
                <div class="text-sm text-muted">Cetak rekap stok berikut identitas mahasiswa sebagai bukti pengujian.</div>
            </div>
            <a href="{{ route('inventory.print') }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                <i data-lucide="printer"></i> Cetak laporan inventaris
            </a>
        </div>
    </div>
    </div>
</div>
@endsection
