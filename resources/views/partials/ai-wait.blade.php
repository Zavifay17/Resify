@php
    // $id      : nama unik state tunggu, dipakai pengatur progres di js/ai-wait.js
    // $judul   : teks utama yang sedang dikerjakan AI
    // $langkah : daftar kegiatan yang ditandai selesai sesuai progres nyata
    // $catatan : penjelasan kecil di bawah daftar
    // $ringkas : true untuk dipakai di dalam modal
    $id = $id ?? 'ai';
    $ringkas = $ringkas ?? false;
    $langkah = $langkah ?? [];
@endphp

<div class="ai-wait{{ $ringkas ? ' ringkas' : '' }}" data-ai-wait="{{ $id }}">
    <div class="ai-pot" aria-hidden="true">
        <span class="uap u1"></span>
        <span class="uap u2"></span>
        <span class="uap u3"></span>
        <span class="gagang kiri"></span>
        <span class="gagang kanan"></span>
        <div class="badan">
            <div class="isi"></div>
            <span class="buih b1"></span>
            <span class="buih b2"></span>
            <span class="buih b3"></span>
        </div>
        <div class="ai-orbit">
            <span class="o1"><i data-lucide="carrot"></i></span>
            <span class="o2"><i data-lucide="wheat"></i></span>
            <span class="o3"><i data-lucide="fish"></i></span>
            <span class="o4"><i data-lucide="leaf"></i></span>
        </div>
    </div>

    @if(!empty($judul))
        <h3>{{ $judul }}</h3>
    @endif

    <div class="ai-bar" data-ai-bar aria-hidden="true"><span></span></div>

    <p class="ai-fase">
        <span data-ai-fase>Menyiapkan permintaan ke Gemini...</span>
        <span class="ai-jam" data-ai-jam></span>
    </p>

    @if($langkah !== [])
        <ul class="ai-langkah">
            @foreach($langkah as $i => $satuLangkah)
                <li data-ai-langkah style="--d: {{ number_format(0.15 + $i * 1.5, 2, '.', '') }}s">{{ $satuLangkah }}</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($catatan))
        <p class="catatan">{{ $catatan }}</p>
    @endif
</div>
