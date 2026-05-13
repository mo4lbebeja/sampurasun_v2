<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\Pembayaran;
use App\Models\Pengadaan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RealisasiAnggaranController extends Controller
{
    public function index(Request $request): Response
    {
        // Authorization check
        $user = $request->user();
        $allowedRoles = ['admin', 'perencanaan', 'pptk', 'pejabat_pengadaan', 'keuangan'];
        $userRoleName = $user->isAdmin() ? 'admin' : ($user->role?->name ?? '');
        if (! in_array($userRoleName, $allowedRoles)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        // Base query berdasarkan tahun anggaran aktif dari login
        $query = Anggaran::query()
            ->with([
                'subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            ])
            ->where(function ($q) use ($tahunAnggaran) {
                $q->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->orderBy('tahun', 'desc')
            ->orderBy('kode_rekening');

        // Filter tahun di halaman tidak perlu lagi lintas tahun.
        // Kalau tetap ingin dipakai, batasi hanya dalam konteks tahun aktif.
        if ($tahun = $request->input('tahun')) {
            $query->where('tahun', $tahun);
        }

        // Filter: status (semua / aktif / nonaktif)
        if ($status = $request->input('status')) {
            if ($status === 'aktif') {
                $query->where('is_active', true);
            } elseif ($status === 'nonaktif') {
                $query->where('is_active', false);
            }
        }

        // Filter: search
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_rekening', 'like', "%{$search}%")
                  ->orWhere('nama_rekening', 'like', "%{$search}%")
                  ->orWhere('uraian', 'like', "%{$search}%");
            });
        }

        $anggaranList = $query->paginate(20)->withQueryString();

        // Hitung komitmen & realisasi per anggaran (manual loop, karena pakai relasi nested)
        $anggaranList->getCollection()->transform(function ($a) {
            // Komitmen: pengadaan kontrak yang BELUM lunas
            $komitmen = Pengadaan::query()
                ->where('status', 'kontrak')
                ->whereDoesntHave('pembayaran', fn ($q) => $q->where('status', 'lunas'))
                ->whereHas('usulan', fn ($q) => $q->where('anggaran_id', $a->id))
                ->sum('nilai_kontrak');

            // Realisasi: pembayaran lunas
            $realisasi = Pembayaran::query()
                ->where('status', 'lunas')
                ->whereHas('pengadaan.usulan', fn ($q) => $q->where('anggaran_id', $a->id))
                ->sum('nilai_bayar');

            // Hitung jumlah pengadaan terkait (untuk drill-down nanti)
            $jumlahPengadaan = Pengadaan::query()
                ->whereIn('status', ['kontrak', 'selesai'])
                ->whereHas('usulan', fn ($q) => $q->where('anggaran_id', $a->id))
                ->count();

            $a->komitmen = (float) $komitmen;
            $a->realisasi = (float) $realisasi;
            $a->jumlah_pengadaan = $jumlahPengadaan;

            return $a;
        });

        // Stats keseluruhan (berdasarkan filter aktif)
        $statsQuery = clone $query;
        $allAnggaran = $statsQuery->get();

        $totalPagu = $allAnggaran->sum('pagu');
        $totalKomitmen = 0;
        $totalRealisasi = 0;

        foreach ($allAnggaran as $a) {
            $totalKomitmen += Pengadaan::query()
                ->where('status', 'kontrak')
                ->whereDoesntHave('pembayaran', fn ($q) => $q->where('status', 'lunas'))
                ->whereHas('usulan', fn ($q) => $q->where('anggaran_id', $a->id))
                ->sum('nilai_kontrak');

            $totalRealisasi += Pembayaran::query()
                ->where('status', 'lunas')
                ->whereHas('pengadaan.usulan', fn ($q) => $q->where('anggaran_id', $a->id))
                ->sum('nilai_bayar');
        }

        $stats = [
            'total_pagu'      => $totalPagu,
            'total_komitmen'  => $totalKomitmen,
            'total_realisasi' => $totalRealisasi,
            'total_terpakai'  => $totalKomitmen + $totalRealisasi,
            'total_sisa'      => $totalPagu - ($totalKomitmen + $totalRealisasi),
            'persen_terpakai' => $totalPagu > 0
                ? round((($totalKomitmen + $totalRealisasi) / $totalPagu) * 100, 1)
                : 0,
            'persen_realisasi' => $totalPagu > 0
                ? round(($totalRealisasi / $totalPagu) * 100, 1)
                : 0,
        ];

        // Daftar tahun untuk dropdown filter
        $tahunOptions = collect([$tahunAnggaran]);

        return Inertia::render('realisasi/Index', [
            'anggaran'     => $anggaranList,
            'stats'        => $stats,
            'tahunOptions' => $tahunOptions,
            'filters'      => [
                'search' => $request->string('search')->toString(),
                'tahun'  => $tahun,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Drill-down: detail pengadaan untuk anggaran tertentu (return JSON untuk modal).
     */
    public function detailJson(Request $request, Anggaran $anggaran)
    {
        $user = $request->user();
        $allowedRoles = ['admin', 'perencanaan', 'pptk', 'pejabat_pengadaan', 'keuangan'];
        $userRoleName = $user->isAdmin() ? 'admin' : ($user->role?->name ?? '');
        if (! in_array($userRoleName, $allowedRoles)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        $anggaran->load([
            'subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
            'subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
        ]);

        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        $sesuaiTahun = (int) $anggaran->tahun === $tahunAnggaran
            || (int) $anggaran->subKegiatan?->dpaAnggaran?->tahun_anggaran === $tahunAnggaran;

        abort_unless($sesuaiTahun, 403);
        
        // Ambil semua pengadaan terkait anggaran ini
        $pengadaanList = Pengadaan::query()
            ->with([
                'usulan:id,no_usulan,judul,anggaran_id,pemohon_id',
                'usulan.pemohon:id,name',
                'penyedia:id,nama',
                'pembayaran:id,pengadaan_id,status,nilai_bayar,tanggal_bayar',
            ])
            ->whereIn('status', ['kontrak', 'selesai', 'batal'])
            ->whereHas('usulan', fn ($q) => $q->where('anggaran_id', $anggaran->id))
            ->latest('id')
            ->get();

        return response()->json([
            'anggaran'      => $anggaran,
            'pengadaan'     => $pengadaanList,
        ]);
    }
}