@extends('layouts.app')

@section('title', 'Documentos | SuWork')

@section('content')
    @php
        $activeView = $filters['view'] ?? 'all';
        $storagePercentage = min(100, $dossierStorage['percentage'] ?? 0);

        $fileIcon = function (?string $name): array {
            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

            return match ($extension) {
                'zip' => ['ki-archive', 'text-warning', 'bg-light-warning'],
                'jpg', 'jpeg', 'png', 'webp', 'gif' => ['ki-picture', 'text-success', 'bg-light-success'],
                default => ['ki-document', 'text-danger', 'bg-light-danger'],
            };
        };

        $documentTitle = $activeView === 'expired' ? 'Documentos vencidos' : 'Documentos actuales';
        $documentSubtitle = $activeView === 'expired'
            ? 'Archivos existentes en todo el sistema cuya vigencia ya vencio.'
            : 'Vista tipo historial para revisar todos los documentos existentes del sistema.';
    @endphp

    @push('styles')
        <style>
            .documents-index .documents-table-card {
                border: 1px solid var(--bs-gray-200);
                border-radius: 1rem;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            }

            .documents-index .documents-filter-card {
                border: 1px solid var(--bs-gray-200);
                border-radius: 1rem;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            }

            .documents-index .documents-file-icon {
                width: 44px;
                height: 44px;
                flex: 0 0 44px;
            }

            .documents-index .documents-kpi-card {
                border: 1px solid var(--bs-gray-200);
                border-radius: 1rem;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
            }
        </style>
    @endpush

    <div class="py-10 property-module documents-index">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">{{ $documentTitle }}</h1>
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
                <div class="card documents-kpi-card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Total existentes</div>
                        <div class="fs-1 fw-bold text-gray-900">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card documents-kpi-card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Propiedades</div>
                        <div class="fs-1 fw-bold text-primary">{{ $stats['properties'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card documents-kpi-card">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Inquilinos</div>
                        <div class="fs-1 fw-bold text-info">{{ $stats['tenants'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <a href="{{ route('documents.expired', ['entity' => $filters['entity'], 'q' => $filters['q']]) }}" class="card documents-kpi-card text-decoration-none">
                    <div class="card-body py-7">
                        <div class="text-muted fw-semibold">Vencidos</div>
                        <div class="fs-1 fw-bold text-warning">{{ $stats['expired'] }}</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="card documents-filter-card mb-8">
            <div class="card-body py-6">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mb-6">
                    <div class="btn-group" role="group" aria-label="Vistas de documentos">
                        <a href="{{ route('documents.index', ['entity' => $filters['entity'], 'q' => $filters['q']]) }}"
                            class="btn {{ $activeView === 'all' ? 'btn-primary' : 'btn-light-primary' }}">
                            Documentos actuales
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
                        <input type="text" name="q" class="form-control form-control-solid"
                            placeholder="Buscar documento, expediente o archivo..." value="{{ $filters['q'] }}">
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

        <div class="card documents-table-card">
            <div class="card-header border-0 pt-6">
                <div class="card-title flex-column align-items-start">
                    <h3 class="fw-bold mb-1">{{ $documentTitle }}</h3>
                    <div class="text-muted fs-7">{{ $documentSubtitle }}</div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted text-uppercase fs-8">
                                <th>Documento</th>
                                <th>Archivo</th>
                                <th>Vence</th>
                                <th>Fecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                @php
                                    [$icon, $iconColor, $iconBg] = $fileIcon($document['file_name']);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <div class="fw-bold text-gray-900">{{ $document['label'] }}</div>
                                            <div class="text-muted fs-7">{{ $document['entity_name'] }}</div>
                                            <div class="text-muted fs-8">{{ $document['entity_type_label'] }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="documents-file-icon rounded {{ $iconBg }} d-flex align-items-center justify-content-center">
                                                <i class="ki-outline {{ $icon }} fs-3 {{ $iconColor }}"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ $document['file_url'] }}" target="_blank" rel="noopener"
                                                    class="fw-semibold text-gray-800 text-hover-primary text-break">
                                                    {{ $document['file_name'] }}
                                                </a>
                                                <div class="text-muted fs-8">
                                                    {{ $document['is_expired'] ? 'Documento vencido' : 'Abrir en una nueva pestaña' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($document['expires_at'])
                                            <span @class(['fw-bold text-warning' => $document['is_expired']])>
                                                {{ $document['expires_at']->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $document['updated_at']?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-2">
                                            <a href="{{ $document['file_url'] }}" target="_blank" rel="noopener"
                                                class="btn btn-icon btn-light btn-active-light-primary btn-sm" title="Ver archivo">
                                                <i class="ki-outline ki-eye fs-2"></i>
                                            </a>
                                            <a href="{{ $document['file_url'] }}" download="{{ $document['file_name'] }}"
                                                class="btn btn-icon btn-light btn-active-light-primary btn-sm" title="Descargar">
                                                <i class="ki-outline ki-file-down fs-2"></i>
                                            </a>
                                            @if ($document['entity_url'])
                                                <a href="{{ $document['entity_url'] }}" class="btn btn-icon btn-primary btn-sm" title="Abrir expediente">
                                                    <i class="ki-outline ki-folder fs-2"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10">
                                        <div class="text-center text-muted py-12">No hay documentos existentes para esta vista.</div>
                                    </td>
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
