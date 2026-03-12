@extends('layouts.app')

@section('title', 'Inquilinos | SuWork')

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
                <h1 class="mb-1 fw-bold text-dark">Inquilinos</h1>
                <div class="text-muted fs-6">{{ $tenants->total() }} inquilinos registrados</div>
            </div>
            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createTenantModal">
                <i class="ki-outline ki-plus fs-4 me-1"></i> Nuevo inquilino
            </button>
        </div>

        <div class="card mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('tenants.index') }}" class="row g-4">
                    <div class="col-lg-8">
                        <input type="text" name="q" class="form-control"
                            placeholder="Buscar por nombre, email, telefono..." value="{{ $search }}">
                    </div>
                    <div class="col-lg-4">
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($dossierStatuses as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" {{ $status === $statusValue ? 'selected' : '' }}>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-3">
                        <a href="{{ route('tenants.index') }}" class="btn btn-light">Limpiar</a>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-5 mb-8">
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body text-center py-7">
                        <div class="fs-1 fw-bold text-success">{{ $stats['complete'] }}</div>
                        <div class="text-muted">Completos</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body text-center py-7">
                        <div class="fs-1 fw-bold text-primary">{{ $stats['in_review'] }}</div>
                        <div class="text-muted">En revision</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body text-center py-7">
                        <div class="fs-1 fw-bold text-warning">{{ $stats['incomplete'] }}</div>
                        <div class="text-muted">Incompletos</div>
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

        <div class="card">
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-5 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase gs-0">
                                <th class="min-w-220px">Inquilino</th>
                                <th class="min-w-220px">Contacto</th>
                                <th class="min-w-140px">Ingreso mensual</th>
                                <th class="min-w-160px">Expediente</th>
                                <th class="min-w-130px text-end">Opciones</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse ($tenants as $tenant)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-4">
                                            <div class="owner-initial">{{ strtoupper(mb_substr($tenant->full_name, 0, 1)) }}</div>
                                            <div>
                                                <div class="fw-bold fs-5 text-gray-900">{{ $tenant->full_name }}</div>
                                                <div class="text-muted fs-7">{{ $tenant->employer ?: '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $tenant->phone_primary }}</div>
                                        <div class="text-muted fs-7">{{ $tenant->email ?: '-' }}</div>
                                    </td>
                                    <td>
                                        {{ $tenant->monthly_income ? '$' . number_format((float) $tenant->monthly_income, 2) : '-' }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $tenant->dossier_status_badge_class }}">
                                            {{ $tenant->dossier_status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('dossiers.tenants.show', $tenant) }}" class="btn btn-sm btn-light-primary me-2">Expediente</a>
                                        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-sm btn-primary">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-16 text-muted">No hay inquilinos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $tenants->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="createTenantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content tenant-modal-content">
                <form method="POST" action="{{ route('tenants.store') }}" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Nuevo inquilino</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body tenant-modal-body">
                        @include('tenants.partials.form-fields')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear inquilino</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($errors->any())
        <script>
            (() => {
                const createTenantModal = document.getElementById('createTenantModal');
                if (!createTenantModal) {
                    return;
                }
                const modal = new bootstrap.Modal(createTenantModal);
                modal.show();
            })();
        </script>
    @endif
@endpush
