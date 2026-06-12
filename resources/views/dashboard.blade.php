@extends('layouts.app')

@section('title', 'Dashboard | SuWork')

@section('content')
    <div class="py-10 executive-dashboard">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Panel ejecutivo</h1>
                <div class="text-muted fs-6">{{ ucfirst($selectedMonth->translatedFormat('F Y')) }}</div>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-3">
                <input type="month" name="month" value="{{ $selectedMonth->format('Y-m') }}" class="form-control w-200px">
                <button type="submit" class="btn btn-primary">Cambiar mes</button>
            </form>
        </div>

        <div class="row g-5 mb-8">
            @foreach ($dashboardKpis as $kpi)
                <div class="col-md-6 col-xl-4 col-xxl-2">
                    <div class="card h-100">
                        <div class="card-body p-7">
                            <div class="d-flex align-items-start justify-content-between mb-5">
                                <div class="text-gray-600 fw-semibold fs-7">{{ $kpi['label'] }}</div>
                                <span class="executive-kpi-icon text-{{ $kpi['tone'] }}">
                                    <i class="bi {{ $kpi['icon'] }}"></i>
                                </span>
                            </div>
                            <div class="fw-bold text-dark fs-2x">{{ $kpi['value'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-5 mb-8">
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header border-0 pt-7">
                        <div>
                            <h3 class="card-title fw-bold text-dark">Resumen de cobranza</h3>
                            <div class="text-muted fs-7">Cobrado, pendiente y vencido del mes seleccionado</div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row align-items-center">
                            <div class="col-lg-5">
                                <div id="dashboard_collection_pie" class="min-h-250px"></div>
                            </div>
                            <div class="col-lg-7">
                                @foreach ($collectionSummary['segments'] as $segment)
                                    <div class="mb-5">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark">
                                                <span class="executive-dot" style="background: {{ $segment['color'] }}"></span>
                                                {{ $segment['label'] }}
                                            </div>
                                            <span class="text-muted fw-bold">{{ $segment['percent'] }}%</span>
                                        </div>
                                        <div class="progress h-8px bg-light mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $segment['percent'] }}%; background: {{ $segment['color'] }};"></div>
                                        </div>
                                        <div class="text-gray-700 fw-semibold">{{ '$' . number_format($segment['value'], 2) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header border-0 pt-7">
                        <div>
                            <h3 class="card-title fw-bold text-dark">Alertas importantes</h3>
                            <div class="text-muted fs-7">Contratos por vencer y atrasos de cobranza del periodo</div>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-danger text-danger fs-7 fw-bold">{{ $importantAlerts->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        @forelse ($importantAlerts as $alert)
                            <a href="{{ $alert['route'] }}"
                                class="executive-alert executive-alert-{{ $alert['tone'] }} d-flex align-items-center gap-4 mb-4 text-decoration-none">
                                <span class="executive-alert__icon">
                                    <i class="bi {{ $alert['icon'] }}"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark">{{ $alert['title'] }}</div>
                                    <div class="text-gray-700 fw-semibold">{{ $alert['subtitle'] }}</div>
                                    <div class="text-muted fs-7">{{ $alert['detail'] }}</div>
                                </div>
                                <i class="bi bi-chevron-right text-gray-500"></i>
                            </a>
                        @empty
                            <div class="rounded border border-dashed border-success bg-light-success p-8 text-success fw-semibold">
                                No hay alertas importantes para este periodo.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header border-0 pt-7">
                        <div>
                            <h3 class="card-title fw-bold text-dark">Resumen de propiedades</h3>
                            <div class="text-muted fs-7">Estado de cobranza por propiedad ocupada</div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-0 gy-4">
                                <thead>
                                    <tr class="fw-bold text-muted text-uppercase fs-8">
                                        <th>Propiedad</th>
                                        <th>Asesor</th>
                                        <th>Inquilino</th>
                                        <th class="text-end">Renta</th>
                                        <th class="text-end">Atrasado</th>
                                        <th class="text-end">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($propertySummaries as $summary)
                                        <tr>
                                            <td>
                                                <a href="{{ route('properties.show', $summary['property']) }}" class="fw-bold text-dark text-hover-primary">
                                                    {{ $summary['property']->internal_name }}
                                                </a>
                                            </td>
                                            <td class="text-gray-700">{{ $summary['advisor_name'] }}</td>
                                            <td class="text-gray-700">{{ $summary['tenant_name'] }}</td>
                                            <td class="text-end fw-bold">{{ '$' . number_format($summary['rent_amount'], 2) }}</td>
                                            <td class="text-end {{ $summary['overdue_amount'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                                {{ $summary['overdue_amount'] > 0 ? '$' . number_format($summary['overdue_amount'], 2) : '-' }}
                                            </td>
                                            <td class="text-end">
                                                <span class="badge badge-light-{{ $summary['status_tone'] }} text-{{ $summary['status_tone'] }}">
                                                    {{ $summary['status_label'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-10">No hay propiedades ocupadas para mostrar.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header border-0 pt-7">
                        <div>
                            <h3 class="card-title fw-bold text-dark">Rentabilidad general</h3>
                            <div class="text-muted fs-7">Ingresos, gastos y utilidad de los últimos 6 meses</div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3 mb-6">
                            <div class="col-4">
                                <div class="executive-summary-box">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Ingresos</div>
                                    <div class="fs-2 fw-bold text-dark">{{ '$' . number_format($profitabilitySummary['income_total'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="executive-summary-box">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Gastos</div>
                                    <div class="fs-2 fw-bold text-danger">{{ '$' . number_format($profitabilitySummary['expense_total'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="executive-summary-box">
                                    <div class="text-muted fs-8 fw-bold text-uppercase">Utilidad</div>
                                    <div class="fs-2 fw-bold {{ $profitabilitySummary['profit_total'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ '$' . number_format($profitabilitySummary['profit_total'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="dashboard_profitability_chart" class="min-h-300px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('/metronic/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        (() => {
            const collectionElement = document.getElementById('dashboard_collection_pie');
            const profitabilityElement = document.getElementById('dashboard_profitability_chart');

            if (collectionElement && window.ApexCharts) {
                new ApexCharts(collectionElement, {
                    chart: {
                        type: 'donut',
                        height: 280,
                        toolbar: {
                            show: false,
                        },
                    },
                    series: @json($collectionSummary['series']),
                    labels: @json(collect($collectionSummary['segments'])->pluck('label')->all()),
                    colors: @json(collect($collectionSummary['segments'])->pluck('color')->all()),
                    legend: {
                        show: false,
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    stroke: {
                        width: 4,
                        colors: ['#ffffff'],
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '72%',
                            },
                        },
                    },
                }).render();
            }

            if (profitabilityElement && window.ApexCharts) {
                new ApexCharts(profitabilityElement, {
                    series: [{
                        name: 'Ingresos',
                        type: 'area',
                        data: @json($profitabilitySummary['income_series']),
                    }, {
                        name: 'Gastos',
                        type: 'area',
                        data: @json($profitabilitySummary['expense_series']),
                    }, {
                        name: 'Utilidad',
                        type: 'line',
                        data: @json($profitabilitySummary['profit_series']),
                    }],
                    chart: {
                        height: 320,
                        toolbar: {
                            show: false,
                        },
                    },
                    stroke: {
                        curve: 'smooth',
                        width: [3, 3, 3],
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    fill: {
                        type: 'solid',
                        opacity: [0.18, 0.16, 1],
                    },
                    colors: ['#0bb783', '#f1416c', '#3f4254'],
                    labels: @json($profitabilitySummary['labels']),
                    legend: {
                        position: 'top',
                    },
                    xaxis: {
                        categories: @json($profitabilitySummary['labels']),
                    },
                    yaxis: {
                        labels: {
                            formatter: function(value) {
                                return '$' + Number(value).toLocaleString('en-US');
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return '$' + Number(value).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                });
                            }
                        }
                    }
                }).render();
            }
        })();
    </script>
@endpush
