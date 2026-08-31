<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyMapLocationResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PropertyMapController extends Controller
{
    public function index(Request $request): View
    {
        $properties = $this->mapPropertiesQuery()
            ->whereNotNull('map_url')
            ->where('map_url', '<>', '')
            ->whereNotNull('map_latitude')
            ->whereNotNull('map_longitude')
            ->orderBy('internal_name')
            ->get();

        $markers = $properties->map(fn (Property $property) => $this->markerFor($property))->values();

        $statusCounts = $markers
            ->groupBy('status')
            ->map(fn ($items) => $items->count())
            ->all();

        return view('properties.map', [
            'markers' => $markers,
            'totalWithMapUrl' => Property::query()
                ->whereNotNull('map_url')
                ->where('map_url', '<>', '')
                ->count(),
            'pendingCoordinatesCount' => $this->pendingCoordinatesQuery()->count(),
            'statusCounts' => $statusCounts,
        ]);
    }

    public function syncPending(PropertyMapLocationResolver $resolver): JsonResponse
    {
        $pending = $this->pendingCoordinatesQuery()
            ->orderBy('id')
            ->limit(3)
            ->get();

        $resolvedIds = [];

        foreach ($pending as $property) {
            $location = $resolver->resolve((string) $property->map_url);

            $property->forceFill([
                'map_latitude' => $location['latitude'] ?? null,
                'map_longitude' => $location['longitude'] ?? null,
                'map_resolved_url' => $location['resolved_url'] ?? null,
                'map_coordinates_resolved_at' => $location ? now() : null,
                'map_coordinates_checked_at' => now(),
            ])->save();

            if ($location) {
                $resolvedIds[] = $property->id;
            }
        }

        $markers = $this->mapPropertiesQuery()
            ->whereKey($resolvedIds)
            ->get()
            ->map(fn (Property $property) => $this->markerFor($property))
            ->values();

        return response()->json([
            'markers' => $markers,
            'processed' => $pending->count(),
            'resolved' => count($resolvedIds),
            'remaining' => $this->pendingCoordinatesQuery()->count(),
        ]);
    }

    private function mapPropertiesQuery()
    {
        return Property::query()
            ->with(['type', 'zone', 'tenant', 'owners:id,name', 'advisor:id,name'])
            ->withCount([
                'maintenanceTickets as open_tickets_count' => fn ($query) => $query->whereNotIn('status', ['resolved', 'completed', 'cancelled']),
                'charges as pending_charges_count' => fn ($query) => $query->whereIn('status', ['pending', 'overdue', 'partial', 'validating']),
            ]);
    }

    private function pendingCoordinatesQuery()
    {
        return Property::query()
            ->whereNotNull('map_url')
            ->where('map_url', '<>', '')
            ->where(function ($query): void {
                $query->whereNull('map_latitude')->orWhereNull('map_longitude');
            })
            ->whereNull('map_coordinates_checked_at');
    }

    private function markerFor(Property $property): array
    {
        return [
            'id' => $property->id,
            'uuid' => $property->uuid,
            'name' => $property->internal_name,
            'reference' => $property->internal_reference,
            'address' => $property->full_address,
            'type' => $property->type?->name,
            'zone' => $property->zone?->name ?: $property->zone_text,
            'status' => $property->status,
            'status_label' => $property->status_label,
            'status_badge_class' => $property->status_badge_class,
            'latitude' => (float) $property->map_latitude,
            'longitude' => (float) $property->map_longitude,
            'photo_url' => $property->facade_photo_path
                ? Storage::url($property->facade_photo_path)
                : asset('metronic/assets/media/svg/files/blank-image.svg'),
            'map_url' => $property->map_url,
            'show_url' => route('properties.show', $property),
            'edit_url' => route('properties.edit', $property),
            'rent' => $property->monthly_rent_price !== null ? number_format((float) $property->monthly_rent_price, 2) : null,
            'tenant' => $property->tenant?->full_name ?: $property->current_tenant_name,
            'owner_names' => $property->owners->pluck('name')->filter()->values()->all(),
            'advisor' => $property->advisor?->name,
            'contract_starts_at' => $property->contract_starts_at?->format('d/m/Y'),
            'contract_expires_at' => $property->contract_expires_at?->format('d/m/Y'),
            'open_tickets_count' => (int) $property->open_tickets_count,
            'pending_charges_count' => (int) $property->pending_charges_count,
        ];
    }
}
