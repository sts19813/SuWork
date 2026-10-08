@php
    $statusTone = $statusTone ?? fn ($value) => match ($value) {
        'completado' => 'green',
        'cancelado' => 'red',
        'en_proceso' => 'purple',
        'programado', 'asignado' => 'blue',
        'pendiente', 'revisado', 'esperando_material', 'reabierto' => 'amber',
        default => 'neutral',
    };
    $priorityTone = $priorityTone ?? fn ($value) => match ($value) {
        'baja' => 'green',
        'media' => 'blue',
        'alta' => 'amber',
        'urgente' => 'red',
        default => 'neutral',
    };
    $canBulkGroupTickets = ($canGroupTickets ?? false) && in_array($activeTab ?? '', ['activos', 'completados'], true);
@endphp

<div class="maintenance-worklist">
    <div class="maintenance-panel">
        <div class="maintenance-list-toolbar">
            <div>
                <div class="maintenance-list-title">{{ $isTenant ? 'Tus tickets' : 'Lista operativa' }}</div>
                <div class="maintenance-list-count">
                    Mostrando {{ $visibleTicketsCount }} de {{ $visibleTicketsTotal }} tickets
                </div>
            </div>
            @if ($search || $selectedProperty || $status || $priority || $category || $dateFrom || $dateTo)
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
