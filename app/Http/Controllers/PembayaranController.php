<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePembayaranRequest;
use App\Models\Pembayaran;
use App\Models\Pengadaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\PembayaranLunasNotification;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Notification;

use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PembayaranController extends Controller
{
    /**
     * Daftar pengadaan yang siap diproses pembayaran.
     */
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
        $pengadaan = Pengadaan::query()
            ->with([
                'usulan:id,no_usulan,judul,total_estimasi,pemohon_id,status,anggaran_id',
                'usulan.pemohon:id,name,unit_kerja_id',
                'usulan.pemohon.unitKerja:id,nama',
                'usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                'penyedia:id,nama,nama_bank,rekening_bank',
                'pembayaran:id,pengadaan_id,status,no_spm,no_sp2d,nilai_bayar,nilai_bersih,tanggal_bayar,updated_at',
            ])
            // ← SEBELUM: whereHas('usulan', fn ($q) => $q->where('status', 'pembayaran'))
            // ← SESUDAH: dokumen sudah complete, pembayaran belum lunas atau belum ada
            ->whereHas('dokumenUpbj', fn ($q) => $q->where('is_complete', true))
            ->where(function ($q) {
                $q->doesntHave('pembayaran')
                  ->orWhereHas('pembayaran', fn ($pb) =>
                      $pb->whereNotIn('status', ['lunas', 'batal'])
                  );
            })
            ->whereHas('usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', fn ($dpa) =>
                        $dpa->where('tahun_anggaran', $tahunAnggaran)
                    );
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
 
        return Inertia::render('pembayaran/Index', [
            'pengadaan' => $pengadaan,
        ]);
    }

    /**
     * Form edit pembayaran — auto-create record kalau belum ada.
     */
    public function edit(Pengadaan $pengadaan): Response|RedirectResponse
    {
        $pengadaan->load('usulan', 'dokumenUpbj');
 
        // ← SEBELUM:
        // if (! $pengadaan->usulan || $pengadaan->usulan->status !== 'pembayaran')
        //
        // ← SESUDAH: cek dokumen UPBJ sudah complete
        if (! $pengadaan->dokumenUpbj?->is_complete) {
            return redirect()
                ->route('pembayaran.index')
                ->with('error', 'Dokumen UPBJ pengadaan ini belum lengkap.');
        }
 
        // Auto-create pembayaran record kalau belum ada
        $pembayaran = Pembayaran::firstOrCreate(
            ['pengadaan_id' => $pengadaan->id],
            [
                'petugas_id'   => $this->petugasId(),
                'nilai_bayar'  => $pengadaan->nilai_kontrak ?? 0,
                'metode_bayar' => 'transfer',
                'status'       => 'pending',
            ],
        );
 
        $pengadaan->load([
            'usulan:id,no_usulan,judul,total_estimasi,status,pemohon_id',
            'usulan.pemohon:id,name,unit_kerja_id',
            'usulan.pemohon.unitKerja:id,nama',
            'penyedia',
            'pejabat:id,name',
            'dokumenUpbj:id,pengadaan_id,no_bast,tanggal_bast',
        ]);
 
        return Inertia::render('pembayaran/Edit', [
            'pengadaan'  => $pengadaan,
            'pembayaran' => $pembayaran,
        ]);
    }

    /**
     * Save data form (draft) — tidak transition status.
     */
    public function update(UpdatePembayaranRequest $request, Pengadaan $pengadaan): RedirectResponse
    {
        $pembayaran = Pembayaran::firstOrCreate(
            ['pengadaan_id' => $pengadaan->id],
            ['petugas_id' => $this->petugasId()],
        );

        $data = $request->validated();

        // Handle bukti bayar upload
        if ($request->hasFile('bukti_bayar')) {
            if ($pembayaran->bukti_bayar) {
                Storage::disk('public')->delete($pembayaran->bukti_bayar);
            }
            $data['bukti_bayar'] = $request->file('bukti_bayar')->store('pembayaran/bukti', 'public');
        }

        // Handle SPP upload
        if ($request->hasFile('file_spp')) {
            if ($pembayaran->file_spp) {
                Storage::disk('public')->delete($pembayaran->file_spp);
            }
            $data['file_spp'] = $request->file('file_spp')
                ->store('pembayaran/spp', 'public');
        }

        // Auto-calc nilai_bersih = nilai_bayar - PPh - PPN
        $nilaiBayar = (float) ($data['nilai_bayar'] ?? 0);
        $pph = (float) ($data['pajak_pph'] ?? 0);
        $ppn = (float) ($data['pajak_ppn'] ?? 0);
        $data['nilai_bersih'] = max(0, $nilaiBayar - $pph - $ppn);

        // Status update logic:
        // - kalau no_spm + no_sp2d + tanggal_bayar belum lengkap: status='pending'
        // - kalau lengkap (tapi belum di-finalize): status='diproses'
        if (! empty($data['no_spm']) && ! empty($data['no_sp2d']) && ! empty($data['tanggal_bayar'])) {
            if ($pembayaran->status === 'pending') {
                $data['status'] = 'diproses';
            }
        }

        $data['petugas_id'] = $request->user()->id;

        $pembayaran->update($data);

        return back()->with('success', 'Data pembayaran berhasil disimpan.');
    }

    /**
     * Selesaikan pembayaran — transisi status usulan ke 'evaluasi'.
     */
    public function complete(Request $request, Pengadaan $pengadaan): RedirectResponse
    {
        $pembayaran = $pengadaan->pembayaran;
 
        if (! $pembayaran) {
            return back()->with('error', 'Data pembayaran belum diinput.');
        }
 
        $missing = [];
        if (empty($pembayaran->no_spm))        $missing[] = 'Nomor SPM';
        if (empty($pembayaran->no_sp2d))       $missing[] = 'Nomor SP2D';
        if (empty($pembayaran->tanggal_bayar)) $missing[] = 'Tanggal Bayar';
        if (empty($pembayaran->bukti_bayar))   $missing[] = 'Bukti Bayar';
 
        if (! empty($missing)) {
            return back()->withErrors([
                'complete' => 'Data berikut belum lengkap: ' . implode(', ', $missing),
            ]);
        }
 
        if ((float) $pembayaran->nilai_bayar <= 0) {
            return back()->withErrors([
                'complete' => 'Nilai bayar harus lebih dari 0.',
            ]);
        }
 
        DB::transaction(function () use ($pembayaran, $pengadaan, $request) {
            $pembayaran->update([
                'status'     => 'lunas',
                'petugas_id' => $request->user()->id,
            ]);
            // PengadaanObserver::updated() otomatis panggil usulan->refreshStatus()
            $pengadaan->update(['status' => 'selesai']);

            // ← DIHAPUS: $pengadaan->usulan->update(['status' => 'evaluasi'])
            // Perencanaan melihat paket dari pembayaran.status = 'lunas' + belum ada evaluasi
            // Tidak perlu update usulan.status
        });
 
        ActivityLogger::fromRequest(
            request:     $request,
            action:      'pembayaran.lunas',
            description: "Pembayaran pengadaan {$pengadaan->no_pengadaan} lunas, siap dievaluasi",
            usulanId:    $pengadaan->usulan?->id,
            subjectType: 'Pengadaan',
            subjectId:   $pengadaan->id,
            properties:  [
                'no_sp2d'     => $pembayaran->no_sp2d,
                'nilai_bayar' => $pembayaran->nilai_bayar,
            ],
        );
 
        $perencanaan = \App\Models\User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'perencanaan'))
            ->where('is_active', true)
            ->get();
 
        \Illuminate\Support\Facades\Notification::send($perencanaan, new PembayaranLunasNotification($pengadaan));
 
        return redirect()
            ->route('pembayaran.index')
            ->with('success', "Pembayaran pengadaan {$pengadaan->no_pengadaan} telah lunas. Diteruskan ke Bagian Perencanaan.");
    }

    /**
     * Halaman rekap semua pembayaran (history & reporting).
     */
    public function rekap(Request $request): \Inertia\Response
    {
        $query = \App\Models\Pembayaran::query()
            ->with([
                'pengadaan:id,no_pengadaan,no_kontrak,nilai_kontrak,penyedia_id,usulan_id,metode',
                'pengadaan.penyedia:id,nama,jenis_badan,nama_bank,rekening_bank',
                'pengadaan.usulan:id,no_usulan,judul,status',
                'petugas:id,name',
            ]);

        // Capture filter values
        $search = $request->string('search')->toString();
        $tahun = $request->input('tahun');
        $bulan = $request->input('bulan');
        $penyediaId = $request->input('penyedia_id');
        $status = $request->input('status');
        $metodeBayar = $request->input('metode_bayar');

        // Apply filters ke query utama
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('no_spm', 'like', "%{$search}%")
                ->orWhere('no_sp2d', 'like', "%{$search}%")
                ->orWhereHas('pengadaan', fn ($p) => $p->where('no_pengadaan', 'like', "%{$search}%"))
                ->orWhereHas('pengadaan.usulan', fn ($u) => $u->where('judul', 'like', "%{$search}%"));
            });
        }
        if ($tahun) {
            $query->where(function ($q) use ($tahun) {
                $q->whereYear('tanggal_bayar', $tahun)
                ->orWhere(function ($q2) use ($tahun) {
                    $q2->whereNull('tanggal_bayar')->whereYear('created_at', $tahun);
                });
            });
        }
        if ($bulan) {
            $query->where(function ($q) use ($bulan) {
                $q->whereMonth('tanggal_bayar', $bulan)
                ->orWhere(function ($q2) use ($bulan) {
                    $q2->whereNull('tanggal_bayar')->whereMonth('created_at', $bulan);
                });
            });
        }
        if ($penyediaId) {
            $query->whereHas('pengadaan', fn ($p) => $p->where('penyedia_id', $penyediaId));
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($metodeBayar) {
            $query->where('metode_bayar', $metodeBayar);
        }

        $pembayaran = $query->latest('updated_at')->paginate(20)->withQueryString();

        // Stats — pakai fresh query base (bukan clone $query yang sudah punya order/eager-load)
        $statsBase = \App\Models\Pembayaran::query();

        if ($search) {
            $statsBase->where(function ($q) use ($search) {
                $q->where('no_spm', 'like', "%{$search}%")
                ->orWhere('no_sp2d', 'like', "%{$search}%")
                ->orWhereHas('pengadaan', fn ($p) => $p->where('no_pengadaan', 'like', "%{$search}%"))
                ->orWhereHas('pengadaan.usulan', fn ($u) => $u->where('judul', 'like', "%{$search}%"));
            });
        }
        if ($tahun) {
            $statsBase->where(function ($q) use ($tahun) {
                $q->whereYear('tanggal_bayar', $tahun)
                ->orWhere(function ($q2) use ($tahun) {
                    $q2->whereNull('tanggal_bayar')->whereYear('created_at', $tahun);
                });
            });
        }
        if ($bulan) {
            $statsBase->where(function ($q) use ($bulan) {
                $q->whereMonth('tanggal_bayar', $bulan)
                ->orWhere(function ($q2) use ($bulan) {
                    $q2->whereNull('tanggal_bayar')->whereMonth('created_at', $bulan);
                });
            });
        }
        if ($penyediaId) {
            $statsBase->whereHas('pengadaan', fn ($p) => $p->where('penyedia_id', $penyediaId));
        }
        if ($status) {
            $statsBase->where('status', $status);
        }
        if ($metodeBayar) {
            $statsBase->where('metode_bayar', $metodeBayar);
        }

        // Total pajak — pakai DB::raw bukan selectRaw + value
        $totalPajak = (clone $statsBase)
            ->where('status', 'lunas')
            ->sum(\DB::raw('pajak_pph + pajak_ppn'));

        $stats = [
            'total'         => (clone $statsBase)->count(),
            'lunas'         => (clone $statsBase)->where('status', 'lunas')->count(),
            'diproses'      => (clone $statsBase)->where('status', 'diproses')->count(),
            'pending'       => (clone $statsBase)->where('status', 'pending')->count(),
            'total_lunas'   => (clone $statsBase)->where('status', 'lunas')->sum('nilai_bayar'),
            'total_pending' => (clone $statsBase)->whereIn('status', ['pending', 'diproses'])->sum('nilai_bayar'),
            'total_pajak'   => $totalPajak,
        ];

        // Filter options untuk dropdown
        $tahunOptions = \App\Models\Pembayaran::query()
            ->selectRaw('DISTINCT YEAR(COALESCE(tanggal_bayar, created_at)) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $penyediaOptions = \App\Models\Penyedia::query()
            ->whereIn('id', \App\Models\Pengadaan::query()
                ->whereHas('pembayaran')
                ->pluck('penyedia_id')
                ->filter())
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return \Inertia\Inertia::render('pembayaran/Rekap', [
            'pembayaran'      => $pembayaran,
            'stats'           => $stats,
            'tahunOptions'    => $tahunOptions,
            'penyediaOptions' => $penyediaOptions,
            'filters'         => [
                'search'       => $search,
                'tahun'        => $tahun,
                'bulan'        => $bulan,
                'penyedia_id'  => $penyediaId,
                'status'       => $status,
                'metode_bayar' => $metodeBayar,
            ],
        ]);
    }

    private function petugasId(): int
    {
        return request()->user()->id;
    }
}