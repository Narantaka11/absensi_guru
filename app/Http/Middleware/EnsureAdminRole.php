<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware khusus API — mengembalikan JSON 403, bukan redirect.
 * Dipakai pada grup route /api/v1/admin/*.
 */
class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin atau kepala sekolah yang dapat mengakses endpoint ini.',
                'data'    => [],
                'errors'  => null,
            ], 403);
        }

        return $next($request);
    }
}
