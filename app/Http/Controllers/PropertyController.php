<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PropertyOwner;
use App\Models\PropertyType;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PropertyController extends Controller
{
    private const DEFAULT_PROPERTY_TYPES = [
        'Casa',
        'Departamento',
        'Local',
        'Townhouse',
        'Oficina',
    ];

    private const DEFAULT_ZONES = [
        'Montebello',
        'Francisco Montejo',
        'Temozon',
        'Playa',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'property_type_id' => ['nullable', 'integer', 'exists:property_types,id'],
            'status' => ['nullable', Rule::in(array_keys(Property::STATUS_LABELS))],
        ]);

        $propertyTypes = $this->getPropertyTypesCatalog();
        $zones = $this->getZonesCatalog();

        $properties = Property::query()
            ->with(['type', 'zone'])
            ->withCount([
                'documents as incidents_count' => fn ($query) => $query->where('status', PropertyDocument::STATUS_PENDING),
            ])
            ->when($request->filled('zone_id'), fn ($query) => $query->where('zone_id', $request->integer('zone_id')))
            ->when($request->filled('property_type_id'), fn ($query) => $query->where('property_type_id', $request->integer('property_type_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('properties.index', [
            'properties' => $properties,
            'zones' => $zones,
            'propertyTypes' => $propertyTypes,
            'filters' => $filters,
            'statusOptions' => Property::STATUS_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('properties.create', [
            'zones' => $this->getZonesCatalog(),
            'propertyTypes' => $this->getPropertyTypesCatalog(),
            'statusOptions' => [
                Property::STATUS_AVAILABLE => Property::STATUS_LABELS[Property::STATUS_AVAILABLE],
                Property::STATUS_IN_PROCESS => Property::STATUS_LABELS[Property::STATUS_IN_PROCESS],
                Property::STATUS_BLOCKED => Property::STATUS_LABELS[Property::STATUS_BLOCKED],
            ],
            'ownerTypes' => PropertyOwner::OWNER_TYPE_LABELS,
            'paymentMethods' => PropertyOwner::PAYMENT_METHOD_LABELS,
            'requiredDocuments' => PropertyDocument::REQUIRED_DOCUMENTS,
            'defaultAreas' => [
                'Cocina',
                'Recámara principal',
                'Sala',
                'Comedor',
            ],
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $property = DB::transaction(function () use ($request, $validated) {
            $property = Property::create([
                'internal_name' => $validated['internal_name'],
                'internal_reference' => $validated['internal_reference'] ?? null,
                'property_type_id' => $validated['property_type_id'],
                'zone_id' => $validated['zone_id'],
                'full_address' => $validated['full_address'],
                'complex_name' => $validated['complex_name'] ?? null,
                'official_number' => $validated['official_number'] ?? null,
                'unit_number' => $validated['unit_number'] ?? null,
                'status' => $validated['status'],
                'current_tenant_name' => $validated['current_tenant_name'] ?? null,
                'contract_expires_at' => $validated['contract_expires_at'] ?? null,
                'onboarding_step' => 5,
                'created_by' => $request->user()->id,
            ]);

            if ($request->hasFile('facade_photo')) {
                $path = $request->file('facade_photo')->store("properties/{$property->id}/facade", 'public');
                $property->update(['facade_photo_path' => $path]);
            }

            foreach ($validated['owners'] as $ownerData) {
                $property->owners()->create([
                    'name' => $ownerData['name'],
                    'phone' => $ownerData['phone'],
                    'email' => $ownerData['email'],
                    'owner_type' => $ownerData['owner_type'],
                    'bank_name' => $ownerData['bank_name'] ?? null,
                    'clabe' => $ownerData['clabe'] ?? null,
                    'account_holder' => $ownerData['account_holder'] ?? null,
                    'payment_method' => $ownerData['payment_method'] ?? null,
                ]);
            }

            foreach (PropertyDocument::REQUIRED_DOCUMENTS as $documentType => $documentLabel) {
                $filePath = null;
                $status = PropertyDocument::STATUS_PENDING;
                $uploadedAt = null;

                if ($request->hasFile("documents.{$documentType}")) {
                    $filePath = $request->file("documents.{$documentType}")->store("properties/{$property->id}/documents", 'public');
                    $status = PropertyDocument::STATUS_UPLOADED;
                    $uploadedAt = now();
                }

                $property->documents()->create([
                    'document_type' => $documentType,
                    'label' => $documentLabel,
                    'file_path' => $filePath,
                    'status' => $status,
                    'uploaded_at' => $uploadedAt,
                ]);
            }

            foreach ($validated['inventory_areas'] ?? [] as $areaIndex => $areaData) {
                $hasItems = collect($areaData['items'] ?? [])->contains(fn ($item) => filled($item['name'] ?? null));
                $hasPhotos = $request->hasFile("inventory_areas.{$areaIndex}.photos");
                $hasContent = filled($areaData['name'] ?? null) || filled($areaData['notes'] ?? null) || $hasItems || $hasPhotos;

                if (!$hasContent) {
                    continue;
                }

                $area = $property->inventoryAreas()->create([
                    'name' => $areaData['name'] ?? 'Área ' . ($areaIndex + 1),
                    'notes' => $areaData['notes'] ?? null,
                ]);

                foreach ($areaData['items'] ?? [] as $itemData) {
                    if (blank($itemData['name'] ?? null)) {
                        continue;
                    }

                    $area->items()->create([
                        'name' => $itemData['name'],
                        'condition' => $itemData['condition'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                foreach ($request->file("inventory_areas.{$areaIndex}.photos", []) as $photoIndex => $photo) {
                    $filePath = $photo->store("properties/{$property->id}/inventory/{$area->id}", 'public');

                    $area->photos()->create([
                        'file_path' => $filePath,
                        'display_order' => $photoIndex,
                    ]);
                }
            }

            return $property;
        });

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'La propiedad se registró correctamente.');
    }

    public function show(Property $property): View
    {
        $property->load([
            'type',
            'zone',
            'owners',
            'documents',
            'inventoryAreas.items',
            'inventoryAreas.photos',
        ]);

        $documents = collect(PropertyDocument::REQUIRED_DOCUMENTS)
            ->map(function (string $label, string $type) use ($property) {
                return $property->documents->firstWhere('document_type', $type)
                    ?? new PropertyDocument([
                        'document_type' => $type,
                        'label' => $label,
                        'status' => PropertyDocument::STATUS_PENDING,
                    ]);
            });

        return view('properties.show', [
            'property' => $property,
            'documents' => $documents,
        ]);
    }

    private function getPropertyTypesCatalog(): Collection
    {
        foreach (self::DEFAULT_PROPERTY_TYPES as $name) {
            PropertyType::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }

        $order = collect(self::DEFAULT_PROPERTY_TYPES)
            ->mapWithKeys(fn (string $name, int $index) => [Str::slug($name) => $index]);

        return PropertyType::query()
            ->where('is_active', true)
            ->whereIn('slug', $order->keys()->all())
            ->get()
            ->sortBy(fn (PropertyType $type) => $order[$type->slug] ?? 999)
            ->values();
    }

    private function getZonesCatalog(): Collection
    {
        foreach (self::DEFAULT_ZONES as $name) {
            Zone::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }

        $order = collect(self::DEFAULT_ZONES)
            ->mapWithKeys(fn (string $name, int $index) => [Str::slug($name) => $index]);

        return Zone::query()
            ->where('is_active', true)
            ->whereIn('slug', $order->keys()->all())
            ->get()
            ->sortBy(fn (Zone $zone) => $order[$zone->slug] ?? 999)
            ->values();
    }
}
