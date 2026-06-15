@php
    $activeEntity = $activeEntity ?? 'property';
    $storagePercentage = min(100, $dossierStorage['percentage'] ?? 0);
    $storageWarningPercent = $dossierStorageSettings['storage_warning_percent'] ?? 80;
    $isNearStorageLimit = $storagePercentage >= $storageWarningPercent || ($dossierStorage['is_over_limit'] ?? false);
@endphp

<div id="dossier-settings-module" class="py-10 dossier-settings">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
        <div>
            <h1 class="mb-1 fw-bold">Configuración de expedientes</h1>
            <div class="text-muted">Define los documentos iniciales que aparecerán en cada expediente.</div>
        </div>
        <a href="{{ route('documents.index') }}" class="btn btn-icon btn-light-primary" title="Documentos">
            <i class="bi bi-folder2-open"></i>
        </a>
    </div>

    <div class="row g-4 mb-6">
        <div class="col-xl-8">
            <div class="storage-panel p-6 p-lg-8 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 mb-7">
                    <div>
                        <span class="badge badge-light-success mb-3">Expedientes</span>
                        <h2 class="fw-bold text-white mb-2">Almacenamiento documental</h2>
                        <div class="text-muted fw-semibold">
                            {{ $dossierStorage['used_label'] }} ocupados de {{ $dossierStorage['limit_label'] }} contratados.
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fs-1 fw-bold text-white">{{ $dossierStorage['percentage_label'] }}%</div>
                        <div class="text-muted fw-semibold">ocupado</div>
                    </div>
                </div>

                <div class="storage-meter mb-6" role="progressbar"
                    aria-valuenow="{{ round($storagePercentage, 2) }}"
                    aria-valuemin="0"
                    aria-valuemax="100">
                    <div class="storage-meter-bar" style="width: {{ $storagePercentage }}%;"></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="storage-soft-stat">
                            <div class="text-muted fw-semibold fs-8 mb-1">Ocupado exacto</div>
                            <div class="fw-bold text-white">{{ $dossierStorage['used_exact_label'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="storage-soft-stat">
                            <div class="text-muted fw-semibold fs-8 mb-1">Disponible</div>
                            <div class="fw-bold text-white">{{ $dossierStorage['available_label'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="storage-soft-stat">
                            <div class="text-muted fw-semibold fs-8 mb-1">Max. por archivo</div>
                            <div class="fw-bold text-white">{{ $dossierUploadLimit['effective_label'] }}</div>
                        </div>
                    </div>
                </div>

                @if ($isNearStorageLimit)
                    <div class="alert alert-warning mt-6 mb-0">
                        El almacenamiento de expedientes esta cerca de su limite configurado.
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-6">
                    <h3 class="fw-bold mb-5">Plan contratado</h3>
                    <form method="POST" action="{{ route('settings.dossiers.storage.update') }}" data-dossier-settings-form>
                        @csrf
                        @method('PATCH')

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Capacidad</label>
                            <div class="input-group input-group-solid">
                                <input type="number" step="0.5" min="1" max="10240" name="storage_limit_gb"
                                    class="form-control form-control-solid"
                                    value="{{ $dossierStorageSettings['storage_limit_gb'] }}">
                                <span class="input-group-text">GB</span>
                            </div>
                            <div class="invalid-feedback d-block" data-error-for="storage_limit_gb"></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Maximo por archivo</label>
                            <div class="input-group input-group-solid">
                                <input type="number" min="1" max="51200" name="max_file_size_mb"
                                    class="form-control form-control-solid"
                                    value="{{ $dossierStorageSettings['max_file_size_mb'] }}">
                                <span class="input-group-text">MB</span>
                            </div>
                            <div class="invalid-feedback d-block" data-error-for="max_file_size_mb"></div>
                            @if ($dossierUploadLimit['is_server_limited'])
                                <div class="form-text text-warning">
                                    El servidor limita la carga efectiva a {{ $dossierUploadLimit['effective_label'] }}.
                                </div>
                            @endif
                        </div>

                        <div class="mb-6">
                            <label class="form-label fw-semibold">Alerta de uso</label>
                            <div class="input-group input-group-solid">
                                <input type="number" min="50" max="100" name="storage_warning_percent"
                                    class="form-control form-control-solid"
                                    value="{{ $dossierStorageSettings['storage_warning_percent'] }}">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="invalid-feedback d-block" data-error-for="storage_warning_percent"></div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check2 me-1"></i> Guardar almacenamiento
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-6">
        @foreach ($entityLabels as $entityType => $entityLabel)
            @php
                $entityRequirements = $requirementsByEntity[$entityType] ?? collect();
                $activeCount = $entityRequirements->where('is_active', true)->count();
            @endphp
            <div class="col-md-4">
                <div class="settings-stat p-5">
                    <div class="text-muted fs-7 text-uppercase">{{ $entityLabel }}</div>
                    <div class="fs-2 fw-bold">{{ $activeCount }}</div>
                    <div class="text-muted fs-8">documentos activos</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-line-tabs mb-8 fs-6" role="tablist">
                @foreach ($entityLabels as $entityType => $entityLabel)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeEntity === $entityType ? 'active' : '' }}"
                            data-bs-toggle="tab"
                            data-bs-target="#dossier-{{ $entityType }}-tab"
                            type="button"
                            role="tab"
                            data-entity="{{ $entityType }}">
                            <i class="bi {{ $entityType === 'property' ? 'bi-house-door' : ($entityType === 'tenant' ? 'bi-people' : 'bi-person-vcard') }} me-1"></i>
                            {{ $entityLabel }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach ($entityLabels as $entityType => $entityLabel)
                    @php
                        $requirements = $requirementsByEntity[$entityType] ?? collect();
                    @endphp
                    <div class="tab-pane fade {{ $activeEntity === $entityType ? 'show active' : '' }}"
                        id="dossier-{{ $entityType }}-tab"
                        role="tabpanel">
                        <div class="row g-8">
                            <div class="col-xl-5">
                                <div class="border rounded p-6">
                                    <h3 class="fw-bold mb-5">Agregar documento</h3>
                                    <form method="POST" action="{{ route('settings.dossiers.requirements.store', ['entity' => $entityType]) }}" data-dossier-settings-form>
                                        @csrf
                                        <input type="hidden" name="entity_type" value="{{ $entityType }}">

                                        <div class="mb-5">
                                            <label class="form-label required">Nombre del documento</label>
                                            <input type="text" name="label" class="form-control" placeholder="Ej: Contrato">
                                            <div class="invalid-feedback d-block" data-error-for="label"></div>
                                        </div>

                                        <label class="form-check form-switch form-check-custom form-check-solid mb-6">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                            <span class="form-check-label fw-semibold">Activo en nuevos expedientes</span>
                                        </label>

                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-plus-lg me-1"></i> Agregar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="col-xl-7">
                                <div class="d-flex flex-column gap-4">
                                    @forelse ($requirements as $requirement)
                                        <div class="document-row {{ $requirement->is_active ? '' : 'is-inactive' }} p-5">
                                            <form method="POST" action="{{ route('settings.dossiers.requirements.update', [$requirement, 'entity' => $entityType]) }}" data-dossier-settings-form>
                                                @csrf
                                                @method('PUT')
                                                <div class="row g-4 align-items-end">
                                                    <div class="col-lg-6">
                                                        <label class="form-label">Documento</label>
                                                        <input type="text" name="label" class="form-control" value="{{ $requirement->label }}">
                                                        <div class="invalid-feedback d-block" data-error-for="label"></div>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <label class="form-label">Orden</label>
                                                        <input type="number" name="sort_order" class="form-control" min="0" max="9999" value="{{ $requirement->sort_order }}">
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <label class="form-check form-switch form-check-custom form-check-solid mb-3">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $requirement->is_active ? 'checked' : '' }}>
                                                            <span class="form-check-label fw-semibold">Activo</span>
                                                        </label>
                                                    </div>
                                                    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                                        <div>
                                                            <span class="badge badge-light-secondary text-secondary">{{ $requirement->document_type }}</span>
                                                            @if (!$requirement->is_active)
                                                                <span class="badge badge-light-warning text-warning ms-2">Inactivo</span>
                                                            @endif
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-check2 me-1"></i> Guardar
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <form method="POST" action="{{ route('settings.dossiers.requirements.destroy', [$requirement, 'entity' => $entityType]) }}" class="text-end mt-3" data-dossier-settings-form>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger" data-dossier-delete>
                                                    <i class="bi bi-trash me-1"></i> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <div class="settings-empty text-center text-muted py-10">
                                            No hay documentos configurados para {{ mb_strtolower($entityLabel) }}.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
