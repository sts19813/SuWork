<?php

namespace App\Http\Controllers;

use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\Property;
use App\Models\PropertyInventoryArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function updateItem(Request $request, Property $property, InventoryCheck $check, InventoryCheckItem $item): RedirectResponse
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

        return back()->with('success', 'Elemento actualizado correctamente.');
    }

    public function addItem(Request $request, Property $property, InventoryCheck $check): RedirectResponse
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
            abort(404, 'Elemento no encontrado');
        }

        // Check if item already exists in this check
        if ($check->items()->where('property_inventory_item_id', $inventoryItem->id)->exists()) {
            return back()->with('error', 'Este elemento ya está en el checklist.');
        }

        $check->items()->create([
            'property_inventory_item_id' => $inventoryItem->id,
            'item_name' => $inventoryItem->name,
            'status' => 'pending',
        ]);

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
}
