<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKategoriBarangRequest;
use App\Models\KategoriBarang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KategoriBarangController extends Controller
{
    public function index(Request $request): Response
    {
        $query = KategoriBarang::query()->orderBy('kode');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $kategori = $query->paginate(20)->withQueryString();

        return Inertia::render('kategori/Index', [
            'kategori' => $kategori,
            'filters'  => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('kategori/Create');
    }

    public function store(StoreKategoriBarangRequest $request): RedirectResponse
    {
        $kategori = KategoriBarang::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('kategori.index')
            ->with('success', "Kategori {$kategori->nama} berhasil ditambahkan.");
    }

    public function edit(KategoriBarang $kategori): Response
    {
        return Inertia::render('kategori/Edit', [
            'kategori' => $kategori,
        ]);
    }

    public function update(StoreKategoriBarangRequest $request, KategoriBarang $kategori): RedirectResponse
    {
        $kategori->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('kategori.index')
            ->with('success', "Kategori {$kategori->nama} berhasil diupdate.");
    }

    public function destroy(KategoriBarang $kategori): RedirectResponse
    {
        // Cek apakah kategori sudah dipakai di usulan_items
        $itemCount = $kategori->usulanItems()->count();

        if ($itemCount > 0) {
            return back()->with('error', "Kategori ini sudah digunakan di {$itemCount} item usulan, tidak bisa dihapus.");
        }

        $nama = $kategori->nama;
        $kategori->delete();

        return redirect()
            ->route('kategori.index')
            ->with('success', "Kategori {$nama} berhasil dihapus.");
    }
}