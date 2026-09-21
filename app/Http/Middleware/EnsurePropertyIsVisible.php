<?php

namespace App\Http\Middleware;

use App\Models\Property;
use App\Support\PropertyVisibility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePropertyIsVisible
{
    public function __construct(private readonly PropertyVisibility $propertyVisibility)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $property = $request->route('property');

        if ($property instanceof Property) {
            abort_unless($this->propertyVisibility->canView($request->user(), $property), 403);
        }

        return $next($request);
    }
}
