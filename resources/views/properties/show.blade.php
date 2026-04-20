@extends('layouts.app')

@section('title', $property->internal_name . ' | SuWork')

@section('content')
    <style>
        .property-header-meta .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            background: #efefef;
            color: #000;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .property-tabs-wrap {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #edf0fb;
            overflow: hidden;
        }

       

        .property-block-card {
            border: 1px solid #edf1fb;
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(25, 41, 80, 0.05);
        }

        .property-value-label {
            color: #7b86ac;
            font-size: 0.86rem;
            margin-bottom: 0.2rem;
        }

        .property-value-content {
            color: #1f2a51;
            font-weight: 600;
            word-break: break-word;
        }

        .inventory-thumb,
        .property-thumb {
            width: 88px;
            height: 88px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e8ecf8;
        }

        .change-log-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.75rem;
        }

        .change-log-value {
            background: #f8faff;
            border: 1px solid #e9eeff;
            border-radius: 8px;
            padding: 0.65rem;
            font-family: Consolas, 'Courier New', monospace;
            font-size: 0.78rem;
            color: #27345f;
            white-space: pre-wrap;
            word-break: break-word;
            margin: 0;
            min-height: 44px;
        }

        .change-log-tag {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #5f6ca0;
            margin-bottom: 0.35rem;
        }

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

        $formatChangeValue = function ($value): string {
            if (is_array($value)) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
            }

            if ($value === null || $value === '') {
                return 'Sin valor';
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            return (string) $value;
        };
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

        <div class="property-page-shell p-6 p-lg-8">
            <div class="card property-block-card mb-8">
                <div class="card-body d-flex flex-wrap align-items-center gap-6 p-8">
                    <img src="{{ $photoUrl }}" class="property-cover" alt="{{ $property->internal_name }}">
                    <div class="flex-grow-1">
                        <h1 class="mb-3 fw-bold">{{ $property->internal_name }}</h1>
                        <div class="property-header-meta mb-4">
                            <span class="meta-pill"><i class="ki-outline ki-home-2 fs-6"></i> {{ $property->type?->name ?? '-' }}</span>
                            <span class="meta-pill"><i class="ki-outline ki-geolocation fs-6"></i> {{ $property->zone?->name ?? '-' }}</span>
                            <span class="meta-pill"><i class="ki-outline ki-profile-user fs-6"></i> {{ $property->tenant?->full_name ?: ($property->current_tenant_name ?: 'Sin inquilino') }}</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <span class="badge {{ $property->status_badge_class }} fs-7">{{ $property->status_label }}</span>
                            <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-light-primary">
                                Editar propiedad
                            </a>

                            @if ($property->tenant_id)
                                <a href="{{ route('charges.index', ['property' => $property->uuid]) }}" class="btn btn-sm btn-light-success">
                                    Cobranza
                                </a>
                            @else
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Asignar inquilino
                                    </button>
                                    <ul class="dropdown-menu">
                                        @foreach ($tenants as $tenant)
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
                                                        @unless ($assignmentCheck['is_complete'])
                                                            <span class="text-warning">(incompleto)</span>
                                                        @endunless
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="property-tabs-wrap">
                <ul class="nav property-tabs-nav" id="propertyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-dashboard-tab" data-bs-toggle="tab" data-bs-target="#tab-dashboard" type="button" role="tab" aria-controls="tab-dashboard" aria-selected="true">
                            Dashboard
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-general-tab" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab" aria-controls="tab-general" aria-selected="false">
                            Informacion general
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-owners-tab" data-bs-toggle="tab" data-bs-target="#tab-owners" type="button" role="tab" aria-controls="tab-owners" aria-selected="false">
                            Propietarios
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-documents-tab" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button" role="tab" aria-controls="tab-documents" aria-selected="false">
                            Documentos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-extra-tab" data-bs-toggle="tab" data-bs-target="#tab-extra" type="button" role="tab" aria-controls="tab-extra" aria-selected="false">
                            Informacion adicional
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-charges-tab" data-bs-toggle="tab" data-bs-target="#tab-charges" type="button" role="tab" aria-controls="tab-charges" aria-selected="false">
                            Cobranza
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-inventory-tab" data-bs-toggle="tab" data-bs-target="#tab-inventory" type="button" role="tab" aria-controls="tab-inventory" aria-selected="false">
                            Inventario
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-history-tab" data-bs-toggle="tab" data-bs-target="#tab-history" type="button" role="tab" aria-controls="tab-history" aria-selected="false">
                            Historico de cambios
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="propertyTabsContent">
                    <div class="tab-pane fade show active property-tab-pane" id="tab-dashboard" role="tabpanel" aria-labelledby="tab-dashboard-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold mb-0">Dashboard</h3>
                            </div>
                            <div class="card-body pt-0">
                                <div class="alert alert-light-secondary mb-0">En blanco por ahora.</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-general" role="tabpanel" aria-labelledby="tab-general-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                                <h3 class="card-title fw-bold">Informacion general</h3>
                                <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-light-primary">Editar propiedad</a>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-lg-6">
                                        <div class="property-value-label">Direccion</div>
                                        <div class="property-value-content">{{ $property->full_address }}</div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="property-value-label">Referencia interna</div>
                                        <div class="property-value-content">{{ $property->internal_reference ?: '-' }}</div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="property-value-label">Complejo o privada</div>
                                        <div class="property-value-content">{{ $property->complex_name ?: '-' }}</div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="property-value-label">Numero interior</div>
                                        <div class="property-value-content">{{ $property->official_number ?: '-' }}</div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="property-value-label">Numero exterior</div>
                                        <div class="property-value-content">{{ $property->unit_number ?: '-' }}</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="property-value-label">Precio renta mensual</div>
                                        <div class="property-value-content">{{ $property->monthly_rent_price ? '$' . number_format((float) $property->monthly_rent_price, 2) : '-' }}</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="property-value-label">Dia de cobro</div>
                                        <div class="property-value-content">{{ $property->charge_day ?: '-' }}</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="property-value-label">Tolerancia (dias)</div>
                                        <div class="property-value-content">{{ is_null($property->charge_tolerance_days) ? '-' : (int) $property->charge_tolerance_days }}</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="property-value-label">Cuota mantenimiento</div>
                                        <div class="property-value-content">{{ $property->maintenance_fee ? '$' . number_format((float) $property->maintenance_fee, 2) : '-' }}</div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="property-value-label">Inquilino actual</div>
                                        <div class="property-value-content">{{ $property->tenant?->full_name ?: ($property->current_tenant_name ?: '-') }}</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="property-value-label">Contrato inicia</div>
                                        <div class="property-value-content">{{ $property->contract_starts_at ? $property->contract_starts_at->format('d/m/Y') : '-' }}</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="property-value-label">Contrato vence</div>
                                        <div class="property-value-content">{{ $property->contract_expires_at ? $property->contract_expires_at->format('d/m/Y') : '-' }}</div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="property-value-label">Estatus</div>
                                        <div class="property-value-content">{{ $property->status_label }}</div>
                                    </div>
                                    @if ($property->map_url)
                                        <div class="col-12">
                                            <div class="property-value-label">URL del mapa</div>
                                            <div class="property-value-content"><a href="{{ $property->map_url }}" target="_blank">{{ $property->map_url }}</a></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-owners" role="tabpanel" aria-labelledby="tab-owners-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                                <h3 class="card-title fw-bold">Propietarios</h3>
                                <a href="{{ route('owners.index') }}" class="btn btn-sm btn-light-primary">Ir a propietarios</a>
                            </div>
                            <div class="card-body pt-0">
                                @if ($property->owners->isEmpty())
                                    <div class="alert alert-light-info mb-0">No hay propietarios asignados.</div>
                                @else
                                    <div class="d-flex flex-column gap-5">
                                        @foreach ($property->owners as $owner)
                                            <div class="border rounded p-5">
                                                <div class="fw-bold fs-5 mb-3">{{ $owner->name }}</div>
                                                <div class="row g-4">
                                                    <div class="col-lg-4"><span class="text-muted">Correo:</span> {{ $owner->email ?: '-' }}</div>
                                                    <div class="col-lg-4"><span class="text-muted">Telefono:</span> {{ $owner->phone ?: '-' }}</div>
                                                    <div class="col-lg-4"><span class="text-muted">Tipo:</span> {{ $owner->owner_type_label }}</div>
                                                    <div class="col-lg-4"><span class="text-muted">Banco:</span> {{ $owner->bank_name ?: '-' }}</div>
                                                    <div class="col-lg-4"><span class="text-muted">CLABE:</span> {{ $owner->clabe ?: '-' }}</div>
                                                    <div class="col-lg-4"><span class="text-muted">Metodo pago:</span> {{ $owner->payment_method_label ?: '-' }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-documents" role="tabpanel" aria-labelledby="tab-documents-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                                <h3 class="card-title fw-bold">Documentos de la propiedad</h3>
                                <a href="{{ route('dossiers.properties.show', $property) }}" class="btn btn-sm btn-light-primary">Abrir expediente</a>
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
                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" class="btn btn-sm btn-light-primary" target="_blank">
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
                                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" class="btn btn-sm btn-light-primary" target="_blank">
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
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-extra" role="tabpanel" aria-labelledby="tab-extra-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold">Datos adicionales</h3>
                            </div>
                            <div class="card-body pt-0">
                                @if ($property->details || $property->description || $property->rental_requirements || $property->amenities)
                                    <div class="row g-6">
                                        @if ($property->details)
                                            <div class="col-lg-6 col-12">
                                                <div class="property-value-label">Detalles</div>
                                                <div class="ck-content">{!! $property->details !!}</div>
                                            </div>
                                        @endif

                                        @if ($property->description)
                                            <div class="col-lg-6 col-12">
                                                <div class="property-value-label">Descripcion</div>
                                                <div class="ck-content">{!! $property->description !!}</div>
                                            </div>
                                        @endif

                                        @if ($property->rental_requirements)
                                            <div class="col-lg-6 col-12">
                                                <div class="property-value-label">Requisitos de renta</div>
                                                <div class="ck-content">{!! $property->rental_requirements !!}</div>
                                            </div>
                                        @endif

                                        @if ($property->amenities)
                                            <div class="col-lg-6 col-12">
                                                <div class="property-value-label">Amenidades</div>
                                                <div class="ck-content">{!! $property->amenities !!}</div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="alert alert-light-info mb-0">No hay informacion adicional capturada.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-charges" role="tabpanel" aria-labelledby="tab-charges-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="card-title fw-bold mb-1">Cobranza</h3>
                                    <div class="text-muted fs-7">Pagos completos: {{ (int) $rentChargesPaid }}/{{ (int) $rentChargesTotal }}</div>
                                </div>
                                <a href="{{ route('charges.index', ['property' => $property->uuid]) }}" class="btn btn-sm btn-light-primary">Abrir cobranza</a>
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
                    </div>

                    <div class="tab-pane fade property-tab-pane" id="tab-inventory" role="tabpanel" aria-labelledby="tab-inventory-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                                <h3 class="card-title fw-bold">Inventario</h3>
                                <a href="{{ route('inventory-checks.index', $property) }}" class="btn btn-sm btn-light-primary">Abrir inventario</a>
                            </div>
                            <div class="card-body pt-0">
                                @if ($property->inventoryAreas->isEmpty())
                                    <div class="alert alert-light-info mb-0">No hay inventario capturado todavia.</div>
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
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}" class="inventory-thumb" alt="{{ $area->name }}">
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
                                                                                        @if ($photo->latestVersion)
                                                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->latestVersion->file_path) }}" class="property-thumb" alt="Foto {{ $item->name }}">
                                                                                        @endif
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

                    <div class="tab-pane fade property-tab-pane" id="tab-history" role="tabpanel" aria-labelledby="tab-history-tab">
                        <div class="card property-block-card">
                            <div class="card-header border-0 pt-6">
                                <h3 class="card-title fw-bold">Historico de cambios</h3>
                            </div>
                            <div class="card-body pt-0">
                                @if ($propertyChangeLogs->isEmpty())
                                    <div class="alert alert-light-info mb-0">No hay cambios registrados para esta propiedad.</div>
                                @else
                                    <div class="d-flex flex-column gap-6">
                                        @foreach ($propertyChangeLogs as $changeLog)
                                            @php
                                                $changeSet = collect($changeLog->change_set ?? [])->filter(fn($item) => is_array($item));
                                            @endphp

                                            @if ($changeSet->isNotEmpty())
                                                <div class="border rounded p-5">
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                                        <div class="fw-bold text-gray-900">
                                                            {{ $changeLog->user?->name ?: 'Sistema' }}
                                                        </div>
                                                        <span class="badge badge-light-primary text-primary">
                                                            {{ $changeLog->changed_at?->format('d/m/Y H:i') ?: '-' }}
                                                        </span>
                                                    </div>

                                                    <div class="d-flex flex-column gap-4">
                                                        @foreach ($changeSet as $field => $values)
                                                            @php
                                                                $label = $propertyChangeFieldLabels[$field] ?? \Illuminate\Support\Str::of($field)->replace('_', ' ')->title();
                                                            @endphp
                                                            <div class="border rounded p-4 bg-light">
                                                                <div class="fw-bold mb-3">{{ $label }}</div>
                                                                <div class="change-log-grid">
                                                                    <div>
                                                                        <div class="change-log-tag">Valor anterior</div>
                                                                        <pre class="change-log-value">{{ $formatChangeValue($values['old'] ?? null) }}</pre>
                                                                    </div>
                                                                    <div>
                                                                        <div class="change-log-tag">Valor nuevo</div>
                                                                        <pre class="change-log-value">{{ $formatChangeValue($values['new'] ?? null) }}</pre>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
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
                    const message = `El inquilino ${tenantName} tiene datos o documentos incompletos:\n- ${details}\n\nDeseas continuar con la asignacion?`;
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

            const tabButtons = document.querySelectorAll('#propertyTabs [data-bs-toggle="tab"]');
            tabButtons.forEach((button) => {
                button.addEventListener('shown.bs.tab', (event) => {
                    const target = event.target.getAttribute('data-bs-target') || '';
                    if (!target.startsWith('#')) {
                        return;
                    }

                    history.replaceState(null, '', target);
                });
            });

            if (window.location.hash) {
                const hashButton = document.querySelector(`#propertyTabs [data-bs-target="${window.location.hash}"]`);
                if (hashButton) {
                    new bootstrap.Tab(hashButton).show();
                }
            }
        })();
    </script>
@endpush
