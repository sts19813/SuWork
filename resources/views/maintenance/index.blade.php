@extends('layouts.app')

@section('title', 'Mantenimiento | SuWork')

@section('content')
    <div class="maintenance-module py-8">
        @php
            $calendarEvents = $calendarItems
                ->filter(fn ($item) => $item->scheduled_visit_at)
                ->groupBy(fn ($item) => $item->scheduled_visit_at->format('Y-m-d'))
                ->map(fn ($items, $date) => [
                    'title' => (string) $items->count(),
                    'start' => $date,
                    'allDay' => true,
                    'extendedProps' => ['count' => $items->count()],
                ])
                ->values()
                ->all();

            $statusTone = fn ($value) => match ($value) {
                'completado' => 'green',
                'cancelado' => 'red',
                'en_proceso' => 'purple',
                'programado', 'asignado' => 'blue',
                'pendiente', 'revisado', 'esperando_material', 'reabierto' => 'amber',
                default => 'neutral',
            };
            $priorityTone = fn ($value) => match ($value) {
                'baja' => 'green',
                'media' => 'blue',
                'alta' => 'amber',
                'urgente' => 'red',
                default => 'neutral',
            };
            $canBulkGroupTickets = ($canGroupTickets ?? false) && $activeTab === 'completados';

            $roleTitle = match ($role) {
                'inquilino' => 'Mis reportes de mantenimiento',
                'tecnico' => 'Mis tickets asignados',
                default => 'Mantenimiento',
            };
            $roleSubtitle = match ($role) {
                'inquilino' => 'Consulta tus tickets y levanta nuevos reportes para tus propiedades asignadas.',
                'tecnico' => 'Agenda de campo, evidencias, estados y comunicación del ticket.',
                default => 'Operación diaria de tickets, técnicos, propiedades y visitas programadas.',
            };

            $kpis = [
                ['label' => 'Total', 'value' => number_format((int) ($metrics['total'] ?? 0)), 'sub' => 'Incidencias visibles', 'tone' => '#334155'],
                ['label' => 'Pendientes', 'value' => number_format((int) ($metrics['pending'] ?? 0)), 'sub' => 'Por atender', 'tone' => '#b45309'],
                ['label' => 'Urgentes', 'value' => number_format((int) ($metrics['urgent'] ?? 0)), 'sub' => 'Prioridad urgente', 'tone' => '#b42318'],
                ['label' => 'En proceso', 'value' => number_format((int) ($metrics['in_progress'] ?? 0)), 'sub' => 'Trabajo activo', 'tone' => '#6d28d9'],
                ['label' => 'Completados', 'value' => number_format((int) ($metrics['completed'] ?? 0)), 'sub' => 'Histórico filtrado', 'tone' => '#15803d'],
                [
                    'label' => 'Resolución',
                    'value' => $metrics['avg_resolution_hours'] !== null ? number_format((float) $metrics['avg_resolution_hours'], 2) . 'h' : '-',
                    'sub' => 'Promedio',
                    'tone' => '#1d4ed8',
                ],
            ];
        @endphp

        <div class="maintenance-page">
            @if (session('success'))
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mb-0">{{ session('error') }}</div>
            @endif

            <div class="maintenance-hero">
                <div>
                    <div class="maintenance-kicker">Operaciones</div>
                    <h1 class="maintenance-title">{{ $roleTitle }}</h1>
                    <div class="maintenance-subtitle">
                        {{ $roleSubtitle }}
                        @if ($selectedProperty)
                            <span class="maintenance-chip maintenance-chip-blue ms-2">{{ $selectedProperty->internal_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="maintenance-actions">
                    @if (!$isTenant)
                        @if ($canManageProviders)
                            <a class="maintenance-soft-btn" href="{{ route('maintenance.technicians.index') }}">
                                <i class="bi bi-person-gear"></i> Técnicos
                            </a>
                            <a class="maintenance-soft-btn" href="{{ route('maintenance.providers.index') }}">
                                <i class="bi bi-building"></i> Proveedores
                            </a>
                        @endif
                    @endif
                    @if ($canCreateTicket)
                        <button class="maintenance-primary-btn" data-bs-toggle="modal" data-bs-target="#createMaintenanceTicketModal">
                            <i class="bi bi-plus-lg"></i> Nuevo ticket
                        </button>
                    @endif
                </div>
            </div>

           

            @if (!$isTenant)
                <div class="maintenance-kpi-strip">
                    @foreach ($kpis as $kpi)
                        <div class="maintenance-kpi">
                            <div class="maintenance-kpi-label">
                                <span class="maintenance-kpi-dot" style="background: {{ $kpi['tone'] }}"></span>
                                {{ $kpi['label'] }}
                            </div>
                            <div class="maintenance-kpi-value">{{ $kpi['value'] }}</div>
                            <div class="maintenance-kpi-sub">{{ $kpi['sub'] }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="maintenance-tenant-summary">
                    <div class="maintenance-kpi">
                        <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#334155"></span>Mis tickets</div>
                        <div class="maintenance-kpi-value">{{ number_format((int) ($metrics['total'] ?? 0)) }}</div>
                        <div class="maintenance-kpi-sub">De tus propiedades</div>
                    </div>
                    <div class="maintenance-kpi">
                        <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#b45309"></span>Pendientes</div>
                        <div class="maintenance-kpi-value">{{ number_format((int) ($metrics['pending'] ?? 0)) }}</div>
                        <div class="maintenance-kpi-sub">Por revisar</div>
                    </div>
                    <div class="maintenance-kpi">
                        <div class="maintenance-kpi-label"><span class="maintenance-kpi-dot" style="background:#15803d"></span>Cerrados</div>
                        <div class="maintenance-kpi-value">{{ number_format((int) ($metrics['completed'] ?? 0)) }}</div>
                        <div class="maintenance-kpi-sub">Completados</div>
                    </div>
                </div>
            @endif

             @php
                $hasLegacyFilters = $selectedProperty || $status || $priority || $category || $dateFrom || $dateTo;
                $hasSearchContext = $search || $hasLegacyFilters;
            @endphp

            <div class="maintenance-search-panel">
                <form class="maintenance-search-form" method="GET" action="{{ route('maintenance.index') }}"
                    data-maintenance-live-search data-current-query="{{ $search }}">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <label class="maintenance-search-field" for="maintenance-global-search">
                        <i class="bi bi-search"></i>
                        <input id="maintenance-global-search" type="search" name="q" value="{{ $search }}"
                            autocomplete="off"
                            placeholder="Buscar por folio, propiedad, estado, prioridad, categoría o fecha">
                    </label>
                    <label class="maintenance-property-filter" for="maintenance-property-filter">
                        <span class="visually-hidden">Filtrar por propiedad</span>
                        <select id="maintenance-property-filter" class="form-select" name="property"
                            data-control="select2" data-placeholder="Todas las propiedades" data-allow-clear="true">
                            <option value="">Todas las propiedades</option>
                            @foreach ($filterProperties as $filterProperty)
                                <option value="{{ $filterProperty->uuid }}" {{ (string) $selectedProperty?->uuid === (string) $filterProperty->uuid ? 'selected' : '' }}>
                                    {{ $filterProperty->internal_name }}{{ $filterProperty->internal_reference ? ' · ' . $filterProperty->internal_reference : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </form>
            </div>

            <div class="maintenance-tabs">
                <a class="maintenance-tab {{ $activeTab === 'activos' ? 'active' : '' }}"
                    href="{{ route('maintenance.index', array_merge(request()->except(['page', 'urgent_page', 'scheduled_page', 'unscheduled_page', 'unassigned_page', 'tab']), ['tab' => 'activos'])) }}">
                    Activos
                </a>
                <a class="maintenance-tab {{ $activeTab === 'completados' ? 'active' : '' }}"
                    href="{{ route('maintenance.index', array_merge(request()->except(['page', 'urgent_page', 'scheduled_page', 'unscheduled_page', 'unassigned_page', 'tab']), ['tab' => 'completados'])) }}">
                    Completados
                </a>
                <a class="maintenance-tab {{ $activeTab === 'cancelados' ? 'active' : '' }}"
                    href="{{ route('maintenance.index', array_merge(request()->except(['page', 'urgent_page', 'scheduled_page', 'unscheduled_page', 'unassigned_page', 'tab']), ['tab' => 'cancelados'])) }}">
                    Cancelados
                </a>
            </div>

            <div class="maintenance-layout {{ $isTenant ? 'maintenance-layout-single' : '' }}">
                <div id="maintenance-results" data-maintenance-results>
                <div class="maintenance-worklist">
                    <div class="maintenance-panel">
                        <div class="maintenance-list-toolbar">
                            <div>
                                <div class="maintenance-list-title">{{ $isTenant ? 'Tus tickets' : 'Lista operativa' }}</div>
                                <div class="maintenance-list-count">
                                    Mostrando {{ $visibleTicketsCount }} de {{ $visibleTicketsTotal }} tickets
                                </div>
                            </div>
                            @if ($hasSearchContext)
                                <span class="maintenance-chip maintenance-chip-blue">Búsqueda activa</span>
                            @endif
                        </div>
                    </div>

                    @if ($canBulkGroupTickets)
                        <form id="maintenanceGroupForm" class="maintenance-group-form" method="POST" action="{{ route('maintenance.group') }}">
                            @csrf
                            <label class="maintenance-group-name">
                                <span>Nombre del ticket</span>
                                <input class="form-control" type="text" name="title" maxlength="190" required
                                    placeholder="Ej. Cierre semanal de mantenimiento">
                            </label>
                            <button class="maintenance-primary-btn" type="submit">
                                <i class="bi bi-collection"></i> Agrupar
                            </button>
                        </form>
                        @error('ticket_ids')
                            <div class="alert alert-danger mb-0">{{ $message }}</div>
                        @enderror
                        @error('title')
                            <div class="alert alert-danger mb-0">{{ $message }}</div>
                        @enderror
                    @endif

                    @forelse ($ticketTables as $bucketKey => $table)
                        @php
                            $bucketTickets = $table['tickets'];
                            $bucketTotal = $bucketTickets instanceof \Illuminate\Pagination\LengthAwarePaginator
                                ? $bucketTickets->total()
                                : $bucketTickets->count();
                            $bucketHasPages = $bucketTickets instanceof \Illuminate\Pagination\LengthAwarePaginator
                                && $bucketTickets->hasPages();
                        @endphp
                        <div class="maintenance-group">
                            <div class="maintenance-group-header">
                                <span class="maintenance-group-icon maintenance-chip-{{ $table['tone'] }}">
                                    <i class="bi {{ $table['icon'] }}"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="maintenance-group-title">{{ $table['title'] }}</div>
                                    <div class="maintenance-group-hint">{{ $table['hint'] }}</div>
                                </div>
                                <span class="maintenance-chip maintenance-chip-neutral ms-auto">{{ $bucketTotal }}</span>
                            </div>
                            <div class="maintenance-list-header {{ $canBulkGroupTickets ? 'is-groupable' : '' }}">
                                @if ($canBulkGroupTickets)
                                    <span></span>
                                @endif
                                <span></span>
                                <span>Folio</span>
                                <span>Ticket</span>
                                <span>Propiedad</span>
                                <span>Responsable</span>
                                <span>Fecha programada</span>
                                <span>Estado</span>
                            </div>
                            @forelse ($bucketTickets as $ticket)
                                @php
                                    $providerName = $ticket->currentProvider?->name;
                                    $providerInitials = collect(explode(' ', trim((string) $providerName)))
                                        ->filter()
                                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                @endphp
                                <div class="maintenance-ticket-row {{ $canBulkGroupTickets ? 'is-groupable' : '' }}" role="link" tabindex="0" data-maintenance-row-url="{{ route('maintenance.show', $ticket) }}">
                                    <span class="maintenance-priority-bar maintenance-priority-{{ $ticket->priority }}"></span>
                                    @if ($canBulkGroupTickets)
                                        <span class="maintenance-group-check" data-maintenance-row-action>
                                            @if (!$ticket->cutItem && !$ticket->master_ticket_id && (int) ($ticket->child_tickets_count ?? 0) === 0)
                                                <input class="form-check-input" type="checkbox" name="ticket_ids[]" value="{{ $ticket->id }}"
                                                    form="maintenanceGroupForm" aria-label="Seleccionar ticket {{ $ticket->display_reference }}">
                                            @else
                                                <i class="bi bi-lock text-muted" title="Ticket no disponible para agrupar"></i>
                                            @endif
                                        </span>
                                    @endif
                                    <span class="maintenance-priority-cell">
                                        <span class="maintenance-reference">#{{ $ticket->display_reference }}</span>
                                        @if ($canUpdateTicketMeta)
                                            <span class="dropdown maintenance-inline-dropdown mt-2" data-maintenance-row-action>
                                                <button class="maintenance-chip maintenance-chip-button maintenance-chip-{{ $priorityTone($ticket->priority) }} dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                                    aria-label="Cambiar urgencia de {{ $ticket->display_reference }}">
                                                    {{ \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }}
                                                </button>
                                                <div class="dropdown-menu maintenance-inline-menu">
                                                    @foreach (\App\Models\MaintenanceTicket::PRIORITY_LABELS as $priorityKey => $priorityLabel)
                                                        <form method="POST" action="{{ route('maintenance.meta', $ticket) }}" class="js-maintenance-inline-meta">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="priority" value="{{ $priorityKey }}">
                                                            <button class="dropdown-item maintenance-inline-option {{ $ticket->priority === $priorityKey ? 'active' : '' }}"
                                                                type="submit" {{ $ticket->priority === $priorityKey ? 'disabled' : '' }}>
                                                                <span class="maintenance-chip maintenance-chip-{{ $priorityTone($priorityKey) }}">{{ $priorityLabel }}</span>
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </span>
                                        @else
                                            <span class="maintenance-chip maintenance-chip-{{ $priorityTone($ticket->priority) }} mt-2">
                                                {{ \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }}
                                            </span>
                                        @endif
                                    </span>
                                    <span class="maintenance-ticket-main">
                                        <span class="maintenance-ticket-name">{{ $ticket->title }}</span>
                                        <span class="maintenance-ticket-meta">
                                            <span><i class="bi bi-tools me-1"></i>{{ \App\Models\MaintenanceTicket::CATEGORY_LABELS[$ticket->category] ?? $ticket->category }}</span>
                                            <span><i class="bi bi-clock me-1"></i>{{ $ticket->reported_at?->format('d/m/Y H:i') ?: '-' }}</span>
                                            @if ((int) $ticket->messages_count > 0)
                                                <span><i class="bi bi-chat-dots me-1"></i>{{ (int) $ticket->messages_count }}</span>
                                            @endif
                                            @if ((int) $ticket->files_count > 0)
                                                <span><i class="bi bi-paperclip me-1"></i>{{ (int) $ticket->files_count }}</span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="maintenance-property-cell">
                                        <span class="maintenance-cell-icon"><i class="bi bi-house-door"></i></span>
                                        <span class="min-w-0">
                                            <span class="maintenance-cell-title" title="{{ $ticket->property?->internal_name ?? '-' }}">
                                                {{ \Illuminate\Support\Str::limit($ticket->property?->internal_name ?? '-', 28) }}
                                            </span>
                                            <span class="maintenance-cell-subtitle" title="{{ $ticket->property?->internal_reference ?: 'Sin referencia' }}">
                                                {{ \Illuminate\Support\Str::limit($ticket->property?->internal_reference ?: 'Sin referencia', 25) }}
                                            </span>
                                        </span>
                                    </span>
                                    <span class="maintenance-provider-cell">
                                        @if ($canUpdateTicketProvider)
                                            <span class="dropdown maintenance-inline-dropdown maintenance-provider-dropdown" data-maintenance-row-action>
                                                <button class="maintenance-provider-trigger dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                    aria-label="Cambiar técnico o proveedor de {{ $ticket->display_reference }}">
                                                    @if ($ticket->currentProvider)
                                                        <span class="maintenance-avatar">{{ $providerInitials ?: ($ticket->currentProvider->isSupplier() ? 'P' : 'T') }}</span>
                                                        <span class="min-w-0">
                                                            <span class="maintenance-cell-title">{{ $ticket->currentProvider->name }}</span>
                                                            <span class="maintenance-cell-subtitle">{{ \App\Models\MaintenanceProvider::TYPE_LABELS[$ticket->currentProvider->type] ?? $ticket->currentProvider->type }}</span>
                                                        </span>
                                                    @else
                                                        <span class="maintenance-cell-icon"><i class="bi bi-person-plus"></i></span>
                                                        <span class="maintenance-cell-title text-warning">Sin asignar</span>
                                                    @endif
                                                </button>
                                                <div class="dropdown-menu maintenance-inline-menu maintenance-provider-menu">
                                                    <form method="POST" action="{{ route('maintenance.meta', $ticket) }}" class="js-maintenance-inline-meta">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="provider_id" value="">
                                                        <button class="dropdown-item maintenance-provider-option {{ !$ticket->currentProvider ? 'active' : '' }}"
                                                            type="submit" {{ !$ticket->currentProvider ? 'disabled' : '' }}>
                                                            <span class="maintenance-cell-icon"><i class="bi bi-person-dash"></i></span>
                                                            <span class="min-w-0">
                                                                <span class="maintenance-cell-title">Sin asignar</span>
                                                                <span class="maintenance-cell-subtitle">Quitar responsable actual</span>
                                                            </span>
                                                        </button>
                                                    </form>
                                                    @foreach ($providers as $provider)
                                                        @php
                                                            $optionInitials = collect(explode(' ', trim((string) $provider->name)))
                                                                ->filter()
                                                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                                                ->take(2)
                                                                ->implode('');
                                                        @endphp
                                                        <form method="POST" action="{{ route('maintenance.meta', $ticket) }}" class="js-maintenance-inline-meta">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                                                            <input type="hidden" name="notes" value="Asignación rápida desde panel">
                                                            <button class="dropdown-item maintenance-provider-option {{ (int) $ticket->current_provider_id === (int) $provider->id ? 'active' : '' }}"
                                                                type="submit" {{ (int) $ticket->current_provider_id === (int) $provider->id ? 'disabled' : '' }}>
                                                                <span class="maintenance-avatar">{{ $optionInitials ?: ($provider->isSupplier() ? 'P' : 'T') }}</span>
                                                                <span class="min-w-0">
                                                                    <span class="maintenance-cell-title">{{ $provider->name }}</span>
                                                                    <span class="maintenance-cell-subtitle">
                                                                        {{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}{{ $provider->isSupplier() && $provider->category ? ' · '.$provider->category : ($provider->specialty ? ' · '.$provider->specialty : '') }}{{ $provider->is_active ? '' : ' · Inactivo' }}
                                                                    </span>
                                                                </span>
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            </span>
                                        @elseif ($ticket->currentProvider)
                                            <span class="maintenance-avatar">{{ $providerInitials ?: 'T' }}</span>
                                            <span class="min-w-0">
                                                <span class="maintenance-cell-title">{{ $ticket->currentProvider->name }}</span>
                                                <span class="maintenance-cell-subtitle">{{ \App\Models\MaintenanceProvider::TYPE_LABELS[$ticket->currentProvider->type] ?? $ticket->currentProvider->type }}</span>
                                            </span>
                                        @else
                                            <span class="maintenance-cell-icon"><i class="bi bi-person-plus"></i></span>
                                            <span class="maintenance-cell-title text-warning">Sin asignar</span>
                                        @endif
                                    </span>
                                    <span class="maintenance-scheduled-cell">
                                        <span class="maintenance-cell-icon"><i class="bi bi-calendar2-week"></i></span>
                                        <span class="min-w-0">
                                            <span class="maintenance-cell-title">{{ $ticket->scheduled_visit_at?->format('d/m/Y H:i') ?: 'Sin programar' }}</span>
                                            <span class="maintenance-cell-subtitle">
                                                {{ $ticket->scheduled_visit_at ? 'Programada' : 'Pendiente de agenda' }}
                                            </span>
                                        </span>
                                    </span>
                                    <span>
                                        <span class="maintenance-chip maintenance-chip-{{ $statusTone($ticket->status) }}">
                                            {{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                                        </span>
                                        @if ($ticket->cutItem)
                                            <span class="maintenance-chip maintenance-chip-green mt-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> Pagado
                                            </span>
                                        @endif
                                        @if ($ticket->master_ticket_id)
                                            <span class="maintenance-chip maintenance-chip-blue mt-1">
                                                <i class="bi bi-diagram-3 me-1"></i> Agrupado
                                            </span>
                                        @elseif ((int) ($ticket->child_tickets_count ?? 0) > 0)
                                            <span class="maintenance-chip maintenance-chip-purple mt-1">
                                                <i class="bi bi-collection me-1"></i> Master · {{ (int) $ticket->child_tickets_count }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            @empty
                                <div class="maintenance-table-empty">
                                    No hay tickets en esta sección.
                                </div>
                            @endforelse
                            @if ($bucketHasPages)
                                <div class="maintenance-table-pagination">
                                    {{ $bucketTickets->links() }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="maintenance-panel maintenance-empty">
                            No hay tickets de mantenimiento.
                        </div>
                    @endforelse

                    @if ($visibleTicketsTotal === 0)
                        <div class="maintenance-panel maintenance-empty">
                            {{ $isTenant ? 'Aún no tienes tickets registrados.' : 'No hay tickets de mantenimiento para los filtros seleccionados.' }}
                        </div>
                    @endif
                </div>
                </div>

                @if (!$isTenant)
                    <aside class="maintenance-side">
                        <div class="maintenance-side-panel">
                            <h3 class="maintenance-panel-title">Agenda del equipo</h3>
                            <div class="maintenance-calendar-hint">El número indica los tickets agendados por día.</div>
                            <div id="maintenance-team-calendar"></div>
                        </div>

                        <div class="maintenance-side-panel">
                            <h3 class="maintenance-panel-title">Próximas visitas</h3>
                            @forelse ($calendarItems->take(5) as $item)
                                <div class="maintenance-mini-row">
                                    <div class="min-w-0">
                                        <div class="maintenance-cell-title">{{ $item->title }}</div>
                                        <div class="maintenance-cell-subtitle">
                                            {{ $item->scheduled_visit_at?->format('d/m/Y H:i') }} · {{ $item->property?->internal_name ?? '-' }}
                                        </div>
                                    </div>
                                    <a class="maintenance-icon-btn" href="{{ route('maintenance.show', $item) }}" aria-label="Abrir ticket">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            @empty
                                <div class="text-muted fs-7">No hay visitas programadas.</div>
                            @endforelse
                        </div>

                        @if ($canManageProviders)
                            <div class="maintenance-side-panel">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                    <h3 class="maintenance-panel-title mb-0">Equipo</h3>
                                    <div class="d-flex gap-2">
                                        <a class="maintenance-soft-btn py-2" href="{{ route('maintenance.technicians.index') }}">Técnicos</a>
                                        <a class="maintenance-soft-btn py-2" href="{{ route('maintenance.providers.index') }}">Proveedores</a>
                                    </div>
                                </div>
                                @forelse ($providers->take(5) as $provider)
                                    <div class="maintenance-mini-row">
                                        <div class="min-w-0">
                                            <div class="maintenance-cell-title">{{ $provider->name }}</div>
                                            <div class="maintenance-cell-subtitle">
                                                {{ $provider->specialty ?: (\App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type) }}
                                            </div>
                                        </div>
                                        <span class="maintenance-chip maintenance-chip-{{ $provider->is_active ? 'green' : 'neutral' }}">
                                            {{ $provider->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="text-muted fs-7">No hay técnicos/proveedores.</div>
                                @endforelse
                            </div>
                        @endif

                        <div class="maintenance-side-panel">
                            <h3 class="maintenance-panel-title">Propiedades con más incidencias</h3>
                            @forelse ($metrics['top_properties'] as $item)
                                <div class="maintenance-mini-row">
                                    <div class="min-w-0">
                                        <div class="maintenance-cell-title">{{ $item->property?->internal_name ?? 'Sin propiedad' }}</div>
                                        <div class="maintenance-cell-subtitle">{{ $item->property?->internal_reference ?: '-' }}</div>
                                    </div>
                                    <span class="maintenance-chip maintenance-chip-amber">{{ $item->total }}</span>
                                </div>
                            @empty
                                <div class="text-muted fs-7">Sin datos</div>
                            @endforelse
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>

    @if ($canCreateTicket)
        <div class="modal fade" id="createMaintenanceTicketModal" tabindex="-1" aria-hidden="true">

            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content" style="height: 90vh; max-height: 90vh; overflow: hidden;">

                    <form method="POST" action="{{ route('maintenance.store') }}" enctype="multipart/form-data"
                        id="createMaintenanceTicketForm" class="d-flex flex-column h-100">

                        @csrf

                        {{-- HEADER --}}
                        <div class="modal-header flex-shrink-0">
                            <h3 class="modal-title">
                                Nuevo ticket de mantenimiento
                            </h3>

                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">
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

                                            <input class="form-control" type="text"
                                                value="{{ $properties->first()->internal_name }}{{ $properties->first()->internal_reference ? ' - ' . $properties->first()->internal_reference : '' }}"
                                                disabled>

                                            <input type="hidden" name="property_id" value="{{ $properties->first()->id }}">
                                        </div>
                                    @else
                                        <div class="col-12">
                                            <label class="form-label required">
                                                Propiedad
                                            </label>

                                            <select class="form-select" name="property_id" required>

                                                <option value="">
                                                    Seleccionar...
                                                </option>

                                                @foreach ($properties as $property)
                                                    <option value="{{ $property->id }}" {{ old('property_id', $selectedProperty?->id) == $property->id ? 'selected' : '' }}>

                                                        {{ $property->internal_name }}
                                                        {{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                                    </option>
                                                @endforeach

                                            </select>
                                        </div>
                                    @endif

                                    <div class="col-12">
                                        <label class="form-label required">
                                            Título
                                        </label>

                                        <input class="form-control" type="text" name="title" value="{{ old('title') }}"
                                            maxlength="190" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Descripción
                                        </label>

                                        <textarea class="form-control" rows="4" name="description"
                                            maxlength="10000">{{ old('description') }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label required">
                                            Evidencia
                                        </label>

                                        <input class="form-control" type="file" name="files[]" multiple required>
                                    </div>

                                @else

                                    <div class="col-md-6">
                                        <label class="form-label required">
                                            Propiedad
                                        </label>

                                        <select class="form-select" name="property_id" required>

                                            <option value="">
                                                Seleccionar...
                                            </option>

                                            @foreach ($properties as $property)
                                                <option value="{{ $property->id }}" {{ old('property_id', $selectedProperty?->id) == $property->id ? 'selected' : '' }}>

                                                    {{ $property->internal_name }}
                                                    {{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label required">
                                            Categoría
                                        </label>

                                        <select class="form-select" name="category" required>

                                            @foreach (\App\Models\MaintenanceTicket::CATEGORY_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('category', 'sin_categoria') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label required">
                                            Prioridad
                                        </label>

                                        <select class="form-select" name="priority" required>

                                            @foreach (\App\Models\MaintenanceTicket::PRIORITY_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('priority', 'sin_asignar') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Técnico o proveedor
                                        </label>

                                        <select class="form-select" name="provider_id" id="createTicketProvider">
                                            <option value="">Sin asignar</option>
                                            @foreach ($providers as $provider)
                                                <option value="{{ $provider->id }}" {{ old('provider_id') == $provider->id ? 'selected' : '' }}>
                                                    {{ $provider->name }} ·
                                                    {{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">
                                            Nombre del ticket
                                        </label>

                                        <input class="form-control" type="text" name="title" value="{{ old('title') }}"
                                            maxlength="190" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label required">
                                            Ubicación exacta
                                        </label>

                                        <input class="form-control" type="text" name="exact_location"
                                            value="{{ old('exact_location') }}" maxlength="255" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label required">
                                            Fecha del reporte
                                        </label>

                                        <input class="form-control" type="datetime-local" name="reported_at"
                                            value="{{ old('reported_at', now()->format('Y-m-d\\TH:i')) }}" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Visita programada
                                        </label>

                                        <input class="form-control" type="datetime-local" name="scheduled_visit_at"
                                            id="createTicketScheduledVisit" value="{{ old('scheduled_visit_at') }}">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Quién paga
                                        </label>

                                        <select class="form-select" name="payer">

                                            <option value="">
                                                Sin definir
                                            </option>

                                            @foreach (\App\Models\MaintenanceTicket::COST_PAYER_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('payer') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Regla
                                        </label>

                                        <select class="form-select" name="payment_rule">

                                            <option value="">
                                                Sin definir
                                            </option>

                                            @foreach (\App\Models\MaintenanceTicket::PAYMENT_RULE_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('payment_rule') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Estado inicial
                                        </label>

                                        <select class="form-select" name="status">

                                            @foreach (\App\Models\MaintenanceTicket::STATUS_LABELS as $key => $label)
                                                <option value="{{ $key }}" {{ old('status', 'pendiente') === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label required">
                                            Descripción
                                        </label>

                                        <textarea class="form-control" rows="4" name="description" maxlength="10000"
                                            required>{{ old('description') }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Notas adicionales
                                        </label>

                                        <textarea class="form-control" rows="3" name="additional_notes"
                                            maxlength="10000">{{ old('additional_notes') }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Archivos (múltiples)
                                        </label>

                                        <input class="form-control" type="file" name="files[]" multiple>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Notas sobre regla de pago
                                        </label>

                                        <textarea class="form-control" rows="2" name="payment_rule_notes"
                                            maxlength="3000">{{ old('payment_rule_notes') }}</textarea>
                                    </div>

                                @endif

                            </div>
                        </div>

                        {{-- FOOTER FIJO --}}
                        <div class="modal-footer flex-shrink-0">
                            <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button class="btn btn-primary" type="submit">
                                Crear ticket
                            </button>
                            <input type="hidden" name="force_conflict" value="0">
                        </div>

                    </form>
                </div>
            </div>
        </div>
    @endif
    <style>
        .maintenance-calendar-hint {
            margin: 4px 0 14px;
            color: var(--maint-muted);
            font-size: 11px;
        }

        #maintenance-team-calendar {
            min-width: 0;
        }

        #maintenance-team-calendar .fc-toolbar {
            gap: 8px;
            margin-bottom: 12px;
        }

        #maintenance-team-calendar .fc-toolbar-title {
            color: var(--maint-ink);
            font-size: 14px;
            font-weight: 800;
            text-transform: capitalize;
        }

        #maintenance-team-calendar .fc-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            padding: 0 !important;
            border: 1px solid var(--maint-border) !important;
            border-radius: 8px !important;
            background: #fff !important;
            color: var(--maint-text) !important;
            box-shadow: none !important;
        }

        #maintenance-team-calendar .fc-col-header-cell-cushion,
        #maintenance-team-calendar .fc-daygrid-day-number {
            color: var(--maint-text);
            font-size: 11px;
        }

        #maintenance-team-calendar .fc-daygrid-day-frame {
            min-height: 54px;
        }

        #maintenance-team-calendar .fc-event {
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        #maintenance-team-calendar .fc-daygrid-event {
            margin: 2px 4px 4px;
        }

        #maintenance-team-calendar .fc-ticket-count {
            display: grid;
            place-items: center;
            width: 24px;
            height: 24px;
            margin: 0 auto;
            border-radius: 50%;
            background: var(--maint-primary);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
        }
    </style>
@endsection

@push('scripts')
    <script src="{{ asset('/metronic/assets/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
    @if ($errors->createMaintenanceTicket->any())
        <script>
            (() => {
                const modalEl = document.getElementById('createMaintenanceTicketModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
    <script>
        (() => {
            const calendarEl = document.getElementById('maintenance-team-calendar');
            if (calendarEl && window.FullCalendar?.Calendar) {
                const events = @json($calendarEvents);
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    headerToolbar: {
                        left: 'prev',
                        center: 'title',
                        right: 'next',
                    },
                    fixedWeekCount: false,
                    showNonCurrentDates: true,
                    displayEventTime: false,
                    height: 'auto',
                    events,
                    eventContent: (arg) => {
                        const count = Number(arg.event.extendedProps?.count || 0);
                        return {
                            html: `<span class="fc-ticket-count" title="${count} ticket${count === 1 ? '' : 's'} agendado${count === 1 ? '' : 's'}">${count}</span>`
                        };
                    },
                });
                calendar.render();
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const liveSearchForm = document.querySelector('[data-maintenance-live-search]');
            const liveSearchInput = liveSearchForm?.querySelector('input[name="q"]');
            const liveSearchTab = liveSearchForm?.querySelector('input[name="tab"]');
            const propertyFilter = document.getElementById('maintenance-property-filter');
            const resultsFrame = document.querySelector('[data-maintenance-results]');
            let liveSearchTimer = null;
            let liveSearchRequest = null;
            let liveSearchSerial = 0;

            const askConfirmation = async (message) => {
                if (window.Swal?.fire) {
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Conflicto de agenda',
                        html: String(message || '').replace(/\n/g, '<br>'),
                        showCancelButton: true,
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'Cancelar',
                    });
                    return result.isConfirmed === true;
                }
                return window.confirm(message);
            };

            const renderNotice = (type, message) => {
                if (window.SuWorkToast?.fire) {
                    window.SuWorkToast.fire(type, message);
                    return;
                }
                console[type === 'danger' || type === 'error' ? 'error' : 'log'](message);
            };

            const submitInlineMeta = async (inlineForm, forceConflict = false) => {
                const data = new FormData(inlineForm);
                if (forceConflict) {
                    data.set('force_conflict', '1');
                }

                const response = await fetch(inlineForm.action, {
                    method: (inlineForm.method || 'POST').toUpperCase(),
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: data,
                });
                const payload = await response.json().catch(() => ({}));

                if (response.status === 422 && payload.requires_confirmation) {
                    const approved = await askConfirmation(payload.message || 'El técnico ya tiene otra asignación este día.');
                    if (!approved) return false;
                    return submitInlineMeta(inlineForm, true);
                }

                if (!response.ok || payload.success === false) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                    throw new Error(errors[0] || payload.message || 'No fue posible guardar el cambio.');
                }

                renderNotice('success', payload.message || 'Guardado correctamente.');
                window.location.reload();
                return true;
            };

            const rowIgnoreSelector = 'a, button, input, select, textarea, label, form, .dropdown-menu, [data-maintenance-row-action]';

            const initMaintenanceRows = (root = document) => {
                root.querySelectorAll('[data-maintenance-row-url]').forEach((row) => {
                    const openTicket = () => {
                        const url = row.dataset.maintenanceRowUrl;
                        if (url) window.location.href = url;
                    };

                    row.addEventListener('click', (event) => {
                        if (event.target.closest(rowIgnoreSelector)) return;
                        openTicket();
                    });

                    row.addEventListener('keydown', (event) => {
                        if (event.key !== 'Enter') return;
                        if (event.target.closest(rowIgnoreSelector)) return;
                        event.preventDefault();
                        openTicket();
                    });
                });

                root.querySelectorAll('.maintenance-ticket-row .dropdown').forEach((dropdown) => {
                    const row = dropdown.closest('.maintenance-ticket-row');
                    dropdown.addEventListener('shown.bs.dropdown', () => {
                        row?.classList.add('is-dropdown-open');
                    });
                    dropdown.addEventListener('hidden.bs.dropdown', () => {
                        row?.classList.remove('is-dropdown-open');
                    });
                });
            };

            const initInlineMetaForms = (root = document) => {
                root.querySelectorAll('.js-maintenance-inline-meta').forEach((inlineForm) => {
                    inlineForm.addEventListener('submit', async (event) => {
                        event.preventDefault();
                        const submitButton = inlineForm.querySelector('[type="submit"]');
                        if (submitButton?.disabled) return;
                        if (submitButton) submitButton.disabled = true;

                        try {
                            const saved = await submitInlineMeta(inlineForm);
                            if (!saved && submitButton) submitButton.disabled = false;
                        } catch (error) {
                            renderNotice('danger', error.message || 'No fue posible guardar el cambio.');
                            if (submitButton) submitButton.disabled = false;
                        }
                    });
                });
            };

            const initMaintenanceInteractions = (root = document) => {
                initMaintenanceRows(root);
                initInlineMetaForms(root);
            };

            const resetPaginationParams = (params) => {
                ['page', 'urgent_page', 'scheduled_page', 'unscheduled_page', 'unassigned_page'].forEach((key) => {
                    params.delete(key);
                });
            };

            const buildMaintenanceUrl = (sourceUrl = null, resetPages = false) => {
                const url = new URL(sourceUrl || liveSearchForm?.action || window.location.href, window.location.origin);
                const params = sourceUrl ? url.searchParams : new URLSearchParams();
                const query = String(liveSearchInput?.value || '').trim();
                const property = String(propertyFilter?.value || '').trim();
                const tab = String(liveSearchTab?.value || params.get('tab') || 'activos').trim();

                if (resetPages) {
                    resetPaginationParams(params);
                }

                if (tab) {
                    params.set('tab', tab);
                }
                if (query !== '') {
                    params.set('q', query);
                } else {
                    params.delete('q');
                }
                if (property !== '') {
                    params.set('property', property);
                } else {
                    params.delete('property');
                }

                url.search = params.toString();
                return url;
            };

            const syncTabLinks = () => {
                if (!liveSearchForm) return;
                document.querySelectorAll('.maintenance-tab').forEach((tabLink) => {
                    const tabUrl = new URL(tabLink.href, window.location.origin);
                    const tabValue = tabUrl.searchParams.get('tab') || 'activos';
                    const wasActive = liveSearchTab?.value === tabValue;
                    const nextUrl = buildMaintenanceUrl(liveSearchForm.action, true);

                    nextUrl.searchParams.set('tab', tabValue);
                    tabLink.href = nextUrl.toString();
                    tabLink.classList.toggle('active', wasActive);
                });
            };

            const refreshMaintenanceList = async (url, replaceHistory = true) => {
                if (!resultsFrame) {
                    window.location.href = url.toString();
                    return;
                }

                liveSearchRequest?.abort();
                liveSearchRequest = new AbortController();
                const requestSerial = ++liveSearchSerial;

                resultsFrame.classList.add('is-loading');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: liveSearchRequest.signal,
                    });

                    if (!response.ok) {
                        throw new Error('No fue posible filtrar los tickets.');
                    }

                    const payload = await response.json();
                    if (requestSerial !== liveSearchSerial) return;

                    resultsFrame.innerHTML = payload.html || '';
                    initMaintenanceInteractions(resultsFrame);

                    if (replaceHistory) {
                        window.history.replaceState({}, '', url.toString());
                    }

                    liveSearchForm.dataset.currentQuery = String(liveSearchInput?.value || '').trim();
                    syncTabLinks();
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        renderNotice('danger', error.message || 'No fue posible filtrar los tickets.');
                    }
                } finally {
                    if (requestSerial === liveSearchSerial) {
                        resultsFrame.classList.remove('is-loading');
                    }
                }
            };

            const scheduleMaintenanceSearch = (delay = 250) => {
                window.clearTimeout(liveSearchTimer);
                liveSearchTimer = window.setTimeout(() => {
                    refreshMaintenanceList(buildMaintenanceUrl(null, true));
                }, delay);
            };

            if (liveSearchForm) {
                liveSearchInput?.addEventListener('input', () => {
                    scheduleMaintenanceSearch();
                });

                liveSearchForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    scheduleMaintenanceSearch(0);
                });

                const handlePropertyChange = () => {
                    scheduleMaintenanceSearch(0);
                };

                if (window.jQuery && propertyFilter) {
                    window.jQuery(propertyFilter).on('change', handlePropertyChange);
                } else {
                    propertyFilter?.addEventListener('change', handlePropertyChange);
                }

                document.querySelectorAll('.maintenance-tab').forEach((tabLink) => {
                    tabLink.addEventListener('click', (event) => {
                        event.preventDefault();
                        const tabUrl = new URL(tabLink.href, window.location.origin);
                        if (liveSearchTab) {
                            liveSearchTab.value = tabUrl.searchParams.get('tab') || 'activos';
                        }
                        refreshMaintenanceList(buildMaintenanceUrl(null, true));
                    });
                });
                syncTabLinks();
            }

            initMaintenanceInteractions(document);

            const form = document.getElementById('createMaintenanceTicketForm');
            if (!form) return;
            const providerInput = form.querySelector('[name="provider_id"]');
            const scheduledInput = document.getElementById('createTicketScheduledVisit');
            const forceInput = form.querySelector('[name="force_conflict"]');
            const conflictUrl = @json(route('maintenance.technician-conflicts'));
            form.addEventListener('submit', async (event) => {
                if (!providerInput?.value || !scheduledInput?.value || forceInput?.value === '1') {
                    return;
                }
                event.preventDefault();
                const response = await fetch(conflictUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        provider_id: providerInput.value,
                        scheduled_visit_at: scheduledInput.value,
                    }),
                }).catch(() => null);
                if (!response?.ok) {
                    form.submit();
                    return;
                }
                const payload = await response.json().catch(() => null);
                if (!payload?.has_conflicts) {
                    form.submit();
                    return;
                }
                const approved = await askConfirmation(payload.message || 'El técnico ya tiene otra asignación este día.');
                if (!approved) return;
                forceInput.value = '1';
                form.submit();
            });
        })();
    </script>
@endpush
