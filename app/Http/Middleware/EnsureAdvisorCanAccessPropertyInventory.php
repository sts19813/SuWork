<?php

namespace App\Http\Middleware;

use App\Models\Property;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdvisorCanAccessPropertyInventory
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isAdmin = $user && ($user->hasRole('administrador') || $user->hasRole('admin'));
        $isAdvisor = $user
            && ! $isAdmin
            && (
                $user->hasAnyRole(['asesores', 'asesor', 'advisor'])
                || $user->can('propiedades.ver_propias')
            );

        if (! $isAdvisor) {
            return $next($request);
        }

        $property = $request->route('property');
        if (! $property instanceof Property) {
            abort(404);
        }

        $isAssigned = (int) $property->advisor_user_id === (int) $user->id
            || $property->advisors()->whereKey($user->id)->exists();

        abort_unless($isAssigned, 403);

        return $next($request);
    }
}
