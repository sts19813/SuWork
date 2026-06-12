@extends('layouts.app')

@section('title', 'Control de Propiedades | SuWork')

@section('content')
    <div class="py-10 property-control-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Control de Alta de Propiedades</h1>
                <div class="text-muted fs-6">Seguimiento de configuración operativa y documental</div>
            </div>
            <div class="text-muted fw-semibold">{{ now()->translatedFormat('d M Y') }}</div>
        </div>

        <div class="row g-5 mb-8">
            <div class="col-xl-4">
                <div class="card h-100 property-control-hero">
                    <div class="card-body p-8">
                        <div class="text-white-75 fw-semibold mb-3">Avance general del sistema</div>
                        <div class="d-flex align-items-end justify-content-between gap-6">
                            <div>
                                <div class="display-5 fw-bold text-white">{{ $summary['overall_progress'] }}%</div>
                                <div class="text-white-75">propiedades configuradas correctamente</div>
                            </div>
                            <div class="property-control-hero__ring">
                                <span>{{ $summary['complete'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-4 col-xl-2">
                <div class="card h-100">
                    <div class="card-body p-7">
                        <div class="text-muted fw-semibold mb-2">Total propiedades</div>
                        <div class="fs-1 fw-bold text-dark">{{ $summary['total'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-4 col-xl-3">
                <div class="card h-100 border border-success border-opacity-25">
                    <div class="card-body p-7">
                        <div class="text-muted fw-semibold mb-2">Completas</div>
                        <div class="fs-1 fw-bold text-success">{{ $summary['complete'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-4 col-xl-3">
                <div class="card h-100 border border-danger border-opacity-25">
                    <div class="card-body p-7">
                        <div class="text-muted fw-semibold mb-2">Incompletas</div>
                        <div class="fs-1 fw-bold text-danger">{{ $summary['incomplete'] }}</div>
                        <div class="text-warning fw-semibold mt-2">{{ $summary['without_advisor'] }} sin asesor</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('properties.control') }}" class="row g-4 align-items-end">
                    <div class="col-lg-5">
                        <label class="form-label fw-semibold">Buscar propiedad o asesor</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-solid"
                            placeholder="Buscar propiedad o asesor...">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Filtro</label>
                        <select name="status" class="form-select">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ $filters['status'] === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 d-flex justify-content-lg-end gap-3">
                        <a href="{{ route('properties.control') }}" class="btn btn-light">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Aplicar</button>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-3 mt-6">
                    @foreach ($statusOptions as $value => $label)
                        <a href="{{ route('properties.control', array_filter(['q' => $filters['q'], 'status' => $value])) }}"
                            class="btn btn-sm {{ $filters['status'] === $value ? 'btn-primary' : 'btn-light' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                    <span class="text-muted fw-semibold align-self-center">{{ $resultCount }} resultados</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gs-0 gy-0 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-8">
                                <th class="ps-7 min-w-220px">Propiedad</th>
                                <th class="min-w-180px">Asesor responsable</th>
                                <th class="min-w-220px">Avance</th>
                                <th class="min-w-160px">Estado</th>
                                <th class="min-w-100px text-end pe-7">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($snapshots as $row)
                                @php
                                    $property = $row['property'];
                                    $collapseId = 'property-control-' . $property->id;
                                @endphp
                                <tr>
                                    <td class="ps-7">
                                        <div class="fw-bold text-dark">{{ $property->internal_name }}</div>
                                        <div class="text-muted fs-7">{{ $property->full_address }}</div>
                                    </td>
                                    <td>
                                        @if ($row['advisor_name'])
                                            <div class="fw-semibold text-dark">{{ $row['advisor_name'] }}</div>
                                            <div class="text-muted fs-7">{{ $property->advisor?->email }}</div>
                                        @else
                                            <span class="text-danger fw-semibold">Sin asesor</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="progress h-8px flex-grow-1 bg-light">
                                                <div class="progress-bar bg-{{ $row['status_tone'] === 'danger' ? 'danger' : ($row['status_tone'] === 'warning' ? 'warning' : 'success') }}"
                                                    style="width: {{ $row['progress_percent'] }}%;"></div>
                                            </div>
                                            <span class="fw-bold text-gray-700">{{ $row['progress_percent'] }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-{{ $row['status_tone'] }} text-{{ $row['status_tone'] }}">
                                            {{ $row['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-7">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-light-primary">Editar</a>
                                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false" aria-controls="{{ $collapseId }}">
                                                Ver
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="property-control-detail-row">
                                    <td colspan="5" class="bg-light p-0">
                                        <div class="collapse" id="{{ $collapseId }}">
                                            <div class="p-7">
                                            <div class="row g-5 mb-6">
                                                @foreach ($checkLabels as $key => $label)
                                                    <div class="col-md-6 col-xl-4">
                                                        <div class="property-control-check {{ ($row['checks'][$key] ?? false) ? 'is-complete' : 'is-missing' }}">
                                                            <i class="bi {{ ($row['checks'][$key] ?? false) ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                                            <span>{{ $label }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="border-top pt-5">
                                                <div class="text-muted fw-semibold mb-3">Pendientes:</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @forelse ($row['missing_labels'] as $missing)
                                                        <span class="badge badge-light-danger text-danger fs-8">{{ $missing }}</span>
                                                    @empty
                                                        <span class="badge badge-light-success text-success fs-8">Sin pendientes</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-15">No hay propiedades que coincidan con el filtro seleccionado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
