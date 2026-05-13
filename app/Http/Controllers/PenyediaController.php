<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePenyediaRequest;
use App\Models\Penyedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PenyediaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Penyedia::query()->latest('id');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('npwp', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($jenisBadan = $request->string('jenis_badan')->toString()) {
            $query->where('jenis_badan', $jenisBadan);
        }

        $penyedia = $query->paginate(15)->withQueryString();

        return Inertia::render('penyedia/Index', [
            'penyedia' => $penyedia,
            'filters'  => [
                'search'      => $request->string('search')->toString(),
                'jenis_badan' => $request->string('jenis_badan')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('penyedia/Create');
    }

    public function store(StorePenyediaRequest $request): RedirectResponse
    {
        $penyedia = Penyedia::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('penyedia.index')
            ->with('success', "Penyedia {$penyedia->nama} berhasil ditambahkan.");
    }

    public function show(Penyedia $penyedia): Response
    {
        return Inertia::render('penyedia/Show', [
            'penyedia' => $penyedia,
        ]);
    }

    public function edit(Penyedia $penyedia): Response
    {
        return Inertia::render('penyedia/Edit', [
            'penyedia' => $penyedia,
        ]);
    }

    public function update(StorePenyediaRequest $request, Penyedia $penyedia): RedirectResponse
    {
        $penyedia->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('penyedia.index')
            ->with('success', "Penyedia {$penyedia->nama} berhasil diupdate.");
    }

    public function destroy(Penyedia $penyedia): RedirectResponse
    {
        // Soft cek: jangan hapus kalau sudah ada relasi pengadaan
        if ($penyedia->pengadaan()->exists()) {
            return back()->with('error', 'Penyedia ini sudah pernah dipakai dalam pengadaan, tidak bisa dihapus.');
        }

        $nama = $penyedia->nama;
        $penyedia->delete();

        return redirect()
            ->route('penyedia.index')
            ->with('success', "Penyedia {$nama} berhasil dihapus.");
    }
}