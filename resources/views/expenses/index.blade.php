@extends('layouts.app')

@section('title', 'Gastos | SuWork')

@section('content')
    <div class="py-10 expenses-module">
        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-check-circle fs-2hx text-success me-4"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information-5 fs-2hx text-warning me-4"></i>
                <div class="fw-semibold">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-cross-circle fs-2hx text-danger me-4"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->updateExpense->any())
            <div class="alert alert-danger d-flex align-items-center p-5 mb-8">
                <i class="ki-outline ki-information-5 fs-2hx text-danger me-4"></i>
                <div class="fw-semibold">
                    No fue posible actualizar el gasto. Verifica los datos e inténtalo nuevamente.
                </div>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Gastos</h1>
                <div class="text-muted fs-6">Control global de gastos por propiedad</div>
            </div>

            <div class="d-flex flex-wrap gap-3">
                {{-- Botón filtros --}}
                <button type="button" class="btn btn-light-primary fw-bold"
                    data-bs-toggle="collapse"
                    data-bs-target="#filtersCollapse"
                    aria-expanded="false"
                    aria-controls="filtersCollapse">
                    <i class="ki-outline ki-filter fs-4 me-1"></i>
                    Filtros
                </button>

                {{-- Botón configuración --}}
                <button type="button"
                    class="btn btn-light-warning fw-bold"
                    data-bs-toggle="modal"
                    data-bs-target="#globalNotificationsModal">
                    <i class="ki-outline ki-setting-2 fs-4 me-1"></i>
                    Configuración
                </button>

                {{-- Nuevo gasto --}}
                <button type="button"
                    class="btn btn-primary fw-bold"
                    data-bs-toggle="modal"
                    data-bs-target="#createExpenseModal">
                    <i class="ki-outline ki-plus fs-4 me-1"></i>
                    Nuevo gasto
                </button>
            </div>
        </div>

        @include('expenses.partials.summary-cards', ['summary' => $summary])

        {{-- FILTROS --}}
        <div class="collapse mb-8" id="filtersCollapse">
            <div class="card">
                <div class="card-header border-0 align-items-center min-h-60px">
                    <h3 class="card-title fw-bold">
                        <i class="ki-outline ki-filter fs-2 text-primary me-2"></i>
                        Filtros
                    </h3>
                </div>

                <div class="card-body py-6">
                    <form method="GET" action="{{ route('expenses.index') }}" class="row g-4 align-items-end">

                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">Propiedad</label>

                            <select name="property" class="form-select">
                                <option value="">Todas</option>

                                @foreach ($properties as $property)
                                    <option value="{{ $property->uuid }}"
                                        {{ $selectedProperty?->uuid === $property->uuid ? 'selected' : '' }}>
                                        {{ $property->internal_name }}
                                        {{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label fw-semibold">Estado</label>

                            <select name="status" class="form-select">
                                @foreach ($statusOptions as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}"
                                        {{ $status === $statusValue ? 'selected' : '' }}>
                                        {{ $statusLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha inicial</label>

                            <input type="date"
                                name="date_from"
                                class="form-control"
                                value="{{ $dateFrom }}">
                        </div>

                        <div class="col-lg-2">
                            <label class="form-label fw-semibold">Fecha final</label>

                            <input type="date"
                                name="date_to"
                                class="form-control"
                                value="{{ $dateTo }}">
                        </div>

                        <div class="col-lg-1 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary w-100">
                                Filtrar
                            </button>
                        </div>

                        <div class="col-12 d-flex justify-content-end">
                            <a href="{{ route('expenses.index') }}" class="btn btn-light">
                                Limpiar
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- TABLA --}}
        <div class="card">
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-5 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase gs-0">
                                <th class="min-w-240px">Concepto</th>
                                <th class="min-w-220px">Propiedad</th>
                                <th class="min-w-140px">Monto</th>
                                <th class="min-w-140px">Vencimiento</th>
                                <th class="min-w-120px">Estado</th>
                                <th class="min-w-120px">Adjuntos</th>
                                <th class="min-w-280px text-end">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="fw-semibold text-gray-700">

                            @forelse ($expenses as $expense)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-gray-900">
                                            {{ $expense->concept }}
                                        </div>

                                        @if ($expense->description)
                                            <div class="text-muted fs-7">
                                                {{ $expense->description }}
                                            </div>
                                        @endif

                                        @include('expenses.partials.attachments', [
                                            'files' => $expense->files->take(4),
                                        ])
                                    </td>

                                    <td>
                                        <div class="fw-bold text-gray-900">
                                            {{ $expense->property?->internal_name ?? '-' }}
                                        </div>

                                        <div class="text-muted fs-7">
                                            {{ $expense->property?->internal_reference ?: '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        ${{ number_format((float) $expense->amount, 2) }}
                                    </td>

                                    <td>
                                        {{ $expense->due_date?->format('d/m/Y') ?? '-' }}
                                    </td>

                                    <td>
                                        @include('expenses.partials.status-badge', [
                                            'expense' => $expense,
                                        ])
                                    </td>

                                    <td>
                                        @if ($expense->files_count > 0)
                                            <span class="badge badge-light-info text-info">
                                                <i class="ki-outline ki-paper-clip fs-6 me-1"></i>
                                                {{ $expense->files_count }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">

                                            @if (!$expense->is_paid)
                                                <form method="POST"
                                                    action="{{ route('expenses.mark-paid', $expense) }}">
                                                    @csrf

                                                    <button type="submit"
                                                        class="btn btn-sm btn-light-success">
                                                        Marcar pagado
                                                    </button>
                                                </form>
                                            @endif

                                            <button type="button"
                                                class="btn btn-sm btn-light-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editExpenseModal-{{ $expense->uuid }}">
                                                Editar
                                            </button>

                                            <form method="POST"
                                                action="{{ route('expenses.destroy', $expense) }}"
                                                onsubmit="return confirm('¿Deseas eliminar este gasto?');">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="btn btn-sm btn-light-danger">
                                                    Eliminar
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"
                                        class="text-center py-16 text-muted">
                                        No hay gastos registrados.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL CONFIGURACIÓN GLOBAL --}}
    <div class="modal fade"
        id="globalNotificationsModal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <form method="POST"
                    action="{{ route('expenses.setup.global') }}">

                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h3 class="modal-title">
                            Configuración global de notificaciones
                        </h3>

                        <button type="button"
                            class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="row g-5">

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Días previos de aviso
                                </label>

                                <input type="number"
                                    name="days_before"
                                    min="0"
                                    max="365"
                                    class="form-control @error('days_before', 'expenseGlobalSetup') is-invalid @enderror"
                                    value="{{ old('days_before', (int) ($globalSetup->days_before ?? 0)) }}"
                                    required>

                                @error('days_before', 'expenseGlobalSetup')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Correos
                                </label>

                                <textarea name="emails"
                                    rows="4"
                                    class="form-control @error('emails', 'expenseGlobalSetup') is-invalid @enderror"
                                    placeholder="correo1@dominio.com, correo2@dominio.com">{{ old('emails', implode(', ', (array) ($globalSetup->emails ?? []))) }}</textarea>

                                <div class="text-muted fs-8 mt-1">
                                    Separar con coma o salto de línea.
                                </div>

                                @error('emails', 'expenseGlobalSetup')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Teléfonos
                                </label>

                                <textarea name="phones"
                                    rows="4"
                                    class="form-control @error('phones', 'expenseGlobalSetup') is-invalid @enderror"
                                    placeholder="9990000000, 9991111111">{{ old('phones', implode(', ', (array) ($globalSetup->phones ?? []))) }}</textarea>

                                <div class="text-muted fs-8 mt-1">
                                    Separar con coma o salto de línea.
                                </div>

                                @error('phones', 'expenseGlobalSetup')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="btn btn-warning">
                            Guardar configuración
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- MODAL CREAR --}}
    <div class="modal fade"
        id="createExpenseModal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <form method="POST"
                    action="{{ route('expenses.store') }}"
                    enctype="multipart/form-data">

                    @csrf

                    <div class="modal-header">
                        <h3 class="modal-title">
                            Registrar gasto
                        </h3>

                        <button type="button"
                            class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">

                        @if ($errors->createExpense->any())
                            <div class="alert alert-danger mb-6">
                                Revisa los datos del formulario.
                            </div>
                        @endif

                        <div class="row g-5">

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Propiedad
                                </label>

                                <select name="property_id"
                                    class="form-select @error('property_id', 'createExpense') is-invalid @enderror"
                                    required>

                                    <option value="">
                                        Seleccionar...
                                    </option>

                                    @foreach ($properties as $property)
                                        <option value="{{ $property->id }}"
                                            {{ (string) old('property_id', $selectedProperty?->id) === (string) $property->id ? 'selected' : '' }}>

                                            {{ $property->internal_name }}
                                            {{ $property->internal_reference ? ' - ' . $property->internal_reference : '' }}
                                        </option>
                                    @endforeach

                                </select>

                                @error('property_id', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">
                                    Concepto
                                </label>

                                <input type="text"
                                    name="concept"
                                    maxlength="190"
                                    class="form-control @error('concept', 'createExpense') is-invalid @enderror"
                                    value="{{ old('concept') }}"
                                    required>

                                @error('concept', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Monto
                                </label>

                                <input type="number"
                                    name="amount"
                                    min="0.01"
                                    step="0.01"
                                    class="form-control @error('amount', 'createExpense') is-invalid @enderror"
                                    value="{{ old('amount') }}"
                                    required>

                                @error('amount', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">
                                    Fecha vencimiento
                                </label>

                                <input type="date"
                                    name="due_date"
                                    class="form-control @error('due_date', 'createExpense') is-invalid @enderror"
                                    value="{{ old('due_date') }}"
                                    required>

                                @error('due_date', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Adjuntos
                                </label>

                                <input type="file"
                                    name="files[]"
                                    multiple
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    class="form-control @error('files.*', 'createExpense') is-invalid @enderror">

                                <div class="text-muted fs-8 mt-1">
                                    JPG, PNG, WEBP, PDF.
                                </div>

                                @error('files.*', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    Descripción
                                </label>

                                <textarea name="description"
                                    rows="3"
                                    class="form-control @error('description', 'createExpense') is-invalid @enderror">{{ old('description') }}</textarea>

                                @error('description', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="btn btn-primary">
                            Guardar gasto
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- MODALES EDITAR --}}
    @foreach ($expenses as $expense)
        <div class="modal fade"
            id="editExpenseModal-{{ $expense->uuid }}"
            tabindex="-1"
            aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">

                    <form method="POST"
                        action="{{ route('expenses.update', $expense) }}"
                        enctype="multipart/form-data">

                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h3 class="modal-title">
                                Editar gasto
                            </h3>

                            <button type="button"
                                class="btn btn-icon btn-sm btn-active-light-primary"
                                data-bs-dismiss="modal">
                                <i class="ki-outline ki-cross fs-1"></i>
                            </button>
                        </div>

                        <div class="modal-body">

                            <div class="row g-5">

                                <div class="col-md-6">
                                    <label class="form-label required">
                                        Concepto
                                    </label>

                                    <input type="text"
                                        name="concept"
                                        maxlength="190"
                                        class="form-control"
                                        value="{{ $expense->concept }}"
                                        required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">
                                        Monto
                                    </label>

                                    <input type="number"
                                        name="amount"
                                        min="0.01"
                                        step="0.01"
                                        class="form-control"
                                        value="{{ number_format((float) $expense->amount, 2, '.', '') }}"
                                        required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label required">
                                        Vencimiento
                                    </label>

                                    <input type="date"
                                        name="due_date"
                                        class="form-control"
                                        value="{{ $expense->due_date?->format('Y-m-d') }}"
                                        required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Descripción
                                    </label>

                                    <textarea name="description"
                                        rows="3"
                                        class="form-control">{{ $expense->description }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Adjuntar nuevos archivos
                                    </label>

                                    <input type="file"
                                        name="files[]"
                                        multiple
                                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                                        class="form-control">
                                </div>

                                @if ($expense->files->isNotEmpty())
                                    <div class="col-12">

                                        <label class="form-label">
                                            Adjuntos actuales
                                        </label>

                                        <div class="d-flex flex-column gap-3">

                                            @foreach ($expense->files as $file)
                                                <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2">

                                                    <div class="d-flex align-items-center gap-3">

                                                        @if ($file->is_image)
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                                                                alt="Adjunto"
                                                                style="width: 36px; height: 36px; object-fit: cover; border-radius: 6px;">
                                                        @else
                                                            <span class="badge badge-light-primary text-primary">
                                                                PDF
                                                            </span>
                                                        @endif

                                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}"
                                                            target="_blank">

                                                            {{ $file->original_name ?: 'Archivo' }}
                                                        </a>

                                                    </div>

                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input"
                                                            type="checkbox"
                                                            name="remove_file_ids[]"
                                                            value="{{ $file->id }}">

                                                        <span class="form-check-label">
                                                            Eliminar
                                                        </span>
                                                    </label>

                                                </div>
                                            @endforeach

                                        </div>
                                    </div>
                                @endif

                            </div>

                        </div>

                        <div class="modal-footer">
                            <button type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
                                Cancelar
                            </button>

                            <button type="submit"
                                class="btn btn-primary">
                                Guardar cambios
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    @if ($errors->createExpense->any())
        <script>
            (() => {
                const modalEl = document.getElementById('createExpenseModal');

                if (!modalEl) return;

                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif

    @if ($errors->expenseGlobalSetup->any())
        <script>
            (() => {
                const modalEl = document.getElementById('globalNotificationsModal');

                if (!modalEl) return;

                new bootstrap.Modal(modalEl).show();
            })();
        </script>
    @endif
@endpush