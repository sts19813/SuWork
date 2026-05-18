@extends('layouts.app')

@section('title', 'Mantenimiento | SuWork')

@section('content')
    <div class="py-10">
        @if (session('success'))
            <div class="alert alert-success mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mb-6">{{ session('error') }}</div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
            <div>
                <h1 class="mb-1 fw-bold">Mantenimiento</h1>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if (!$isTenant)
                    <button
                        class="btn btn-light"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#maintenanceFiltersCollapse"
                        aria-expanded="false"
                        aria-controls="maintenanceFiltersCollapse"
                    >
                        Filtros
                    </button>
                    @if ($canManageProviders)
                        <a class="btn btn-light-primary" href="{{ route('maintenance.technicians.index') }}">+ Nuevo técnico</a>
                    @endif
                @endif
                @if ($canCreateTicket)
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMaintenanceTicketModal">+ Nuevo ticket</button>
                @endif
            </div>
        </div>

        @if (!$isTenant)
        <div class="collapse mb-6" id="maintenanceFiltersCollapse">
            <div class="card">
                <div class="card-body">
                    <form class="row g-4 align-items-end" method="GET" action="{{ route('maintenance.index') }}">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" name="q" value="{{ $search }}" placeholder="Título, folio, propiedad">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Propiedad</label>
                            <select class="form-select" name="property">
                                <option value="">Todas</option>
                                @foreach ($properties as $property)
                                    <option value="{{ $property->uuid }}" {{ $selectedProperty?->uuid === $property->uuid ? 'selected' : '' }}>
                                        {{ $property->internal_name }}{{ $property->internal_reference ? ' - '.$property->internal_reference : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="status">
                                @foreach ($statusOptions as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" {{ $status === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Prioridad</label>
                            <select class="form-select" name="priority">
                                @foreach ($priorityOptions as $priorityKey => $priorityLabel)
                                    <option value="{{ $priorityKey }}" {{ $priority === $priorityKey ? 'selected' : '' }}>{{ $priorityLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="category">
                                @foreach ($categoryOptions as $categoryKey => $categoryLabel)
                                    <option value="{{ $categoryKey }}" {{ $category === $categoryKey ? 'selected' : '' }}>{{ $categoryLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-primary w-100">Buscar</button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Desde</label>
                            <input type="date" class="form-control" name="from" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hasta</label>
                            <input type="date" class="form-control" name="to" value="{{ $dateTo }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a class="btn btn-light w-100" href="{{ route('maintenance.index', ['tab' => $activeTab]) }}">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-5 mb-6">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted mb-1">Total incidencias</div>
                        <div class="fs-2 fw-bold">{{ number_format((int) ($metrics['total'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted mb-1">Tickets pendientes</div>
                        <div class="fs-2 fw-bold">{{ number_format((int) ($metrics['pending'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted mb-1">Tickets urgentes</div>
                        <div class="fs-2 fw-bold">{{ number_format((int) ($metrics['urgent'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted mb-1">Tickets en proceso</div>
                        <div class="fs-2 fw-bold">{{ number_format((int) ($metrics['in_progress'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted mb-1">Tickets completados</div>
                        <div class="fs-2 fw-bold">{{ number_format((int) ($metrics['completed'] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted mb-1">Promedio resolución (h)</div>
                        <div class="fs-2 fw-bold">{{ $metrics['avg_resolution_hours'] !== null ? number_format((float) $metrics['avg_resolution_hours'], 2) : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="card mb-6">
            <div class="card-header border-0 pb-0">
                <ul class="nav nav-tabs nav-line-tabs">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'activos' ? 'active' : '' }}" href="{{ route('maintenance.index', array_merge(request()->except(['page', 'tab']), ['tab' => 'activos'])) }}">Activos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'completados' ? 'active' : '' }}" href="{{ route('maintenance.index', array_merge(request()->except(['page', 'tab']), ['tab' => 'completados'])) }}">Completados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'cancelados' ? 'active' : '' }}" href="{{ route('maintenance.index', array_merge(request()->except(['page', 'tab']), ['tab' => 'cancelados'])) }}">Cancelados</a>
                    </li>
                </ul>
            </div>
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted">
                                <th>Folio</th>
                                <th>Ticket</th>
                                <th>Solicitado fecha</th>
                                <th>Propiedad</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Técnico/Proveedor</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td class="fw-semibold">{{ $ticket->reference ?: \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($ticket->uuid, 0, 8)) }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $ticket->title }}</div>
                                        <div class="text-muted fs-8">{{ \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }}</div>
                                    </td>
                                    <td>{{ $ticket->reported_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $ticket->property?->internal_name ?? '-' }}</div>
                                        <div class="text-muted fs-8">{{ $ticket->property?->internal_reference ?: '-' }}</div>
                                    </td>
                                    <td>{{ \App\Models\MaintenanceTicket::CATEGORY_LABELS[$ticket->category] ?? $ticket->category }}</td>
                                    <td><span class="badge badge-light">{{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}</span></td>
                                    <td>
                                        @if ($ticket->currentProvider)
                                            <div class="fw-semibold">{{ $ticket->currentProvider->name }}</div>
                                            <div class="text-muted fs-8">{{ \App\Models\MaintenanceProvider::TYPE_LABELS[$ticket->currentProvider->type] ?? $ticket->currentProvider->type }}</div>
                                        @else
                                            <span class="text-muted">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('maintenance.show', $ticket) }}" class="btn btn-sm btn-light-primary">Abrir</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-12 text-muted">No hay tickets de mantenimiento.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $tickets->links() }}
            </div>
        </div>

        @if (!$isTenant)
        <div class="row g-5 mb-6">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Propiedades con más incidencias</h3>
                    </div>
                    <div class="card-body">
                        @forelse ($metrics['top_properties'] as $item)
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <div>
                                    <div class="fw-semibold">{{ $item->property?->internal_name ?? 'Sin propiedad' }}</div>
                                    <div class="text-muted fs-8">{{ $item->property?->internal_reference ?: '-' }}</div>
                                </div>
                                <div class="fw-bold">{{ $item->total }}</div>
                            </div>
                        @empty
                            <div class="text-muted">Sin datos</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Actividades del equipo</h3>
                    </div>
                    <div class="card-body">
                        @forelse ($calendarItems as $item)
                            <div class="py-3 border-bottom">
                                <div class="row g-2">
                                    <div class="col-md-7">
                                        <div class="fw-semibold">Técnico: {{ $item->currentProvider?->name ?? 'Sin asignar' }}</div>
                                    </div>
                                    <div class="col-md-5 text-md-end">
                                        <span class="fw-semibold">Folio: {{ $item->reference ?: \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->uuid, 0, 8)) }}</span>
                                    </div>
                                    <div class="col-md-7">
                                        <span class="fw-semibold">Fecha programada:</span> {{ $item->scheduled_visit_at?->format('d/m/Y H:i') ?: '-' }}
                                    </div>
                                    <div class="col-md-5 text-md-end">
                                        <span class="fw-semibold">Propiedad:</span> {{ $item->property?->internal_name ?? '-' }}
                                    </div>
                                    <div class="col-12">
                                        <span class="fw-semibold">Ticket:</span> {{ $item->title }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">Sin datos</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if (!$isTenant)
        <div class="card mb-6 d-none">
            <div class="card-header">
                <h3 class="card-title">Calendario interno de mantenimiento</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted">
                                <th>Visita programada</th>
                                <th>Ticket</th>
                                <th>Propiedad</th>
                                <th>Técnico/Proveedor</th>
                                <th>Disponibilidad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($calendarItems as $item)
                                <tr>
                                    <td>{{ $item->scheduled_visit_at?->format('d/m/Y H:i') }}</td>
                                    <td><a href="{{ route('maintenance.show', $item) }}">{{ $item->title }}</a></td>
                                    <td>{{ $item->property?->internal_name ?? '-' }}</td>
                                    <td>{{ $item->currentProvider?->name ?? 'Sin asignar' }}</td>
                                    <td>{{ $item->currentProvider?->availability ?? '-' }}</td>
                                    <td>{{ \App\Models\MaintenanceTicket::STATUS_LABELS[$item->status] ?? $item->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-10">No hay visitas programadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if ($canManageProviders)
            <div class="card d-none">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Catálogo de técnicos/proveedores</h3>
                    <button class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createProviderModal">Nuevo</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr class="text-muted">
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Contacto</th>
                                    <th>Cuenta sistema</th>
                                    <th>Especialidad</th>
                                    <th>Costo promedio</th>
                                    <th>Calificación</th>
                                    <th>Disponibilidad</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($providers as $provider)
                                    <tr>
                                        <td>{{ $provider->name }}</td>
                                        <td>{{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}</td>
                                        <td>
                                            <div>{{ $provider->email ?: '-' }}</div>
                                            <div class="text-muted fs-8">{{ $provider->phone ?: '-' }}</div>
                                        </td>
                                        <td>
                                            @if ($provider->user)
                                                <div class="fw-semibold">{{ $provider->user->name }}</div>
                                                <div class="text-muted fs-8">{{ $provider->user->email }}</div>
                                            @else
                                                <span class="text-muted">Sin cuenta</span>
                                            @endif
                                        </td>
                                        <td>{{ $provider->specialty ?: '-' }}</td>
                                        <td>{{ $provider->average_cost !== null ? '$'.number_format((float) $provider->average_cost, 2) : '-' }}</td>
                                        <td>{{ $provider->rating !== null ? number_format((float) $provider->rating, 2) : '-' }}</td>
                                        <td>{{ $provider->availability ?: '-' }}</td>
                                        <td>{{ $provider->is_active ? 'Activo' : 'Inactivo' }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#editProviderModal-{{ $provider->id }}">
                                                Editar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-10">No hay técnicos/proveedores.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

@if ($canCreateTicket)
    <div class="modal fade"
        id="createMaintenanceTicketModal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content"
                style="height: 90vh; max-height: 90vh; overflow: hidden;">

                <form method="POST"
                    action="{{ route('maintenance.store') }}"
                    enctype="multipart/form-data"
                    class="d-flex flex-column h-100">

                    @csrf

                    {{-- HEADER --}}
                    <div class="modal-header flex-shrink-0">
                        <h3 class="modal-title">
                            Nuevo ticket de mantenimiento
                        </h3>

                        <button type="button"
                            class="btn btn-icon btn-sm btn-light"
                            data-bs-dismiss="modal">
                            ×
                        </button>
                    </div>

                    {{-- BODY CON SCROLL INTERNO --}}
                    <div class="modal-body overflow-auto">
                        <div class="row g-4">

                            @if ($isTenant)

                                @if ($properties->count() === 1)
                                    <div class="col-12">
                                        <label class="form-label required">
                                            Propiedad
                                        </label>

                                        <input class="form-control"
                                            type="text"
                                            value="{{ $properties->first()->internal_name }}{{ $properties->first()->internal_reference ? ' - '.$properties->first()->internal_reference : '' }}"
                                            disabled>

                                        <input type="hidden"
                                            name="property_id"
                                            value="{{ $properties->first()->id }}">
                                    </div>
                                @else
                                    <div class="col-12">
                                        <label class="form-label required">
                                            Propiedad
                                        </label>

                                        <select class="form-select"
                                            name="property_id"
                                            required>

                                            <option value="">
                                                Seleccionar...
                                            </option>

                                            @foreach ($properties as $property)
                                                <option value="{{ $property->id }}"
                                                    {{ old('property_id', $selectedProperty?->id) == $property->id ? 'selected' : '' }}>

                                                    {{ $property->internal_name }}
                                                    {{ $property->internal_reference ? ' - '.$property->internal_reference : '' }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>
                                @endif

                                <div class="col-12">
                                    <label class="form-label required">
                                        Título
                                    </label>

                                    <input class="form-control"
                                        type="text"
                                        name="title"
                                        value="{{ old('title') }}"
                                        maxlength="190"
                                        required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Descripción
                                    </label>

                                    <textarea class="form-control"
                                        rows="4"
                                        name="description"
                                        maxlength="10000">{{ old('description') }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label required">
                                        Evidencia
                                    </label>

                                    <input class="form-control"
                                        type="file"
                                        name="files[]"
                                        multiple
                                        required>
                                </div>

                            @else

                                <div class="col-md-6">
                                    <label class="form-label required">
                                        Propiedad
                                    </label>

                                    <select class="form-select"
                                        name="property_id"
                                        required>

                                        <option value="">
                                            Seleccionar...
                                        </option>

                                        @foreach ($properties as $property)
                                            <option value="{{ $property->id }}"
                                                {{ old('property_id', $selectedProperty?->id) == $property->id ? 'selected' : '' }}>

                                                {{ $property->internal_name }}
                                                {{ $property->internal_reference ? ' - '.$property->internal_reference : '' }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">
                                        Categoría
                                    </label>

                                    <select class="form-select"
                                        name="category"
                                        required>

                                        @foreach (\App\Models\MaintenanceTicket::CATEGORY_LABELS as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ old('category') === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">
                                        Prioridad
                                    </label>

                                    <select class="form-select"
                                        name="priority"
                                        required>

                                        @foreach (\App\Models\MaintenanceTicket::PRIORITY_LABELS as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ old('priority') === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label required">
                                        Nombre del ticket
                                    </label>

                                    <input class="form-control"
                                        type="text"
                                        name="title"
                                        value="{{ old('title') }}"
                                        maxlength="190"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Referencia
                                    </label>

                                    <input class="form-control"
                                        type="text"
                                        name="reference"
                                        value="{{ old('reference') }}"
                                        maxlength="190">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">
                                        Ubicación exacta
                                    </label>

                                    <input class="form-control"
                                        type="text"
                                        name="exact_location"
                                        value="{{ old('exact_location') }}"
                                        maxlength="255"
                                        required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">
                                        Fecha del reporte
                                    </label>

                                    <input class="form-control"
                                        type="datetime-local"
                                        name="reported_at"
                                        value="{{ old('reported_at', now()->format('Y-m-d\\TH:i')) }}"
                                        required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">
                                        Visita programada
                                    </label>

                                    <input class="form-control"
                                        type="datetime-local"
                                        name="scheduled_visit_at"
                                        value="{{ old('scheduled_visit_at') }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Quién paga
                                    </label>

                                    <select class="form-select"
                                        name="payer">

                                        <option value="">
                                            Sin definir
                                        </option>

                                        @foreach (\App\Models\MaintenanceTicket::PAYER_LABELS as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ old('payer') === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Regla
                                    </label>

                                    <select class="form-select"
                                        name="payment_rule">

                                        <option value="">
                                            Sin definir
                                        </option>

                                        @foreach (\App\Models\MaintenanceTicket::PAYMENT_RULE_LABELS as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ old('payment_rule') === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Estado inicial
                                    </label>

                                    <select class="form-select"
                                        name="status">

                                        @foreach (\App\Models\MaintenanceTicket::STATUS_LABELS as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ old('status', 'pendiente') === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label required">
                                        Descripción
                                    </label>

                                    <textarea class="form-control"
                                        rows="4"
                                        name="description"
                                        maxlength="10000"
                                        required>{{ old('description') }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Notas adicionales
                                    </label>

                                    <textarea class="form-control"
                                        rows="3"
                                        name="additional_notes"
                                        maxlength="10000">{{ old('additional_notes') }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Archivos (múltiples)
                                    </label>

                                    <input class="form-control"
                                        type="file"
                                        name="files[]"
                                        multiple>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Notas sobre regla de pago
                                    </label>

                                    <textarea class="form-control"
                                        rows="2"
                                        name="payment_rule_notes"
                                        maxlength="3000">{{ old('payment_rule_notes') }}</textarea>
                                </div>

                            @endif

                        </div>
                    </div>

                    {{-- FOOTER FIJO --}}
                    <div class="modal-footer flex-shrink-0">
                        <button class="btn btn-light"
                            type="button"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button class="btn btn-primary"
                            type="submit">
                            Crear ticket
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endif
    @if ($canManageProviders)
        <div class="modal fade" id="createProviderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('maintenance.providers.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h3 class="modal-title">Nuevo técnico/proveedor</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label required">Tipo</label>
                                    <select class="form-select" name="type" required>
                                        @foreach (\App\Models\MaintenanceProvider::TYPE_LABELS as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label required">Nombre</label>
                                    <input class="form-control" type="text" name="name" maxlength="190" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo</label>
                                    <input class="form-control" type="email" name="email" maxlength="190">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input class="form-control" type="text" name="phone" maxlength="40">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Especialidad</label>
                                    <input class="form-control" type="text" name="specialty" maxlength="190">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Costo promedio</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="average_cost">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Calificación</label>
                                    <input class="form-control" type="number" step="0.01" min="0" max="5" name="rating">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Disponibilidad</label>
                                    <input class="form-control" type="text" name="availability" maxlength="255">
                                </div>
                                <div class="col-12"><hr class="my-1"></div>
                                <div class="col-md-6">
                                    <label class="form-label">Vincular usuario existente</label>
                                    <select class="form-select" name="user_id">
                                        <option value="">Sin vincular</option>
                                        @foreach ($users as $userRow)
                                            <option value="{{ $userRow->id }}">{{ $userRow->name }} · {{ $userRow->email }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" id="create_user_account_new" name="create_user_account">
                                        <label class="form-check-label" for="create_user_account_new">Crear cuenta nueva para técnico</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre cuenta</label>
                                    <input class="form-control" type="text" name="account_name" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Correo cuenta</label>
                                    <input class="form-control" type="email" name="account_email" maxlength="190">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contraseña cuenta</label>
                                    <input class="form-control" type="text" name="account_password" maxlength="120">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" id="send_credentials_email_new" name="send_credentials_email">
                                        <label class="form-check-label" for="send_credentials_email_new">Enviar acceso por correo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($providers as $provider)
            <div class="modal fade" id="editProviderModal-{{ $provider->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('maintenance.providers.update', $provider) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h3 class="modal-title">Editar técnico/proveedor</h3>
                                <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label required">Tipo</label>
                                        <select class="form-select" name="type" required>
                                            @foreach (\App\Models\MaintenanceProvider::TYPE_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ $provider->type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label required">Nombre</label>
                                        <input class="form-control" type="text" name="name" maxlength="190" value="{{ $provider->name }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Correo</label>
                                        <input class="form-control" type="email" name="email" maxlength="190" value="{{ $provider->email }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Teléfono</label>
                                        <input class="form-control" type="text" name="phone" maxlength="40" value="{{ $provider->phone }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Especialidad</label>
                                        <input class="form-control" type="text" name="specialty" maxlength="190" value="{{ $provider->specialty }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Costo promedio</label>
                                        <input class="form-control" type="number" step="0.01" min="0" name="average_cost" value="{{ $provider->average_cost }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Calificación</label>
                                        <input class="form-control" type="number" step="0.01" min="0" max="5" name="rating" value="{{ $provider->rating }}">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Disponibilidad</label>
                                        <input class="form-control" type="text" name="availability" maxlength="255" value="{{ $provider->availability }}">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" name="is_active" id="provider_active_{{ $provider->id }}" {{ $provider->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="provider_active_{{ $provider->id }}">Activo</label>
                                        </div>
                                    </div>
                                    <div class="col-12"><hr class="my-1"></div>
                                    <div class="col-md-6">
                                        <label class="form-label">Vincular usuario existente</label>
                                        <select class="form-select" name="user_id">
                                            <option value="">Sin vincular</option>
                                            @foreach ($users as $userRow)
                                                <option value="{{ $userRow->id }}" {{ (int) $provider->user_id === (int) $userRow->id ? 'selected' : '' }}>
                                                    {{ $userRow->name }} · {{ $userRow->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" id="create_user_account_{{ $provider->id }}" name="create_user_account">
                                            <label class="form-check-label" for="create_user_account_{{ $provider->id }}">Crear cuenta nueva</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre cuenta</label>
                                        <input class="form-control" type="text" name="account_name" maxlength="255" value="{{ $provider->name }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Correo cuenta</label>
                                        <input class="form-control" type="email" name="account_email" maxlength="190">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contraseña cuenta</label>
                                        <input class="form-control" type="text" name="account_password" maxlength="120">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" value="1" id="send_credentials_email_{{ $provider->id }}" name="send_credentials_email">
                                            <label class="form-check-label" for="send_credentials_email_{{ $provider->id }}">Enviar acceso por correo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
@endsection

@push('scripts')
    @if ($errors->createMaintenanceTicket->any())
        <script>
            (() => {
                const modalEl = document.getElementById('createMaintenanceTicketModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
@endpush
