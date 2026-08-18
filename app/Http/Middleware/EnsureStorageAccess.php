<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStorageAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $canAccessStorage = $user && (
            $user->hasRole('administrador')
            || $user->hasRole('admin')
            || $user->hasRole('tecnico')
            || $user->hasRole('technician')
        );

        abort_unless($canAccessStorage, 403);

        return $next($request);
    }
}
