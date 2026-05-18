@extends('layouts.app')

@section('title', 'Almacén | SuWork')

@section('content')
    <div class="container-fluid py-6">

        @if (session('success'))
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

        <div class="card border-0 shadow-sm">

            {{-- HEADER --}}
            <div class="card-header border-0 pt-7 pb-4">

                <div class="d-flex flex-column flex-lg-row justify-content-between w-100 gap-5">

                    <div class="d-flex align-items-center gap-4">
                        <div class="symbol symbol-70px">
                            <div class="bg-light-primary">
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
                                Administración y control de inventario
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">

                        <a href="{{ route('storage_items.trashed') }}" class="btn btn-light-warning fw-bold">
                            <i class="ki-duotone ki-trash fs-5 me-1"></i>
                            Eliminados
                        </a>

                        <a href="{{ route('storage_items.create') }}" class="btn btn-primary fw-bold">
                            <i class="ki-duotone ki-plus fs-5 me-1"></i>
                            Nuevo Item
                        </a>

                    </div>

                </div>

            </div>

            {{-- FILTROS --}}
            <div class="card-body pt-0">

                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-8">

                    <form method="GET" action="{{ route('storage_items.index') }}"
                        class="d-flex flex-wrap gap-3 align-items-center">

                        <input type="hidden" name="view" value="{{ $viewMode }}">

                        <div class="position-relative">
                            <i
                                class="ki-duotone ki-magnifier fs-3 text-gray-500 position-absolute top-50 translate-middle-y ms-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>

                            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-solid ps-12"
                                style="min-width: 320px;" placeholder="Buscar item...">
                        </div>

                        <button class="btn btn-primary fw-bold">
                            Buscar
                        </button>

                        <a href="{{ route('storage_items.index', ['view' => $viewMode]) }}" class="btn btn-light">
                            Limpiar
                        </a>

                    </form>

                    <div class="btn-group">

                        <a href="{{ route('storage_items.index', ['q' => $search, 'view' => 'grid']) }}" class="btn {{ $viewMode === 'grid'
        ? 'btn-primary'
        : 'btn-light-primary' }}">

                            <i class="ki-duotone ki-category fs-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>

                        </a>

                        <a href="{{ route('storage_items.index', ['q' => $search, 'view' => 'table']) }}" class="btn {{ $viewMode === 'table'
        ? 'btn-primary'
        : 'btn-light-primary' }}">

                            <i class="ki-duotone ki-row-horizontal fs-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>

                        </a>


                    </div>

                </div>

                {{-- EMPTY --}}
                @if ($items->isEmpty())

                    <div class="text-center py-20">

                        <div class="mb-5">
                            <i class="ki-duotone ki-package fs-5tx text-gray-300">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </div>

                        <h3 class="fw-bold text-dark mb-3">
                            No hay items registrados
                        </h3>

                        <div class="text-muted fs-6 mb-7">
                            Comienza agregando productos al almacén
                        </div>

                        <a href="{{ route('storage_items.create') }}" class="btn btn-primary fw-bold">
                            Agregar Item
                        </a>

                    </div>

                    {{-- TABLE --}}
                @elseif ($viewMode === 'table')

                    <div class="table-responsive">

                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="storageItemsTable">

                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Item</th>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th>Cantidad</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>

                            <tbody class="fw-semibold text-gray-700">

                                @foreach ($items as $item)

                                    <tr>

                                        <td>
                                            <div class="d-flex align-items-center gap-4">

                                                <div class="symbol symbol-55px">

                                                    @if ($item->photo)

                                                        <img src="{{ asset('storage/' . $item->photo) }}"
                                                            class="object-fit-cover rounded" alt="{{ $item->name }}">

                                                    @else

                                                        <div class="symbol-label bg-light-primary">
                                                            <i class="ki-duotone ki-picture fs-2x text-primary">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </div>

                                                    @endif

                                                </div>

                                                <div>
                                                    <div class="fw-bold text-dark">
                                                        {{ $item->name }}
                                                    </div>

                                                    <div class="text-muted fs-7">
                                                        {{ Str::limit($item->description, 70) }}
                                                    </div>
                                                </div>

                                            </div>
                                        </td>

                                        <td>{{ $item->product_type }}</td>

                                        <td>{{ $item->brand ?: '-' }}</td>

                                        <td>
                                            <span class="badge badge-light-primary fs-7 fw-bold">
                                                {{ $item->quantity }}
                                            </span>
                                        </td>

                                        <td>

                                            @if ($item->condition === 'bueno')
                                                <span class="badge badge-light-success">
                                                    Bueno
                                                </span>
                                            @elseif($item->condition === 'regular')
                                                <span class="badge badge-light-warning">
                                                    Regular
                                                </span>
                                            @else
                                                <span class="badge badge-light-danger">
                                                    Malo
                                                </span>
                                            @endif

                                        </td>

                                        <td class="text-end">

                                            <div class="d-flex justify-content-end gap-2 flex-wrap">

                                                <a href="{{ route('storage_items.show', $item) }}"
                                                    class="btn btn-sm btn-light-dark">
                                                    Ver
                                                </a>

                                                <a href="{{ route('storage_items.edit', $item) }}"
                                                    class="btn btn-sm btn-light-primary">
                                                    Editar
                                                </a>

                                                <button type="button" class="btn btn-sm btn-light-info" data-bs-toggle="modal"
                                                    data-bs-target="#noteModal{{ $item->id }}">
                                                    Nota
                                                </button>

                                                <button type="button" class="btn btn-sm btn-light-danger" data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal{{ $item->id }}">
                                                    Eliminar
                                                </button>

                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                    {{-- GRID --}}
                @else

                    <div class="row g-6">

                        @foreach ($items as $item)

                            <div class="col-xl-3 col-lg-4 col-md-6">

                                <div class="card border border-gray-200 shadow-sm h-100">

                                    <div class="position-relative">

                                        @if ($item->photo)

                                            <img src="{{ asset('storage/' . $item->photo) }}" class="w-100 rounded-top"
                                                style="height: 220px; object-fit: cover;" alt="{{ $item->name }}">

                                        @else

                                            <div class="bg-light-primary d-flex align-items-center justify-content-center rounded-top"
                                                style="height: 220px;">

                                                <i class="ki-duotone ki-picture fs-5x text-primary opacity-50">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>

                                            </div>

                                        @endif

                                        <div class="position-absolute top-0 end-0 m-4">

                                            <span class="badge badge-primary fw-bold px-4 py-3">
                                                {{ $item->quantity }}
                                            </span>

                                        </div>

                                    </div>

                                    <div class="card-body d-flex flex-column">

                                        <div class="mb-4">

                                            <div class="text-muted text-uppercase fw-bold fs-8 mb-1">
                                                {{ $item->product_type }}
                                            </div>

                                            <h3 class="fw-bold text-dark mb-0 fs-4">
                                                {{ $item->name }}
                                            </h3>

                                        </div>

                                        @if ($item->brand)
                                            <div class="text-gray-600 fs-7 mb-4">
                                                Marca:
                                                <span class="fw-bold">
                                                    {{ $item->brand }}
                                                </span>
                                            </div>
                                        @endif

                                        <div class="mb-5">

                                            @if ($item->condition === 'bueno')
                                                <span class="badge badge-light-success">
                                                    Bueno
                                                </span>
                                            @elseif($item->condition === 'regular')
                                                <span class="badge badge-light-warning">
                                                    Regular
                                                </span>
                                            @else
                                                <span class="badge badge-light-danger">
                                                    Malo
                                                </span>
                                            @endif

                                        </div>

                                        <div class="text-muted fs-7 mb-6 flex-grow-1">
                                            {{ Str::limit($item->description, 90) }}
                                        </div>

                                        <div class="d-grid gap-2">

                                            <a href="{{ route('storage_items.show', $item) }}" class="btn btn-light-dark">
                                                Ver
                                            </a>

                                            <a href="{{ route('storage_items.edit', $item) }}" class="btn btn-light-primary">
                                                Editar
                                            </a>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                    <div class="d-flex justify-content-center mt-10">
                        {{ $items->links(data: ['view' => 'pagination::bootstrap-5']) }}
                    </div>

                @endif

            </div>

        </div>

        {{-- MODALS --}}
        @foreach ($items as $item)

            {{-- DELETE --}}
            <div class="modal fade" id="deleteModal{{ $item->id }}" tabindex="-1">

                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content border-0 shadow">

                        <div class="modal-header border-0">
                            <h2 class="fw-bold">
                                Eliminar Item
                            </h2>

                            <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">

                                <i class="ki-duotone ki-cross fs-2"></i>

                            </button>
                        </div>

                        <form action="{{ route('storage_items.deleteWithNote', $item) }}" method="POST">

                            @csrf

                            <div class="modal-body">

                                <div class="alert alert-light-danger mb-5">
                                    ¿Deseas eliminar
                                    <strong>{{ $item->name }}</strong>?
                                </div>

                                <textarea name="delete_note" rows="4" required class="form-control form-control-solid"
                                    placeholder="Razón o nota..."></textarea>

                            </div>

                            <div class="modal-footer border-0">

                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    Cancelar
                                </button>

                                <button type="submit" class="btn btn-danger fw-bold">
                                    Eliminar
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

            {{-- NOTE --}}
            <div class="modal fade" id="noteModal{{ $item->id }}" tabindex="-1">

                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content border-0 shadow">

                        <div class="modal-header border-0">

                            <h2 class="fw-bold">
                                Agregar Nota
                            </h2>

                            <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">

                                <i class="ki-duotone ki-cross fs-2"></i>

                            </button>

                        </div>

                        <form action="{{ route('storage_items.addNote', $item) }}" method="POST">

                            @csrf

                            <div class="modal-body">

                                <div class="mb-5">
                                    <div class="fw-bold fs-4">
                                        {{ $item->name }}
                                    </div>

                                    <div class="text-muted fs-7">
                                        {{ $item->product_type }}
                                    </div>
                                </div>

                                <textarea name="note" rows="4" required class="form-control form-control-solid"
                                    placeholder="Escribe una nota..."></textarea>

                            </div>

                            <div class="modal-footer border-0">

                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    Cancelar
                                </button>

                                <button type="submit" class="btn btn-info fw-bold">
                                    Guardar Nota
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        @endforeach

    </div>
@endsection