<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengadaanRequest;
use App\Http\Requests\UpdateKontrakRequest;
use App\Models\Pengadaan;
use App\Models\Penyedia;
use App\Models\UsulanPengadaan;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;


class PengadaanController extends Controller
{
    /**
     * Daftar pengadaan: semua yang belum selesai untuk pejabat pengadaan.
     */

    public function index(Request $request): Response
    {
        $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

        // Tampilkan usulan disetujui hanya untuk tahun anggaran aktif
        $usulanSiap = UsulanPengadaan::query()
            ->with([
                'pemohon:id,name,unit_kerja_id',
                'pemohon.unitKerja:id,nama',
                'anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
            ])
            ->where('status', 'disetujui')
            ->whereHas('anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->latest('id')
            ->get();

        // Tampilkan pengadaan berjalan hanya untuk tahun anggaran aktif
        $pengadaanBerjalan = Pengadaan::query()
            ->with([
                'usulan:id,no_usulan,judul,total_estimasi,status,anggaran_id',
                'usulan.anggaran:id,sub_kegiatan_id,tahun,kode_rekening,nama_rekening',
                'usulan.anggaran.subKegiatan:id,dpa_anggaran_id,kode_sub_kegiatan,nama_kegiatan,tahun_anggaran',
                'usulan.anggaran.subKegiatan.dpaAnggaran:id,tahun_anggaran,no_dpa,tanggal_dpa,nama_dpa',
                'penyedia:id,nama',
            ])
            ->whereIn('status', ['proses', 'kontrak'])
            ->whereHas('usulan.anggaran', function ($anggaran) use ($tahunAnggaran) {
                $anggaran->where('tahun', $tahunAnggaran)
                    ->orWhereHas('subKegiatan.dpaAnggaran', function ($dpa) use ($tahunAnggaran) {
                        $dpa->where('tahun_anggaran', $tahunAnggaran);
                    });
            })
            ->latest('id')
            ->get();

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
        if ($usulan->status !== 'disetujui') {
            return back()->with('error', 'Usulan ini tidak dalam status disetujui.');
        }

        // Cek pengadaan existing
        $existing = $usulan->pengadaan;

        if ($existing && $existing->status !== 'batal') {
            return back()->with('error', 'Usulan ini sudah ada record pengadaan aktif.');
        }

        $pengadaan = DB::transaction(function () use ($request, $usulan, $existing, $numberService) {
            // Hapus pengadaan lama yang sudah dibatalkan
            if ($existing) {
                $existing->delete();
            }

            $tahunAnggaran = (int) $request->session()->get('tahun_anggaran');

            $noPengadaan = $numberService->generateInsideTransaction(
                type: 'pengadaan',
                prefix: 'PGD',
                tahunAnggaran: $tahunAnggaran,
            );

            $pengadaan = Pengadaan::create([
                'usulan_id' => $usulan->id,
                'pejabat_id' => $request->user()->id,
                'no_pengadaan' => $noPengadaan,
                'metode' => $request->validated('metode'),
                'tanggal_mulai' => $request->validated('tanggal_mulai'),
                'catatan' => $request->validated('catatan'),
                'status' => 'proses',
            ]);

            $usulan->update(['status' => 'dalam_pengadaan']);

            return $pengadaan;
        }, 5);

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
        'usulan:id,no_usulan,judul,total_estimasi,status',
        'usulan.pemohon:id,name,unit_kerja_id',
        'usulan.pemohon.unitKerja:id,nama',
        'usulan.items.kategori:id,nama',
        'pejabat:id,name,nip,jabatan,alamat',
        'pejabatPenandatangan:id,name,nip,jabatan,alamat',
        'kpaPenandatangan:id,name,nip,jabatan,alamat',
        'penyedia',
    ]);

    $penyediaOptions = Penyedia::query()
        ->where('is_active', true)
        ->orderBy('nama')
        ->get([
            'id',
            'nama',
            'jenis_badan',
            'npwp',
            'nama_pic',
            'alamat',
            'telepon',
        ])
        ->map(fn ($penyedia) => [
            'id' => $penyedia->id,
            'nama' => $penyedia->nama,
            'jenis_badan' => $penyedia->jenis_badan,
            'npwp' => $penyedia->npwp,
            'nama_pic' => $penyedia->nama_pic,
            'alamat' => $penyedia->alamat,
            'telepon' => $penyedia->telepon,
        ])
        ->values();

        $pejabatOptions = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'jabatan'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
                'jabatan' => $user->jabatan,
                'alamat' => $user->alamat,
            ])
            ->values();

        $kpaOptions = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'jabatan', 'alamat'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
                'jabatan' => $user->jabatan,
                'alamat' => $user->alamat,
            ])
            ->values();

    return Inertia::render('pengadaan/Show', [
        'pengadaan' => $pengadaan,

        // Untuk komponen baru
        'penyediaOptions' => $penyediaOptions,

        // Transisi: dipertahankan agar kode lama tidak rusak
        'penyediaList' => $penyediaOptions,
        'pejabatOptions' => $pejabatOptions,
        'kpaOptions' => $kpaOptions,
    ]);
}

    /**
     * Update kontrak — final step yang memindahkan ke UPBJ.
     */
    public function updateKontrak(Request $request, Pengadaan $pengadaan)
    {
        if ($pengadaan->status === 'selesai' || $pengadaan->status === 'batal') {
            return back()->withErrors([
                'pengadaan' => 'Dokumen kontrak tidak dapat diubah pada status ini.',
            ]);
        }

        if ($pengadaan->status === 'proses') {
            $validated = $request->validate([
                'penyedia_id' => ['required', 'exists:penyedia,id'],
                'pejabat_penandatangan_id' => ['nullable', 'exists:users,id'],
                'kpa_penandatangan_id' => ['nullable', 'exists:users,id'],
                'no_kontrak' => ['required', 'string', 'max:255'],
                'tanggal_kontrak' => ['required', 'date'],
                'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_kontrak'],
                'nilai_kontrak' => ['required', 'numeric', 'min:0'],
                'catatan' => ['nullable', 'string'],
                'file_kontrak' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
                'file_hps' => ['nullable', 'file', 'mimes:pdf,xls,xlsx', 'max:20480'],
            ]);

            $data = [
                'penyedia_id' => $validated['penyedia_id'],
                'pejabat_penandatangan_id' => $validated['pejabat_penandatangan_id'] ?? null,
                'kpa_penandatangan_id' => $validated['kpa_penandatangan_id'] ?? null,
                'no_kontrak' => $validated['no_kontrak'],
                'tanggal_kontrak' => $validated['tanggal_kontrak'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'nilai_kontrak' => $validated['nilai_kontrak'],
                'catatan' => $validated['catatan'] ?? null,
                'status' => 'kontrak',
            ];
        } else {
            $validated = $request->validate([
                'file_kontrak' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
                'file_hps' => ['nullable', 'file', 'mimes:pdf,xls,xlsx', 'max:20480'],
            ]);

            $data = [];
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
            return back()->with('info', 'Tidak ada dokumen yang diupload.');
        }

        $pengadaan->update($data);

        if ($pengadaan->wasChanged('status') && $pengadaan->status === 'kontrak') {
            $pengadaan->usulan?->update([
                'status' => 'dokumen',
            ]);
        }

        return redirect()
            ->route('pengadaan.show', $pengadaan)
            ->with('success', $pengadaan->status === 'kontrak'
                ? 'Kontrak berhasil disimpan dan diteruskan ke UPBJ.'
                : 'Dokumen kontrak berhasil diperbarui.');
    }

    /**
     * Batalkan pengadaan (kembalikan ke status disetujui).
     */
    public function cancel(Pengadaan $pengadaan): RedirectResponse
    {
        if (! in_array($pengadaan->status, ['proses'])) {
            return back()->with('error', 'Pengadaan dalam status ini tidak bisa dibatalkan.');
        }

        DB::transaction(function () use ($pengadaan) {
            $pengadaan->update(['status' => 'batal']);
            $pengadaan->usulan->update(['status' => 'disetujui']);
        });

        return redirect()
            ->route('pengadaan.index')
            ->with('success', 'Pengadaan dibatalkan, usulan dikembalikan ke status disetujui.');
    }
}