<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictProviderToOperations
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isProvider = $user && ($user->hasRole('proveedor') || $user->hasRole('provider'));

        if ($isProvider && ! $request->routeIs('maintenance.*', 'profile.*')) {
            abort(403);
        }

        return $next($request);
    }
}
