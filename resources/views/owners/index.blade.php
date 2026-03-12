@extends('layouts.app')

@section('title', 'Propietarios | SuWork')

@section('content')
    <div class="py-10 property-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Propietarios</h1>
                <div class="text-muted fs-6">{{ $owners->total() }} propietarios registrados</div>
            </div>
            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createOwnerModal">
                <i class="ki-outline ki-plus fs-4 me-1"></i> Nuevo propietario
            </button>
        </div>

        <div class="card mb-8">
            <div class="card-body py-6">
                <form method="GET" action="{{ route('owners.index') }}" class="d-flex gap-3">
                    <input type="text" name="q" class="form-control"
                        placeholder="Buscar por nombre, telefono, email o RFC..." value="{{ $search }}">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="{{ route('owners.index') }}" class="btn btn-light">Limpiar</a>
                </form>
            </div>
        </div>

        <div class="row g-6">
            @forelse ($owners as $owner)
                <div class="col-lg-6">
                    <div class="card owner-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="d-flex align-items-center gap-4">
                                    <div class="owner-initial">{{ strtoupper(mb_substr($owner->name, 0, 1)) }}</div>
                                    <div>
                                        <div class="fw-bold fs-3 mb-1">{{ $owner->name }}</div>
                                        <div class="text-muted fs-7">RFC: {{ $owner->rfc ?: '-' }}</div>
                                    </div>
                                </div>
                                <a href="{{ route('owners.edit', $owner) }}" class="btn btn-sm btn-light-primary">Editar</a>
                            </div>

                            <div class="text-gray-700 mb-1">{{ $owner->phone }}</div>
                            <div class="text-gray-700 mb-3">{{ $owner->email ?: '-' }}</div>
                            <div class="separator my-3"></div>
                            <div class="text-gray-700">Banco: <span class="fw-semibold">{{ $owner->bank_name ?: '-' }}</span></div>
                            <div class="text-gray-700">CLABE: <span class="fw-semibold">{{ $owner->clabe ?: '-' }}</span></div>
                            <div class="text-muted fs-8 mt-3">{{ $owner->properties_count }} propiedades asociadas</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light-info mb-0">No hay propietarios registrados.</div>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $owners->links() }}
        </div>
    </div>

    <div class="modal fade" id="createOwnerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('owners.store') }}" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="modal-header">
                        <h3 class="modal-title">Nuevo propietario</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        @include('owners.partials.form-fields')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($errors->any())
        <script>
            (() => {
                const createOwnerModal = document.getElementById('createOwnerModal');
                if (!createOwnerModal) {
                    return;
                }
                const modal = new bootstrap.Modal(createOwnerModal);
                modal.show();
            })();
        </script>
    @endif
@endpush
