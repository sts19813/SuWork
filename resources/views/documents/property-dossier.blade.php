@extends('layouts.app')

@section('title', 'Expediente de Propiedad | SuWork')

@section('content')
    @php
        $photoUrl = $property->facade_photo_path
            ? \Illuminate\Support\Facades\Storage::url($property->facade_photo_path)
            : asset('metronic/assets/media/svg/files/blank-image.svg');
    @endphp

    <div class="py-10 property-module">
        <div class="mb-8">
            <a href="{{ route('properties.show', $property) }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a propiedad
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
                <div>
                    <h1 class="mb-2 fw-bold">Expediente de propiedad</h1>
                    <div class="fs-4 fw-bold text-gray-900">{{ $property->internal_name }}</div>
                    <div class="text-muted">{{ $property->type?->name ?? '-' }} | {{ $property->zone?->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Documentos versionados</h3>
            </div>
            <div class="card-body pt-0 d-flex flex-column gap-6">
                @foreach ($documents as $document)
                    @php
                        $versions = $document->relationLoaded('versions') ? $document->versions : collect();
                    @endphp
                    <div class="border rounded p-6">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-4 mb-5">
                            <div>
                                <h4 class="mb-1">{{ $document->label }}</h4>
                                <span class="badge {{ $document->status_badge_class }}">{{ $document->status_label }}</span>
                                <span class="badge badge-light-info text-info ms-2">v{{ $versions->count() }}</span>
                            </div>
                            @if ($document->file_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" class="btn btn-sm btn-light-primary" target="_blank">
                                    Ver archivo vigente
                                </a>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('dossiers.properties.documents.upload', [$property, $document->document_type]) }}"
                            enctype="multipart/form-data" class="row g-4 align-items-end">
                            @csrf
                            <div class="col-lg-5">
                                <label class="form-label">Subir nueva version</label>
                                <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label">Vence el (opcional)</label>
                                <input type="date" name="expires_at" class="form-control"
                                    value="{{ $document->expires_at?->format('Y-m-d') }}">
                            </div>
                            <div class="col-lg-3">
                                <button type="submit" class="btn btn-primary w-100">Guardar version</button>
                            </div>
                        </form>

                        @if ($versions->isNotEmpty())
                            <div class="table-responsive mt-5">
                                <table class="table table-row-bordered align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted text-uppercase fs-8">
                                            <th>Version</th>
                                            <th>Archivo</th>
                                            <th>Fecha carga</th>
                                            <th class="text-end">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($versions as $version)
                                            <tr>
                                                <td>v{{ $version->version_number }}</td>
                                                <td>{{ $version->original_name }}</td>
                                                <td>{{ $version->uploaded_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td class="text-end">
                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($version->file_path) }}" target="_blank"
                                                        class="btn btn-sm btn-light-primary">
                                                        Ver
                                                    </a>
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
        </div>
    </div>
@endsection
