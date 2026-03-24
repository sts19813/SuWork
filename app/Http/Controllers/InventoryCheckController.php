<?php

namespace App\Http\Controllers;

use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\Property;
use App\Models\PropertyInventoryArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\PropertyInventoryItem;
use App\Models\PropertyInventoryItemPhoto;

class InventoryCheckController extends Controller
{
    public function index(Property $property): View
    {
        $property->load([
            'inventoryAreas.items.photos',
            'inventoryChecks.items',
        ]);

        $entryChecks = $property->inventoryChecks()
            ->where('type', InventoryCheck::TYPE_ENTRY)
            ->latest()
            ->paginate(10);

        $exitChecks = $property->inventoryChecks()
            ->where('type', InventoryCheck::TYPE_EXIT)
            ->latest()
            ->paginate(10);

        return view('inventory-checks.index', [
            'property' => $property,
            'entryChecks' => $entryChecks,
            'exitChecks' => $exitChecks,
        ]);
    }

    public function create(Property $property, string $type): View
    {
        if (!in_array($type, [InventoryCheck::TYPE_ENTRY, InventoryCheck::TYPE_EXIT])) {
            abort(400, 'Tipo de check inválido');
        }

        $property->load('inventoryAreas.items.photos');
        
        // Get available tenants for this property
        $tenants = \App\Models\Tenant::all();

        return view('inventory-checks.create', [
            'property' => $property,
            'type' => $type,
            'tenants' => $tenants,
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:entry,exit'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $check = $property->inventoryChecks()->create([
            'type' => $validated['type'],
            'tenant_id' => $validated['tenant_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => InventoryCheck::STATUS_DRAFT,
            'created_by' => $request->user()->id,
        ]);

        // Create items from all inventory items
        $allItems = $property->inventoryAreas()
            ->with('items')
            ->get()
            ->flatMap(fn($area) => $area->items);

        foreach ($allItems as $item) {
            $check->items()->create([
                'property_inventory_item_id' => $item->id,
                'item_name' => $item->name,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('inventory-checks.show', [$property, $check])
            ->with('success', 'Checklist creado correctamente.');
    }

    public function show(Property $property, InventoryCheck $check): View
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $check->load([
            'items.inventoryItem.photos',
            'items.inventoryItem.area',
            'tenant',
            'creator',
        ]);

        return view('inventory-checks.show', [
            'property' => $property,
            'check' => $check,
        ]);
    }

    public function updateItem(Request $request, Property $property, InventoryCheck $check, InventoryCheckItem $item)
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,ok,damaged,missing'],
            'notes' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $data = [
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store(
                "properties/{$property->id}/checks/{$check->id}/items",
                'public'
            );
            $data['photo_path'] = $photoPath;
        }

        $item->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Elemento actualizado correctamente.']);
        }

        return back()->with('success', 'Elemento actualizado correctamente.');
    }

    public function addItem(Request $request, Property $property, InventoryCheck $check)
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'property_inventory_item_id' => ['required', 'exists:property_inventory_items,id'],
        ]);

        // Verify the item belongs to this property
        $inventoryItem = $property->inventoryAreas()
            ->with('items')
            ->get()
            ->flatMap(fn($area) => $area->items)
            ->firstWhere('id', $validated['property_inventory_item_id']);

        if (!$inventoryItem) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Elemento no encontrado'], 404);
            }
            abort(404, 'Elemento no encontrado');
        }

        // Check if item already exists in this check
        if ($check->items()->where('property_inventory_item_id', $inventoryItem->id)->exists()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Este elemento ya está en el checklist.'], 422);
            }
            return back()->with('error', 'Este elemento ya está en el checklist.');
        }

        $check->items()->create([
            'property_inventory_item_id' => $inventoryItem->id,
            'item_name' => $inventoryItem->name,
            'status' => 'pending',
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Elemento agregado al checklist.']);
        }
        return back()->with('success', 'Elemento agregado al checklist.');
    }

    public function removeItem(Property $property, InventoryCheck $check, InventoryCheckItem $item): RedirectResponse
    {
        if ($check->property_id !== $property->id || $item->inventory_check_id !== $check->id) {
            abort(403, 'No autorizado');
        }

        $item->delete();

        return back()->with('success', 'Elemento removido del checklist.');
    }

    public function complete(Request $request, Property $property, InventoryCheck $check): RedirectResponse
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $check->update([
            'status' => InventoryCheck::STATUS_COMPLETED,
            'notes' => $validated['notes'] ?? $check->notes,
            'completed_at' => now(),
        ]);

        return redirect()->route('inventory-checks.show', [$property, $check])
            ->with('success', 'Checklist completado correctamente.');
    }

    public function history(Property $property): View
    {
        $property->load('inventoryChecks');

        $checks = $property->inventoryChecks()
            ->with('items')
            ->latest('completed_at')
            ->paginate(20);

        return view('inventory-checks.history', [
            'property' => $property,
            'checks' => $checks,
        ]);
    }

    public function getItemHistory(Property $property, $itemId)
    {
 
        // Obtener todos los checks completados de esta propiedad
        $completedChecks = $property->inventoryChecks()
            ->where('status', InventoryCheck::STATUS_COMPLETED)
            ->with(['items' => function($query) use ($itemId) {
                $query->where('property_inventory_item_id', $itemId);
            }])
            ->get();

        // Obtener la foto actual del inventario
        $inventoryItem = $property->inventoryAreas()
            ->with('items.photos')
            ->get()
            ->flatMap(fn($area) => $area->items)
            ->firstWhere('id', $itemId);

        $history = [];

        // Agregar foto actual del inventario
        if ($inventoryItem && $inventoryItem->photos->isNotEmpty()) {
            $history[] = [
                'type' => 'inventory',
                'date' => $inventoryItem->created_at->format('d/m/Y H:i'),
                'check_type' => null,
                'status' => 'original',
                'photo_url' => \Illuminate\Support\Facades\Storage::url($inventoryItem->photos->first()->latestVersion->file_path),
                'notes' => null,
            ];
        }

        // Agregar fotos de checks completados
        foreach ($completedChecks as $check) {
            $checkItem = $check->items->first();
            if ($checkItem && $checkItem->photo_path) {
                $history[] = [
                    'type' => 'check',
                    'date' => $check->completed_at->format('d/m/Y H:i'),
                    'check_type' => $check->type,
                    'status' => $checkItem->status,
                    'photo_url' => \Illuminate\Support\Facades\Storage::url($checkItem->photo_path),
                    'notes' => $checkItem->notes,
                ];
            }
        }

        return response()->json($history);
    }

    public function addNewItem(Request $request, Property $property, InventoryCheck $check)
    {
        if ($check->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'area_name' => ['required', 'string', 'max:255'],
            'new_area_name' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        // Determinar el nombre del área
        $areaName = $validated['area_name'] === '__new__' ? $validated['new_area_name'] : $validated['area_name'];

        // Buscar o crear el área
        $area = $property->inventoryAreas()->firstOrCreate(
            ['name' => $areaName],
            ['name' => $areaName]
        );

        // Crear el nuevo elemento
        $inventoryItem = $area->items()->create([
            'name' => $validated['item_name'],
            'condition' => 'Nuevo elemento agregado durante check',
        ]);

        // Subir foto si se proporcionó
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store(
                "properties/{$property->id}/inventory/items",
                'public'
            );

            // Crear registro de foto
            $photo = $inventoryItem->photos()->create([
                'name' => $request->file('photo')->getClientOriginalName(),
                'status' => PropertyInventoryItemPhoto::STATUS_ACTIVE,
            ]);

            // Crear versión de la foto
            $photo->versions()->create([
                'file_path' => $photoPath,
                'file_name' => $request->file('photo')->getClientOriginalName(),
                'mime_type' => $request->file('photo')->getMimeType(),
                'file_size' => $request->file('photo')->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        // Agregar el elemento al check
        $check->items()->create([
            'property_inventory_item_id' => $inventoryItem->id,
            'item_name' => $inventoryItem->name,
            'status' => 'pending',
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Nuevo elemento creado y agregado al checklist.']);
        }
        return back()->with('success', 'Nuevo elemento creado y agregado al checklist.');
    }

    // Inventory Management Methods
    public function storeArea(Request $request, Property $property)
    {
        

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $area = $property->inventoryAreas()->create([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Handle photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filePath = $photo->store("properties/{$property->id}/inventory/{$area->id}", 'public');
                $area->photos()->create([
                    'file_path' => $filePath,
                    'file_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType(),
                    'file_size' => $photo->getSize(),
                ]);
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'area' => $area->load('photos')]);
        }
        return back()->with('success', 'Área creada exitosamente.');
    }

    public function updateArea(Request $request, Property $property, PropertyInventoryArea $area)
    {
        if ($property->user_id !== auth()->id() || $area->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $area->update([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Handle new photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filePath = $photo->store("properties/{$property->id}/inventory/{$area->id}", 'public');
                $area->photos()->create([
                    'file_path' => $filePath,
                    'file_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType(),
                    'file_size' => $photo->getSize(),
                ]);
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'area' => $area->load('photos')]);
        }
        return back()->with('success', 'Área actualizada exitosamente.');
    }

    public function destroyArea(Property $property, PropertyInventoryArea $area)
    {
        if ($property->user_id !== auth()->id() || $area->property_id !== $property->id) {
            abort(403, 'No autorizado');
        }

        $area->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Área eliminada exitosamente.');
    }

    public function storeItem(Request $request, $propertyId, $areaId)
    {
        $property = Property::findOrFail($propertyId);
        $area = $property->inventoryAreas()->findOrFail($areaId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'condition' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $item = $area->items()->create([
            'name' => $validated['name'],
            'condition' => $validated['condition'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Handle photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filePath = $photo->store("properties/{$property->id}/inventory/items/{$item->id}", 'public');

                $photoRecord = $item->photos()->create([
                    'name' => $photo->getClientOriginalName(),
                    'status' => PropertyInventoryItemPhoto::STATUS_ACTIVE,
                ]);

                $photoRecord->versions()->create([
                    'file_path' => $filePath,
                    'file_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType(),
                    'file_size' => $photo->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'item' => $item->load('photos')]);
        }
        return back()->with('success', 'Elemento creado exitosamente.');
    }

    public function updateInventoryItem(Request $request, Property $property, PropertyInventoryArea $area, PropertyInventoryItem $item)
    {
        if ($property->user_id !== auth()->id() || $area->property_id !== $property->id || $item->area_id !== $area->id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'condition' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $item->update([
            'name' => $validated['name'],
            'condition' => $validated['condition'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Handle new photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filePath = $photo->store("properties/{$property->id}/inventory/items/{$item->id}", 'public');

                $photoRecord = $item->photos()->create([
                    'name' => $photo->getClientOriginalName(),
                    'status' => PropertyInventoryItemPhoto::STATUS_ACTIVE,
                ]);

                $photoRecord->versions()->create([
                    'file_path' => $filePath,
                    'file_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType(),
                    'file_size' => $photo->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'item' => $item->load('photos')]);
        }
        return back()->with('success', 'Elemento actualizado exitosamente.');
    }

    public function destroyInventoryItem(Property $property, PropertyInventoryArea $area, PropertyInventoryItem $item)
    {
        if ($property->user_id !== auth()->id() || $area->property_id !== $property->id || $item->area_id !== $area->id) {
            abort(403, 'No autorizado');
        }

        $item->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Elemento eliminado exitosamente.');
    }
}
