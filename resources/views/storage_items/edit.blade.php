@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">

    <div class="row justify-content-center">

        <div class="col-xl-9 col-lg-10">

            {{-- Header --}}
            <div class="card border-0 shadow-sm mb-6">

                <div class="card-body py-5 px-5">

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                        <div class="d-flex align-items-center mb-5 mb-md-0">

                            <div class="symbol symbol-70px me-5">
                                <div class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-pencil fs-1 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>

                            <div>

                                <div class="text-muted fw-semibold fs-7 text-uppercase">
                                    {{ $item->product_type }}
                                </div>

                                <h1 class="fw-bold text-dark mb-1">
                                    Editar Item
                                </h1>

                                <div class="text-muted fs-6">
                                    Modifica la información del producto almacenado.
                                </div>

                            </div>

                        </div>

                        <a href="{{ route('storage_items.index') }}"
                           class="btn btn-light-primary fw-bold">

                            <i class="ki-duotone ki-arrow-left fs-4 me-1"></i>
                            Volver

                        </a>

                    </div>

                </div>

            </div>

            {{-- Errors --}}
            @if($errors->any())

                <div class="alert alert-danger d-flex align-items-start p-5 mb-6">

                    <i class="ki-duotone ki-information-5 fs-2hx text-danger me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>

                    <div class="flex-grow-1">

                        <h4 class="fw-bold text-danger mb-3">
                            Se encontraron errores
                        </h4>

                        <ul class="mb-0 ps-5">

                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach

                        </ul>

                    </div>

                </div>

            @endif

            {{-- Form --}}
            <form action="{{ route('storage_items.update', $item) }}"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <div class="card border-0 shadow-sm mb-6">

                    <div class="card-body p-6">

                        {{-- Section --}}
                        <div class="mb-10">

                            <h3 class="fw-bold text-dark mb-1">
                                Información General
                            </h3>

                            <div class="text-muted fs-7">
                                Actualiza los datos principales del item.
                            </div>

                        </div>

                        <div class="row g-5 mb-8">

                            {{-- Product Type --}}
                            <div class="col-md-6">

                                <label class="form-label required fw-semibold fs-6">
                                    Tipo de producto
                                </label>

                                <input type="text"
                                       name="product_type"
                                       class="form-control form-control-solid @error('product_type') is-invalid @enderror"
                                       placeholder="Ej: Herramienta, Electrónico..."
                                       value="{{ old('product_type', $item->product_type) }}"
                                       required>

                                @error('product_type')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            {{-- Name --}}
                            <div class="col-md-6">

                                <label class="form-label required fw-semibold fs-6">
                                    Nombre
                                </label>

                                <input type="text"
                                       name="name"
                                       class="form-control form-control-solid @error('name') is-invalid @enderror"
                                       placeholder="Nombre del producto"
                                       value="{{ old('name', $item->name) }}"
                                       required>

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            {{-- Brand --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold fs-6">
                                    Marca
                                </label>

                                <input type="text"
                                       name="brand"
                                       class="form-control form-control-solid"
                                       placeholder="Marca del producto"
                                       value="{{ old('brand', $item->brand) }}">

                            </div>

                            {{-- Quantity --}}
                            <div class="col-md-3">

                                <label class="form-label required fw-semibold fs-6">
                                    Cantidad
                                </label>

                                <input type="number"
                                       name="quantity"
                                       min="1"
                                       class="form-control form-control-solid @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity', $item->quantity) }}"
                                       required>

                                @error('quantity')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            {{-- Condition --}}
                            <div class="col-md-3">

                                <label class="form-label fw-semibold fs-6">
                                    Estado
                                </label>

                                <select name="condition"
                                        class="form-select form-select-solid @error('condition') is-invalid @enderror"
                                        data-control="select2"
                                        data-hide-search="true">

                                    <option value="bueno"
                                        {{ old('condition', $item->condition) === 'bueno' ? 'selected' : '' }}>
                                        Bueno
                                    </option>

                                    <option value="regular"
                                        {{ old('condition', $item->condition) === 'regular' ? 'selected' : '' }}>
                                        Regular
                                    </option>

                                    <option value="malo"
                                        {{ old('condition', $item->condition) === 'malo' ? 'selected' : '' }}>
                                        Malo
                                    </option>

                                </select>

                                @error('condition')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                        {{-- Photo --}}
                        <div class="separator separator-dashed my-10"></div>

                        <div class="mb-10">

                            <h3 class="fw-bold text-dark mb-1">
                                Imagen del Producto
                            </h3>

                            <div class="text-muted fs-7">
                                Puedes reemplazar la fotografía actual.
                            </div>

                        </div>

                        @if($item->photo)

                            <div class="mb-5">

                                <img src="{{ asset('storage/' . $item->photo) }}"
                                     alt="{{ $item->name }}"
                                     class="rounded shadow-sm"
                                     style="width: 220px; height: 220px; object-fit: cover;">

                            </div>

                        @endif

                        <div class="mb-8">

                            <label class="form-label fw-semibold fs-6">
                                Nueva Foto
                            </label>

                            <input type="file"
                                   name="photo"
                                   accept="image/*"
                                   class="form-control form-control-solid @error('photo') is-invalid @enderror">

                            <div class="text-muted fs-7 mt-2">
                                Deja este campo vacío para conservar la imagen actual.
                            </div>

                            @error('photo')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                        {{-- Description --}}
                        <div class="separator separator-dashed my-10"></div>

                        <div class="mb-10">

                            <h3 class="fw-bold text-dark mb-1">
                                Descripción
                            </h3>

                            <div class="text-muted fs-7">
                                Información adicional del producto.
                            </div>

                        </div>

                        <div class="mb-5">

                            <textarea name="description"
                                      rows="5"
                                      class="form-control form-control-solid"
                                      placeholder="Agrega detalles importantes del item...">{{ old('description', $item->description) }}</textarea>

                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="card-footer border-0 d-flex justify-content-end gap-3 py-5 px-6">

                        <a href="{{ route('storage_items.index') }}"
                           class="btn btn-light">

                            Cancelar

                        </a>

                        <button type="submit"
                                class="btn btn-primary fw-bold">

                            <i class="ki-duotone ki-check fs-4 me-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>

                            Actualizar Item

                        </button>

                    </div>

                </div>

            </form>
        </div>

    </div>

</div>
@endsection