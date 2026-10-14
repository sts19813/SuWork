<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictTicketReviewerToMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isReviewer = $user?->hasRole('revisor de tickets');

        if ($isReviewer && ! $request->routeIs('maintenance.index', 'maintenance.show', 'maintenance.validate', 'profile.*')) {
            abort(403);
        }

        return $next($request);
    }
}
