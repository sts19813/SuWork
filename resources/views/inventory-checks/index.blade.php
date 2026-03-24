@extends('layouts.app')

@section('title', 'Inventario y Check | ' . $property->internal_name . ' | SuWork')

@section('content')
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
    <div class="py-10 inventory-check-module">
        <div class="mb-8">
            <a href="{{ route('properties.show', $property) }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a propiedad
            </a>
        </div>

        <div class="mb-9">
            <h1 class="mb-1 fw-bold">{{ $property->internal_name }} - Inventario</h1>
            <p class="text-muted mb-0">Gestiona el inventario, crea checks de entrada/salida y visualiza el histórico.</p>
        </div>

        <!-- Quick Actions Top -->
        <div class="row g-3 mb-8">
            <div class="col-lg-4">
                <a href="{{ route('inventory-checks.create', [$property, 'entry']) }}" class="btn btn-primary btn-lg w-100">
                    <i class="ki-outline ki-check-circle fs-4 me-2"></i> Check de Entrada
                </a>
            </div>
            <div class="col-lg-4">
                <a href="{{ route('inventory-checks.create', [$property, 'exit']) }}" class="btn btn-danger btn-lg w-100">
                    <i class="ki-outline ki-exit-right fs-4 me-2"></i> Check de Salida
                </a>
            </div>
            <div class="col-lg-4">
                <a href="{{ route('inventory-checks.history', $property) }}" class="btn btn-light-primary btn-lg w-100">
                    <i class="ki-outline ki-clock fs-4 me-2"></i> Ver Histórico
                </a>

            </div>
        </div>

        <div class="row g-6">
            <!-- Inventario -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                        <h3 class="card-title fw-bold mb-0">Inventario de la propiedad</h3>

                        <a href="{{ route('properties.edit', $property) }}?step=5" class="btn btn-light-primary">
                            <i class="ki-outline ki-pencil fs-4 me-1"></i>
                            Editar inventario
                        </a>
                    </div>
                    <div class="card-body pt-0">
                        @if ($property->inventoryAreas->isEmpty())
                            <div class="alert alert-light-info mb-0">No hay inventario capturado todavía.</div>
                        @else
                            <div class="d-flex flex-column gap-6">
                                @foreach ($property->inventoryAreas as $area)
                                    <div class="border rounded p-5">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h4 class="mb-0">{{ $area->name }}</h4>
                                            <div class="d-flex gap-2">

                                                <span class="text-muted">{{ $area->items->count() }} elementos</span>
                                            </div>
                                        </div>

                                        @if ($area->notes)
                                            <p class="text-gray-700 mb-4">{{ $area->notes }}</p>
                                        @endif

                                        @if ($area->photos->isNotEmpty())
                                            <div class="mb-4">
                                                <strong class="d-block mb-3">Fotos del área:</strong>
                                                <div class="inventory-photo-grid">
                                                    @foreach ($area->photos as $photo)
                                                        <div class="position-relative">
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}"
                                                                class="rounded inventory-thumb cursor-pointer" alt="{{ $area->name }}"
                                                                data-bs-toggle="modal" data-bs-target="#photoModal"
                                                                onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}'">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if ($area->items->isNotEmpty())
                                            <div class="table-responsive">
                                                <table class="table table-row-bordered align-middle mb-0">
                                                    <thead>
                                                        <tr class="text-muted text-uppercase fs-8">
                                                            <th>Elemento</th>
                                                            <th>Estado</th>
                                                            <th>Notas</th>
                                                            <th>Foto actual</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($area->items as $item)
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ $item->name }}</strong>
                                                                    <button type="button" class="btn btn-sm btn-light-secondary ms-2"
                                                                        data-bs-toggle="modal" data-bs-target="#editItemModal"
                                                                        onclick="editItem(this)" data-item-id="{{ $item->id }}"
                                                                        data-item-name="{{ $item->name }}" data-area-id="{{ $area->id }}">
                                                                        <i class="ki-outline ki-pencil fs-7"></i> Editar
                                                                    </button>
                                                                </td>
                                                                <td>{{ $item->condition ?: '-' }}</td>
                                                                <td>{{ $item->notes ?: '-' }}</td>
                                                                <td>
                                                                    @if ($item->photos->isNotEmpty())
                                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item->photos->first()->latestVersion->file_path) }}"
                                                                            class="rounded inventory-thumb cursor-pointer"
                                                                            alt="Foto {{ $item->name }}" data-bs-toggle="modal"
                                                                            data-bs-target="#photoModal"
                                                                            onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($item->photos->first()->latestVersion->file_path) }}'">

                                                                    @else
                                                                        <span class="text-muted">Sin foto</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold">Checks activos</h3>
                    </div>
                    <div class="card-body pt-0">
                        @php
                            $activeEntryCheck = $property->inventoryChecks()
                                ->where('type', \App\Models\InventoryCheck::TYPE_ENTRY)
                                ->where('status', \App\Models\InventoryCheck::STATUS_DRAFT)
                                ->latest()
                                ->first();
                            $activeExitCheck = $property->inventoryChecks()
                                ->where('type', \App\Models\InventoryCheck::TYPE_EXIT)
                                ->where('status', \App\Models\InventoryCheck::STATUS_DRAFT)
                                ->latest()
                                ->first();
                        @endphp

                        @if ($activeEntryCheck)
                            <div class="border rounded p-3 mb-3">
                                <strong class="d-block mb-2">Check de Entrada (En progreso)</strong>
                                <p class="text-muted mb-3">{{ $activeEntryCheck->created_at->diffForHumans() }}</p>
                                <a href="{{ route('inventory-checks.show', [$property, $activeEntryCheck]) }}"
                                    class="btn btn-sm btn-primary w-100">
                                    Continuar
                                </a>
                            </div>
                        @endif

                        @if ($activeExitCheck)
                            <div class="border rounded p-3">
                                <strong class="d-block mb-2">Check de Salida (En progreso)</strong>
                                <p class="text-muted mb-3">{{ $activeExitCheck->created_at->diffForHumans() }}</p>
                                <a href="{{ route('inventory-checks.show', [$property, $activeExitCheck]) }}"
                                    class="btn btn-sm btn-danger w-100">
                                    Continuar
                                </a>
                            </div>
                        @endif

                        @if (!$activeEntryCheck && !$activeExitCheck)
                            <p class="text-muted text-center">No hay checks en progreso</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Inventory Management -->
            <div class="col-lg-12 mt-8">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold">Gestión de Inventario</h3>
                        <div class="card-toolbar">
                            <button type="button" id="add-area-btn" class="btn btn-primary w-100 mt-6">
                                <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar área
                            </button>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="inventory-areas-container" class="d-flex flex-column gap-6"></div>

                    </div>
                </div>
            </div>


        </div>
    </div>

    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body text-center">
                    <img id="modalPhotoImg" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <template id="inventory-area-template">
        <div class="border rounded p-6 inventory-area" data-area-index="__AREA_INDEX__" data-next-item-index="1">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <h4 class="mb-0">Área __AREA_NUMBER__</h4>
                <button type="button" class="btn btn-sm btn-light-danger btn-remove-area">Eliminar</button>
            </div>

            <div class="row g-5 mb-6">
                <div class="col-lg-6">
                    <input type="text" class="form-control area-name" placeholder="Nombre del área">
                </div>
                <div class="col-lg-6">
                    <input type="text" class="form-control area-notes" placeholder="Notas">
                </div>
                <div class="col-12">
                    <label class="form-label">Fotos del área (máx 6)</label>
                    <input type="file" class="form-control area-photos" accept="image/*" multiple>
                </div>
            </div>

            <div class="items-container d-flex flex-column gap-4"></div>

            <button type="button" class="btn btn-light-primary w-100 mt-4 btn-add-item">
                + Agregar elemento
            </button>

            <button type="button" class="btn btn-success w-100 mt-3 btn-save-area">
                Guardar área
            </button>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        const itemTemplate = (index) => `
                                        <div class="row g-4 inventory-item">
                                            <div class="col-lg-3">
                                                <input type="text" class="form-control item-name" placeholder="Elemento">
                                            </div>

                                            <div class="col-lg-2">
                                                <select class="form-select item-condition">
                                                    <option value="">Estado</option>
                                                    <option value="bueno">Bueno</option>
                                                    <option value="regular">Regular</option>
                                                    <option value="malo">Malo</option>
                                                </select>
                                            </div>

                                            <div class="col-lg-3">
                                                <input type="text" class="form-control item-notes" placeholder="Notas">
                                            </div>

                                            <div class="col-lg-3">
                                                <input type="file" class="form-control item-photos" multiple>
                                            </div>

                                            <div class="col-lg-1">
                                                <button type="button" class="btn btn-light-danger btn-remove-item">X</button>
                                            </div>
                                        </div>
                                    `;
        document.addEventListener('DOMContentLoaded', () => {

            const container = document.getElementById('inventory-areas-container');
            const areaTemplate = document.getElementById('inventory-area-template').innerHTML;
            let areaIndex = 0;

            document.getElementById('add-area-btn').addEventListener('click', () => {
                const html = areaTemplate
                    .replaceAll('__AREA_INDEX__', areaIndex)
                    .replaceAll('__AREA_NUMBER__', areaIndex + 1);

                container.insertAdjacentHTML('beforeend', html);
                areaIndex++;
            });

            container.addEventListener('click', async (e) => {

                // agregar item
                if (e.target.closest('.btn-add-item')) {
                    const area = e.target.closest('.inventory-area');
                    const itemsContainer = area.querySelector('.items-container');
                    itemsContainer.insertAdjacentHTML('beforeend', itemTemplate());
                }

                // eliminar item
                if (e.target.closest('.btn-remove-item')) {
                    e.target.closest('.inventory-item').remove();
                }

                // eliminar área
                if (e.target.closest('.btn-remove-area')) {
                    e.target.closest('.inventory-area').remove();
                }

                // guardar área + items
                if (e.target.closest('.btn-save-area')) {

                    const area = e.target.closest('.inventory-area');

                    const name = area.querySelector('.area-name').value;
                    const notes = area.querySelector('.area-notes').value;

                    const formData = new FormData();
                    formData.append('name', name);
                    formData.append('notes', notes);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                    const areaPhotos = area.querySelector('.area-photos').files;

                    for (let f of areaPhotos) {
                        formData.append('photos[]', f);
                    }

                    // 1. crear área
                    const res = await fetch('{{ route("inventory.areas.store", $property) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (!data.success) return alert('Error creando área');

                    const areaId = data.area.id;

                    // 2. crear items
                    const items = area.querySelectorAll('.inventory-item');

                    for (let item of items) {

                        const fd = new FormData();
                        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        fd.append('name', item.querySelector('.item-name').value);
                        fd.append('condition', item.querySelector('.item-condition').value);
                        fd.append('notes', item.querySelector('.item-notes').value);

                        const files = item.querySelector('.item-photos').files;
                        for (let f of files) {
                            fd.append('photos[]', f);
                        }

                        const resItem = await fetch(`/propiedades/{{ $property->id }}/inventario/areas/${areaId}/items`, {
                            method: 'POST',
                            body: fd,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!resItem.ok) {
                            const err = await resItem.json();
                            console.error('ITEM ERROR:', err);
                            alert(err.message || 'Error creando item');
                        }
                    }

                    alert('Área guardada correctamente');
                    location.reload();
                }
            });
        });
    </script>
@endpush