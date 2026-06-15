@extends('layouts.app')

@section('title', 'Documentos | SuWork')

@section('content')
    @php
        $activeView = $filters['view'] ?? 'all';
        $storagePercentage = min(100, $dossierStorage['percentage'] ?? 0);
    @endphp

    <div class="py-10 property-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">{{ $activeView === 'expired' ? 'Documentos vencidos' : 'Documentos' }}</h1>
                <div class="text-muted fs-6">{{ $documents->total() }} documentos encontrados</div>
            </div>
            <div class="d-flex gap-2">
                @canany(['expedientes.ver_bitacora_eliminados', 'expedientes.eliminar_archivos'])
                    <a href="{{ route('documents.deleted-files-log') }}" class="btn btn-icon btn-light-danger" title="Bitacora eliminados">
                        <i class="ki-outline ki-archive fs-2"></i>
                    </a>
                @endcanany
                <a href="{{ route('settings.dossiers.index') }}" class="btn btn-icon btn-light-primary" title="Configurar expedientes">
                    <i class="ki-outline ki-setting-2 fs-2"></i>
                </a>
            </div>
        </div>

        <div class="row g-5 mb-8">
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Total</div>
                        <div class="fs-1 fw-bold text-gray-900">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Cargados</div>
                        <div class="fs-1 fw-bold text-success">{{ $stats['with_file'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Pendientes de archivo</div>
                        <div class="fs-1 fw-bold text-primary">{{ $stats['missing'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <a href="{{ route('documents.expired', ['entity' => $filters['entity']]) }}" class="card text-decoration-none">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Vencidos</div>
                        <div class="fs-1 fw-bold text-warning">{{ $stats['expired'] }}</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="card mb-8">
            <div class="card-body py-6">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mb-6">
                    <div class="btn-group" role="group" aria-label="Vistas de documentos">
                        <a href="{{ route('documents.index', ['entity' => $filters['entity'], 'q' => $filters['q']]) }}"
                            class="btn {{ $activeView === 'all' ? 'btn-primary' : 'btn-light-primary' }}">
                            Todos
                        </a>
                        <a href="{{ route('documents.expired', ['entity' => $filters['entity'], 'q' => $filters['q']]) }}"
                            class="btn {{ $activeView === 'expired' ? 'btn-primary' : 'btn-light-primary' }}">
                            Vencidos
                        </a>
                    </div>
                    <div class="min-w-250px">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-semibold">Almacenamiento</span>
                            <span class="fw-bold">{{ $dossierStorage['used_label'] }} / {{ $dossierStorage['limit_label'] }}</span>
                        </div>
                        <div class="progress h-8px">
                            <div class="progress-bar bg-primary" style="width: {{ $storagePercentage }}%"></div>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ $activeView === 'expired' ? route('documents.expired') : route('documents.index') }}" class="row g-4">
                    <div class="col-lg-7">
                        <input type="text" name="q" class="form-control form-control-solid" placeholder="Buscar documento, expediente o archivo..."
                            value="{{ $filters['q'] }}">
                    </div>
                    <div class="col-lg-3">
                        <select name="entity" class="form-select form-select-solid">
                            <option value="">Todos los expedientes</option>
                            <option value="property" {{ $filters['entity'] === 'property' ? 'selected' : '' }}>Propiedades</option>
                            <option value="tenant" {{ $filters['entity'] === 'tenant' ? 'selected' : '' }}>Inquilinos</option>
                            <option value="owner" {{ $filters['entity'] === 'owner' ? 'selected' : '' }}>Propietarios</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ki-outline ki-magnifier fs-3"></i>
                            Buscar
                        </button>
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
                                <th class="min-w-280px">Documento</th>
                                <th class="min-w-240px">Expediente</th>
                                <th class="min-w-150px">Vencimiento</th>
                                <th class="min-w-120px">Versiones</th>
                                <th class="min-w-160px text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($documents as $document)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="symbol symbol-40px">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="ki-outline ki-document fs-2 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="fw-bold text-gray-900">{{ $document['label'] }}</div>
                                                <div class="text-muted fs-7 text-truncate">{{ $document['file_name'] ?: 'Sin archivo cargado' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $document['entity_name'] }}</div>
                                        <div class="text-muted fs-7">{{ $document['entity_type_label'] }}</div>
                                    </td>
                                    <td>
                                        @if ($document['expires_at'])
                                            <span @class(['text-warning fw-bold' => $document['is_expired']])>
                                                {{ $document['expires_at']->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">Sin fecha</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info text-info">v{{ $document['versions_count'] ?: 0 }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if ($document['file_url'])
                                            <a href="{{ $document['file_url'] }}" target="_blank" class="btn btn-icon btn-light btn-active-light-primary btn-sm" title="Ver archivo">
                                                <i class="ki-outline ki-eye fs-2"></i>
                                            </a>
                                        @endif
                                        @if ($document['entity_url'])
                                            <a href="{{ $document['entity_url'] }}" class="btn btn-icon btn-primary btn-sm" title="Abrir expediente">
                                                <i class="ki-outline ki-folder fs-2"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-16 text-muted">No hay documentos para esta vista.</td>
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
