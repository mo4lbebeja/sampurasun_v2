<?php

namespace App\Http\Controllers;

use App\Models\DpaAnggaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DpaAnggaranController extends Controller
{
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        $query = DpaAnggaran::query()
            ->where('tahun_anggaran', $tahunAnggaran);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('no_dpa', 'like', "%{$search}%")
                    ->orWhere('nama_dpa', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if ($tahun = $request->input('tahun')) {
            $query->where('tahun_anggaran', $tahun);
        }

        $dpa = $query->paginate(20)->withQueryString();

        $tahunOptions = DpaAnggaran::query()
            ->select('tahun_anggaran')
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran');

        return Inertia::render('dpa-anggaran/Index', [
            'dpa' => $dpa,
            'tahunOptions' => $tahunOptions,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'tahun' => $tahun,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dpa-anggaran/Create', [
            'currentYear' => now()->year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2100'],
            'no_dpa' => ['required', 'string', 'max:255'],
            'tanggal_dpa' => ['nullable', 'date'],
            'nama_dpa' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $dpa = DpaAnggaran::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('dpa-anggaran.index')
            ->with('success', "DPA {$dpa->no_dpa} berhasil ditambahkan.");
    }

    public function edit(DpaAnggaran $dpaAnggaran): Response
    {
        return Inertia::render('dpa-anggaran/Edit', [
            'dpa' => $dpaAnggaran,
        ]);
    }

    public function update(Request $request, DpaAnggaran $dpaAnggaran): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_anggaran' => ['required', 'integer', 'min:2020', 'max:2100'],
            'no_dpa' => ['required', 'string', 'max:255'],
            'tanggal_dpa' => ['nullable', 'date'],
            'nama_dpa' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $dpaAnggaran->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('dpa-anggaran.index')
            ->with('success', "DPA {$dpaAnggaran->no_dpa} berhasil diperbarui.");
    }

    public function destroy(DpaAnggaran $dpaAnggaran): RedirectResponse
    {
        $subKegiatanCount = $dpaAnggaran->subKegiatan()->count();

        if ($subKegiatanCount > 0) {
            return back()->with('error', "DPA ini sudah digunakan oleh {$subKegiatanCount} sub kegiatan, tidak bisa dihapus.");
        }

        $noDpa = $dpaAnggaran->no_dpa;
        $dpaAnggaran->delete();

        return redirect()
            ->route('dpa-anggaran.index')
            ->with('success', "DPA {$noDpa} berhasil dihapus.");
    }
}