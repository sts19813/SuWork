@extends('layouts.app')

@section('title', 'Editar Inquilino | SuWork')

@section('content')
    <div class="py-10 property-module">
        <div class="mb-8">
            <a href="{{ route('tenants.index') }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a inquilinos
            </a>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Editar inquilino</h3>
            </div>
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('tenants.update', $tenant) }}">
                    @csrf
                    @method('PUT')
                    @include('tenants.partials.form-fields', ['tenant' => $tenant])

                    <div class="d-flex justify-content-end gap-3 mt-8">
                        <a href="{{ route('tenants.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

