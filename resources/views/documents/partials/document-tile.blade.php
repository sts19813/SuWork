@php
    $versions = $document->relationLoaded('versions') ? $document->versions : collect();
    $latestVersion = $versions->first();
    $fileName = $latestVersion?->original_name;
    [$icon, $iconColor, $iconBg, $extensionLabel] = $fileIcon($fileName ?: $document->file_path);
    $isExpired = $document->expires_at && $document->expires_at->lt(today());
@endphp

<div class="document-tile p-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-4">
        <div class="d-flex align-items-start gap-4 min-w-0">
            <div class="document-file-icon rounded {{ $iconBg }} d-flex align-items-center justify-content-center">
                <i class="ki-outline {{ $icon }} fs-2 {{ $iconColor }}"></i>
            </div>
            <div class="min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h4 class="fw-bold text-gray-900 mb-0">{{ $document->label }}</h4>
                    <span class="badge badge-light">{{ $extensionLabel }}</span>
                    @if ($versions->count() > 1)
                        <span class="badge badge-light-info text-info">v{{ $versions->count() }}</span>
                    @endif
                    @if ($isExpired)
                        <span class="badge badge-light-warning text-warning">Vencido</span>
                    @endif
                </div>
                <div class="text-muted fw-semibold fs-7 text-truncate">
                    {{ $fileName ?: 'Sin archivo cargado' }}
                </div>
                @if ($document->expires_at)
                    <div class="text-muted fs-8 mt-1">Vence: {{ $document->expires_at->format('d/m/Y') }}</div>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($document->file_path)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" target="_blank"
                    class="btn btn-icon btn-light btn-active-light-primary btn-sm" title="Ver archivo vigente">
                    <i class="ki-outline ki-eye fs-2"></i>
                </a>
                <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" download
                    class="btn btn-icon btn-light btn-active-light-primary btn-sm" title="Descargar">
                    <i class="ki-outline ki-file-down fs-2"></i>
                </a>
            @endif
            @if ($canDeleteDossierFiles && $document->exists && $document->file_path)
                <form method="POST" action="{{ $destroyRouteResolver($document) }}" class="d-inline"
                    onsubmit="return confirm('Eliminar este documento y su historial de versiones?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-icon btn-light btn-active-light-danger btn-sm" type="submit" title="Eliminar">
                        <i class="ki-outline ki-trash fs-2"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if ($document->exists)
        <form method="POST" action="{{ $uploadRouteResolver($document) }}" enctype="multipart/form-data"
            class="mt-5"
            data-dossier-upload-form
            data-max-upload-size="{{ $dossierUploadLimit['effective_bytes'] }}"
            data-max-upload-label="{{ $dossierUploadLimit['effective_label'] }}">
            @csrf
            <div class="row g-4 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">Vencimiento opcional</label>
                    <input type="date" name="expires_at" class="form-control form-control-solid"
                        value="{{ $document->expires_at?->format('Y-m-d') }}">
                </div>
                <div class="col-lg-8">
                    <input id="upload-{{ $entityType }}-{{ $document->document_type }}" type="file" name="file"
                        class="d-none" accept=".pdf,.jpg,.jpeg,.png,.zip" data-dossier-file-input>
                    <label for="upload-{{ $entityType }}-{{ $document->document_type }}"
                        class="document-dropzone d-flex align-items-center justify-content-center text-center p-5"
                        data-document-dropzone>
                        <span>
                            <i class="ki-outline ki-file-up fs-2x text-gray-500 d-block mb-2"></i>
                            <span class="fw-bold text-gray-900 d-block">Arrastra o selecciona archivo</span>
                            <span class="text-muted fs-8 d-block mt-1">La carga inicia automaticamente</span>
                        </span>
                    </label>
                </div>
            </div>
        </form>
    @endif
</div>
