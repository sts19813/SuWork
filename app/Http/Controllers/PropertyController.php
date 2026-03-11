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
        return view('properties.create', $this->formViewData());
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $property = $this->saveProperty($request);

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'La propiedad se registró correctamente.');
    }

    public function edit(Property $property): View
    {
        $property->load([
            'owners',
            'documents',
            'inventoryAreas.items',
            'inventoryAreas.photos',
        ]);

        return view('properties.create', $this->formViewData($property, true));
    }

    public function update(StorePropertyRequest $request, Property $property): RedirectResponse
    {
        $property = $this->saveProperty($request, $property);

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'La propiedad se actualizó correctamente.');
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

    private function formViewData(?Property $property = null, bool $isEdit = false): array
    {
        return [
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
                'Recamara principal',
                'Sala',
                'Comedor',
            ],
            'property' => $property,
            'isEdit' => $isEdit,
        ];
    }

    private function saveProperty(StorePropertyRequest $request, ?Property $property = null): Property
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($request, $validated, $property) {
            $property = $property ?? new Property();

            $property->fill([
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
            ]);

            if (!$property->exists) {
                $property->created_by = $request->user()->id;
            }

            $property->save();

            if ($request->hasFile('facade_photo')) {
                $path = $request->file('facade_photo')->store("properties/{$property->id}/facade", 'public');
                $property->update(['facade_photo_path' => $path]);
            }

            $this->syncOwners($property, $validated['owners']);
            $this->syncDocuments($property, $request);
            $this->syncInventory($property, $validated['inventory_areas'] ?? [], $request);

            return $property->fresh();
        });
    }

    private function syncOwners(Property $property, array $owners): void
    {
        $property->owners()->delete();

        foreach ($owners as $ownerData) {
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
    }

    private function syncDocuments(Property $property, StorePropertyRequest $request): void
    {
        $existingDocuments = $property->documents()->get()->keyBy('document_type');

        foreach (PropertyDocument::REQUIRED_DOCUMENTS as $documentType => $documentLabel) {
            $document = $existingDocuments->get($documentType);

            if ($document) {
                $updates = ['label' => $documentLabel];

                if ($request->hasFile("documents.{$documentType}")) {
                    $updates['file_path'] = $request->file("documents.{$documentType}")
                        ->store("properties/{$property->id}/documents", 'public');
                    $updates['status'] = PropertyDocument::STATUS_UPLOADED;
                    $updates['uploaded_at'] = now();
                }

                $document->update($updates);
                continue;
            }

            $data = [
                'document_type' => $documentType,
                'label' => $documentLabel,
                'status' => PropertyDocument::STATUS_PENDING,
                'uploaded_at' => null,
                'file_path' => null,
            ];

            if ($request->hasFile("documents.{$documentType}")) {
                $data['file_path'] = $request->file("documents.{$documentType}")
                    ->store("properties/{$property->id}/documents", 'public');
                $data['status'] = PropertyDocument::STATUS_UPLOADED;
                $data['uploaded_at'] = now();
            }

            $property->documents()->create($data);
        }

        $property->documents()
            ->whereNotIn('document_type', array_keys(PropertyDocument::REQUIRED_DOCUMENTS))
            ->delete();
    }

    private function syncInventory(Property $property, array $inventoryAreas, StorePropertyRequest $request): void
    {
        $property->inventoryAreas()->delete();

        foreach ($inventoryAreas as $areaIndex => $areaData) {
            $hasItems = collect($areaData['items'] ?? [])->contains(fn ($item) => filled($item['name'] ?? null));
            $hasPhotos = $request->hasFile("inventory_areas.{$areaIndex}.photos");
            $hasContent = filled($areaData['name'] ?? null) || filled($areaData['notes'] ?? null) || $hasItems || $hasPhotos;

            if (!$hasContent) {
                continue;
            }

            $area = $property->inventoryAreas()->create([
                'name' => $areaData['name'] ?? 'Area ' . ($areaIndex + 1),
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

