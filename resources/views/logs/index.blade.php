@extends('layouts.app')

@section('title', 'AI Logs & Monitoring')

@section('content')
<div x-data="{
    rawModal: false,
    selectedLog: null,
    viewRaw(log) {
        this.selectedLog = log;
        this.rawModal = true;
    }
}">

    {{-- Header halaman --}}
    <div class="page-head">
        <div class="flex-between" style="align-items:flex-start">
            <div>
                <div class="flex-center flex-wrap gap-8">
                    <span class="eyebrow" style="margin-bottom:0"><i data-lucide="shield-check"></i> Standar Penilaian RPL</span>
                    <span class="text-xs text-muted">Monitoring Biaya, Token & Latensi API AI</span>
                </div>
                <h1>Log & Performa Kecerdasan Buatan</h1>
                <p class="sub mb-0">Transparansi pemanggilan Gemini API, penghitungan token input/output, dan efisiensi sistem caching.</p>
            </div>

            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                <i data-lucide="sliders"></i> Konfigurasi Model Gemini
            </a>
        </div>
    </div>

    {{-- Ringkasan pemakaian AI --}}
    <div class="grid grid-4">
        <div class="stat accent-info">
            <div class="label"><i data-lucide="hash"></i> Total Token Terpakai</div>
            <div class="value">{{ number_format($totalTokens) }}<span class="unit">tokens</span></div>
            <div class="foot mono">Prompt: {{ number_format($promptTokens) }} | Respon: {{ number_format($responseTokens) }}</div>
        </div>

        <div class="stat accent-warn">
            <div class="label"><i data-lucide="timer"></i> Rata-Rata Latensi API</div>
            <div class="value">{{ $avgLatency }}<span class="unit">ms</span></div>
            <div class="foot">Kecepatan inferensi Gemini Flash</div>
        </div>

        <div class="stat accent-ok">
            <div class="label"><i data-lucide="check-circle-2"></i> Tingkat Keberhasilan API</div>
            <div class="value">{{ $successRate }}%<span class="unit">uptime</span></div>
            <div class="foot">Total {{ $totalHits }} eksekusi tercatat</div>
        </div>

        <div class="stat accent-brand">
            <div class="label"><i data-lucide="database"></i> Hemat Kuota via Caching</div>
            <div class="value">~{{ number_format($savedTokens) }}<span class="unit">tokens</span></div>
            <div class="foot">Berkat MySQL & Laravel Cache</div>
        </div>
    </div>

    {{-- Filter pemanggilan --}}
    <div class="card mt-24">
        <div class="card-body tight">
            <form action="{{ route('logs.index') }}" method="GET" class="flex-center flex-wrap gap-12">
                <span class="label mb-0">Filter Fitur AI:</span>

                <select name="action" class="select" style="width:auto;min-width:200px" onchange="this.form.submit()">
                    <option value="all" {{ request('action') === 'all' || !request('action') ? 'selected' : '' }}>Semua Fitur</option>
                    <option value="recipe_generation" {{ request('action') === 'recipe_generation' ? 'selected' : '' }}>Generator Resep</option>
                    <option value="vision_detection" {{ request('action') === 'vision_detection' ? 'selected' : '' }}>Computer Vision</option>
                    <option value="substitute_suggestion" {{ request('action') === 'substitute_suggestion' ? 'selected' : '' }}>Bahan Pengganti</option>
                    <option value="expiration_rescue" {{ request('action') === 'expiration_rescue' ? 'selected' : '' }}>Resep Penyelamat (Zero Waste)</option>
                    <option value="test_connection" {{ request('action') === 'test_connection' ? 'selected' : '' }}>Uji Koneksi API</option>
                </select>

                <select name="status" class="select" style="width:auto;min-width:160px" onchange="this.form.submit()">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua Status</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Sukses</option>
                    <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Error</option>
                    <option value="fallback" {{ request('status') === 'fallback' ? 'selected' : '' }}>Engine Cerdas</option>
                </select>

                @if(request()->anyFilled(['action', 'status']))
                    <a href="{{ route('logs.index') }}" class="btn btn-ghost btn-sm">Reset Filter</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabel log pemanggilan --}}
    <div class="card mt-16">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Fitur AI</th>
                        <th>Model</th>
                        <th>Ringkasan Prompt</th>
                        <th class="num">Token</th>
                        <th class="num">Latensi</th>
                        <th>Status</th>
                        <th class="actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="mono text-xs text-muted" style="white-space:nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <span class="flex-center gap-8"><span class="dot dot-ok"></span><span class="strong">{{ $log->action_label }}</span></span>
                        </td>
                        <td class="mono text-xs">{{ $log->model_used }}</td>
                        <td class="truncate text-sm" style="max-width:320px" title="{{ $log->prompt_summary }}">{{ $log->prompt_summary }}</td>
                        <td class="num mono">{{ $log->total_tokens ?: '-' }}</td>
                        <td class="num mono">{{ $log->execution_time_ms }} ms</td>
                        <td>
                            <span class="chip {{ $log->status === 'success' ? 'chip-ok' : ($log->status === 'fallback' ? 'chip-warn' : 'chip-danger') }}">{{ strtoupper($log->status) }}</span>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn btn-secondary btn-sm" @click="viewRaw({{ json_encode($log) }})">Lihat JSON</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty"><p class="mb-0">Belum ada catatan log pemanggilan API AI.</p></div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            @php
                $dari = max(1, $logs->currentPage() - 2);
                $sampai = min($logs->lastPage(), $logs->currentPage() + 2);
            @endphp
            <div class="pagination">
                <span>Menampilkan {{ $logs->firstItem() }}&ndash;{{ $logs->lastItem() }} dari {{ $logs->total() }} catatan pemanggilan AI</span>
                <div class="links">
                    @if($logs->onFirstPage())
                        <span class="is-disabled" aria-hidden="true">&lsaquo;</span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a>
                    @endif

                    @for($p = $dari; $p <= $sampai; $p++)
                        @if($p === $logs->currentPage())
                            <span class="is-active" aria-current="page">{{ $p }}</span>
                        @else
                            <a href="{{ $logs->url($p) }}">{{ $p }}</a>
                        @endif
                    @endfor

                    @if($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" rel="next" aria-label="Berikutnya">&rsaquo;</a>
                    @else
                        <span class="is-disabled" aria-hidden="true">&rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Detail satu pemanggilan --}}
    <div class="modal-mask" x-show="rawModal" x-cloak>
        <div class="modal" @click.outside="rawModal = false" role="dialog" aria-modal="true" aria-label="Inspeksi data respon AI">
            <div class="modal-head">
                <div class="flex-center gap-12">
                    <i data-lucide="code" style="width:16px;height:16px;color:var(--muted)"></i>
                    <div>
                        <h3 class="mb-0">Inspeksi Data Respon JSON Strict AI</h3>
                        <div class="text-xs text-muted" x-text="selectedLog?.action_label"></div>
                    </div>
                </div>
                <button type="button" class="icon-btn" @click="rawModal = false" aria-label="Tutup">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="modal-body col gap-16">
                <dl class="kv">
                    <dt>Waktu</dt><dd class="mono text-xs" x-text="selectedLog?.created_at"></dd>
                    <dt>Model</dt><dd class="mono" x-text="selectedLog?.model_used"></dd>
                    <dt>Token terpakai</dt><dd x-text="selectedLog?.total_tokens"></dd>
                    <dt>Latensi</dt><dd x-text="selectedLog?.execution_time_ms + ' ms'"></dd>
                    <dt>Status</dt><dd x-text="selectedLog?.status"></dd>
                </dl>

                <div>
                    <div class="label">Prompt Singkat:</div>
                    <div class="code-block" x-text="selectedLog?.prompt_summary"></div>
                </div>

                <div>
                    <div class="label">Raw Output Response (Strict JSON):</div>
                    <pre class="code-block" x-text="selectedLog?.raw_response || 'Tidak ada data respon tersimpan.'"></pre>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-secondary" @click="rawModal = false">Tutup</button>
            </div>
        </div>
    </div>

</div>
@endsection
