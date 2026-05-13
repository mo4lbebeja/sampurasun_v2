<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDokumenUpbjRequest;
use App\Models\DokumenUpbj;
use App\Models\Pengadaan;
use App\Models\Penyedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\NomorDokumenPengadaanService;
use Barryvdh\DomPDF\Facade\Pdf;

class DokumenUpbjController extends Controller
{
    /**
     * Daftar pengadaan yang siap diproses dokumen UPBJ.
     */
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
        $pengadaan = Pengadaan::query()
            ->with([
                'usulan:id,no_usulan,judul,total_estimasi,pemohon_id,status,anggaran_id',
                'usulan.pemohon:id,name,unit_kerja_id',
                'usulan.pemohon.unitKerja:id,nama,kode',
                'usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                'penyedia:id,nama',
                'dokumenUpbj:id,pengadaan_id,is_complete,no_bast,completed_at,updated_at',
            ])
            ->whereHas('usulan', fn ($q) => $q->where('status', 'dokumen'))
            ->whereHas('usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', fn ($dpa) =>
                        $dpa->where('tahun_anggaran', $tahunAnggaran)
                    );
            })
            ->where('status', 'kontrak')
            ->latest('id')
            ->paginate(20)           // ← ganti dari ->get()
            ->withQueryString();
 
        return Inertia::render('dokumen/Index', [
            'pengadaan' => $pengadaan,
        ]);
    }

    public function generateDokumen(
        Pengadaan $pengadaan,
        NomorDokumenPengadaanService $service
    ) {
        $exists = $pengadaan->dokumenPengadaan()
            ->whereIn('jenis', [
                'bast',
                'bapmhp',
                'baprhp',
                'bapp',
            ])
            ->exists();

        if ($exists) {
            return back()->with('info', 'Nomor dokumen pengadaan sudah pernah dibuat.');
        }

        $service->generateSet(
            pengadaan: $pengadaan,
            tahun: now()->year,
            bulan: now()->month,
            userId: auth()->id(),
        );

        return back()->with('success', 'Nomor BAST, BAPMHP, BAPRHP, dan BAPP berhasil dibuat.');
    }

    /**
     * Form edit dokumen — auto-create record kalau belum ada.
     */
    public function edit(Pengadaan $pengadaan): Response|RedirectResponse
    {
        // Validasi: hanya pengadaan yang siap proses dokumen
        $pengadaan->load('usulan');

        if (! $pengadaan->usulan || $pengadaan->usulan->status !== 'dokumen') {
            return redirect()
                ->route('dokumen.index')
                ->with('error', 'Pengadaan ini tidak dalam tahap dokumen.');
        }

        // Auto-create dokumen_upbj kalau belum ada (find or create)
        $dokumen = DokumenUpbj::firstOrCreate(
            ['pengadaan_id' => $pengadaan->id],
            ['petugas_id' => $this->petugasId()],
        );

        $pengadaan->load([
            'usulan:id,no_usulan,judul,total_estimasi,status,pemohon_id',
            'usulan.pemohon:id,name,unit_kerja_id',
            'usulan.pemohon.unitKerja:id,nama,kode',
            'penyedia:id,nama,jenis_badan,npwp',
            'pejabat:id,name',
            'dokumenPengadaan',
        ]);

        return Inertia::render('dokumen/Edit', [
            'pengadaan' => $pengadaan,
            'dokumen'   => $dokumen,
        ]);
    }

    /**
     * Save data form (draft) — tidak transition status.
     */
    public function update(UpdateDokumenUpbjRequest $request, Pengadaan $pengadaan): RedirectResponse
    {
        $dokumen = DokumenUpbj::firstOrCreate(
            ['pengadaan_id' => $pengadaan->id],
            ['petugas_id' => $this->petugasId()],
        );

        $data = $request->validated();

        // Handle file uploads — overwrite kalau ada file lama
        $fileFields = ['file_bast', 'file_invoice', 'file_faktur_pajak', 'file_kuitansi', 'file_spp'];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                if ($dokumen->{$field}) {
                    Storage::disk('public')->delete($dokumen->{$field});
                }
                $folder = "dokumen-upbj/" . str_replace('file_', '', $field);
                $data[$field] = $request->file($field)->store($folder, 'public');
            }
        }

        // petugas_id selalu update dengan user yang terakhir edit
        $data['petugas_id'] = $request->user()->id;

        $dokumen->update($data);

        return back()->with('success', 'Data dokumen berhasil disimpan.');
    }

    /**
     * Selesaikan dokumen — transisi status usulan ke 'pembayaran'.
     */
    public function complete(Request $request, Pengadaan $pengadaan): RedirectResponse
    {
        $pengadaan->load([
            'usulan',
            'dokumenUpbj',
            'dokumenPengadaan',
        ]);

        $dokumen = $pengadaan->dokumenUpbj;

        if (! $dokumen) {
            return back()->with('error', 'Dokumen belum diinput.');
        }

        /*
        * Nomor BAST wajib berasal dari dokumen_pengadaan,
        * bukan lagi input manual.
        */
        $bast = $pengadaan->dokumenPengadaan()
            ->where('jenis', 'bast')
            ->first();

        if (! $bast) {
            return back()->withErrors([
                'complete' => 'Nomor BAST belum dibuat. Generate nomor dokumen terlebih dahulu.',
            ]);
        }

        /*
        * Sinkronkan nomor BAST resmi ke dokumen_upbj lama.
        * Ini menjaga kompatibilitas dengan fitur rekap/filter existing.
        */
        if (empty($dokumen->no_bast)) {
            $dokumen->update([
                'no_bast' => $bast->nomor,
                'tanggal_bast' => $dokumen->tanggal_bast ?? now()->toDateString(),
            ]);
        }

        // Validasi: semua file wajib harus sudah ter-upload
        $required = ['file_bast', 'file_invoice', 'file_faktur_pajak', 'file_kuitansi', 'file_spp'];
        $missing = [];

        foreach ($required as $field) {
            if (empty($dokumen->{$field})) {
                $missing[] = match ($field) {
                    'file_bast'         => 'BAST',
                    'file_invoice'      => 'Invoice',
                    'file_faktur_pajak' => 'Faktur Pajak',
                    'file_kuitansi'     => 'Kuitansi',
                    'file_spp'          => 'SPP',
                };
            }
        }

        if (! empty($missing)) {
            return back()->withErrors([
                'complete' => 'Dokumen berikut belum diunggah: ' . implode(', ', $missing),
            ]);
        }

        DB::transaction(function () use ($dokumen, $pengadaan, $request, $bast) {
            $dokumen->update([
                'no_bast' => $dokumen->no_bast ?: $bast->nomor,
                'tanggal_bast' => $dokumen->tanggal_bast ?? now()->toDateString(),
                'is_complete' => true,
                'completed_at' => now(),
                'petugas_id' => $request->user()->id,
            ]);

            $pengadaan->usulan->update(['status' => 'pembayaran']);
        });

        return redirect()
            ->route('dokumen.index')
            ->with('success', "Dokumen pengadaan {$pengadaan->no_pengadaan} telah lengkap. Diteruskan ke Keuangan.");
    }

    /**
     * Halaman rekap semua dokumen UPBJ (history & reporting).
     */
    public function rekap(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        $query = \App\Models\DokumenUpbj::query()
            ->with([
                'pengadaan:id,no_pengadaan,no_kontrak,nilai_kontrak,penyedia_id,usulan_id,metode',
                'pengadaan.penyedia:id,nama,jenis_badan',

                // wajib bawa anggaran_id agar bisa akses anggaran
                'pengadaan.usulan:id,no_usulan,judul,status,pemohon_id,anggaran_id',
                'pengadaan.usulan.pemohon:id,name,unit_kerja_id',
                'pengadaan.usulan.pemohon.unitKerja:id,nama',

                // relasi tahun anggaran
                'pengadaan.usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'pengadaan.usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'pengadaan.usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',

                'petugas:id,name',
            ])
            ->whereHas('pengadaan.usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            });

        // lanjutkan filter search, tahun, bulan, penyedia, status seperti sebelumnya
        // Filter: search (no_bast, no_pengadaan, judul usulan)
        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('no_bast', 'like', "%{$search}%")
                ->orWhereHas('pengadaan', fn ($p) => $p->where('no_pengadaan', 'like', "%{$search}%"))
                ->orWhereHas('pengadaan.usulan', fn ($u) => $u->where('judul', 'like', "%{$search}%"));
            });
        }

        // Filter: tahun (berdasarkan tanggal_bast atau created_at kalau BAST belum ada)
        if ($tahun = $request->input('tahun')) {
            $query->where(function ($q) use ($tahun) {
                $q->whereYear('tanggal_bast', $tahun)
                ->orWhere(function ($q2) use ($tahun) {
                    $q2->whereNull('tanggal_bast')
                        ->whereYear('created_at', $tahun);
                });
            });
        }

        // Filter: bulan (1-12)
        if ($bulan = $request->input('bulan')) {
            $query->where(function ($q) use ($bulan) {
                $q->whereMonth('tanggal_bast', $bulan)
                ->orWhere(function ($q2) use ($bulan) {
                    $q2->whereNull('tanggal_bast')
                        ->whereMonth('created_at', $bulan);
                });
            });
        }

        // Filter: penyedia
        if ($penyediaId = $request->input('penyedia_id')) {
            $query->whereHas('pengadaan', fn ($p) => $p->where('penyedia_id', $penyediaId));
        }

        // Filter: status (belum/proses/selesai)
        if ($status = $request->input('status')) {
            if ($status === 'selesai') {
                $query->where('is_complete', true);
            } elseif ($status === 'proses') {
                $query->where('is_complete', false)
                    ->whereNotNull('no_bast');
            } elseif ($status === 'belum') {
                $query->where('is_complete', false)
                    ->whereNull('no_bast');
            }
        }

        $dokumen = $query->latest('updated_at')->paginate(20)->withQueryString();

        // Stats summary (untuk banner di atas)
        $stats = [
            'total'      => (clone $query)->count(),
            'selesai'    => (clone $query)->where('is_complete', true)->count(),
            'proses'     => (clone $query)->where('is_complete', false)->whereNotNull('no_bast')->count(),
            'belum'      => (clone $query)->where('is_complete', false)->whereNull('no_bast')->count(),
            'total_nilai' => \App\Models\Pengadaan::query()
                ->whereIn('id', (clone $query)->pluck('pengadaan_id'))
                ->sum('nilai_kontrak'),
        ];

        // Filter options
        $tahunOptions = \App\Models\DokumenUpbj::query()
            ->selectRaw('DISTINCT YEAR(COALESCE(tanggal_bast, created_at)) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $penyediaOptions = \App\Models\Penyedia::query()
            ->whereIn('id', \App\Models\Pengadaan::query()
                ->whereHas('dokumenUpbj')
                ->pluck('penyedia_id')
                ->filter())
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return Inertia::render('dokumen/Rekap', [
            'dokumen'         => $dokumen,
            'stats'           => $stats,
            'tahunOptions'    => $tahunOptions,
            'penyediaOptions' => $penyediaOptions,
            'filters'         => [
                'search'      => $request->string('search')->toString(),
                'tahun'       => $tahun,
                'bulan'       => $bulan,
                'penyedia_id' => $penyediaId,
                'status'      => $status,
            ],
        ]);
    }

    /**
     * Return data dokumen + pengadaan dalam JSON (untuk modal di rekap).
     */
    public function dokumenJson(\App\Models\Pengadaan $pengadaan)
    {
        $pengadaan->load([
            'usulan:id,no_usulan,judul,total_estimasi,status,pemohon_id',
            'usulan.pemohon:id,name,unit_kerja_id',
            'usulan.pemohon.unitKerja:id,nama,kode,alamat',
            'penyedia:id,nama,jenis_badan,npwp,nama_pic,alamat,telepon,rekening_bank,nama_bank,atas_nama_rekening',
            'pejabat:id,name',
            'dokumenUpbj',
            'dokumenUpbj.petugas:id,name',
            'dokumenPengadaan',
        ]);

        return response()->json([
            'pengadaan' => $pengadaan,
            'dokumen'   => $pengadaan->dokumenUpbj,

            /*
            * Rekap Dokumen hanya untuk monitoring/arsip.
            * Semua modal dari rekap wajib readonly.
            */
            'can_edit' => false,
            'readonly' => true,
        ]);
    }

    /**
     * Cetak dokumen pengadaan.
     */
    public function cetak(Pengadaan $pengadaan, string $jenis)
    {
        abort_unless(in_array($jenis, [
            'ringkasan-kontrak',
            'spp-sptj',
            'surat-pesanan',
            'bast',
            'bapmhp',
            'baprhp',
            'bapp',
        ], true), 404);

        $pengadaan->load([
            'usulan:id,no_usulan,judul,total_estimasi,status,pemohon_id,anggaran_id,tanggal_usulan,latar_belakang,keterangan',
            'usulan.pemohon:id,name,unit_kerja_id,nip,jabatan',
            'usulan.pemohon.unitKerja:id,nama,kode,alamat',
            'usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening,uraian,pagu',
            'usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
            'usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            'usulan.items:id,usulan_id,kategori_id,nama_barang,spesifikasi,satuan,jumlah,harga_satuan_estimasi,subtotal',
            'usulan.items.kategori:id,nama',
            'penyedia:id,nama,jenis_badan,npwp,nama_pic,alamat,telepon,rekening_bank,nama_bank,atas_nama_rekening',
            'pejabat:id,name,nip,jabatan,alamat',
            'pejabatPenandatangan:id,name,nip,jabatan,alamat',
            'kpaPenandatangan:id,name,nip,jabatan,alamat',
            'dokumenUpbj',
            'dokumenPengadaan',
        ]);

        if ($jenis === 'ringkasan-kontrak') {
            $pdf = Pdf::loadView('dokumen.cetak.ringkasan-kontrak', [
                'pengadaan' => $pengadaan,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('Ringkasan-Kontrak-' . $pengadaan->id . '.pdf');
        }
        if ($jenis === 'spp-sptj') {
            $pdf = Pdf::loadView('dokumen.cetak.spp-sptj', [
                'pengadaan' => $pengadaan,
                'dokumen' => null,
                'jenis' => $jenis,
            ])->setPaper('a4', 'portrait');
            return $pdf->stream('SPP-SPTJ-' . $pengadaan->id . '.pdf');

        }
        if ($jenis === 'surat-pesanan') {
            $pdf = Pdf::loadView('dokumen.cetak.surat-pesanan', [
                'pengadaan' => $pengadaan,
                'dokumen' => null,
                'jenis' => $jenis,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('Surat-Pesanan-' . $pengadaan->id . '.pdf');
        }
        $dokumen = $pengadaan->dokumenPengadaan()
            ->where('jenis', $jenis)
            ->firstOrFail();

        $view = match ($jenis) {
            'bast' => 'dokumen.cetak.bast',
            'bapmhp' => 'dokumen.cetak.bapmhp',
            'baprhp' => 'dokumen.cetak.baprhp',
            'bapp' => 'dokumen.cetak.bapp',
            'spp-sptj' => 'dokumen.cetak.spp-sptj',
            default => abort(404),
        };

        $pdf = Pdf::loadView($view, [
            'pengadaan' => $pengadaan,
            'dokumen' => $dokumen,
            'jenis' => $jenis,
        ])->setPaper('a4', 'portrait');
        return $pdf->stream(str_replace('/', '-', $dokumen->nomor) . '.pdf');
    }

    protected function petugasId(): int
    {
        return auth()->id();
    }
}