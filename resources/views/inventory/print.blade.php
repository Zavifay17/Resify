<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Inventaris Dapur - Tugas Akhir RPL BSI</title>
    <style>
        /* Dokumen cetak laporan inventaris - ditulis manual, tanpa framework. */
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px 16px 48px;
            background: #fff;
            color: #1f2a37;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12.5px;
            line-height: 1.5;
        }

        h1, h2, h3, p { margin: 0; }
        a { color: #143050; }

        .lembar { max-width: 900px; margin: 0 auto; }

        /* Bilah aksi, tidak ikut tercetak */
        .bilah-aksi {
            max-width: 900px;
            margin: 0 auto 18px;
            padding: 11px 14px;
            border: 1px solid #cfd6dd;
            border-radius: 6px;
            background: #f6f7f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            font-size: 12px;
        }
        .bilah-aksi a { font-weight: 700; text-decoration: none; }
        .bilah-aksi a:hover { text-decoration: underline; }
        .bilah-aksi .petunjuk { color: #6b7a89; margin-right: 10px; }
        .btn-cetak {
            font: inherit;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            background: #143050;
            border: 1px solid #143050;
            border-radius: 6px;
            padding: 7px 13px;
            cursor: pointer;
        }
        .btn-cetak:hover { background: #0e2540; }

        /* Kop surat */
        .kop {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #1f2a37;
        }
        .kop-kiri { display: flex; align-items: center; gap: 14px; }
        .lencana {
            width: 58px;
            height: 58px;
            flex: none;
            border: 2px solid #1f2a37;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .04em;
        }
        .kop h2 { font-size: 15px; text-transform: uppercase; letter-spacing: .02em; }
        .kop .prodi { font-size: 12px; font-weight: 700; margin-top: 2px; }
        .kop .sistem { font-size: 11px; color: #4a5866; margin-top: 3px; }
        .kop-kanan { text-align: right; font-size: 11px; color: #3c4a58; white-space: nowrap; }
        .kop-kanan .nomor { font-family: "Courier New", monospace; }

        .judul-laporan {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-top: 22px;
            text-decoration: underline;
        }
        .sub-judul { text-align: center; font-size: 11px; color: #4a5866; margin-top: 5px; }

        /* Kotak identitas & ringkasan */
        .kotak-data {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin: 20px 0;
            padding: 12px 14px;
            border: 1px solid #cfd6dd;
            border-radius: 6px;
            font-size: 11.5px;
        }
        .kotak-data p { margin-bottom: 3px; }
        .kotak-data strong { color: #1f2a37; }

        /* Tabel bahan */
        .tabel-luar { overflow-x: auto; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        caption {
            text-align: left;
            font-size: 10.5px;
            color: #6b7a89;
            padding-bottom: 6px;
        }
        th {
            background: #f3f5f7;
            border-top: 1px solid #8fa0af;
            border-bottom: 1px solid #8fa0af;
            text-align: left;
            padding: 7px 9px;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .03em;
            white-space: nowrap;
        }
        td { padding: 6px 9px; border-bottom: 1px solid #d7dde3; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .tengah { text-align: center; }
        .mono { font-family: "Courier New", monospace; }
        .nama { font-weight: 700; }
        .st-habis { color: #b91c1c; font-weight: 700; }
        .st-segera { color: #b45309; font-weight: 700; }
        .st-aman { color: #15803d; font-weight: 700; }

        /* Tanda tangan */
        .ttd {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #cfd6dd;
            text-align: center;
            font-size: 11px;
        }
        .ttd .jabatan { font-weight: 700; margin-top: 2px; }
        .ttd .ruang { height: 72px; }
        .ttd .nama-ttd { font-weight: 700; text-decoration: underline; }
        .ttd .ket { font-size: 10px; color: #4a5866; margin-top: 2px; font-family: "Courier New", monospace; }

        .catatan {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px dashed #cfd6dd;
            font-size: 10px;
            color: #6b7a89;
        }
        .catatan ol { margin: 4px 0 0; padding-left: 18px; }

        .page-break { page-break-after: always; }

        @media print {
            body { padding: 0; font-size: 11px; }
            .no-print { display: none !important; }
            .lembar { max-width: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
            .kotak-data, .tabel-luar, .ttd { break-inside: avoid; }
            @page { margin: 15mm 13mm; size: A4 portrait; }
        }

        @media screen and (max-width: 700px) {
            .kop { flex-wrap: wrap; gap: 8px; }
            .kop-kanan { white-space: normal; text-align: left; }
            .ttd { grid-template-columns: minmax(0, 1fr); gap: 26px; }
        }
    </style>
</head>
<body>

    <!-- Bilah aksi (tidak tercetak) -->
    <div class="bilah-aksi no-print">
        <a href="{{ route('inventory.index') }}">&larr; Kembali ke Inventaris</a>
        <div>
            <span class="petunjuk">Gunakan Ctrl + P atau tombol berikut:</span>
            <button type="button" class="btn-cetak" onclick="window.print()">Cetak Dokumen Laporan</button>
        </div>
    </div>

    <main class="lembar">

        <!-- Kop surat akademik -->
        <header class="kop">
            <div class="kop-kiri">
                <span class="lencana">BSI</span>
                <div>
                    <h2>Universitas Bina Sarana Informatika</h2>
                    <p class="prodi">FAKULTAS TEKNIK &amp; INFORMATIKA &bull; PROGRAM STUDI REKAYASA PERANGKAT LUNAK</p>
                    <p class="sistem">Sistem Resepify: Manajemen Inventaris Dapur Cerdas Berbasis Kecerdasan Buatan</p>
                </div>
            </div>
            <div class="kop-kanan">
                <p class="nomor">Form: TA-RPL-AI-01</p>
                <p>Tgl Cetak: {{ date('d F Y') }}</p>
            </div>
        </header>

        <h1 class="judul-laporan">Laporan Inventarisasi Bahan Makanan Dapur</h1>
        <p class="sub-judul">Dokumen Bukti Pengujian Sistem Smart Expiration &amp; Hybrid Database</p>

        <!-- Data mahasiswa & ringkasan stok -->
        <section class="kotak-data">
            <div>
                <p><strong>Nama Mahasiswa:</strong> {{ $user->name }}</p>
                <p><strong>NIM:</strong> {{ $user->nim ?? '12210889' }}</p>
                <p><strong>Program Studi:</strong> Rekayasa Perangkat Lunak (RPL)</p>
            </div>
            <div>
                <p><strong>Total Stok Terdaftar:</strong> {{ $stats['total_items'] }} Komoditas</p>
                <p><strong>Bahan Segera Dimasak (&le; 3 hari):</strong> {{ $stats['expiring_count'] }} Item</p>
                <p><strong>Sinkronisasi Cloud Supabase:</strong> {{ $stats['supabase_synced_count'] }} Item</p>
            </div>
        </section>

        <!-- Tabel bahan -->
        <div class="tabel-luar">
            <table>
                <caption>Daftar bahan per {{ date('d/m/Y') }}, diurutkan dari batas kedaluwarsa terdekat ({{ $items->count() }} baris).</caption>
                <thead>
                    <tr>
                        <th style="width:32px">No</th>
                        <th>Nama Bahan Pangan</th>
                        <th style="width:110px">Kategori</th>
                        <th class="num" style="width:90px">Jumlah Stok</th>
                        <th style="width:115px">Lokasi Simpan</th>
                        <th style="width:76px">Tgl Beli</th>
                        <th style="width:92px">Batas Kedaluwarsa</th>
                        <th class="tengah" style="width:150px">Status Masa Simpan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr>
                            <td class="mono">{{ $index + 1 }}</td>
                            <td class="nama">{{ $item->item_name }}</td>
                            <td>{{ $item->category }}</td>
                            <td class="num">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} {{ $item->unit }}</td>
                            <td>{{ $item->storage_location }}</td>
                            <td class="mono">{{ $item->purchase_date?->format('d/m/Y') }}</td>
                            <td class="mono">{{ $item->expiration_date?->format('d/m/Y') }}</td>
                            <td class="tengah">
                                @if($item->computed_status === 'expired')
                                    <span class="st-habis">Kadaluwarsa</span>
                                @elseif($item->computed_status === 'expiring_soon')
                                    <span class="st-segera">Segera Dimasak ({{ $item->days_until_expiration }} hari)</span>
                                @else
                                    <span class="st-aman">Aman ({{ $item->days_until_expiration }} hari)</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Tanda tangan -->
        <section class="ttd">
            <div>
                <p>Mengetahui,</p>
                <p class="jabatan">Dosen Pembimbing Tugas Akhir</p>
                <div class="ruang"></div>
                <p class="nama-ttd">(........................................................)</p>
                <p class="ket">NIDN. ........................................</p>
            </div>
            <div>
                <p>Jakarta, {{ date('d F Y') }}</p>
                <p class="jabatan">Mahasiswa Pengembang</p>
                <div class="ruang"></div>
                <p class="nama-ttd">({{ $user->name }})</p>
                <p class="ket">NIM. {{ $user->nim ?? '12210889' }}</p>
            </div>
        </section>

        <footer class="catatan">
            <p>Dokumen ini dibuat langsung dari halaman Inventaris Dapur aplikasi Resepify.</p>
            <ol>
                <li>Status dihitung dari selisih tanggal kedaluwarsa terhadap tanggal cetak: aman (&gt; 3 hari), segera dimasak (&le; 3 hari), kadaluwarsa (tanggal sudah lewat).</li>
                <li>Kolom cloud hanya terisi bila bahan memiliki id di Supabase; selain itu data berada di MySQL lokal.</li>
                <li>Angka ringkasan mengikuti kondisi stok pada saat dokumen dicetak.</li>
            </ol>
        </footer>

    </main>

</body>
</html>
