@php
    $selectedRoles = $userItem?->roles?->pluck('name')->all() ?? [];
    $selectedPermissions = $userItem?->permissions?->pluck('name')->all() ?? [];
    $reviewerAccess = $userItem?->maintenanceReviewerAccess;
@endphp

<div class="row g-5">
    <div class="col-md-4">
        <label class="form-label required">Nombre</label>
        <input type="text" class="form-control form-control-solid" name="name" value="{{ $userItem?->name }}" required>
        <div class="invalid-feedback d-block" data-error-for="name"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label required">Correo</label>
        <input type="email" class="form-control form-control-solid" name="email" value="{{ $userItem?->email }}" required>
        <div class="invalid-feedback d-block" data-error-for="email"></div>
    </div>
    <div class="col-md-4">
        <label class="form-label {{ $userItem ? '' : 'required' }}">{{ $userItem ? 'Nueva contraseña' : 'Contraseña' }}</label>
        <input type="password" class="form-control form-control-solid" name="password" minlength="8" {{ $userItem ? '' : 'required' }} placeholder="{{ $userItem ? 'Opcional' : '' }}">
        <div class="invalid-feedback d-block" data-error-for="password"></div>
    </div>
    <div class="col-12">
        <label class="form-label">Roles</label>
        <div class="permission-grid">
            @forelse ($roleOptions as $roleName)
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="role_names[]" value="{{ $roleName }}" {{ in_array($roleName, $selectedRoles, true) ? 'checked' : '' }}>
                    <span class="form-check-label">{{ $roleName === 'revisor de tickets' ? 'Revisor de Tickets' : $roleName }}</span>
                </label>
            @empty
                <div class="text-muted">Aún no hay roles creados.</div>
            @endforelse
        </div>
        <div class="invalid-feedback d-block" data-error-for="role_names"></div>
    </div>
    <div class="col-12" data-reviewer-access-settings>
        <div class="border rounded p-4 bg-light">
            <div class="fw-semibold mb-1">Acceso de Revisor de Tickets</div>
            <div class="text-muted fs-7 mb-4">El rango se aplica al listado y al acceso directo a cada ticket.</div>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Tipo de rango</label>
                    <select class="form-select" name="reviewer_range_type" data-reviewer-range-type>
                        <option value="relative" {{ ($reviewerAccess?->range_type ?? 'relative') === 'relative' ? 'selected' : '' }}>Relativo a hoy</option>
                        <option value="custom" {{ $reviewerAccess?->range_type === 'custom' ? 'selected' : '' }}>Rango personalizado</option>
                    </select>
                </div>
                <div class="col-md-4" data-reviewer-relative-field>
                    <label class="form-label">Días anteriores</label>
                    <input type="number" class="form-control" min="0" max="36500" name="reviewer_days_before" value="{{ $reviewerAccess?->days_before ?? 30 }}">
                </div>
                <div class="col-md-4" data-reviewer-relative-field>
                    <label class="form-label">Días posteriores</label>
                    <input type="number" class="form-control" min="0" max="36500" name="reviewer_days_after" value="{{ $reviewerAccess?->days_after ?? 0 }}">
                </div>
                <div class="col-md-4 d-none" data-reviewer-custom-field>
                    <label class="form-label">Desde</label>
                    <input type="date" class="form-control" name="reviewer_starts_on" value="{{ $reviewerAccess?->starts_on?->format('Y-m-d') }}">
                </div>
                <div class="col-md-4 d-none" data-reviewer-custom-field>
                    <label class="form-label">Hasta</label>
                    <input type="date" class="form-control" name="reviewer_ends_on" value="{{ $reviewerAccess?->ends_on?->format('Y-m-d') }}">
                </div>
            </div>
        </div>
        <div class="invalid-feedback d-block" data-error-for="reviewer_starts_on"></div>
    </div>
    <div class="col-12">
        <label class="form-label">Permisos directos</label>
        <div class="permission-grid">
            @forelse ($permissionOptions as $permissionName)
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="permission_names[]" value="{{ $permissionName }}" {{ in_array($permissionName, $selectedPermissions, true) ? 'checked' : '' }}>
                    <span class="form-check-label">{{ $permissionName }}</span>
                </label>
            @empty
                <div class="text-muted">Aún no hay permisos creados.</div>
            @endforelse
        </div>
        <div class="invalid-feedback d-block" data-error-for="permission_names"></div>
    </div>
    <div class="col-12">
        <label class="form-check form-switch form-check-custom form-check-solid">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $userItem === null || $userItem->is_active ? 'checked' : '' }}>
            <span class="form-check-label">Usuario activo y con acceso al sistema</span>
        </label>
    </div>
</div>
