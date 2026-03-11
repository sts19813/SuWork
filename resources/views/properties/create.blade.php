@extends('layouts.app')

@section('title', (($isEdit ?? false) ? 'Editar Propiedad' : 'Nueva Propiedad') . ' | SuWork')

@section('content')
    @php
        $isEdit = $isEdit ?? false;
        $property = $property ?? null;

        $steps = [
            1 => 'Datos de la propiedad',
            2 => 'Propietarios',
            3 => 'Documentos',
            4 => 'Inventario',
            5 => 'Estado inicial',
        ];

        $statusDescriptions = [
            \App\Models\Property::STATUS_AVAILABLE => 'La propiedad está lista para ser rentada.',
            \App\Models\Property::STATUS_IN_PROCESS => 'La propiedad está en preparación o trámite.',
            \App\Models\Property::STATUS_BLOCKED => 'La propiedad no está disponible temporalmente.',
        ];

        $ownerDefaults = [
            'name' => '',
            'phone' => '',
            'email' => '',
            'owner_type' => \App\Models\PropertyOwner::OWNER_INDIVIDUAL,
            'bank_name' => '',
            'clabe' => '',
            'account_holder' => '',
            'payment_method' => \App\Models\PropertyOwner::PAYMENT_METHOD_TRANSFER,
        ];

        $propertyOwnerDefaults = $isEdit && $property
            ? $property->owners
                ->map(
                    fn($owner) => [
                        'name' => $owner->name,
                        'phone' => $owner->phone,
                        'email' => $owner->email,
                        'owner_type' => $owner->owner_type,
                        'bank_name' => $owner->bank_name,
                        'clabe' => $owner->clabe,
                        'account_holder' => $owner->account_holder,
                        'payment_method' => $owner->payment_method,
                    ],
                )
                ->values()
                ->all()
            : [$ownerDefaults];
        $oldOwners = old('owners', $propertyOwnerDefaults ?: [$ownerDefaults]);

        $defaultAreaData = collect($defaultAreas)->map(function ($area) {
            return [
                'name' => $area,
                'notes' => '',
                'items' => [
                    ['name' => '', 'condition' => '', 'notes' => ''],
                ],
            ];
        });
        $propertyAreaDefaults = $isEdit && $property
            ? $property->inventoryAreas
                ->map(
                    fn($area) => [
                        'name' => $area->name,
                        'notes' => $area->notes,
                        'items' => $area->items->map(
                            fn($item) => [
                                'name' => $item->name,
                                'condition' => $item->condition,
                                'notes' => $item->notes,
                            ],
                        )
                            ->values()
                            ->all() ?: [['name' => '', 'condition' => '', 'notes' => '']],
                    ],
                )
                ->values()
                ->all()
            : [];
        $oldAreas = old('inventory_areas', $propertyAreaDefaults ?: $defaultAreaData->toArray());

        $fieldValue = function (string $key, mixed $default = '') use ($isEdit, $property) {
            return old($key, $isEdit && $property ? data_get($property, $key, $default) : $default);
        };

        $selectedStatus = old(
            'status',
            $isEdit && $property ? $property->status : \App\Models\Property::STATUS_AVAILABLE,
        );

        $existingDocuments = $isEdit && $property ? $property->documents->keyBy('document_type') : collect();
        $existingFacadePhoto = $isEdit && $property ? $property->facade_photo_path : null;
        $selectedType = (string) $fieldValue('property_type_id');
        $selectedZone = (string) $fieldValue('zone_id');

        $initialStep = (int) old('wizard_step', 1);
        if ($errors->isNotEmpty()) {
            $initialStep = 1;
            foreach ($errors->keys() as $errorKey) {
                if (str_starts_with($errorKey, 'owners.')) {
                    $initialStep = 2;
                    break;
                }
                if (str_starts_with($errorKey, 'documents.')) {
                    $initialStep = 3;
                    break;
                }
                if (str_starts_with($errorKey, 'inventory_areas.')) {
                    $initialStep = 4;
                    break;
                }
                if ($errorKey === 'status') {
                    $initialStep = 5;
                    break;
                }
            }
        }
    @endphp

    <div class="py-10 property-module">
        <div class="mb-8">
            <a href="{{ route('properties.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver al listado
            </a>
        </div>

        <div class="mb-9">
            <h1 class="mb-1 fw-bold">{{ $isEdit ? 'Editar Propiedad' : 'Nueva Propiedad' }}</h1>
            <p class="text-muted mb-0">
                {{ $isEdit ? 'Actualiza la información de la propiedad en los siguientes pasos.' : 'Completa los siguientes pasos para registrar una nueva propiedad.' }}
            </p>
        </div>

        <form id="property-wizard-form" method="POST"
            action="{{ $isEdit ? route('properties.update', $property) : route('properties.store') }}"
            enctype="multipart/form-data">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="wizard_step" id="wizard-step-input" value="{{ $initialStep }}">

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

            <div class="property-stepper mb-10" id="property-stepper">
                @foreach ($steps as $stepNumber => $stepLabel)
                    <div class="step-item" data-step="{{ $stepNumber }}">
                        <div class="step-circle">{{ $stepNumber }}</div>
                        <div class="step-label">{{ $stepLabel }}</div>
                    </div>
                @endforeach
            </div>

            {{-- STEP 1 --}}
            <div class="card mb-8 wizard-step" data-step-panel="1">
                <div class="card-body p-lg-10">
                    <h3 class="mb-6 fw-bold">Datos de la propiedad</h3>

                    <div class="notice d-flex bg-light-warning rounded border border-warning border-dashed p-4 mb-8">
                        <div class="d-flex flex-column text-warning">
                            <span class="fw-bold">Estado: {{ \App\Models\Property::STATUS_LABELS[$selectedStatus] ?? 'Borrador' }}</span>
                        </div>
                    </div>

                    <div class="row g-6">
                        <div class="col-lg-6">
                            <label class="form-label required">Nombre interno de la propiedad</label>
                            <input type="text" name="internal_name" class="form-control @error('internal_name') is-invalid @enderror"
                                value="{{ $fieldValue('internal_name') }}" placeholder="Ej: Casa Montebello 101">
                            @error('internal_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Referencia interna o alias</label>
                            <input type="text" name="internal_reference"
                                class="form-control @error('internal_reference') is-invalid @enderror"
                                value="{{ $fieldValue('internal_reference') }}" placeholder="Ej: MB-101">
                            @error('internal_reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label required">Tipo de propiedad</label>
                            <select name="property_type_id" required
                                class="form-select @error('property_type_id') is-invalid @enderror">
                                <option value="" disabled {{ $selectedType ? '' : 'selected' }}>Seleccionar tipo</option>
                                @foreach ($propertyTypes as $type)
                                    <option value="{{ $type->id }}"
                                        {{ $selectedType === (string) $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('property_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label required">Zona</label>
                            <select name="zone_id" required class="form-select @error('zone_id') is-invalid @enderror">
                                <option value="" disabled {{ $selectedZone ? '' : 'selected' }}>Seleccionar zona</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}" {{ $selectedZone === (string) $zone->id ? 'selected' : '' }}>
                                        {{ $zone->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('zone_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label required">Dirección completa</label>
                            <input type="text" name="full_address" class="form-control @error('full_address') is-invalid @enderror"
                                value="{{ $fieldValue('full_address') }}" placeholder="Calle, número, colonia, CP">
                            @error('full_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Complejo o privada</label>
                            <input type="text" name="complex_name" class="form-control @error('complex_name') is-invalid @enderror"
                                value="{{ $fieldValue('complex_name') }}" placeholder="Nombre del complejo">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Número oficial</label>
                            <input type="text" name="official_number"
                                class="form-control @error('official_number') is-invalid @enderror"
                                value="{{ $fieldValue('official_number') }}" placeholder="Número">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Unidad o departamento</label>
                            <input type="text" name="unit_number" class="form-control @error('unit_number') is-invalid @enderror"
                                value="{{ $fieldValue('unit_number') }}" placeholder="Ej: A-302">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Foto de fachada de la propiedad</label>
                            @if ($existingFacadePhoto)
                                <div class="mb-4">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($existingFacadePhoto) }}" alt="Fachada actual"
                                        class="property-cover">
                                </div>
                            @endif
                            <label class="upload-box">
                                <input type="file" name="facade_photo" accept=".jpg,.jpeg,.png">
                                <i class="ki-outline ki-cloud-add fs-2x text-muted mb-2"></i>
                                <span class="fw-semibold text-gray-700">Haz clic para subir una imagen</span>
                                <span class="text-muted fs-8">PNG, JPG hasta 10MB</span>
                            </label>
                            @error('facade_photo')
                                <div class="text-danger fs-8 mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 2 --}}
            <div class="card mb-8 wizard-step d-none" data-step-panel="2">
                <div class="card-body p-lg-10">
                    <h3 class="mb-3 fw-bold">Propietarios</h3>
                    <p class="text-muted mb-8">Agrega la información de uno o varios titulares de la propiedad.</p>

                    <div id="owners-container">
                        @foreach ($oldOwners as $ownerIndex => $owner)
                            <div class="owner-block border rounded p-6 mb-6" data-owner-index="{{ $ownerIndex }}">
                                <div class="d-flex justify-content-between align-items-center mb-6">
                                    <h4 class="mb-0">Propietario <span class="owner-number">{{ $loop->iteration }}</span></h4>
                                    <button type="button" class="btn btn-sm btn-light-danger btn-remove-owner {{ $loop->count === 1 ? 'd-none' : '' }}">
                                        Eliminar
                                    </button>
                                </div>

                                <div class="row g-5">
                                    <div class="col-12">
                                        <label class="form-label required">Nombre del propietario</label>
                                        <input type="text" name="owners[{{ $ownerIndex }}][name]"
                                            class="form-control @error("owners.$ownerIndex.name") is-invalid @enderror"
                                            value="{{ $owner['name'] ?? '' }}" placeholder="Nombre completo">
                                        @error("owners.$ownerIndex.name")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label required">Teléfono</label>
                                        <input type="text" name="owners[{{ $ownerIndex }}][phone]"
                                            class="form-control @error("owners.$ownerIndex.phone") is-invalid @enderror"
                                            value="{{ $owner['phone'] ?? '' }}" placeholder="999 123 4567">
                                        @error("owners.$ownerIndex.phone")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label required">Correo electrónico</label>
                                        <input type="email" name="owners[{{ $ownerIndex }}][email]"
                                            class="form-control @error("owners.$ownerIndex.email") is-invalid @enderror"
                                            value="{{ $owner['email'] ?? '' }}" placeholder="correo@ejemplo.com">
                                        @error("owners.$ownerIndex.email")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label required d-block">Tipo de titular</label>
                                        <div class="d-flex gap-6">
                                            @foreach ($ownerTypes as $typeValue => $typeLabel)
                                                <label class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="radio"
                                                        name="owners[{{ $ownerIndex }}][owner_type]" value="{{ $typeValue }}"
                                                        {{ ($owner['owner_type'] ?? '') === $typeValue ? 'checked' : '' }}>
                                                    <span class="form-check-label">{{ $typeLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <h5 class="mb-2">Datos bancarios para recibir pagos</h5>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">Banco</label>
                                        <input type="text" name="owners[{{ $ownerIndex }}][bank_name]" class="form-control"
                                            value="{{ $owner['bank_name'] ?? '' }}" placeholder="Nombre del banco">
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">CLABE</label>
                                        <input type="text" name="owners[{{ $ownerIndex }}][clabe]"
                                            class="form-control @error("owners.$ownerIndex.clabe") is-invalid @enderror"
                                            value="{{ $owner['clabe'] ?? '' }}" placeholder="18 dígitos">
                                        @error("owners.$ownerIndex.clabe")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Titular de la cuenta</label>
                                        <input type="text" name="owners[{{ $ownerIndex }}][account_holder]" class="form-control"
                                            value="{{ $owner['account_holder'] ?? '' }}" placeholder="Nombre del titular">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Método de pago permitido</label>
                                        <select name="owners[{{ $ownerIndex }}][payment_method]" class="form-select">
                                            @foreach ($paymentMethods as $methodValue => $methodLabel)
                                                <option value="{{ $methodValue }}"
                                                    {{ ($owner['payment_method'] ?? '') === $methodValue ? 'selected' : '' }}>
                                                    {{ $methodLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-owner-btn" class="btn btn-light-primary w-100 border-dashed">
                        <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar otro propietario
                    </button>
                </div>
            </div>

            {{-- STEP 3 --}}
            <div class="card mb-8 wizard-step d-none" data-step-panel="3">
                <div class="card-body p-lg-10">
                    <h3 class="mb-3 fw-bold">Documentos de la propiedad</h3>
                    <p class="text-muted mb-8">Sube los documentos obligatorios para completar el expediente de la propiedad.</p>

                    <div class="alert alert-light-danger border border-danger border-dashed d-flex justify-content-between align-items-center mb-8">
                        <div>
                            <div class="fw-bold text-danger">Faltan documentos</div>
                            <div class="text-danger fs-7">Puedes completar este paso más adelante desde el expediente.</div>
                        </div>
                        <i class="ki-outline ki-information-5 fs-2 text-danger"></i>
                    </div>

                    <div class="d-flex flex-column gap-6">
                        @foreach ($requiredDocuments as $documentKey => $documentLabel)
                            @php
                                $existingDocument = $existingDocuments->get($documentKey);
                            @endphp
                            <div class="border rounded p-6">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="mb-0">{{ $documentLabel }}</h4>
                                    <span class="badge {{ $existingDocument?->status_badge_class ?? 'badge-light-secondary text-secondary' }}">
                                        {{ $existingDocument?->status_label ?? 'Pendiente' }}
                                    </span>
                                </div>
                                <label class="upload-box upload-box-sm">
                                    <input type="file" name="documents[{{ $documentKey }}]" accept=".pdf,.jpg,.jpeg,.png">
                                    <i class="ki-outline ki-cloud-add fs-2x text-muted mb-2"></i>
                                    <span class="fw-semibold text-gray-700">Haz clic para subir documento</span>
                                    <span class="text-muted fs-8">PDF, JPG, PNG hasta 10MB</span>
                                </label>
                                @if ($existingDocument?->file_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($existingDocument->file_path) }}"
                                        target="_blank" class="btn btn-sm btn-light-primary mt-3">
                                        Ver archivo actual
                                    </a>
                                @endif
                                @error("documents.$documentKey")
                                    <div class="text-danger fs-8 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- STEP 4 --}}
            <div class="card mb-8 wizard-step d-none" data-step-panel="4">
                <div class="card-body p-lg-10">
                    <h3 class="mb-3 fw-bold">Inventario de la propiedad</h3>
                    <p class="text-muted mb-8">Documenta espacios y elementos. Este paso también puede completarse posteriormente.</p>

                    <div id="inventory-areas-container" class="d-flex flex-column gap-6">
                        @foreach ($oldAreas as $areaIndex => $area)
                            @php
                                $items = $area['items'] ?? [['name' => '', 'condition' => '', 'notes' => '']];
                            @endphp
                            <div class="border rounded p-6 inventory-area" data-area-index="{{ $areaIndex }}"
                                data-next-item-index="{{ count($items) }}">
                                <div class="d-flex justify-content-between align-items-center mb-5">
                                    <h4 class="mb-0">Área {{ $loop->iteration }}</h4>
                                    <button type="button"
                                        class="btn btn-sm btn-light-danger btn-remove-area {{ count($oldAreas) === 1 ? 'd-none' : '' }}">
                                        Eliminar área
                                    </button>
                                </div>
                                <div class="row g-5 mb-6">
                                    <div class="col-lg-6">
                                        <label class="form-label">Nombre del área</label>
                                        <input type="text" name="inventory_areas[{{ $areaIndex }}][name]" class="form-control"
                                            value="{{ $area['name'] ?? '' }}" placeholder="Ej: Cocina">
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">Notas del área</label>
                                        <input type="text" name="inventory_areas[{{ $areaIndex }}][notes]" class="form-control"
                                            value="{{ $area['notes'] ?? '' }}" placeholder="Observaciones">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Fotos generales del área (hasta 3)</label>
                                        <input type="file" name="inventory_areas[{{ $areaIndex }}][photos][]" class="form-control"
                                            accept=".jpg,.jpeg,.png" multiple>
                                    </div>
                                </div>
                                <div class="items-container d-flex flex-column gap-4">
                                    @foreach ($items as $itemIndex => $item)
                                        <div class="row g-4 inventory-item">
                                            <div class="col-lg-4">
                                                <input type="text"
                                                    name="inventory_areas[{{ $areaIndex }}][items][{{ $itemIndex }}][name]"
                                                    class="form-control" value="{{ $item['name'] ?? '' }}"
                                                    placeholder="Elemento (Ej: Parrilla)">
                                            </div>
                                            <div class="col-lg-3">
                                                <input type="text"
                                                    name="inventory_areas[{{ $areaIndex }}][items][{{ $itemIndex }}][condition]"
                                                    class="form-control" value="{{ $item['condition'] ?? '' }}"
                                                    placeholder="Estado (Ej: Bueno)">
                                            </div>
                                            <div class="col-lg-4">
                                                <input type="text"
                                                    name="inventory_areas[{{ $areaIndex }}][items][{{ $itemIndex }}][notes]"
                                                    class="form-control" value="{{ $item['notes'] ?? '' }}" placeholder="Notas">
                                            </div>
                                            <div class="col-lg-1">
                                                <button type="button" class="btn btn-icon btn-light-danger btn-remove-item">
                                                    <i class="ki-outline ki-trash fs-5"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-light-primary border-dashed w-100 mt-5 btn-add-item">
                                    <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar elemento
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-area-btn" class="btn btn-light-primary w-100 mt-6 border-dashed">
                        <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar otra área
                    </button>
                </div>
            </div>

            {{-- STEP 5 --}}
            <div class="card mb-8 wizard-step d-none" data-step-panel="5">
                <div class="card-body p-lg-10">
                    <h3 class="mb-3 fw-bold">Estado inicial de la propiedad</h3>
                    <p class="text-muted mb-8">Selecciona el estado inicial con el que se registrará esta propiedad en el sistema.</p>

                    <div class="d-flex flex-column gap-4 mb-8">
                        @foreach ($statusOptions as $statusValue => $statusLabel)
                            @php
                                $isSelected = $selectedStatus === $statusValue;
                            @endphp
                            <label class="status-option {{ $isSelected ? 'is-selected' : '' }}">
                                <input type="radio" name="status" value="{{ $statusValue }}"
                                    {{ $isSelected ? 'checked' : '' }}>
                                <div>
                                    <div class="fw-bold fs-4 mb-1">{{ $statusLabel }}</div>
                                    <div class="text-gray-700">{{ $statusDescriptions[$statusValue] ?? '' }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('status')
                        <div class="text-danger fs-7 mb-4">{{ $message }}</div>
                    @enderror

                    <div class="notice d-flex bg-light-primary border border-primary border-dashed rounded p-4 mb-6">
                        <span class="text-primary">Nota: Podrás cambiar el estado de la propiedad en cualquier momento desde
                            su expediente.</span>
                    </div>
                    <div class="row g-6">
                        <div class="col-lg-6">
                            <label class="form-label">Inquilino actual (opcional)</label>
                            <input type="text" name="current_tenant_name" class="form-control"
                                value="{{ $fieldValue('current_tenant_name') }}" placeholder="Ej: María González">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Contrato vence (opcional)</label>
                            <input type="date" name="contract_expires_at" class="form-control"
                                value="{{ old('contract_expires_at', $isEdit && $property && $property->contract_expires_at ? $property->contract_expires_at->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <button type="button" id="wizard-prev" class="btn btn-light">
                    <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Anterior
                </button>
                <div class="d-flex gap-3">
                    <button type="button" id="wizard-next" class="btn btn-primary">
                        Guardar y continuar <i class="ki-outline ki-arrow-right fs-4 ms-1"></i>
                    </button>
                    <button type="submit" id="wizard-submit" class="btn btn-success d-none">
                        <i class="ki-outline ki-check fs-4 me-1"></i> {{ $isEdit ? 'Actualizar propiedad' : 'Guardar propiedad' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
<template id="owner-template">
    <div class="owner-block border rounded p-6 mb-6" data-owner-index="__INDEX__">
        <div class="d-flex justify-content-between align-items-center mb-6">
            <h4 class="mb-0">Propietario <span class="owner-number">__NUMBER__</span></h4>
            <button type="button" class="btn btn-sm btn-light-danger btn-remove-owner">Eliminar</button>
        </div>
        <div class="row g-5">
            <div class="col-12">
                <label class="form-label required">Nombre del propietario</label>
                <input type="text" name="owners[__INDEX__][name]" class="form-control" placeholder="Nombre completo">
            </div>
            <div class="col-lg-6">
                <label class="form-label required">Teléfono</label>
                <input type="text" name="owners[__INDEX__][phone]" class="form-control" placeholder="999 123 4567">
            </div>
            <div class="col-lg-6">
                <label class="form-label required">Correo electrónico</label>
                <input type="email" name="owners[__INDEX__][email]" class="form-control" placeholder="correo@ejemplo.com">
            </div>
            <div class="col-12">
                <label class="form-label required d-block">Tipo de titular</label>
                <div class="d-flex gap-6">
                    @foreach ($ownerTypes as $typeValue => $typeLabel)
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="radio" name="owners[__INDEX__][owner_type]"
                                value="{{ $typeValue }}"
                                {{ $typeValue === \App\Models\PropertyOwner::OWNER_INDIVIDUAL ? 'checked' : '' }}>
                            <span class="form-check-label">{{ $typeLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="col-12">
                <h5 class="mb-2">Datos bancarios para recibir pagos</h5>
            </div>
            <div class="col-lg-6">
                <label class="form-label">Banco</label>
                <input type="text" name="owners[__INDEX__][bank_name]" class="form-control" placeholder="Nombre del banco">
            </div>
            <div class="col-lg-6">
                <label class="form-label">CLABE</label>
                <input type="text" name="owners[__INDEX__][clabe]" class="form-control" placeholder="18 dígitos">
            </div>
            <div class="col-12">
                <label class="form-label">Titular de la cuenta</label>
                <input type="text" name="owners[__INDEX__][account_holder]" class="form-control" placeholder="Nombre del titular">
            </div>
            <div class="col-12">
                <label class="form-label">Método de pago permitido</label>
                <select name="owners[__INDEX__][payment_method]" class="form-select">
                    @foreach ($paymentMethods as $methodValue => $methodLabel)
                        <option value="{{ $methodValue }}"
                            {{ $methodValue === \App\Models\PropertyOwner::PAYMENT_METHOD_TRANSFER ? 'selected' : '' }}>
                            {{ $methodLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</template>

<template id="inventory-area-template">
    <div class="border rounded p-6 inventory-area" data-area-index="__AREA_INDEX__" data-next-item-index="1">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h4 class="mb-0">Área __AREA_NUMBER__</h4>
            <button type="button" class="btn btn-sm btn-light-danger btn-remove-area">Eliminar área</button>
        </div>
        <div class="row g-5 mb-6">
            <div class="col-lg-6">
                <label class="form-label">Nombre del área</label>
                <input type="text" name="inventory_areas[__AREA_INDEX__][name]" class="form-control"
                    placeholder="Ej: Cocina">
            </div>
            <div class="col-lg-6">
                <label class="form-label">Notas del área</label>
                <input type="text" name="inventory_areas[__AREA_INDEX__][notes]" class="form-control"
                    placeholder="Observaciones">
            </div>
            <div class="col-12">
                <label class="form-label">Fotos generales del área (hasta 3)</label>
                <input type="file" name="inventory_areas[__AREA_INDEX__][photos][]" class="form-control"
                    accept=".jpg,.jpeg,.png" multiple>
            </div>
        </div>
        <div class="items-container d-flex flex-column gap-4">
            <div class="row g-4 inventory-item">
                <div class="col-lg-4">
                    <input type="text" name="inventory_areas[__AREA_INDEX__][items][0][name]" class="form-control"
                        placeholder="Elemento (Ej: Parrilla)">
                </div>
                <div class="col-lg-3">
                    <input type="text" name="inventory_areas[__AREA_INDEX__][items][0][condition]" class="form-control"
                        placeholder="Estado (Ej: Bueno)">
                </div>
                <div class="col-lg-4">
                    <input type="text" name="inventory_areas[__AREA_INDEX__][items][0][notes]" class="form-control"
                        placeholder="Notas">
                </div>
                <div class="col-lg-1">
                    <button type="button" class="btn btn-icon btn-light-danger btn-remove-item">
                        <i class="ki-outline ki-trash fs-5"></i>
                    </button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-light-primary border-dashed w-100 mt-5 btn-add-item">
            <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar elemento
        </button>
    </div>
</template>

@endsection

@push('scripts')
    <script>
        (() => {
            const totalSteps = 5;
            const stepper = document.getElementById('property-stepper');
            const panels = [...document.querySelectorAll('.wizard-step')];
            const prevBtn = document.getElementById('wizard-prev');
            const nextBtn = document.getElementById('wizard-next');
            const submitBtn = document.getElementById('wizard-submit');
            const stepInput = document.getElementById('wizard-step-input');
            const form = document.getElementById('property-wizard-form');
            let currentStep = parseInt(stepInput.value || '1', 10);

            if (Number.isNaN(currentStep) || currentStep < 1 || currentStep > totalSteps) {
                currentStep = 1;
            }

            const renderWizard = () => {
                const stepNodes = [...stepper.querySelectorAll('.step-item')];
                stepNodes.forEach((node) => {
                    const step = parseInt(node.dataset.step, 10);
                    node.classList.toggle('is-active', step === currentStep);
                    node.classList.toggle('is-completed', step < currentStep);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('d-none', parseInt(panel.dataset.stepPanel, 10) !== currentStep);
                });

                prevBtn.disabled = currentStep === 1;
                nextBtn.classList.toggle('d-none', currentStep === totalSteps);
                submitBtn.classList.toggle('d-none', currentStep !== totalSteps);
                stepInput.value = currentStep.toString();
            };

            prevBtn.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    renderWizard();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentStep < totalSteps) {
                    currentStep++;
                    renderWizard();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            form.addEventListener('submit', () => {
                currentStep = totalSteps;
                stepInput.value = totalSteps.toString();
            });

            const ownersContainer = document.getElementById('owners-container');
            const ownerTemplate = document.getElementById('owner-template').innerHTML;
            const addOwnerBtn = document.getElementById('add-owner-btn');
            let ownerIndex = ownersContainer.querySelectorAll('.owner-block').length;

            const refreshOwners = () => {
                const blocks = ownersContainer.querySelectorAll('.owner-block');
                blocks.forEach((block, index) => {
                    const numberNode = block.querySelector('.owner-number');
                    if (numberNode) {
                        numberNode.textContent = (index + 1).toString();
                    }
                    const removeBtn = block.querySelector('.btn-remove-owner');
                    if (removeBtn) {
                        removeBtn.classList.toggle('d-none', blocks.length === 1);
                    }
                });
            };

            addOwnerBtn.addEventListener('click', () => {
                const html = ownerTemplate
                    .replaceAll('__INDEX__', ownerIndex.toString())
                    .replaceAll('__NUMBER__', (ownerIndex + 1).toString());
                ownersContainer.insertAdjacentHTML('beforeend', html);
                ownerIndex++;
                refreshOwners();
            });

            ownersContainer.addEventListener('click', (event) => {
                const removeButton = event.target.closest('.btn-remove-owner');
                if (!removeButton) {
                    return;
                }

                const block = removeButton.closest('.owner-block');
                if (block && ownersContainer.querySelectorAll('.owner-block').length > 1) {
                    block.remove();
                    refreshOwners();
                }
            });

            const areasContainer = document.getElementById('inventory-areas-container');
            const areaTemplate = document.getElementById('inventory-area-template').innerHTML;
            const addAreaBtn = document.getElementById('add-area-btn');
            let areaIndex = areasContainer.querySelectorAll('.inventory-area').length;

            const refreshAreaButtons = () => {
                const areas = areasContainer.querySelectorAll('.inventory-area');
                areas.forEach((area, index) => {
                    const title = area.querySelector('h4');
                    if (title) {
                        title.textContent = `Área ${index + 1}`;
                    }
                    const removeBtn = area.querySelector('.btn-remove-area');
                    if (removeBtn) {
                        removeBtn.classList.toggle('d-none', areas.length === 1);
                    }
                });
            };

            const itemTemplate = (currentAreaIndex, itemIndex) => `
                <div class="row g-4 inventory-item">
                    <div class="col-lg-4">
                        <input type="text" name="inventory_areas[${currentAreaIndex}][items][${itemIndex}][name]" class="form-control" placeholder="Elemento (Ej: Parrilla)">
                    </div>
                    <div class="col-lg-3">
                        <input type="text" name="inventory_areas[${currentAreaIndex}][items][${itemIndex}][condition]" class="form-control" placeholder="Estado (Ej: Bueno)">
                    </div>
                    <div class="col-lg-4">
                        <input type="text" name="inventory_areas[${currentAreaIndex}][items][${itemIndex}][notes]" class="form-control" placeholder="Notas">
                    </div>
                    <div class="col-lg-1">
                        <button type="button" class="btn btn-icon btn-light-danger btn-remove-item">
                            <i class="ki-outline ki-trash fs-5"></i>
                        </button>
                    </div>
                </div>
            `;

            addAreaBtn.addEventListener('click', () => {
                const html = areaTemplate
                    .replaceAll('__AREA_INDEX__', areaIndex.toString())
                    .replaceAll('__AREA_NUMBER__', (areaIndex + 1).toString());
                areasContainer.insertAdjacentHTML('beforeend', html);
                areaIndex++;
                refreshAreaButtons();
            });

            areasContainer.addEventListener('click', (event) => {
                const addItemBtn = event.target.closest('.btn-add-item');
                if (addItemBtn) {
                    const area = addItemBtn.closest('.inventory-area');
                    const itemsContainer = area.querySelector('.items-container');
                    const currentAreaIndex = area.dataset.areaIndex;
                    const nextItemIndex = parseInt(area.dataset.nextItemIndex || '0', 10);
                    itemsContainer.insertAdjacentHTML('beforeend', itemTemplate(currentAreaIndex, nextItemIndex));
                    area.dataset.nextItemIndex = (nextItemIndex + 1).toString();
                    return;
                }

                const removeAreaBtn = event.target.closest('.btn-remove-area');
                if (removeAreaBtn) {
                    const area = removeAreaBtn.closest('.inventory-area');
                    if (area && areasContainer.querySelectorAll('.inventory-area').length > 1) {
                        area.remove();
                        refreshAreaButtons();
                    }
                    return;
                }

                const removeItemBtn = event.target.closest('.btn-remove-item');
                if (removeItemBtn) {
                    const item = removeItemBtn.closest('.inventory-item');
                    const area = removeItemBtn.closest('.inventory-area');
                    if (item && area && area.querySelectorAll('.inventory-item').length > 1) {
                        item.remove();
                    }
                }
            });

            document.querySelectorAll('input[name="status"]').forEach((radio) => {
                radio.addEventListener('change', () => {
                    document.querySelectorAll('.status-option').forEach((node) => node.classList.remove('is-selected'));
                    radio.closest('.status-option')?.classList.add('is-selected');
                });
            });

            refreshOwners();
            refreshAreaButtons();
            renderWizard();
        })();
    </script>
@endpush
