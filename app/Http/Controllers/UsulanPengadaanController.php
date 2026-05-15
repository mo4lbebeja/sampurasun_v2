<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsulanRequest;
use App\Models\Anggaran;
use App\Models\KategoriBarang;
use App\Models\UsulanPengadaan;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\UsulanBaruNotification;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\UpdateUsulanRequest;
//use App\Notifications\UsulanDisetujuiNotification;

class UsulanPengadaanController extends Controller
{
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        $query = UsulanPengadaan::query()
            ->with([
                'pemohon:id,name,unit_kerja_id',
                'pemohon.unitKerja:id,nama',
                'anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            ])
            ->whereHas('anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->latest('tanggal_usulan');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('no_usulan', 'like', "%{$search}%");
            });
        }

        // Non-admin & non-PPTK: hanya lihat usulan dari unit kerja sendiri
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->hasRole('pptk')) {
            $query->whereHas('pemohon', function ($q) use ($user) {
                $q->where('unit_kerja_id', $user->unit_kerja_id);
            });
        }

        $usulan = $query->paginate(15)->withQueryString();

        return Inertia::render('usulan/Index', [
            'usulan' => $usulan,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        return Inertia::render('usulan/Create', [
            'kategoriList' => KategoriBarang::query()
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama']),

            'anggaranList' => Anggaran::query()
                ->with([
                    'subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                    'subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                ])
                ->where('is_active', true)
                ->where(function ($q) use ($tahunAnggaran) {
                    $q->where('tahun', $tahunAnggaran)
                        ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                            $dpa->where('tahun_anggaran', $tahunAnggaran);
                        });
                })
                ->orderBy('kode_rekening')
                ->get(['id', 'sub_kegiatan_id', 'tahun', 'kode_rekening', 'nama_rekening', 'uraian', 'pagu', 'terpakai', 'sisa']),
        ]);
    }

    public function store(StoreUsulanRequest $request, DocumentNumberService $numberService): RedirectResponse
    {
        $usulan = DB::transaction(function () use ($request, $numberService) {
            $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
            $noUsulan = $numberService->generateInsideTransaction(
                type: 'usulan',
                prefix: 'USL',
                tahunAnggaran: $tahunAnggaran,
            );
 
            $totalEstimasi = collect($request->validated('items'))
                ->sum(fn ($i) => $i['jumlah'] * $i['harga_satuan_estimasi']);
 
            $anggaran = Anggaran::query()
                ->whereKey($request->validated('anggaran_id'))
                ->lockForUpdate()
                ->firstOrFail();
 
            if ($totalEstimasi > (float) $anggaran->sisa) {
                return back()
                    ->withErrors([
                        'items' => sprintf(
                            'Total estimasi Rp %s melebihi sisa anggaran Rp %s.',
                            number_format($totalEstimasi, 0, ',', '.'),
                            number_format((float) $anggaran->sisa, 0, ',', '.'),
                        ),
                    ])
                    ->withInput();
            }
 
            $usulan = UsulanPengadaan::create([
                'no_usulan'    => $noUsulan,
                'pemohon_id'   => $request->user()->id,
                'anggaran_id'  => $request->validated('anggaran_id'),
                'tanggal_usulan' => now(),
                'judul'        => $request->validated('judul'),
                'latar_belakang' => $request->validated('latar_belakang'),
                'keterangan'   => $request->validated('keterangan'),
                'total_estimasi' => $totalEstimasi,
                'status'       => 'diajukan',
                'submitted_at' => now(),
            ]);
 
            foreach ($request->validated('items') as $item) {
                $usulan->items()->create([
                    'kategori_id'          => $item['kategori_id'],
                    'nama_barang'          => $item['nama_barang'],
                    'spesifikasi'          => $item['spesifikasi'] ?? null,
                    'satuan'               => $item['satuan'],
                    'jumlah'               => $item['jumlah'],
                    'harga_satuan_estimasi' => $item['harga_satuan_estimasi'],
                    'subtotal'             => $item['jumlah'] * $item['harga_satuan_estimasi'],
                ]);
            }
 
            return $usulan;
        }, 5);
 
        // ── ActivityLogger ───────────────────────────────────────────
        ActivityLogger::fromRequest(
            request:     $request,
            action:      'usulan.submit',
            description: "Usulan {$usulan->no_usulan} diajukan: {$usulan->judul}",
            usulanId:    $usulan->id,
            subjectType: 'UsulanPengadaan',
            subjectId:   $usulan->id,
        );
 
        // ── Notifikasi ke semua PPTK ─────────────────────────────────
        $pptk = \App\Models\User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'pptk'))
            ->where('is_active', true)
            ->get();
 
        Notification::send($pptk, new UsulanBaruNotification($usulan));
        // ─────────────────────────────────────────────────────────────
 
        return redirect()
            ->route('usulan.index')
            ->with('success', "Usulan {$usulan->no_usulan} berhasil diajukan.");
    }

    public function show(UsulanPengadaan $usulan): Response
    {
        $usulan->load([
            'pemohon:id,name,jabatan,unit_kerja_id',
            'pemohon.unitKerja:id,nama',
            'anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening,pagu',
            'anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
            'anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            'items.kategori:id,nama',
            'approvals.approver:id,name,jabatan',
            // Semua paket pengadaan dari usulan ini
            'pengadaan:id,usulan_id,no_pengadaan,nama_paket,status,metode,estimasi_paket,nilai_kontrak',
        ]);
 
        $tahunAnggaran = (int) request()->session()->get('tahun_anggaran');
 
        $sesuaiTahun = (int) $usulan->anggaran?->tahun === $tahunAnggaran
            || (int) $usulan->anggaran?->subKegiatan?->dpaAnggaran?->tahun_anggaran === $tahunAnggaran;
 
        abort_unless($sesuaiTahun, 403);
 
        // ID item yang sudah di-assign ke paket manapun yang tidak batal
        $itemsAssignedIds = \App\Models\PengadaanItemAssignment::query()
            ->whereHas('pengadaan', fn ($q) =>
                $q->where('usulan_id', $usulan->id)
                  ->where('status', '!=', 'batal')
            )
            ->pluck('usulan_item_id')
            ->toArray();
 
        return Inertia::render('usulan/Show', [
            'usulan'           => $usulan,
            'pengadaanList'    => $usulan->pengadaan,
            'itemsAssignedIds' => $itemsAssignedIds,
        ]);
    }

    public function edit(Request $request, UsulanPengadaan $usulan): Response|RedirectResponse
    {
        // Hanya status draft yang boleh diedit
        if ($usulan->status !== 'draft') {
            return redirect()
                ->route('usulan.show', $usulan)
                ->with('error', 'Usulan ini tidak dapat diedit pada status saat ini.');
        }
 
        // Hanya pemohon asli atau admin
        if (! $request->user()->isAdmin()
            && $usulan->pemohon_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit usulan ini.');
        }
 
        // Load relasi yang diperlukan — termasuk items.kategori agar form pre-filled
        $usulan->load([
            'items.kategori:id,nama,kode',
            'anggaran:id,kode_rekening,nama_rekening,pagu,sisa,tahun',
            'approvals' => fn ($q) => $q->where('keputusan', 'revisi')->latest()->limit(1),
        ]);
 
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
        // Daftar anggaran aktif — PASTIKAN anggaran yang dipilih sebelumnya selalu masuk
        $anggaranList = Anggaran::query()
            ->where('is_active', true)
            ->where(function ($q) use ($tahunAnggaran, $usulan) {
                $q->where('tahun', $tahunAnggaran)
                  ->orWhereHas('subKegiatan.dpaAnggaran', fn ($dpa) =>
                      $dpa->where('tahun_anggaran', $tahunAnggaran)
                  )
                  // Selalu sertakan anggaran yang sebelumnya dipilih
                  // agar dropdown tidak kosong saat dibuka
                  ->orWhere('id', $usulan->anggaran_id);
            })
            ->select('id', 'kode_rekening', 'nama_rekening', 'pagu', 'sisa', 'tahun')
            ->orderBy('kode_rekening')
            ->get();
 
        $kategoriList = KategoriBarang::query()
            ->where('is_active', true)
            ->select('id', 'kode', 'nama')
            ->orderBy('kode')
            ->get();
 
        // Catatan revisi terakhir dari PPTK
        $catatanRevisi = $usulan->approvals->first()?->catatan;
 
        return Inertia::render('usulan/Edit', [
            'usulan'        => $usulan,
            'anggaranList'  => $anggaranList,
            'kategoriList'  => $kategoriList,
            'catatanRevisi' => $catatanRevisi,
        ]);
    }
 
    /**
     * Simpan perubahan dan ajukan ulang ke PPTK.
     */
    public function update(UpdateUsulanRequest $request, UsulanPengadaan $usulan): RedirectResponse
    {
        if ($usulan->status !== 'draft') {
            return back()->with('error', 'Usulan ini tidak dapat diubah pada status saat ini.');
        }
 
        if (! $request->user()->isAdmin()
            && $usulan->pemohon_id !== $request->user()->id) {
            abort(403);
        }
 
        DB::transaction(function () use ($request, $usulan) {
            $totalEstimasi = collect($request->validated('items'))
                ->sum(fn ($i) => $i['jumlah'] * $i['harga_satuan_estimasi']);
 
            // Update data utama usulan
            $usulan->update([
                'judul'          => $request->validated('judul'),
                'latar_belakang' => $request->validated('latar_belakang'),
                'keterangan'     => $request->validated('keterangan'),
                'anggaran_id'    => $request->validated('anggaran_id'),
                'total_estimasi' => $totalEstimasi,
                'status'         => 'diajukan',  // langsung ajukan ulang
                'submitted_at'   => now(),
            ]);
 
            // Hapus items lama dan buat ulang dari data baru
            $usulan->items()->delete();
 
            foreach ($request->validated('items') as $item) {
                $usulan->items()->create([
                    'kategori_id'           => $item['kategori_id'],
                    'nama_barang'           => $item['nama_barang'],
                    'spesifikasi'           => $item['spesifikasi'] ?? null,
                    'satuan'                => $item['satuan'],
                    'jumlah'                => $item['jumlah'],
                    'harga_satuan_estimasi' => $item['harga_satuan_estimasi'],
                    'subtotal'              => $item['jumlah'] * $item['harga_satuan_estimasi'],
                ]);
            }
        });
 
        // Log aktivitas
        ActivityLogger::fromRequest(
            request:     $request,
            action:      'usulan.submit',
            description: "Usulan {$usulan->no_usulan} diperbaiki dan diajukan ulang: {$usulan->judul}",
            usulanId:    $usulan->id,
            subjectType: 'UsulanPengadaan',
            subjectId:   $usulan->id,
        );
 
        // Notifikasi ke semua PPTK
        $pptk = \App\Models\User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'pptk'))
            ->where('is_active', true)
            ->get();
 
        \Illuminate\Support\Facades\Notification::send($pptk, new UsulanBaruNotification($usulan));
 
        return redirect()
            ->route('usulan.show', $usulan)
            ->with('success', "Usulan {$usulan->no_usulan} berhasil diperbaiki dan diajukan ulang ke PPTK.");
    }

    public function destroy(UsulanPengadaan $usulan): RedirectResponse
    {
        abort(404, 'Belum diimplementasi');
    }
}