@extends('layouts.app')

@section('title', 'Propiedades | SuWork')

@section('content')
    <div class="py-10 property-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Propiedades</h1>
                <div class="text-muted fs-6">{{ $properties->count() }} propiedades encontradas</div>
            </div>
            <a href="{{ route('properties.create') }}" class="btn btn-primary fw-bold">
                <i class="ki-outline ki-plus fs-4 me-1"></i> Nueva Propiedad
            </a>
        </div>

        <div class="card mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('properties.index') }}" class="row g-6">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Zona</label>
                        <select name="zone_id" class="form-select">
                            <option value="">Todas las zonas</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}" {{ (string) ($filters['zone_id'] ?? '') === (string) $zone->id ? 'selected' : '' }}>
                                    {{ $zone->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Tipo de propiedad</label>
                        <select name="property_type_id" class="form-select">
                            <option value="">Todos los tipos</option>
                            @foreach ($propertyTypes as $type)
                                <option value="{{ $type->id }}" {{ (string) ($filters['property_type_id'] ?? '') === (string) $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select">
                            <option value="">Todos los estados</option>
                            @foreach ($statusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" {{ (string) ($filters['status'] ?? '') === (string) $statusValue ? 'selected' : '' }}>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-3">
                        <a href="{{ route('properties.index') }}" class="btn btn-light">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body py-0">
                <div class="d-flex justify-content-end py-5">
                    <div class="w-100 w-md-300px">
                        <label for="properties_text_search" class="form-label fw-semibold mb-2">Buscar por texto</label>
                        <input
                            id="properties_text_search"
                            type="text"
                            class="form-control form-control-solid"
                            placeholder="Nombre, tipo, zona, estado, inquilino..."
                        >
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="properties_table" class="table table-row-bordered align-middle gy-5 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase gs-0">
                                <th class="min-w-125px">Foto</th>
                                <th class="min-w-200px">Nombre interno</th>
                                <th class="min-w-140px">Tipo</th>
                                <th class="min-w-140px">Zona</th>
                                <th class="min-w-130px">Estado</th>
                                <th class="min-w-150px">Inquilino</th>
                                <th class="min-w-130px">Contrato vence</th>
                                <th class="min-w-110px">Incidencias</th>
                                <th class="text-end">Opciones</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($properties as $property)
                                @php
                                    $photoUrl = $property->facade_photo_path
                                        ? \Illuminate\Support\Facades\Storage::url($property->facade_photo_path)
                                        : asset('metronic/assets/media/svg/files/blank-image.svg');
                                @endphp
                                <tr>
                                    <td>
                                        <img
                                            src="{{ $photoUrl }}"
                                            class="property-thumb"
                                            alt="{{ $property->internal_name }}"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </td>
                                    <td>
                                        <a href="{{ route('properties.show', $property) }}"
                                            class="text-gray-900 fw-bold text-hover-primary fs-6">
                                            {{ $property->internal_name }}
                                        </a>
                                    </td>
                                    <td>{{ $property->type?->name ?? '-' }}</td>
                                    <td>
                                        @php
                                            $zoneName = $property->zone?->name;
                                            $zoneText = filled($property->zone_text) ? trim((string) $property->zone_text) : null;
                                            $zoneDisplay = $zoneName ?: $zoneText ?: '-';
                                            $showZoneTextDetail = $zoneName && $zoneText && strcasecmp($zoneName, $zoneText) !== 0;
                                        @endphp
                                        <div class="fw-semibold">{{ $zoneDisplay }}</div>
                                        @if ($showZoneTextDetail)
                                            <div class="text-muted fs-8">{{ $zoneText }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $property->status_badge_class }}">{{ $property->status_label }}</span>
                                    </td>
                                    <td>{{ $property->tenant?->full_name ?: ($property->current_tenant_name ?: '-') }}</td>
                                    <td>
                                        {{ $property->contract_expires_at ? $property->contract_expires_at->format('d/m/Y') : '-' }}
                                    </td>
                                    <td>
                                        @if ($property->incidents_count > 0)
                                            <span class="text-danger fw-bold">
                                                <i class="ki-outline ki-information-5 text-danger fs-6"></i>
                                                {{ $property->incidents_count }}
                                            </span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-1">
                                            <a href="{{ route('properties.show', $property) }}"
                                                class="btn btn-xs btn-light-primary">
                                                Ver
                                            </a>

                                            {{--

                                            <a href="{{ route('dossiers.properties.show', $property) }}"
                                                class="btn btn-xs btn-light-info">
                                                Expediente
                                            </a>
                                            <a href="{{ route('inventory-checks.index', $property) }}"
                                                class="btn btn-xs btn-light-warning">
                                                Inventario
                                            </a>
                                            @if ($property->tenant_id)
                                                <a href="{{ route('charges.index', ['property' => $property->uuid]) }}"
                                                    class="btn btn-xs btn-light-success">
                                                    Cobranza
                                                </a>
                                            @endif
                                            <a href="{{ route('properties.edit', $property) }}" class="btn btn-xs btn-primary">
                                                Editar
                                            </a>
                                            --}}
                                            
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-16 text-muted" data-empty-row="true">
                                        Aún no hay propiedades registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const tableElement = document.getElementById('properties_table');
            if (!tableElement || typeof $ === 'undefined' || !$.fn.DataTable) {
                return;
            }

            const emptyCell = tableElement.querySelector('td[data-empty-row="true"]');
            if (emptyCell) {
                emptyCell.closest('tr')?.remove();
            }

            const dataTable = $(tableElement).DataTable({
                dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                pageLength: 10,
                lengthChange: false,
                order: [],
                info: true,
                searching: true,
                language: {
                    search: 'Buscar:',
                    searchPlaceholder: 'Buscar...',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ propiedades',
                    infoEmpty: 'Mostrando 0 a 0 de 0 propiedades',
                    paginate: {
                        first: 'Primera',
                        last: 'Última',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                    emptyTable: 'Aún no hay propiedades registradas.',
                    zeroRecords: 'No se encontraron coincidencias.',
                },
                columnDefs: [
                    {
                        targets: [0, 8],
                        orderable: false,
                    },
                ],
            });

            const textSearchInput = document.getElementById('properties_text_search');
            if (textSearchInput) {
                textSearchInput.addEventListener('keyup', (event) => {
                    dataTable.search(event.target.value).draw();
                });
            }
        })();
    </script>
@endpush
