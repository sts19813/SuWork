<div class="charges-list-table-card">
    <div class="table-responsive">
        <table class="table table-row-bordered align-middle mb-0" id="{{ $tableId }}">
            <thead>
                <tr class="fw-bold text-muted text-uppercase fs-8">
                    <th class="ps-7 min-w-220px">Concepto</th>
                    <th class="min-w-220px">Inquilino / Propiedad</th>
                    <th class="min-w-170px">Asesor responsable</th>
                    <th class="min-w-130px">Vencimiento</th>
                    <th class="min-w-140px">Monto</th>
                    <th class="min-w-120px">Estado</th>
                    @if ($showActions)
                        <th class="min-w-150px text-end pe-7">Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($charges as $charge)
                    @php
                        $canRegisterPayment = $canManageCharges && in_array(
                            $charge->status,
                            [\App\Models\Charge::STATUS_PENDING, \App\Models\Charge::STATUS_PARTIAL, \App\Models\Charge::STATUS_IN_VALIDATION],
                            true,
                        );
                        $canEditCharge = $canRegisterPayment;
                        $canDeleteCharge = $canManageCharges
                            && $charge->status !== \App\Models\Charge::STATUS_CANCELED
                            && ($charge->status !== \App\Models\Charge::STATUS_PAID || $canDeletePaidCharges);
                    @endphp
                    <tr class="charges-list-row" data-charge-row>
                        <td class="ps-7">
                            <div class="charges-list-title">{{ $charge->concept }}</div>
                            <div class="charges-list-meta">{{ $charge->type_label }}</div>
                        </td>
                        <td data-mobile-label="Inquilino / propiedad">
                            <div class="charges-list-value">{{ $charge->tenant?->full_name ?? '-' }}</div>
                            <div class="charges-list-meta">
                                {{ $charge->property?->internal_name ?: '-' }}
                                @if ($charge->property?->internal_reference)
                                    · Ref. {{ $charge->property->internal_reference }}
                                @endif
                            </div>
                        </td>
                        <td data-mobile-label="Asesor responsable">
                            <div class="charges-list-value">{{ $charge->property?->advisor?->name ?? '-' }}</div>
                        </td>
                        <td data-mobile-label="Vencimiento">
                            <div class="charges-list-value">{{ $charge->due_date?->format('d M Y') ?? '-' }}</div>
                        </td>
                        <td data-mobile-label="Monto">
                            <div class="charges-list-value">${{ number_format((float) $charge->amount, 2) }}</div>
                            @if ($charge->outstanding_amount > 0 && $charge->status !== \App\Models\Charge::STATUS_CANCELED)
                                <div class="charges-list-meta">Saldo: ${{ number_format($charge->outstanding_amount, 2) }}</div>
                            @endif
                        </td>
                        <td data-mobile-label="Estado">
                            <span class="badge {{ $charge->status_badge_class }}">{{ $charge->display_status_label }}</span>
                        </td>
                        @if ($showActions)
                            <td class="text-end pe-7" data-mobile-label="Acciones">
                                <div class="dropdown charges-list-actions">
                                    <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        Acciones <i class="ki-outline ki-down fs-7 ms-1"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4">
                                        <a class="dropdown-item px-5" href="{{ route('charges.show', array_filter([
                                            'charge' => $charge,
                                            'property' => $selectedProperty?->uuid,
                                            'return_to' => request()->fullUrl(),
                                        ])) }}">Ver cargo</a>

                                        @if ($canEditCharge)
                                            <button type="button" class="dropdown-item px-5" data-edit-charge
                                                data-action="{{ route('charges.update', $charge) }}"
                                                data-charge="{{ $charge->uuid }}" data-type="{{ $charge->type }}"
                                                data-due-date="{{ $charge->due_date?->format('Y-m-d') }}"
                                                data-amount="{{ number_format((float) $charge->amount, 2, '.', '') }}"
                                                data-period-month="{{ $charge->period_month }}"
                                                data-period-year="{{ $charge->period_year }}"
                                                data-concept="{{ $charge->concept }}" data-notes="{{ $charge->notes }}">
                                                Editar cargo
                                            </button>
                                        @endif

                                        @if ($canRegisterPayment)
                                            <button type="button" class="dropdown-item px-5" data-register-payment
                                                data-charge="{{ $charge->uuid }}"
                                                data-action="{{ route('charges.payments.store', $charge) }}"
                                                data-concept="{{ $charge->concept }}"
                                                data-outstanding="{{ number_format($charge->outstanding_amount, 2, '.', '') }}">
                                                Registrar pago
                                            </button>
                                        @endif

                                        <button type="button" class="dropdown-item px-5" data-copy-link="{{ route('charges.public.show', ['token' => $charge->payment_token]) }}">
                                            Copiar link
                                        </button>

                                        @if ($canDeleteCharge)
                                            <div class="dropdown-divider"></div>
                                            <form method="POST" action="{{ route('charges.destroy', $charge) }}"
                                                class="js-delete-charge-form"
                                                data-charge-concept="{{ $charge->concept }}"
                                                data-charge-paid="{{ $charge->status === \App\Models\Charge::STATUS_PAID ? 'true' : 'false' }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="deletion_note" value="">
                                                <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                                                <button type="submit" class="dropdown-item px-5 text-danger">Eliminar cargo</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showActions ? 7 : 6 }}" class="text-center py-16 text-muted" data-empty-row="true">
                            No hay cargos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
