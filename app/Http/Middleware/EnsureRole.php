<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(...$roles)) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_FORBIDDEN_ROLE',
                'message' => 'Anda tidak memiliki hak akses untuk menggunakan fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}

