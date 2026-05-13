<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ringkasan Kontrak</title>

    <style>
        @page {
            margin: 24px 34px;
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
            margin-bottom: 14px;
        }

        .kop img {
            width: 100%;
            max-height: 95px;
            object-fit: contain;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            line-height: 1.25;
            margin-bottom: 18px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .main-table td {
            border: 1px solid #111;
            vertical-align: top;
            padding: 5px 7px;
        }

        .no {
            width: 18px;
            text-align: center;
            font-style: normal;
        }

        .label {
            width: 250px;
            font-style: normal;
        }

        .colon {
            width: 20px;
            text-align: center;
        }

        .value {
            font-style: normal;
        }

        .items-table {
            margin-top: 4px;
            width: 100%;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #111;
            padding: 3px 5px;
            font-size: 10px;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .signature {
            margin-top: 46px;
            width: 100%;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-style: normal;
        }

        .signature-space {
            height: 72px;
        }
    </style>
</head>

<body>
@php
    $usulan = $pengadaan->usulan;
    $anggaran = $usulan?->anggaran;
    $subKegiatan = $anggaran?->subKegiatan;
    $dpa = $subKegiatan?->dpaAnggaran;

    $noDpa = $dpa?->no_dpa ?? '-';

    $tanggalDpa = $dpa?->tanggal_dpa
        ? \Carbon\Carbon::parse($dpa->tanggal_dpa)->translatedFormat('d F Y')
        : '-';

    $penyedia = $pengadaan->penyedia;
    $pejabat = $pengadaan->pejabatPenandatangan ?? $pengadaan->pejabat;
    $items = $usulan?->items ?? collect();

    $tahun = $anggaran?->tahun
        ?? ($pengadaan->tanggal_kontrak ? \Carbon\Carbon::parse($pengadaan->tanggal_kontrak)->format('Y') : now()->year);

    /*
     * Sesuai data tabel anggaran:
     * - kode_rekening
     * - nama_rekening
     *
     * Untuk judul "PENGADAAN {Uraian Rekening}",
     * kita pakai nama_rekening sebagai uraian utama.
     */
    $uraianRekening = $anggaran?->nama_rekening
        ?? $usulan?->judul
        ?? '-';

    $kodeRekening = $anggaran?->kode_rekening ?? '-';

    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        return \Carbon\Carbon::parse($value)->translatedFormat('d F Y');
    };

    $formatRupiah = function ($value) {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

    $tanggalKontrak = $formatTanggal($pengadaan->tanggal_kontrak);
    $tanggalSelesai = $formatTanggal($pengadaan->tanggal_selesai);

    $jangkaWaktu = '-';

    if ($pengadaan->tanggal_kontrak && $pengadaan->tanggal_selesai) {
        $mulai = \Carbon\Carbon::parse($pengadaan->tanggal_kontrak);
        $selesai = \Carbon\Carbon::parse($pengadaan->tanggal_selesai);
        $hari = $mulai->diffInDays($selesai) + 1;
        $jangkaWaktu = $hari . ' hari kalender';
    }
@endphp

@php
    $kopPath = public_path('storage/images/kop-surat.png');
    $kopBase64 = file_exists($kopPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($kopPath))
        : null;
@endphp

<div class="kop">
    @if($kopBase64)
        <img src="{{ $kopBase64 }}" alt="Kop Surat">
    @else
        <div>KOP SURAT</div>
    @endif
</div>

<div class="title">
    RINGKASAN KONTRAK<br>
    PENGADAAN {{ strtoupper($uraianRekening) }}<br>
    TAHUN ANGGARAN {{ $tahun }}
</div>

<table class="main-table">
    <tr>
        <td class="no">1</td>
        <td class="label">Nomor dan Tanggal DPA</td>
        <td class="colon">:</td>
        <td class="value">
            {{ $noDpa }}, tanggal {{ $tanggalDpa }}
        </td>
    </tr>

    <tr>
        <td class="no">2</td>
        <td class="label">Nama Kegiatan/Kode Rekening Kegiatan</td>
        <td class="colon">:</td>
        <td class="value">
            {{ $subKegiatan->nama_kegiatan ?? '-' }} / {{ $kodeRekening }}
        </td>
    </tr>

    <tr>
        <td class="no">3</td>
        <td class="label">Nomor dan Tanggal SP/Surat Pesanan</td>
        <td class="colon">:</td>
        <td class="value">
            {{ $pengadaan->no_kontrak ?? '-' }}, tanggal {{ $tanggalKontrak }}
        </td>
    </tr>

    <tr>
        <td class="no">4</td>
        <td class="label">Nilai</td>
        <td class="colon">:</td>
        <td class="value">{{ $formatRupiah($pengadaan->nilai_kontrak ?? 0) }}</td>
    </tr>

    <tr>
        <td class="no">4</td>
        <td class="label">Nama Penyedia</td>
        <td class="colon">:</td>
        <td class="value">{{ $penyedia->nama ?? '-' }}</td>
    </tr>

    <tr>
        <td class="no">5</td>
        <td class="label">Alamat Penyedia Jasa</td>
        <td class="colon">:</td>
        <td class="value">{{ $penyedia->alamat ?? '-' }}</td>
    </tr>

    <tr>
        <td class="no">6</td>
        <td class="label">NPWP</td>
        <td class="colon">:</td>
        <td class="value">{{ $penyedia->npwp ?? '-' }}</td>
    </tr>

    <tr>
        <td class="no">7</td>
        <td class="label">Uraian Pekerjaan dan Volume Pekerjaan</td>
        <td class="colon">:</td>
        <td class="value">
            Pengadaan {{ $uraianRekening }}, meliputi:

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th style="width: 90px;">Satuan</th>
                        <th style="width: 90px;">Kuantitas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->nama_barang ?? '-' }}</td>
                            <td class="text-center">{{ $item->satuan ?? '-' }}</td>
                            <td class="text-center">{{ $item->jumlah ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </td>
    </tr>

    <tr>
        <td class="no">8</td>
        <td class="label">Tata Cara Pembayaran</td>
        <td class="colon">:</td>
        <td class="value">
            Dibayar melalui Rekening Penyedia Rekening Bank
            {{ $penyedia->nama_bank ?? '{nama bank}' }}
            atas nama {{ $penyedia->atas_nama_rekening ?? '{atas nama}' }}
            dengan Nomor Rekening {{ $penyedia->rekening_bank ?? '{no rekening}' }}
        </td>
    </tr>

    <tr>
        <td class="no">9</td>
        <td class="label">Jangka Waktu Pelaksanaan</td>
        <td class="colon">:</td>
        <td class="value">{{ $jangkaWaktu }}</td>
    </tr>

    <tr>
        <td class="no">10</td>
        <td class="label">Tanggal Penyelesaian Pekerjaan</td>
        <td class="colon">:</td>
        <td class="value">{{ $tanggalSelesai }}</td>
    </tr>

    <tr>
        <td class="no">11</td>
        <td class="label">Jangka Waktu Pemeliharaan</td>
        <td class="colon">:</td>
        <td class="value">--</td>
    </tr>

    <tr>
        <td class="no">12</td>
        <td class="label">Ketentuan Sanksi</td>
        <td class="colon">:</td>
        <td class="value">--</td>
    </tr>
</table>

<table class="signature">
    <tr>
        <td></td>
        <td>
            <div>Garut, {{ $tanggalKontrak }}</div>
            <div>Pejabat Pengadaan</div>
            <div>{{ $subKegiatan->nama_kegiatan ?? 'NAMA KEGIATAN' }}</div>
            <div>Tahun Anggaran {{ $tahun }},</div>

            <div class="signature-space"></div>

            <div>{{ $pejabat->name ?? '_____________________________________' }}</div>
            <div>NIP. {{ $pejabat->nip ?? '................................' }}</div>
        </td>
    </tr>
</table>

</body>
</html>