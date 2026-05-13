<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware reusable untuk pengecekan role.
 *
 * Pemakaian di route:
 *   Route::get('/realisasi', ...)->middleware('role:admin,perencanaan,pptk');
 *
 * Pemakaian di controller (Laravel 11 HasMiddleware):
 *   public static function middleware(): array {
 *       return [new Middleware('role:admin,perencanaan')];
 *   }
 *
 * Menggantikan pola manual:
 *   $allowedRoles = ['admin', 'perencanaan', 'pptk'];
 *   if (! in_array($userRoleName, $allowedRoles)) { abort(403); }
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Admin selalu lolos
        if ($user->isAdmin()) {
            return $next($request);
        }

        $userRole = $user->role?->name ?? '';

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}