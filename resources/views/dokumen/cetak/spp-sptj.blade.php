<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPP/SPTJ</title>

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
            margin-bottom: 46px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            font-size: 13px;
            line-height: 1.25;
            margin-bottom: 0;
            text-transform: uppercase;
        }

        .nomor {
            text-align: center;
            margin-top: 0;
            margin-bottom: 36px;
        }

        .paragraph {
            text-align: justify;
            margin-bottom: 10px;
        }

        .indent {
            text-indent: 28px;
        }

        .number-list {
            margin: 0 0 10px 18px;
            padding-left: 12px;
        }

        .number-list li {
            margin-bottom: 8px;
            text-align: justify;
        }

        .identity-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 14px;
            margin-bottom: 22px;
        }

        .identity-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .identity-no {
            width: 28px;
        }

        .identity-label {
            width: 145px;
        }

        .identity-colon {
            width: 12px;
            text-align: center;
        }

        .belanja-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 16px;
            table-layout: auto;
        }

        .belanja-table th,
        .belanja-table td {
            border: 1px solid #111;
            padding: 4px 4px;
            vertical-align: top;
            font-size: 8.5px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .belanja-table th {
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
        }

        /* Paksa lebar kolom untuk DomPDF */
        .belanja-table .cell-no {
            width: 4% !important;
            max-width: 4% !important;
            text-align: center;
        }

        .belanja-table .cell-kode {
            width: 11% !important;
            max-width: 11% !important;
        }

        .belanja-table .cell-penerima {
            width: 13% !important;
            max-width: 13% !important;
        }

        .belanja-table .cell-uraian {
            width: 39% !important;
            max-width: 39% !important;
        }

        .belanja-table .cell-spm-tanggal {
            width: 9% !important;
            max-width: 9% !important;
            text-align: center;
        }

        .belanja-table .cell-spm-nomor {
            width: 9% !important;
            max-width: 9% !important;
            text-align: center;
        }

        .belanja-table .cell-jumlah {
            width: 15% !important;
            max-width: 15% !important;
            text-align: right;
        }

        .uraian-cell {
            padding: 4px 4px;
        }

        .items-wrap {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .items-table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            margin-top: 3px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #111;
            padding: 2px 3px;
            font-size: 7.5px;
            line-height: 1.15;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
        }

        .items-table .item-nama {
            width: 58% !important;
            max-width: 58% !important;
        }

        .items-table .item-satuan {
            width: 18% !important;
            max-width: 18% !important;
            text-align: center;
        }

        .items-table .item-kuantitas {
            width: 24% !important;
            max-width: 24% !important;
            text-align: center;
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

        .uraian-cell {
            padding: 4px 4px;
        }

        .items-wrap {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .items-table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 3px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #111;
            padding: 2px 3px;
            font-size: 8px;
            line-height: 1.15;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
        }

        .signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 48px;
            table-layout: fixed;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }

        .signature-space {
            height: 84px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .page-break {
            page-break-before: always;
        }

        .mt-lg {
            margin-top: 34px;
        }
        .blank-space {
            display: inline-block;
            min-width: 180px;
            height: 12px;
        }
        .blank-space2 {
            display: inline-block;
            min-width: 100px;
            height: 12px;
        }
        .blank-space3 {
            display: inline-block;
            min-width: 75px;
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

    $kpa = $pengadaan->kpaPenandatangan
        ?? $pengadaan->pejabatPenandatangan
        ?? $pengadaan->pejabat;

    $tahun = $anggaran?->tahun
        ?? ($pengadaan->tanggal_kontrak
            ? \Carbon\Carbon::parse($pengadaan->tanggal_kontrak)->format('Y')
            : now()->year);

    $namaUnitKerja = $unitKerja?->nama ?? 'NAMA UNIT KERJA';
    $kodeUnitKerja = $unitKerja?->kode ?? '';

    $namaKegiatan = $subKegiatan?->nama_kegiatan ?? 'NAMA KEGIATAN';
    $kodeSubKegiatan = $subKegiatan?->kode_sub_kegiatan ?? '';

    $namaRekening = $anggaran?->nama_rekening ?? $usulan?->judul ?? '-';
    $kodeRekening = $anggaran?->kode_rekening ?? '-';

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

    $dokumenMap = $pengadaan->dokumenPengadaan
        ? $pengadaan->dokumenPengadaan->keyBy('jenis')
        : collect();

    $bapp = $dokumenMap->get('bapp');

    $tanggalSuratRaw = $bapp?->tanggal
        ?? $pengadaan->dokumenUpbj?->tanggal_bast
        ?? now();

    $tanggalSurat = $formatTanggal($tanggalSuratRaw);

    $nilaiKontrak = (float) ($pengadaan->nilai_kontrak ?? 0);
    $nilaiKontrakText = 'Rp. ' . number_format($nilaiKontrak, 0, ',', '.');
    $nilaiKontrakTerbilang = '(' . $angkaTerbilang((int) $nilaiKontrak) . ' Rupiah)';

    $namaKpa = $kpa?->name ?? '-';
    $jabatanKpa = $kpa?->jabatan ?? 'Kuasa Pengguna Anggaran';
    $nipKpa = $kpa?->nip ?? '-';

    $namaPenyedia = $penyedia?->nama ?? '-';
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
    SURAT PERNYATAAN PENGAJUAN SPP-GU
</div>

<div class="nomor">
    Nomor : <span class="blank-space">&nbsp;</span>
</div>

<div class="paragraph indent">
    Sehubungan dengan Surat Permintaan Pembayaran Ganti Uang (SPP-GU)
    Nomor : <span class="blank-space">&nbsp;</span> tanggal <span class="blank-space2">&nbsp;</span>
    yang kami ajukan sebesar {{ $nilaiKontrakText }} {{ $nilaiKontrakTerbilang }}.
    Untuk keperluan Kegiatan Pengadaan {{ $namaKegiatan }} {{ $namaUnitKerja }}
    Tahun Anggaran {{ $tahun }}, dengan ini menyatakan dengan sebenarnya bahwa :
</div>

<ol class="number-list">
    <li>
        Jumlah GU tersebut diatas akan dipergunakan untuk keperluan guna membiayai
        kegiatan : Pengadaan {{ $namaKegiatan }} pada {{ $namaUnitKerja }}
        yang akan kami laksanakan sesuai DPA-SKPD.
    </li>
    <li>
        Jumlah GU tersebut akan digunakan untuk membiayai pengeluaran-pengeluaran
        yang menurut ketentuan yang berlaku harus dilakukan dengan Pembayaran
        Ganti Uang (GU).
    </li>
</ol>

<div class="paragraph indent">
    Demikian Surat Pernyataan ini dibuat untuk melengkapi persyaratan pengajuan
    SPM-GU SKPD kami.
</div>

<table class="signature mt-lg">
    <tr>
        <td></td>
        <td>
            <div><span class="blank-space3">&nbsp;</span>, <span class="blank-space2">&nbsp;</span></div>
            <div>{{ $jabatanKpa }},</div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaKpa }}</div>
            <div>NIP. {{ $nipKpa }}</div>
        </td>
    </tr>
</table>

{{-- HALAMAN 2 --}}
<div class="page-break"></div>

<div class="kop">
    @if($kopBase64)
        <img src="{!! $kopBase64 !!}" alt="Kop Surat">
    @else
        <div class="kop-fallback">KOP</div>
        <div class="kop-line"></div>
    @endif
</div>

<div class="title">
    SURAT PERNYATAAN TANGGUNG JAWAB BELANJA (GU)
</div>

<div class="nomor">
    Nomor : <span class="blank-space">&nbsp;</span>
</div>

<table class="identity-table">
    <tr>
        <td class="identity-no">1.</td>
        <td class="identity-label">Nama Satuan Kerja</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaUnitKerja }}</td>
    </tr>
    <tr>
        <td>2.</td>
        <td>Kode Satuan Kerja</td>
        <td class="identity-colon">:</td>
        <td>{{ $kodeUnitKerja ?: '...................................' }}</td>
    </tr>
    <tr>
        <td>3.</td>
        <td>Sub Kegiatan</td>
        <td class="identity-colon">:</td>
        <td>{{ $namaKegiatan }}</td>
    </tr>
    <tr>
        <td>4.</td>
        <td>Sub Kode Kegiatan</td>
        <td class="identity-colon">:</td>
        <td>{{ $kodeSubKegiatan ?: '...................................' }}</td>
    </tr>
    <tr>
        <td>5.</td>
        <td>Kode Rekening</td>
        <td class="identity-colon">:</td>
        <td>{{ $kodeRekening }}</td>
    </tr>
    <tr>
        <td>6.</td>
        <td>Tahun Anggaran</td>
        <td class="identity-colon">:</td>
        <td>{{ $tahun }}</td>
    </tr>
</table>

<div class="paragraph indent">
    Yang bertandatangan di bawah ini Kuasa Pengguna Anggaran Kegiatan
    {{ $namaRekening }} menyatakan bahwa saya bertanggungjawab penuh atas
    segala pengeluaran yang telah dibayarkan lunas sesuai dengan ketentuan
    perundang-undangan, kepada yang berhak menerima dengan perincian sebagai berikut :
</div>

<table class="belanja-table">
    <thead>
        <tr>
            <th rowspan="2" class="cell-no">No</th>
            <th rowspan="2" class="cell-kode">Kode<br>Rekening</th>
            <th rowspan="2" class="cell-penerima">Penerima</th>
            <th rowspan="2" class="cell-uraian">Uraian</th>
            <th colspan="2">Bukti SPM</th>
            <th rowspan="2" class="cell-jumlah">Jumlah</th>
        </tr>
        <tr>
            <th class="cell-spm-tanggal">Tanggal</th>
            <th class="cell-spm-nomor">Nomor</th>
        </tr>
        <tr>
            <th class="cell-no">1</th>
            <th class="cell-kode">2</th>
            <th class="cell-penerima">3</th>
            <th class="cell-uraian">4</th>
            <th class="cell-spm-tanggal">5</th>
            <th class="cell-spm-nomor">6</th>
            <th class="cell-jumlah">7</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td class="cell-no">1</td>

            <td class="cell-kode">
                {{ $kodeRekening }}
            </td>

            <td class="cell-penerima">
                {{ $namaPic }}<br>
                Selaku Direktur<br>
                {{ $namaPenyedia }}
            </td>

            <td class="cell-uraian uraian-cell">
                <div class="items-wrap">
                    <div>
                        Pengadaan {{ $namaRekening }}, meliputi:
                    </div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th class="item-nama">Nama Barang</th>
                                <th class="item-satuan">Satuan</th>
                                <th class="item-kuantitas">Kuantitas</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td class="item-nama">{{ $item->nama_barang ?? '-' }}</td>
                                    <td class="item-satuan">{{ $item->satuan ?? '-' }}</td>
                                    <td class="item-kuantitas">{{ $item->jumlah ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="item-nama">&nbsp;</td>
                                    <td class="item-satuan">&nbsp;</td>
                                    <td class="item-kuantitas">&nbsp;</td>
                                </tr>
                            @endforelse

                            @for($i = $items->count(); $i < 3; $i++)
                                <tr>
                                    <td class="item-nama">&nbsp;</td>
                                    <td class="item-satuan">&nbsp;</td>
                                    <td class="item-kuantitas">&nbsp;</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </td>

            <td class="cell-spm-tanggal"></td>
            <td class="cell-spm-nomor"></td>

            <td class="cell-jumlah">
                {{ $nilaiKontrakText }}
            </td>
        </tr>

        <tr>
            <td class="cell-no"></td>
            <td class="cell-kode"></td>
            <td class="cell-penerima font-bold">Jumlah Belanja</td>
            <td class="cell-uraian"></td>
            <td class="cell-spm-tanggal"></td>
            <td class="cell-spm-nomor"></td>
            <td class="cell-jumlah font-bold">{{ $nilaiKontrakText }}</td>
        </tr>
    </tbody>
</table>

<div class="paragraph indent">
    Bukti-bukti belanja tersebut diatas disimpan sesuai dengan ketentuan yang berlaku
    pada SKPD Dinas, untuk kelengkapan administrasi dan keperluan pemeriksaan aparat
    pengawasan fungsional/badan pemeriksa.
</div>

<div class="paragraph indent">
    Demikian Surat Pernyataan ini dibuat dengan sebenarnya.
</div>

<table class="signature">
    <tr>
        <td></td>
        <td>
            <div><span class="blank-space3">&nbsp;</span>, <span class="blank-space2">&nbsp;</span></div>
            <div>{{ $jabatanKpa }},</div>

            <div class="signature-space"></div>

            <div class="signature-name">{{ $namaKpa }}</div>
            <div>NIP. {{ $nipKpa }}</div>
        </td>
    </tr>
</table>

</body>
</html>