@php
    /** @var \App\Models\Tenant|null $tenant */
    $tenant = $tenant ?? null;
@endphp

<div class="row g-5">
    <div class="col-12">
        <h5 class="mb-0 fw-bold text-uppercase fs-7 text-muted">Datos personales</h5>
    </div>
    <div class="col-12">
        <label class="form-label required">Nombre completo</label>
        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
            value="{{ old('full_name', $tenant?->full_name) }}">
        @error('full_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label required">Telefono principal</label>
        <input type="text" name="phone_primary" class="form-control @error('phone_primary') is-invalid @enderror"
            value="{{ old('phone_primary', $tenant?->phone_primary) }}">
        @error('phone_primary')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Telefono secundario</label>
        <input type="text" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror"
            value="{{ old('phone_secondary', $tenant?->phone_secondary) }}">
        @error('phone_secondary')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $tenant?->email) }}">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">CURP</label>
        <input type="text" name="curp" class="form-control @error('curp') is-invalid @enderror"
            value="{{ old('curp', $tenant?->curp) }}">
        @error('curp')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">RFC</label>
        <input type="text" name="rfc" class="form-control @error('rfc') is-invalid @enderror"
            value="{{ old('rfc', $tenant?->rfc) }}">
        @error('rfc')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Employer</label>
        <input type="text" name="employer" class="form-control @error('employer') is-invalid @enderror"
            value="{{ old('employer', $tenant?->employer) }}">
        @error('employer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Ocupacion</label>
        <input type="text" name="occupation" class="form-control @error('occupation') is-invalid @enderror"
            value="{{ old('occupation', $tenant?->occupation) }}">
        @error('occupation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Ingreso mensual (MXN)</label>
        <input type="number" min="0" step="0.01" name="monthly_income"
            class="form-control @error('monthly_income') is-invalid @enderror"
            value="{{ old('monthly_income', $tenant?->monthly_income) }}">
        @error('monthly_income')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mt-3">
        <h5 class="mb-0 fw-bold text-uppercase fs-7 text-muted">Referencias</h5>
    </div>
    <div class="col-lg-6">
        <label class="form-label">Referencia personal - nombre</label>
        <input type="text" name="personal_reference_name" class="form-control"
            value="{{ old('personal_reference_name', $tenant?->personal_reference_name) }}">
    </div>
    <div class="col-lg-6">
        <label class="form-label">Referencia personal - tel</label>
        <input type="text" name="personal_reference_phone" class="form-control"
            value="{{ old('personal_reference_phone', $tenant?->personal_reference_phone) }}">
    </div>
    <div class="col-lg-6">
        <label class="form-label">Referencia laboral - nombre</label>
        <input type="text" name="work_reference_name" class="form-control"
            value="{{ old('work_reference_name', $tenant?->work_reference_name) }}">
    </div>
    <div class="col-lg-6">
        <label class="form-label">Referencia laboral - tel</label>
        <input type="text" name="work_reference_phone" class="form-control"
            value="{{ old('work_reference_phone', $tenant?->work_reference_phone) }}">
    </div>

    <div class="col-12 mt-3">
        <h5 class="mb-0 fw-bold text-uppercase fs-7 text-muted">Expediente y domicilio</h5>
    </div>
    <div class="col-lg-6">
        <label class="form-label">Contacto de emergencia - nombre</label>
        <input type="text" name="emergency_contact_name" class="form-control"
            value="{{ old('emergency_contact_name', $tenant?->emergency_contact_name) }}">
    </div>
    <div class="col-lg-6">
        <label class="form-label">Contacto de emergencia - tel</label>
        <input type="text" name="emergency_contact_phone" class="form-control"
            value="{{ old('emergency_contact_phone', $tenant?->emergency_contact_phone) }}">
    </div>
    <div class="col-lg-6">
        <label class="form-label">Domicilio anterior</label>
        <textarea name="previous_address" rows="3" class="form-control">{{ old('previous_address', $tenant?->previous_address) }}</textarea>
    </div>
    <div class="col-lg-6">
        <label class="form-label">Domicilio actual</label>
        <textarea name="current_address" rows="3" class="form-control">{{ old('current_address', $tenant?->current_address) }}</textarea>
    </div>
    <div class="col-lg-6">
        <label class="form-label">Estado del expediente</label>
        <select name="dossier_status" class="form-select @error('dossier_status') is-invalid @enderror">
            @foreach ($dossierStatuses as $statusValue => $statusLabel)
                <option value="{{ $statusValue }}"
                    {{ old('dossier_status', $tenant?->dossier_status ?? \App\Models\Tenant::DOSSIER_INCOMPLETE) === $statusValue ? 'selected' : '' }}>
                    {{ $statusLabel }}
                </option>
            @endforeach
        </select>
        @error('dossier_status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Años laborales</label>
        <input type="number" min="0" max="80" name="employment_years" class="form-control"
            value="{{ old('employment_years', $tenant?->employment_years) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Notas internas</label>
        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $tenant?->notes) }}</textarea>
    </div>
</div>

