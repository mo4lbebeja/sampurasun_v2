<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengadaanRequest;
use App\Models\Pengadaan;
use App\Models\Penyedia;
use App\Models\UsulanPengadaan;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use App\Notifications\PengadaanKontrakNotification;

class PengadaanController extends Controller
{
    /**
     * Daftar pengadaan: semua yang belum selesai untuk pejabat pengadaan.
     */

    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
        $anggaranFilter = function ($anggaran) use ($tahunAnggaran) {
            $anggaran->where('tahun', $tahunAnggaran)
                ->orWhereHas('subKegiatan.dpaAnggaran', fn ($dpa) =>
                    $dpa->where('tahun_anggaran', $tahunAnggaran)
                );
        };
 
        // Usulan siap: status 'disetujui' ATAU 'dalam_pengadaan' yang masih
        // punya item belum dipaketkan
        $usulanSiap = UsulanPengadaan::query()
            ->with([
                'pemohon:id,name,unit_kerja_id',
                'pemohon.unitKerja:id,nama',
                'anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                // Load items + assignment status
                'items:id,usulan_id,nama_barang,satuan,jumlah,subtotal,kategori_id',
                'items.kategori:id,nama',
                // Load paket yang sudah ada
                'pengadaan:id,usulan_id,no_pengadaan,nama_paket,status,metode,estimasi_paket',
            ])
            ->where(function ($q) {
                $q->where('status', 'disetujui')
                  // Juga tampilkan yang dalam_pengadaan tapi masih ada item belum dipaketkan
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'dalam_pengadaan')
                         ->whereHas('items', function ($itemQ) {
                             $itemQ->whereNotExists(function ($assignQ) {
                                 $assignQ->from('pengadaan_item_assignments as pia')
                                     ->whereColumn('pia.usulan_item_id', 'usulan_items.id')
                                     ->join('pengadaan as p', 'p.id', '=', 'pia.pengadaan_id')
                                     ->where('p.status', '!=', 'batal');
                             });
                         });
                  });
            })
            ->whereHas('anggaran', $anggaranFilter)
            ->latest('id')
            ->paginate(20, ['*'], 'siap_page')
            ->withQueryString();
 
        // Tambahkan info item yang sudah di-assign per usulan
        $usulanSiap->getCollection()->transform(function ($usulan) {
            $assignedItemIds = \App\Models\PengadaanItemAssignment::query()
                ->whereHas('pengadaan', fn ($q) =>
                    $q->where('usulan_id', $usulan->id)->where('status', '!=', 'batal')
                )
                ->pluck('usulan_item_id')
                ->toArray();
 
            $usulan->items_assigned_ids = $assignedItemIds;
            $usulan->items_total        = $usulan->items->count();
            $usulan->items_assigned     = count($assignedItemIds);
            $usulan->items_remaining    = $usulan->items_total - $usulan->items_assigned;
 
            return $usulan;
        });
 
        // Pengadaan berjalan — tidak berubah
        $pengadaanBerjalan = Pengadaan::query()
            ->with([
                'usulan:id,no_usulan,judul,total_estimasi,status,anggaran_id',
                'usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                'penyedia:id,nama',
            ])
            ->whereIn('status', ['proses', 'kontrak'])
            ->whereHas('usulan.anggaran', $anggaranFilter)
            ->latest('id')
            ->paginate(20, ['*'], 'berjalan_page')
            ->withQueryString();
 
        return Inertia::render('pengadaan/Index', [
            'usulanSiap'        => $usulanSiap,
            'pengadaanBerjalan' => $pengadaanBerjalan,
        ]);
    }

    /**
     * Mulai pengadaan dari usulan yang disetujui.
     */
    public function start(
        StorePengadaanRequest $request,
        UsulanPengadaan $usulan,
        DocumentNumberService $numberService
    ): RedirectResponse {
        // Status harus 'disetujui' ATAU 'dalam_pengadaan' (untuk paket tambahan)
        if (! in_array($usulan->status, ['disetujui', 'dalam_pengadaan'])) {
            return back()->with('error', 'Usulan ini tidak bisa dibuatkan paket pengadaan baru.');
        }
 
        $itemIds = array_unique(
            array_filter(
                (array) $request->validated('item_ids', []),
                fn ($id) => is_numeric($id) && $id > 0
            )
        );
 
        $pakaiItemAssignment = ! empty($itemIds);
 
        if ($pakaiItemAssignment) {
            // Pastikan semua item milik usulan ini
            $validItemIds = $usulan->items()->pluck('id')->toArray();
            $invalid = array_diff($itemIds, $validItemIds);
 
            if (! empty($invalid)) {
                return back()->withErrors([
                    'item_ids' => 'Beberapa item bukan milik usulan ini.',
                ]);
            }
 
            // SEBELUM: whereHas yang bisa lolos karena terlalu spesifik
            // SESUDAH: cek global langsung ke tabel pia — lebih sederhana & tidak bisa lolos
            $sudahDipakai = \App\Models\PengadaanItemAssignment::query()
                ->whereIn('usulan_item_id', $itemIds)
                ->whereHas('pengadaan', fn ($q) =>
                    $q->where('status', '!=', 'batal')
                )
                ->pluck('usulan_item_id')
                ->toArray();
 
            if (! empty($sudahDipakai)) {
                $namaItem = $usulan->items()
                    ->whereIn('id', $sudahDipakai)
                    ->pluck('nama_barang')
                    ->implode(', ');
 
                return back()->withErrors([
                    'item_ids' => "Item berikut sudah ada di paket lain: {$namaItem}",
                ]);
            }
        }
 
        $pengadaan = DB::transaction(function () use (
            $request, $usulan, $numberService, $itemIds, $pakaiItemAssignment
        ) {
            $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
            $noPengadaan = $numberService->generateInsideTransaction(
                type: 'pengadaan',
                prefix: 'PGD',
                tahunAnggaran: $tahunAnggaran,
            );
 
            // Hitung estimasi paket dari item terpilih
            $estimasiPaket = $pakaiItemAssignment
                ? $usulan->items()->whereIn('id', $itemIds)->sum('subtotal')
                : (float) $usulan->total_estimasi;
 
            $pengadaan = Pengadaan::create([
                'usulan_id'      => $usulan->id,
                'pejabat_id'     => $request->user()->id,
                'no_pengadaan'   => $noPengadaan,
                'nama_paket'     => $request->validated('nama_paket'),
                'estimasi_paket' => $estimasiPaket,
                'metode'         => $request->validated('metode'),
                'tanggal_mulai'  => $request->validated('tanggal_mulai'),
                'catatan'        => $request->validated('catatan'),
                'status'         => 'proses',
            ]);
 
            // Simpan assignment item
            if ($pakaiItemAssignment) {
                // Hapus assignment lama dari paket yang sudah dibatalkan
                // agar item bisa diassign ulang ke paket baru
                \App\Models\PengadaanItemAssignment::query()
                    ->whereIn('usulan_item_id', $itemIds)
                    ->whereHas('pengadaan', fn ($q) => $q->where('status', 'batal'))
                    ->delete();
                    
                $now = now();
                $rows = array_map(fn ($id) => [
                    'pengadaan_id'   => $pengadaan->id,
                    'usulan_item_id' => $id,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ], $itemIds);
 
                // Insert sekaligus — lebih efisien dari loop create()
                \App\Models\PengadaanItemAssignment::insert($rows);
            }
 
            // Status usulan dihandle oleh PengadaanObserver via refreshStatus()
            return $pengadaan;
        }, 5);
 
        ActivityLogger::fromRequest(
            request:     $request,
            action:      'pengadaan.start',
            description: "Pengadaan {$pengadaan->no_pengadaan} dimulai untuk usulan {$usulan->no_usulan}" .
                         ($pengadaan->nama_paket ? " (Paket: {$pengadaan->nama_paket})" : ''),
            usulanId:    $usulan->id,
            subjectType: 'Pengadaan',
            subjectId:   $pengadaan->id,
            properties:  [
                'metode'      => $pengadaan->metode,
                'nama_paket'  => $pengadaan->nama_paket,
                'jumlah_item' => count($itemIds),
            ],
        );
 
        return redirect()
            ->route('pengadaan.show', $pengadaan)
            ->with('success', "Pengadaan {$pengadaan->no_pengadaan} berhasil dimulai.");
    }

    /**
     * Detail pengadaan + form input kontrak.
     */
    public function show(Pengadaan $pengadaan): Response
    {
        $pengadaan->load([
            'usulan:id,no_usulan,judul,total_estimasi,status,anggaran_id,pemohon_id',
            'usulan.pemohon:id,name,unit_kerja_id,jabatan',
            'usulan.pemohon.unitKerja:id,nama',
            'usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening,pagu',
            'usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
            'usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            // Semua item usulan (untuk menampilkan yang masuk paket ini)
            'usulan.items.kategori:id,nama',
            // Semua paket lain dari usulan yang sama (sibling)
            'usulan.pengadaan:id,usulan_id,no_pengadaan,nama_paket,metode,status,estimasi_paket,nilai_kontrak',
            // Item yang masuk paket INI
            'usulanItems' => fn ($q) => $q->with('kategori:id,nama'),
            'penyedia',
            'pejabat:id,name,jabatan',
            'pejabatPenandatangan:id,name,jabatan,nip',
            'kpaPenandatangan:id,name,jabatan,nip',
            'dokumenUpbj',
            'dokumenPengadaan',
            'pembayaran',
            'evaluasi',
        ]);
 
        $penyediaOptions = Penyedia::query()
            ->where('is_active', true)
            ->select('id', 'nama', 'jenis_badan', 'npwp', 'nama_pic', 'alamat', 'telepon')
            ->orderBy('nama')
            ->get();
 
        $pejabatOptions = User::query()
            ->select('id', 'name', 'nip', 'jabatan')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
 
        // ID item yang masuk paket INI
        $itemIdsInPaket = $pengadaan->usulanItems->pluck('id')->toArray();
 
        return Inertia::render('pengadaan/Show', [
            'pengadaan'       => $pengadaan,
            'penyediaOptions' => $penyediaOptions,
            'pejabatOptions'  => $pejabatOptions,
            'kpaOptions'      => $pejabatOptions,
            'itemIdsInPaket'  => $itemIdsInPaket,
        ]);
    }

    /**
     * Update kontrak — final step yang memindahkan ke UPBJ.
     */
    // =========================================================
    // method updateKontrak()
    // =========================================================
    public function updateKontrak(Request $request, Pengadaan $pengadaan)
    {
        if ($pengadaan->status === 'selesai' || $pengadaan->status === 'batal') {
            return back()->withErrors([
                'pengadaan' => 'Dokumen kontrak tidak dapat diubah pada status ini.',
            ]);
        }
 
        if ($pengadaan->status === 'proses') {
            $validated = $request->validate([
                'penyedia_id'              => ['required', 'exists:penyedia,id'],
                'pejabat_penandatangan_id' => ['nullable', 'exists:users,id'],
                'kpa_penandatangan_id'     => ['nullable', 'exists:users,id'],
                'no_kontrak'               => ['required', 'string', 'max:255'],
                'tanggal_kontrak'          => ['required', 'date'],
                'tanggal_selesai'          => ['required', 'date', 'after_or_equal:tanggal_kontrak'],
                'catatan'                  => ['nullable', 'string'],
                'file_kontrak'             => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
                'file_hps'                 => ['nullable', 'file', 'mimes:pdf,xls,xlsx', 'max:20480'],
 
                // Harga per item — opsional, hanya jika paket pakai item assignment
                'item_kontrak'                          => ['nullable', 'array'],
                'item_kontrak.*.usulan_item_id'         => ['required', 'integer', 'exists:usulan_items,id'],
                'item_kontrak.*.harga_satuan_kontrak'   => ['required', 'numeric', 'min:0'],
 
                // Hanya dipakai jika tidak ada item_kontrak (paket tanpa item assignment)
                'nilai_kontrak'            => ['required_without:item_kontrak', 'nullable', 'numeric', 'min:0'],
            ]);
 
            $data = [
                'penyedia_id'              => $validated['penyedia_id'],
                'pejabat_penandatangan_id' => $validated['pejabat_penandatangan_id'] ?? null,
                'kpa_penandatangan_id'     => $validated['kpa_penandatangan_id'] ?? null,
                'no_kontrak'               => $validated['no_kontrak'],
                'tanggal_kontrak'          => $validated['tanggal_kontrak'],
                'tanggal_selesai'          => $validated['tanggal_selesai'],
                'catatan'                  => $validated['catatan'] ?? null,
                'status'                   => 'kontrak',
            ];
 
            // ── Hitung nilai_kontrak ──────────────────────────────────
            if (! empty($validated['item_kontrak'])) {
                // Simpan harga per item ke pivot table
                foreach ($validated['item_kontrak'] as $itemKontrak) {
                    \App\Models\PengadaanItemAssignment::query()
                        ->where('pengadaan_id', $pengadaan->id)
                        ->where('usulan_item_id', $itemKontrak['usulan_item_id'])
                        ->update([
                            'harga_satuan_kontrak' => $itemKontrak['harga_satuan_kontrak'],
                        ]);
                }
 
                // Auto-hitung nilai_kontrak dari SUM(jumlah × harga_satuan_kontrak)
                $data['nilai_kontrak'] = \Illuminate\Support\Facades\DB::table('pengadaan_item_assignments as pia')
                    ->join('usulan_items as ui', 'ui.id', '=', 'pia.usulan_item_id')
                    ->where('pia.pengadaan_id', $pengadaan->id)
                    ->sum(\Illuminate\Support\Facades\DB::raw('ui.jumlah * pia.harga_satuan_kontrak'));
            } else {
                // Paket tanpa item assignment — pakai nilai_kontrak manual
                $data['nilai_kontrak'] = $validated['nilai_kontrak'] ?? 0;
            }
        } else {
            // Status sudah kontrak — hanya bisa upload file atau update harga item
            $validated = $request->validate([
                'file_kontrak'                        => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
                'file_hps'                            => ['nullable', 'file', 'mimes:pdf,xls,xlsx', 'max:20480'],
                'item_kontrak'                        => ['nullable', 'array'],
                'item_kontrak.*.usulan_item_id'       => ['required', 'integer', 'exists:usulan_items,id'],
                'item_kontrak.*.harga_satuan_kontrak' => ['required', 'numeric', 'min:0'],
            ]);
 
            $data = [];
 
            // Update harga per item jika dikirim
            if (! empty($validated['item_kontrak'])) {
                foreach ($validated['item_kontrak'] as $itemKontrak) {
                    \App\Models\PengadaanItemAssignment::query()
                        ->where('pengadaan_id', $pengadaan->id)
                        ->where('usulan_item_id', $itemKontrak['usulan_item_id'])
                        ->update([
                            'harga_satuan_kontrak' => $itemKontrak['harga_satuan_kontrak'],
                        ]);
                }
 
                $data['nilai_kontrak'] = \Illuminate\Support\Facades\DB::table('pengadaan_item_assignments as pia')
                    ->join('usulan_items as ui', 'ui.id', '=', 'pia.usulan_item_id')
                    ->where('pia.pengadaan_id', $pengadaan->id)
                    ->sum(\Illuminate\Support\Facades\DB::raw('ui.jumlah * pia.harga_satuan_kontrak'));
            }
        }
 
        if ($request->hasFile('file_kontrak')) {
            $data['file_kontrak'] = $request->file('file_kontrak')
                ->store('pengadaan/kontrak', 'public');
        }
 
        if ($request->hasFile('file_hps')) {
            $data['file_hps'] = $request->file('file_hps')
                ->store('pengadaan/hps', 'public');
        }
 
        if (empty($data)) {
            return back()->with('info', 'Tidak ada perubahan yang disimpan.');
        }
 
        $pengadaan->update($data);
 
        if ($pengadaan->wasChanged('status') && $pengadaan->status === 'kontrak') {
            ActivityLogger::fromRequest(
                request:     $request,
                action:      'pengadaan.kontrak',
                description: "Kontrak {$pengadaan->no_kontrak} disimpan untuk pengadaan {$pengadaan->no_pengadaan}",
                usulanId:    $pengadaan->usulan?->id,
                subjectType: 'Pengadaan',
                subjectId:   $pengadaan->id,
                properties:  [
                    'no_kontrak'    => $pengadaan->no_kontrak,
                    'nilai_kontrak' => $pengadaan->nilai_kontrak,
                    'penyedia_id'   => $pengadaan->penyedia_id,
                ],
            );
 
            $upbj = \App\Models\User::query()
                ->whereHas('role', fn ($q) => $q->where('name', 'upbj'))
                ->where('is_active', true)
                ->get();
 
            \Illuminate\Support\Facades\Notification::send(
                $upbj,
                new PengadaanKontrakNotification($pengadaan)
            );
        }
 
        return redirect()
            ->route('pengadaan.show', $pengadaan)
            ->with('success', $pengadaan->status === 'kontrak'
                ? 'Kontrak berhasil disimpan dan diteruskan ke UPBJ.'
                : 'Kontrak berhasil diperbarui.');
    }

    /**
     * Batalkan pengadaan (kembalikan ke status disetujui).
     */
    public function cancel(Request $request, Pengadaan $pengadaan): RedirectResponse
    {
        if (! in_array($pengadaan->status, ['proses'])) {
            return back()->with('error', 'Pengadaan dalam status ini tidak bisa dibatalkan.');
        }
 
        DB::transaction(function () use ($pengadaan) {
            $pengadaan->update(['status' => 'batal']);
            $pengadaan->usulan->update(['status' => 'disetujui']);
        });
 
        // ── ActivityLogger ───────────────────────────────────────────
        ActivityLogger::fromRequest(
            request:     $request,
            action:      'pengadaan.cancel',
            description: "Pengadaan {$pengadaan->no_pengadaan} dibatalkan",
            usulanId:    $pengadaan->usulan?->id,
            subjectType: 'Pengadaan',
            subjectId:   $pengadaan->id,
        );
        // ─────────────────────────────────────────────────────────────
 
        return redirect()
            ->route('pengadaan.index')
            ->with('success', 'Pengadaan dibatalkan, usulan dikembalikan ke status disetujui.');
    }
}