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
            auth()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'tahun_anggaran' => 'Silakan login dan pilih tahun anggaran terlebih dahulu.',
                ]);
        }

        return $next($request);
    }
}