<div class="tab-pane fade property-tab-pane" id="tab-expenses" role="tabpanel" aria-labelledby="tab-expenses-tab">
    <div class="card property-block-card">
        <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="card-title fw-bold mb-1">Resumen de gastos</h3>
                <div class="text-muted fs-7">Seguimiento de pendientes, pagados y atrasados.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#expenseSetupModal">
                    Configuración de notificación
                </button>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createExpenseModalProperty">
                    Nuevo gasto
                </button>
                <a href="{{ route('expenses.index', ['property' => $property->uuid]) }}" class="btn btn-sm btn-light">
                    Abrir módulo global
                </a>
            </div>
        </div>

        <div class="card-body pt-0">
            @include('expenses.partials.summary-cards', ['summary' => $propertyExpenseSummary])

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
                                            @php
                                                $expensePayload = [
                                                    'uuid' => $expense->uuid,
                                                    'concept' => $expense->concept,
                                                    'amount' => number_format((float) $expense->amount, 2, '.', ''),
                                                    'due_date' => $expense->due_date?->format('Y-m-d'),
                                                    'due_date_label' => $expense->due_date?->format('d/m/Y') ?? '-',
                                                    'description' => $expense->description,
                                                    'mark_action' => route('expenses.mark-paid', $expense),
                                                    'update_action' => route('expenses.update', $expense),
                                                ];
                                            @endphp
                                            <button type="button" class="btn btn-sm btn-light-success js-mark-paid-btn"
                                                data-expense='@json($expensePayload)'
                                                data-bs-toggle="modal" data-bs-target="#markPaidExpenseModal">
                                                Marcar pagado
                                            </button>
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

    <div class="modal fade" id="expenseSetupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('expenses.properties.setup', $property) }}">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h3 class="modal-title">Configuración de notificaciones</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-4">
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

                            <div class="col-md-4">
                                <label class="form-label required">Días de aviso</label>
                                <input type="number" min="0" max="365" name="days_before"
                                    class="form-control @error('days_before', 'expensePropertySetup') is-invalid @enderror"
                                    value="{{ old('days_before', (int) ($resolvedPropertyExpenseNotificationSetup['days_before'] ?? $globalExpenseNotificationSetup->days_before ?? 0)) }}">
                                @error('days_before', 'expensePropertySetup')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Correos</label>
                                <textarea name="emails" rows="2" class="form-control @error('emails', 'expensePropertySetup') is-invalid @enderror"
                                    placeholder="correo1@dominio.com, correo2@dominio.com">{{ old('emails', implode(', ', (array) ($resolvedPropertyExpenseNotificationSetup['emails'] ?? []))) }}</textarea>
                                @error('emails', 'expensePropertySetup')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Teléfonos</label>
                                <textarea name="phones" rows="2" class="form-control @error('phones', 'expensePropertySetup') is-invalid @enderror"
                                    placeholder="9990000000, 9991111111">{{ old('phones', implode(', ', (array) ($resolvedPropertyExpenseNotificationSetup['phones'] ?? []))) }}</textarea>
                                @error('phones', 'expensePropertySetup')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createExpenseModalProperty" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="property_id" value="{{ $property->id }}">
                    <input type="hidden" name="property_context" value="{{ $property->uuid }}">

                    <div class="modal-header">
                        <h3 class="modal-title">Registrar gasto</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Concepto</label>
                                <input type="text" name="concept" maxlength="190"
                                    class="form-control @error('concept', 'createExpense') is-invalid @enderror"
                                    value="{{ old('concept') }}" required>
                                @error('concept', 'createExpense')
                                    <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
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

                            <div class="col-md-12">
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
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar gasto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="markPaidExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <form id="markPaidExpenseForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="property_context" value="{{ $property->uuid }}">

                    <div class="modal-header">
                        <h3 class="modal-title">Marcar gasto como pagado</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-4">
                            <div class="fw-bold" id="markPaidExpenseConcept">-</div>
                            <div class="text-muted fs-7" id="markPaidExpenseAmount">-</div>
                            <div class="text-muted fs-7" id="markPaidExpenseDueDate">-</div>
                        </div>

                        <div class="form-check form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="markPaidAttachToggle">
                            <label class="form-check-label" for="markPaidAttachToggle">Deseo adjuntar comprobante de pago</label>
                        </div>

                        <div id="markPaidReceiptWrap" class="d-none">
                            <label class="form-label">Comprobante (archivo adicional)</label>
                            <input type="file" id="markPaidReceipt" accept=".jpg,.jpeg,.png,.webp,.pdf" class="form-control">
                            <div class="text-muted fs-8 mt-1">Se guarda como adjunto del gasto antes de marcarlo pagado.</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="markPaidSubmitBtn">Confirmar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const modalEl = document.getElementById('markPaidExpenseModal');
            if (!modalEl) return;

            const form = document.getElementById('markPaidExpenseForm');
            const conceptEl = document.getElementById('markPaidExpenseConcept');
            const amountEl = document.getElementById('markPaidExpenseAmount');
            const dueDateEl = document.getElementById('markPaidExpenseDueDate');
            const attachToggle = document.getElementById('markPaidAttachToggle');
            const receiptWrap = document.getElementById('markPaidReceiptWrap');
            const receiptInput = document.getElementById('markPaidReceipt');
            const submitBtn = document.getElementById('markPaidSubmitBtn');
            const csrfToken = @json(csrf_token());
            const propertyContext = @json($property->uuid);
            let payload = null;

            document.querySelectorAll('.js-mark-paid-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    try {
                        payload = JSON.parse(button.dataset.expense || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    form.setAttribute('action', payload.mark_action || '');
                    conceptEl.textContent = payload.concept || '-';
                    amountEl.textContent = payload.amount ? `$${Number(payload.amount).toFixed(2)}` : '-';
                    dueDateEl.textContent = payload.due_date_label || '-';
                    attachToggle.checked = false;
                    receiptWrap.classList.add('d-none');
                    receiptInput.value = '';
                });
            });

            attachToggle.addEventListener('change', () => {
                receiptWrap.classList.toggle('d-none', !attachToggle.checked);
            });

            form.addEventListener('submit', async (event) => {
                if (!attachToggle.checked) {
                    return;
                }

                const file = receiptInput.files && receiptInput.files[0] ? receiptInput.files[0] : null;
                if (!file) {
                    event.preventDefault();
                    alert('Selecciona un comprobante para adjuntar o desactiva la opción de adjuntar.');
                    return;
                }

                event.preventDefault();
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';

                try {
                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('_method', 'PUT');
                    formData.append('property_context', propertyContext);
                    formData.append('concept', payload?.concept || '');
                    formData.append('amount', payload?.amount || '0.01');
                    formData.append('due_date', payload?.due_date || new Date().toISOString().slice(0, 10));
                    formData.append('description', payload?.description || '');
                    formData.append('files[]', file);

                    const response = await fetch(payload?.update_action || '', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error('No fue posible adjuntar el comprobante.');
                    }

                    attachToggle.checked = false;
                    receiptWrap.classList.add('d-none');
                    receiptInput.value = '';
                    form.submit();
                } catch (error) {
                    alert(error.message || 'No fue posible adjuntar el comprobante.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Confirmar pago';
                }
            });

            @if ($errors->expensePropertySetup->any())
                const setupModal = document.getElementById('expenseSetupModal');
                if (setupModal) {
                    new bootstrap.Modal(setupModal).show();
                }
            @endif

            @if ($errors->createExpense->any())
                const createModal = document.getElementById('createExpenseModalProperty');
                if (createModal) {
                    new bootstrap.Modal(createModal).show();
                }
            @endif
        })();
    </script>
@endpush
