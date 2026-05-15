<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST</title>

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
            line-height: 1.45;
        }
        
        @include('dokumen.cetak.partials.kop-surat-style')

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            line-height: 1.3;
            margin-top: 8px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .nomor {
            text-align: center;
            margin-bottom: 18px;
        }

        .paragraph {
            text-align: justify;
            margin-bottom: 10px;
        }

        .identity-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px;
        }

        .identity-table td {
            vertical-align: top;
            padding: 2px 4px;
        }

        .identity-no {
            width: 22px;
        }

        .identity-label {
            width: 72px;
        }

        .identity-colon {
            width: 12px;
            text-align: center;
        }

        .signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 32px;
            table-layout: fixed;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }

        .signature-title {
            font-weight: bold;
            text-transform: uppercase;
        }

        .signature-role {
            min-height: 64px;
        }

        .signature-space {
            height: 70px;
        }

        .signature-name {
            min-height: 18px;
            font-weight: bold;
            text-decoration: underline;
        }

        .signature-position {
            min-height: 16px;
        }

        .font-bold {
            font-weight: bold;
        }

        .underline {
            text-decoration: underline;
        }

        .page-break {
            page-break-before: always;
        }

        .lampiran-title {
            margin-top: 50px;
            margin-bottom: 22px;
            line-height: 1.4;
        }

        .lampiran-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lampiran-table th,
        .lampiran-table td {
            border: 1px solid #111;
            padding: 4px 5px;
            vertical-align: middle;
        }

        .lampiran-table th {
            text-align: center;
            font-weight: normal;
        }

        .text-center {
            text-align: center;
        }

        .small {
            font-size: 10px;
        }
    </style>
</head>
<body>
@include('dokumen.cetak._item_helpers')
@php
    $usulan = $pengadaan->usulan;
    $anggaran = $usulan?->anggaran;
    $subKegiatan = $anggaran?->subKegiatan;
    $penyedia = $pengadaan->penyedia;
    $pejabat = $pengadaan->pejabatPenandatangan ?? $pengadaan->pejabat;

    $tahun = $anggaran?->tahun
        ?? ($pengadaan->tanggal_kontrak ? \Carbon\Carbon::parse($pengadaan->tanggal_kontrak)->format('Y') : now()->year);

    $namaRekening = $anggaran?->nama_rekening
        ?? $usulan?->judul
        ?? '-';

    $namaKegiatan = $subKegiatan?->nama_kegiatan ?? 'NAMA KEGIATAN';

    $dokumenMap = $pengadaan->dokumenPengadaan
        ? $pengadaan->dokumenPengadaan->keyBy('jenis')
        : collect();

    $bast = $dokumenMap->get('bast') ?? $dokumen ?? null;
    $bapmhp = $dokumenMap->get('bapmhp');
    $baprhp = $dokumenMap->get('baprhp');

    $tanggalBastRaw = $bast?->tanggal
        ?? $pengadaan->dokumenUpbj?->tanggal_bast
        ?? now();

    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->translatedFormat('d F Y');
    };

    $tanggalBast = $formatTanggal($tanggalBastRaw);
    $tanggalBapmhp = $formatTanggal($bapmhp?->tanggal);
    $tanggalBaprhp = $formatTanggal($baprhp?->tanggal);

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

        return (string) $angka;
    };

    $namaHariIndonesia = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu',
    ];

    $namaBulanIndonesia = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $tanggalCarbon = \Carbon\Carbon::parse($tanggalBastRaw);

    $hari = $namaHariIndonesia[$tanggalCarbon->format('l')] ?? $tanggalCarbon->format('l');
    $tanggalText = $angkaTerbilang((int) $tanggalCarbon->day);
    $bulanText = $namaBulanIndonesia[(int) $tanggalCarbon->month] ?? $tanggalCarbon->format('F');
    $tahunText = $angkaTerbilang((int) $tanggalCarbon->year);

    $kalimatTanggal = $hari . ' Tanggal ' . $tanggalText . ' Bulan ' . $bulanText . ' Tahun ' . $tahunText;    
    $nomorBast = $bast?->nomor ?? $pengadaan->dokumenUpbj?->no_bast ?? '-';
    $nomorBapmhp = $bapmhp?->nomor ?? '-';
    $nomorBaprhp = $baprhp?->nomor ?? '-';

    $namaPenyedia = $penyedia?->nama ?? '-';
    $namaPic = $penyedia?->nama_pic ?? '-';
    $alamatPenyedia = $penyedia?->alamat ?? '-';

    $namaPejabat = $pejabat?->name ?? '-';
    $alamatPejabat = $pejabat?->alamat ?? '-';
    $jabatanPejabat = $pejabat?->jabatan ?? 'Pejabat Pengadaan';
    $nipPejabat = $pejabat?->nip ?? '-';

    $kopPath = public_path('storage/images/kop-surat.png');
    $kopBase64 = null;

    if (file_exists($kopPath)) {
        $kopBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath));
    }
@endphp

{{-- HALAMAN 1 --}}
@include('dokumen.cetak.partials.kop-surat')

<div class="title">
    Berita Acara Serah Terima<br>
    Pengadaan {{ $namaRekening }}
</div>

<div class="nomor">
    Nomor : {{ $nomorBast }}
</div>

<div class="paragraph">
    Pada hari ini {{ $kalimatTanggal }}, kami yang bertandatangan di bawah ini:
</div>

<table class="identity-table">
    <tr>
        <td class="identity-no">1.</td>
        <td class="identity-label">Nama</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaPic }}</td>
    </tr>
    <tr>
        <td></td>
        <td>Alamat</td>
        <td class="identity-colon">:</td>
        <td>{{ $alamatPenyedia }}</td>
    </tr>
    <tr>
        <td></td>
        <td>Jabatan</td>
        <td class="identity-colon">:</td>
        <td>Direktur</td>
    </tr>
</table>

<div class="paragraph">
    Bertindak untuk dan atas nama {{ $namaPenyedia }} selaku Penyedia Barang/Jasa,
    yang selanjutnya disebut <span class="font-bold">PIHAK KESATU</span>.
</div>

<table class="identity-table">
    <tr>
        <td class="identity-no">2.</td>
        <td class="identity-label">Nama</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaPejabat }}</td>
    </tr>
    <tr>
        <td></td>
        <td>Alamat</td>
        <td class="identity-colon">:</td>
        <td>{{ $alamatPejabat }}</td>
    </tr>
    <tr>
        <td></td>
        <td>Jabatan</td>
        <td class="identity-colon">:</td>
        <td>{{ $jabatanPejabat }}</td>
    </tr>
</table>

<div class="paragraph">
    Bertindak untuk dan atas nama Instansi, yang selanjutnya disebut
    <span class="font-bold">PIHAK KEDUA</span>.
</div>

<div class="paragraph">
    PIHAK KESATU telah menyerahkan kepada PIHAK KEDUA dan PIHAK KEDUA telah memeriksa
    dan menerima sesuai dengan Berita Acara Pemeriksaan Hasil Pekerjaan Nomor:
    {{ $nomorBapmhp }} tanggal {{ $tanggalBapmhp }} dan Berita Acara Penerimaan Hasil
    Pekerjaan Nomor: {{ $nomorBaprhp }} tanggal {{ $tanggalBaprhp }}.
</div>

<div class="paragraph">
    Sesuai dengan hasil tersebut, maka pekerjaan Pengadaan {{ $namaRekening }} telah
    selesai sepenuhnya (100%).
</div>

<div class="paragraph">
    Demikian Berita Acara Serah Terima Hasil Pekerjaan Pengadaan {{ $namaRekening }}
    dalam rangkap 3 (Tiga) untuk dipergunakan sebagaimana mestinya.
</div>

<table class="signature">
    <tr>
        <td>
            <div class="signature-title">PIHAK KESATU</div>

            <div class="signature-role">
                <div>{{ $namaPenyedia }},</div>
            </div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaPic }}</div>
            <div class="signature-position">Direktur</div>
        </td>

        <td>
            <div class="signature-title">PIHAK KEDUA</div>

            <div class="signature-role">
                <div>Pejabat Pengadaan</div>
                <div>{{ $namaKegiatan }}</div>
                <div>Tahun Anggaran {{ $tahun }},</div>
            </div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaPejabat }}</div>
            <div class="signature-position">NIP. {{ $nipPejabat }}</div>
        </td>
    </tr>
</table>

{{-- HALAMAN 2 --}}
<div class="page-break"></div>

@include('dokumen.cetak.partials.kop-surat')


<div class="lampiran-title">
    Lampiran Berita Acara Serah Terima Hasil Pekerjaan<br>
    Pengadaan {{ $namaRekening }}
</div>

<table class="lampiran-table">
    <thead>
        <tr>
            <th rowspan="2" style="width: 34px;">No</th>
            <th rowspan="2">Uraian</th>
            <th rowspan="2" style="width: 70px;">Satuan</th>
            <th rowspan="2" style="width: 70px;">Volume</th>
            <th colspan="2" style="width: 160px;">HASIL</th>
            <th rowspan="2" style="width: 90px;">KET</th>
        </tr>
        <tr>
            <th style="width: 90px;">Lengkap/Sesuai</th>
            <th style="width: 70px;">Tidak</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->nama_barang ?? '-' }}</td>
                <td class="text-center">{{ $item->satuan ?? '-' }}</td>
                <td class="text-center">{{ $item->jumlah ?? '-' }}</td>
                <td class="text-center">√</td>
                <td></td>
                <td></td>
            </tr>
        @empty
            <tr>
                <td class="text-center">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td class="text-center">√</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endforelse

        @for($i = $items->count(); $i < 5; $i++)
            <tr>
                <td class="text-center">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endfor
    </tbody>
</table>

<table class="signature">
    <tr>
        <td>
            <div class="signature-title">PIHAK KESATU</div>

            <div class="signature-role">
                <div>{{ $namaPenyedia }},</div>
            </div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaPic }}</div>
            <div class="signature-position">Direktur</div>
        </td>

        <td>
            <div class="signature-title">PIHAK KEDUA</div>

            <div class="signature-role">
                <div>Pejabat Pengadaan</div>
                <div>{{ $namaKegiatan }}</div>
                <div>Tahun Anggaran {{ $tahun }},</div>
            </div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaPejabat }}</div>
            <div class="signature-position">NIP. {{ $nipPejabat }}</div>
        </td>
    </tr>
</table>

</body>
</html>