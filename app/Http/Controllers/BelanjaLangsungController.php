<?php

namespace App\Http\Controllers;

use App\Models\Anggaran;
use App\Models\BelanjaLangsung;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BelanjaLangsungController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $user          = $request->user();
        $tahun         = (int) $request->session()->get('tahun_anggaran');
        $isAdmin       = $user->isAdmin();
        $role          = $user->role?->name ?? '';
        $search        = $request->string('search')->toString();
        $filterStatus  = $request->string('status')->toString();
        $filterJenis   = $request->string('jenis')->toString();

        $query = BelanjaLangsung::query()
            ->with(['pembelanja:id,name', 'anggaran:id,kode_rekening,nama_rekening', 'approver:id,name'])
            ->where('tahun_anggaran', $tahun);

        // Batasi tampilan sesuai role
        if (! $isAdmin && $role === 'sarana_umum') {
            $query->where('pembelanja_id', $user->id);
        } elseif (! $isAdmin && $role === 'pptk') {
            $query->whereIn('status', ['diajukan', 'disetujui', 'ditolak', 'dibayar']);
        } elseif (! $isAdmin && $role === 'keuangan') {
            $query->whereIn('status', ['disetujui', 'dibayar']);
        }

        if ($search) {
            $query->where(fn ($q) =>
                $q->where('uraian', 'like', "%{$search}%")
                  ->orWhere('no_nota', 'like', "%{$search}%")
            );
        }

        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }

        if ($filterJenis) {
            $query->where('jenis', $filterJenis);
        }

        $belanja = $query->latest()->paginate(15)->withQueryString();

        // Stats
        $statsBase = BelanjaLangsung::query()->where('tahun_anggaran', $tahun);
        $stats = [
            'total'     => (clone $statsBase)->count(),
            'pending'   => (clone $statsBase)->whereIn('status', ['draft', 'diajukan'])->count(),
            'disetujui' => (clone $statsBase)->where('status', 'disetujui')->count(),
            'dibayar'   => (clone $statsBase)->where('status', 'dibayar')->count(),
            'total_nominal' => (float) (clone $statsBase)->where('status', 'dibayar')->sum('nominal'),
        ];

        return Inertia::render('belanja-langsung/Index', [
            'belanja'     => $belanja,
            'stats'       => $stats,
            'jenisLabel'  => BelanjaLangsung::$jenisLabel,
            'userRole'    => $role,
            'filters'     => [
                'search' => $search,
                'status' => $filterStatus,
                'jenis'  => $filterJenis,
            ],
        ]);
    }

    // ── Create ─────────────────────────────────────────────────────

    public function create(Request $request): Response
    {
        $tahun      = (int) $request->session()->get('tahun_anggaran');
        $user       = $request->user();
        $roleName   = $user->role?->name ?? '';
        $isKeuangan = in_array($roleName, ['keuangan', 'admin']);

        $anggaranList = Anggaran::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->where('tahun', $tahun)
                ->orWhereHas('subKegiatan.dpaAnggaran', fn ($d) =>
                    $d->where('tahun_anggaran', $tahun)
                )
            )
            ->select('id', 'kode_rekening', 'nama_rekening', 'pagu', 'sisa')
            ->orderBy('kode_rekening')
            ->get();

        return Inertia::render('belanja-langsung/Create', [
            'anggaranList' => $anggaranList,
            'jenisOptions' => BelanjaLangsung::$jenisLabel,
            'tahun'        => $tahun,
            'isKeuangan'   => $isKeuangan,             // ← TAMBAHAN
            'threshold'    => (float) (\App\Models\AppSetting::getValue('threshold_reimburse') ?? 1000000), // ← TAMBAHAN
        ]);
    }

    // ── Store ──────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'anggaran_id'     => ['required', 'exists:anggaran,id'],
            'no_nota'         => ['nullable', 'string', 'max:100'],
            'tanggal_belanja' => ['required', 'date'],
            'uraian'          => ['required', 'string', 'max:1000'],
            'jenis'           => ['required', 'in:atk,konsumsi,transport,materai,fotokopi,kebersihan,lainnya'],
            'nominal'         => ['required', 'numeric', 'min:100'],
            'catatan'         => ['nullable', 'string', 'max:500'],
            'file_nota'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'tanggal_dibayar' => ['nullable', 'date'],
            'langsung_ajukan' => ['nullable', 'boolean'],
        ]);

        $tahun     = (int) $request->session()->get('tahun_anggaran');
        $nominal   = (float) $validated['nominal'];
        $threshold = (float) (\App\Models\AppSetting::getValue('threshold_reimburse') ?? 1000000);
        $roleName  = $request->user()->role?->name ?? '';
        $isKeuangan = in_array($roleName, ['keuangan', 'admin']);
        $isSarana   = $roleName === 'sarana_umum';

        // Tentukan status awal berdasarkan role + nominal
        if ($isKeuangan && $nominal < $threshold) {
            // Keuangan input langsung → langsung dibayar
            $status         = 'dibayar';
            $tanggalDibayar = $validated['tanggal_dibayar'] ?? now()->toDateString();
            $dibayarOleh    = $request->user()->id;
            $kirimTembusan  = true;
        } elseif ($nominal < $threshold) {
            // Sarana/umum nominal kecil → skip PPTK, langsung ke Keuangan
            $status         = 'disetujui';
            $tanggalDibayar = null;
            $dibayarOleh    = null;
            $kirimTembusan  = false;
        } else {
            // Nominal besar → wajib lewat PPTK
            $status         = $validated['langsung_ajukan'] ? 'diajukan' : 'draft';
            $tanggalDibayar = null;
            $dibayarOleh    = null;
            $kirimTembusan  = false;
        }

        $data = [
            'anggaran_id'     => $validated['anggaran_id'],
            'pembelanja_id'   => $request->user()->id,
            'no_nota'         => $validated['no_nota'] ?? null,
            'tanggal_belanja' => $validated['tanggal_belanja'],
            'uraian'          => $validated['uraian'],
            'jenis'           => $validated['jenis'],
            'nominal'         => $nominal,
            'catatan'         => $validated['catatan'] ?? null,
            'tahun_anggaran'  => $tahun,
            'status'          => $status,
            'tanggal_dibayar' => $tanggalDibayar,
            'dibayar_oleh'    => $dibayarOleh,
        ];

        if ($request->hasFile('file_nota')) {
            $data['file_nota'] = $request->file('file_nota')
                ->store('belanja-langsung/nota', 'public');
        }

        $belanja = \App\Models\BelanjaLangsung::create($data);

        // Kirim tembusan ke semua PPTK
        if ($kirimTembusan) {
            $pptk = \App\Models\User::query()
                ->whereHas('role', fn ($q) => $q->where('name', 'pptk'))
                ->where('is_active', true)
                ->get();

            foreach ($pptk as $p) {
                $p->notify(new \App\Notifications\BelanjaLangsungTembusanNotification($belanja));
            }
        }

        $pesan = match ($status) {
            'dibayar'   => 'Nota berhasil dicatat sebagai sudah dibayar. Tembusan dikirim ke PPTK.',
            'disetujui' => 'Nota berhasil diajukan langsung ke Keuangan (nominal di bawah threshold).',
            'diajukan'  => 'Nota berhasil diajukan ke PPTK untuk disetujui.',
            default     => 'Nota berhasil disimpan sebagai draft.',
        };

        return redirect()
            ->route('belanja-langsung.index')
            ->with('success', $pesan);
    }

    // ── Edit ───────────────────────────────────────────────────────

    public function edit(Request $request, BelanjaLangsung $belanjaLangsung): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && $belanjaLangsung->pembelanja_id !== $user->id) {
            abort(403);
        }

        if (! in_array($belanjaLangsung->status, ['draft', 'ditolak'])) {
            return redirect()
                ->route('belanja-langsung.index')
                ->with('error', 'Nota ini tidak bisa diedit pada status saat ini.');
        }

        $tahun = (int) $request->session()->get('tahun_anggaran');

        $anggaranList = Anggaran::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->where('tahun', $tahun)
                ->orWhereHas('subKegiatan.dpaAnggaran', fn ($d) =>
                    $d->where('tahun_anggaran', $tahun)
                )
                ->orWhere('id', $belanjaLangsung->anggaran_id)
            )
            ->select('id', 'kode_rekening', 'nama_rekening', 'pagu', 'sisa')
            ->orderBy('kode_rekening')
            ->get();

        return Inertia::render('belanja-langsung/Edit', [
            'belanja'      => $belanjaLangsung,
            'anggaranList' => $anggaranList,
            'jenisOptions' => BelanjaLangsung::$jenisLabel,
        ]);
    }

    // ── Update ─────────────────────────────────────────────────────

    public function update(Request $request, BelanjaLangsung $belanjaLangsung): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && $belanjaLangsung->pembelanja_id !== $user->id) {
            abort(403);
        }

        if (! in_array($belanjaLangsung->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'Nota tidak bisa diubah pada status ini.');
        }

        $validated = $request->validate([
            'anggaran_id'     => ['required', 'exists:anggaran,id'],
            'no_nota'         => ['nullable', 'string', 'max:100'],
            'tanggal_belanja' => ['required', 'date'],
            'uraian'          => ['required', 'string', 'max:1000'],
            'jenis'           => ['required', 'in:atk,konsumsi,transport,materai,fotokopi,kebersihan,lainnya'],
            'nominal'         => ['required', 'numeric', 'min:100'],
            'catatan'         => ['nullable', 'string', 'max:500'],
            'file_nota'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'langsung_ajukan' => ['nullable', 'boolean'],
        ]);

        $data = collect($validated)->except(['file_nota', 'langsung_ajukan'])->toArray();
        $data['status'] = $validated['langsung_ajukan'] ? 'diajukan' : 'draft';

        if ($request->hasFile('file_nota')) {
            if ($belanjaLangsung->file_nota) {
                Storage::disk('public')->delete($belanjaLangsung->file_nota);
            }
            $data['file_nota'] = $request->file('file_nota')
                ->store('belanja-langsung/nota', 'public');
        }

        $belanjaLangsung->update($data);

        return redirect()
            ->route('belanja-langsung.index')
            ->with('success', 'Nota berhasil diperbarui.');
    }

    // ── Ajukan ─────────────────────────────────────────────────────

    public function ajukan(Request $request, BelanjaLangsung $belanjaLangsung): RedirectResponse
    {
        if ($belanjaLangsung->status !== 'draft') {
            return back()->with('error', 'Nota sudah diajukan sebelumnya.');
        }

        $belanjaLangsung->update(['status' => 'diajukan']);

        return back()->with('success', 'Nota berhasil diajukan ke PPTK.');
    }

    // ── Approve (PPTK) ─────────────────────────────────────────────

    public function approve(Request $request, BelanjaLangsung $belanjaLangsung): RedirectResponse
    {
        $request->validate([
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        if ($belanjaLangsung->status !== 'diajukan') {
            return back()->with('error', 'Status nota tidak valid untuk disetujui.');
        }

        $belanjaLangsung->update([
            'status'      => 'disetujui',
            'approver_id' => $request->user()->id,
            'catatan'     => $request->input('catatan'),
        ]);

        return back()->with('success', 'Nota disetujui. Keuangan bisa memproses pembayaran.');
    }

    // ── Reject (PPTK) ──────────────────────────────────────────────

    public function reject(Request $request, BelanjaLangsung $belanjaLangsung): RedirectResponse
    {
        $request->validate([
            'catatan_penolakan' => ['required', 'string', 'max:500'],
        ]);

        if ($belanjaLangsung->status !== 'diajukan') {
            return back()->with('error', 'Status nota tidak valid untuk ditolak.');
        }

        $belanjaLangsung->update([
            'status'             => 'ditolak',
            'approver_id'        => $request->user()->id,
            'catatan_penolakan'  => $request->input('catatan_penolakan'),
        ]);

        return back()->with('success', 'Nota ditolak. Pembelanja dapat memperbaiki dan mengajukan ulang.');
    }

    // ── Bayar (Keuangan) ───────────────────────────────────────────

    public function bayar(Request $request, BelanjaLangsung $belanjaLangsung): RedirectResponse
    {
        $request->validate([
            'tanggal_dibayar' => ['required', 'date'],
        ]);

        if ($belanjaLangsung->status !== 'disetujui') {
            return back()->with('error', 'Nota belum disetujui PPTK.');
        }

        $belanjaLangsung->update([
            'status'          => 'dibayar',
            'dibayar_oleh'    => $request->user()->id,
            'tanggal_dibayar' => $request->input('tanggal_dibayar'),
        ]);

        return back()->with('success', 'Pembayaran reimburse berhasil dicatat.');
    }

    // ── Destroy ────────────────────────────────────────────────────

    public function destroy(Request $request, BelanjaLangsung $belanjaLangsung): RedirectResponse
    {
        if (! $request->user()->isAdmin() && $belanjaLangsung->pembelanja_id !== $request->user()->id) {
            abort(403);
        }

        if ($belanjaLangsung->status !== 'draft') {
            return back()->with('error', 'Hanya nota berstatus draft yang bisa dihapus.');
        }

        if ($belanjaLangsung->file_nota) {
            Storage::disk('public')->delete($belanjaLangsung->file_nota);
        }

        $belanjaLangsung->delete();

        return redirect()
            ->route('belanja-langsung.index')
            ->with('success', 'Nota berhasil dihapus.');
    }
}