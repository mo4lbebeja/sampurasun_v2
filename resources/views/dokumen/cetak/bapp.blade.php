<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAPP</title>

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
            width: 90px;
        }

        .identity-colon {
            width: 12px;
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 42px;
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
            height: 72px;
        }

        .signature-name {
            min-height: 18px;
            font-weight: bold;
            text-decoration: underline;
        }

        .signature-position {
            min-height: 16px;
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

    /*
     * Sesuai template BAPP:
     * PIHAK KESATU = KPA/Direktur
     * PIHAK KEDUA  = Penyedia
     */
    $kpa = $pengadaan->kpaPenandatangan
        ?? $pengadaan->pejabatPenandatangan
        ?? $pengadaan->pejabat;

    $tahun = $anggaran?->tahun
        ?? ($pengadaan->tanggal_kontrak ? \Carbon\Carbon::parse($pengadaan->tanggal_kontrak)->format('Y') : now()->year);

    $namaRekening = $anggaran?->nama_rekening
        ?? $usulan?->judul
        ?? '-';

    $namaKegiatan = $subKegiatan?->nama_kegiatan ?? 'NAMA KEGIATAN';
    $namaUnitKerja = $unitKerja?->nama ?? 'NAMA UNIT KERJA';

    $dokumenMap = $pengadaan->dokumenPengadaan
        ? $pengadaan->dokumenPengadaan->keyBy('jenis')
        : collect();

    $bapp = $dokumenMap->get('bapp') ?? $dokumen ?? null;
    $bast = $dokumenMap->get('bast');

    $tanggalBappRaw = $bapp?->tanggal
        ?? $pengadaan->dokumenUpbj?->tanggal_bast
        ?? now();

    $tanggalBastRaw = $bast?->tanggal
        ?? $pengadaan->dokumenUpbj?->tanggal_bast
        ?? null;

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

    $tanggalCarbon = \Carbon\Carbon::parse($tanggalBappRaw);

    $hari = $namaHariIndonesia[$tanggalCarbon->format('l')] ?? $tanggalCarbon->format('l');
    $tanggalText = $angkaTerbilang((int) $tanggalCarbon->day);
    $bulanText = $namaBulanIndonesia[(int) $tanggalCarbon->month] ?? $tanggalCarbon->format('F');
    $tahunText = $angkaTerbilang((int) $tanggalCarbon->year);

    $kalimatTanggal = $hari . ' Tanggal ' . $tanggalText . ' Bulan ' . $bulanText . ' Tahun ' . $tahunText;

    $nomorBapp = $bapp?->nomor ?? '-';
    $nomorBast = $bast?->nomor ?? $pengadaan->dokumenUpbj?->no_bast ?? '-';
    $tanggalBast = $formatTanggal($tanggalBastRaw);

    $tanggalKontrak = $formatTanggal($pengadaan->tanggal_kontrak);

    $nilaiKontrak = (float) ($pengadaan->nilai_kontrak ?? 0);
    $nilaiKontrakText = 'Rp ' . number_format($nilaiKontrak, 0, ',', '.');
    $nilaiKontrakTerbilang = $angkaTerbilang((int) $nilaiKontrak) . ' Rupiah';

    $namaKpa = $kpa?->name ?? '-';
    $jabatanKpa = $kpa?->jabatan ?? 'Kuasa Pengguna Anggaran';
    $nipKpa = $kpa?->nip ?? '-';

    $namaPenyedia = $penyedia?->nama ?? '-';
    $namaPic = $penyedia?->nama_pic ?? '-';
    $alamatPenyedia = $penyedia?->alamat ?? '-';
    $namaBank = $penyedia?->nama_bank ?? '-';
    $rekeningBank = $penyedia?->rekening_bank ?? '-';
    $atasNamaRekening = $penyedia?->atas_nama_rekening ?? '-';

    $kopPath = public_path('storage/images/kop-surat.png');
    $kopBase64 = null;

    if (file_exists($kopPath)) {
        $kopBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath));
    }
@endphp

@include('dokumen.cetak.partials.kop-surat')


<div class="title">
    Berita Acara Persetujuan Pembayaran
</div>

<div class="nomor">
    Nomor : {{ $nomorBapp }}
</div>

<div class="paragraph">
    Pada hari ini {{ $kalimatTanggal }}, kami yang bertandatangan di bawah ini:
</div>

<table class="identity-table">
    <tr>
        <td class="identity-no">1.</td>
        <td class="identity-label">Nama</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaKpa }}</td>
    </tr>
    <tr>
        <td></td>
        <td>Jabatan</td>
        <td class="identity-colon">:</td>
        <td>{{ $jabatanKpa }} {{ $namaKegiatan }}</td>
    </tr>
</table>

<div class="paragraph">
    Selanjutnya disebut <span class="font-bold">PIHAK KESATU</span>.
</div>

<table class="identity-table">
    <tr>
        <td class="identity-no">2.</td>
        <td class="identity-label">Nama</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaPic }}</td>
    </tr>
    <tr>
        <td></td>
        <td>Jabatan</td>
        <td class="identity-colon">:</td>
        <td>Direktur</td>
    </tr>
    <tr>
        <td></td>
        <td>Perusahaan</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaPenyedia }}</td>
    </tr>
    <tr>
        <td></td>
        <td>Alamat</td>
        <td class="identity-colon">:</td>
        <td>{{ $alamatPenyedia }}</td>
    </tr>
</table>

<div class="paragraph">
    Selanjutnya disebut <span class="font-bold">PIHAK KEDUA</span>.
</div>

<div class="paragraph">
    Berdasarkan Berita Acara Serah Terima Hasil Pekerjaan Nomor: {{ $nomorBast }}
    tanggal {{ $tanggalBast }} bahwa PIHAK KEDUA telah berhak menerima pembayaran
    pekerjaan Pengadaan {{ $namaRekening }} dari kegiatan {{ $namaKegiatan }} sebesar
    {{ $nilaiKontrakText }} ({{ $nilaiKontrakTerbilang }}); sebagaimana yang diisyaratkan
    dalam Surat Pesanan/SP Nomor {{ $pengadaan->no_kontrak ?? '-' }} tanggal {{ $tanggalKontrak }}
    yang akan ditransfer melalui {{ $namaBank }} dengan Nomor Rekening {{ $rekeningBank }}
    atas nama {{ $atasNamaRekening }}.
</div>

<div class="paragraph">
    Demikian Berita Acara Pembayaran ini dibuat dalam rangkap 3 (Tiga), untuk dipergunakan
    sebagaimana mestinya.
</div>

<table class="signature">
    <tr>
        <td>
            <div class="signature-title">PIHAK KESATU</div>

            <div class="signature-role">
                <div>{{ $jabatanKpa }}</div>
                <div>{{ $namaUnitKerja }}</div>
                <div>Tahun Anggaran {{ $tahun }},</div>
            </div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaKpa }}</div>
            <div class="signature-position">NIP. {{ $nipKpa }}</div>
        </td>

        <td>
            <div class="signature-title">PIHAK KEDUA</div>

            <div class="signature-role">
                <div>{{ $namaPenyedia }}</div>
            </div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaPic }}</div>
            <div class="signature-position">Direktur</div>
        </td>
    </tr>
</table>

</body>
</html>