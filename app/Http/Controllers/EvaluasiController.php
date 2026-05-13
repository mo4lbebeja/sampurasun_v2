<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluasiRequest;
use App\Models\Evaluasi;
use App\Models\Pengadaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EvaluasiController extends Controller
{
    /**
     * Daftar pengadaan yang siap dievaluasi + history evaluasi yang sudah selesai.
     */
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        // Pengadaan dengan usulan status='evaluasi' dan tahun anggaran aktif
        $siapEvaluasi = Pengadaan::query()
            ->with([
                'usulan:id,no_usulan,judul,total_estimasi,pemohon_id,status,anggaran_id',
                'usulan.pemohon:id,name,unit_kerja_id',
                'usulan.pemohon.unitKerja:id,nama',
                'usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                'penyedia:id,nama',
            ])
            ->whereHas('usulan', fn ($q) => $q->where('status', 'evaluasi'))
            ->whereHas('usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->latest('id')
            ->get();

        // History evaluasi tahun anggaran aktif
        $sudahDievaluasi = Evaluasi::query()
            ->with([
                'pengadaan:id,no_pengadaan,nilai_kontrak,penyedia_id,usulan_id',
                'pengadaan.penyedia:id,nama',
                'pengadaan.usulan:id,no_usulan,judul,anggaran_id',
                'pengadaan.usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'pengadaan.usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'pengadaan.usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                'evaluator:id,name',
            ])
            ->whereHas('pengadaan.usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->latest('tanggal_evaluasi')
            ->limit(20)
            ->get();

        return Inertia::render('evaluasi/Index', [
            'siapEvaluasi'    => $siapEvaluasi,
            'sudahDievaluasi' => $sudahDievaluasi,
        ]);
    }

    /**
     * Form input/lihat evaluasi.
     */
    public function edit(Pengadaan $pengadaan): Response|RedirectResponse
    {
        $pengadaan->load('usulan', 'evaluasi');

        // Validasi: hanya pengadaan dengan usulan status='evaluasi' atau 'selesai' (untuk view)
        if (! $pengadaan->usulan
            || ! in_array($pengadaan->usulan->status, ['evaluasi', 'selesai'])) {
            return redirect()
                ->route('evaluasi.index')
                ->with('error', 'Pengadaan ini tidak dalam tahap evaluasi.');
        }

        $pengadaan->load([
            'usulan:id,no_usulan,judul,total_estimasi,status,pemohon_id',
            'usulan.pemohon:id,name,unit_kerja_id',
            'usulan.pemohon.unitKerja:id,nama',
            'penyedia',
            'pejabat:id,name',
            'pembayaran:id,pengadaan_id,nilai_bersih,tanggal_bayar',
        ]);

        return Inertia::render('evaluasi/Edit', [
            'pengadaan' => $pengadaan,
            'evaluasi'  => $pengadaan->evaluasi, // bisa null kalau belum dievaluasi
        ]);
    }

    /**
     * Submit evaluasi — final action, tidak bisa diubah lagi.
     */
    public function store(StoreEvaluasiRequest $request, Pengadaan $pengadaan): RedirectResponse
    {
        $pengadaan->load('usulan', 'evaluasi');

        if (! $pengadaan->usulan || $pengadaan->usulan->status !== 'evaluasi') {
            return back()->with('error', 'Pengadaan ini tidak dalam tahap evaluasi.');
        }

        if ($pengadaan->evaluasi) {
            return back()->with('error', 'Evaluasi sudah pernah disimpan.');
        }

        $data = $request->validated();

        // Auto-calc nilai rata-rata
        $data['nilai_rata_rata'] = round(
            ($data['nilai_kinerja_penyedia']
            + $data['ketepatan_waktu']
            + $data['kesesuaian_spesifikasi']
            + $data['kualitas_barang']) / 4,
            2
        );

        // Handle file upload
        if ($request->hasFile('file_laporan')) {
            $data['file_laporan'] = $request->file('file_laporan')->store('evaluasi/laporan', 'public');
        }

        $data['evaluator_id'] = $request->user()->id;
        $data['pengadaan_id'] = $pengadaan->id;

        DB::transaction(function () use ($pengadaan, $data) {
            Evaluasi::create($data);
            $pengadaan->usulan->update(['status' => 'selesai']);
        });

        return redirect()
            ->route('evaluasi.index')
            ->with('success', "Evaluasi {$pengadaan->no_pengadaan} berhasil disimpan. Pengadaan kini berstatus selesai.");
    }
}