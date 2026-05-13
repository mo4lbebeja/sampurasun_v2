<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideUsulanRequest;
use App\Models\UsulanPengadaan;
use App\Notifications\UsulanDisetujuiNotification;   // ← tambahkan
use App\Services\ActivityLogger;                      // ← tambahkan
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;         // ← tambahkan
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    /**
     * Daftar usulan yang menunggu approval (untuk PPTK).
     */
    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        $usulan = UsulanPengadaan::query()
            ->with([
                'pemohon:id,name,unit_kerja_id',
                'pemohon.unitKerja:id,nama',

                // Relasi anggaran untuk filter tahun aktif
                'anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            ])
            ->where('status', 'diajukan')
            ->whereHas('anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('approval/Index', [
            'usulan' => $usulan,
        ]);
    }

    /**
     * Proses keputusan approval untuk satu usulan.
     */
    public function decide(DecideUsulanRequest $request, UsulanPengadaan $usulan): RedirectResponse
    {
        $usulan->load([
            'anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
            'anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
            'anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
        ]);
 
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');
 
        $sesuaiTahun = (int) $usulan->anggaran?->tahun === $tahunAnggaran
            || (int) $usulan->anggaran?->subKegiatan?->dpaAnggaran?->tahun_anggaran === $tahunAnggaran;
 
        abort_unless($sesuaiTahun, 403);
 
        if ($usulan->status !== 'diajukan') {
            return back()->withErrors([
                'keputusan' => 'Usulan ini sudah tidak bisa diproses.',
            ]);
        }
 
        DB::transaction(function () use ($request, $usulan) {
            $usulan->approvals()->create([
                'approver_id'      => $request->user()->id,
                'keputusan'        => $request->validated('keputusan'),
                'catatan'          => $request->validated('catatan'),
                'tanggal_keputusan' => now(),
            ]);
 
            $newStatus = match ($request->validated('keputusan')) {
                'disetujui' => 'disetujui',
                'ditolak'   => 'ditolak',
                'revisi'    => 'draft',
            };
 
            $usulan->update(['status' => $newStatus]);
        });
 
        $keputusan = $request->validated('keputusan');
 
        // ── ActivityLogger ───────────────────────────────────────────
        $actionMap = [
            'disetujui' => 'approval.approve',
            'ditolak'   => 'approval.reject',
            'revisi'    => 'approval.revise',
        ];
 
        $descMap = [
            'disetujui' => "Usulan {$usulan->no_usulan} disetujui oleh {$request->user()->name}",
            'ditolak'   => "Usulan {$usulan->no_usulan} ditolak oleh {$request->user()->name}",
            'revisi'    => "Usulan {$usulan->no_usulan} diminta revisi oleh {$request->user()->name}",
        ];
 
        ActivityLogger::fromRequest(
            request:     $request,
            action:      $actionMap[$keputusan],
            description: $descMap[$keputusan],
            usulanId:    $usulan->id,
            subjectType: 'UsulanPengadaan',
            subjectId:   $usulan->id,
            properties:  ['catatan' => $request->validated('catatan')],
        );
 
        // ── Notifikasi ke Pejabat Pengadaan (hanya saat disetujui) ───
        if ($keputusan === 'disetujui') {
            $pejabatPengadaan = \App\Models\User::query()
                ->whereHas('role', fn ($q) => $q->where('name', 'pejabat_pengadaan'))
                ->where('is_active', true)
                ->get();
 
            Notification::send($pejabatPengadaan, new UsulanDisetujuiNotification($usulan));
        }
        // ─────────────────────────────────────────────────────────────
 
        $msgMap = [
            'disetujui' => "Usulan {$usulan->no_usulan} berhasil disetujui.",
            'ditolak'   => "Usulan {$usulan->no_usulan} ditolak.",
            'revisi'    => "Permintaan revisi dikirim untuk usulan {$usulan->no_usulan}.",
        ];
 
        return redirect()
            ->route('usulan.show', $usulan)
            ->with('success', $msgMap[$keputusan]);
    }
}