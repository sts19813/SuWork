@extends('layouts.app')

@section('title', 'Dashboard | SuWork')

@section('content')
    <div class="py-10">
        <div class="card">
            <div class="card-body p-10">
                <h2 class="fw-bold mb-3">Sistema de administración</h2>
                <p class="text-muted mb-6">Administra tus propiedades, expediente documental e inventario inicial.</p>
                <a href="{{ route('properties.index') }}" class="btn btn-primary">
                    Ir al módulo de propiedades
                </a>
            </div>
        </div>
    </div>

@endsection
@push('scripts')

@endpush
