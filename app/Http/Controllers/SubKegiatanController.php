<?php

namespace App\Http\Controllers;

use App\Models\DpaAnggaran;
use App\Models\SubKegiatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubKegiatanController extends Controller
{
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
        $query = SubKegiatan::query()
            ->with('dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa')
            ->where(function ($q) use ($tahunAnggaran) {
                $q->where('tahun_anggaran', $tahunAnggaran)
                    ->orWhereHas('dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->orderByDesc('tahun_anggaran')
            ->orderBy('nama_kegiatan');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_sub_kegiatan', 'like', "%{$search}%")
                    ->orWhere('nama_kegiatan', 'like', "%{$search}%")
                    ->orWhereHas('dpaAnggaran', function ($dpa) use ($search) {
                        $dpa->where('no_dpa', 'like', "%{$search}%")
                            ->orWhere('nama_dpa', 'like', "%{$search}%");
                    });
            });
        }

        if ($tahun = $request->input('tahun')) {
            $query->where('tahun_anggaran', $tahun);
        }

        if ($dpaId = $request->input('dpa_anggaran_id')) {
            $query->where('dpa_anggaran_id', $dpaId);
        }

        $subKegiatan = $query->paginate(20)->withQueryString();

        $tahunOptions = SubKegiatan::query()
            ->select('tahun_anggaran')
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran');

        $dpaOptions = DpaAnggaran::query()
            ->where('is_active', true)
            ->orderByDesc('tahun_anggaran')
            ->orderBy('no_dpa')
            ->get(['id', 'tahun_anggaran', 'no_dpa', 'tanggal_dpa', 'nama_dpa']);

        return Inertia::render('sub-kegiatan/Index', [
            'subKegiatan' => $subKegiatan,
            'tahunOptions' => $tahunOptions,
            'dpaOptions' => $dpaOptions,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'tahun' => $tahun,
                'dpa_anggaran_id' => $dpaId,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        $dpaOptions = DpaAnggaran::query()
            ->where('is_active', true)
            ->where('tahun_anggaran', $tahunAnggaran)
            ->orderByDesc('tahun_anggaran')
            ->orderBy('no_dpa')
            ->get([
                'id',
                'tahun_anggaran',
                'no_dpa',
                'tanggal_dpa',
                'nama_dpa',
                'is_active',
            ]);

        return Inertia::render('sub-kegiatan/Create', [
            'dpaOptions' => $dpaOptions,
            'tahunAnggaran' => $tahunAnggaran,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dpa_anggaran_id' => ['nullable', 'exists:dpa_anggaran,id'],
            'kode_sub_kegiatan' => ['nullable', 'string', 'max:255'],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2100'],
            'is_active' => ['boolean'],
        ]);

        $subKegiatan = SubKegiatan::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('sub-kegiatan.index')
            ->with('success', "Sub kegiatan {$subKegiatan->nama_kegiatan} berhasil ditambahkan.");
    }

    public function edit(Request $request, SubKegiatan $subKegiatan): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
        
        $subKegiatan->load('dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa');

        $dpaOptions = DpaAnggaran::query()
            ->where(function ($query) use ($subKegiatan) {
                $query->where('is_active', true);

                if ($subKegiatan->dpa_anggaran_id) {
                    $query->orWhere('id', $subKegiatan->dpa_anggaran_id);
                }
            })
            ->orderByDesc('tahun_anggaran')
            ->orderBy('no_dpa')
            ->get(['id', 'tahun_anggaran', 'no_dpa', 'tanggal_dpa', 'nama_dpa']);

        return Inertia::render('sub-kegiatan/Edit', [
            'subKegiatan' => $subKegiatan,
            'dpaOptions' => $dpaOptions,
        ]);
    }

    public function update(Request $request, SubKegiatan $subKegiatan): RedirectResponse
    {
        $validated = $request->validate([
            'dpa_anggaran_id' => ['nullable', 'exists:dpa_anggaran,id'],
            'kode_sub_kegiatan' => ['nullable', 'string', 'max:255'],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2100'],
            'is_active' => ['boolean'],
        ]);

        $subKegiatan->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('sub-kegiatan.index')
            ->with('success', "Sub kegiatan {$subKegiatan->nama_kegiatan} berhasil diperbarui.");
    }

    public function destroy(SubKegiatan $subKegiatan): RedirectResponse
    {
        $anggaranCount = $subKegiatan->anggaran()->count();

        if ($anggaranCount > 0) {
            return back()->with('error', "Sub kegiatan ini sudah digunakan oleh {$anggaranCount} anggaran, tidak bisa dihapus.");
        }

        $nama = $subKegiatan->nama_kegiatan;
        $subKegiatan->delete();

        return redirect()
            ->route('sub-kegiatan.index')
            ->with('success', "Sub kegiatan {$nama} berhasil dihapus.");
    }
}