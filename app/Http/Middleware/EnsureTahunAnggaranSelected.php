<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTahunAnggaranSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('tahun_anggaran')) {
            /**
             * SEBELUM: auth()->logout() + session()->invalidate() + regenerateToken()
             *   Masalah: session()->invalidate() merusak CSRF token di tab browser lain
             *   yang masih terbuka, dan terasa brutal karena tidak ada pesan jelas.
             *
             * SESUDAH: logout biasa (tanpa invalidate) + redirect login dengan pesan.
             *   - user diminta login ulang dan memilih tahun anggaran (sudah ada
             *     di form login, lihat auth/Login.vue + LoginResponse.php)
             *   - tidak ada route baru yang perlu dibuat
             *   - LogoutResponse sudah membersihkan 'tahun_anggaran' dari session
             */
            auth()->logout();

            return redirect()
                ->route('login')
                ->with('warning', 'Sesi Anda telah berakhir. Silakan login ulang dan pilih tahun anggaran.');
        }

        return $next($request);
    }
}