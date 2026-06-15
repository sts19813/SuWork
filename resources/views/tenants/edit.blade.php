@extends('layouts.app')

@section('title', 'Editar Inquilino | SuWork')

@php
    $initials = collect(preg_split('/\s+/', trim((string) $tenant->full_name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@push('styles')
    <style>
        .tenant-edit-shell .tenant-profile-card {
            overflow: hidden;
            border: 0;
            background:
                radial-gradient(circle at top right, rgba(0, 158, 247, 0.14), transparent 36%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }

        .tenant-edit-shell .tenant-profile-meta {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px dashed var(--bs-gray-300);
        }

        .tenant-edit-shell .tenant-profile-meta:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .tenant-edit-shell .tenant-action-bar {
            border-top: 1px dashed var(--bs-gray-300);
            padding-top: 1.5rem;
        }

        .tenant-edit-shell .tenant-form-card {
            border: 0;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }
    </style>
@endpush

@section('content')
    <div class="py-10 property-module tenant-edit-shell">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <a href="{{ route('tenants.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a inquilinos
            </a>

            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('dossiers.tenants.show', $tenant) }}" class="btn btn-light-primary">
                    <i class="ki-outline ki-folder fs-4 me-1"></i> Ir a expediente
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-8">
                <div class="fw-bold mb-2">Hay errores en el formulario:</div>
                <ul class="mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-8">
            <div class="col-xxl-4">
                <div class="card tenant-profile-card h-100">
                    <div class="card-body p-8">
                        <div class="d-flex align-items-center gap-5 mb-6">
                            <div class="symbol symbol-80px symbol-circle">
                                <span class="symbol-label bg-light-primary text-primary fs-1 fw-bolder">
                                    {{ $initials ?: 'I' }}
                                </span>
                            </div>
                            <div>
                                <span class="badge {{ $tenant->dossier_status_badge_class }} mb-3">{{ $tenant->dossier_status_label }}</span>
                                <h1 class="fs-2 fw-bold text-gray-900 mb-1">{{ $tenant->full_name }}</h1>
                                <div class="text-muted">Edita sus datos sin mezclar el expediente dentro del formulario.</div>
                            </div>
                        </div>

                        <div class="separator my-6"></div>

                        <div class="tenant-profile-meta">
                            <span class="symbol symbol-40px">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-outline ki-phone fs-3 text-success"></i>
                                </span>
                            </span>
                            <div>
                                <div class="text-muted fs-8 text-uppercase fw-bold">Contacto</div>
                                <div class="fw-semibold text-gray-900">{{ $tenant->phone_primary }}</div>
                                <div class="text-muted fs-7">{{ $tenant->email }}</div>
                            </div>
                        </div>

                        <div class="tenant-profile-meta">
                            <span class="symbol symbol-40px">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-outline ki-briefcase fs-3 text-info"></i>
                                </span>
                            </span>
                            <div>
                                <div class="text-muted fs-8 text-uppercase fw-bold">Perfil laboral</div>
                                <div class="fw-semibold text-gray-900">{{ $tenant->occupation ?: 'Sin ocupacion registrada' }}</div>
                                <div class="text-muted fs-7">{{ $tenant->employer ?: 'Sin empleador registrado' }}</div>
                            </div>
                        </div>

                        <div class="tenant-profile-meta">
                            <span class="symbol symbol-40px">
                                <span class="symbol-label bg-light-warning">
                                    <i class="ki-outline ki-dollar fs-3 text-warning"></i>
                                </span>
                            </span>
                            <div>
                                <div class="text-muted fs-8 text-uppercase fw-bold">Ingreso mensual</div>
                                <div class="fw-semibold text-gray-900">
                                    {{ $tenant->monthly_income ? '$' . number_format((float) $tenant->monthly_income, 2) . ' MXN' : 'No especificado' }}
                                </div>
                                <div class="text-muted fs-7">
                                    {{ $tenant->employment_years ? $tenant->employment_years . ' anios laborales' : 'Sin antiguedad registrada' }}
                                </div>
                            </div>
                        </div>

                       
                    </div>
                </div>
            </div>

            <div class="col-xxl-8">
                <div class="card tenant-form-card">
                    <form method="POST" action="{{ route('tenants.update', $tenant) }}">
                        @csrf
                        @method('PUT')

                        <div class="card-header border-0 pt-8">
                            <div class="card-title flex-column align-items-start">
                                <h2 class="fw-bold mb-1">Perfil del inquilino</h2>
                                <div class="text-muted fs-7">Organizado por secciones para que la edicion sea mas clara y rapida.</div>
                            </div>
                        </div>

                        <div class="card-body pt-2 px-8 pb-4">
                            @include('tenants.partials.form-fields', ['tenant' => $tenant])
                        </div>

                        <div class="card-footer border-0 pt-0 pb-8 px-8">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 tenant-action-bar">
                                <div class="text-muted fs-7">
                                    Guarda cambios cuando termines. El expediente se administra desde su modulo independiente.
                                </div>
                                <div class="d-flex gap-3">
                                    <a href="{{ route('tenants.index') }}" class="btn btn-light">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ki-outline ki-check fs-4 me-1"></i> Guardar cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
