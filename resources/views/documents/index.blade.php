@extends('layouts.app')

@section('title', 'Documentos | SuWork')

@section('content')
    <div class="py-10 property-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Documentos</h1>
                <div class="text-muted fs-6">{{ $documents->total() }} documentos registrados</div>
            </div>
            <a href="{{ route('properties.index') }}" class="btn btn-primary fw-bold">
                <i class="ki-outline ki-folder fs-4 me-1"></i> Ver expedientes
            </a>
        </div>

        <div class="row g-5 mb-8">
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body text-center py-7">
                        <div class="fs-1 fw-bold text-success">{{ $stats['approved'] }}</div>
                        <div class="text-muted">Vigentes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body text-center py-7">
                        <div class="fs-1 fw-bold text-primary">{{ $stats['pending_review'] }}</div>
                        <div class="text-muted">Pend. revision</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body text-center py-7">
                        <div class="fs-1 fw-bold text-warning">{{ $stats['expired'] }}</div>
                        <div class="text-muted">Vencidos</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body text-center py-7">
                        <div class="fs-1 fw-bold text-danger">{{ $stats['rejected'] }}</div>
                        <div class="text-muted">Rechazados</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('documents.index') }}" class="row g-4">
                    <div class="col-lg-6">
                        <input type="text" name="q" class="form-control" placeholder="Buscar documento..."
                            value="{{ $filters['q'] }}">
                    </div>
                    <div class="col-lg-3">
                        <select name="entity" class="form-select">
                            <option value="">Todas las entidades</option>
                            <option value="property" {{ $filters['entity'] === 'property' ? 'selected' : '' }}>Propiedades</option>
                            <option value="tenant" {{ $filters['entity'] === 'tenant' ? 'selected' : '' }}>Inquilinos</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <select name="status" class="form-select">
                            <option value="">Todos los estados</option>
                            @foreach ($statusFilters as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" {{ $filters['status'] === $statusValue ? 'selected' : '' }}>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-3">
                        <a href="{{ route('documents.index') }}" class="btn btn-light">Limpiar</a>
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
                                <th class="min-w-260px">Documento</th>
                                <th class="min-w-220px">Entidad</th>
                                <th class="min-w-160px">Vencimiento</th>
                                <th class="min-w-150px">Estado</th>
                                <th class="min-w-120px">Versiones</th>
                                <th class="min-w-210px text-end">Opciones</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($documents as $document)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="ki-outline ki-document fs-2 text-gray-500"></i>
                                            <div>
                                                <div class="fw-bold text-gray-900">{{ $document['label'] }}</div>
                                                <div class="text-muted fs-7">{{ $document['file_name'] ?: 'Sin archivo cargado' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $document['entity_name'] }}</div>
                                        <div class="text-muted fs-7">{{ $document['entity_type_label'] }}</div>
                                    </td>
                                    <td>
                                        {{ $document['expires_at'] ? $document['expires_at']->format('d/m/Y') : '-' }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $document['status_badge_class'] }}">{{ $document['status_label'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info text-info">v{{ $document['versions_count'] ?: 0 }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            @if ($document['file_url'])
                                                <a href="{{ $document['file_url'] }}" target="_blank" class="btn btn-sm btn-light-primary">
                                                    Ver archivo
                                                </a>
                                            @endif
                                            @if ($document['entity_url'])
                                                <a href="{{ $document['entity_url'] }}" class="btn btn-sm btn-primary">
                                                    Expediente
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-16 text-muted">No hay documentos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
@endsection
