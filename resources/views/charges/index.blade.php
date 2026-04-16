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

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Cobranza</h1>
                <div class="text-muted fs-6">Cargos, pagos y conciliacion</div>
            </div>
            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createChargeModal">
                <i class="ki-outline ki-plus fs-4 me-1"></i> Nuevo cargo
            </button>
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
                                <th class="min-w-200px text-end">Opciones</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($charges as $charge)
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
                        @if ($errors->any())
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
@endsection

@push('scripts')
    <script>
        (() => {
            const propertySelect = document.getElementById('chargeProperty');
            const tenantSelect = document.getElementById('chargeTenant');

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
        })();
    </script>

    @if ($errors->any())
        <script>
            (() => {
                const modalEl = document.getElementById('createChargeModal');
                if (!modalEl) {
                    return;
                }

                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            })();
        </script>
    @endif
@endpush
