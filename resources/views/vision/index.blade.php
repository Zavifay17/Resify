@extends('layouts.app')

@section('title', 'Deteksi Foto Bahan')

@section('content')
<div x-data="{
    loading: false,
    previewUrl: null,
    detectedItems: [],
    summary: '',
    selectedSample: null,
    isSimulated: false,
    latency: 0,

    selectSample(key, url) {
        this.selectedSample = key;
        this.previewUrl = url;
        this.detectedItems = [];
        this.summary = '';
    },

    onFileChange(e) {
        const file = e.target.files[0];
        if (file) {
            this.selectedSample = null;
            this.previewUrl = URL.createObjectURL(file);
            this.detectedItems = [];
            this.summary = '';
        }
    },

    analyzePhoto() {
        const fileInput = document.getElementById('camera_input');
        if (!fileInput.files[0] && !this.selectedSample) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Gambar Terlebih Dahulu',
                text: 'Silakan unggah foto kulkas Anda atau pilih salah satu contoh foto pengujian.',
                background: '#111C38',
                color: '#fff'
            });
            return;
        }

        this.loading = true;
        const formData = new FormData();

        if (fileInput.files[0]) {
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
                this.isSimulated = res.is_simulated || false;
                this.latency = res.latency_ms || 0;
                if (res.image_url) {
                    this.previewUrl = res.image_url;
                }
                lucide.createIcons();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Analisis Gagal',
                    text: res.message || 'Terjadi kesalahan saat memproses gambar.',
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
}">

    {{-- Header halaman --}}
    <div class="page-head">
        <div class="flex-between" style="align-items:flex-start">
            <div>
                <div class="flex-center flex-wrap gap-8">
                    <span class="eyebrow" style="margin-bottom:0"><i data-lucide="scan-line"></i> Computer Vision AI</span>
                    <span class="text-xs text-muted">Gemini Flash Vision</span>
                </div>
                <h1>Input Bahan Otomatis Lewat Foto</h1>
                <p class="sub mb-0">Unggah foto isi kulkas atau meja dapur Anda. AI mengenali bahan makanan, kategori gizi, dan masa simpannya secara instan.</p>
            </div>

            <a href="{{ route('inventory.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i> Kembali ke Inventaris
            </a>
        </div>
    </div>

    {{-- Ruang kerja: panel unggah di kiri, hasil deteksi di kanan --}}
    <div class="grid grid-side">

        {{-- Panel kontrol --}}
        <div class="card">
            <div class="card-head">
                <h3><i data-lucide="upload-cloud" style="width:15px;height:15px;vertical-align:-2px"></i> Unggah atau Pilih Foto Kulkas</h3>
            </div>
            <div class="card-body">
                <div class="stack-16">
                    <label for="camera_input" class="dropzone" style="display:block">
                        <input type="file" id="camera_input" accept="image/*" @change="onFileChange($event)" class="hide">

                        <div x-show="!previewUrl">
                            <i data-lucide="camera"></i>
                            <div class="strong text-sm" style="color:var(--ink)">Klik untuk Ambil Foto / Unggah File</div>
                            <div class="text-xs">Mendukung file JPG, PNG, WEBP (Maksimal 10 MB)</div>
                        </div>

                        <div x-show="previewUrl">
                            <div class="preview-frame">
                                <img :src="previewUrl" alt="Preview Foto Kulkas">
                            </div>
                            <div class="text-xs mt-8">Ganti Foto</div>
                        </div>
                    </label>

                    <div>
                        <div class="eyebrow">Atau Pilih Sampel Demo Sidang:</div>
                        <div class="grid grid-3 gap-8">
                            <button type="button" @click="selectSample('fridge_1', 'https://images.unsplash.com/photo-1584269600464-37b1b58a9fe7?auto=format&fit=crop&w=800&q=80')"
                                class="card" :class="selectedSample === 'fridge_1' ? 'is-dashed' : ''"
                                style="padding:8px;text-align:left;cursor:pointer">
                                <div class="preview-frame mb-8">
                                    <img src="https://images.unsplash.com/photo-1584269600464-37b1b58a9fe7?auto=format&fit=crop&w=200&q=80" alt="Kulkas Sayur">
                                </div>
                                <span class="text-xs truncate">Kulkas Sayur</span>
                            </button>

                            <button type="button" @click="selectSample('fridge_2', 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=800&q=80')"
                                class="card" :class="selectedSample === 'fridge_2' ? 'is-dashed' : ''"
                                style="padding:8px;text-align:left;cursor:pointer">
                                <div class="preview-frame mb-8">
                                    <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=200&q=80" alt="Pasar Segar">
                                </div>
                                <span class="text-xs truncate">Pasar Segar</span>
                            </button>

                            <button type="button" @click="selectSample('fridge_3', 'https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?auto=format&fit=crop&w=800&q=80')"
                                class="card" :class="selectedSample === 'fridge_3' ? 'is-dashed' : ''"
                                style="padding:8px;text-align:left;cursor:pointer">
                                <div class="preview-frame mb-8">
                                    <img src="https://images.unsplash.com/photo-1571171637578-41bc2dd41cd2?auto=format&fit=crop&w=200&q=80" alt="Bumbu Dapur">
                                </div>
                                <span class="text-xs truncate">Bumbu Dapur</span>
                            </button>
                        </div>
                    </div>

                    <button type="button" @click="analyzePhoto()" :disabled="loading" class="btn btn-primary btn-lg btn-block">
                        <template x-if="!loading">
                            <span class="flex-center gap-8">
                                <i data-lucide="sparkles"></i>
                                <span>Pindai Bahan dengan Gemini Vision AI</span>
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex-center gap-8">
                                <span class="spinner"></span>
                                <span>Gemini Vision Sedang Menganalisis Objek...</span>
                            </span>
                        </template>
                    </button>

                    {{-- Catatan penyimpanan awan --}}
                    <div class="alert alert-info">
                        <i data-lucide="cloud"></i>
                        <div class="body">
                            <strong>Penyimpanan Foto:</strong>
                            Supabase Cloud Storage Bucket
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel hasil --}}
        <div>
            {{-- Belum ada analisis --}}
            <div x-show="!loading && detectedItems.length === 0" class="card">
                <div class="empty">
                    <i data-lucide="scan-line"></i>
                    <h3>Hasil Deteksi Objek Akan Muncul Di Sini</h3>
                    <p>Setelah Anda mengunggah atau memilih sampel foto kulkas lalu menekan tombol pemindaian, model Computer Vision Gemini akan menampilkan daftar bahan makanan beserta perkiraan takaran dan masa simpannya.</p>
                </div>
            </div>

            {{-- Sedang memindai --}}
            <div x-show="loading" class="card">
                <div class="card-body">
                    <div class="stack-16 text-center">
                        <div class="flex-center" style="justify-content:center">
                            <i data-lucide="brain-circuit" style="width:28px;height:28px;color:var(--brand)"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">Gemini Vision AI Sedang Memindai Gambar</h3>
                            <div class="text-sm text-muted mt-8">Mengklasifikasikan bahan pangan, memperkirakan takaran, dan menghitung estimasi masa simpan...</div>
                        </div>
                        <div class="stack-8" style="max-width:420px;margin:0 auto">
                            <div class="skeleton" style="height:12px"></div>
                            <div class="skeleton" style="height:12px"></div>
                            <div class="skeleton" style="height:12px;max-width:70%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hasil deteksi --}}
            <div x-show="!loading && detectedItems.length > 0" class="card">
                <div class="card-head" style="align-items:flex-start">
                    <div>
                        <div class="flex-center gap-8">
                            <span class="dot dot-ok"></span>
                            <h3 class="mb-0">Hasil Deteksi Computer Vision</h3>
                        </div>
                        <div class="text-sm text-muted mt-8" x-text="summary"></div>
                    </div>
                    <div class="flex-center flex-wrap gap-8">
                        <span class="chip mono" x-text="'Latensi: ' + latency + ' ms'"></span>
                        <span class="chip chip-brand" x-text="detectedItems.length + ' Bahan Dikenali'"></span>
                    </div>
                </div>

                <form action="{{ route('vision.save-batch') }}" method="POST">
                    @csrf

                    <div class="card-body" style="max-height:460px;overflow-y:auto">
                        <div class="stack-12">
                            <template x-for="(item, index) in detectedItems" :key="index">
                                <div class="card">
                                    <div class="card-body tight">
                                        <div class="flex-between mb-12" style="align-items:flex-start">
                                            <div class="flex-center gap-12">
                                                <span class="chip chip-navy" x-text="index + 1"></span>
                                                <div>
                                                    <input type="text" :name="'items[' + index + '][item_name]'" x-model="item.item_name" required
                                                        class="input" style="max-width:240px">
                                                    <div class="flex-center flex-wrap gap-8 mt-8">
                                                        <span class="chip chip-info" x-text="item.category"></span>
                                                        <input type="hidden" :name="'items[' + index + '][category]'" :value="item.category">
                                                        <span class="text-xs text-muted" x-text="'Lokasi: ' + (item.storage_location || 'Kulkas')"></span>
                                                        <input type="hidden" :name="'items[' + index + '][storage_location]'" :value="item.storage_location || 'Kulkas (Chiller)'">
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Keyakinan deteksi --}}
                                            <div class="text-right" style="min-width:140px">
                                                <span class="chip chip-ok mono" x-text="Math.round((item.confidence_score || 0.95) * 100) + '% Akurat'"></span>
                                                <div class="conf-bar mt-8">
                                                    <span :style="'width: ' + Math.round((item.confidence_score || 0.95) * 100) + '%'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Takaran, satuan & masa simpan --}}
                                        <div class="grid grid-3 gap-12">
                                            <div>
                                                <label class="label">Jumlah</label>
                                                <input type="number" step="0.5" :name="'items[' + index + '][estimated_quantity]'" x-model="item.estimated_quantity" required
                                                    class="input mono">
                                            </div>
                                            <div>
                                                <label class="label">Satuan</label>
                                                <input type="text" :name="'items[' + index + '][unit]'" x-model="item.unit" required class="input">
                                            </div>
                                            <div>
                                                <label class="label">Masa Simpan (Hari)</label>
                                                <input type="number" :name="'items[' + index + '][typical_shelf_life_days]'" x-model="item.typical_shelf_life_days" required
                                                    class="input mono">
                                            </div>
                                        </div>

                                        <input type="hidden" :name="'items[' + index + '][freshness_notes]'" :value="item.freshness_notes || 'Terdeteksi otomatis via Vision AI'">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="card-foot">
                        <div class="flex-between">
                            <button type="button" @click="detectedItems = []" class="btn btn-ghost btn-sm">Bersihkan Hasil</button>
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="check-check"></i>
                                <span>Simpan Semua Bahan ke Inventaris Dapur</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
