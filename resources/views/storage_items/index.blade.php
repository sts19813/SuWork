@extends('layouts.app')

@section('content')
    <div class="container-fluid py-5">

        {{-- Header --}}
        <div class="card border-0 shadow-sm mb-6">
            <div class="card-body py-5 px-5">

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-70px me-5">
                            <div class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-package fs-1 text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>

                        <div>
                            <h1 class="fw-bold text-dark mb-1">
                                Gestión de Almacén
                            </h1>

                            <div class="text-muted fw-semibold fs-6">
                                Administración y control de items registrados.
                            </div>
                        </div>

                    </div>

                    <div class="d-flex gap-3 mt-5 mt-md-0">

                        <a href="{{ route('storage_items.trashed') }}" class="btn btn-light-warning fw-bold">

                            <i class="ki-duotone ki-trash fs-4 me-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>

                            Ver eliminados
                        </a>

                        <a href="{{ route('storage_items.create') }}" class="btn btn-primary fw-bold">

                            <i class="ki-duotone ki-plus fs-4 me-1"></i>

                            Nuevo item
                        </a>

                    </div>

                </div>

            </div>
        </div>

        {{-- Success --}}
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-6">
                <i class="ki-duotone ki-check-circle fs-2hx text-success me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>

                <div class="fw-semibold fs-6">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        {{-- Empty --}}
        @if($items->isEmpty())

            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-20">

                    <div class="mb-5">
                        <i class="ki-duotone ki-package fs-5x text-gray-300">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </div>

                    <h3 class="text-dark fw-bold mb-2">
                        No hay items registrados
                    </h3>

                    <div class="text-muted fs-6 mb-7">
                        Comienza agregando productos al almacén.
                    </div>

                    <a href="{{ route('storage_items.create') }}" class="btn btn-primary fw-bold">
                        Agregar Item
                    </a>

                </div>
            </div>

        @else

            <div class="row g-5">

                @foreach($items as $item)

                    <div class="col-xl-4 col-lg-6">

                        <div class="card border-0 shadow-sm h-100 overflow-hidden">

                            {{-- Imagen --}}
                            <div class="position-relative">

                                @if($item->photo)

                                    <img src="{{ asset('storage/' . $item->photo) }}" alt="{{ $item->name }}" class="w-100"
                                        style="height: 240px; object-fit: cover;">

                                @else

                                    <div class="bg-light-primary d-flex align-items-center justify-content-center"
                                        style="height: 240px;">

                                        <i class="ki-duotone ki-picture fs-5x text-primary opacity-50">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>

                                    </div>

                                @endif

                                {{-- Quantity --}}
                                <div class="position-absolute top-0 end-0 m-4">

                                    <span class="badge badge-primary fs-7 fw-bold px-4 py-3">
                                        {{ $item->quantity }} unidades
                                    </span>

                                </div>

                            </div>

                            {{-- Body --}}
                            <div class="card-body d-flex flex-column p-5">

                                <div class="mb-4">

                                    <div class="text-muted fs-7 fw-semibold text-uppercase mb-1">
                                        {{ $item->product_type }}
                                    </div>

                                    <h3 class="fw-bold text-dark mb-0">
                                        {{ $item->name }}
                                    </h3>

                                </div>

                                @if($item->brand)

                                    <div class="d-flex align-items-center mb-4">

                                        <span class="bullet bullet-dot bg-primary me-2"></span>

                                        <span class="text-gray-700 fs-6">
                                            <span class="fw-bold">Marca:</span>
                                            {{ $item->brand }}
                                        </span>

                                    </div>

                                @endif

                                {{-- Estado --}}
                                <div class="mb-5">

                                    @if($item->condition === 'bueno')

                                        <span class="badge badge-light-success fw-bold px-4 py-3">
                                            Bueno
                                        </span>

                                    @elseif($item->condition === 'regular')

                                        <span class="badge badge-light-warning fw-bold px-4 py-3">
                                            Regular
                                        </span>

                                    @else

                                        <span class="badge badge-light-danger fw-bold px-4 py-3">
                                            Malo
                                        </span>

                                    @endif

                                </div>

                                {{-- Description --}}
                                @if($item->description)

                                    <div class="text-muted fs-6 mb-6 flex-grow-1">
                                        {{ Str::limit($item->description, 90) }}
                                    </div>

                                @else

                                    <div class="flex-grow-1"></div>

                                @endif

                                {{-- Actions --}}
                                <div class="d-flex gap-2 mt-auto">

                                    <a href="{{ route('storage_items.show', $item) }}" class="btn btn-light-dark flex-fill">
                                        <i class="ki-duotone ki-eye fs-5 me-1"></i>
                                        Ver
                                    </a>

                                    <a href="{{ route('storage_items.edit', $item) }}" class="btn btn-light-primary flex-fill">
                                        <i class="ki-duotone ki-pencil fs-5 me-1"></i>
                                        Editar
                                    </a>

                                    {{-- NUEVO BOTON --}}
                                    <button type="button" class="btn btn-light-info" data-bs-toggle="modal"
                                        data-bs-target="#noteModal{{ $item->id }}">

                                        <i class="ki-duotone ki-note-2 fs-5">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>

                                    </button>

                                    <button type="button" class="btn btn-light-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteModal{{ $item->id }}">

                                        <i class="ki-duotone ki-trash fs-5">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>

                                    </button>

                                </div>

                            </div>

                        </div>

                        {{-- Delete Modal --}}
                        <div class="modal fade" id="deleteModal{{ $item->id }}" tabindex="-1">

                            <div class="modal-dialog modal-dialog-centered">

                                <div class="modal-content border-0 shadow">

                                    <div class="modal-header border-0 pb-0">

                                        <div>
                                            <h2 class="fw-bold text-dark mb-1">
                                                Eliminar Item
                                            </h2>

                                            <div class="text-muted fs-7">
                                                Esta acción moverá el item a eliminados.
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">

                                            <i class="ki-duotone ki-cross fs-2"></i>

                                        </button>

                                    </div>

                                    <form action="{{ route('storage_items.deleteWithNote', $item) }}" method="POST">

                                        @csrf

                                        <div class="modal-body py-5">

                                            <div class="alert alert-light-danger d-flex align-items-center p-4 mb-5">

                                                <i class="ki-duotone ki-information-5 fs-2 text-danger me-3">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                </i>

                                                <div class="fw-semibold">
                                                    ¿Deseas eliminar
                                                    <strong>{{ $item->name }}</strong>?
                                                </div>

                                            </div>

                                            <div class="mb-3">

                                                <label class="form-label required fw-semibold">
                                                    Razón o nota
                                                </label>

                                                <textarea class="form-control form-control-solid" name="delete_note" rows="4"
                                                    required
                                                    placeholder="Ej: Producto dañado, movido de almacén, reemplazado, etc."></textarea>

                                            </div>

                                        </div>

                                        <div class="modal-footer border-0 pt-0">

                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">

                                                Cancelar
                                            </button>

                                            <button type="submit" class="btn btn-danger fw-bold">

                                                <i class="ki-duotone ki-trash fs-5 me-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                </i>

                                                Eliminar
                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-10">
                {{ $items->links(data: ['view' => 'pagination::bootstrap-5']) }}
            </div>

        @endif

    </div>

    {{-- Modal Nota / Bitácora --}}
    <div class="modal fade" id="noteModal{{ $item->id }}" tabindex="-1">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content border-0 shadow">

                <div class="modal-header border-0 pb-0">

                    <div>

                        <h2 class="fw-bold text-dark mb-1">
                            Agregar Nota
                        </h2>

                        <div class="text-muted fs-7">
                            Registrar movimiento o comentario del item.
                        </div>

                    </div>

                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">

                        <i class="ki-duotone ki-cross fs-2"></i>

                    </button>

                </div>

                <form action="{{ route('storage_items.addNote', $item) }}" method="POST">

                    @csrf

                    <div class="modal-body py-5 px-6">

                        <div class="mb-5">

                            <div class="fw-bold text-dark fs-5 mb-2">
                                {{ $item->name }}
                            </div>

                            <div class="text-muted fs-7">
                                {{ $item->product_type }}
                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label required fw-semibold">
                                Nota / Movimiento
                            </label>

                            <textarea name="note" rows="4" class="form-control form-control-solid" required
                                placeholder="Ej: Revisado, movido de almacén, reparado, entregado, etc."></textarea>

                        </div>

                    </div>

                    <div class="modal-footer border-0 pt-0">

                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">

                            Cancelar

                        </button>

                        <button type="submit" class="btn btn-info fw-bold">

                            <i class="ki-duotone ki-note-2 fs-4 me-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>

                            Guardar Nota

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
@endsection