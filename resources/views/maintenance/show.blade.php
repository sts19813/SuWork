@extends('layouts.app')

@section('title', 'Ticket mantenimiento | SuWork')

@section('content')
    <div class="py-10">
        @if (session('success'))
            <div class="alert alert-success mb-6">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mb-6">{{ session('error') }}</div>
        @endif
        <div id="ticketAjaxNotice" class="mb-6"></div>

        @php
            $tenantName = $ticket->property?->tenant?->full_name
                ?: ($ticket->property?->current_tenant_name
                    ?: ($ticket->reported_by_role === 'inquilino' ? ($ticket->reported_by_name ?: '-') : '-'));
            $tenantPhone = $ticket->property?->tenant?->phone_primary ?: '-';
            $propertyPhoto = $ticket->property?->facade_photo_path
                ? asset('storage/' . ltrim((string) $ticket->property->facade_photo_path, '/'))
                : null;
            $mapsLink = $ticket->property?->map_url;
            if (!$mapsLink && filled($ticket->property?->full_address)) {
                $mapsLink = 'https://www.google.com/maps/search/?api=1&query=' . urlencode((string) $ticket->property?->full_address);
            }
            $evidenceImages = $ticket->files
                ->map(function ($file) {
                    $file->preview_url = $file->url ?: (filled($file->path) ? \Illuminate\Support\Facades\Storage::disk('public')->url($file->path) : null);
                    return $file;
                })
                ->filter(fn($file) => str_starts_with((string) $file->mime_type, 'image/') && filled($file->preview_url))
                ->values();
        @endphp

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
            <div>
                <h1 class="mb-1 fw-bold">Mantenimiento / Ticket</h1>
                <div class="text-muted">
                    Folio {{ $ticket->display_reference }}
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-light" href="{{ route('maintenance.index') }}">Regresar</a>
                <a class="btn btn-light-primary" href="#ticket-history-section">Historial de cambios</a>
                @if ($canQuickScheduleVisit)
                    <button
                        class="btn btn-primary"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#quickScheduleCollapse"
                        aria-expanded="false"
                        aria-controls="quickScheduleCollapse"
                    >
                        Programar visita rápida
                    </button>
                @endif
            </div>
        </div>

        @if ($canQuickScheduleVisit)
            <div class="collapse mb-6" id="quickScheduleCollapse">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('maintenance.schedule-visit', $ticket) }}" id="quickScheduleForm" class="row g-3 align-items-end">
                            @csrf
                            @method('PATCH')
                            <div class="col-md-5">
                                <label class="form-label">Fecha programada</label>
                                <input class="form-control" type="datetime-local" name="scheduled_visit_at" id="quickScheduleDate" value="{{ $ticket->scheduled_visit_at?->format('Y-m-d\\TH:i') }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nota</label>
                                <input class="form-control" type="text" name="notes" maxlength="3000" placeholder="Opcional">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100">Guardar</button>
                            </div>
                            <input type="hidden" name="force_conflict" value="0">
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-5 mb-6">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted mb-2">Foto propiedad</div>
                        @if ($propertyPhoto)
                            <img src="{{ $propertyPhoto }}" alt="Foto propiedad" class="w-100 rounded" style="height: 92px; object-fit: cover;">
                        @else
                            <div class="rounded bg-light d-flex align-items-center justify-content-center text-muted" style="height: 92px;">Sin foto</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted mb-2">Propiedad</div>
                        @if (in_array($role, ['administrador', 'tecnico'], true))
                            <select disabled class="form-select form-select-sm js-ticket-meta" data-field="property_id">
                                @foreach ($properties as $property)
                                    <option value="{{ $property->id }}" {{ $ticket->property_id === $property->id ? 'selected' : '' }}>
                                        {{ $property->internal_name }}{{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div class="fw-bold">{{ $ticket->property?->internal_name ?? '-' }}</div>
                            <div class="text-muted fs-8">{{ $ticket->property?->internal_reference ?: '-' }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted mb-2">Categoría</div>
                        @if (in_array($role, ['administrador', 'tecnico'], true))
                            <select class="form-select form-select-sm js-ticket-meta" data-field="category">
                                @foreach ($categoryOptions as $key => $label)
                                    <option value="{{ $key }}" {{ $ticket->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="fw-bold">{{ $categoryOptions[$ticket->category] ?? $ticket->category }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted mb-2">Prioridad</div>
                        @if (in_array($role, ['administrador', 'tecnico'], true))
                            <select class="form-select form-select-sm js-ticket-meta" data-field="priority">
                                @foreach ($priorityOptions as $key => $label)
                                    <option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="fw-bold">{{ $priorityOptions[$ticket->priority] ?? $ticket->priority }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted mb-2">Inquilino</div>
                        <div class="fw-bold">{{ $tenantName ?: '-' }}</div>
                        <div class="text-muted fs-8">{{ $tenantPhone }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted mb-2">Técnico asignado</div>
                        @if (in_array($role, ['administrador', 'tecnico'], true))
                            <select class="form-select form-select-sm js-ticket-meta" data-field="provider_id" data-ticket-uuid="{{ $ticket->uuid }}" data-scheduled-visit-at="{{ $ticket->scheduled_visit_at?->format('Y-m-d\\TH:i:s') }}">
                                <option value="">Sin asignar</option>
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ $ticket->current_provider_id === $provider->id ? 'selected' : '' }}>
                                        {{ $provider->name }} · {{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div class="fw-bold">{{ $ticket->currentProvider?->name ?? 'Sin asignar' }}</div>
                            <div class="text-muted fs-8">{{ $ticket->currentProvider?->phone ?: ($ticket->currentProvider?->email ?: '-') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 mb-6">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Detalle del ticket</h3>
                        @if ($canEditTicket)
                            <button class="btn btn-light-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editTicketModal">Editar ticket</button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row g-4 mb-4">
                            <div class="col-md-8">
                                <div class="text-muted mb-1">Título</div>
                                <div class="fw-semibold">{{ $ticket->title }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted mb-1">Ubicación exacta</div>
                                <div class="fw-semibold">{{ $ticket->exact_location ?: '-' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted mb-1">Fecha reporte</div>
                                <div class="fw-semibold">{{ $ticket->reported_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted mb-1">Visita programada</div>
                                <div class="fw-semibold">{{ $ticket->scheduled_visit_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted mb-1">Regla de pago</div>
                                <div class="fw-semibold">{{ $paymentRuleOptions[$ticket->payment_rule] ?? 'Sin definir' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted mb-1">Quién paga</div>
                                <div class="fw-semibold">{{ $payerOptions[$ticket->payer] ?? 'Sin definir' }}</div>
                            </div>
                            <div class="col-md-8">
                                <div class="text-muted mb-1">Notas regla de pago</div>
                                <div>{{ $ticket->payment_rule_notes ?: '-' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted mb-1">Descripción</div>
                                <div>{{ $ticket->description }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted mb-1">Notas adicionales</div>
                                <div>{{ $ticket->additional_notes ?: '-' }}</div>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted mb-2">Imágenes del ticket</div>
                            <div class="row g-3">
                                @forelse ($evidenceImages as $file)
                                    <div class="col-md-4 col-6">
                                        <a href="{{ $file->preview_url }}" target="_blank">
                                            <img src="{{ $file->preview_url }}" alt="{{ $file->original_name }}" class="w-100 rounded" style="height: 140px; object-fit: cover;">
                                        </a>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted">Sin imágenes.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card mb-5">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Contacto del inquilino</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-2"><strong>Celular:</strong> {{ $tenantPhone }}</div>
                        <div><strong>Ubicación de la propiedad:</strong>
                            @if ($mapsLink)
                                <a href="{{ $mapsLink }}" target="_blank">LINK</a>
                            @else
                                <span>-</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Estado</h3>
                    </div>
                    <div class="card-body">
                        @if ($canChangeStatus)
                            <form method="POST" action="{{ route('maintenance.status', $ticket) }}" class="row g-3 mb-4">
                                @csrf
                                @method('PATCH')
                                <div class="col-12">
                                    <label class="form-label">Cambiar estado</label>
                                    <select class="form-select" name="status">
                                        @foreach ($statusOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Nota</label>
                                    <textarea class="form-control" name="notes" rows="3" maxlength="3000" placeholder="Opcional"></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-primary">Actualizar estado</button>
                                </div>
                            </form>
                        @endif
                        <div class="d-flex flex-column gap-2 fs-8">
                            <div><strong>Creado:</strong> {{ $ticket->created_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            <div><strong>Asignado:</strong> {{ $ticket->assigned_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            <div><strong>Iniciado:</strong> {{ $ticket->started_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            <div><strong>Terminado:</strong> {{ $ticket->completed_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            <div><strong>Cancelado:</strong> {{ $ticket->canceled_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            <div><strong>Motivo cancelación:</strong> {{ $ticket->cancel_reason ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" id="ticket-history-section">
            <div class="card-header border-0 pb-0">
                <ul class="nav nav-tabs nav-line-tabs">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ticket-history-changes" type="button">Historial de cambios</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ticket-history-notifications" type="button">Historial de notificaciones</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="ticket-history-changes">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-muted">
                                        <th>Fecha</th>
                                        <th>De</th>
                                        <th>A</th>
                                        <th>Usuario</th>
                                        <th>Nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ticket->statusHistory as $row)
                                        <tr>
                                            <td>{{ $row->changed_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $row->from_status ? (\App\Models\MaintenanceTicket::STATUS_LABELS[$row->from_status] ?? $row->from_status) : '-' }}</td>
                                            <td>{{ \App\Models\MaintenanceTicket::STATUS_LABELS[$row->to_status] ?? $row->to_status }}</td>
                                            <td>{{ $row->changedBy?->name ?? '-' }}</td>
                                            <td>{{ $row->notes ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-8">Sin historial.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="ticket-history-notifications">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-muted">
                                        <th>Fecha</th>
                                        <th>Evento</th>
                                        <th>Canal</th>
                                        <th>Destino</th>
                                        <th>Envío</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ticket->notifications as $notification)
                                        <tr>
                                            <td>{{ $notification->created_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $notification->event }}</td>
                                            <td>{{ $notification->channel }}</td>
                                            <td>{{ $notification->recipient ?: '-' }}</td>
                                            <td>{{ $notification->was_sent ? 'Enviado' : 'Falló' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-8">Sin notificaciones.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canEditTicket)
        <div class="modal fade" id="editTicketModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('maintenance.update', $ticket) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h3 class="modal-title">Editar ticket</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Propiedad</label>
                                    <select class="form-select" name="property_id" required>
                                        @foreach ($properties as $property)
                                            <option value="{{ $property->id }}" {{ $ticket->property_id === $property->id ? 'selected' : '' }}>
                                                {{ $property->internal_name }}{{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Categoría</label>
                                    <select class="form-select" name="category" required>
                                        @foreach ($categoryOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Prioridad</label>
                                    <select class="form-select" name="priority" required>
                                        @foreach ($priorityOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label required">Título</label>
                                    <input class="form-control" type="text" name="title" value="{{ $ticket->title }}" maxlength="190" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Folio</label>
                                    <input class="form-control" type="text" value="{{ $ticket->display_reference }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Ubicación exacta</label>
                                    <input class="form-control" type="text" name="exact_location" value="{{ $ticket->exact_location }}" maxlength="255" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label required">Fecha reporte</label>
                                    <input class="form-control" type="datetime-local" name="reported_at" value="{{ $ticket->reported_at?->format('Y-m-d\\TH:i') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Visita programada</label>
                                    <input class="form-control" type="datetime-local" name="scheduled_visit_at" value="{{ $ticket->scheduled_visit_at?->format('Y-m-d\\TH:i') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Quién paga</label>
                                    <select class="form-select" name="payer">
                                        <option value="">Sin definir</option>
                                        @foreach ($payerOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->payer === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Regla de pago</label>
                                    <select class="form-select" name="payment_rule">
                                        <option value="">Sin definir</option>
                                        @foreach ($paymentRuleOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->payment_rule === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Notas regla pago</label>
                                    <input class="form-control" type="text" name="payment_rule_notes" value="{{ $ticket->payment_rule_notes }}" maxlength="3000">
                                </div>
                                <div class="col-12">
                                    <label class="form-label required">Descripción</label>
                                    <textarea class="form-control" rows="4" name="description" maxlength="10000" required>{{ $ticket->description }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notas adicionales</label>
                                    <textarea class="form-control" rows="3" name="additional_notes" maxlength="10000">{{ $ticket->additional_notes }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Agregar archivos</label>
                                    <input class="form-control" type="file" name="files[]" multiple>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button class="btn btn-primary" type="submit">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const conflictUrl = @json(route('maintenance.technician-conflicts'));
            const ticketUuid = @json($ticket->uuid);
            const providerSelect = document.querySelector('.js-ticket-meta[data-field="provider_id"]');
            const providerScheduledValue = () => providerSelect?.dataset.scheduledVisitAt || @json($ticket->scheduled_visit_at?->format('Y-m-d\\TH:i:s'));
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
            const checkConflicts = async (providerId, scheduledVisitAt) => {
                if (!providerId || !scheduledVisitAt) return null;
                const response = await fetch(conflictUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        provider_id: providerId,
                        scheduled_visit_at: scheduledVisitAt,
                        exclude_ticket_uuid: ticketUuid,
                    }),
                }).catch(() => null);
                if (!response?.ok) return null;
                const payload = await response.json().catch(() => null);
                return payload?.has_conflicts ? payload : null;
            };

            const selects = document.querySelectorAll('.js-ticket-meta');
            if (!selects.length) {
                const quickScheduleForm = document.getElementById('quickScheduleForm');
                if (!quickScheduleForm) return;
            }

            const notice = document.getElementById('ticketAjaxNotice');
            const metaUrl = @json(route('maintenance.meta', $ticket));

            const renderNotice = (type, message) => {
                if (!notice) return;
                notice.innerHTML = `<div class="alert alert-${type} mb-0 py-3">${message}</div>`;
                window.clearTimeout(renderNotice._timeoutId);
                renderNotice._timeoutId = window.setTimeout(() => {
                    notice.innerHTML = '';
                }, 2500);
            };

            selects.forEach((select) => {
                select.dataset.prevValue = select.value;
                select.addEventListener('focus', () => {
                    select.dataset.prevValue = select.value;
                });

                select.addEventListener('change', async () => {
                    const field = select.dataset.field;
                    const nextValue = select.value;
                    const prevValue = select.dataset.prevValue ?? '';
                    if (!field || nextValue === prevValue) {
                        return;
                    }

                    select.disabled = true;
                    try {
                        const payloadData = {
                            [field]: field === 'provider_id' && nextValue === '' ? null : nextValue
                        };
                        if (field === 'provider_id') {
                            const scheduledVisitAt = providerScheduledValue();
                            const conflicts = await checkConflicts(nextValue, scheduledVisitAt);
                            if (conflicts) {
                                const approved = await askConfirmation(conflicts.message || 'El técnico ya tiene otra asignación este día.');
                                if (!approved) {
                                    select.value = prevValue;
                                    return;
                                }
                                payloadData.force_conflict = 1;
                            }
                        }
                        const response = await fetch(metaUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(payloadData),
                        });

                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok || payload.success === false) {
                            throw new Error(payload.message || 'No fue posible guardar el cambio.');
                        }

                        select.dataset.prevValue = nextValue;
                        if (field === 'provider_id') {
                            select.dataset.scheduledVisitAt = payload.data?.scheduled_visit_at || select.dataset.scheduledVisitAt || '';
                        }
                        renderNotice('success', payload.message || 'Guardado correctamente.');
                    } catch (error) {
                        select.value = prevValue;
                        renderNotice('danger', error.message || 'Error al guardar.');
                    } finally {
                        select.disabled = false;
                    }
                });
            });

            const quickScheduleForm = document.getElementById('quickScheduleForm');
            const quickScheduleDate = document.getElementById('quickScheduleDate');
            const quickForce = quickScheduleForm?.querySelector('[name="force_conflict"]');
            if (!quickScheduleForm || !providerSelect || !quickScheduleDate) return;

            quickScheduleForm.addEventListener('submit', async (event) => {
                if (!providerSelect.value || !quickScheduleDate.value || quickForce?.value === '1') {
                    return;
                }
                event.preventDefault();
                const conflicts = await checkConflicts(providerSelect.value, quickScheduleDate.value);
                if (!conflicts) {
                    quickScheduleForm.submit();
                    return;
                }
                const approved = await askConfirmation(conflicts.message || 'El técnico ya tiene otra asignación este día.');
                if (!approved) return;
                if (quickForce) quickForce.value = '1';
                quickScheduleForm.submit();
            });
        })();
    </script>
@endpush
