<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
   public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    'id'           => $request->user()->id,
                    'name'         => $request->user()->name,
                    'email'        => $request->user()->email,
                    'avatar'       => $request->user()->avatar,
                    'nip'          => $request->user()->nip,
                    'jabatan'      => $request->user()->jabatan,
                    'role'         => $request->user()->role?->name,
                    'role_label'   => $request->user()->role?->display_name,
                    'unit_kerja'   => $request->user()->unitKerja?->nama,
                    'is_admin'     => $request->user()->isAdmin(),
                ] : null,
            ],
            'tahunAnggaranAktif' => $request->session()->get('tahun_anggaran'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',
 
            // ── TAMBAHAN: Notifikasi unread count ───────────────────
            // Hanya query kalau user login; 0 kalau tidak.
            // count() sangat cepat, tidak perlu khawatir performa.
            'notifUnreadCount' => $request->user()
                ? $request->user()->unreadNotifications()->count()
                : 0,
        ];
    }

}
