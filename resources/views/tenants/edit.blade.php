@extends('layouts.app')

@section('title', 'Editar Inquilino | SuWork')

@section('content')
    <div class="py-10 property-module">
        <div class="mb-8">
            <a href="{{ route('tenants.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a inquilinos
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Editar inquilino</h3>
            </div>
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('tenants.update', $tenant) }}">
                    @csrf
                    @method('PUT')
                    @include('tenants.partials.form-fields', ['tenant' => $tenant])

                    <div class="d-flex justify-content-end gap-3 mt-8">
                        <a href="{{ route('tenants.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-8">
            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold">Expediente del inquilino</h3>
                <a href="{{ route('dossiers.tenants.show', $tenant) }}" class="btn btn-light-primary btn-sm">
                    Ver historial completo
                </a>
            </div>
            <div class="card-body pt-0 d-flex flex-column gap-5">
                @foreach ($tenantDocuments as $document)
                    <div class="border rounded p-5">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                            <div>
                                <div class="fw-bold fs-5">{{ $document->label }}</div>
                                <span class="badge {{ $document->status_badge_class }}">{{ $document->status_label }}</span>
                                <span class="badge badge-light-info text-info ms-2">v{{ $document->versions->count() }}</span>
                            </div>
                            @if ($document->file_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" target="_blank"
                                    class="btn btn-sm btn-light-primary">
                                    Ver archivo vigente
                                </a>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('dossiers.tenants.documents.upload', [$tenant, $document->document_type]) }}"
                            enctype="multipart/form-data" class="row g-4 align-items-end">
                            @csrf
                            <div class="col-lg-5">
                                <label class="form-label">Nueva version</label>
                                <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label">Vence el (opcional)</label>
                                <input type="date" name="expires_at" class="form-control"
                                    value="{{ $document->expires_at?->format('Y-m-d') }}">
                            </div>
                            <div class="col-lg-3">
                                <button type="submit" class="btn btn-primary w-100">Subir version</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
