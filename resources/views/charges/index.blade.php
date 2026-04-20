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
                <h1 class="mb-1 fw-bold text-dark">
                    @if ($selectedProperty)
                        Cobranza de {{ $selectedProperty->internal_name }}
                    @else
                        Cobranza
                    @endif
                </h1>
                <div class="text-muted fs-6">
                    @if ($selectedProperty)
                        Cargos, pagos y conciliacion de esta propiedad
                    @else
                        Cargos, pagos y conciliacion
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-3">
                @if ($selectedProperty)
                    <a href="{{ route('properties.show', $selectedProperty) }}" class="btn btn-light fw-bold">
                        <i class="ki-outline ki-home fs-4 me-1"></i> Ver propiedad
                    </a>
                @endif
                <button type="button" class="btn btn-light-primary fw-bold" data-bs-toggle="modal" data-bs-target="#bulkChargeModal">
                    <i class="ki-outline ki-calendar-add fs-4 me-1"></i> Generar cobranza
                </button>
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createChargeModal">
                    <i class="ki-outline ki-plus fs-4 me-1"></i> Nuevo cargo
                </button>
            </div>
        </div>

        @if ($selectedProperty && $showPropertySetupCard)
            @php
                $selectedSetupTenantId = (string) old('tenant_id', $selectedProperty->tenant_id ?: '');
                $setupContractStartsAt = old('contract_starts_at', $selectedProperty->contract_starts_at?->format('Y-m-d'));
                $setupContractExpiresAt = old('contract_expires_at', $selectedProperty->contract_expires_at?->format('Y-m-d'));
                $setupMonthlyRentPrice = old('monthly_rent_price', number_format((float) ($selectedProperty->monthly_rent_price ?? 0), 2, '.', ''));
                $setupChargeDay = old('charge_day', $selectedProperty->charge_day ?: $selectedProperty->contract_starts_at?->day);
                $setupChargeToleranceDays = old('charge_tolerance_days', (int) ($selectedProperty->charge_tolerance_days ?? 0));
                $initialPropertySetupPlan = old('rent_charge_plan', $selectedProperty->rent_charge_plan ?? []);
                $initialPropertySetupPlan = collect($initialPropertySetupPlan)
                    ->filter(fn($row) => is_array($row))
                    ->values()
                    ->all();
            @endphp

            <div class="card mb-8">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Configuracion de cobranza de la propiedad</h3>
                </div>
                <div class="card-body pt-0">
                    @if ($errors->propertySetup->any())
                        <div class="alert alert-danger mb-6">
                            <div class="fw-bold mb-2">Revisa la configuracion de cobranza:</div>
                            <ul class="mb-0 ps-5">
                                @foreach ($errors->propertySetup->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('charges.properties.setup', $selectedProperty) }}" id="propertySetupForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="force_assignment" id="propertySetupForceAssignment" value="0">
                        <div id="property-setup-plan-inputs"></div>

                        <div class="notice d-flex bg-light-primary border border-primary border-dashed rounded p-4 mb-6">
                            <span class="text-primary">Nota: Podras cambiar el estado de la propiedad en cualquier momento desde su expediente.</span>
                        </div>

                        <div class="row g-6">
                            <div class="col-lg-6">
                                <label class="form-label">Inquilino (opcional)</label>
                                <select name="tenant_id" id="propertySetupTenant" class="form-select @error('tenant_id', 'propertySetup') is-invalid @enderror">
                                    <option value="">Sin asignar</option>
                                    @foreach ($propertySetupTenants as $tenant)
                                        @php
                                            $setupCheck = $tenantAssignmentChecks[(string) $tenant->id] ?? ['missing' => [], 'is_complete' => true];
                                        @endphp
                                        <option value="{{ $tenant->id }}"
                                            data-missing='@json($setupCheck['missing'])'
                                            {{ $selectedSetupTenantId === (string) $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->full_name }} {{ $tenant->phone_primary ? '- ' . $tenant->phone_primary : '' }}{{ $setupCheck['is_complete'] ? '' : ' (incompleto)' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tenant_id', 'propertySetup')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="text-muted fs-8 mt-2">
                                    ¿No aparece? <a href="{{ route('tenants.index') }}" target="_blank">Crear inquilino</a>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Contrato inicia (opcional)</label>
                                <input type="date" name="contract_starts_at" id="propertySetupContractStartsAt"
                                    class="form-control @error('contract_starts_at', 'propertySetup') is-invalid @enderror"
                                    value="{{ $setupContractStartsAt }}">
                                @error('contract_starts_at', 'propertySetup')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Contrato vence (opcional)</label>
                                <input type="date" name="contract_expires_at" id="propertySetupContractExpiresAt"
                                    class="form-control @error('contract_expires_at', 'propertySetup') is-invalid @enderror"
                                    value="{{ $setupContractExpiresAt }}">
                                @error('contract_expires_at', 'propertySetup')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Precio renta mensual</label>
                                <input type="number" name="monthly_rent_price" id="propertySetupMonthlyRentPrice"
                                    class="form-control @error('monthly_rent_price', 'propertySetup') is-invalid @enderror"
                                    min="0" step="0.01" value="{{ $setupMonthlyRentPrice }}">
                                @error('monthly_rent_price', 'propertySetup')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="text-muted fs-8 mt-2">Si está vacío se considera 0. Es necesario que sea mayor a 0 para generar pagos.</div>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">Dia de cobro</label>
                                <input type="number" name="charge_day" id="propertySetupChargeDay"
                                    class="form-control @error('charge_day', 'propertySetup') is-invalid @enderror"
                                    min="1" max="31" step="1" value="{{ $setupChargeDay }}">
                                @error('charge_day', 'propertySetup')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">Tolerancia (dias)</label>
                                <input type="number" name="charge_tolerance_days" id="propertySetupChargeToleranceDays"
                                    class="form-control @error('charge_tolerance_days', 'propertySetup') is-invalid @enderror"
                                    min="0" max="31" step="1" value="{{ $setupChargeToleranceDays }}">
                                @error('charge_tolerance_days', 'propertySetup')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-5 bg-light">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                                        <div>
                                            <div class="fw-bold">Lista de pagos de renta</div>
                                            <div class="text-muted fs-8" id="propertySetupPlanSummary">
                                                Configura contrato y renta mensual para generar la lista automatica.
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#propertySetupPlanModal">
                                            Ver lista de pagos
                                        </button>
                                    </div>
                                    <div class="text-muted fs-8">
                                        Total de pagos generados: <span class="fw-bold" id="propertySetupPlanRowsCount">0</span>
                                    </div>
                                </div>
                                @error('rent_charge_plan', 'propertySetup')
                                    <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-6">
                            <button type="submit" class="btn btn-primary">Guardar y generar pagos</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="propertySetupPlanModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title">Lista de pagos</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                                <i class="ki-outline ki-cross fs-1"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light-primary py-3 px-4 mb-5">
                                El monto inicia con la renta mensual y puedes ajustarlo por periodo para contratos de mas de un anio.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-row-bordered align-middle">
                                    <thead>
                                        <tr class="text-muted text-uppercase fs-8">
                                            <th>Periodo</th>
                                            <th>Vencimiento</th>
                                            <th>Monto (MXN)</th>
                                            <th>Concepto</th>
                                        </tr>
                                    </thead>
                                    <tbody id="propertySetupPlanTableBody">
                                        <tr id="propertySetupPlanEmptyState">
                                            <td colspan="4" class="text-center text-muted py-8">No hay pagos configurados.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

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
                    @if ($selectedProperty)
                        <input type="hidden" name="property" value="{{ $selectedProperty->uuid }}">
                    @endif
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
                        <a href="{{ route('charges.index', $selectedProperty ? ['property' => $selectedProperty->uuid] : []) }}" class="btn btn-light">Limpiar</a>
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
                                    $canEditCharge = in_array(
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
                                            <a href="{{ route('charges.show', $charge) }}{{ $selectedProperty ? '?property=' . urlencode($selectedProperty->uuid) : '' }}" class="btn btn-sm btn-light">
                                                Ver
                                            </a>

                                            @if ($canEditCharge)
                                                <button type="button" class="btn btn-sm btn-light-primary"
                                                    data-edit-charge
                                                    data-action="{{ route('charges.update', $charge) }}"
                                                    data-charge="{{ $charge->uuid }}"
                                                    data-type="{{ $charge->type }}"
                                                    data-due-date="{{ $charge->due_date?->format('Y-m-d') }}"
                                                    data-amount="{{ number_format((float) $charge->amount, 2, '.', '') }}"
                                                    data-period-month="{{ $charge->period_month }}"
                                                    data-period-year="{{ $charge->period_year }}"
                                                    data-concept="{{ $charge->concept }}"
                                                    data-notes="{{ $charge->notes }}">
                                                    Editar cargo
                                                </button>
                                            @endif

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
                    @if ($selectedProperty)
                        <input type="hidden" name="property_context" value="{{ $selectedProperty->uuid }}">
                    @endif
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
                                            {{ (string) old('property_id', $selectedProperty?->id) === (string) $property->id ? 'selected' : '' }}>
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

    <div class="modal fade" id="editChargeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="editChargeForm" class="h-100 d-flex flex-column">
                    @csrf
                    @method('PUT')
                    @if ($selectedProperty)
                        <input type="hidden" name="property_context" value="{{ $selectedProperty->uuid }}">
                    @endif
                    <div class="modal-header">
                        <h3 class="modal-title">Editar cargo</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->updateCharge->any())
                            <div class="alert alert-danger mb-5">
                                Revisa la informacion del cargo.
                            </div>
                        @endif

                        <input type="hidden" name="charge_uuid" id="editChargeUuid" value="{{ old('charge_uuid') }}">

                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Tipo</label>
                                <select name="type" id="editChargeType" class="form-select" required>
                                    @foreach ($typeOptions as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" {{ old('type') === $typeValue ? 'selected' : '' }}>
                                            {{ $typeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Fecha vencimiento</label>
                                <input type="date" name="due_date" id="editChargeDueDate" class="form-control"
                                    value="{{ old('due_date') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Monto (MXN)</label>
                                <input type="number" min="0.01" step="0.01" name="amount" id="editChargeAmount" class="form-control"
                                    value="{{ old('amount') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Mes (periodo)</label>
                                <input type="number" min="1" max="12" name="period_month" id="editChargePeriodMonth" class="form-control"
                                    value="{{ old('period_month') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Anio (periodo)</label>
                                <input type="number" min="2000" max="2200" name="period_year" id="editChargePeriodYear" class="form-control"
                                    value="{{ old('period_year') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Concepto</label>
                                <input type="text" name="concept" id="editChargeConcept" class="form-control"
                                    value="{{ old('concept') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" id="editChargeNotes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
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
                    @if ($selectedProperty)
                        <input type="hidden" name="property_context" value="{{ $selectedProperty->uuid }}">
                    @endif
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
                        @php
                            $bulkChargeDay = old('charge_day', $selectedProperty?->charge_day ?: $selectedProperty?->contract_starts_at?->day);
                            $bulkChargeToleranceDays = old('charge_tolerance_days', (int) ($selectedProperty?->charge_tolerance_days ?? 0));
                        @endphp

                        <div class="row g-5 mb-6">
                            <div class="col-md-6">
                                <label class="form-label required">Propiedad</label>
                                <select name="property_id" id="bulkPropertyId" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($chargeableProperties as $property)
                                        <option value="{{ $property->id }}"
                                            data-tenant-name="{{ $property->tenant?->full_name }}"
                                            data-contract-start="{{ $property->contract_starts_at?->format('Y-m-d') }}"
                                            data-contract-expires="{{ $property->contract_expires_at?->format('Y-m-d') }}"
                                            data-monthly-rent="{{ number_format((float) ($property->monthly_rent_price ?? 0), 2, '.', '') }}"
                                            data-charge-day="{{ $property->charge_day }}"
                                            data-charge-tolerance-days="{{ (int) ($property->charge_tolerance_days ?? 0) }}"
                                            {{ (string) old('property_id', $selectedProperty?->id) === (string) $property->id ? 'selected' : '' }}>
                                            {{ $property->internal_name }}{{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Inquilino actual</label>
                                <input type="text" id="bulkTenantName" class="form-control" readonly
                                    value="">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contrato inicia (opcional)</label>
                                <input type="date" name="contract_starts_at" id="bulkContractStartsAt" class="form-control"
                                    value="{{ old('contract_starts_at', $selectedProperty?->contract_starts_at?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contrato vence (opcional)</label>
                                <input type="date" name="contract_expires_at" id="bulkContractExpiresAt" class="form-control"
                                    value="{{ old('contract_expires_at', $selectedProperty?->contract_expires_at?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio renta mensual</label>
                                <input type="number" name="monthly_rent_price" id="bulkMonthlyRentPrice" class="form-control"
                                    min="0" step="0.01"
                                    value="{{ old('monthly_rent_price', number_format((float) ($selectedProperty?->monthly_rent_price ?? 0), 2, '.', '')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Dia de cobro</label>
                                <input type="number" name="charge_day" id="bulkChargeDay" class="form-control"
                                    min="1" max="31" step="1" value="{{ $bulkChargeDay }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tolerancia (dias)</label>
                                <input type="number" name="charge_tolerance_days" id="bulkChargeToleranceDays" class="form-control"
                                    min="0" max="31" step="1" value="{{ $bulkChargeToleranceDays }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mb-5">
                            <button type="button" class="btn btn-light-primary" id="previewBulkChargesBtn">
                                Ver lista de pagos
                            </button>
                        </div>

                        <div id="bulkChargeRowsContainer"></div>

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
                                            <th>Inquilino</th>
                                            <th>Periodo</th>
                                            <th>Vencimiento</th>
                                            <th>Monto (editable)</th>
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

            const editChargeForm = document.getElementById('editChargeForm');
            const editChargeUuid = document.getElementById('editChargeUuid');
            const editChargeType = document.getElementById('editChargeType');
            const editChargeDueDate = document.getElementById('editChargeDueDate');
            const editChargeAmount = document.getElementById('editChargeAmount');
            const editChargePeriodMonth = document.getElementById('editChargePeriodMonth');
            const editChargePeriodYear = document.getElementById('editChargePeriodYear');
            const editChargeConcept = document.getElementById('editChargeConcept');
            const editChargeNotes = document.getElementById('editChargeNotes');

            document.querySelectorAll('[data-edit-charge]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!editChargeForm) {
                        return;
                    }

                    editChargeForm.setAttribute('action', button.getAttribute('data-action') || '');
                    if (editChargeUuid) editChargeUuid.value = button.getAttribute('data-charge') || '';
                    if (editChargeType) editChargeType.value = button.getAttribute('data-type') || 'rent';
                    if (editChargeDueDate) editChargeDueDate.value = button.getAttribute('data-due-date') || '';
                    if (editChargeAmount) editChargeAmount.value = button.getAttribute('data-amount') || '';
                    if (editChargePeriodMonth) editChargePeriodMonth.value = button.getAttribute('data-period-month') || '';
                    if (editChargePeriodYear) editChargePeriodYear.value = button.getAttribute('data-period-year') || '';
                    if (editChargeConcept) editChargeConcept.value = button.getAttribute('data-concept') || '';
                    if (editChargeNotes) editChargeNotes.value = button.getAttribute('data-notes') || '';

                    const modalEl = document.getElementById('editChargeModal');
                    if (!modalEl) {
                        return;
                    }
                    new bootstrap.Modal(modalEl).show();
                });
            });

            const previewBtn = document.getElementById('previewBulkChargesBtn');
            const bulkChargeForm = document.getElementById('bulkChargeForm');
            const bulkPropertyId = document.getElementById('bulkPropertyId');
            const bulkTenantName = document.getElementById('bulkTenantName');
            const bulkContractStartsAt = document.getElementById('bulkContractStartsAt');
            const bulkContractExpiresAt = document.getElementById('bulkContractExpiresAt');
            const bulkMonthlyRentPrice = document.getElementById('bulkMonthlyRentPrice');
            const bulkChargeDay = document.getElementById('bulkChargeDay');
            const bulkChargeToleranceDays = document.getElementById('bulkChargeToleranceDays');
            const bulkPreviewContainer = document.getElementById('bulkPreviewContainer');
            const bulkPreviewBody = document.getElementById('bulkPreviewBody');
            const bulkSummaryText = document.getElementById('bulkSummaryText');
            const bulkChargeRowsContainer = document.getElementById('bulkChargeRowsContainer');
            let bulkRows = [];

            const toMoney = (value, fallback = 0) => {
                const parsed = Number.parseFloat(String(value ?? '').replace(/,/g, ''));
                if (!Number.isFinite(parsed)) {
                    return fallback;
                }

                return Math.round(parsed * 100) / 100;
            };

            const propertySetupForm = document.getElementById('propertySetupForm');
            if (propertySetupForm) {
                const propertySetupTenant = document.getElementById('propertySetupTenant');
                const propertySetupForceAssignment = document.getElementById('propertySetupForceAssignment');
                const propertySetupContractStartsAt = document.getElementById('propertySetupContractStartsAt');
                const propertySetupContractExpiresAt = document.getElementById('propertySetupContractExpiresAt');
                const propertySetupMonthlyRentPrice = document.getElementById('propertySetupMonthlyRentPrice');
                const propertySetupChargeDay = document.getElementById('propertySetupChargeDay');
                const propertySetupPlanInputs = document.getElementById('property-setup-plan-inputs');
                const propertySetupPlanTableBody = document.getElementById('propertySetupPlanTableBody');
                const propertySetupPlanSummary = document.getElementById('propertySetupPlanSummary');
                const propertySetupPlanRowsCount = document.getElementById('propertySetupPlanRowsCount');
                const propertySetupPlanEmptyState = document.getElementById('propertySetupPlanEmptyState');
                const initialPropertySetupPlan = @json($selectedProperty ? ($initialPropertySetupPlan ?? []) : []);

                const monthNames = [
                    'Enero',
                    'Febrero',
                    'Marzo',
                    'Abril',
                    'Mayo',
                    'Junio',
                    'Julio',
                    'Agosto',
                    'Septiembre',
                    'Octubre',
                    'Noviembre',
                    'Diciembre',
                ];

                const parseIsoDate = (value) => {
                    const stringValue = String(value || '').trim();
                    const parts = stringValue.split('-');
                    if (parts.length !== 3) {
                        return null;
                    }

                    const year = Number.parseInt(parts[0], 10);
                    const month = Number.parseInt(parts[1], 10);
                    const day = Number.parseInt(parts[2], 10);
                    if (!year || month < 1 || month > 12 || day < 1 || day > 31) {
                        return null;
                    }

                    return { year, month, day };
                };
                const toDay = (value) => {
                    const parsed = Number.parseInt(String(value || ''), 10);
                    if (!Number.isInteger(parsed) || parsed < 1 || parsed > 31) {
                        return null;
                    }

                    return parsed;
                };

                const pad2 = (value) => String(value).padStart(2, '0');
                const periodKey = (year, month) => `${year}-${pad2(month)}`;
                const formatIsoDate = (year, month, day) => `${year}-${pad2(month)}-${pad2(day)}`;
                const buildConceptLabel = (periodMonth, periodYear) => {
                    const monthLabel = monthNames[periodMonth - 1] || String(periodMonth);
                    return `Renta ${monthLabel} ${periodYear}`;
                };

                const resolveDueDateForPeriod = (candidate, year, month, fallbackDay) => {
                    const parsedCandidate = parseIsoDate(candidate);
                    if (parsedCandidate && parsedCandidate.year === year && parsedCandidate.month === month) {
                        return formatIsoDate(year, month, parsedCandidate.day);
                    }

                    const daysInMonth = new Date(year, month, 0).getDate();
                    return formatIsoDate(year, month, Math.min(Math.max(1, fallbackDay), daysInMonth));
                };

                const normalizeExistingPlanRows = (rows) => {
                    if (!Array.isArray(rows)) {
                        return [];
                    }

                    return rows
                        .map((row) => {
                            const month = Number.parseInt(row?.period_month, 10);
                            const year = Number.parseInt(row?.period_year, 10);
                            if (!month || !year || month < 1 || month > 12) {
                                return null;
                            }

                            return {
                                period_month: month,
                                period_year: year,
                                due_date: String(row?.due_date || ''),
                                amount: toMoney(row?.amount, 0),
                                concept: String(row?.concept || '').trim(),
                                notes: row?.notes ? String(row.notes) : null,
                                is_custom_amount: Boolean(row?.is_custom_amount),
                            };
                        })
                        .filter(Boolean);
                };

                let propertySetupPlanRows = normalizeExistingPlanRows(initialPropertySetupPlan);

                const syncPropertySetupChargeDayFromContract = () => {
                    if (!propertySetupChargeDay) {
                        return;
                    }

                    const starts = parseIsoDate(propertySetupContractStartsAt?.value);
                    if (!starts) {
                        return;
                    }

                    const currentDay = toDay(propertySetupChargeDay.value);
                    if (currentDay !== null) {
                        return;
                    }

                    propertySetupChargeDay.value = String(starts.day);
                };

                const buildAutoPropertySetupPlan = () => {
                    const starts = parseIsoDate(propertySetupContractStartsAt?.value);
                    const expires = parseIsoDate(propertySetupContractExpiresAt?.value);
                    const currentMonthlyRentPrice = toMoney(propertySetupMonthlyRentPrice?.value, 0);
                    const setupChargeDay = toDay(propertySetupChargeDay?.value) ?? starts?.day ?? null;
                    if (!starts || !expires || currentMonthlyRentPrice <= 0 || !setupChargeDay) {
                        return [];
                    }

                    const startsDate = new Date(starts.year, starts.month - 1, 1);
                    const expiresDate = new Date(expires.year, expires.month - 1, 1);
                    if (startsDate > expiresDate) {
                        return [];
                    }

                    const baseContractDay = setupChargeDay;
                    const existingByPeriod = new Map(
                        propertySetupPlanRows.map((row) => [periodKey(row.period_year, row.period_month), row]),
                    );
                    const builtRows = [];
                    const cursor = new Date(startsDate.getFullYear(), startsDate.getMonth(), 1);

                    while (cursor <= expiresDate) {
                        const year = cursor.getFullYear();
                        const month = cursor.getMonth() + 1;
                        const key = periodKey(year, month);
                        const current = existingByPeriod.get(key);
                        const customAmount = Boolean(current?.is_custom_amount);
                        const amount = customAmount
                            ? toMoney(current?.amount, currentMonthlyRentPrice)
                            : currentMonthlyRentPrice;
                        const dueDate = resolveDueDateForPeriod(current?.due_date, year, month, baseContractDay);
                        const concept = (current?.concept || '').trim() || buildConceptLabel(month, year);

                        if (amount > 0) {
                            builtRows.push({
                                period_month: month,
                                period_year: year,
                                due_date: dueDate,
                                amount,
                                concept,
                                notes: current?.notes || null,
                                is_custom_amount: customAmount,
                            });
                        }

                        cursor.setMonth(cursor.getMonth() + 1);
                    }

                    return builtRows;
                };

                const syncPropertySetupPlanInputs = () => {
                    if (!propertySetupPlanInputs) {
                        return;
                    }

                    propertySetupPlanInputs.innerHTML = '';
                    propertySetupPlanRows.forEach((row, index) => {
                        const appendInput = (name, value) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `rent_charge_plan[${index}][${name}]`;
                            input.value = value;
                            propertySetupPlanInputs.appendChild(input);
                        };

                        appendInput('period_month', row.period_month);
                        appendInput('period_year', row.period_year);
                        appendInput('due_date', row.due_date);
                        appendInput('amount', toMoney(row.amount, 0).toFixed(2));
                        appendInput('concept', row.concept || '');
                        appendInput('is_custom_amount', row.is_custom_amount ? '1' : '0');
                        if (row.notes) {
                            appendInput('notes', row.notes);
                        }
                    });
                };

                const renderPropertySetupPlan = () => {
                    if (!propertySetupPlanTableBody) {
                        return;
                    }

                    propertySetupPlanTableBody.innerHTML = '';
                    if (!propertySetupPlanRows.length) {
                        if (propertySetupPlanEmptyState) {
                            propertySetupPlanTableBody.appendChild(propertySetupPlanEmptyState);
                        } else {
                            propertySetupPlanTableBody.innerHTML = `
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-8">No hay pagos configurados.</td>
                                </tr>
                            `;
                        }
                    } else {
                        propertySetupPlanRows.forEach((row, index) => {
                            const tr = document.createElement('tr');

                            const periodCell = document.createElement('td');
                            periodCell.textContent = `${pad2(row.period_month)}/${row.period_year}`;
                            tr.appendChild(periodCell);

                            const dueDateCell = document.createElement('td');
                            const dueDateInput = document.createElement('input');
                            dueDateInput.type = 'date';
                            dueDateInput.className = 'form-control form-control-sm';
                            dueDateInput.value = row.due_date || '';
                            dueDateInput.dataset.planField = 'due_date';
                            dueDateInput.dataset.planIndex = String(index);
                            dueDateCell.appendChild(dueDateInput);
                            tr.appendChild(dueDateCell);

                            const amountCell = document.createElement('td');
                            const amountInput = document.createElement('input');
                            amountInput.type = 'number';
                            amountInput.min = '0.01';
                            amountInput.step = '0.01';
                            amountInput.className = 'form-control form-control-sm';
                            amountInput.value = toMoney(row.amount, 0).toFixed(2);
                            amountInput.dataset.planField = 'amount';
                            amountInput.dataset.planIndex = String(index);
                            amountCell.appendChild(amountInput);
                            tr.appendChild(amountCell);

                            const conceptCell = document.createElement('td');
                            const conceptInput = document.createElement('input');
                            conceptInput.type = 'text';
                            conceptInput.className = 'form-control form-control-sm';
                            conceptInput.maxLength = 190;
                            conceptInput.value = row.concept || '';
                            conceptInput.dataset.planField = 'concept';
                            conceptInput.dataset.planIndex = String(index);
                            conceptCell.appendChild(conceptInput);
                            tr.appendChild(conceptCell);

                            propertySetupPlanTableBody.appendChild(tr);
                        });
                    }

                    const total = propertySetupPlanRows.reduce((sum, row) => sum + toMoney(row.amount, 0), 0);
                    if (propertySetupPlanSummary) {
                        if (propertySetupPlanRows.length) {
                            propertySetupPlanSummary.textContent = `Total proyectado: $${total.toFixed(2)} en ${propertySetupPlanRows.length} cargos.`;
                        } else {
                            propertySetupPlanSummary.textContent = 'Configura contrato y renta mensual para generar la lista automatica.';
                        }
                    }
                    if (propertySetupPlanRowsCount) {
                        propertySetupPlanRowsCount.textContent = String(propertySetupPlanRows.length);
                    }
                };

                const rebuildPropertySetupPlan = () => {
                    propertySetupPlanRows = buildAutoPropertySetupPlan();
                    renderPropertySetupPlan();
                    syncPropertySetupPlanInputs();
                };

                propertySetupPlanTableBody?.addEventListener('change', (event) => {
                    const target = event.target.closest('[data-plan-field]');
                    if (!target) {
                        return;
                    }

                    const index = Number.parseInt(target.dataset.planIndex || '-1', 10);
                    if (!Number.isInteger(index) || !propertySetupPlanRows[index]) {
                        return;
                    }

                    const row = propertySetupPlanRows[index];
                    const field = target.dataset.planField;
                    if (field === 'amount') {
                        row.amount = toMoney(target.value, row.amount);
                        row.is_custom_amount = true;
                    } else if (field === 'due_date') {
                        row.due_date = String(target.value || '').trim();
                    } else if (field === 'concept') {
                        row.concept = String(target.value || '').trim();
                    }

                    syncPropertySetupPlanInputs();
                    renderPropertySetupPlan();
                });

                propertySetupContractStartsAt?.addEventListener('change', () => {
                    syncPropertySetupChargeDayFromContract();
                    rebuildPropertySetupPlan();
                });
                propertySetupContractExpiresAt?.addEventListener('change', rebuildPropertySetupPlan);
                propertySetupMonthlyRentPrice?.addEventListener('input', rebuildPropertySetupPlan);
                propertySetupMonthlyRentPrice?.addEventListener('change', rebuildPropertySetupPlan);
                propertySetupChargeDay?.addEventListener('input', rebuildPropertySetupPlan);
                propertySetupChargeDay?.addEventListener('change', rebuildPropertySetupPlan);
                syncPropertySetupChargeDayFromContract();
                rebuildPropertySetupPlan();

                propertySetupForm.addEventListener('submit', async (event) => {
                    syncPropertySetupPlanInputs();
                    if (propertySetupForceAssignment?.value === '1') {
                        return;
                    }

                    const selectedOption = propertySetupTenant?.options[propertySetupTenant.selectedIndex];
                    if (!selectedOption || !selectedOption.value) {
                        return;
                    }

                    let missing = [];
                    try {
                        missing = JSON.parse(selectedOption.dataset.missing || '[]');
                    } catch (error) {
                        missing = [];
                    }

                    if (!Array.isArray(missing) || !missing.length) {
                        return;
                    }

                    event.preventDefault();

                    const tenantName = selectedOption.textContent.trim();
                    const details = missing.join('\n- ');
                    const message = `El inquilino ${tenantName} tiene datos o documentos incompletos:\n- ${details}\n\n¿Deseas continuar con la asignacion?`;
                    let confirmed = false;

                    if (window.Swal?.fire) {
                        const result = await window.Swal.fire({
                            title: 'Inquilino incompleto',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Si, continuar',
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true,
                        });
                        confirmed = !!result.isConfirmed;
                    } else {
                        confirmed = window.confirm(message);
                    }

                    if (!confirmed) {
                        return;
                    }

                    if (propertySetupForceAssignment) {
                        propertySetupForceAssignment.value = '1';
                    }
                    propertySetupForm.submit();
                });
            }

            const syncBulkRowsInputs = () => {
                if (!bulkChargeRowsContainer) {
                    return;
                }

                bulkChargeRowsContainer.innerHTML = '';
                bulkRows.forEach((row, index) => {
                    const appendInput = (name, value) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `rows[${index}][${name}]`;
                        input.value = value;
                        bulkChargeRowsContainer.appendChild(input);
                    };

                    appendInput('period_month', row.period_month);
                    appendInput('period_year', row.period_year);
                    appendInput('due_date', row.due_date);
                    appendInput('amount', toMoney(row.amount, 0).toFixed(2));
                    appendInput('concept', row.concept || '');
                    appendInput('notes', row.notes || '');
                });
            };

            const renderBulkRows = () => {
                if (!bulkPreviewBody) {
                    return;
                }

                bulkPreviewBody.innerHTML = '';
                if (!bulkRows.length) {
                    bulkPreviewBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-muted py-8">No se encontraron cargos para esta propiedad.</td>
                        </tr>
                    `;
                    bulkSummaryText.textContent = 'Total: 0 | Nuevos: 0 | Existentes: 0';
                    bulkPreviewContainer?.classList.remove('d-none');
                    syncBulkRowsInputs();
                    return;
                }

                const summary = {
                    total: bulkRows.length,
                    to_create: bulkRows.filter((row) => !row.already_exists).length,
                    already_exists: bulkRows.filter((row) => row.already_exists).length,
                };

                bulkRows.forEach((row, index) => {
                    const alreadyClass = row.already_exists ? 'badge-light-warning text-warning' : 'badge-light-success text-success';
                    const alreadyLabel = row.already_exists ? 'Ya existe' : 'Se creara';
                    const disabled = row.already_exists ? 'disabled' : '';
                    bulkPreviewBody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>${row.property_name}</td>
                            <td>${row.tenant_name || '-'}</td>
                            <td>${String(row.period_month).padStart(2, '0')}/${row.period_year}</td>
                            <td>${row.due_date}</td>
                            <td>
                                <input type="number" min="0.01" step="0.01" class="form-control form-control-sm"
                                    data-bulk-row-index="${index}" data-bulk-field="amount"
                                    value="${toMoney(row.amount, 0).toFixed(2)}" ${disabled}>
                            </td>
                            <td><span class="badge ${alreadyClass}">${alreadyLabel}</span></td>
                        </tr>
                    `);
                });

                bulkSummaryText.textContent = `Total: ${summary.total} | Nuevos: ${summary.to_create} | Existentes: ${summary.already_exists}`;
                bulkPreviewContainer?.classList.remove('d-none');
                syncBulkRowsInputs();
            };

            const resetBulkPreview = () => {
                bulkRows = [];
                if (bulkPreviewBody) {
                    bulkPreviewBody.innerHTML = '';
                }
                bulkPreviewContainer?.classList.add('d-none');
                syncBulkRowsInputs();
            };

            const parseDateDay = (value) => {
                const stringValue = String(value || '').trim();
                const parts = stringValue.split('-');
                if (parts.length !== 3) {
                    return null;
                }

                const day = Number.parseInt(parts[2], 10);
                if (!Number.isInteger(day) || day < 1 || day > 31) {
                    return null;
                }

                return day;
            };

            const normalizeChargeDay = (value) => {
                const day = Number.parseInt(String(value || ''), 10);
                if (!Number.isInteger(day) || day < 1 || day > 31) {
                    return null;
                }

                return day;
            };

            const syncBulkChargeDayFromContract = () => {
                if (!bulkChargeDay) {
                    return;
                }

                const currentDay = normalizeChargeDay(bulkChargeDay.value);
                if (currentDay !== null) {
                    return;
                }

                const startsDay = parseDateDay(bulkContractStartsAt?.value);
                if (startsDay !== null) {
                    bulkChargeDay.value = String(startsDay);
                }
            };

            const applyBulkPropertyDefaults = () => {
                if (!bulkPropertyId) {
                    return;
                }

                const selected = bulkPropertyId.options[bulkPropertyId.selectedIndex];
                if (!selected) {
                    return;
                }

                if (bulkTenantName) {
                    bulkTenantName.value = selected.dataset?.tenantName || '';
                }
                if (bulkContractStartsAt) {
                    bulkContractStartsAt.value = selected.dataset?.contractStart || '';
                }
                if (bulkContractExpiresAt) {
                    bulkContractExpiresAt.value = selected.dataset?.contractExpires || '';
                }
                if (bulkMonthlyRentPrice) {
                    bulkMonthlyRentPrice.value = selected.dataset?.monthlyRent || '';
                }
                if (bulkChargeDay) {
                    const dayFromProperty = normalizeChargeDay(selected.dataset?.chargeDay);
                    bulkChargeDay.value = dayFromProperty !== null ? String(dayFromProperty) : '';
                    if (dayFromProperty === null) {
                        syncBulkChargeDayFromContract();
                    }
                }
                if (bulkChargeToleranceDays) {
                    bulkChargeToleranceDays.value = String(selected.dataset?.chargeToleranceDays || '0');
                }
            };

            bulkPropertyId?.addEventListener('change', () => {
                applyBulkPropertyDefaults();
                resetBulkPreview();
            });

            if (bulkTenantName && bulkPropertyId) {
                const selected = bulkPropertyId.options[bulkPropertyId.selectedIndex];
                bulkTenantName.value = selected?.dataset?.tenantName || '';
            }
            syncBulkChargeDayFromContract();

            bulkPreviewBody?.addEventListener('change', (event) => {
                const input = event.target.closest('[data-bulk-field="amount"]');
                if (!input) {
                    return;
                }

                const index = Number.parseInt(input.getAttribute('data-bulk-row-index') || '-1', 10);
                if (!Number.isInteger(index) || !bulkRows[index]) {
                    return;
                }

                bulkRows[index].amount = toMoney(input.value, bulkRows[index].amount);
                renderBulkRows();
            });

            bulkContractStartsAt?.addEventListener('change', () => {
                syncBulkChargeDayFromContract();
                resetBulkPreview();
            });
            bulkContractExpiresAt?.addEventListener('change', resetBulkPreview);
            bulkMonthlyRentPrice?.addEventListener('input', resetBulkPreview);
            bulkMonthlyRentPrice?.addEventListener('change', resetBulkPreview);
            bulkChargeDay?.addEventListener('input', resetBulkPreview);
            bulkChargeDay?.addEventListener('change', resetBulkPreview);
            bulkChargeToleranceDays?.addEventListener('input', resetBulkPreview);
            bulkChargeToleranceDays?.addEventListener('change', resetBulkPreview);

            previewBtn?.addEventListener('click', async () => {
                const propertyId = bulkPropertyId?.value;

                if (!propertyId) {
                    alert('Selecciona una propiedad.');
                    return;
                }

                previewBtn.disabled = true;
                previewBtn.textContent = 'Cargando...';

                try {
                    syncBulkChargeDayFromContract();
                    const response = await fetch("{{ route('charges.bulk.preview') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            property_id: propertyId,
                            contract_starts_at: bulkContractStartsAt?.value || null,
                            contract_expires_at: bulkContractExpiresAt?.value || null,
                            monthly_rent_price: bulkMonthlyRentPrice?.value || null,
                            charge_day: bulkChargeDay?.value || null,
                            charge_tolerance_days: bulkChargeToleranceDays?.value || null,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data?.message || 'No fue posible generar la vista previa.');
                    }

                    const preview = data.preview || {};
                    bulkRows = Array.isArray(preview.rows) ? preview.rows : [];
                    renderBulkRows();
                } catch (error) {
                    alert(error.message || 'No fue posible generar la vista previa.');
                } finally {
                    previewBtn.disabled = false;
                    previewBtn.textContent = 'Ver lista de pagos';
                }
            });

            bulkChargeForm?.addEventListener('submit', () => {
                syncBulkChargeDayFromContract();
                syncBulkRowsInputs();
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

    @if ($errors->updateCharge->any())
        <script>
            (() => {
                const modalEl = document.getElementById('editChargeModal');
                if (!modalEl) return;

                const form = document.getElementById('editChargeForm');
                const chargeUuid = @json(old('charge_uuid'));
                if (form && chargeUuid) {
                    form.setAttribute('action', "{{ url('/cobranza') }}/" + chargeUuid);
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
