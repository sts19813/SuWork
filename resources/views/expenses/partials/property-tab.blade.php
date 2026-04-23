<div class="tab-pane fade property-tab-pane" id="tab-expenses" role="tabpanel" aria-labelledby="tab-expenses-tab">
    <div class="d-flex flex-column gap-8">
        <div class="card property-block-card">
            <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title fw-bold mb-1">Resumen de gastos</h3>
                    <div class="text-muted fs-7">Seguimiento de pendientes, pagados y atrasados.</div>
                </div>
                <a href="{{ route('expenses.index', ['property' => $property->uuid]) }}" class="btn btn-sm btn-light-primary">
                    Abrir módulo global
                </a>
            </div>
            <div class="card-body pt-0">
                @include('expenses.partials.summary-cards', ['summary' => $propertyExpenseSummary])
            </div>
        </div>

        <div class="card property-block-card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Notificaciones de gastos</h3>
            </div>
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('expenses.properties.setup', $property) }}" class="row g-5">
                    @csrf
                    @method('PUT')

                    <div class="col-md-3">
                        <label class="form-label required">Configuración</label>
                        <select name="use_global_setup" class="form-select @error('use_global_setup', 'expensePropertySetup') is-invalid @enderror">
                            <option value="1" {{ (string) old('use_global_setup', $resolvedPropertyExpenseNotificationSetup['uses_global'] ? 1 : 0) === '1' ? 'selected' : '' }}>
                                Usar configuración global
                            </option>
                            <option value="0" {{ (string) old('use_global_setup', $resolvedPropertyExpenseNotificationSetup['uses_global'] ? 1 : 0) === '0' ? 'selected' : '' }}>
                                Configuración personalizada
                            </option>
                        </select>
                        @error('use_global_setup', 'expensePropertySetup')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">Días de aviso</label>
                        <input type="number" min="0" max="365" name="days_before"
                            class="form-control @error('days_before', 'expensePropertySetup') is-invalid @enderror"
                            value="{{ old('days_before', (int) ($resolvedPropertyExpenseNotificationSetup['days_before'] ?? $globalExpenseNotificationSetup->days_before ?? 0)) }}">
                        @error('days_before', 'expensePropertySetup')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Correos</label>
                        <textarea name="emails" rows="2" class="form-control @error('emails', 'expensePropertySetup') is-invalid @enderror"
                            placeholder="correo1@dominio.com, correo2@dominio.com">{{ old('emails', implode(', ', (array) ($resolvedPropertyExpenseNotificationSetup['emails'] ?? []))) }}</textarea>
                        @error('emails', 'expensePropertySetup')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Teléfonos</label>
                        <textarea name="phones" rows="2" class="form-control @error('phones', 'expensePropertySetup') is-invalid @enderror"
                            placeholder="9990000000, 9991111111">{{ old('phones', implode(', ', (array) ($resolvedPropertyExpenseNotificationSetup['phones'] ?? []))) }}</textarea>
                        @error('phones', 'expensePropertySetup')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-light-primary">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card property-block-card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Registrar gasto</h3>
            </div>
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="row g-5">
                    @csrf

                    <input type="hidden" name="property_id" value="{{ $property->id }}">
                    <input type="hidden" name="property_context" value="{{ $property->uuid }}">

                    <div class="col-md-4">
                        <label class="form-label required">Concepto</label>
                        <input type="text" name="concept" maxlength="190"
                            class="form-control @error('concept', 'createExpense') is-invalid @enderror"
                            value="{{ old('concept') }}" required>
                        @error('concept', 'createExpense')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label required">Monto</label>
                        <input type="number" name="amount" min="0.01" step="0.01"
                            class="form-control @error('amount', 'createExpense') is-invalid @enderror"
                            value="{{ old('amount') }}" required>
                        @error('amount', 'createExpense')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">Fecha vencimiento</label>
                        <input type="date" name="due_date"
                            class="form-control @error('due_date', 'createExpense') is-invalid @enderror"
                            value="{{ old('due_date') }}" required>
                        @error('due_date', 'createExpense')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Adjuntos</label>
                        <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                            class="form-control @error('files.*', 'createExpense') is-invalid @enderror">
                        @error('files.*', 'createExpense')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" rows="3" class="form-control @error('description', 'createExpense') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description', 'createExpense')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Guardar gasto</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card property-block-card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Listado de gastos</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle mb-0">
                        <thead>
                            <tr class="text-muted text-uppercase fs-8">
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Adjuntos</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($propertyExpenses as $expense)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $expense->concept }}</div>
                                        @if ($expense->description)
                                            <div class="text-muted fs-7">{{ $expense->description }}</div>
                                        @endif
                                        @include('expenses.partials.attachments', ['files' => $expense->files->take(4)])
                                    </td>
                                    <td>${{ number_format((float) $expense->amount, 2) }}</td>
                                    <td>{{ $expense->due_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td>@include('expenses.partials.status-badge', ['expense' => $expense])</td>
                                    <td>
                                        @if ($expense->files_count > 0)
                                            <span class="badge badge-light-info text-info">
                                                <i class="ki-outline ki-paper-clip fs-6 me-1"></i>{{ $expense->files_count }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            @if (!$expense->is_paid)
                                                <form method="POST" action="{{ route('expenses.mark-paid', $expense) }}">
                                                    @csrf
                                                    <input type="hidden" name="property_context" value="{{ $property->uuid }}">
                                                    <button type="submit" class="btn btn-sm btn-light-success">Marcar pagado</button>
                                                </form>
                                            @endif

                                            <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="collapse"
                                                data-bs-target="#expense-edit-{{ $expense->uuid }}" aria-expanded="false"
                                                aria-controls="expense-edit-{{ $expense->uuid }}">
                                                Editar
                                            </button>

                                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                                onsubmit="return confirm('¿Deseas eliminar este gasto?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="property_context" value="{{ $property->uuid }}">
                                                <button type="submit" class="btn btn-sm btn-light-danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="collapse" id="expense-edit-{{ $expense->uuid }}">
                                    <td colspan="6" class="bg-light-primary">
                                        <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data"
                                            class="row g-4 p-2">
                                            @csrf
                                            @method('PUT')

                                            <input type="hidden" name="property_context" value="{{ $property->uuid }}">

                                            <div class="col-md-4">
                                                <label class="form-label required">Concepto</label>
                                                <input type="text" name="concept" maxlength="190" class="form-control"
                                                    value="{{ $expense->concept }}" required>
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label required">Monto</label>
                                                <input type="number" name="amount" min="0.01" step="0.01" class="form-control"
                                                    value="{{ number_format((float) $expense->amount, 2, '.', '') }}" required>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label required">Vencimiento</label>
                                                <input type="date" name="due_date" class="form-control"
                                                    value="{{ $expense->due_date?->format('Y-m-d') }}" required>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Nuevos adjuntos</label>
                                                <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                    class="form-control">
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">Descripción</label>
                                                <textarea name="description" rows="2" class="form-control">{{ $expense->description }}</textarea>
                                            </div>

                                            @if ($expense->files->isNotEmpty())
                                                <div class="col-12">
                                                    <div class="d-flex flex-column gap-2">
                                                        @foreach ($expense->files as $file)
                                                            <label class="form-check form-check-custom form-check-solid d-flex justify-content-between border rounded px-3 py-2">
                                                                <div>
                                                                    @if ($file->is_image)
                                                                        <span class="badge badge-light-warning text-warning me-2">Imagen</span>
                                                                    @else
                                                                        <span class="badge badge-light-primary text-primary me-2">PDF</span>
                                                                    @endif
                                                                    {{ $file->original_name ?: 'Archivo' }}
                                                                </div>
                                                                <div>
                                                                    <input class="form-check-input" type="checkbox" name="remove_file_ids[]"
                                                                        value="{{ $file->id }}">
                                                                    <span class="form-check-label ms-2">Eliminar</span>
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="col-12 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-sm btn-primary">Guardar cambios</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-8">No hay gastos registrados para esta propiedad.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
