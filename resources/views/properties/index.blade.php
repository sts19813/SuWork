@extends('layouts.app')

@section('title', 'Propiedades | SuWork')

@section('content')
    @php
        $canManagePropertyAdvisors = auth()->user()?->hasRole('administrador')
            || auth()->user()?->hasRole('admin')
            || auth()->user()?->can('propiedades.asignar_asesores');
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
                                    $assignedAdvisorNames = $assignedAdvisors->pluck('name')->implode(' ');
                                    $primaryAdvisor = $assignedAdvisors->first();
                                    $primaryAdvisorInitials = $primaryAdvisor
                                        ? collect(explode(' ', trim((string) $primaryAdvisor->name)))
                                            ->filter()
                                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                            ->take(2)
                                            ->implode('')
                                        : '';
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
                                    <td data-search="{{ $assignedAdvisorNames ?: 'Sin asesor' }}">
                                        @if ($canManagePropertyAdvisors)
                                            <span class="dropup dropdown maintenance-inline-dropdown maintenance-provider-dropdown" data-property-advisor-action>
                                                <button class="maintenance-provider-trigger dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    aria-label="Cambiar asesor responsable de {{ $property->internal_name }}">
                                                    @if ($primaryAdvisor)
                                                        <span class="maintenance-avatar">{{ $primaryAdvisorInitials ?: 'A' }}</span>
                                                        <span class="min-w-0">
                                                            <span class="maintenance-cell-title">{{ $primaryAdvisor->name }}</span>
                                                            <span class="maintenance-cell-subtitle">
                                                                {{ $primaryAdvisor->email ?: 'Asesor responsable' }}
                                                                @if ($assignedAdvisors->count() > 1)
                                                                    · +{{ $assignedAdvisors->count() - 1 }}
                                                                @endif
                                                            </span>
                                                        </span>
                                                    @else
                                                        <span class="maintenance-cell-icon"><i class="bi bi-person-plus"></i></span>
                                                        <span class="maintenance-cell-title text-warning">Sin asesor</span>
                                                    @endif
                                                </button>
                                                <div class="dropdown-menu maintenance-inline-menu maintenance-provider-menu">
                                                    <form method="POST" action="{{ route('properties.update.advisors', $property) }}" class="js-property-advisors-inline-form" data-no-ajax>
                                                        @csrf
                                                        @method('PUT')
                                                        <button class="dropdown-item maintenance-provider-option {{ $assignedAdvisors->isEmpty() ? 'active' : '' }}"
                                                            type="submit" {{ $assignedAdvisors->isEmpty() ? 'disabled' : '' }}
                                                            data-property-name="{{ $property->internal_name }}"
                                                            data-advisor-name="Sin asesor">
                                                            <span class="maintenance-cell-icon"><i class="bi bi-person-dash"></i></span>
                                                            <span class="min-w-0">
                                                                <span class="maintenance-cell-title">Sin asesor</span>
                                                                <span class="maintenance-cell-subtitle">Quitar asesor actual</span>
                                                            </span>
                                                        </button>
                                                    </form>
                                                    @foreach ($availableAdvisors as $advisor)
                                                        @php
                                                            $advisorInitials = collect(explode(' ', trim((string) $advisor->name)))
                                                                ->filter()
                                                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                                                ->take(2)
                                                                ->implode('');
                                                        @endphp
                                                        <form method="POST" action="{{ route('properties.update.advisors', $property) }}" class="js-property-advisors-inline-form" data-no-ajax>
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="advisor_user_ids[]" value="{{ $advisor->id }}">
                                                            <button class="dropdown-item maintenance-provider-option {{ $assignedAdvisorIds->contains($advisor->id) && $assignedAdvisorIds->count() === 1 ? 'active' : '' }}"
                                                                type="submit" {{ $assignedAdvisorIds->contains($advisor->id) && $assignedAdvisorIds->count() === 1 ? 'disabled' : '' }}
                                                                data-property-name="{{ $property->internal_name }}"
                                                                data-advisor-name="{{ $advisor->name }}">
                                                                <span class="maintenance-avatar">{{ $advisorInitials ?: 'A' }}</span>
                                                                <span class="min-w-0">
                                                                    <span class="maintenance-cell-title">{{ $advisor->name }}</span>
                                                                    <span class="maintenance-cell-subtitle">{{ $advisor->email ?: 'Asesor' }}</span>
                                                                </span>
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </span>
                                        @else
                                            <div class="d-flex flex-wrap gap-1">
                                                @forelse ($assignedAdvisors as $advisor)
                                                    <span class="badge badge-light-primary">{{ $advisor->name }}</span>
                                                @empty
                                                    <span class="text-muted">Sin asesor</span>
                                                @endforelse
                                            </div>
                                        @endif
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
                pageLength: 30,
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

            if (window.bootstrap?.Dropdown) {
                const openAdvisorMenus = new Set();
                const positionAdvisorMenu = (trigger, menu) => {
                    const margin = 12;
                    const triggerRect = trigger.getBoundingClientRect();
                    const menuWidth = menu.offsetWidth;
                    const menuHeight = menu.offsetHeight;
                    const left = Math.min(
                        Math.max(triggerRect.left, margin),
                        Math.max(margin, window.innerWidth - menuWidth - margin),
                    );
                    const top = Math.max(margin, triggerRect.top - menuHeight - margin);

                    menu.style.position = 'fixed';
                    menu.style.inset = 'auto auto auto auto';
                    menu.style.left = `${left}px`;
                    menu.style.top = `${top}px`;
                    menu.style.transform = 'none';
                };
                const repositionOpenAdvisorMenus = () => {
                    openAdvisorMenus.forEach((trigger) => {
                        const menu = trigger.__propertyAdvisorMenu;

                        if (menu?.classList.contains('show')) {
                            positionAdvisorMenu(trigger, menu);
                        }
                    });
                };

                tableElement.querySelectorAll('[data-property-advisor-action] [data-bs-toggle="dropdown"]').forEach((trigger) => {
                    const dropdown = trigger.closest('[data-property-advisor-action]');
                    const menu = dropdown?.querySelector('.dropdown-menu');

                    if (!dropdown || !menu) {
                        return;
                    }

                    const originalParent = menu.parentElement;
                    const originalNextSibling = menu.nextSibling;

                    trigger.__propertyAdvisorMenu = menu;
                    window.bootstrap.Dropdown.getOrCreateInstance(trigger, {
                        display: 'static',
                    });

                    trigger.addEventListener('show.bs.dropdown', () => {
                        document.body.appendChild(menu);
                        menu.classList.add('property-advisor-menu-portal');
                        openAdvisorMenus.add(trigger);
                    });

                    trigger.addEventListener('shown.bs.dropdown', () => {
                        positionAdvisorMenu(trigger, menu);
                    });

                    trigger.addEventListener('hidden.bs.dropdown', () => {
                        openAdvisorMenus.delete(trigger);
                        menu.classList.remove('property-advisor-menu-portal');
                        menu.removeAttribute('style');

                        if (originalNextSibling?.parentElement === originalParent) {
                            originalParent.insertBefore(menu, originalNextSibling);
                        } else {
                            originalParent.appendChild(menu);
                        }
                    });
                });

                window.addEventListener('resize', repositionOpenAdvisorMenus);
                window.addEventListener('scroll', repositionOpenAdvisorMenus, true);
            }

            document.querySelectorAll('.js-property-advisors-inline-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitButton = form.querySelector('[type="submit"]');
                    if (submitButton?.disabled) {
                        return;
                    }

                    const propertyName = submitButton?.dataset.propertyName || 'esta propiedad';
                    const advisorName = submitButton?.dataset.advisorName || 'Sin asesor';
                    const message = advisorName !== 'Sin asesor'
                        ? `${propertyName} quedará asignada a ${advisorName}.`
                        : 'La propiedad quedará sin asesores responsables.';

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            icon: 'question',
                            title: '¿Guardar asignación?',
                            text: message,
                            showCancelButton: true,
                            confirmButtonText: 'Sí, guardar',
                            cancelButtonText: 'Revisar',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-primary',
                                cancelButton: 'btn btn-light',
                            },
                        });

                        if (!result.isConfirmed) {
                            return;
                        }
                    } else if (!window.confirm(message)) {
                        return;
                    }

                    if (submitButton) {
                        submitButton.disabled = true;
                    }

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
                        setTimeout(() => window.location.reload(), 450);
                    } catch (error) {
                        window.SuWorkToast?.fire('danger', error.message || 'No fue posible guardar la asignación.');
                    } finally {
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    }
                });
            });
        })();
    </script>
@endpush
