@extends('layouts.app')

@section('title', 'Usuarios y Permisos | SuWork')

@section('content')
    <div class="py-10 property-module">
        @if (session('success'))
            <div class="alert alert-success mb-6">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mb-6">
                <div class="fw-bold mb-2">Hay errores en el formulario:</div>
                <ul class="mb-0 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-6">
            <div>
                <h1 class="mb-1 fw-bold">Usuarios, roles y permisos</h1>
                <div class="text-muted">Módulo completo de control de acceso.</div>
            </div>
        </div>

        <div class="card mb-6">
            <div class="card-body">
                <form class="row g-4 align-items-end" method="GET" action="{{ route('access.index') }}">
                    <div class="col-md-7">
                        <label class="form-label">Buscar usuario</label>
                        <input type="text" class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="Nombre o correo">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" name="role">
                            <option value="">Todos</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" {{ $filters['role'] === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Filtrar</button>
                        <a href="{{ route('access.index') }}" class="btn btn-light flex-fill">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Alta de usuario</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('access.users.store') }}" class="row g-4">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label required">Nombre</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Correo</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Contraseña</label>
                        <input type="password" class="form-control" name="password" minlength="8" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Roles</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($roles as $role)
                                <label class="form-check form-check-inline form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="role_names[]" value="{{ $role->name }}">
                                    <span class="form-check-label">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Permisos únicos (directos)</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($permissions as $permission)
                                <label class="form-check form-check-inline form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="permission_names[]" value="{{ $permission->name }}">
                                    <span class="form-check-label">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-check form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <span class="form-check-label">Usuario activo</span>
                        </label>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit">Crear usuario</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Usuarios registrados</h3>
            </div>
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0">
                        <thead>
                            <tr class="text-muted text-uppercase fs-8">
                                <th>Usuario</th>
                                <th>Estado</th>
                                <th>Roles</th>
                                <th>Permisos únicos</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $userItem)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $userItem->name }}</div>
                                        <div class="text-muted fs-8">{{ $userItem->email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $userItem->is_active ? 'badge-light-success text-success' : 'badge-light-danger text-danger' }}">
                                            {{ $userItem->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>{{ $userItem->roles->pluck('name')->implode(', ') ?: '-' }}</td>
                                    <td>{{ $userItem->permissions->pluck('name')->implode(', ') ?: '-' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#editUserModal-{{ $userItem->id }}">
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-muted">No hay usuarios registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        </div>

        <div class="row g-6">
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Catálogo de roles</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('access.roles.store') }}" class="mb-8">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label required">Nuevo rol</label>
                                <input type="text" class="form-control" name="name" placeholder="ej. auditor" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Permisos del rol</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($permissions as $permission)
                                        <label class="form-check form-check-inline form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="permission_names[]" value="{{ $permission->name }}">
                                            <span class="form-check-label">{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit">Crear rol</button>
                        </form>

                        <div class="d-flex flex-column gap-5">
                            @foreach ($roles as $role)
                                <form method="POST" action="{{ route('access.roles.update', $role) }}" class="border rounded p-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3">
                                        <label class="form-label">Nombre del rol</label>
                                        <input type="text" class="form-control" name="name" value="{{ $role->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Permisos del rol</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($permissions as $permission)
                                                <label class="form-check form-check-inline form-check-solid">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="permission_names[]"
                                                        value="{{ $permission->name }}"
                                                        {{ $role->permissions->contains('name', $permission->name) ? 'checked' : '' }}
                                                    >
                                                    <span class="form-check-label">{{ $permission->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button class="btn btn-light-primary btn-sm" type="submit">Guardar rol</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Catálogo de permisos</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('access.permissions.store') }}" class="row g-3 mb-8">
                            @csrf
                            <div class="col-md-9">
                                <label class="form-label required">Nuevo permiso</label>
                                <input type="text" class="form-control" name="name" placeholder="ej. expedientes.eliminar_archivos" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-primary w-100" type="submit">Agregar</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-row-bordered align-middle">
                                <thead>
                                    <tr class="text-muted text-uppercase fs-8">
                                        <th>Permiso</th>
                                        <th>Guard</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permissions as $permission)
                                        <tr>
                                            <td>{{ $permission->name }}</td>
                                            <td>{{ $permission->guard_name }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('access.permissions.update', $permission) }}" class="d-flex gap-2 justify-content-end">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" class="form-control form-control-sm" style="max-width:320px;" name="name" value="{{ $permission->name }}" required>
                                                    <button class="btn btn-sm btn-light-primary" type="submit">Guardar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($users as $userItem)
        <div class="modal fade" id="editUserModal-{{ $userItem->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <form method="POST" action="{{ route('access.users.update', $userItem) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h3 class="modal-title">Editar usuario</h3>
                            <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">×</button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label required">Nombre</label>
                                    <input type="text" class="form-control" name="name" value="{{ $userItem->name }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Correo</label>
                                    <input type="email" class="form-control" name="email" value="{{ $userItem->email }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nueva contraseña</label>
                                    <input type="password" class="form-control" name="password" minlength="8" placeholder="Opcional">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Roles</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach ($roles as $role)
                                            <label class="form-check form-check-inline form-check-solid">
                                                <input class="form-check-input" type="checkbox" name="role_names[]" value="{{ $role->name }}" {{ $userItem->roles->contains('name', $role->name) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $role->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Permisos únicos</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach ($permissions as $permission)
                                            <label class="form-check form-check-inline form-check-solid">
                                                <input class="form-check-input" type="checkbox" name="permission_names[]" value="{{ $permission->name }}" {{ $userItem->permissions->contains('name', $permission->name) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $permission->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-check form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $userItem->is_active ? 'checked' : '' }}>
                                        <span class="form-check-label">Usuario activo</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

