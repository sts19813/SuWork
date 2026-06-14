@extends('layouts.app')

@section('title', 'Propiedades | SuWork')

@section('content')
    @php
        $canManagePropertyAdvisors = auth()->user()?->hasRole('administrador')
            || auth()->user()?->hasRole('admin')
            || auth()->user()?->can('usuarios.gestionar');
    @endphp

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
            @unless ($isAdvisorUser)
                <a href="{{ route('properties.create') }}" class="btn btn-primary fw-bold">
                    <i class="ki-outline ki-plus fs-4 me-1"></i> Nueva Propiedad
                </a>
            @endunless
        </div>

        <div class="card mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('properties.index') }}" class="row g-6">
                    @if ($isAdvisorUser)
                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Vista</label>
                            <select name="property_scope" class="form-select">
                                <option value="mine" {{ $propertyScope !== 'all' ? 'selected' : '' }}>Mis propiedades</option>
                                <option value="all" {{ $propertyScope === 'all' ? 'selected' : '' }}>Todas las propiedades</option>
                            </select>
                        </div>
                    @endif
                    <div class="col-lg-{{ $isAdvisorUser ? '3' : '4' }}">
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
                    <div class="col-lg-{{ $isAdvisorUser ? '3' : '4' }}">
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
                    <div class="col-lg-{{ $isAdvisorUser ? '3' : '4' }}">
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
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Asesores responsables</label>
                        <select name="advisor_user_id" class="form-select">
                            <option value="">Todos los asesores</option>
                            @foreach ($availableAdvisors as $advisor)
                                <option value="{{ $advisor->id }}" {{ (string) ($filters['advisor_user_id'] ?? '') === (string) $advisor->id ? 'selected' : '' }}>
                                    {{ $advisor->name }}{{ $advisor->email ? ' · ' . $advisor->email : '' }}
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
                            placeholder="Nombre, tipo, zona, estado, inquilino, asesor..."
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
                                <th class="min-w-180px">Asesores responsables</th>
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
                                    $assignedAdvisorIds = $property->advisors->pluck('id')
                                        ->push($property->advisor_user_id)
                                        ->filter()
                                        ->unique()
                                        ->values();
                                    $assignedAdvisors = $availableAdvisors->whereIn('id', $assignedAdvisorIds);
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
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse ($assignedAdvisors as $advisor)
                                                <span class="badge badge-light-primary">{{ $advisor->name }}</span>
                                            @empty
                                                <span class="text-muted">Sin asesor</span>
                                            @endforelse
                                        </div>
                                    </td>
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
                                            @if ($canManagePropertyAdvisors)
                                                <button type="button" class="btn btn-xs btn-light-info" data-bs-toggle="modal" data-bs-target="#propertyAdvisorsModal-{{ $property->id }}">
                                                    Asesores
                                                </button>
                                            @endif

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
                                    <td colspan="10" class="text-center py-16 text-muted" data-empty-row="true">
                                        Aún no hay propiedades registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($canManagePropertyAdvisors)
            @foreach ($properties as $property)
                @php
                    $assignedAdvisorIds = $property->advisors->pluck('id')
                        ->push($property->advisor_user_id)
                        ->filter()
                        ->unique()
                        ->map(fn ($advisorId) => (int) $advisorId)
                        ->values()
                        ->all();
                @endphp
                <div class="modal fade" id="propertyAdvisorsModal-{{ $property->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('properties.update.advisors', $property) }}" class="js-property-advisors-form" data-no-ajax>
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <div>
                                        <h5 class="modal-title">Asesores responsables</h5>
                                        <div class="text-muted fs-7">{{ $property->internal_name }}</div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        @forelse ($availableAdvisors as $advisor)
                                            <div class="col-md-6">
                                                <label class="form-check form-switch form-check-custom form-check-solid border rounded p-4 h-100">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="advisor_user_ids[]"
                                                        value="{{ $advisor->id }}"
                                                        {{ in_array((int) $advisor->id, $assignedAdvisorIds, true) ? 'checked' : '' }}
                                                    >
                                                    <span class="form-check-label">
                                                        <span class="d-block fw-semibold">{{ $advisor->name }}</span>
                                                        <span class="d-block text-muted fs-8">{{ $advisor->email }}</span>
                                                    </span>
                                                </label>
                                            </div>
                                        @empty
                                            <div class="col-12">
                                                <div class="alert alert-warning mb-0">No hay usuarios activos disponibles para asignar.</div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar asesores</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
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
                        targets: [0, 9],
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

            document.querySelectorAll('.js-property-advisors-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const checkedCount = form.querySelectorAll('input[name="advisor_user_ids[]"]:checked').length;
                    const message = checkedCount
                        ? `Se asignarán ${checkedCount} asesor(es) responsable(s) a esta propiedad.`
                        : 'La propiedad quedará sin asesores responsables.';

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            icon: 'question',
                            title: '¿Guardar asignación?',
                            text: message,
                            showCancelButton: true,
                            confirmButtonText: 'Sí, guardar',
                            cancelButtonText: 'Revisar',
                        });

                        if (!result.isConfirmed) {
                            return;
                        }
                    } else if (!window.confirm(message)) {
                        return;
                    }

                    const submitButton = form.querySelector('button[type="submit"]');
                    submitButton?.setAttribute('disabled', 'disabled');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                            credentials: 'same-origin',
                        });
                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok || payload.success === false) {
                            const firstError = Object.values(payload.errors || {}).flat()[0];
                            window.SuWorkToast?.fire('danger', firstError || payload.message || 'No fue posible guardar la asignación.');
                            return;
                        }

                        window.SuWorkToast?.fire('success', payload.message || 'Asignación guardada.');
                        window.location.reload();
                    } catch (error) {
                        window.SuWorkToast?.fire('danger', error.message || 'No fue posible guardar la asignación.');
                    } finally {
                        submitButton?.removeAttribute('disabled');
                    }
                });
            });
        })();
    </script>
@endpush
