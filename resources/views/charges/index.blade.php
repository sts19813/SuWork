@extends('layouts.app')

@section('title', 'Cobranza | SuWork')

@section('content')
    <div class="py-10 charges-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information fs-2hx text-warning me-4"></i>
                <div class="fw-semibold">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-cross-circle fs-2hx text-danger me-4"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Cobranza</h1>
                <div class="text-muted fs-6">Cargos, pagos y conciliacion</div>
            </div>
            <div class="d-flex flex-wrap gap-3">
                <button type="button" class="btn btn-light-primary fw-bold" data-bs-toggle="modal" data-bs-target="#bulkChargeModal">
                    <i class="ki-outline ki-calendar-add fs-4 me-1"></i> Generar cobranza
                </button>
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createChargeModal">
                    <i class="ki-outline ki-plus fs-4 me-1"></i> Nuevo cargo
                </button>
            </div>
        </div>

        <div class="row g-5 mb-8">
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-4 py-6">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-light-warning">
                                <i class="ki-outline ki-time text-warning fs-3"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7">Por cobrar</div>
                            <div class="fw-bold fs-2">${{ number_format(max(0, $stats['pending_amount']), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-4 py-6">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-light-danger">
                                <i class="ki-outline ki-information-3 text-danger fs-3"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7">Vencido</div>
                            <div class="fw-bold fs-2">${{ number_format(max(0, $stats['overdue_amount']), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-4 py-6">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-light-success">
                                <i class="ki-outline ki-check text-success fs-3"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7">Cobrado ({{ $currentMonthLabel }})</div>
                            <div class="fw-bold fs-2">${{ number_format(max(0, $stats['collected_month']), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-4 py-6">
                        <div class="symbol symbol-40px">
                            <span class="symbol-label bg-light-info">
                                <i class="ki-outline ki-dollar text-info fs-3"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7">Pend. validacion</div>
                            <div class="fw-bold fs-2">{{ $stats['pending_validation'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 d-flex align-items-center gap-6 fs-5 fw-semibold border-bottom pb-3">
            <span class="text-primary border-bottom border-2 border-primary pb-2">Cargos ({{ $stats['charges_count'] }})</span>
            <span class="text-muted">Pagos ({{ $stats['payments_count'] }})</span>
        </div>

        <div class="card mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('charges.index') }}" class="row g-4 align-items-end">
                    <div class="col-lg-9">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input type="text" name="q" class="form-control"
                            placeholder="Concepto, inquilino, propiedad..." value="{{ $search }}">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select">
                            @foreach ($statusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" {{ $status === $statusValue ? 'selected' : '' }}>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-3">
                        <a href="{{ route('charges.index') }}" class="btn btn-light">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-5 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase gs-0">
                                <th class="min-w-220px">Concepto</th>
                                <th class="min-w-220px">Inquilino / Propiedad</th>
                                <th class="min-w-130px">Vencimiento</th>
                                <th class="min-w-140px">Monto</th>
                                <th class="min-w-120px">Estado</th>
                                <th class="min-w-320px text-end">Opciones</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($charges as $charge)
                                @php
                                    $canRegisterPayment = in_array(
                                        $charge->status,
                                        [\App\Models\Charge::STATUS_PENDING, \App\Models\Charge::STATUS_PARTIAL, \App\Models\Charge::STATUS_IN_VALIDATION],
                                        true,
                                    );
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold text-gray-900 fs-6">{{ $charge->concept }}</div>
                                        <div class="text-muted fs-7">{{ $charge->type_label }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-gray-900 fs-6">{{ $charge->tenant?->full_name ?? '-' }}</div>
                                        <div class="text-muted fs-7">
                                            {{ $charge->property?->internal_reference ?: $charge->property?->internal_name ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $charge->due_date?->format('d M Y') ?? '-' }}
                                    </td>
                                    <td>
                                        <div class="fw-bold text-gray-900">${{ number_format((float) $charge->amount, 2) }}</div>
                                        @if ($charge->outstanding_amount > 0 && $charge->status !== \App\Models\Charge::STATUS_CANCELED)
                                            <div class="text-muted fs-7">Saldo: ${{ number_format($charge->outstanding_amount, 2) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $charge->status_badge_class }}">
                                            {{ $charge->display_status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <a href="{{ route('charges.show', $charge) }}" class="btn btn-sm btn-light">
                                                Ver
                                            </a>

                                            @if ($canRegisterPayment)
                                                <button type="button" class="btn btn-sm btn-success"
                                                    data-register-payment
                                                    data-charge="{{ $charge->uuid }}"
                                                    data-action="{{ route('charges.payments.store', $charge) }}"
                                                    data-concept="{{ $charge->concept }}"
                                                    data-outstanding="{{ number_format($charge->outstanding_amount, 2, '.', '') }}">
                                                    Registrar pago
                                                </button>
                                            @endif

                                            <a href="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}"
                                                target="_blank" class="btn btn-sm btn-light-primary">
                                                Abrir link
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light"
                                                data-copy-link="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}">
                                                Copiar link
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-16 text-muted">No hay cargos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $charges->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="createChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('charges.store') }}" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Nuevo cargo</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->createCharge->any())
                            <div class="alert alert-danger mb-6">
                                Revisa los datos del formulario.
                            </div>
                        @endif

                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Propiedad</label>
                                <select name="property_id" class="form-select" id="chargeProperty" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($properties as $property)
                                        <option value="{{ $property->id }}" data-tenant-id="{{ $property->tenant_id }}"
                                            {{ (string) old('property_id') === (string) $property->id ? 'selected' : '' }}>
                                            {{ $property->internal_name }}{{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('property_id', 'createCharge')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Inquilino</label>
                                <select name="tenant_id" class="form-select" id="chargeTenant" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" {{ (string) old('tenant_id') === (string) $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tenant_id', 'createCharge')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Tipo</label>
                                <select name="type" class="form-select" required>
                                    @foreach ($typeOptions as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" {{ old('type', 'rent') === $typeValue ? 'selected' : '' }}>
                                            {{ $typeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha vencimiento</label>
                                <input type="date" name="due_date" class="form-control"
                                    value="{{ old('due_date') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Monto (MXN)</label>
                                <input type="number" min="0.01" step="0.01" name="amount" class="form-control"
                                    value="{{ old('amount') }}" placeholder="0.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Mes (periodo)</label>
                                <input type="number" min="1" max="12" name="period_month" class="form-control"
                                    value="{{ old('period_month', now()->month) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Anio (periodo)</label>
                                <input type="number" min="2000" max="2200" name="period_year" class="form-control"
                                    value="{{ old('period_year', now()->year) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Concepto</label>
                                <input type="text" name="concept" class="form-control"
                                    value="{{ old('concept') }}" placeholder="Ej. Renta Enero 2026" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" class="form-control" rows="4"
                                    placeholder="Notas internas">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear cargo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="registerPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="registerPaymentForm" enctype="multipart/form-data" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Registrar pago</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="bg-light rounded p-4 mb-6">
                            <div class="text-muted fs-7 mb-1">Cargo a cubrir</div>
                            <div class="fw-bold fs-4 mb-1" id="registerPaymentConcept">-</div>
                            <div class="text-muted fs-6">
                                Saldo pendiente: <span class="text-danger fw-bold" id="registerPaymentOutstanding">$0.00</span>
                            </div>
                        </div>

                        @if ($errors->registerPayment->any())
                            <div class="alert alert-danger mb-5">
                                Revisa la captura del pago.
                            </div>
                        @endif

                        <input type="hidden" name="charge_uuid" id="registerPaymentChargeUuid" value="{{ old('charge_uuid') }}">

                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Monto (MXN)</label>
                                <input type="number" min="0.01" step="0.01" name="amount" id="registerPaymentAmount"
                                    value="{{ old('amount') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha de pago</label>
                                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}"
                                    class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Metodo de pago</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($paymentMethods as $methodValue => $methodLabel)
                                        <option value="{{ $methodValue }}" {{ old('payment_method') === $methodValue ? 'selected' : '' }}>
                                            {{ $methodLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Referencia / Folio</label>
                                <input type="text" name="reference" class="form-control"
                                    value="{{ old('reference') }}" placeholder="Numero de referencia">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Comprobante de pago (imagen)</label>
                                <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" class="form-control" rows="3"
                                    placeholder="Notas internas">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Registrar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('charges.bulk.store') }}" id="bulkChargeForm" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Generar cobranza mensual</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->generateCharges->any())
                            <div class="alert alert-danger mb-5">
                                Revisa los datos para generar la cobranza.
                            </div>
                        @endif

                        <div class="row g-5 mb-6">
                            <div class="col-md-8">
                                <label class="form-label required">Inquilino</label>
                                <select name="tenant_id" id="bulkTenantId" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}" {{ (string) old('tenant_id') === (string) $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Dia de pago</label>
                                <input type="number" min="1" max="31" id="bulkPaymentDay" name="payment_day"
                                    value="{{ old('payment_day', 5) }}" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mb-5">
                            <button type="button" class="btn btn-light-primary" id="previewBulkChargesBtn">
                                Previsualizar tabla
                            </button>
                        </div>

                        <div class="border rounded p-4 d-none" id="bulkPreviewContainer">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0">Vista previa</h4>
                                <span class="text-muted fs-7" id="bulkSummaryText"></span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-row-bordered align-middle">
                                    <thead>
                                        <tr class="text-muted text-uppercase fs-8">
                                            <th>Propiedad</th>
                                            <th>Periodo</th>
                                            <th>Vencimiento</th>
                                            <th>Monto</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulkPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear cargos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const propertySelect = document.getElementById('chargeProperty');
            const tenantSelect = document.getElementById('chargeTenant');
            const registerPaymentForm = document.getElementById('registerPaymentForm');
            const registerPaymentConcept = document.getElementById('registerPaymentConcept');
            const registerPaymentOutstanding = document.getElementById('registerPaymentOutstanding');
            const registerPaymentAmount = document.getElementById('registerPaymentAmount');
            const registerPaymentChargeUuid = document.getElementById('registerPaymentChargeUuid');

            if (propertySelect && tenantSelect) {
                propertySelect.addEventListener('change', () => {
                    const selected = propertySelect.options[propertySelect.selectedIndex];
                    const tenantId = selected?.dataset?.tenantId;
                    if (tenantId) {
                        tenantSelect.value = tenantId;
                    }
                });
            }

            document.querySelectorAll('[data-copy-link]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const link = button.getAttribute('data-copy-link');
                    if (!link) {
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(link);
                        button.textContent = 'Copiado';
                        setTimeout(() => {
                            button.textContent = 'Copiar link';
                        }, 1400);
                    } catch (error) {
                        window.prompt('Copia este link:', link);
                    }
                });
            });

            document.querySelectorAll('[data-register-payment]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!registerPaymentForm) {
                        return;
                    }

                    const action = button.getAttribute('data-action') || '';
                    const chargeUuid = button.getAttribute('data-charge') || '';
                    const concept = button.getAttribute('data-concept') || '-';
                    const outstanding = parseFloat(button.getAttribute('data-outstanding') || '0');

                    registerPaymentForm.setAttribute('action', action);
                    if (registerPaymentChargeUuid) {
                        registerPaymentChargeUuid.value = chargeUuid;
                    }
                    registerPaymentConcept.textContent = concept;
                    registerPaymentOutstanding.textContent = `$${outstanding.toFixed(2)}`;
                    registerPaymentAmount.value = outstanding.toFixed(2);
                    registerPaymentAmount.max = outstanding.toFixed(2);

                    const modalEl = document.getElementById('registerPaymentModal');
                    if (!modalEl) {
                        return;
                    }
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                });
            });

            const previewBtn = document.getElementById('previewBulkChargesBtn');
            const bulkTenantId = document.getElementById('bulkTenantId');
            const bulkPaymentDay = document.getElementById('bulkPaymentDay');
            const bulkPreviewContainer = document.getElementById('bulkPreviewContainer');
            const bulkPreviewBody = document.getElementById('bulkPreviewBody');
            const bulkSummaryText = document.getElementById('bulkSummaryText');

            previewBtn?.addEventListener('click', async () => {
                const tenantId = bulkTenantId?.value;
                const paymentDay = bulkPaymentDay?.value;

                if (!tenantId || !paymentDay) {
                    alert('Selecciona inquilino y dia de pago.');
                    return;
                }

                previewBtn.disabled = true;
                previewBtn.textContent = 'Cargando...';

                try {
                    const response = await fetch("{{ route('charges.bulk.preview') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            tenant_id: tenantId,
                            payment_day: paymentDay,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error('No fue posible generar la vista previa.');
                    }

                    const preview = data.preview || {};
                    const rows = preview.rows || [];
                    const summary = preview.summary || {};

                    bulkPreviewBody.innerHTML = '';
                    if (!rows.length) {
                        bulkPreviewBody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center text-muted py-8">No se encontraron cargos para ese inquilino.</td>
                            </tr>
                        `;
                    } else {
                        rows.forEach((row) => {
                            const alreadyClass = row.already_exists ? 'badge-light-warning text-warning' : 'badge-light-success text-success';
                            const alreadyLabel = row.already_exists ? 'Ya existe' : 'Se creara';
                            bulkPreviewBody.insertAdjacentHTML('beforeend', `
                                <tr>
                                    <td>${row.property_name}</td>
                                    <td>${String(row.period_month).padStart(2, '0')}/${row.period_year}</td>
                                    <td>${row.due_date}</td>
                                    <td>$${Number(row.amount).toFixed(2)}</td>
                                    <td><span class="badge ${alreadyClass}">${alreadyLabel}</span></td>
                                </tr>
                            `);
                        });
                    }

                    bulkSummaryText.textContent = `Total: ${summary.total || 0} | Nuevos: ${summary.to_create || 0} | Existentes: ${summary.already_exists || 0}`;
                    bulkPreviewContainer?.classList.remove('d-none');
                } catch (error) {
                    alert(error.message || 'No fue posible generar la vista previa.');
                } finally {
                    previewBtn.disabled = false;
                    previewBtn.textContent = 'Previsualizar tabla';
                }
            });
        })();
    </script>

    @if ($errors->createCharge->any())
        <script>
            (() => {
                const modalEl = document.getElementById('createChargeModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif

    @if ($errors->registerPayment->any())
        <script>
            (() => {
                const modalEl = document.getElementById('registerPaymentModal');
                if (!modalEl) return;

                const form = document.getElementById('registerPaymentForm');
                const chargeUuid = @json(old('charge_uuid'));
                if (form && chargeUuid) {
                    form.setAttribute('action', "{{ url('/cobranza') }}/" + chargeUuid + "/pagos");
                }
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif

    @if ($errors->generateCharges->any())
        <script>
            (() => {
                const modalEl = document.getElementById('bulkChargeModal');
                if (!modalEl) return;
                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
@endpush
