@extends('layouts.app')

@section('title', 'Inventaris Dapur Cerdas')

@section('content')
<div class="stack-20" x-data="{
    addModal: false,
    editModal: false,
    selectedMaster: '',
    editItem: { id: null, item_name: '', category: 'Sayuran', quantity: 1, unit: 'gram', storage_location: 'Kulkas (Chiller)', purchase_date: '', expiration_date: '', notes: '' },
    openEdit(item) {
        this.editItem = { ...item };
        this.editModal = true;
    },
    onMasterSelect(e) {
        const option = e.target.selectedOptions[0];
        if (option && option.dataset.name) {
            document.getElementById('input_item_name').value = option.dataset.name;
            document.getElementById('input_category').value = option.dataset.category;
            document.getElementById('input_unit').value = option.dataset.unit;

            // Hitung otomatis perkiraan tanggal expired dari shelf life
            const days = parseInt(option.dataset.shelfdays) || 7;
            const now = new Date();
            now.setDate(now.getDate() + days);
            document.getElementById('input_expiration_date').value = now.toISOString().split('T')[0];
        }
    }
}">

    <!-- Header halaman & tombol aksi -->
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-main">
                <div class="page-head-meta">
                    <span class="eyebrow"><i data-lucide="refrigerator"></i> Manajemen Stok Dapur</span>
                    <span class="chip chip-brand">Stok Kulkas &amp; Dapur Mahasiswa</span>
                    <span class="chip">Total: {{ $items->total() }} Bahan</span>
                </div>
                <h1>Manajemen Inventaris Dapur</h1>
                <p class="sub">Pantau masa simpan bahan makanan, kurangi limbah pangan, dan kelola stok dapur secara real-time.</p>
            </div>

            <div class="page-head-actions">
                <button type="button" @click="addModal = true" class="btn btn-primary">
                    <i data-lucide="plus-circle"></i> Tambah Bahan Manual
                </button>
                <a href="{{ route('vision.index') }}" class="btn btn-secondary">
                    <i data-lucide="camera"></i> Scan Foto Kulkas
                </a>
                <form action="{{ route('inventory.sync-supabase') }}" method="POST" style="margin:0">
                    @csrf
                    <button type="submit" title="Sinkronisasi ke Supabase Cloud" class="icon-btn">
                        <i data-lucide="refresh-cw"></i>
                    </button>
                </form>
                <a href="{{ route('inventory.print') }}" target="_blank" title="Cetak Laporan Inventaris" class="icon-btn">
                    <i data-lucide="printer"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Ringkasan stok -->
    <div class="grid grid-4">
        <div class="stat accent-brand">
            <div class="label"><i data-lucide="refrigerator"></i> Total Bahan</div>
            <div class="value">{{ $stats['total_items'] }}<span class="unit">item</span></div>
            <div class="foot">{{ $stats['supabase_synced_count'] }} bahan ada di Supabase Cloud</div>
        </div>

        <div class="stat accent-ok">
            <div class="label"><i data-lucide="shield-check"></i> Kondisi Aman</div>
            <div class="value">{{ $stats['safe_count'] }}<span class="unit">item</span></div>
            <div class="foot">Sisa masa simpan lebih dari 3 hari</div>
        </div>

        <div class="stat accent-warn">
            <div class="label"><i data-lucide="alarm-clock"></i> Segera Dimasak</div>
            <div class="value">{{ $stats['expiring_count'] }}<span class="unit">item</span></div>
            <div class="foot">&le; 3 hari lagi, utamakan diolah</div>
        </div>

        <div class="stat accent-info">
            <div class="label"><i data-lucide="calendar-x-2"></i> Kadaluwarsa</div>
            <div class="value">{{ $stats['expired_count'] }}<span class="unit">item</span></div>
            <div class="foot">Sudah lewat tanggal batas</div>
        </div>
    </div>

    <div class="grid grid-side">
        <!-- Panel filter -->
        <aside class="card filter-box">
            <form action="{{ route('inventory.index') }}" method="GET">
                <div class="filter-group">
                    <h4>Cari Bahan</h4>
                    <div class="search">
                        <i data-lucide="search"></i>
                        <input type="text" name="q" value="{{ request('q') }}"
                            placeholder="Cari nama bahan makanan (misal: Bawang, Telur, Kangkung)...">
                    </div>
                </div>

                <div class="filter-group">
                    <h4>Status Simpan</h4>
                    <select name="status" onchange="this.form.submit()" class="select">
                        <option value="" {{ !request('status') ? 'selected' : '' }}>Semua Status Simpan</option>
                        <option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>Segera Dimasak (&le; 3 hari)</option>
                        <option value="safe" {{ request('status') === 'safe' ? 'selected' : '' }}>Kondisi Aman</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Kadaluwarsa</option>
                    </select>
                </div>

                <div class="filter-group">
                    <h4>Lokasi Simpan</h4>
                    <select name="location" onchange="this.form.submit()" class="select">
                        <option value="all" {{ request('location') === 'all' || !request('location') ? 'selected' : '' }}>Semua Lokasi Simpan</option>
                        <option value="Kulkas (Chiller)" {{ request('location') === 'Kulkas (Chiller)' ? 'selected' : '' }}>Kulkas (Chiller)</option>
                        <option value="Freezer" {{ request('location') === 'Freezer' ? 'selected' : '' }}>Freezer Beku</option>
                        <option value="Lemari Kering (Pantry)" {{ request('location') === 'Lemari Kering (Pantry)' ? 'selected' : '' }}>Lemari Kering / Pantry</option>
                    </select>
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn btn-secondary btn-sm btn-block">Terapkan</button>
                    @if(request()->anyFilled(['q', 'status', 'location', 'category']))
                        <div class="text-center mt-8">
                            <a href="{{ route('inventory.index') }}" class="text-xs">Reset</a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="filter-group">
                <h4>Kategori:</h4>
                @php
                    $categories = ['all' => 'Semua', 'Bumbu Dapur' => 'Bumbu Dapur', 'Protein Hewani' => 'Protein Hewani', 'Protein Nabati' => 'Protein Nabati', 'Sayuran' => 'Sayuran Segar', 'Karbohidrat' => 'Karbohidrat', 'Saus & Minyak' => 'Saus & Minyak'];
                    $currentCat = request('category', 'all');
                @endphp
                <div class="flex flex-wrap gap-4">
                    @foreach($categories as $catKey => $catLabel)
                        <a href="{{ route('inventory.index', array_merge(request()->query(), ['category' => $catKey])) }}"
                           class="chip {{ $currentCat === $catKey ? 'chip-brand' : '' }}">
                            {{ $catLabel }}
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>

        <!-- Tabel bahan -->
        @php $maxQty = max(1, (float) $items->max('quantity')); @endphp
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Daftar Bahan Makanan</h2>
                    <span class="hint">Urut dari batas kedaluwarsa terdekat</span>
                </div>
                <span class="hint">{{ $items->count() }} bahan ditampilkan</span>
            </div>

            <div class="card-body flush">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Bahan</th>
                                <th>Kategori</th>
                                <th class="num">Jumlah Stok</th>
                                <th>Lokasi Simpan</th>
                                <th>Beli</th>
                                <th>Batas Expired</th>
                                <th>Status</th>
                                <th>Cloud</th>
                                <th class="actions">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                @php
                                    $badge = $item->status_badge;
                                    $chipClass = ['danger' => 'chip-danger', 'warning' => 'chip-warn', 'success' => 'chip-ok'][$badge['severity']] ?? 'chip';
                                    $dotClass = ['danger' => 'dot-danger', 'warning' => 'dot-warn', 'success' => 'dot-ok'][$badge['severity']] ?? 'dot';
                                    $isiBarang = max(6, round(($item->quantity / $maxQty) * 100));
                                @endphp
                                <tr>
                                    <td>
                                        <div class="flex-center gap-8">
                                            <span class="text-muted">{{ $item->ingredient?->icon ?? '🥗' }}</span>
                                            <div class="flex-1" style="max-width:230px">
                                                <div class="strong truncate" title="{{ $item->item_name }}">{{ $item->item_name }}</div>
                                                @if($item->notes)
                                                    <div class="text-xs text-muted truncate" title="{{ $item->notes }}">{{ $item->notes }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-sm text-muted">{{ $item->category }}</td>
                                    <td class="num">
                                        <div class="mono strong">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}<span class="text-xs text-muted"> {{ $item->unit }}</span></div>
                                        <div class="bar mt-8"><span style="width:{{ $isiBarang }}%"></span></div>
                                    </td>
                                    <td class="text-sm text-muted">{{ $item->storage_location }}</td>
                                    <td class="text-xs mono">{{ $item->purchase_date?->format('d M Y') }}</td>
                                    <td class="text-xs">
                                        <span class="mono" style="{{ $item->days_until_expiration <= 3 ? 'color:var(--warn);font-weight:650' : '' }}">
                                            {{ $item->expiration_date?->format('d M Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="chip {{ $chipClass }}" style="max-width:190px;white-space:normal">
                                            <span class="dot {{ $dotClass }}"></span>{{ $badge['label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-xs mono" title="{{ $item->supabase_id ? 'Tersinkronisasi ke Supabase Cloud' : 'Tersimpan di MySQL Lokal' }}">
                                            <span class="dot {{ $item->supabase_id ? 'dot-ok' : 'dot' }}" style="margin-right:6px"></span>
                                            {{ $item->supabase_id ? 'Supabase Cloud' : 'MySQL Lokal' }}
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="{{ route('recipes.generator', ['items' => $item->item_name]) }}" title="Ciptakan Resep dari Bahan Ini" class="btn btn-ghost btn-sm">
                                            <i data-lucide="sparkles"></i>
                                        </a>
                                        <button type="button" title="Ubah Bahan" @click="openEdit({{ json_encode($item) }})" class="btn btn-secondary btn-sm">
                                            <i data-lucide="edit-3"></i>
                                        </button>
                                        <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Hapus {{ $item->item_name }} dari inventaris dapur?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Bahan" class="btn btn-danger btn-sm">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="empty">
                                            <i data-lucide="inbox"></i>
                                            <h3>Tidak Ada Bahan Makanan Ditemukan</h3>
                                            <p>Belum ada bahan masakan yang sesuai dengan filter pencarian ini. Tambahkan bahan baru atau scan foto kulkas Anda.</p>
                                            <button type="button" @click="addModal = true" class="btn btn-primary">
                                                <i data-lucide="plus"></i> Tambah Bahan Pertama
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($items->hasPages())
                @php
                    $dari = max(1, $items->currentPage() - 2);
                    $sampai = min($items->lastPage(), $items->currentPage() + 2);
                @endphp
                <div class="pagination">
                    <span>Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }} &middot; total {{ $items->total() }} bahan</span>
                    <div class="links">
                        @if($items->onFirstPage())
                            <span class="is-disabled" aria-hidden="true">&lsaquo;</span>
                        @else
                            <a href="{{ $items->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a>
                        @endif

                        @for($p = $dari; $p <= $sampai; $p++)
                            @if($p === $items->currentPage())
                                <span class="is-active" aria-current="page">{{ $p }}</span>
                            @else
                                <a href="{{ $items->url($p) }}">{{ $p }}</a>
                            @endif
                        @endfor

                        @if($items->hasMorePages())
                            <a href="{{ $items->nextPageUrl() }}" rel="next" aria-label="Berikutnya">&rsaquo;</a>
                        @else
                            <span class="is-disabled" aria-hidden="true">&rsaquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL 1: Tambah Bahan Manual -->
    <div class="modal-mask" x-show="addModal" x-cloak>
        <div class="modal" role="dialog" aria-modal="true" aria-label="Tambah bahan makanan baru" @click.outside="addModal = false">
            <div class="modal-head">
                <h3>Tambah Bahan Makanan Baru</h3>
                <button type="button" class="icon-btn" @click="addModal = false" aria-label="Tutup">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form action="{{ route('inventory.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- Pilih dari Master Ingredient Populer -->
                    <div class="field">
                        <label class="label">Pilih dari Master Bahan Populer (Otomatis Isi)</label>
                        <select @change="onMasterSelect($event)" class="select">
                            <option value="">-- Pilih Bahan Nusantara (Opsional) --</option>
                            @foreach($masterIngredients as $m)
                                <option value="{{ $m->id }}"
                                        data-name="{{ $m->name }}"
                                        data-category="{{ $m->category }}"
                                        data-unit="{{ $m->default_unit }}"
                                        data-shelfdays="{{ $m->typical_shelf_life_days }}">
                                    {{ $m->icon }} {{ $m->name }} ({{ $m->category }})
                                </option>
                            @endforeach
                        </select>
                        <div class="hint">Nama, kategori, satuan, dan perkiraan tanggal kedaluwarsa ikut terisi.</div>
                    </div>

                    <div class="field">
                        <label class="label" for="input_item_name">Nama Bahan Masakan <span class="req">*</span></label>
                        <input type="text" id="input_item_name" name="item_name" required class="input"
                            placeholder="Contoh: Daging Ayam Filet, Wortel, Tempe">
                    </div>

                    <div class="grid grid-2">
                        <div class="field">
                            <label class="label" for="input_category">Kategori Gizi <span class="req">*</span></label>
                            <select id="input_category" name="category" required class="select">
                                <option value="Sayuran">Sayuran Segar</option>
                                <option value="Protein Hewani">Protein Hewani</option>
                                <option value="Protein Nabati">Protein Nabati</option>
                                <option value="Bumbu Dapur">Bumbu Dapur &amp; Rempah</option>
                                <option value="Karbohidrat">Karbohidrat / Pokok</option>
                                <option value="Saus & Minyak">Saus &amp; Minyak</option>
                                <option value="Buah">Buah-buahan</option>
                                <option value="Susu & Olahan">Susu &amp; Olahan</option>
                            </select>
                        </div>

                        <div class="field">
                            <label class="label">Lokasi Penyimpanan <span class="req">*</span></label>
                            <select name="storage_location" required class="select">
                                <option value="Kulkas (Chiller)">Kulkas (Chiller)</option>
                                <option value="Freezer">Freezer Beku</option>
                                <option value="Lemari Kering (Pantry)">Lemari Kering / Pantry</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="field">
                            <label class="label">Jumlah Stok <span class="req">*</span></label>
                            <input type="number" step="0.1" name="quantity" value="1" required class="input mono">
                        </div>
                        <div class="field">
                            <label class="label" for="input_unit">Satuan <span class="req">*</span></label>
                            <input type="text" id="input_unit" name="unit" value="buah" required class="input"
                                placeholder="gram, ikat, butir, buah, sdm">
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="field">
                            <label class="label">Tanggal Beli <span class="req">*</span></label>
                            <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required class="input">
                        </div>
                        <div class="field">
                            <label class="label" for="input_expiration_date">Tanggal Kedaluwarsa <span class="req">*</span></label>
                            <input type="date" id="input_expiration_date" name="expiration_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required class="input">
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="notes" class="input"
                            placeholder="Contoh: Beli di pasar tradisional, kemasan sudah dibuka">
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" @click="addModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan ke Inventaris</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Bahan -->
    <div class="modal-mask" x-show="editModal" x-cloak>
        <div class="modal" role="dialog" aria-modal="true" aria-label="Edit bahan makanan" @click.outside="editModal = false">
            <div class="modal-head">
                <h3>Edit Bahan Makanan</h3>
                <button type="button" class="icon-btn" @click="editModal = false" aria-label="Tutup">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <form :action="'{{ url('inventory') }}/' + editItem.id" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="field">
                        <label class="label">Nama Bahan</label>
                        <input type="text" name="item_name" x-model="editItem.item_name" required class="input">
                    </div>

                    <div class="grid grid-2">
                        <div class="field">
                            <label class="label">Kategori Gizi</label>
                            <select name="category" x-model="editItem.category" required class="select">
                                <option value="Sayuran">Sayuran Segar</option>
                                <option value="Protein Hewani">Protein Hewani</option>
                                <option value="Protein Nabati">Protein Nabati</option>
                                <option value="Bumbu Dapur">Bumbu Dapur</option>
                                <option value="Karbohidrat">Karbohidrat</option>
                                <option value="Saus & Minyak">Saus &amp; Minyak</option>
                                <option value="Buah">Buah</option>
                                <option value="Susu & Olahan">Susu &amp; Olahan</option>
                            </select>
                        </div>

                        <div class="field">
                            <label class="label">Lokasi Simpan</label>
                            <select name="storage_location" x-model="editItem.storage_location" required class="select">
                                <option value="Kulkas (Chiller)">Kulkas (Chiller)</option>
                                <option value="Freezer">Freezer</option>
                                <option value="Lemari Kering (Pantry)">Lemari Kering (Pantry)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="field">
                            <label class="label">Jumlah</label>
                            <input type="number" step="0.1" name="quantity" x-model="editItem.quantity" required class="input mono">
                        </div>
                        <div class="field">
                            <label class="label">Satuan</label>
                            <input type="text" name="unit" x-model="editItem.unit" required class="input">
                        </div>
                    </div>

                    <div class="grid grid-2">
                        <div class="field">
                            <label class="label">Tanggal Beli</label>
                            <input type="date" name="purchase_date" x-model="editItem.purchase_date" required class="input">
                        </div>
                        <div class="field">
                            <label class="label">Tanggal Kedaluwarsa</label>
                            <input type="date" name="expiration_date" x-model="editItem.expiration_date" required class="input">
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Catatan</label>
                        <input type="text" name="notes" x-model="editItem.notes" class="input">
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" @click="editModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Perbarui Bahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
