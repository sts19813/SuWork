<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\AirbnbFinancialEvent;
use App\Models\Charge;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AirbnbIntegrationController extends Controller
{
    public function availableProperties(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'include_claimed' => ['nullable', 'boolean'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $properties = Property::query()
            ->with(['type:id,name', 'zone:id,name'])
            ->whereNull('archived_at')
            ->when(
                ! $request->boolean('include_claimed'),
                fn (Builder $query) => $query->whereNull('airbnb_managed_at'),
            )
            ->whereNull('tenant_id')
            ->whereDoesntHave('charges', fn (Builder $query) => $query->whereIn('status', [
                Charge::STATUS_PENDING,
                Charge::STATUS_PARTIAL,
                Charge::STATUS_IN_VALIDATION,
            ]))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $like = "%{$search}%";

                    $inner
                        ->where('internal_name', 'like', $like)
                        ->orWhere('internal_reference', 'like', $like)
                        ->orWhere('full_address', 'like', $like)
                        ->orWhere('uuid', 'like', $like);
                });
            })
            ->orderBy('internal_name')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $properties->map(fn (Property $property): array => $this->propertyPayload($property))->values(),
        ]);
    }

    public function claimProperty(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'external_property_id' => ['required', 'string', 'max:120'],
            'external_property_code' => ['nullable', 'string', 'max:120'],
            'external_property_name' => ['nullable', 'string', 'max:190'],
        ]);

        $property = DB::transaction(function () use ($property, $validated): Property {
            $locked = Property::query()->whereKey($property->id)->lockForUpdate()->firstOrFail();

            if (
                filled($locked->airbnb_external_property_id)
                && $locked->airbnb_external_property_id !== $validated['external_property_id']
            ) {
                abort(409, 'La propiedad ya está reclamada por otra propiedad de Airbnb.');
            }

            if (filled($locked->airbnb_external_property_id)) {
                Property::withoutEvents(function () use ($locked, $validated): void {
                    $locked->forceFill([
                        'airbnb_external_property_code' => $validated['external_property_code'] ?? $locked->airbnb_external_property_code,
                        'airbnb_external_property_name' => $validated['external_property_name'] ?? $locked->airbnb_external_property_name,
                    ])->save();
                });

                return $locked;
            }

            if ($locked->tenant_id) {
                abort(422, 'La propiedad tiene un inquilino asignado y no puede reclamarse para Airbnb.');
            }

            $hasOpenCharges = $locked->charges()
                ->whereIn('status', [
                    Charge::STATUS_PENDING,
                    Charge::STATUS_PARTIAL,
                    Charge::STATUS_IN_VALIDATION,
                ])
                ->exists();

            if ($hasOpenCharges) {
                abort(422, 'La propiedad tiene cobranza abierta y no puede reclamarse para Airbnb.');
            }

            Property::withoutEvents(function () use ($locked, $validated): void {
                $locked->forceFill([
                    'status' => Property::STATUS_BLOCKED,
                    'airbnb_managed_at' => now(),
                    'airbnb_external_property_id' => $validated['external_property_id'],
                    'airbnb_external_property_code' => $validated['external_property_code'] ?? null,
                    'airbnb_external_property_name' => $validated['external_property_name'] ?? null,
                ])->save();
            });

            return $locked;
        });

        return response()->json([
            'data' => $this->propertyPayload($property->refresh()),
        ]);
    }

    public function releaseProperty(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'external_property_id' => ['required', 'string', 'max:120'],
        ]);

        $property = DB::transaction(function () use ($property, $validated): Property {
            $locked = Property::query()->whereKey($property->id)->lockForUpdate()->firstOrFail();

            if (! filled($locked->airbnb_external_property_id)) {
                return $locked;
            }

            if ($locked->airbnb_external_property_id !== $validated['external_property_id']) {
                abort(409, 'La propiedad está reclamada por otra propiedad de Airbnb.');
            }

            Property::withoutEvents(function () use ($locked): void {
                $locked->forceFill([
                    'status' => $locked->status === Property::STATUS_BLOCKED
                        ? Property::STATUS_AVAILABLE
                        : $locked->status,
                    'airbnb_managed_at' => null,
                    'airbnb_external_property_id' => null,
                    'airbnb_external_property_code' => null,
                    'airbnb_external_property_name' => null,
                ])->save();
            });

            return $locked;
        });

        return response()->json([
            'data' => $this->propertyPayload($property->refresh()),
        ]);
    }

    public function storeFinancialEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_uuid' => ['required', 'uuid', 'exists:properties,uuid'],
            'external_event_id' => ['required', 'string', 'max:120'],
            'event_type' => ['required', 'string', 'max:60'],
            'external_reservation_id' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'occurred_on' => ['nullable', 'date'],
            'payload' => ['nullable', 'array'],
        ]);

        $property = Property::query()
            ->where('uuid', $validated['property_uuid'])
            ->firstOrFail();

        $event = AirbnbFinancialEvent::query()->firstOrNew([
            'external_event_id' => $validated['external_event_id'],
        ]);
        $created = ! $event->exists;

        $event->fill([
            'property_id' => $property->id,
            'event_type' => $validated['event_type'],
            'external_reservation_id' => $validated['external_reservation_id'] ?? null,
            'amount' => $validated['amount'],
            'currency' => strtoupper((string) ($validated['currency'] ?? 'MXN')),
            'occurred_on' => $validated['occurred_on'] ?? null,
            'payload' => $validated['payload'] ?? null,
        ]);
        $event->save();

        return response()->json([
            'data' => [
                'id' => $event->id,
                'external_event_id' => $event->external_event_id,
                'created' => $created,
            ],
        ], $created ? 201 : 200);
    }

    private function propertyPayload(Property $property): array
    {
        return [
            'uuid' => $property->uuid,
            'internal_name' => $property->internal_name,
            'internal_reference' => $property->internal_reference,
            'full_address' => $property->full_address,
            'status' => $property->status,
            'status_label' => $property->status_label,
            'type' => $property->type?->name,
            'zone' => $property->zone?->name ?: $property->zone_text,
            'monthly_rent_price' => $property->monthly_rent_price,
            'airbnb_managed_at' => $property->airbnb_managed_at?->toISOString(),
            'airbnb_external_property_id' => $property->airbnb_external_property_id,
            'airbnb_external_property_code' => $property->airbnb_external_property_code,
            'airbnb_external_property_name' => $property->airbnb_external_property_name,
        ];
    }
}
