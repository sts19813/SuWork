@php
    $requiredDocuments = collect($documents ?? []);
    $customDocuments = collect($customDocuments ?? []);
    $allDocuments = $requiredDocuments->concat($customDocuments)->values();
    $historicalVersions = $allDocuments
        ->flatMap(function ($document) {
            $versions = $document->relationLoaded('versions') ? $document->versions : collect();

            return $versions->skip(1)->map(fn ($version) => ['document' => $document, 'version' => $version]);
        })
        ->values();
    $expiredDocuments = $allDocuments->filter(fn ($document) => $document->expires_at && $document->expires_at->lt(today()))->values();
    $filledCount = $allDocuments->filter(fn ($document) => filled($document->file_path))->count();
    $totalCount = $allDocuments->count();
    $storagePercentage = min(100, $dossierStorage['percentage'] ?? 0);

    $fileIcon = function (?string $name): array {
        $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

        return match ($extension) {
            'zip' => ['ki-archive', 'text-warning', 'bg-light-warning', 'ZIP'],
            'jpg', 'jpeg', 'png' => ['ki-picture', 'text-success', 'bg-light-success', strtoupper($extension ?: 'IMG')],
            default => ['ki-document', 'text-danger', 'bg-light-danger', strtoupper($extension ?: 'PDF')],
        };
    };
@endphp

@push('styles')
    <style>
        .dossier-drive .drive-nav-link {
            border-radius: 8px;
            color: var(--bs-gray-700);
            transition: background-color .2s ease, color .2s ease;
        }

        .dossier-drive .drive-nav-link.active,
        .dossier-drive .drive-nav-link:hover {
            background-color: var(--bs-primary-light);
            color: var(--bs-primary);
        }

        .dossier-drive .document-dropzone {
            border: 2px dashed var(--bs-gray-300);
            border-radius: 8px;
            min-height: 126px;
            cursor: pointer;
            transition: border-color .2s ease, background-color .2s ease;
        }

        .dossier-drive .document-dropzone:hover,
        .dossier-drive .document-dropzone.is-dragging {
            border-color: var(--bs-primary);
            background-color: var(--bs-primary-light);
        }

        .dossier-drive .document-file-icon {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
        }

        .dossier-drive .document-tile {
            border: 1px solid #edf0f5;
            border-radius: 8px;
            background: #fff;
        }

        .dossier-drive .upload-progress-item {
            border: 1px solid var(--bs-gray-200);
            border-radius: 8px;
            padding: 1rem;
            background: var(--bs-body-bg);
        }

        .dossier-drive .storage-mini-meter {
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: #edf2f7;
        }

        .dossier-drive .storage-mini-meter-bar {
            height: 100%;
            border-radius: inherit;
            background: var(--bs-primary);
        }
    </style>
@endpush

<div class="py-10 property-module dossier-drive">
    <div class="mb-8">
        <a href="{{ $backUrl }}" class="text-gray-600 text-hover-primary fw-semibold">
            <i class="ki-outline ki-arrow-left fs-4 me-1"></i> {{ $backLabel }}
        </a>
    </div>

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

    <div class="card mb-8">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-6 p-8">
            <div>
                <h1 class="mb-2 fw-bold">{{ $title }}</h1>
                <div class="fs-4 fw-bold text-gray-900">{{ $entityName }}</div>
                <div class="text-muted">{{ $entityMeta }}</div>
            </div>
            <div class="min-w-250px">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Documentos cargados</span>
                    <span class="fw-bold">{{ $filledCount }}/{{ $totalCount }}</span>
                </div>
                <div class="progress h-8px mb-4">
                    <div class="progress-bar bg-primary" style="width: {{ $totalCount > 0 ? ($filledCount / $totalCount) * 100 : 0 }}%"></div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Espacio usado</span>
                    <span class="fw-bold">{{ $dossierStorage['used_label'] ?? '0 B' }}</span>
                </div>
                <div class="storage-mini-meter">
                    <div class="storage-mini-meter-bar" style="width: {{ $storagePercentage }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-8">
        <div class="col-xl-3">
            <div class="card">
                <div class="card-body p-5">
                    <div class="d-flex flex-column gap-2" role="tablist">
                        <button class="drive-nav-link active border-0 text-start bg-transparent px-4 py-3 fw-semibold"
                            data-bs-toggle="tab" data-bs-target="#required-documents-pane" type="button">
                            <i class="ki-outline ki-folder fs-2 me-2"></i> Documentos
                            <span class="badge badge-light ms-2">{{ $allDocuments->count() }}</span>
                        </button>
                        <button class="drive-nav-link border-0 text-start bg-transparent px-4 py-3 fw-semibold"
                            data-bs-toggle="tab" data-bs-target="#historical-documents-pane" type="button">
                            <i class="ki-outline ki-time fs-2 me-2"></i> Historicos
                            <span class="badge badge-light ms-2">{{ $historicalVersions->count() }}</span>
                        </button>
                        <a href="{{ route('documents.expired', ['entity' => $entityType]) }}"
                            class="drive-nav-link px-4 py-3 fw-semibold">
                            <i class="ki-outline ki-calendar-tick fs-2 me-2"></i> Vencidos
                            <span class="badge badge-light-warning text-warning ms-2">{{ $expiredDocuments->count() }}</span>
                        </a>
                    </div>

                    <div class="separator my-6"></div>

                    <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data"
                        data-dossier-upload-form
                        data-custom-document-form
                        data-max-upload-size="{{ $dossierUploadLimit['effective_bytes'] }}"
                        data-max-upload-label="{{ $dossierUploadLimit['effective_label'] }}">
                        @csrf
                        <input type="hidden" name="label" data-custom-label>
                        <input type="hidden" name="expires_at">
                        <input id="custom-upload-{{ $entityType }}" type="file" name="file" class="d-none"
                            accept=".pdf,.jpg,.jpeg,.png,.zip" data-dossier-file-input>
                        <label for="custom-upload-{{ $entityType }}" class="document-dropzone d-flex align-items-center justify-content-center text-center p-5" data-document-dropzone>
                            <span>
                                <i class="ki-outline ki-file-up fs-2x text-gray-500 d-block mb-3"></i>
                                <span class="fw-bold text-gray-900 d-block">Agregar archivo</span>
                                <span class="text-muted fs-8 d-block mt-2">PDF, imagenes o ZIP hasta {{ $dossierUploadLimit['effective_label'] }}</span>
                            </span>
                        </label>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-9">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="required-documents-pane">
                    <div class="card">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold mb-0">Documentos principales</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0 d-flex flex-column gap-4">
                            @foreach ($requiredDocuments as $document)
                                @include('documents.partials.document-tile', ['document' => $document])
                            @endforeach
                        </div>
                    </div>

                    <div class="card mt-6">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold mb-0">Otros documentos</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0 d-flex flex-column gap-4">
                            @forelse ($customDocuments as $document)
                                @include('documents.partials.document-tile', ['document' => $document])
                            @empty
                                <div class="text-center text-muted py-12">Aun no hay documentos adicionales.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="historical-documents-pane">
                    <div class="card">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold mb-0">Documentos reemplazados</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    <thead>
                                        <tr class="text-muted text-uppercase fs-8">
                                            <th>Documento</th>
                                            <th>Version</th>
                                            <th>Archivo</th>
                                            <th>Fecha</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($historicalVersions as $item)
                                            @php
                                                $document = $item['document'];
                                                $version = $item['version'];
                                            @endphp
                                            <tr>
                                                <td class="fw-bold text-gray-900">{{ $document->label }}</td>
                                                <td>v{{ $version->version_number }}</td>
                                                <td>{{ $version->original_name }}</td>
                                                <td>{{ $version->uploaded_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td class="text-end">
                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($version->file_path) }}" target="_blank"
                                                        class="btn btn-icon btn-light btn-active-light-primary btn-sm" title="Ver">
                                                        <i class="ki-outline ki-eye fs-2"></i>
                                                    </a>
                                                    @if ($canDeleteDossierFiles)
                                                        <form method="POST" action="{{ $versionDestroyRouteResolver($document, $version) }}" class="d-inline"
                                                            onsubmit="return confirm('Eliminar esta version del expediente?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-icon btn-light btn-active-light-danger btn-sm" title="Eliminar">
                                                                <i class="ki-outline ki-trash fs-2"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-12">No hay documentos reemplazados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <div class="text-muted fw-semibold mb-3" data-document-file-summary>Sin cargas activas.</div>
                <div class="d-flex flex-column gap-3" data-document-upload-progress-list></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var summary = document.querySelector('[data-document-file-summary]');
            var progressList = document.querySelector('[data-document-upload-progress-list]');
            var activeUploads = 0;
            var finishedUploads = 0;
            var failedUploads = 0;

            function showToast(type, message) {
                if (window.SuWorkToast?.fire) {
                    window.SuWorkToast.fire(type, message);
                    return;
                }
                window.alert(message);
            }

            function formatBytes(bytes) {
                if (!bytes) return '0 B';
                var units = ['B', 'KB', 'MB', 'GB'];
                var size = bytes;
                var unitIndex = 0;

                while (size >= 1024 && unitIndex < units.length - 1) {
                    size = size / 1024;
                    unitIndex++;
                }

                return (unitIndex === 0 ? size : size.toFixed(1)) + ' ' + units[unitIndex];
            }

            function createProgressItem(file) {
                var item = document.createElement('div');
                item.className = 'upload-progress-item';
                item.innerHTML =
                    '<div class="d-flex justify-content-between align-items-center mb-2">' +
                        '<div class="min-w-0">' +
                            '<div class="fw-bold text-gray-900 text-truncate"></div>' +
                            '<div class="text-muted fs-7"></div>' +
                        '</div>' +
                        '<span class="badge badge-light-primary" data-upload-status>0%</span>' +
                    '</div>' +
                    '<div class="progress h-6px">' +
                        '<div class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>' +
                    '</div>';

                item.querySelector('.fw-bold').textContent = file.name;
                item.querySelector('.text-muted').textContent = formatBytes(file.size);
                progressList.prepend(item);

                return item;
            }

            function setProgress(item, percent, text, badgeClass) {
                var bar = item.querySelector('.progress-bar');
                var status = item.querySelector('[data-upload-status]');
                bar.style.width = percent + '%';
                bar.setAttribute('aria-valuenow', percent);
                status.textContent = text || percent + '%';
                if (badgeClass) status.className = 'badge ' + badgeClass;
            }

            function firstValidationError(response) {
                if (!response || !response.errors) return response?.message || null;
                var keys = Object.keys(response.errors);
                return keys.length ? response.errors[keys[0]][0] : response.message || null;
            }

            function uploadError(request, maxLabel) {
                if (request.status === 413) return 'El archivo supera el limite permitido. Maximo: ' + maxLabel + '.';
                if (request.status === 401 || request.status === 419) return 'Tu sesion expiro. Actualiza la pagina.';
                if (request.responseText) {
                    try {
                        return firstValidationError(JSON.parse(request.responseText)) || 'No se pudo cargar el archivo.';
                    } catch (error) {
                        return 'No se pudo cargar el archivo.';
                    }
                }
                return 'No se pudo cargar el archivo.';
            }

            function finishUpload(item, ok, message) {
                finishedUploads++;
                failedUploads += ok ? 0 : 1;
                setProgress(item, 100, ok ? 'Cargado' : 'Error', ok ? 'badge-light-success' : 'badge-light-danger');

                if (!ok && message) {
                    var errorText = document.createElement('div');
                    errorText.className = 'text-danger fs-7 mt-2';
                    errorText.textContent = message;
                    item.appendChild(errorText);
                }

                summary.textContent = finishedUploads + ' de ' + activeUploads + ' archivo(s) procesado(s).';

                if (finishedUploads === activeUploads && failedUploads === 0) {
                    showToast('success', 'Documento cargado correctamente.');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 650);
                } else if (finishedUploads === activeUploads) {
                    showToast('danger', message || 'No se pudieron cargar algunos archivos.');
                }
            }

            function labelFromFile(file) {
                return (file.name || 'Documento').replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ').trim() || 'Documento';
            }

            function uploadFile(form, file) {
                var maxSize = parseInt(form.dataset.maxUploadSize || '0', 10);
                var maxLabel = form.dataset.maxUploadLabel || 'limite configurado';
                var item = createProgressItem(file);

                if (maxSize > 0 && file.size > maxSize) {
                    finishUpload(item, false, 'El archivo pesa ' + formatBytes(file.size) + '. El maximo permitido es ' + maxLabel + '.');
                    return;
                }

                if (form.matches('[data-custom-document-form]')) {
                    var labelInput = form.querySelector('[data-custom-label]');
                    if (labelInput) labelInput.value = labelFromFile(file);
                }

                var request = new XMLHttpRequest();
                var formData = new FormData(form);
                formData.set('file', file);

                request.open('POST', form.action, true);
                request.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                request.setRequestHeader('Accept', 'application/json');

                request.upload.addEventListener('progress', function (event) {
                    if (!event.lengthComputable) return;
                    setProgress(item, Math.min(99, Math.round((event.loaded / event.total) * 100)));
                });

                request.addEventListener('load', function () {
                    var ok = request.status >= 200 && request.status < 300;
                    finishUpload(item, ok, ok ? '' : uploadError(request, maxLabel));
                });

                request.addEventListener('error', function () {
                    finishUpload(item, false, 'Error de conexion al cargar el archivo.');
                });

                request.send(formData);
            }

            function uploadFiles(form, files) {
                files = Array.prototype.slice.call(files || []);
                if (!files.length) return;

                if (finishedUploads === activeUploads) {
                    activeUploads = 0;
                    finishedUploads = 0;
                    failedUploads = 0;
                    progressList.innerHTML = '';
                }

                activeUploads += files.length;
                summary.textContent = 'Cargando ' + activeUploads + ' archivo(s)...';
                files.forEach(function (file) {
                    uploadFile(form, file);
                });
            }

            document.querySelectorAll('[data-dossier-upload-form]').forEach(function (form) {
                var input = form.querySelector('[data-dossier-file-input]');
                var dropzone = form.querySelector('[data-document-dropzone]');

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                });

                input?.addEventListener('change', function () {
                    uploadFiles(form, input.files);
                    input.value = '';
                });

                if (!dropzone) return;

                ['dragenter', 'dragover'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        dropzone.classList.add('is-dragging');
                    });
                });

                ['dragleave', 'drop'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        dropzone.classList.remove('is-dragging');
                    });
                });

                dropzone.addEventListener('drop', function (event) {
                    uploadFiles(form, event.dataTransfer.files);
                });
            });
        });
    </script>
@endpush
