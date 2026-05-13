<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\Pembayaran;
use App\Models\Pengadaan;
use App\Models\UsulanPengadaan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
        $user = $request->user();

        /*
         * Query dasar usulan.
         * Admin melihat semua data.
         * User non-admin hanya melihat usulan dari unit kerjanya
         * melalui relasi: usulan -> pemohon -> unitKerja.
         */
        $usulanQuery = UsulanPengadaan::query()
            ->with([
                'pemohon',
                'pemohon.unitKerja',
                'anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            ])
            ->whereHas('anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            });

        if (!$user->hasRole('admin')) {
            $usulanQuery->whereHas('pemohon', function ($query) use ($user) {
                $query->where('unit_kerja_id', $user->unit_kerja_id);
            });
        }

        /*
         * Query dasar pengadaan.
         */
        $pengadaanQuery = Pengadaan::query()
            ->whereHas('usulan', function ($query) use ($user) {
                if (!$user->hasRole('admin')) {
                    $query->whereHas('pemohon', function ($q) use ($user) {
                        $q->where('unit_kerja_id', $user->unit_kerja_id);
                    });
                }
            })
            ->whereHas('usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            });

        /*
         * Query dasar pembayaran.
         */
        $pembayaranQuery = Pembayaran::query()
            ->whereHas('pengadaan.usulan', function ($query) use ($user) {
                if (!$user->hasRole('admin')) {
                    $query->whereHas('pemohon', function ($q) use ($user) {
                        $q->where('unit_kerja_id', $user->unit_kerja_id);
                    });
                }
            })
            ->whereHas('pengadaan.usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            });

        /*
         * Statistik utama.
         */
        $totalUsulan = (clone $usulanQuery)->count();

        $baruBulanIni = (clone $usulanQuery)
            ->whereYear('created_at', $tahunAnggaran)
            ->whereMonth('created_at', now()->month)
            ->count();

        $menungguApproval = (clone $usulanQuery)
            ->where('status', 'diajukan')
            ->count();

        $dalamProses = (clone $usulanQuery)
            ->whereIn('status', [
                'disetujui',
                'dalam_pengadaan',
                'dokumen',
                'pembayaran',
            ])
            ->count();

        $selesaiBulanIni = (clone $usulanQuery)
            ->where('status', 'selesai')
            ->whereYear('updated_at', $tahunAnggaran)
            ->whereMonth('updated_at', now()->month)
            ->count();

        $totalEstimasi = (clone $usulanQuery)
            ->whereNotIn('status', [
                'ditolak',
                'revisi',
                'selesai',
            ])
            ->sum('total_estimasi');

        $totalKomitmen = (clone $pengadaanQuery)
            ->sum('nilai_kontrak');

        $totalRealisasi = (clone $pembayaranQuery)
            ->sum('nilai_bayar');

        /*
         * Anggaran aktif.
         */
        $anggaranQuery = Anggaran::query()
            ->where('is_active', true)
            ->where(function ($q) use ($tahunAnggaran) {
                $q->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            });

        $totalPagu = (clone $anggaranQuery)->sum('pagu');
        $totalTerpakai = (clone $anggaranQuery)->sum('terpakai');
        $totalSisa = (clone $anggaranQuery)->sum('sisa');

        /*
         * Fallback jika kolom terpakai/sisa belum di-update.
         */
        if ($totalTerpakai <= 0 && ($totalKomitmen > 0 || $totalRealisasi > 0)) {
            $totalTerpakai = $totalKomitmen + $totalRealisasi;
        }

        if ($totalSisa <= 0 && $totalPagu > 0) {
            $totalSisa = max($totalPagu - $totalTerpakai, 0);
        }

        $persenRealisasi = $totalPagu > 0
            ? round(($totalRealisasi / $totalPagu) * 100, 1)
            : 0;

        $persenTerpakai = $totalPagu > 0
            ? round(($totalTerpakai / $totalPagu) * 100, 1)
            : 0;

        $pembayaranBulanIni = (clone $pembayaranQuery)
            ->whereYear('created_at', $tahunAnggaran)
            ->whereMonth('created_at', now()->month)
            ->count();

        $stats = [
            'total_usulan' => (int) $totalUsulan,
            'menunggu_approval' => (int) $menungguApproval,
            'dalam_proses' => (int) $dalamProses,
            'selesai_bulan_ini' => (int) $selesaiBulanIni,

            'total_estimasi' => (float) $totalEstimasi,
            'baru_bulan_ini' => (int) $baruBulanIni,

            'total_pagu' => (float) $totalPagu,
            'total_komitmen' => (float) $totalKomitmen,
            'total_realisasi' => (float) $totalRealisasi,
            'total_terpakai' => (float) $totalTerpakai,
            'total_sisa' => (float) $totalSisa,

            'persen_realisasi' => (float) $persenRealisasi,
            'persen_terpakai' => (float) $persenTerpakai,

            'pembayaran_bulan_ini' => (int) $pembayaranBulanIni,
        ];

        /*
         * Distribusi status untuk Workflow 6 Tahap.
         *
         * Contoh output:
         * [
         *   'draft' => 1,
         *   'dokumen' => 2,
         * ]
         */
        $statusDistribution = (clone $usulanQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        /*
         * Usulan terbaru untuk tabel "Usulan Terbaru".
         */
        $recentUsulan = (clone $usulanQuery)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($usulan) => [
                'id' => $usulan->id,
                'no_usulan' => $usulan->no_usulan,
                'judul' => $usulan->judul,
                'tanggal_usulan' => optional($usulan->tanggal_usulan)->format('Y-m-d'),
                'total_estimasi' => (string) $usulan->total_estimasi,
                'status' => $usulan->status,
                'pemohon' => $usulan->pemohon
                    ? [
                        'id' => $usulan->pemohon->id,
                        'name' => $usulan->pemohon->name,
                    ]
                    : null,
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentUsulan' => $recentUsulan,
            'statusDistribution' => $statusDistribution,

            /*
             * Props lain kita biarkan kosong dulu.
             * Nanti di tahap berikutnya kita isi satu per satu.
             */
            'activityFeed' => [],
            'metodeDistribution' => [],
            'roleData' => [
                'role' => $user->role?->name ?? '',
            ],
            'userRole' => $user->role?->name ?? '',
            'tahunAnggaranAktif' => $tahunAnggaran,
        ]);
    }
}