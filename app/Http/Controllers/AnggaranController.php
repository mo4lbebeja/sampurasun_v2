<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnggaranRequest;
use App\Models\Anggaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\SubKegiatan;

class AnggaranController extends Controller
{
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        $subKegiatanOptions = SubKegiatan::query()
            ->with('dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa')
            ->where('is_active', true)
            ->where('tahun_anggaran', $tahunAnggaran)
            ->orderBy('kode_sub_kegiatan')
            ->get([
                'id',
                'dpa_anggaran_id',
                'kode_sub_kegiatan',
                'nama_kegiatan',
                'tahun_anggaran',
            ]);

        $selectedSubKegiatanId = $request->integer('sub_kegiatan_id');

        if (! $selectedSubKegiatanId && $subKegiatanOptions->isNotEmpty()) {
            $selectedSubKegiatanId = (int) $subKegiatanOptions->first()->id;
        }

        $selectedSubKegiatan = $subKegiatanOptions
            ->firstWhere('id', $selectedSubKegiatanId);

        $query = Anggaran::query()
            ->with('subKegiatan.dpaAnggaran')
            ->where('tahun', $tahunAnggaran)
            ->when($selectedSubKegiatanId, function ($query) use ($selectedSubKegiatanId) {
                $query->where('sub_kegiatan_id', $selectedSubKegiatanId);
            })
            ->orderBy('kode_rekening');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_rekening', 'like', "%{$search}%")
                    ->orWhere('nama_rekening', 'like', "%{$search}%")
                    ->orWhere('uraian', 'like', "%{$search}%");
            });
        }

        $anggaran = $query
            ->paginate(20)
            ->through(function (Anggaran $anggaran) {
                $pagu = (float) $anggaran->pagu;
                $terpakai = (float) $anggaran->terpakai;
                $sisa = max($pagu - $terpakai, 0);

                return [
                    'id' => $anggaran->id,
                    'tahun' => $anggaran->tahun,
                    'sub_kegiatan_id' => $anggaran->sub_kegiatan_id,
                    'kode_rekening' => $anggaran->kode_rekening,
                    'nama_rekening' => $anggaran->nama_rekening,
                    'uraian' => $anggaran->uraian,
                    'pagu' => $pagu,
                    'terpakai' => $terpakai,
                    'sisa' => $sisa,
                    'is_active' => (bool) $anggaran->is_active,
                ];
            })
            ->withQueryString();

        $statsQuery = Anggaran::query()
            ->where('tahun', $tahunAnggaran)
            ->when($selectedSubKegiatanId, function ($query) use ($selectedSubKegiatanId) {
                $query->where('sub_kegiatan_id', $selectedSubKegiatanId);
            });

        $totalPagu = (float) $statsQuery->sum('pagu');

        $totalTerpakai = (float) Anggaran::query()
            ->where('tahun', $tahunAnggaran)
            ->when($selectedSubKegiatanId, function ($query) use ($selectedSubKegiatanId) {
                $query->where('sub_kegiatan_id', $selectedSubKegiatanId);
            })
            ->sum('terpakai');

        $stats = [
            'total_pagu' => $totalPagu,
            'total_terpakai' => $totalTerpakai,
            'total_sisa' => max($totalPagu - $totalTerpakai, 0),
            'total_rekening' => $anggaran->total(),
        ];

        return Inertia::render('anggaran/Index', [
            'anggaran' => $anggaran,
            'subKegiatanOptions' => $subKegiatanOptions,
            'selectedSubKegiatanId' => $selectedSubKegiatanId,
            'selectedSubKegiatan' => $selectedSubKegiatan,
            'stats' => $stats,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'sub_kegiatan_id' => $selectedSubKegiatanId,
            ],
            'tahunAnggaran' => $tahunAnggaran,
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
        $subKegiatanId = $request->integer('sub_kegiatan_id');

        if (! $subKegiatanId) {
            return redirect()
                ->route('anggaran.index')
                ->with('error', 'Pilih sub kegiatan terlebih dahulu sebelum menambah pos anggaran.');
        }

        $selectedSubKegiatan = SubKegiatan::query()
            ->with('dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa')
            ->where('id', $subKegiatanId)
            ->where('is_active', true)
            ->where('tahun_anggaran', $tahunAnggaran)
            ->first();

        if (! $selectedSubKegiatan) {
            return redirect()
                ->route('anggaran.index')
                ->with('error', 'Sub kegiatan tidak valid atau tidak sesuai tahun anggaran login.');
        }

        $summary = [
            'total_pagu' => (float) Anggaran::query()
                ->where('tahun', $tahunAnggaran)
                ->where('sub_kegiatan_id', $selectedSubKegiatan->id)
                ->sum('pagu'),

            'total_terpakai' => (float) Anggaran::query()
                ->where('tahun', $tahunAnggaran)
                ->where('sub_kegiatan_id', $selectedSubKegiatan->id)
                ->sum('terpakai'),
        ];

        $summary['total_sisa'] = max(
            $summary['total_pagu'] - $summary['total_terpakai'],
            0
        );

        return Inertia::render('anggaran/Create', [
            'selectedSubKegiatan' => $selectedSubKegiatan,
            'tahunAnggaran' => $tahunAnggaran,
            'defaultStatusAktif' => true,
            'summary' => $summary,
        ]);
    }

    public function store(StoreAnggaranRequest $request): RedirectResponse
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
        $validated = $request->validated();

        $anggaran = Anggaran::create([
            ...$validated,
            'tahun' => $tahunAnggaran,
            'sub_kegiatan_id' => $validated['sub_kegiatan_id'],
            'is_active' => true,
            'terpakai' => (float) ($validated['terpakai'] ?? 0),
        ]);

        if ($request->input('submit_action') === 'save_add_more') {
            return redirect()
                ->route('anggaran.create', [
                    'sub_kegiatan_id' => $anggaran->sub_kegiatan_id,
                ])
                ->with('success', "Anggaran {$anggaran->kode_rekening} berhasil ditambahkan. Silakan tambah kode rekening berikutnya.");
        }

        return redirect()
            ->route('anggaran.index', [
                'sub_kegiatan_id' => $anggaran->sub_kegiatan_id,
            ])
            ->with('success', "Anggaran {$anggaran->kode_rekening} berhasil ditambahkan.");
    }

    public function edit(Request $request, Anggaran $anggaran): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        $anggaran->load('subKegiatan.dpaAnggaran');

        if ((int) $anggaran->tahun !== $tahunAnggaran) {
            abort(404, 'Anggaran tidak sesuai tahun anggaran aktif.');
        }

        $selectedSubKegiatan = $anggaran->subKegiatan;

        abort_if(! $selectedSubKegiatan, 404, 'Sub kegiatan tidak ditemukan.');

        $totalPagu = (float) Anggaran::query()
            ->where('tahun', $tahunAnggaran)
            ->where('sub_kegiatan_id', $selectedSubKegiatan->id)
            ->sum('pagu');

        $totalTerpakai = (float) Anggaran::query()
            ->where('tahun', $tahunAnggaran)
            ->where('sub_kegiatan_id', $selectedSubKegiatan->id)
            ->sum('terpakai');

        $summary = [
            'total_pagu' => $totalPagu,
            'total_terpakai' => $totalTerpakai,
            'total_sisa' => max($totalPagu - $totalTerpakai, 0),
        ];

        return Inertia::render('anggaran/Edit', [
            'anggaran' => [
                'id' => $anggaran->id,
                'sub_kegiatan_id' => $anggaran->sub_kegiatan_id,
                'tahun' => $anggaran->tahun,
                'kode_rekening' => $anggaran->kode_rekening,
                'nama_rekening' => $anggaran->nama_rekening,
                'uraian' => $anggaran->uraian,
                'pagu' => $anggaran->pagu,
                'terpakai' => $anggaran->terpakai,
                'sisa' => $anggaran->sisa,
                'is_active' => (bool) $anggaran->is_active,
            ],
            'selectedSubKegiatan' => $selectedSubKegiatan,
            'tahunAnggaran' => $tahunAnggaran,
            'summary' => $summary,
        ]);
}

    public function update(StoreAnggaranRequest $request, Anggaran $anggaran): RedirectResponse
    {
        $anggaran->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
            'terpakai'  => (float) ($request->input('terpakai') ?? $anggaran->terpakai),
        ]);

        return redirect()
            ->route('anggaran.index')
            ->with('success', "Anggaran {$anggaran->kode_rekening} berhasil diupdate.");
    }

    public function destroy(Anggaran $anggaran): RedirectResponse
    {
        // Cek apakah anggaran sudah dipakai di usulan
        $usulanCount = $anggaran->usulanPengadaan()->count();

        if ($usulanCount > 0) {
            return back()->with('error', "Anggaran ini sudah digunakan di {$usulanCount} usulan, tidak bisa dihapus.");
        }

        $kode = $anggaran->kode_rekening;
        $anggaran->delete();

        return redirect()
            ->route('anggaran.index')
            ->with('success', "Anggaran {$kode} berhasil dihapus.");
    }
}