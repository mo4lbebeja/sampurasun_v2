<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\Pembayaran;
use App\Models\Pengadaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RealisasiAnggaranController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $allowedRoles = ['admin', 'perencanaan', 'pptk', 'pejabat_pengadaan', 'keuangan'];
        $userRoleName = $user->isAdmin() ? 'admin' : ($user->role?->name ?? '');
        if (! in_array($userRoleName, $allowedRoles)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }
 
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
        // ── Subquery komitmen (sama persis dengan sebelumnya) ─────────
        $komitmenSub = DB::table('pengadaan as p')
            ->join('usulan_pengadaan as u', 'u.id', '=', 'p.usulan_id')
            ->where('p.status', 'kontrak')
            ->whereNotExists(function ($sub) {
                $sub->from('pembayaran as pb')
                    ->whereColumn('pb.pengadaan_id', 'p.id')
                    ->where('pb.status', 'lunas');
            })
            ->whereNull('u.deleted_at')
            ->select('u.anggaran_id', DB::raw('SUM(p.nilai_kontrak) as total_komitmen'))
            ->groupBy('u.anggaran_id');
 
        // ── Subquery realisasi formal: pembayaran lunas ───────────────
        $realisasiSub = DB::table('pembayaran as pb')
            ->join('pengadaan as p', 'p.id', '=', 'pb.pengadaan_id')
            ->join('usulan_pengadaan as u', 'u.id', '=', 'p.usulan_id')
            ->where('pb.status', 'lunas')
            ->whereNull('u.deleted_at')
            ->select('u.anggaran_id', DB::raw('SUM(pb.nilai_bayar) as total_realisasi'))
            ->groupBy('u.anggaran_id');
 
        // ── Subquery realisasi langsung: belanja langsung dibayar ─────
 
        // ── Subquery jumlah pengadaan (sama persis) ───────────────────
        $jumlahPengadaanSub = DB::table('pengadaan as p')
            ->join('usulan_pengadaan as u', 'u.id', '=', 'p.usulan_id')
            ->whereIn('p.status', ['kontrak', 'selesai'])
            ->whereNull('u.deleted_at')
            ->select('u.anggaran_id', DB::raw('COUNT(p.id) as jumlah_pengadaan'))
            ->groupBy('u.anggaran_id');
 
        // ── Base query — tambahkan leftJoinSub untuk belanja langsung ─
        $query = Anggaran::query()
            ->with([
                'subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            ])
            ->leftJoinSub($komitmenSub,            'k',  fn ($j) => $j->on('anggaran.id', '=', 'k.anggaran_id'))
            ->leftJoinSub($realisasiSub,           'r',  fn ($j) => $j->on('anggaran.id', '=', 'r.anggaran_id'))
            ->leftJoinSub($jumlahPengadaanSub,     'jp', fn ($j) => $j->on('anggaran.id', '=', 'jp.anggaran_id'))
            ->addSelect([
                'anggaran.*',
                DB::raw('COALESCE(k.total_komitmen, 0) as komitmen'),
                // Realisasi = formal (pembayaran) + langsung (belanja langsung)
                DB::raw('COALESCE(r.total_realisasi, 0) as realisasi'), // ← DIUPDATE
                DB::raw('COALESCE(jp.jumlah_pengadaan, 0) as jumlah_pengadaan'),
            ])
            ->where(function ($q) use ($tahunAnggaran) {
                $q->where('anggaran.tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->orderBy('anggaran.tahun', 'desc')
            ->orderBy('anggaran.kode_rekening');
 
        // Filter tambahan (sama persis dengan sebelumnya)
        if ($tahun = $request->input('tahun')) {
            $query->where('anggaran.tahun', $tahun);
        }
 
        if ($status = $request->input('status')) {
            if ($status === 'aktif') {
                $query->where('anggaran.is_active', true);
            } elseif ($status === 'nonaktif') {
                $query->where('anggaran.is_active', false);
            }
        }
 
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('anggaran.kode_rekening', 'like', "%{$search}%")
                  ->orWhere('anggaran.nama_rekening', 'like', "%{$search}%")
                  ->orWhere('anggaran.uraian', 'like', "%{$search}%");
            });
        }
 
        $anggaranList = $query->paginate(20)->withQueryString();

        // Fetch belanja langsung per anggaran
        $belanjaLangsungTotal = \App\Models\BelanjaLangsung::query()
            ->where('status', 'dibayar')
            ->whereIn('anggaran_id', $anggaranList->pluck('id'))
            ->selectRaw('anggaran_id, SUM(nominal) as total')
            ->groupBy('anggaran_id')
            ->pluck('total', 'anggaran_id');

        // Tambahkan ke setiap row dengan setAttribute agar terbaca saat serialisasi
        $anggaranList->getCollection()->each(function ($row) use ($belanjaLangsungTotal) {
            $tambahan = (float) ($belanjaLangsungTotal[$row->id] ?? 0);
            $row->setAttribute('realisasi',
                (float) $row->getAttribute('realisasi') + $tambahan
            );
        });
        
        // Stats keseluruhan (sama persis dengan sebelumnya)
        $statsQuery = clone $query;
        $statsQuery->getQuery()->orders = [];
        $statsQuery->getQuery()->limit  = null;
        $statsQuery->getQuery()->offset = null;
 
        $statsRow = DB::table(DB::raw("({$statsQuery->toBase()->toSql()}) as agg"))
            ->mergeBindings($statsQuery->toBase())
            ->selectRaw('
                COALESCE(SUM(pagu), 0)      as total_pagu,
                COALESCE(SUM(komitmen), 0)  as total_komitmen,
                COALESCE(SUM(realisasi), 0) as total_realisasi
            ')
            ->first();
 
        $totalPagu      = (float) ($statsRow->total_pagu ?? 0);
        $totalKomitmen  = (float) ($statsRow->total_komitmen ?? 0);
        $totalRealisasi = (float) ($statsRow->total_realisasi ?? 0);

        // Tambahkan belanja langsung ke total realisasi
        $totalBelanjaLangsung = \App\Models\BelanjaLangsung::query()
            ->where('status', 'dibayar')
            ->where('tahun_anggaran', $tahunAnggaran)  // ← pakai tahun, bukan pluck id
            ->sum('nominal');

        $totalRealisasi += (float) $totalBelanjaLangsung;
        $totalTerpakai   = $totalKomitmen + $totalRealisasi;  // ← tetap ada, tapi setelah update
 
        $stats = [
            'total_pagu'       => $totalPagu,
            'total_komitmen'   => $totalKomitmen,
            'total_realisasi'  => $totalRealisasi,
            'total_terpakai'   => $totalTerpakai,
            'total_sisa'       => $totalPagu - $totalTerpakai,
            'persen_terpakai'  => $totalPagu > 0
                ? round(($totalTerpakai / $totalPagu) * 100, 1) : 0,
            'persen_realisasi' => $totalPagu > 0
                ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0,
        ];
 
        return Inertia::render('realisasi/Index', [
            'anggaran'     => $anggaranList,
            'stats'        => $stats,
            'tahunOptions' => collect([$tahunAnggaran]),
            'filters'      => [
                'search' => $request->string('search')->toString(),
                'tahun'  => $request->input('tahun') ?? null,
                'status' => $request->input('status') ?? null,
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

            // TAMBAHKAN ini setelah $pengadaanList:
            $belanjaLangsungList = \App\Models\BelanjaLangsung::query()
                ->with(['pembelanja:id,name'])
                ->where('anggaran_id', $anggaran->id)
                ->where('status', 'dibayar')
                ->orderByDesc('tanggal_dibayar')
                ->get()
                ->map(fn ($bl) => [
                    'id'              => $bl->id,
                    'uraian'          => $bl->uraian,
                    'jenis'           => $bl->jenis,
                    'jenis_label'     => \App\Models\BelanjaLangsung::$jenisLabel[$bl->jenis] ?? $bl->jenis,
                    'nominal'         => $bl->nominal,
                    'tanggal_dibayar' => $bl->tanggal_dibayar,
                    'pembelanja'      => $bl->pembelanja?->name,
                    'no_nota'         => $bl->no_nota,
                ]);

            // GANTI return:
            return response()->json([
                'anggaran'         => $anggaran,
                'pengadaan'        => $pengadaanList,
                'belanja_langsung' => $belanjaLangsungList,  // ← TAMBAHKAN
        ]);
    }
}