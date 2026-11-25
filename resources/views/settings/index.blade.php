@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="page-head">
    <div class="page-head-row">
        <div class="page-head-main">
            <div class="page-head-meta">
                <span class="eyebrow"><i data-lucide="sliders-horizontal"></i> Konfigurasi Sistem</span>
                <span class="chip chip-brand">Gemini API &amp; Supabase Cloud</span>
            </div>
            <h1>Pengaturan Sistem</h1>
            <p class="sub">
                Atur profil dan preferensi memasak, lalu lengkapi kunci API Gemini serta koneksi Supabase Cloud.
                Preferensi profil dipakai generator resep, sedangkan kunci API disimpan ke database.
            </p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<div class="grid grid-side" x-data="{
    testingGemini: false,
    geminiResult: null,
    testingSupabase: false,
    supabaseResult: null,
    sqlCopied: false,
    projectRef: '{{ $supabaseRef }}',
    setupSql: @js($setupSql),

    copySetupSql() {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(this.setupSql);
            this.sqlCopied = true;
            setTimeout(() => this.sqlCopied = false, 3500);
        }
    },

    getProjectRef() {
        const url = document.getElementById('setting_supabase_url')?.value || '';
        const m = url.match(/https:\/\/([a-z0-9_-]+)\.supabase\.co/i);
        return m ? m[1] : (this.projectRef || 'lyugejvitpgpmtsdtzsi');
    },

    testGemini() {
        const keyBaru = document.getElementById('setting_gemini_key').value.trim();
        const model = document.getElementById('setting_gemini_model').value;

        this.testingGemini = true;
        this.geminiResult = null;

        window.AiTunggu?.jalankan('kunci', { estimasi: 12000 });

        fetch('{{ route('settings.test-gemini') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ api_key: keyBaru.length >= 30 ? keyBaru : null, model: model })
        })
        .then((res) => {
            window.AiTunggu?.geser('kunci', 0.85);
            return res.json();
        })
        .then(res => {
            this.testingGemini = false;
            this.geminiResult = res;
            window.AiTunggu?.selesai('kunci');
        })
        .catch(err => {
            this.testingGemini = false;
            this.geminiResult = { success: false, message: 'Gagal menghubungi server lokal.' };
            window.AiTunggu?.gagal('kunci');
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
    },

    // ===== Skema cloud & ERD =====
    tabelCloud: [],
    cekTabel: false,
    membangunSkema: false,
    hasilBangun: null,
    mengirimData: false,
    hasilKirim: null,
    perluToken: {{ $perluToken ? 'true' : 'false' }},

    get tabelBelumAda() {
        return this.tabelCloud.filter(t => !t.ada).map(t => t.tabel);
    },

    kirimJson(route, payload = {}) {
        return fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify(payload)
        }).then(res => res.json());
    },

    muatTabelCloud() {
        this.cekTabel = true;

        this.kirimJson('{{ route('settings.supabase.tables') }}')
            .then(res => {
                this.tabelCloud = res.tabel || [];
                this.perluToken = !!res.perlu_token;
            })
            .catch(() => this.tabelCloud = [])
            .finally(() => this.cekTabel = false);
    },

    bangunSkema() {
        this.membangunSkema = true;
        this.hasilBangun = null;

        this.kirimJson('{{ route('settings.supabase.build') }}')
            .then(res => {
                this.hasilBangun = res;
                if (res.tabel) this.tabelCloud = res.tabel;
                lucide.createIcons();
            })
            .catch(() => this.hasilBangun = { success: false, message: 'Gagal menghubungi server lokal.' })
            .finally(() => this.membangunSkema = false);
    },

    sinkronkanData() {
        this.mengirimData = true;
        this.hasilKirim = null;

        this.kirimJson('{{ route('settings.supabase.sync-data') }}')
            .then(res => {
                this.hasilKirim = res;
                this.muatTabelCloud();
                lucide.createIcons();
            })
            .catch(() => this.hasilKirim = { success: false, message: 'Gagal menghubungi server lokal.' })
            .finally(() => this.mengirimData = false);
    },

    init() {
        if ('{{ $supabaseUrl }}' !== '') this.muatTabelCloud();
    }
}">

    {{-- Ringkasan status; nilai kunci tidak pernah ditampilkan di halaman ini --}}
    <aside class="col">
        <div class="card">
            <div class="card-head"><h3 class="mb-0">Status Koneksi</h3></div>
            <div class="card-body tight col gap-8">
                <div class="flex-between">
                    <span class="text-sm">Gemini API</span>
                    @if ($geminiKeyDatabaseSalah)
                        <span class="chip chip-warn"><span class="dot dot-warn"></span> Kunci di database tidak valid</span>
                    @elseif ($geminiApiKey)
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
            </div>
            <div class="card-foot flex-center flex-wrap">
                <a href="#section-profil" class="text-sm">Profil</a>
                <span class="text-muted">&middot;</span>
                <a href="#section-gemini" class="text-sm">Gemini</a>
                <span class="text-muted">&middot;</span>
                <a href="#section-google" class="text-sm">Google</a>
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
            <span class="chip chip-brand">Akun Anda</span>
        </div>

        <div class="card-body">
            <div class="grid grid-2">
                <div class="field">
                    <label class="label" for="profil_nama">Nama lengkap <span class="req">*</span></label>
                    <input class="input" type="text" id="profil_nama" name="name"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')
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
          onsubmit="['setting_gemini_key','setting_supabase_key','setting_google_id','setting_google_secret'].forEach(function(id){var el=document.getElementById(id); if(!el.value) el.disabled=true;});">
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
                               value="" autocomplete="new-password" placeholder="AIzaSy... atau AQ...">
                        <div class="hint">
                            Disimpan pada tabel <span class="mono">system_settings</span>; bila dikosongkan, sistem mengambil
                            <span class="mono">GEMINI_API_KEY</span> dari file .env.
                            Kosongkan bila kunci tidak diganti.
                        </div>
                        @if ($geminiKeyDatabaseSalah)
                            <div class="alert alert-warn">
                                <i data-lucide="alert-triangle"></i>
                                <div class="text-sm">
                                    Nilai <span class="mono">gemini_api_key</span> di database bukan bentuk API key Google, jadi sistem
                                    memakai kunci dari <span class="mono">.env</span> agar AI tetap jalan.
                                    Tempel kunci utuh dari Google AI Studio di kolom atas lalu simpan untuk menggantinya.
                                </div>
                            </div>
                        @endif
                        @error('gemini_api_key')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="setting_gemini_model">Model Gemini</label>
                        <select class="select" id="setting_gemini_model" name="gemini_model">
                            <option value="gemini-3-flash-preview" {{ $geminiModel === 'gemini-3-flash-preview' || !$geminiModel ? 'selected' : '' }}>
                                Gemini 3 Flash (sangat cepat, stabil &amp; direkomendasikan)
                            </option>
                            <option value="gemini-3.1-flash-lite" {{ $geminiModel === 'gemini-3.1-flash-lite' ? 'selected' : '' }}>
                                Gemini 3.1 Flash Lite (paling hemat kuota)
                            </option>
                            <option value="gemini-3.8-flash" {{ $geminiModel === 'gemini-3.8-flash' ? 'selected' : '' }}>
                                Gemini 3.8 Flash (fitur penalaran terbaru)
                            </option>
                            <option value="gemini-3.1-pro-preview" {{ $geminiModel === 'gemini-3.1-pro-preview' ? 'selected' : '' }}>
                                Gemini 3.1 Pro (penalaran kompleks &amp; resep mendalam)
                            </option>
                            <option value="gemini-3.5-flash" {{ $geminiModel === 'gemini-3.5-flash' ? 'selected' : '' }}>
                                Gemini 3.5 Flash (server sering padat)
                            </option>
                        </select>
                        <div class="hint">Model ringan mempercepat respons resep dan menekan latensi API.</div>
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
                                <span class="ai-titik" data-ai-titik="kunci"><i></i><i></i><i></i></span>
                                Menghubungi Google AI Studio<span class="ai-jam" data-ai-jam></span>
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

        <section class="card" id="section-google">
            <div class="card-head">
                <div>
                    <h2>Login Google</h2>
                    <span class="hint">Pengguna bisa daftar dan masuk memakai akun Google; akun itu dicatat sistem sehingga kunjungan berikutnya tinggal klik sekali.</span>
                </div>
                @if ($googleSiap)
                    <span class="chip chip-ok"><span class="dot dot-ok"></span> Aktif</span>
                @else
                    <span class="chip chip-warn"><span class="dot dot-warn"></span> Belum aktif</span>
                @endif
            </div>

            <div class="card-body">
                @if ($googlePerluDiperbaiki !== [])
                    <div class="alert alert-warn">
                        <i data-lucide="alert-triangle"></i>
                        <div class="text-sm">
                            Nilai tersimpan di database belum berbentuk kredensial Google untuk
                            <span class="strong">{{ implode(' dan ', $googlePerluDiperbaiki) }}</span>, jadi sistem memakai nilai dari
                            <span class="mono">.env</span> agar tombol Google tetap bisa dipakai.
                            Tempel ulang dari Google Cloud &gt; Google Auth Platform &gt; Clients, lalu simpan.
                        </div>
                    </div>
                @endif
                <div class="grid grid-2">
                    <div class="field">
                        <label class="label" for="setting_google_id">OAuth Client ID</label>
                        <input class="input mono" type="text" id="setting_google_id" name="google_client_id"
                               value="" autocomplete="off" placeholder="xxxx.apps.googleusercontent.com">
                        <div class="hint">Kosongkan bila tidak diganti. Tersimpan di database, tidak ditulis ke file .env.</div>
                        @error('google_client_id')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="setting_google_secret">OAuth Client Secret</label>
                        <input class="input mono" type="password" id="setting_google_secret" name="google_client_secret"
                               value="" autocomplete="off" placeholder="GOCSPX-...">
                        <div class="hint">Sama seperti kunci lain: hanya diisi ulang, tidak dibaca balik.</div>
                        @error('google_client_secret')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="field">
                    <label class="label">Authorized redirect URI di Google Cloud</label>
                    <input class="input mono" type="text" readonly value="{{ $googleCallback }}"
                           onclick="this.select()">
                    <div class="hint">Tempel persis seperti ini pada <em>Authorized redirect URIs</em>, lalu simpan.
                        Tombol Google langsung muncul di halaman Masuk &amp; Daftar tanpa perlu <span class="mono">php artisan config:clear</span>.</div>
                </div>

                <div class="alert alert-info mt-12">
                    <i data-lucide="list-checks"></i>
                    <div class="body">
                        <strong>Cara membuat API-nya</strong>
                        <ol class="text-sm mt-8 mb-0">
                            <li>Buka <a href="https://console.cloud.google.com/auth/overview?project=resepify" target="_blank" rel="noopener">Google Cloud Console</a>, lalu di deretan atas pilih project <strong>Resepify</strong> (bukan Agrilens).</li>
                            <li>Menu kiri <strong>Google Auth Platform &rarr; Branding</strong>: isi <em>App name</em> <span class="mono">Resepify</span>, <em>User support email</em>, dan <em>Developer contact email</em> dengan email Anda &rarr; <strong>Save</strong>.</li>
                            <li><strong>Google Auth Platform &rarr; Audience</strong>: biarkan <em>User type</em> <strong>External</strong>. Selama statusnya masih <em>Testing</em>, klik <strong>Add users</strong> dan masukkan alamat Gmail yang akan dipakai login &mdash; tanpa ini Google menolak dengan pesan <span class="mono">access_denied</span>.</li>
                            <li><strong>Google Auth Platform &rarr; Clients</strong> &rarr; tombol <strong>Create Clients</strong> &rarr; <em>Application type</em>: <strong>Web application</strong>, beri nama <span class="mono">Resepify local</span>.</li>
                            <li>Isi <strong>Authorized JavaScript origins</strong> (Add URI): <span class="mono">{{ rtrim(url('/'), '/') }}</span></li>
                            <li>Isi <strong>Authorized redirect URIs</strong> (Add URI): tempel persis nilai pada kolom baca-saja di atas, lalu <strong>Create</strong>.</li>
                            <li>Akan muncul <strong>Client ID</strong> dan <strong>Client Secret</strong>. Salin keduanya (atau <em>Download client IDs</em> untuk menyimpan .txt) lalu tempel ke dua kolom di atas &rarr; <strong>Simpan konfigurasi API</strong>.</li>
                            <li>Selesai: tombol Google langsung muncul di halaman Masuk &amp; Daftar. Klik sekali, pilih akun Gmail tadi, dan akun Anda tercatat otomatis.</li>
                        </ol>
                        <div class="hint mt-12">
                            Butuh bantuan? Kirim saja <strong>Client ID</strong>-nya ke saya (yang ini bukan rahasia).
                            Untuk <strong>Client Secret</strong>, tempel sendiri di kolom di atas &mdash; tidak perlu dikirim ke mana-mana.
                        </div>
                    </div>
                </div>
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
                    <span class="hint">Diuji dengan memanggil endpoint <span class="mono">/rest/v1/</span> dan <span class="mono">/storage/v1/bucket/</span>.</span>
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

                        {{-- Panduan Solusi Cepat bila Tabel Belum Ada (HTTP 404) --}}
                        <template x-if="!supabaseResult.success && supabaseResult.status_code === 404">
                            <div class="card mt-12" style="background:var(--bg); border: 1px solid var(--line-strong);">
                                <div class="card-body tight stack-12">
                                    <div class="strong text-sm flex-center gap-6" style="color:var(--brand-dark)">
                                        <i data-lucide="wrench" style="width:16px;height:16px"></i>
                                        <span>Cara Pasang Tabel ke Supabase (Hanya 1 Menit):</span>
                                    </div>
                                    <ol class="text-sm text-muted stack-6" style="padding-left:18px;margin:0">
                                        <li>Tekan tombol <strong>Salin Script SQL Setup</strong> di bawah.</li>
                                        <li>Tekan tombol <strong>Buka SQL Editor Supabase</strong> (membuka tab baru).</li>
                                        <li>Paste (<span class="mono">Ctrl + V</span>) di halaman query Supabase, lalu klik tombol hijau <strong>RUN</strong>.</li>
                                        <li>Kembali ke tab ini dan klik <strong>Uji koneksi Supabase</strong> lagi. Selesai!</li>
                                    </ol>
                                    <div class="flex-center flex-wrap gap-8 pt-4">
                                        <button type="button" class="btn btn-primary btn-sm" @click="copySetupSql()">
                                            <i data-lucide="copy"></i> <span x-text="sqlCopied ? 'Script Berhasil Disalin!' : 'Salin Script SQL Setup'"></span>
                                        </button>
                                        <a :href="'https://supabase.com/dashboard/project/' + getProjectRef() + '/sql/new'" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                            <i data-lucide="external-link"></i> Buka SQL Editor Supabase
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>

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

    <div class="card">
        <div class="card-head">
            <h3 class="mb-0">
                <i data-lucide="database"></i>
                Skema Cloud &amp; ERD Supabase
            </h3>
            <button type="button" class="btn btn-secondary btn-sm" @click="muatTabelCloud()" :disabled="cekTabel">
                <i data-lucide="refresh-cw"></i> Muat status tabel
            </button>
        </div>

        <div class="card-body tight stack-12">
            <p class="text-sm text-muted mb-0">
                Cloud Supabase mencerminkan <span class="strong mono">9</span> tabel MySQL lokal beserta relasinya,
                sehingga halaman <span class="mono">Schema Visualizer</span> menampilkan ERD yang utuh
                (bukan hanya tabel <span class="mono">kitchen_inventories</span>).
            </p>

            <div class="flex-center flex-wrap gap-8">
                <template x-if="cekTabel">
                    <span class="hint">Memeriksa tabel di Supabase Cloud...</span>
                </template>

                <template x-if="!cekTabel && tabelCloud.length === 0">
                    <span class="hint">Status tabel belum terbaca. Isi URL &amp; key Supabase di atas, lalu muat status.</span>
                </template>

                <template x-for="t in tabelCloud" :key="t.tabel">
                    <span class="chip" :style="t.ada ? 'border-color:var(--brand)' : 'opacity:.62'">
                        <span class="mono" x-text="t.tabel"></span>
                        <span class="strong" x-text="t.ada ? ('✓ ' + (t.baris ?? 0) + ' baris') : 'belum ada'"></span>
                    </span>
                </template>
            </div>

            <template x-if="!cekTabel && tabelCloud.length > 0 && tabelBelumAda.length === 0">
                <div class="hint">
                    Sembilan tabel sudah terbentuk di cloud, jadi tombol <em>Bangun semua tabel otomatis</em> tidak diperlukan
                    lagi (dan token kelolaan tidak dibutuhkan). Tekan <strong>Sinkronkan data cloud</strong> setiap kali ada
                    data baru di MySQL lokal.
                </div>
            </template>

            <template x-if="tabelBelumAda.length > 0">
                <div class="hint">
                    Tabel yang belum terbentuk di cloud:
                    <span class="mono" x-text="tabelBelumAda.join(', ')"></span>.
                    Jalankan script SQL Setup lalu tekan <strong>Sinkronkan data cloud</strong>.
                </div>
            </template>

            <div class="flex-center flex-wrap gap-8">
                <button type="button" class="btn btn-primary btn-sm" @click="bangunSkema()" :disabled="membangunSkema">
                    <template x-if="!membangunSkema">
                        <span class="flex-center gap-4"><i data-lucide="hammer"></i> Bangun semua tabel otomatis</span>
                    </template>
                    <template x-if="membangunSkema">
                        <span class="flex-center gap-4">
                            <span class="spinner" style="border-color:#cfd6dd;border-top-color:var(--brand)"></span>
                            Membangun skema di cloud...
                        </span>
                    </template>
                </button>

                <button type="button" class="btn btn-secondary btn-sm" @click="sinkronkanData()" :disabled="mengirimData">
                    <template x-if="!mengirimData">
                        <span class="flex-center gap-4"><i data-lucide="upload-cloud"></i> Sinkronkan data cloud</span>
                    </template>
                    <template x-if="mengirimData">
                        <span class="flex-center gap-4">
                            <span class="spinner" style="border-color:#cfd6dd;border-top-color:var(--brand)"></span>
                            Mengirim data lokal...
                        </span>
                    </template>
                </button>
            </div>

            <template x-if="perluToken">
                <div class="hint">
                    Tombol otomatis memakai <span class="mono">SUPABASE_ACCESS_TOKEN</span> di <span class="mono">.env</span>
                    (buat di halaman Access Tokens Supabase). Tanpa token, pakai cara manual di bawah.
                </div>
            </template>

            <template x-if="hasilBangun">
                <div class="alert" :class="hasilBangun.success ? 'alert-ok' : 'alert-danger'">
                    <i :data-lucide="hasilBangun.success ? 'check-circle-2' : 'alert-circle'"></i>
                    <div class="body">
                        <strong x-text="hasilBangun.message"></strong>
                        <template x-if="hasilBangun.success">
                            <span class="text-sm">Buka <span class="mono">Schema Visualizer</span> Supabase untuk melihat ERD-nya.</span>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="hasilKirim">
                <div class="alert" :class="hasilKirim.success ? 'alert-ok' : 'alert-danger'">
                    <i :data-lucide="hasilKirim.success ? 'check-circle-2' : 'alert-circle'"></i>
                    <div class="body">
                        <strong x-text="hasilKirim.message"></strong>
                        <div class="text-sm mono mt-4">
                            <template x-for="r in (hasilKirim.tabel || [])" :key="r.tabel">
                                <span>
                                    <span x-text="r.tabel + ': ' + r.terkirim + '/' + r.total"></span>
                                    <span x-show="!r.ok" style="color:var(--danger)" x-text="' &leftarrow; ' + r.pesan"></span>
                                </span><br>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <div class="card is-flat">
                <div class="card-body tight stack-12">
                    <div class="strong text-sm flex-center gap-6" style="color:var(--brand-dark)">
                        <i data-lucide="wrench" style="width:16px;height:16px"></i>
                        <span>Cara manual (tanpa token, ± 1 menit):</span>
                    </div>
                    <ol class="text-sm text-muted stack-6" style="padding-left:18px;margin:0">
                        <li>Tekan <strong>Salin Script SQL Setup</strong> di bawah.</li>
                        <li>Buka <strong>SQL Editor</strong> project Supabase Anda di tab baru.</li>
                        <li>Paste (<span class="mono">Ctrl + V</span>) lalu klik <strong>RUN</strong>. Skrip boleh dijalankan berulang kali.</li>
                        <li>Kembali ke sini, klik <strong>Sinkronkan data cloud</strong> agar ERD terisi baris, lalu <strong>Muat status tabel</strong>.</li>
                    </ol>
                    <div class="flex-center flex-wrap gap-8 pt-4">
                        <button type="button" class="btn btn-secondary btn-sm" @click="copySetupSql()">
                            <i data-lucide="copy"></i> <span x-text="sqlCopied ? 'Script Berhasil Disalin!' : 'Salin Script SQL Setup'"></span>
                        </button>
                        <a :href="'https://supabase.com/dashboard/project/' + getProjectRef() + '/sql/new'" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                            <i data-lucide="external-link"></i> Buka SQL Editor Supabase
                        </a>
                        <a :href="'https://supabase.com/dashboard/project/' + getProjectRef() + '/database/schemas'" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                            <i data-lucide="network"></i> Buka ERD Schema Visualizer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card is-flat">
        <div class="card-body tight flex-between flex-wrap">
            <div>
                <div class="strong">Perlu lampiran untuk laporan?</div>
                <div class="text-sm text-muted">Cetak rekap stok dapur beserta identitas akun sebagai bukti pengujian.</div>
            </div>
            <a href="{{ route('inventory.print') }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                <i data-lucide="printer"></i> Cetak laporan inventaris
            </a>
        </div>
    </div>
    </div>
</div>
@endsection
