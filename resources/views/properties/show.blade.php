@extends('layouts.app')

@section('title', $property->internal_name . ' | SuWork')

@section('content')
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
                        <button type="button" class="btn btn-sm btn-primary disabled" disabled>Asignar inquilino</button>
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
                        <div class="text-muted mb-1">Número oficial</div>
                        <div class="fw-semibold">{{ $property->official_number ?: '-' }}</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-muted mb-1">Unidad</div>
                        <div class="fw-semibold">{{ $property->unit_number ?: '-' }}</div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-muted mb-1">Inquilino actual</div>
                        <div class="fw-semibold">{{ $property->current_tenant_name ?: '-' }}</div>
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
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Documentos de la propiedad</h3>
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($area->items as $item)
                                                    <tr>
                                                        <td>{{ $item->name }}</td>
                                                        <td>{{ $item->condition ?: '-' }}</td>
                                                        <td>{{ $item->notes ?: '-' }}</td>
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
