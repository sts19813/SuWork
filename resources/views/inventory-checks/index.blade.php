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
                <a href="{{ route('inventory-checks.create', [$property, 'entry']) }}"
                    class="btn btn-primary btn-lg w-100">
                    <i class="ki-outline ki-check-circle fs-4 me-2"></i> Check de Entrada
                </a>
            </div>
            <div class="col-lg-4">
                <a href="{{ route('inventory-checks.create', [$property, 'exit']) }}"
                    class="btn btn-danger btn-lg w-100">
                    <i class="ki-outline ki-exit-right fs-4 me-2"></i> Check de Salida
                </a>
            </div>
            <div class="col-lg-4">
                <a href="{{ route('inventory-checks.history', $property) }}"
                    class="btn btn-light-primary btn-lg w-100">
                    <i class="ki-outline ki-clock fs-4 me-2"></i> Ver Histórico
                </a>
            </div>
        </div>

        <div class="row g-6">
            <!-- Inventario -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold">Inventario de la propiedad</h3>
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
                                            <span class="text-muted">{{ $area->items->count() }} elementos</span>
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
                                                                class="rounded inventory-thumb cursor-pointer"
                                                                alt="{{ $area->name }}"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#photoModal"
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
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-light-secondary ms-2"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editItemModal"
                                                                        onclick="editItem(this)"
                                                                        data-item-id="{{ $item->id }}"
                                                                        data-item-name="{{ $item->name }}"
                                                                        data-area-id="{{ $area->id }}">
                                                                        <i class="ki-outline ki-pencil fs-7"></i> Editar
                                                                    </button>
                                                                </td>
                                                                <td>{{ $item->condition ?: '-' }}</td>
                                                                <td>{{ $item->notes ?: '-' }}</td>
                                                                <td>
                                                                    @if ($item->photos->isNotEmpty())
                                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item->photos->first()->latestVersion->file_path) }}"
                                                                            class="rounded inventory-thumb cursor-pointer"
                                                                            alt="Foto {{ $item->name }}"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#photoModal"
                                                                            onclick="document.getElementById('modalPhotoImg').src='{{ \Illuminate\Support\Facades\Storage::url($item->photos->first()->latestVersion->file_path) }}'">
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-light-info ms-2"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#itemHistoryModal"
                                                                            onclick="showItemHistory({{ $item->id }}, '{{ $item->name }}')">
                                                                            <i class="ki-outline ki-clock fs-7"></i> Histórico
                                                                        </button>
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
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto en grande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalPhotoImg" src="" alt="Foto" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Item History Modal -->
    <div class="modal fade" id="itemHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Histórico de <span id="itemHistoryName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="itemHistoryContent" class="d-flex flex-column gap-4">
                        <p class="text-muted">Cargando histórico...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar elemento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Aquí podrías editar el elemento del inventario. La funcionalidad de edición en tiempo real será implementada según necesidad.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function editItem(button) {
            const itemId = button.dataset.itemId;
            const itemName = button.dataset.itemName;
            const areaId = button.dataset.areaId;
            // Implementar lógica de edición aquí
            console.log('Edit item:', itemId, itemName, areaId);
        }

        function showItemHistory(itemId, itemName) {
            // Obtener el histórico de fotos del item desde todos los checks completados
            document.getElementById('itemHistoryName').textContent = itemName;
            const contentDiv = document.getElementById('itemHistoryContent');
            contentDiv.innerHTML = '<p class="text-muted">Cargando histórico...</p>';
            
            // Crear HTML dinámico con las fotos del item desde todos los checks
            // El histórico mostrará cada foto de cada check completado
            const historyHTML = `
                <div class="border rounded p-4 mb-3">
                    <div class="d-flex gap-3 align-items-start">
                        <div>
                            <strong>Foto actual del inventario</strong>
                            <p class="text-muted mb-2">Estado original</p>
                        </div>
                    </div>
                </div>
            `;
            
            contentDiv.innerHTML = historyHTML || '<p class="text-muted">No hay histórico de fotos disponible.</p>';
        }
    </script>
@endpush
