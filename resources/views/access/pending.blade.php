@extends('layouts.app')

@section('title', 'Acceso pendiente | SuWork')

@section('content')
    <div class="py-10">
        <div class="card">
            <div class="card-body p-10">
                <h2 class="fw-bold mb-3">Acceso pendiente de autorización</h2>
                <p class="text-muted mb-6">
                    Tu cuenta no tiene permisos de módulos asignados. Un administrador debe habilitar al menos un permiso
                    (por ejemplo: ver almacén, ver cobranza o ver expedientes).
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('profile.index') }}" class="btn btn-light-primary">Ir a mi perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-light-danger">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

