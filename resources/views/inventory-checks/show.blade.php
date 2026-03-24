@extends('layouts.app')

@section('title', ($check->type === 'entry' ? 'Check de Entrada' : 'Check de Salida') . ' | ' . $property->internal_name . ' | SuWork')

@section('content')
    <div class="py-10 inventory-check-show">
        <style>
            .inventory-thumb {
                max-width: 150px !important;
                max-height: 150px !important;
                object-fit: cover;
            }
            .inventory-photo-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
        </style>
        <div class="mb-8">
            <a href="{{ route('inventory-checks.index', $property) }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver al inventario
            </a>
        </div>

        <div class="row g-6 mb-8">
            <div class="col-lg-8">
                <h1 class="mb-1 fw-bold">
                    {{ $check->type === 'entry' ? '✓ Check de Entrada' : '✕ Check de Salida' }}
                </h1>
                <div class="d-flex gap-3 align-items-center">
                    <span class="badge {{ $check->status === 'completed' ? 'badge-success' : 'badge-warning' }}">
                        {{ $check->status === 'completed' ? 'Completado' : 'En progreso' }}
                    </span>
                    <span class="text-muted">{{ $check->created_at->format('d/m/Y H:i') }}</span>
                    @if ($check->tenant)
                        <span class="text-primary">Inquilino: <strong>{{ $check->tenant->full_name }}</strong></span>
                    @endif
                </div>
            </div>
            <div class="col-lg-4 text-end">
                @if ($check->status === 'draft')
                    <form method="POST" action="{{ route('inventory-checks.complete', [$property, $check]) }}" 
                          style="display: inline;" onsubmit="return confirm('¿Confirmar que el check está completo?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">
                            <i class="ki-outline ki-check-circle fs-4 me-2"></i> Completar Check
                        </button>
                    </form>
                @else
                    <span class="badge badge-success badge-lg">Completado</span>
                @endif
            </div>
        </div>

        @if ($check->notes)
            <div class="alert alert-light-info mb-8">
                <strong>Notas:</strong>
                <p class="mb-0 mt-2">{{ $check->notes }}</p>
            </div>
        @endif

        <!-- Items de Check -->
        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">
                    Elementos ({{ $check->items->count() }})
                    <span class="badge badge-success ms-2">{{ $check->items->where('status', 'ok')->count() }} ✓</span>
                    <span class="badge badge-danger ms-1">{{ $check->items->where('status', 'damaged')->count() }} ✗</span>
                    <span class="badge badge-warning ms-1">{{ $check->items->where('status', 'missing')->count() }} ⊘</span>
                    <span class="badge badge-secondary ms-1">{{ $check->items->where('status', 'pending')->count() }} ⏳</span>
                </h3>
            </div>
            <div class="card-body pt-0">
                @forelse ($check->items->groupBy(fn($item) => $item->inventoryItem?->area->name ?? 'Sin área') as $areaName => $items)
                    <div class="mb-8">
                        <h5 class="mb-4 fw-bold text-primary">{{ $areaName }}</h5>

                        <div class="d-flex flex-column gap-4">
                            @foreach ($items as $checkItem)
                                <div class="border rounded p-4 {{ $checkItem->status !== 'pending' ? 'bg-light-' . ($checkItem->status === 'ok' ? 'success' : ($checkItem->status === 'damaged' ? 'danger' : 'warning')) : '' }}">
                                    <div class="row g-4">
                                        <div class="col-lg-auto">
                                            @if ($checkItem->photo_path)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($checkItem->photo_path) }}"
                                                    class="rounded inventory-thumb cursor-pointer"
                                                    alt="{{ $checkItem->item_name }}"
                                                    onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($checkItem->photo_path) }}';"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#photoModal">
                                            @elseif ($checkItem->inventoryItem?->photos->isNotEmpty())
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($checkItem->inventoryItem->photos->first()->latestVersion->file_path) }}"
                                                    class="rounded inventory-thumb cursor-pointer opacity-50"
                                                    alt="{{ $checkItem->item_name }}"
                                                    title="Foto original (no validada)"
                                                    onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($checkItem->inventoryItem->photos->first()->latestVersion->file_path) }}';"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#photoModal">
                                            @else
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center inventory-thumb">
                                                    <span class="text-muted">Sin foto</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-lg">
                                            <div class="row g-3">
                                                <div class="col-lg-4">
                                                    <strong>{{ $checkItem->item_name }}</strong>
                                                </div>
                                                @if ($check->status === 'draft')
                                                    <div class="col-lg-8">
                                                        <form method="POST" action="{{ route('inventory-checks.update-item', [$property, $check, $checkItem]) }}" class="row g-2">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="col-lg-5">
                                                                <select name="status" class="form-select form-select-sm" required>
                                                                    <option value="pending" {{ $checkItem->status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                                                    <option value="ok" {{ $checkItem->status === 'ok' ? 'selected' : '' }}>✓ OK</option>
                                                                    <option value="damaged" {{ $checkItem->status === 'damaged' ? 'selected' : '' }}>✗ Dañado</option>
                                                                    <option value="missing" {{ $checkItem->status === 'missing' ? 'selected' : '' }}>⊘ Faltante</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-lg-7">
                                                                <input type="text" name="notes" class="form-control form-control-sm"
                                                                    placeholder="Notas"
                                                                    value="{{ $checkItem->notes ?? '' }}">
                                                            </div>
                                                            <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <div class="col-lg-8">
                                                        <div class="row g-2">
                                                            <div class="col-lg-5">
                                                                <span class="badge {{ $checkItem->status === 'ok' ? 'badge-success' : ($checkItem->status === 'damaged' ? 'badge-danger' : 'badge-warning') }}">
                                                                    {{ $checkItem->status === 'ok' ? '✓ OK' : ($checkItem->status === 'damaged' ? '✗ Dañado' : '⊘ Faltante') }}
                                                                </span>
                                                            </div>
                                                            <div class="col-lg-7">
                                                                @if ($checkItem->notes)
                                                                    <small class="text-muted">{{ $checkItem->notes }}</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if ($check->status === 'draft' && (auth()->id() === $check->created_by))
                                        <div class="row g-2 mt-3">
                                            <div class="col">
                                                <form method="POST" action="{{ route('inventory-checks.remove-item', [$property, $check, $checkItem]) }}" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger"
                                                        onclick="return confirm('¿Remover este elemento del check?')">
                                                        <i class="ki-outline ki-trash fs-7"></i> Remover
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="alert alert-light-warning mb-0">No hay elementos en este check.</div>
                @endforelse

                @if ($check->status === 'draft' && (auth()->id() === $check->created_by))
                    <hr class="my-8">
                    <h5 class="mb-4 fw-bold">Agregar elementos</h5>
                    <form method="POST" action="{{ route('inventory-checks.add-item', [$property, $check]) }}" class="row g-3">
                        @csrf
                        <div class="col-lg-8">
                            <select name="property_inventory_item_id" class="form-select" required>
                                <option value="">-- Seleccionar elemento --</option>
                                @php
                                    $allItems = $property->inventoryAreas
                                        ->flatMap(fn($area) => $area->items)
                                        ->filter(fn($item) => !$check->items->contains('property_inventory_item_id', $item->id));
                                @endphp
                                @foreach ($property->inventoryAreas as $area)
                                    @php
                                        $areaItems = $area->items->filter(fn($item) => !$check->items->contains('property_inventory_item_id', $item->id));
                                    @endphp
                                    @if ($areaItems->isNotEmpty())
                                        <optgroup label="{{ $area->name }}">
                                            @foreach ($areaItems as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <button type="submit" class="btn btn-light-primary w-100">
                                <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-8">
            <a href="{{ route('inventory-checks.index', $property) }}" class="btn btn-light">
                Volver al inventario
            </a>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto del elemento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhotoImg" src="" alt="Foto" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>
@endsection
