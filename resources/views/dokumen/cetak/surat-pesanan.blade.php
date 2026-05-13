<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pesanan</title>

    <style>
        @page {
            margin-top: 24px;
            margin-right: 30px;
            margin-bottom: 24px;
            margin-left: 54px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.35;
        }

        .kop {
            text-align: center;
            margin-top: 0;
            margin-bottom: 18px;
        }

        .kop img {
            width: 100%;
            max-height: 95px;
        }

        .kop-fallback {
            text-align: center;
            font-size: 14px;
            margin-bottom: 18px;
        }

        .kop-line {
            border-top: 2px solid #111;
            margin-top: 10px;
            margin-bottom: 44px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            line-height: 1.25;
            margin-bottom: 0;
            text-transform: uppercase;
        }

        .subtitle {
            text-align: center;
            font-size: 11px;
            margin-bottom: 18px;
        }

        .paragraph {
            text-align: justify;
            margin-bottom: 10px;
        }

        .identity-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 14px;
        }

        .identity-table td {
            vertical-align: top;
            padding: 2px 4px;
        }

        .identity-label {
            width: 250px;
        }

        .identity-colon {
            width: 12px;
            text-align: center;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 10px;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #111;
            padding: 4px 5px;
            font-size: 9px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
        }

        /* Paksa lebar kolom untuk DomPDF */
        .items-table .cell-no {
            width: 6% !important;
            max-width: 6% !important;
            text-align: center;
        }

        .items-table .cell-uraian {
            width: 39% !important;
            max-width: 39% !important;
        }

        .items-table .cell-satuan {
            width: 10% !important;
            max-width: 10% !important;
            text-align: center;
        }

        .items-table .cell-kuantitas {
            width: 10% !important;
            max-width: 10% !important;
            text-align: center;
        }

        .items-table .cell-harga {
            width: 17% !important;
            max-width: 17% !important;
            text-align: right;
        }

        .items-table .cell-jumlah {
            width: 24% !important;
            max-width: 24% !important;
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .terms {
            margin: 14px 0 0 18px;
            padding-left: 12px;
        }

        .terms li {
            margin-bottom: 6px;
            text-align: justify;
        }

        .signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
            table-layout: fixed;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }

        .signature-space {
            height: 70px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .page-break {
            page-break-before: always;
        }

        /* ================= COVER HALAMAN 2 ================= */

        .cover-logo {
            text-align: left;
            margin-top: 8px;
            margin-bottom: 34px;
            font-size: 12px;
        }

        .cover-logo img {
            width: 70px;
            max-height: 70px;
        }

        .cover-header {
            text-align: center;
            font-weight: bold;
            line-height: 1.45;
            margin-bottom: 34px;
        }

        .cover-title {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            text-transform: uppercase;
            margin-bottom: 34px;
        }

        .cover-block {
            margin-bottom: 18px;
        }

        .cover-block-label {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .cover-block-value {
            font-weight: bold;
            margin-left: 112px;
        }

        .cover-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .cover-table td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 12px;
        }

        .cover-label {
            width: 180px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .cover-colon {
            width: 14px;
            text-align: center;
        }

        .cover-value {
            font-weight: bold;
        }

        .blank-space {
            display: inline-block;
            min-width: 170px;
            height: 12px;
        }
    </style>
</head>

<body>
@php
    $usulan = $pengadaan->usulan;
    $anggaran = $usulan?->anggaran;
    $subKegiatan = $anggaran?->subKegiatan;
    $unitKerja = $usulan?->pemohon?->unitKerja;
    $penyedia = $pengadaan->penyedia;
    $items = $usulan?->items ?? collect();

    $pejabat = $pengadaan->pejabatPenandatangan ?? $pengadaan->pejabat;

    $tahun = $anggaran?->tahun
        ?? ($pengadaan->tanggal_kontrak
            ? \Carbon\Carbon::parse($pengadaan->tanggal_kontrak)->format('Y')
            : now()->year);

    $namaUnitKerja = $unitKerja?->nama ?? 'NAMA UNIT KERJA';
    $alamatUnitKerja = $unitKerja?->alamat ?? '-';

    $namaKegiatan = $subKegiatan?->nama_kegiatan ?? 'NAMA KEGIATAN';
    $namaRekening = $anggaran?->nama_rekening ?? $usulan?->judul ?? '-';

    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->translatedFormat('d F Y');
    };

    $angkaTerbilang = function (int $angka) use (&$angkaTerbilang): string {
        $angka = abs($angka);

        $huruf = [
            '',
            'Satu',
            'Dua',
            'Tiga',
            'Empat',
            'Lima',
            'Enam',
            'Tujuh',
            'Delapan',
            'Sembilan',
            'Sepuluh',
            'Sebelas',
        ];

        if ($angka < 12) {
            return $huruf[$angka];
        }

        if ($angka < 20) {
            return $angkaTerbilang($angka - 10) . ' Belas';
        }

        if ($angka < 100) {
            return $angkaTerbilang(intdiv($angka, 10)) . ' Puluh'
                . ($angka % 10 ? ' ' . $angkaTerbilang($angka % 10) : '');
        }

        if ($angka < 200) {
            return 'Seratus'
                . ($angka - 100 ? ' ' . $angkaTerbilang($angka - 100) : '');
        }

        if ($angka < 1000) {
            return $angkaTerbilang(intdiv($angka, 100)) . ' Ratus'
                . ($angka % 100 ? ' ' . $angkaTerbilang($angka % 100) : '');
        }

        if ($angka < 2000) {
            return 'Seribu'
                . ($angka - 1000 ? ' ' . $angkaTerbilang($angka - 1000) : '');
        }

        if ($angka < 1000000) {
            return $angkaTerbilang(intdiv($angka, 1000)) . ' Ribu'
                . ($angka % 1000 ? ' ' . $angkaTerbilang($angka % 1000) : '');
        }

        if ($angka < 1000000000) {
            return $angkaTerbilang(intdiv($angka, 1000000)) . ' Juta'
                . ($angka % 1000000 ? ' ' . $angkaTerbilang($angka % 1000000) : '');
        }

        if ($angka < 1000000000000) {
            return $angkaTerbilang(intdiv($angka, 1000000000)) . ' Miliar'
                . ($angka % 1000000000 ? ' ' . $angkaTerbilang($angka % 1000000000) : '');
        }

        return (string) $angka;
    };

    $tanggalKontrak = $formatTanggal($pengadaan->tanggal_kontrak);
    $tanggalSelesai = $formatTanggal($pengadaan->tanggal_selesai);

    $jangkaWaktu = '-';

    if ($pengadaan->tanggal_kontrak && $pengadaan->tanggal_selesai) {
        $mulai = \Carbon\Carbon::parse($pengadaan->tanggal_kontrak);
        $selesai = \Carbon\Carbon::parse($pengadaan->tanggal_selesai);
        $jangkaWaktu = ($mulai->diffInDays($selesai) + 1) . ' hari kalender';
    }

    $nilaiKontrak = (float) ($pengadaan->nilai_kontrak ?? 0);
    $nilaiKontrakText = 'Rp. ' . number_format($nilaiKontrak, 0, ',', '.');
    $nilaiKontrakTerbilang = $angkaTerbilang((int) $nilaiKontrak) . ' Rupiah';

    $namaPejabat = $pejabat?->name ?? '-';
    $jabatanPejabat = $pejabat?->jabatan ?? 'Pejabat Pengadaan';
    $nipPejabat = $pejabat?->nip ?? '-';
    $alamatPejabat = $pejabat?->alamat ?? '-';

    $namaPenyedia = $penyedia?->nama ?? '-';
    $alamatPenyedia = $penyedia?->alamat ?? '-';
    $namaPic = $penyedia?->nama_pic ?? '-';

    $kopPath = public_path('storage/images/kop-surat.png');
    $kopBase64 = file_exists($kopPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath))
        : null;
@endphp

{{-- HALAMAN 1 --}}
<div class="kop">
    @if($kopBase64)
        <img src="{!! $kopBase64 !!}" alt="Kop Surat">
    @else
        <div class="kop-fallback">KOP</div>
        <div class="kop-line"></div>
    @endif
</div>

<div class="title">
    SURAT PESANAN
</div>

<div class="subtitle">
    Nomor: {{ $pengadaan->no_kontrak ?? '-' }}
</div>

<div class="paragraph">
    Yang bertanda tangan di bawah ini :
</div>

<table class="identity-table">
    <tr>
        <td class="identity-label">Nama</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaPejabat }}</td>
    </tr>
    <tr>
        <td>Jabatan</td>
        <td class="identity-colon">:</td>
        <td>{{ $jabatanPejabat }} Kegiatan {{ $namaKegiatan }}</td>
    </tr>
    <tr>
        <td>Alamat Kantor</td>
        <td class="identity-colon">:</td>
        <td>{{ $alamatPejabat }}</td>
    </tr>
</table>

<div class="paragraph">
    Dalam hal ini mewakili Pengguna Barang/Jasa pada {{ $namaUnitKerja }}
    selanjutnya disebut sebagai Pemesan, bersama ini memesan barang kepada:
</div>

<table class="identity-table">
    <tr>
        <td class="identity-label">Nama Penyedia</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaPenyedia }}</td>
    </tr>
    <tr>
        <td>Alamat Kantor</td>
        <td class="identity-colon">:</td>
        <td>{{ $alamatPenyedia }}</td>
    </tr>
    <tr>
        <td>Yang dalam hal ini diwakili oleh</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaPic }} selaku Direktur</td>
    </tr>
</table>

<div class="paragraph">
    Untuk menyediakan barang/jasa sebagai berikut :
</div>

<table class="items-table">
    <thead>
        <tr>
            <th class="cell-no">No</th>
            <th class="cell-uraian">Uraian</th>
            <th class="cell-satuan">Satuan</th>
            <th class="cell-kuantitas">Kuantitas</th>
            <th class="cell-harga">Harga Satuan</th>
            <th class="cell-jumlah">Jumlah + PPN 11%</th>
        </tr>
    </thead>

    <tbody>
        @forelse($items as $index => $item)
            <tr>
                <td class="cell-no">{{ $index + 1 }}</td>
                <td class="cell-uraian">{{ $item->nama_barang ?? '-' }}</td>
                <td class="cell-satuan">{{ $item->satuan ?? '-' }}</td>
                <td class="cell-kuantitas">{{ $item->jumlah ?? '-' }}</td>
                <td class="cell-harga">
                    Rp. {{ number_format((float) ($item->harga_satuan_estimasi ?? 0), 0, ',', '.') }}
                </td>
                <td class="cell-jumlah">
                    Rp. {{ number_format((float) ($item->subtotal ?? 0), 0, ',', '.') }}
                </td>
            </tr>
        @empty
            <tr>
                <td class="cell-no">&nbsp;</td>
                <td class="cell-uraian">&nbsp;</td>
                <td class="cell-satuan">&nbsp;</td>
                <td class="cell-kuantitas">&nbsp;</td>
                <td class="cell-harga">&nbsp;</td>
                <td class="cell-jumlah">&nbsp;</td>
            </tr>
        @endforelse

        @for($i = $items->count(); $i < 2; $i++)
            <tr>
                <td class="text-center">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endfor

        <tr>
            <td colspan="5" class="font-bold">JUMLAH TOTAL</td>
            <td class="text-right font-bold">{{ $nilaiKontrakText }}</td>
        </tr>

        <tr>
            <td colspan="6" class="text-center font-bold">
                TERBILANG : {{ $nilaiKontrakTerbilang }}
            </td>
        </tr>
    </tbody>
</table>

<ol class="terms">
    <li>
        Tanggal barang/jasa diterima sejak {{ $tanggalKontrak }}.
    </li>
    <li>
        Syarat-syarat pekerjaan sesuai dengan persyaratan dan ketentuan kontrak;
    </li>
    <li>
        Waktu penyelesaian selama {{ $jangkaWaktu }} dan pekerjaan harus sudah selesai
        pada tanggal {{ $tanggalSelesai }}.
    </li>
    <li>
        Alamat Penerima Barang/Jasa : {{ $alamatUnitKerja }}
    </li>
    <li>
        Terhadap setiap hari keterlambatan penyelesaian pekerjaan Penyedia akan dikenakan
        denda keterlambatan sebesar 1/1000 (satu per seribu) dari nilai bagian tertentu
        dari Nilai Kontrak sebelum PPN.
    </li>
</ol>

<table class="signature">
    <tr>
        <td>
            <div>Garut, {{ $tanggalKontrak }}</div>
            <div>Untuk dan atas nama,</div>
            <div>{{ $namaUnitKerja }}</div>
            <div>{{ $jabatanPejabat }}</div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaPejabat }}</div>
            <div>NIP. {{ $nipPejabat }}</div>
        </td>

        <td>
            <div>&nbsp;</div>
            <div>Menyetujui</div>
            <div>{{ $namaPenyedia }}</div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaPic }}</div>
            <div>Direktur</div>
        </td>
    </tr>
</table>

{{-- HALAMAN 2 / COVER --}}
<div class="page-break"></div>

<div class="cover-logo">
    Logo
</div>

<div class="cover-header">
    <div>PEMERINTAH DAERAH PROVINSI</div>
    <div>{{ strtoupper($namaUnitKerja) }}</div>
    <div>{{ $alamatUnitKerja }}</div>
</div>

<div class="cover-title">
    SURAT PESANAN (SP)
</div>

<div class="cover-block">
    <div class="cover-block-label">KEGIATAN :</div>
    <div class="cover-block-value">
        {{ $namaKegiatan }}
    </div>
</div>

<div class="cover-block">
    <div class="cover-block-label">PEKERJAAN :</div>
    <div class="cover-block-value">
        {{ $namaRekening }}
    </div>
</div>

<table class="cover-table">
    <tr>
        <td class="cover-label">Nomor Invoice</td>
        <td class="cover-colon">:</td>
        <td><span class="blank-space">&nbsp;</span></td>
    </tr>

    <tr>
        <td class="cover-label">NOMOR</td>
        <td class="cover-colon">:</td>
        <td class="cover-value">{{ $pengadaan->no_kontrak ?? '-' }}</td>
    </tr>

    <tr>
        <td class="cover-label">TANGGAL</td>
        <td class="cover-colon">:</td>
        <td class="cover-value">{{ $tanggalKontrak }}</td>
    </tr>

    <tr>
        <td class="cover-label">TAHUN ANGGARAN</td>
        <td class="cover-colon">:</td>
        <td class="cover-value">{{ $tahun }}</td>
    </tr>

    <tr>
        <td class="cover-label">SUMBER DANA</td>
        <td class="cover-colon">:</td>
        <td><span class="blank-space">&nbsp;</span></td>
    </tr>

    <tr>
        <td class="cover-label">BIAYA</td>
        <td class="cover-colon">:</td>
        <td class="cover-value">
            {{ $nilaiKontrakText }},- ({{ $nilaiKontrakTerbilang }})
        </td>
    </tr>

    <tr>
        <td class="cover-label">MULAI TANGGAL</td>
        <td class="cover-colon">:</td>
        <td class="cover-value">
            {{ $tanggalKontrak }} s/d {{ $tanggalSelesai }}
        </td>
    </tr>

    <tr>
        <td class="cover-label">LOKASI</td>
        <td class="cover-colon">:</td>
        <td class="cover-value">
            {{ $namaUnitKerja }}<br>
            PROVINSI <span class="blank-space">&nbsp;</span>
        </td>
    </tr>

    <tr>
        <td class="cover-label">PENYEDIA</td>
        <td class="cover-colon">:</td>
        <td class="cover-value">
            {{ $namaPenyedia }}<br>
            {{ $alamatPenyedia }}
        </td>
    </tr>
</table>

</body>
</html>