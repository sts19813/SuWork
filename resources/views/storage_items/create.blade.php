@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">

            {{-- Header --}}
            <div class="card mb-5 border-0 shadow-sm">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between py-5 px-5">
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="symbol symbol-60px me-4">
                                <div class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-package fs-1 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>

                            <div>
                                <h1 class="fw-bold text-dark mb-1">
                                    Nuevo Item de Almacén
                                </h1>

                                <div class="text-muted fw-semibold fs-6">
                                    Registra productos, herramientas o materiales del inventario.
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('storage_items.index') }}"
                       class="btn btn-light-primary fw-bold mt-4 mt-md-0">
                        <i class="ki-duotone ki-arrow-left fs-4 me-1"></i>
                        Volver
                    </a>
                </div>
            </div>

            {{-- Errors --}}
            @if($errors->any())
                <div class="alert alert-danger d-flex align-items-start p-5 mb-5">
                    <i class="ki-duotone ki-information-5 fs-2hx text-danger me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>

                    <div class="d-flex flex-column">
                        <h4 class="mb-2 text-danger fw-bold">
                            Se encontraron errores
                        </h4>

                        <ul class="mb-0 ps-5">
                            @foreach($errors->all() as $error)
                                <li class="mb-1">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('storage_items.store') }}"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf

                <div class="card border-0 shadow-sm">

                    <div class="card-body p-5">

                        {{-- Información General --}}
                        <div class="mb-10">
                            <h3 class="fw-bold text-dark mb-1">
                                Información General
                            </h3>

                            <div class="text-muted fs-7">
                                Datos principales del producto.
                            </div>
                        </div>

                        <div class="row g-5 mb-8">

                            <div class="col-md-6">
                                <label class="form-label required fw-semibold fs-6">
                                    Tipo de producto
                                </label>

                                <input type="text"
                                       name="product_type"
                                       class="form-control form-control-solid @error('product_type') is-invalid @enderror"
                                       placeholder="Ej: Herramienta, Electrónico..."
                                       value="{{ old('product_type') }}"
                                       required>

                                @error('product_type')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required fw-semibold fs-6">
                                    Nombre
                                </label>

                                <input type="text"
                                       name="name"
                                       class="form-control form-control-solid @error('name') is-invalid @enderror"
                                       placeholder="Nombre del producto"
                                       value="{{ old('name') }}"
                                       required>

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-6">
                                    Marca
                                </label>

                                <input type="text"
                                       name="brand"
                                       class="form-control form-control-solid"
                                       placeholder="Marca del producto"
                                       value="{{ old('brand') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label required fw-semibold fs-6">
                                    Cantidad
                                </label>

                                <input type="number"
                                       name="quantity"
                                       min="1"
                                       class="form-control form-control-solid @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity', 1) }}"
                                       required>

                                @error('quantity')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold fs-6">
                                    Estado
                                </label>

                                <select name="condition"
                                        class="form-select form-select-solid @error('condition') is-invalid @enderror"
                                        data-control="select2"
                                        data-hide-search="true">

                                    <option value="">Selecciona estado</option>

                                    <option value="bueno"
                                        {{ old('condition') === 'bueno' ? 'selected' : '' }}>
                                        Bueno
                                    </option>

                                    <option value="regular"
                                        {{ old('condition') === 'regular' ? 'selected' : '' }}>
                                        Regular
                                    </option>

                                    <option value="malo"
                                        {{ old('condition') === 'malo' ? 'selected' : '' }}>
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

                        {{-- Imagen --}}
                        <div class="separator separator-dashed my-10"></div>

                        <div class="mb-10">
                            <h3 class="fw-bold text-dark mb-1">
                                Imagen del Producto
                            </h3>

                            <div class="text-muted fs-7">
                                Sube una fotografía del artículo.
                            </div>
                        </div>

                        <div class="mb-8">

                            <label class="form-label fw-semibold fs-6">
                                Foto
                            </label>

                            <input type="file"
                                   name="photo"
                                   accept="image/*"
                                   class="form-control form-control-solid @error('photo') is-invalid @enderror">

                            <div class="text-muted fs-7 mt-2">
                                Formatos permitidos: JPG, PNG, WEBP. Máximo 5MB.
                            </div>

                            @error('photo')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Descripción --}}
                        <div class="separator separator-dashed my-10"></div>

                        <div class="mb-10">
                            <h3 class="fw-bold text-dark mb-1">
                                Descripción
                            </h3>

                            <div class="text-muted fs-7">
                                Información adicional del item.
                            </div>
                        </div>

                        <div class="mb-5">
                            <textarea name="description"
                                      rows="5"
                                      class="form-control form-control-solid"
                                      placeholder="Agrega detalles importantes del producto...">{{ old('description') }}</textarea>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="card-footer border-0 d-flex justify-content-end gap-3 py-5 px-5">

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

                            Guardar Item
                        </button>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection