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

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
            <div>
                <h1 class="mb-1 fw-bold">{{ $ticket->title }}</h1>
                <div class="text-muted">
                    {{ $ticket->reference ?: $ticket->uuid }} · {{ \App\Models\MaintenanceTicket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light" href="{{ route('maintenance.index') }}">Volver</a>
            </div>
        </div>

        <div class="row g-5 mb-6">
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <div class="text-muted">Propiedad</div>
                    <div class="fw-bold">{{ $ticket->property?->internal_name ?? '-' }}</div>
                    <div class="text-muted fs-8">{{ $ticket->property?->internal_reference ?: '-' }}</div>
                </div></div>
            </div>
            <div class="col-md-2">
                <div class="card"><div class="card-body">
                    <div class="text-muted">Categoría</div>
                    <div class="fw-bold">{{ \App\Models\MaintenanceTicket::CATEGORY_LABELS[$ticket->category] ?? $ticket->category }}</div>
                </div></div>
            </div>
            <div class="col-md-2">
                <div class="card"><div class="card-body">
                    <div class="text-muted">Prioridad</div>
                    <div class="fw-bold">{{ \App\Models\MaintenanceTicket::PRIORITY_LABELS[$ticket->priority] ?? $ticket->priority }}</div>
                </div></div>
            </div>
            <div class="col-md-2">
                <div class="card"><div class="card-body">
                    <div class="text-muted">Reportó</div>
                    <div class="fw-bold">{{ $ticket->reported_by_name ?: ($ticket->reporter?->name ?? '-') }}</div>
                    <div class="text-muted fs-8">{{ \App\Models\MaintenanceTicket::REPORTER_ROLE_LABELS[$ticket->reported_by_role] ?? $ticket->reported_by_role }}</div>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <div class="text-muted">Proveedor actual</div>
                    <div class="fw-bold">{{ $ticket->currentProvider?->name ?? 'Sin asignar' }}</div>
                    <div class="text-muted fs-8">{{ $ticket->currentProvider?->phone ?: ($ticket->currentProvider?->email ?: '-') }}</div>
                </div></div>
            </div>
        </div>

        <div class="row g-5 mb-6">
            <div class="col-lg-7">
                <div class="card mb-5">
                    <div class="card-header"><h3 class="card-title">Detalle</h3></div>
                    <div class="card-body">
                        <div class="mb-3"><strong>Ubicación exacta:</strong> {{ $ticket->exact_location }}</div>
                        <div class="mb-3"><strong>Descripción:</strong><br>{{ $ticket->description }}</div>
                        <div class="mb-3"><strong>Notas adicionales:</strong><br>{{ $ticket->additional_notes ?: '-' }}</div>
                        <div class="row g-3">
                            <div class="col-md-6"><strong>Fecha de reporte:</strong> {{ $ticket->reported_at?->format('d/m/Y H:i') }}</div>
                            <div class="col-md-6"><strong>Visita programada:</strong> {{ $ticket->scheduled_visit_at?->format('d/m/Y H:i') ?: '-' }}</div>
                            <div class="col-md-6"><strong>Quién paga:</strong> {{ $ticket->payer ? (\App\Models\MaintenanceTicket::PAYER_LABELS[$ticket->payer] ?? $ticket->payer) : '-' }}</div>
                            <div class="col-md-6"><strong>Regla:</strong> {{ $ticket->payment_rule ? (\App\Models\MaintenanceTicket::PAYMENT_RULE_LABELS[$ticket->payment_rule] ?? $ticket->payment_rule) : '-' }}</div>
                            <div class="col-12"><strong>Notas regla de pago:</strong> {{ $ticket->payment_rule_notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>

                @if ($canEditTicket)
                    <div class="card mb-5">
                        <div class="card-header"><h3 class="card-title">Editar ticket</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('maintenance.update', $ticket) }}" enctype="multipart/form-data" class="row g-4">
                                @csrf
                                @method('PUT')
                                <div class="col-md-6">
                                    <label class="form-label">Categoría</label>
                                    <select class="form-select" name="category">
                                        @foreach ($categoryOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Prioridad</label>
                                    <select class="form-select" name="priority">
                                        @foreach ($priorityOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Nombre</label>
                                    <input class="form-control" name="title" value="{{ $ticket->title }}" maxlength="190">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Referencia</label>
                                    <input class="form-control" name="reference" value="{{ $ticket->reference }}" maxlength="190">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ubicación</label>
                                    <input class="form-control" name="exact_location" value="{{ $ticket->exact_location }}" maxlength="255">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Reporte</label>
                                    <input class="form-control" type="datetime-local" name="reported_at" value="{{ $ticket->reported_at?->format('Y-m-d\\TH:i') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Visita</label>
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
                                    <label class="form-label">Regla</label>
                                    <select class="form-select" name="payment_rule">
                                        <option value="">Sin definir</option>
                                        @foreach ($paymentRuleOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->payment_rule === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Notas regla</label>
                                    <input class="form-control" name="payment_rule_notes" value="{{ $ticket->payment_rule_notes }}" maxlength="3000">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" rows="4" name="description" maxlength="10000">{{ $ticket->description }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notas adicionales</label>
                                    <textarea class="form-control" rows="3" name="additional_notes" maxlength="10000">{{ $ticket->additional_notes }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Agregar archivos</label>
                                    <input class="form-control" type="file" name="files[]" multiple>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-primary">Guardar cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card mb-5">
                    <div class="card-header"><h3 class="card-title">Chat y comentarios</h3></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('maintenance.messages', $ticket) }}" class="row g-3 mb-5">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">Canal</label>
                                <select class="form-select" name="channel">
                                    @foreach ($messageChannels as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Mensaje</label>
                                <input class="form-control" name="message" maxlength="5000" required>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-light-primary">Enviar</button>
                            </div>
                        </form>
                        <div class="d-flex flex-column gap-4">
                            @forelse ($ticket->messages as $message)
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="fw-semibold">{{ $message->sender?->name ?? 'Sistema' }}</div>
                                        <div class="text-muted fs-8">{{ $message->created_at?->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <div class="text-muted fs-8 mb-1">{{ $messageChannels[$message->channel] ?? $message->channel }}</div>
                                    <div>{{ $message->message }}</div>
                                </div>
                            @empty
                                <div class="text-muted">Sin mensajes.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-5">
                    <div class="card-header"><h3 class="card-title">Estado</h3></div>
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
                                    <textarea class="form-control" name="notes" rows="3" maxlength="3000"></textarea>
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

                @if ($canManageAssignments)
                    <div class="card mb-5">
                        <div class="card-header"><h3 class="card-title">Asignación de técnico/proveedor</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('maintenance.assign', $ticket) }}" class="row g-3 mb-4">
                                @csrf
                                <div class="col-12">
                                    <label class="form-label">Técnico/proveedor</label>
                                    <select class="form-select" name="provider_id" required>
                                        <option value="">Seleccionar...</option>
                                        @foreach ($providers as $provider)
                                            <option value="{{ $provider->id }}" {{ $ticket->current_provider_id === $provider->id ? 'selected' : '' }}>
                                                {{ $provider->name }} · {{ \App\Models\MaintenanceProvider::TYPE_LABELS[$provider->type] ?? $provider->type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Agendar visita</label>
                                    <input class="form-control" type="datetime-local" name="scheduled_visit_at" value="{{ $ticket->scheduled_visit_at?->format('Y-m-d\\TH:i') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Nota</label>
                                    <textarea class="form-control" name="notes" rows="2" maxlength="3000"></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-light-primary">Asignar/Reasignar</button>
                                </div>
                            </form>
                            <div class="d-flex flex-column gap-3">
                                @forelse ($ticket->assignments as $assignment)
                                    <div class="border rounded p-3">
                                        <div class="fw-semibold">{{ $assignment->provider?->name ?? '-' }}</div>
                                        <div class="text-muted fs-8">
                                            {{ $assignment->assigned_at?->format('d/m/Y H:i') }} ·
                                            {{ $assignment->is_current ? 'Actual' : 'Histórico' }}
                                        </div>
                                        <div class="text-muted fs-8">{{ $assignment->notes ?: '-' }}</div>
                                    </div>
                                @empty
                                    <div class="text-muted">Sin historial de asignación.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                @if ($canManageCosts)
                    <div class="card mb-5">
                        <div class="card-header"><h3 class="card-title">Costos, facturas y evidencias</h3></div>
                        <div class="card-body">
                            @php
                                $costRow = $ticket->costs->first();
                            @endphp
                            <form method="POST" action="{{ route('maintenance.costs', $ticket) }}" enctype="multipart/form-data" class="row g-3">
                                @csrf
                                @method('PUT')
                                <div class="col-md-6">
                                    <label class="form-label">Mano de obra</label>
                                    <input class="form-control" type="number" name="labor_cost" step="0.01" min="0" value="{{ $costRow?->labor_cost ?? 0 }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Materiales</label>
                                    <input class="form-control" type="number" name="material_cost" step="0.01" min="0" value="{{ $costRow?->material_cost ?? 0 }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Anticipo</label>
                                    <input class="form-control" type="number" name="advance_cost" step="0.01" min="0" value="{{ $costRow?->advance_cost ?? 0 }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Costo final</label>
                                    <input class="form-control" type="number" name="final_cost" step="0.01" min="0" value="{{ $costRow?->final_cost ?? 0 }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Moneda</label>
                                    <input class="form-control" name="currency" value="{{ $costRow?->currency ?? 'MXN' }}" maxlength="10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Quién paga</label>
                                    <select class="form-select" name="payer">
                                        <option value="">Sin definir</option>
                                        @foreach ($payerOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->payer === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Regla</label>
                                    <select class="form-select" name="payment_rule">
                                        <option value="">Sin definir</option>
                                        @foreach ($paymentRuleOptions as $key => $label)
                                            <option value="{{ $key }}" {{ $ticket->payment_rule === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Notas regla</label>
                                    <input class="form-control" name="payment_rule_notes" value="{{ $ticket->payment_rule_notes }}" maxlength="3000">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notas de costos</label>
                                    <textarea class="form-control" rows="3" name="notes">{{ $costRow?->notes }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Facturas</label>
                                    <input class="form-control" type="file" name="invoice_files[]" multiple>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Evidencias (foto/video/documento)</label>
                                    <input class="form-control" type="file" name="evidence_files[]" multiple>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Firmas</label>
                                    <input class="form-control" type="file" name="signature_files[]" multiple>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-light-primary">Guardar costos y evidencias</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card mb-5">
                    <div class="card-header"><h3 class="card-title">Archivos y evidencias</h3></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('maintenance.files', $ticket) }}" enctype="multipart/form-data" class="row g-3 mb-4">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="kind">
                                    @foreach (\App\Models\MaintenanceTicketFile::KIND_LABELS as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Archivos</label>
                                <input class="form-control" type="file" name="files[]" multiple required>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-light-primary">Subir</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-muted">
                                        <th>Tipo</th>
                                        <th>Archivo</th>
                                        <th>Subido por</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ticket->files as $file)
                                        <tr>
                                            <td>{{ \App\Models\MaintenanceTicketFile::KIND_LABELS[$file->kind] ?? $file->kind }}</td>
                                            <td>
                                                <a href="{{ $file->url }}" target="_blank">{{ $file->original_name }}</a>
                                                <div class="text-muted fs-8">{{ $file->mime_type }} · {{ number_format((int) $file->size / 1024, 0) }} KB · {{ $file->is_compressed ? 'comprimido' : 'original' }}</div>
                                            </td>
                                            <td>{{ $file->uploader?->name ?? '-' }}</td>
                                            <td>{{ $file->created_at?->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-8">Sin archivos.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Historial de cambios</h3></div>
                    <div class="card-body">
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
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Historial de notificaciones</h3></div>
                    <div class="card-body">
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
@endsection
