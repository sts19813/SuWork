@extends('layouts.payment')

@section('title', 'Pagar cargo')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Pago de cargo</h3>
                </div>
                <div class="card-body">
                    @if (request()->boolean('cancelled'))
                        <div class="alert alert-warning mb-6">
                            El pago fue cancelado. Puedes intentarlo de nuevo.
                        </div>
                    @endif

                    <div class="mb-7">
                        <div class="text-muted fs-7 mb-1">Concepto</div>
                        <div class="fw-bold fs-4 text-dark">{{ $charge->concept }}</div>
                        <div class="text-muted fs-7">{{ $charge->type_label }}</div>
                    </div>

                    <div class="row g-5 mb-7">
                        <div class="col-sm-6">
                            <div class="text-muted fs-7 mb-1">Inquilino</div>
                            <div class="fw-semibold text-dark">{{ $charge->tenant?->full_name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted fs-7 mb-1">Propiedad</div>
                            <div class="fw-semibold text-dark">{{ $charge->property?->internal_name ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted fs-7 mb-1">Periodo</div>
                            <div class="fw-semibold text-dark">
                                {{ str_pad((string) $charge->period_month, 2, '0', STR_PAD_LEFT) }}/{{ $charge->period_year }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted fs-7 mb-1">Vencimiento</div>
                            <div class="fw-semibold text-dark">{{ $charge->due_date?->format('d/m/Y') }}</div>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-6"></div>

                    <div class="d-flex justify-content-between align-items-center mb-7">
                        <span class="fw-semibold fs-5">Total a pagar</span>
                        <span class="fw-bold fs-2 text-primary">${{ number_format($charge->outstanding_amount, 2) }}</span>
                    </div>

                    <form method="POST" action="{{ route('charges.public.checkout', ['token' => $charge->payment_token]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            Pagar con Stripe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
