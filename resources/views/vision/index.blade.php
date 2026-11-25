@extends('layouts.app')

@section('title', 'Deteksi Foto Bahan')

@section('content')
<div x-data="{
    activeTab: 'camera', // 'camera' or 'upload'
    stream: null,
    cameraActive: false,
    cameraError: null,
    capturedImage: null,
    previewUrl: null,
    selectedSample: null,
    loading: false,
    detectedItems: [],
    summary: '',
    latency: 0,
    demoNotice: '',

    init() {
        // Otomatis buka kamera jika di tab kamera
        this.$nextTick(() => {
            if (this.activeTab === 'camera') {
                this.startCamera();
            }
        });
    },

    startCamera() {
        this.cameraError = null;
        this.capturedImage = null;
        this.previewUrl = null;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.cameraError = 'Perangkat atau browser Anda tidak mendukung akses kamera langsung. Silakan gunakan tab Unggah Berkas.';
            this.activeTab = 'upload';
            return;
        }

        const constraints = {
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(mediaStream => {
                this.stream = mediaStream;
                this.cameraActive = true;
                this.$nextTick(() => {
                    const video = document.getElementById('camera_feed');
                    if (video) {
                        video.srcObject = mediaStream;
                        video.play().catch(e => console.log('Autoplay prevented:', e));
                    }
                });
            })
            .catch(err => {
                // Fallback ke kamera standar jika environment/belakang gagal
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(mediaStream => {
                        this.stream = mediaStream;
                        this.cameraActive = true;
                        this.$nextTick(() => {
                            const video = document.getElementById('camera_feed');
                            if (video) {
                                video.srcObject = mediaStream;
                                video.play().catch(e => console.log('Autoplay prevented:', e));
                            }
                        });
                    })
                    .catch(e => {
                        console.error('Kamera gagal dibuka:', e);
                        this.cameraError = 'Izin kamera belum diberikan atau kamera tidak terdeteksi. Silakan berikan izin akses kamera atau beralih ke tab Unggah Berkas.';
                        this.cameraActive = false;
                    });
            });
    },

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        this.cameraActive = false;
    },

    switchTab(tab) {
        this.activeTab = tab;
        if (tab === 'camera') {
            if (!this.capturedImage) {
                this.startCamera();
            }
        } else {
            this.stopCamera();
        }
    },

    capturePhoto() {
        const video = document.getElementById('camera_feed');
        if (!video) return;

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.88);
        this.capturedImage = dataUrl;
        this.previewUrl = dataUrl;
        this.stopCamera();
    },

    retakePhoto() {
        this.capturedImage = null;
        this.previewUrl = null;
        this.detectedItems = [];
        this.summary = '';
        this.startCamera();
    },

    onFileChange(e) {
        const file = e.target.files[0];
        if (file) {
            this.selectedSample = null;
            this.capturedImage = null;
            this.previewUrl = URL.createObjectURL(file);
            this.detectedItems = [];
            this.summary = '';
            this.demoNotice = '';
        }
    },

    selectSample(key, url) {
        this.selectedSample = key;
        this.capturedImage = null;
        this.previewUrl = url;
        this.detectedItems = [];
        this.summary = '';
        this.demoNotice = '';
    },

    removeItem(index) {
        this.detectedItems.splice(index, 1);
        if (this.detectedItems.length === 0) {
            this.summary = 'Semua bahan hasil deteksi telah dibersihkan.';
        }
    },

    getIcon(name) {
        if (!name) return '🥦';
        const raw = name.toLowerCase();
        if (raw.includes('sachet') || raw.includes('bumbu instan') || raw.includes('racik') || raw.includes('bumbu')) return '🧂';
        if (raw.includes('kaleng')) return '🥫';
        if (raw.includes('kotak') || raw.includes('kemasan') || raw.includes('sachet')) return '📦';
        if (raw.includes('santan') || raw.includes('susu')) return '🥛';
        if (raw.includes('kecap') || raw.includes('saus') || raw.includes('minyak')) return '🍶';
        if (raw.includes('tepung') || raw.includes('beras') || raw.includes('nasi')) return '🍚';
        if (raw.includes('gula')) return '🍬';
        if (raw.includes('garam')) return '🧂';
        if (raw.includes('mie') || raw.includes('bihun') || raw.includes('pasta')) return '🍜';
        if (raw.includes('jahe') || raw.includes('kunyit') || raw.includes('lengkuas') || raw.includes('serai') || raw.includes('kencur')) return '🫚';
        if (raw.includes('wortel')) return '🥕';
        if (raw.includes('paprika')) return '🫑';
        if (raw.includes('timun') || raw.includes('mentimun')) return '🥒';
        if (raw.includes('terong')) return '🍆';
        if (raw.includes('jagung')) return '🌽';
        if (raw.includes('tomat')) return '🍅';
        if (raw.includes('kentang')) return '🥔';
        if (raw.includes('brokoli') || raw.includes('kembang kol')) return '🥦';
        if (raw.includes('kubis') || raw.includes('kol') || raw.includes('selada') || raw.includes('sawi') || raw.includes('pakcoy')) return '🥬';
        if (raw.includes('kangkung') || raw.includes('bayam') || raw.includes('daun') || raw.includes('seledri')) return '🌿';
        if (raw.includes('cabai') || raw.includes('cabe') || raw.includes('rawit')) return '🌶️';
        if (raw.includes('bawang putih')) return '🧄';
        if (raw.includes('bawang')) return '🧅';
        if (raw.includes('telur')) return '🥚';
        if (raw.includes('ayam') || raw.includes('daging')) return '🍗';
        if (raw.includes('ikan') || raw.includes('udang')) return '🐟';
        if (raw.includes('tahu')) return '🧈';
        if (raw.includes('tempe')) return '🥪';
        if (raw.includes('beras') || raw.includes('nasi')) return '🍚';
        if (raw.includes('mie')) return '🍜';
        if (raw.includes('apel')) return '🍎';
        if (raw.includes('jeruk') || raw.includes('lemon')) return '🍋';
        return '🥕';
    },

    analyzePhoto() {
        const fileInput = document.getElementById('camera_input');
        const hasFile = fileInput && fileInput.files && fileInput.files[0];

        if (!this.capturedImage && !hasFile && !this.selectedSample) {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Ada Foto',
                text: 'Silakan ambil foto menggunakan kamera atau pilih foto kulkas Anda.',
                background: '#111C38',
                color: '#fff'
            });
            return;
        }

        this.loading = true;
        this.detectedItems = [];
        this.summary = '';

        const formData = new FormData();

        if (this.capturedImage) {
            formData.append('image_base64', this.capturedImage);
        } else if (hasFile) {
            formData.append('image', fileInput.files[0]);
        } else if (this.selectedSample) {
            formData.append('sample', this.selectedSample);
        }

        fetch('{{ route('vision.analyze') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            this.loading = false;
            if (res.success && res.data) {
                this.detectedItems = res.data.detected_items || [];
                this.summary = res.data.summary || '';
                this.latency = res.latency_ms || 0;
                this.demoNotice = res.notice || '';
                lucide.createIcons();

                if (this.detectedItems.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Tidak Ada Bahan Makanan',
                        text: 'AI tidak menemukan bahan makanan pada foto ini. Coba foto yang lebih jelas atau pilih contoh sampel.',
                        background: '#111C38',
                        color: '#fff'
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Pemindaian Gagal',
                    text: res.message || 'Terjadi kendala saat menganalisis foto bahan.',
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
                title: 'Koneksi Terputus',
                text: 'Gagal menghubungi server.',
                background: '#111C38',
                color: '#fff'
            });
        });
    }
}" x-init="init()" class="stack-20">

    {{-- Header Halaman Sederhana --}}
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-main">
                <span class="eyebrow"><i data-lucide="scan-line"></i> Pemindai Cerdas</span>
                <h1>Deteksi Bahan Masakan dari Foto</h1>
                <p class="sub">Foto langsung isi kulkas atau meja dapur Anda, AI akan mengenali nama bahan dan mencatatnya ke inventaris secara otomatis.</p>
            </div>
            <div class="page-head-actions">
                <a href="{{ route('inventory.index') }}" class="btn btn-secondary">
                    <i data-lucide="arrow-left"></i> Kembali ke Inventaris
                </a>
            </div>
        </div>
    </div>

    {{-- Layout Kerja 2 Kolom Bersih --}}
    <div class="grid grid-side gap-20">

        {{-- Panel Kiri: Kamera Langsung atau Unggah Foto --}}
        <div class="card">
            {{-- Tab Pilihan: Kamera Langsung vs Unggah Berkas --}}
            <div class="card-head" style="padding:10px 16px; background:var(--bg-subtle); border-bottom:1px solid var(--border)">
                <div class="flex gap-8" style="width:100%">
                    <button type="button" @click="switchTab('camera')"
                        class="btn btn-sm flex-1 flex-center gap-6"
                        :class="activeTab === 'camera' ? 'btn-primary' : 'btn-ghost'">
                        <i data-lucide="camera" style="width:14px;height:14px"></i>
                        <span>Kamera Langsung</span>
                    </button>
                    <button type="button" @click="switchTab('upload')"
                        class="btn btn-sm flex-1 flex-center gap-6"
                        :class="activeTab === 'upload' ? 'btn-primary' : 'btn-ghost'">
                        <i data-lucide="upload" style="width:14px;height:14px"></i>
                        <span>Unggah File</span>
                    </button>
                </div>
            </div>

            <div class="card-body">
                {{-- Mode 1: Kamera Langsung (Webcam / HP Camera) --}}
                <div x-show="activeTab === 'camera'" class="stack-16">
                    {{-- Alert jika kamera error --}}
                    <div x-show="cameraError" class="alert alert-warn" style="margin-bottom:0">
                        <i data-lucide="alert-circle"></i>
                        <div class="body text-xs" x-text="cameraError"></div>
                    </div>

                    {{-- Live Viewfinder (saat kamera aktif dan belum di-capture) --}}
                    <div x-show="!capturedImage" style="position:relative; width:100%; border-radius:12px; overflow:hidden; background:#0f172a; aspect-ratio:4/3; display:flex; align-items:center; justify-content:center;">
                        <video id="camera_feed" playsinline autoplay muted style="width:100%; height:100%; object-fit:cover;"></video>

                        {{-- Frame Target Visual --}}
                        <div style="position:absolute; inset:16px; border:2px dashed rgba(255,255,255,0.4); border-radius:10px; pointer-events:none; display:flex; align-items:flex-end; justify-content:center; padding:12px">
                            <span style="background:rgba(0,0,0,0.6); color:#fff; font-size:12px; padding:4px 10px; border-radius:20px; backdrop-filter:blur(4px)">
                                Arahkan kamera ke bahan makanan
                            </span>
                        </div>
                    </div>

                    {{-- Tombol Ambil Foto --}}
                    <div x-show="!capturedImage">
                        <button type="button" @click="capturePhoto()" class="btn btn-primary btn-block btn-lg flex-center gap-8">
                            <i data-lucide="camera"></i>
                            <span>Ambil Foto Bahan</span>
                        </button>
                    </div>

                    {{-- Preview Foto yang Baru Diambil (Freeze Frame) --}}
                    <div x-show="capturedImage" class="stack-12">
                        <div style="width:100%; border-radius:12px; overflow:hidden; border:2px solid var(--brand); aspect-ratio:4/3; background:#000;">
                            <img :src="capturedImage" alt="Hasil Foto" style="width:100%; height:100%; object-fit:cover;">
                        </div>

                        <div class="grid grid-2 gap-8">
                            <button type="button" @click="retakePhoto()" :disabled="loading" class="btn btn-secondary flex-center gap-6">
                                <i data-lucide="refresh-cw"></i>
                                <span>Foto Ulang</span>
                            </button>
                            <button type="button" @click="analyzePhoto()" :disabled="loading" class="btn btn-primary flex-center gap-6">
                                <span x-show="!loading" class="flex-center gap-6">
                                    <i data-lucide="sparkles"></i>
                                    <span>Pindai Foto AI</span>
                                </span>
                                <span x-show="loading" class="flex-center gap-6">
                                    <span class="ai-titik"><i></i><i></i><i></i></span>
                                    <span>Menganalisis...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Mode 2: Unggah File / Sampel Foto --}}
                <div x-show="activeTab === 'upload'" class="stack-16">
                    <label for="camera_input" class="dropzone" style="display:block; cursor:pointer">
                        <input type="file" id="camera_input" accept="image/*" @change="onFileChange($event)" class="hide">

                        <div x-show="!previewUrl || capturedImage">
                            <i data-lucide="upload-cloud" style="width:32px;height:32px;color:var(--brand)"></i>
                            <div class="strong text-sm mt-8" style="color:var(--ink)">Pilih Foto dari Komputer / HP</div>
                            <div class="text-xs text-muted">Format JPG, PNG, WEBP (Maksimal 10 MB)</div>
                        </div>

                        <div x-show="previewUrl && !capturedImage">
                            <div class="preview-frame" style="max-height:220px">
                                <img :src="previewUrl" alt="Foto Dipilih">
                            </div>
                            <div class="text-xs text-muted mt-8">Klik untuk ganti foto</div>
                        </div>
                    </label>

    {{-- Pilihan Contoh Sampel Cepat --}}
                    <div>
                        <div class="eyebrow" style="margin-bottom:6px">Atau Uji Contoh Sampel:</div>
                        <div class="grid grid-2 gap-8">
                            @foreach ($sampel as $key => $s)
                                <button type="button" @click="selectSample('{{ $key }}', '{{ $s['url'] }}')"
                                    class="card sample-thumb" :class="selectedSample === '{{ $key }}' ? 'is-dashed' : ''">
                                    <img src="{{ $s['url'] }}" alt="{{ $s['label'] }}" loading="lazy">
                                    <span class="text-xs strong">{{ $s['label'] }}</span>
                                    <span class="text-xs text-muted">{{ $s['ket'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" @click="analyzePhoto()" :disabled="loading" class="btn btn-primary btn-block btn-lg">
                        <span x-show="!loading" class="flex-center gap-8">
                            <i data-lucide="sparkles"></i>
                            <span>Pindai Foto dengan AI</span>
                        </span>
                        <span x-show="loading" class="flex-center gap-8">
                            <span class="ai-titik"><i></i><i></i><i></i></span>
                            <span>Memindai Objek Bahan...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Panel Kanan: Hasil Deteksi Sederhana & Bersih --}}
        <div>
            {{-- Kondisi 1: Belum Ada Analisis --}}
            <div x-show="!loading && detectedItems.length === 0" class="card">
                <div class="empty" style="padding:40px 20px">
                    <i data-lucide="scan-line" style="width:36px;height:36px;color:var(--brand)"></i>
                    <h3>Hasil Deteksi Bahan Akan Tampil Di Sini</h3>
                    <p class="text-muted" style="max-width:380px; margin:0 auto">
                        Ambil foto bahan makanan dengan kamera atau unggah foto, lalu tekan tombol pindai untuk mengenali nama bahan secara otomatis.
                    </p>
                </div>
            </div>

            {{-- Kondisi 2: Sedang Memproses --}}
            <div x-show="loading" class="card">
                @include('partials.ai-wait', [
                    'judul' => 'AI Sedang Mengenali Bahan Makanan',
                    'langkah' => [
                        'Membaca foto ke dalam memori aman',
                        'Memindai isi kulkas per rak',
                        'Mengenal bumbu sachet & kemasan',
                        'Menaksir jumlah dan masa simpan',
                    ],
                    'catatan' => 'Gemini AI mencocokkan warna, tekstur, dan bentuk tiap bahan sebelum menghitung estimasinya.',
                ])
            </div>

            {{-- Kondisi 3: Hasil Deteksi Tampil (Sederhana, Bersih, Format Baris Ringkas) --}}
            <div x-show="!loading && detectedItems.length > 0" class="card">
                <div class="card-head flex-between" style="border-bottom:1px solid var(--border)">
                    <div>
                        <h3 class="mb-0 flex-center gap-8">
                            <span class="dot dot-ok"></span>
                            <span>Bahan Berhasil Dikenali</span>
                        </h3>
                        <span class="hint text-xs text-muted" x-text="summary"></span>
                    </div>
                    <span class="chip chip-brand mono strong" x-text="detectedItems.length + ' Item'"></span>
                </div>

                <div x-show="demoNotice" class="alert alert-warn" style="margin:12px 16px 0">
                    <i data-lucide="alert-triangle"></i>
                    <div class="body text-xs" x-text="demoNotice"></div>
                </div>

                <form action="{{ route('vision.save-batch') }}" method="POST">
                    @csrf

                    <div class="card-body flush" style="max-height:480px; overflow-y:auto">
                        <table class="table" style="font-size:13px">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Nama Bahan Makanan</th>
                                    <th style="width:110px">Kategori</th>
                                    <th style="width:140px">Takaran</th>
                                    <th style="width:100px">Masa Simpan</th>
                                    <th style="width:40px; text-align:center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in detectedItems" :key="index">
                                    <tr>
                                        <td>
                                            <span style="font-size:18px" x-text="getIcon(item.item_name)"></span>
                                        </td>
                                        <td>
                                            <input type="text" :name="'items[' + index + '][item_name]'" x-model="item.item_name" required
                                                class="input" style="padding:6px 10px; font-weight:600">
                                            <input type="hidden" :name="'items[' + index + '][storage_location]'" :value="item.storage_location || 'Kulkas (Chiller)'">
                                            <input type="hidden" :name="'items[' + index + '][freshness_notes]'" :value="item.freshness_notes || 'Terdeteksi otomatis via Vision AI'">
                                        </td>
                                        <td>
                                            <span class="chip chip-info text-xs" x-text="item.category"></span>
                                            <input type="hidden" :name="'items[' + index + '][category]'" :value="item.category">
                                        </td>
                                        <td>
                                            <div class="flex-center gap-4">
                                                <input type="number" step="0.5" :name="'items[' + index + '][estimated_quantity]'" x-model="item.estimated_quantity" required
                                                    class="input mono" style="padding:6px 8px; width:55px; text-align:center">
                                                <input type="text" :name="'items[' + index + '][unit]'" x-model="item.unit" required
                                                    class="input" style="padding:6px 8px; width:65px">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="chip text-xs mono" x-text="item.typical_shelf_life_days + ' hari'"></span>
                                            <input type="hidden" :name="'items[' + index + '][typical_shelf_life_days]'" :value="item.typical_shelf_life_days">
                                        </td>
                                        <td style="text-align:center">
                                            <button type="button" @click="removeItem(index)" class="btn btn-ghost btn-sm" style="padding:4px 6px; color:var(--danger)" title="Hapus bahan ini">
                                                <i data-lucide="x" style="width:14px;height:14px"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-foot flex-between">
                        <button type="button" @click="detectedItems = []" class="btn btn-ghost btn-sm text-muted">
                            Bersihkan Hasil
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="check-check"></i>
                            <span>Simpan Semua ke Inventaris</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
