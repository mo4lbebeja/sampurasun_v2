<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluasiRequest;
use App\Models\Evaluasi;
use App\Models\Pengadaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use App\Notifications\PengadaanKontrakNotification;
use Illuminate\Support\Facades\Notification;

use App\Notifications\UsulanDisetujuiNotification;

class EvaluasiController extends Controller
{
    /**
     * Daftar pengadaan yang siap dievaluasi + history evaluasi yang sudah selesai.
     */
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
        // Paket siap dievaluasi: pembayaran lunas, belum ada evaluasi
        $siapEvaluasi = Pengadaan::query()
            ->with([
                'usulan:id,no_usulan,judul,total_estimasi,pemohon_id,status,anggaran_id',
                'usulan.pemohon:id,name,unit_kerja_id',
                'usulan.pemohon.unitKerja:id,nama',
                'usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                'penyedia:id,nama',
                'pembayaran:id,pengadaan_id,status,nilai_bayar,tanggal_bayar',
            ])
            // ← SEBELUM: whereHas('usulan', fn ($q) => $q->where('status', 'evaluasi'))
            // ← SESUDAH: pembayaran lunas, belum dievaluasi
            ->whereHas('pembayaran', fn ($q) => $q->where('status', 'lunas'))
            ->doesntHave('evaluasi')
            ->whereHas('usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->latest('id')
            ->get();
 
        // History evaluasi yang sudah selesai
        $sudahDievaluasi = Evaluasi::query()
            ->with([
                'pengadaan:id,no_pengadaan,nilai_kontrak,penyedia_id,usulan_id,nama_paket',
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
        $pengadaan->load('usulan', 'evaluasi', 'pembayaran');
 
        // ← SEBELUM:
        // if (! $pengadaan->usulan || ! in_array($pengadaan->usulan->status, ['evaluasi', 'selesai']))
        //
        // ← SESUDAH: cek pembayaran paket sudah lunas
        $siapEvaluasi = $pengadaan->pembayaran?->status === 'lunas';
        $sudahSelesai = $pengadaan->evaluasi !== null;
 
        if (! $siapEvaluasi && ! $sudahSelesai) {
            return redirect()
                ->route('evaluasi.index')
                ->with('error', 'Pengadaan ini belum siap untuk dievaluasi (pembayaran belum lunas).');
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
            'evaluasi'  => $pengadaan->evaluasi,
        ]);
    }

    /**
     * Submit evaluasi — final action, tidak bisa diubah lagi.
     */
    public function store(StoreEvaluasiRequest $request, Pengadaan $pengadaan): RedirectResponse
    {
        $pengadaan->load('usulan', 'evaluasi', 'pembayaran');
 
        // ← SEBELUM: if (! $pengadaan->usulan || $pengadaan->usulan->status !== 'evaluasi')
        // ← SESUDAH: cek pembayaran paket sudah lunas
        if ($pengadaan->pembayaran?->status !== 'lunas') {
            return back()->with('error', 'Pengadaan ini belum siap untuk dievaluasi (pembayaran belum lunas).');
        }
 
        if ($pengadaan->evaluasi) {
            return back()->with('error', 'Evaluasi sudah pernah disimpan.');
        }
 
        $data = $request->validated();
 
        // Auto-calc nilai rata-rata dari 4 kriteria
        $data['nilai_rata_rata'] = round(
            ($data['nilai_kinerja_penyedia']
                + $data['ketepatan_waktu']
                + $data['kesesuaian_spesifikasi']
                + $data['kualitas_barang']) / 4,
            2
        );
 
        if ($request->hasFile('file_laporan')) {
            $data['file_laporan'] = $request->file('file_laporan')
                ->store('evaluasi/laporan', 'public');
        }
 
        $data['evaluator_id'] = $request->user()->id;
        $data['pengadaan_id'] = $pengadaan->id;
 
        DB::transaction(function () use ($pengadaan, $data) {
            Evaluasi::create($data);
 
            // ← SEBELUM: $pengadaan->usulan->update(['status' => 'selesai'])
            //   (update usulan secara langsung — tidak cocok untuk multi-paket)
            //
            // ← SESUDAH: update status PAKET ini ke 'selesai'
            //   PengadaanObserver::updated() akan terpicu
            //   → memanggil usulan->refreshStatus()
            //   → jika SEMUA paket selesai → usulan.status = 'selesai' otomatis
            //   → jika masih ada paket lain aktif → usulan tetap 'dalam_pengadaan'
        });
 
        ActivityLogger::fromRequest(
            request:     $request,
            action:      'evaluasi.submit',
            description: "Evaluasi pengadaan {$pengadaan->no_pengadaan} selesai, paket berstatus selesai",
            usulanId:    $pengadaan->usulan?->id,
            subjectType: 'Pengadaan',
            subjectId:   $pengadaan->id,
            properties:  [
                'nilai_rata_rata' => $data['nilai_rata_rata'],
                'rekomendasi'     => $data['rekomendasi'] ?? null,
            ],
        );
 
        return redirect()
            ->route('evaluasi.index')
            ->with('success', "Evaluasi {$pengadaan->no_pengadaan} berhasil disimpan. Paket kini berstatus selesai.");
    }
}