@extends('layouts.app')

@section('title', $property->internal_name . ' | SuWork')

@section('content')

<style>
    .ck-content table {
    width: 100%;
    border-collapse: collapse;
}

.ck-content table td,
.ck-content table th {
    border: 1px solid #ddd;
    padding: 8px;
}

.ck-content table tr:nth-child(even) {
    background-color: #f9f9f9;
}
</style>
    @php
        $photoUrl = $property->facade_photo_path
            ? \Illuminate\Support\Facades\Storage::url($property->facade_photo_path)
            : asset('metronic/assets/media/svg/files/blank-image.svg');
    @endphp

    <div class="py-10 property-module">
        <div class="mb-8">
            <a href="{{ route('properties.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver al listado
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information-5 fs-2hx text-warning me-4"></i>
                <div class="fw-semibold">{{ session('warning') }}</div>
            </div>
        @endif

        <div class="card mb-8">
            <div class="card-body d-flex flex-wrap align-items-center gap-6 p-8">
                <img src="{{ $photoUrl }}" class="property-cover" alt="{{ $property->internal_name }}">
                <div class="flex-grow-1">
                    <h1 class="mb-3 fw-bold">{{ $property->internal_name }}</h1>
                    <div class="d-flex flex-wrap gap-5 mb-4">
                        <div><span class="text-muted me-2">Tipo:</span> {{ $property->type?->name ?? '-' }}</div>
                        <div><span class="text-muted me-2">Zona:</span> {{ $property->zone?->name ?? '-' }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge {{ $property->status_badge_class }}">{{ $property->status_label }}</span>
                        <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-light-primary">
                            Editar propiedad
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Asignar inquilino
                            </button>
                            <ul class="dropdown-menu">
                                @foreach($tenants as $tenant)
                                    @php
                                        $assignmentCheck = $tenantAssignmentChecks[(string) $tenant->id] ?? ['missing' => [], 'is_complete' => true];
                                    @endphp
                                    <li>
                                        <form method="POST"
                                            action="{{ route('properties.update.tenant', $property) }}"
                                            class="d-inline js-assign-tenant-form"
                                            data-tenant-name="{{ $tenant->full_name }}"
                                            data-missing='@json($assignmentCheck['missing'])'>
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                                            <input type="hidden" name="force_assignment" value="0">
                                            <button type="submit" class="dropdown-item">
                                                {{ $tenant->full_name }}
                                                @unless($assignmentCheck['is_complete'])
                                                    <span class="text-warning">(incompleto)</span>
                                                @endunless
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('properties.update.tenant', $property) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="tenant_id" value="">
                                        <input type="hidden" name="force_assignment" value="0">
                                        <button type="submit" class="dropdown-item text-danger">
                                            Remover inquilino
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-8">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Información general</h3>
            </div>
            <div class="card-body pt-0">
                <div class="row g-6">
                    <div class="col-lg-6">
                        <div class="text-muted mb-1">Dirección</div>
                        <div class="fw-semibold">{{ $property->full_address }}</div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-muted mb-1">Referencia interna</div>
                        <div class="fw-semibold">{{ $property->internal_reference ?: '-' }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted mb-1">Complejo o privada</div>
                        <div class="fw-semibold">{{ $property->complex_name ?: '-' }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted mb-1">Número Interior</div>
                        <div class="fw-semibold">{{ $property->official_number ?: '-' }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted mb-1">Número Exterior</div>
                        <div class="fw-semibold">{{ $property->unit_number ?: '-' }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted mb-1">Precio renta mensual</div>
                        <div class="fw-semibold">{{ $property->monthly_rent_price ? '$' . number_format($property->monthly_rent_price, 2) : '-' }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted mb-1">Cuota mantenimiento</div>
                        <div class="fw-semibold">{{ $property->maintenance_fee ? '$' . number_format($property->maintenance_fee, 2) : '-' }}</div>
                    </div>
                    @if ($property->map_url)
                        <div class="col-12">
                            <div class="text-muted mb-1">URL del mapa</div>
                            <div class="fw-semibold"><a href="{{ $property->map_url }}" target="_blank">{{ $property->map_url }}</a></div>
                        </div>
                    @endif
                    <div class="col-lg-6">
                        <div class="text-muted mb-1">Inquilino actual</div>
                        <div class="fw-semibold">{{ $property->tenant?->full_name ?: ($property->current_tenant_name ?: '-') }}</div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-muted mb-1">Contrato inicia</div>
                        <div class="fw-semibold">
                            {{ $property->contract_starts_at ? $property->contract_starts_at->format('d/m/Y') : '-' }}
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-muted mb-1">Contrato vence</div>
                        <div class="fw-semibold">
                            {{ $property->contract_expires_at ? $property->contract_expires_at->format('d/m/Y') : '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-8">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Propietarios</h3>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex flex-column gap-5">
                    @foreach ($property->owners as $owner)
                        <div class="border rounded p-5">
                            <div class="fw-bold fs-5 mb-3">{{ $owner->name }}</div>
                            <div class="row g-4">
                                <div class="col-lg-4"><span class="text-muted">Correo:</span> {{ $owner->email }}</div>
                                <div class="col-lg-4"><span class="text-muted">Teléfono:</span> {{ $owner->phone }}</div>
                                <div class="col-lg-4"><span class="text-muted">Tipo:</span> {{ $owner->owner_type_label }}</div>
                                <div class="col-lg-4"><span class="text-muted">Banco:</span> {{ $owner->bank_name ?: '-' }}</div>
                                <div class="col-lg-4"><span class="text-muted">CLABE:</span> {{ $owner->clabe ?: '-' }}</div>
                                <div class="col-lg-4"><span class="text-muted">Método pago:</span>
                                    {{ $owner->payment_method_label ?: '-' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-8">
            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold">Documentos de la propiedad</h3>
                <a href="{{ route('dossiers.properties.show', $property) }}" class="btn btn-sm btn-light-primary">
                    Abrir expediente
                </a>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex flex-column gap-4">
                    @foreach ($documents as $document)
                        <div class="border rounded px-5 py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <i class="ki-outline ki-document text-gray-500 fs-2"></i>
                                <span class="fw-semibold">{{ $document->label }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge {{ $document->status_badge_class }}">{{ $document->status_label }}</span>
                                <span class="badge badge-light-info text-info">v{{ $document->versions->count() }}</span>
                                @if ($document->file_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}"
                                        class="btn btn-sm btn-light-primary" target="_blank">
                                        Ver archivo
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($customDocuments->isNotEmpty())
                    <div class="separator my-6"></div>
                    <h4 class="fw-bold mb-4">Otros documentos</h4>
                    <div class="d-flex flex-column gap-4">
                        @foreach ($customDocuments as $document)
                            <div class="border rounded px-5 py-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="ki-outline ki-document text-gray-500 fs-2"></i>
                                    <span class="fw-semibold">{{ $document->label }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge {{ $document->status_badge_class }}">{{ $document->status_label }}</span>
                                    <span class="badge badge-light-info text-info">v{{ $document->versions->count() }}</span>
                                    @if ($document->file_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}"
                                            class="btn btn-sm btn-light-primary" target="_blank">
                                            Ver archivo
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

       @if ($property->details || $property->description || $property->rental_requirements || $property->amenities)
            <div class="card mb-8">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Datos adicionales</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-6">

                        @if ($property->details)
                            <div class="col-lg-6 col-12">
                                <div class="text-muted mb-1">Detalles</div>

                                <div class="ck-content">
                                    {!! $property->details !!}
                                </div>
                           
                            </div>
                        @endif

                        @if ($property->description)
                            <div class="col-lg-6 col-12">
                                <div class="text-muted mb-1">Descripción</div>

                                 <div class="ck-content">
                                    <div class="fw-semibold">{!! $property->description !!}</div>
                                </div>
                            </div>
                        @endif

                        @if ($property->rental_requirements)
                            <div class="col-lg-6 col-12">
                                <div class="text-muted mb-1">Requisitos de renta</div>
                                 <div class="ck-content">
                                    <div class="fw-semibold">{!! $property->rental_requirements !!}</div>
                                </div>
                            </div>
                        @endif

                        @if ($property->amenities)
                            <div class="col-lg-6 col-12">
                                <div class="text-muted mb-1">Amenidades</div>
                                <div class="ck-content">
                                    <div class="fw-semibold">{!! $property->amenities !!}</div>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        @endif

        <div class="card mb-8">
            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title fw-bold mb-1">Cobranza</h3>
                    <div class="text-muted fs-7">Pagos completos: {{ (int) $rentChargesPaid }}/{{ (int) $rentChargesTotal }}</div>
                </div>
                <a href="{{ route('charges.index', ['property' => $property->uuid]) }}" class="btn btn-sm btn-light-primary">
                    Abrir cobranza
                </a>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0">
                        <thead>
                            <tr class="text-muted text-uppercase fs-8">
                                <th>Concepto</th>
                                <th>Periodo</th>
                                <th>Vencimiento</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th class="text-end">Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($propertyCharges as $charge)
                                <tr>
                                    <td>{{ $charge->concept }}</td>
                                    <td>{{ str_pad((string) $charge->period_month, 2, '0', STR_PAD_LEFT) }}/{{ $charge->period_year }}</td>
                                    <td>{{ $charge->due_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>${{ number_format((float) $charge->amount, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $charge->status_badge_class }}">{{ $charge->display_status_label }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('charges.show', $charge) }}?property={{ urlencode($property->uuid) }}" class="btn btn-sm btn-light">Ver</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-8">No hay cargos registrados para esta propiedad.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Inventario</h3>
            </div>
            <div class="card-body pt-0">
                @if ($property->inventoryAreas->isEmpty())
                    <div class="alert alert-light-info mb-0">No hay inventario capturado todavía.</div>
                @else
                    <div class="d-flex flex-column gap-6">
                        @foreach ($property->inventoryAreas as $area)
                            <div class="border rounded p-5">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="mb-0">{{ $area->name }}</h4>
                                    <span class="text-muted">{{ $area->items->count() }} elementos</span>
                                </div>

                                @if ($area->notes)
                                    <p class="text-gray-700 mb-4">{{ $area->notes }}</p>
                                @endif

                                @if ($area->photos->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-4 mb-4">
                                        @foreach ($area->photos as $photo)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}"
                                                class="inventory-thumb" alt="{{ $area->name }}">
                                        @endforeach
                                    </div>
                                @endif

                                @if ($area->items->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered align-middle mb-0">
                                            <thead>
                                                <tr class="text-muted text-uppercase fs-8">
                                                    <th>Elemento</th>
                                                    <th>Estado</th>
                                                    <th>Notas</th>
                                                    <th>Fotos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($area->items as $item)
                                                    <tr>
                                                        <td>{{ $item->name }}</td>
                                                        <td>{{ $item->condition ?: '-' }}</td>
                                                        <td>{{ $item->notes ?: '-' }}</td>
                                                        <td>
                                                            @if ($item->photos->isNotEmpty())
                                                                <div class="d-flex gap-2">
                                                                    @foreach ($item->photos as $photo)
                                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->latestVersion->file_path) }}" class="property-thumb" alt="Foto {{ $item->name }}">
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            document.querySelectorAll('.js-assign-tenant-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    const forceInput = form.querySelector('input[name="force_assignment"]');
                    if (forceInput && forceInput.value === '1') {
                        return;
                    }

                    let missing = [];
                    try {
                        missing = JSON.parse(form.dataset.missing || '[]');
                    } catch (error) {
                        missing = [];
                    }

                    if (!Array.isArray(missing) || !missing.length) {
                        return;
                    }

                    event.preventDefault();

                    const tenantName = form.dataset.tenantName || 'este inquilino';
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

                    if (forceInput) {
                        forceInput.value = '1';
                    }
                    form.submit();
                });
            });
        })();
    </script>
@endpush
