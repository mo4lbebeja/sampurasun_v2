<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;       // ← TAMBAHAN: import yang kurang
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

        if (! $user->hasRole('admin')) {
            $usulanQuery->whereHas('pemohon', function ($query) use ($user) {
                $query->where('unit_kerja_id', $user->unit_kerja_id);
            });
        }

        $pengadaanQuery = Pengadaan::query()
            ->whereHas('usulan', function ($query) use ($user) {
                if (! $user->hasRole('admin')) {
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

        $pembayaranQuery = Pembayaran::query()
            ->whereHas('pengadaan.usulan', function ($query) use ($user) {
                if (! $user->hasRole('admin')) {
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

        // ── Statistik utama ─────────────────────────────────────────
        $totalUsulan = (clone $usulanQuery)->count();

        $baruBulanIni = (clone $usulanQuery)
            ->whereYear('created_at', $tahunAnggaran)
            ->whereMonth('created_at', now()->month)
            ->count();

        $menungguApproval = (clone $usulanQuery)
            ->where('status', 'diajukan')
            ->count();

        $dalamProses = (clone $usulanQuery)
            ->whereIn('status', ['disetujui', 'dalam_pengadaan'])
            ->count();

        $selesaiBulanIni = Pembayaran::query()
            ->where('status', 'lunas')
            ->whereYear(
                \Illuminate\Support\Facades\DB::raw('COALESCE(tanggal_bayar, updated_at)'),
                now()->year
            )
            ->whereMonth(
                \Illuminate\Support\Facades\DB::raw('COALESCE(tanggal_bayar, updated_at)'),
                now()->month
            )
            ->whereHas('pengadaan.usulan', function ($query) use ($user) {
                if (! $user->hasRole('admin')) {
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
            })
            ->count();

        $totalEstimasi = (clone $usulanQuery)
            ->whereNotIn('status', ['ditolak', 'revisi', 'selesai'])
            ->sum('total_estimasi');

        $totalKomitmen  = (clone $pengadaanQuery)->sum('nilai_kontrak');
        $totalRealisasi = (clone $pembayaranQuery)->sum('nilai_bayar');

        // ── Anggaran aktif ──────────────────────────────────────────
        $anggaranQuery = Anggaran::query()
            ->where('is_active', true)
            ->where(function ($q) use ($tahunAnggaran) {
                $q->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            });

        $totalPagu     = (clone $anggaranQuery)->sum('pagu');
        $totalTerpakai = (clone $anggaranQuery)->sum('terpakai');
        $totalSisa     = (clone $anggaranQuery)->sum('sisa');

        if ($totalTerpakai <= 0 && ($totalKomitmen > 0 || $totalRealisasi > 0)) {
            $totalTerpakai = $totalKomitmen + $totalRealisasi;
        }

        if ($totalSisa <= 0 && $totalPagu > 0) {
            $totalSisa = max($totalPagu - $totalTerpakai, 0);
        }

        $persenRealisasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0;
        $persenTerpakai  = $totalPagu > 0 ? round(($totalTerpakai  / $totalPagu) * 100, 1) : 0;

        $pembayaranBulanIni = (clone $pembayaranQuery)
            ->whereYear('created_at', $tahunAnggaran)
            ->whereMonth('created_at', now()->month)
            ->count();

        $stats = [
            'total_usulan'         => (int)   $totalUsulan,
            'menunggu_approval'    => (int)   $menungguApproval,
            'dalam_proses'         => (int)   $dalamProses,
            'selesai_bulan_ini'    => (int)   $selesaiBulanIni,
            'total_estimasi'       => (float) $totalEstimasi,
            'baru_bulan_ini'       => (int)   $baruBulanIni,
            'total_pagu'           => (float) $totalPagu,
            'total_komitmen'       => (float) $totalKomitmen,
            'total_realisasi'      => (float) $totalRealisasi,
            'total_terpakai'       => (float) $totalTerpakai,
            'total_sisa'           => (float) $totalSisa,
            'persen_realisasi'     => (float) $persenRealisasi,
            'persen_terpakai'      => (float) $persenTerpakai,
            'pembayaran_bulan_ini' => (int)   $pembayaranBulanIni,
        ];

        // ── Distribusi status ───────────────────────────────────────
        $statusDistribution = (clone $usulanQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // ── Usulan terbaru ──────────────────────────────────────────
        $recentUsulan = (clone $usulanQuery)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($usulan) => [
                'id'             => $usulan->id,
                'no_usulan'      => $usulan->no_usulan,
                'judul'          => $usulan->judul,
                'tanggal_usulan' => optional($usulan->tanggal_usulan)->format('Y-m-d'),
                'total_estimasi' => (string) $usulan->total_estimasi,
                'status'         => $usulan->status,
                'pemohon'        => $usulan->pemohon
                    ? ['id' => $usulan->pemohon->id, 'name' => $usulan->pemohon->name]
                    : null,
            ])
            ->values();

        // ── Activity feed ───────────────────────────────────────────
                $activityFeed = ActivityLog::query()
            ->with([
                'user:id,name,role_id',
                'user.role:id,name,display_name',
                'usulan:id,no_usulan,judul',
            ])
            ->when(! $user->isAdmin(), function ($q) use ($user) {
                $q->whereHas('usulan.pemohon', fn ($p) =>
                    $p->where('unit_kerja_id', $user->unit_kerja_id)
                );
            })
            ->whereIn('action', [
                'usulan.submit',
                'approval.approve',
                'approval.reject',
                'approval.revise',
                'pengadaan.start',
                'pengadaan.kontrak',
                'pengadaan.cancel',
                'dokumen.complete',
                'pembayaran.lunas',
                'evaluasi.submit',
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id'          => $log->id,
                'type'        => str_contains($log->action, 'approval') ? 'approval' : 'submission',
                'keputusan'   => match ($log->action) {
                    'approval.approve' => 'disetujui',
                    'approval.reject'  => 'ditolak',
                    'approval.revise'  => 'revisi',
                    default            => null,
                },
                'action'      => $log->action,
                'description' => $log->description,
                'actor'       => $log->user?->name ?? '—',
                'no_usulan'   => $log->usulan?->no_usulan,
                'judul'       => $log->usulan?->judul,
                'usulan_id'   => $log->usulan_id,
                // ← SEBELUM: 'created_at' => $log->created_at?->toISOString()
                // ← SESUDAH: 'timestamp'  → sesuai dengan type di Dashboard.vue
                'timestamp'   => $log->created_at?->toISOString(),
            ])
            ->values()
            ->toArray();

        // ── Distribusi metode pengadaan ─────────────────────────────
        $metodeDistribution = Pengadaan::query()
            ->whereHas('usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', fn ($dpa) =>
                        $dpa->where('tahun_anggaran', $tahunAnggaran)
                    );
            })
            ->selectRaw('metode, COUNT(*) as count, SUM(nilai_kontrak) as total_nilai')
            ->groupBy('metode')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'metode'      => $row->metode,
                'count'       => (int)   $row->count,
                'total_nilai' => (float) $row->total_nilai,
            ])
            ->toArray();

        // Workflow counts per tahap — berdasarkan kondisi paket, bukan usulan.status
        $anggaranScope = function ($q) use ($tahunAnggaran) {
            $q->where('tahun', $tahunAnggaran)
            ->orWhereHas('subKegiatan.dpaAnggaran', fn ($d) =>
                $d->where('tahun_anggaran', $tahunAnggaran)
            );
        };

        $workflowCounts = [
            // Tahap 1: Usulan masuk (draft + diajukan)
            'usulan' => (clone $usulanQuery)
                ->whereIn('status', ['draft', 'diajukan'])
                ->count(),

            // Tahap 2: Menunggu approval PPTK
            'approval' => (clone $usulanQuery)
                ->where('status', 'diajukan')
                ->count(),

            // Tahap 3: Pengadaan sedang diproses (negosiasi/proses)
            'pengadaan' => (clone $usulanQuery)
                            ->where('status', 'disetujui')
                            ->count()
                        + Pengadaan::query()
                            ->where('status', 'proses')
                            ->whereYear('created_at', $tahunAnggaran)
                            ->count(),

            // Tahap 4: Dokumen UPBJ — kontrak sudah, dokumen belum selesai
            'dokumen' => Pengadaan::query()
                ->where('status', 'kontrak')
                ->where(function ($q) {
                    $q->doesntHave('dokumenUpbj')
                    ->orWhereHas('dokumenUpbj', fn ($d) => $d->where('is_complete', false));
                })
                ->whereHas('usulan.anggaran', $anggaranScope)
                ->count(),

            // Tahap 5: Pembayaran — dokumen selesai, belum lunas
            'pembayaran' => Pengadaan::query()
                ->whereHas('dokumenUpbj', fn ($q) => $q->where('is_complete', true))
                ->where(function ($q) {
                    $q->doesntHave('pembayaran')
                    ->orWhereHas('pembayaran', fn ($pb) =>
                        $pb->whereNotIn('status', ['lunas', 'batal'])
                    );
                })
                ->whereHas('usulan.anggaran', $anggaranScope)
                ->count(),

            // Card 6: Selesai = pembayaran sudah lunas
            'evaluasi' => Pengadaan::query()
                ->whereHas('pembayaran', fn ($q) => $q->where('status', 'lunas'))
                ->whereHas('usulan.anggaran', $anggaranScope)
                ->count(),
        ];

        // ── Realisasi per bulan ──────────────────────────────────────────
        $namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        $realisasiBulanan = collect(range(1, 12))->map(function ($bulan) use (
            $tahunAnggaran, $pembayaranQuery, $namaBulan
        ) {
            // Realisasi: pembayaran lunas — fallback ke created_at jika tanggal_bayar null
            $realisasiFormal = (clone $pembayaranQuery)
                ->where('status', 'lunas')
                ->whereRaw('YEAR(COALESCE(tanggal_bayar, created_at)) = ?', [$tahunAnggaran])
                ->whereRaw('MONTH(COALESCE(tanggal_bayar, created_at)) = ?', [$bulan])
                ->sum('nilai_bayar');

            $realisasiLangsung = \App\Models\BelanjaLangsung::query()
                ->where('status', 'dibayar')
                ->where('tahun_anggaran', $tahunAnggaran)
                ->whereRaw('MONTH(COALESCE(tanggal_dibayar, created_at)) = ?', [$bulan])
                ->sum('nominal');

            $nilaiRealisasi = $realisasiFormal + $realisasiLangsung;

            // Komitmen: paket yang kontraknya ditandatangani bulan ini
            $nilaiKomitmen = \App\Models\Pengadaan::query()
                ->whereNotNull('tanggal_kontrak')
                ->whereDoesntHave('pembayaran', fn ($q) => $q->where('status', 'lunas')) // ← exclude yg sudah lunas
                ->whereRaw('YEAR(tanggal_kontrak) = ?', [$tahunAnggaran])
                ->whereRaw('MONTH(tanggal_kontrak) = ?', [$bulan])
                ->sum('nilai_kontrak');

            return [
                'bulan'     => $bulan,
                'label'     => $namaBulan[$bulan - 1],
                'realisasi' => (float) $nilaiRealisasi,
                'komitmen'  => (float) $nilaiKomitmen,
            ];
        })->values()->toArray();

        return Inertia::render('Dashboard', [
            'stats'              => $stats,
            'recentUsulan'       => $recentUsulan,
            'statusDistribution' => $statusDistribution,
            'workflowCounts'     => $workflowCounts,
            'activityFeed'       => $activityFeed,        // ← PERBAIKAN: pakai variabel, bukan []
            'metodeDistribution' => $metodeDistribution,  // ← PERBAIKAN: pakai variabel, bukan []
            'roleData'           => ['role' => $user->role?->name ?? ''],
            'userRole'           => $user->role?->name ?? '',
            'tahunAnggaranAktif' => $tahunAnggaran,
            'realisasiBulanan'   => $realisasiBulanan,
        ]);
    }
}