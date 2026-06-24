@extends('layouts.app')

@section('title', 'Pendientes | SuWork')

@php
    $filters = [
        'all' => ['label' => 'Todos', 'icon' => 'bi-list-check'],
        'urgent' => ['label' => 'Urgentes', 'icon' => 'bi-exclamation-octagon'],
        'today' => ['label' => 'Hoy', 'icon' => 'bi-calendar-day'],
        'charges' => ['label' => 'Cobranza', 'icon' => 'bi-wallet2'],
        'maintenance' => ['label' => 'Tickets', 'icon' => 'bi-tools'],
        'documents' => ['label' => 'Docs', 'icon' => 'bi-folder2-open'],
        'contracts' => ['label' => 'Contratos', 'icon' => 'bi-file-earmark-text'],
    ];

    $dateRanges = [
        'today' => ['label' => 'Hoy', 'icon' => 'bi-calendar-day'],
        'current_week' => ['label' => 'Esta semana', 'icon' => 'bi-calendar-week'],
        'current_month' => ['label' => 'Este mes', 'icon' => 'bi-calendar3'],
    ];

    $summaryCards = [
        ['label' => 'Urgentes', 'value' => $urgentTasksCount, 'tone' => 'danger', 'icon' => 'bi-exclamation-octagon'],
        ['label' => 'Para hoy', 'value' => $todayTasksCount, 'tone' => 'primary', 'icon' => 'bi-calendar-day'],
        ['label' => 'Proximos', 'value' => $upcomingTasksCount, 'tone' => 'warning', 'icon' => 'bi-hourglass-split'],
        ['label' => 'Propiedades', 'value' => $assignedPropertyCount, 'tone' => 'success', 'icon' => 'bi-house-door'],
    ];
@endphp

@push('styles')
    <style>
        .advisor-tasks {
            max-width: 1180px;
            margin: 0 auto;
        }

        .advisor-tasks-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .advisor-tasks-title {
            font-size: 2rem;
            line-height: 1.15;
        }

        .advisor-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.875rem;
            margin-bottom: 1rem;
        }

        .advisor-summary-card {
            border: 1px solid #e9edf4;
            border-radius: 8px;
            background: #fff;
            padding: 1rem;
        }

        .advisor-summary-card__icon {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f5f8fa;
        }

        .advisor-filter-strip {
            display: flex;
            gap: 0.65rem;
            overflow-x: auto;
            padding: 0.25rem 0 0.75rem;
            margin-bottom: 0.5rem;
            scrollbar-width: thin;
        }

        .advisor-range-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            border: 1px solid #e9edf4;
            border-radius: 8px;
            background: #fff;
            padding: 0.85rem;
            margin-bottom: 1rem;
        }

        .advisor-range-tabs {
            display: inline-flex;
            gap: 0.35rem;
            border-radius: 8px;
            background: #f5f8fa;
            padding: 0.25rem;
        }

        .advisor-range-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 6px;
            color: #4b5675;
            font-weight: 700;
            padding: 0.55rem 0.75rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .advisor-range-tab.is-active {
            background: #fff;
            color: #1b84ff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        .advisor-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            flex: 0 0 auto;
            border: 1px solid #dfe5ee;
            border-radius: 999px;
            background: #fff;
            color: #4b5675;
            font-weight: 700;
            padding: 0.65rem 0.95rem;
            text-decoration: none;
        }

        .advisor-filter-chip.is-active {
            border-color: #1b84ff;
            background: #eef6ff;
            color: #1b84ff;
        }

        .advisor-task-list {
            display: grid;
            gap: 0.85rem;
        }

        .advisor-task-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: center;
            border: 1px solid #e9edf4;
            border-radius: 8px;
            background: #fff;
            padding: 1rem;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .advisor-task-item:hover {
            border-color: #b9d8ff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }

        .advisor-task-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f5f8fa;
            font-size: 1.25rem;
        }

        .advisor-task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-bottom: 0.35rem;
        }

        .advisor-task-due {
            min-width: 116px;
            text-align: right;
        }

        .advisor-empty {
            border: 1px dashed #badbcc;
            border-radius: 8px;
            background: #f3fbf7;
            padding: 2rem;
            color: #0f5132;
        }

        @media (max-width: 991.98px) {
            .advisor-tasks {
                padding-bottom: 5rem;
            }

            .advisor-tasks-hero {
                display: block;
            }

            .advisor-tasks-title {
                font-size: 1.65rem;
            }

            .advisor-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .advisor-range-bar {
                align-items: stretch;
            }

            .advisor-range-tabs {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                width: 100%;
            }

            .advisor-range-tab {
                justify-content: center;
                padding: 0.65rem 0.45rem;
            }

            .advisor-task-item {
                grid-template-columns: auto minmax(0, 1fr);
                align-items: flex-start;
                padding: 0.95rem;
            }

            .advisor-task-due {
                grid-column: 1 / -1;
                width: 100%;
                min-width: 0;
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-top: 0.75rem;
                border-top: 1px solid #eef1f6;
            }
        }

        @media (max-width: 575.98px) {
            .advisor-summary-grid {
                gap: 0.65rem;
            }

            .advisor-summary-card {
                padding: 0.85rem;
            }

            .advisor-summary-card__icon {
                width: 32px;
                height: 32px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-8 advisor-tasks">
        <div class="advisor-tasks-hero">
            <div>
                <div class="text-muted fs-7 fw-bold text-uppercase mb-2">Centro de trabajo</div>
                <h1 class="advisor-tasks-title fw-bold text-dark mb-2">Mis pendientes</h1>
                <div class="text-muted fs-6">
                    Tareas y urgencias de tus propiedades asignadas para {{ strtolower($periodLabel) }}.
                </div>
            </div>

            <div class="badge badge-light-primary text-primary fs-7 fw-bold mt-2">
                {{ $allTasksCount }} pendiente{{ $allTasksCount === 1 ? '' : 's' }}
            </div>
        </div>

        <div class="advisor-summary-grid">
            @foreach ($summaryCards as $card)
                <div class="advisor-summary-card">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                        <div class="text-muted fs-8 fw-bold text-uppercase">{{ $card['label'] }}</div>
                        <span class="advisor-summary-card__icon text-{{ $card['tone'] }}">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </span>
                    </div>
                    <div class="fs-2 fw-bold text-dark">{{ number_format($card['value']) }}</div>
                </div>
            @endforeach
        </div>

        <div class="advisor-range-bar">
            <div>
                <div class="text-muted fs-8 fw-bold text-uppercase mb-1">Rango de fecha</div>
                <div class="fw-bold text-dark">
                    {{ $periodStart->translatedFormat('d M Y') }}
                    @unless ($periodStart->isSameDay($periodEnd))
                        - {{ $periodEnd->translatedFormat('d M Y') }}
                    @endunless
                </div>
                @if ($periodIncludesOverdue)
                    <div class="text-muted fs-8 mt-1">Incluye todos los pendientes vencidos, sin importar la fecha.</div>
                @endif
            </div>

            <div class="advisor-range-tabs" aria-label="Rango de fecha de pendientes">
                @foreach ($dateRanges as $key => $range)
                    <a href="{{ route('advisor.tasks.index', ['range' => $key, 'filter' => $activeFilter]) }}"
                        class="advisor-range-tab {{ $activeRange === $key ? 'is-active' : '' }}">
                        <i class="bi {{ $range['icon'] }}"></i>
                        <span>{{ $range['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="advisor-filter-strip" aria-label="Filtros de pendientes">
            @foreach ($filters as $key => $filter)
                <a href="{{ route('advisor.tasks.index', ['range' => $activeRange, 'filter' => $key]) }}"
                    class="advisor-filter-chip {{ $activeFilter === $key ? 'is-active' : '' }}">
                    <i class="bi {{ $filter['icon'] }}"></i>
                    <span>{{ $filter['label'] }}</span>
                    <span class="badge badge-light-secondary text-gray-700">{{ $filterCounts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <div class="advisor-task-list">
            @forelse ($tasks as $task)
                <a href="{{ $task['route'] }}" class="advisor-task-item">
                    <span class="advisor-task-icon text-{{ $task['tone'] }}">
                        <i class="bi {{ $task['icon'] }}"></i>
                    </span>

                    <span class="min-w-0">
                        <span class="advisor-task-meta">
                            <span class="badge badge-light-{{ $task['tone'] }} text-{{ $task['tone'] }}">{{ $task['category_label'] }}</span>
                            @if ($task['priority'] === 'urgent')
                                <span class="badge badge-light-danger text-danger">Urgente</span>
                            @elseif ($task['is_today'])
                                <span class="badge badge-light-primary text-primary">Hoy</span>
                            @endif
                        </span>
                        <span class="d-block fw-bold text-dark text-truncate">{{ $task['title'] }}</span>
                        <span class="d-block text-gray-700 fw-semibold text-truncate">{{ $task['subtitle'] }}</span>
                        <span class="d-block text-muted fs-7 text-truncate">{{ $task['detail'] }}</span>
                    </span>

                    <span class="advisor-task-due">
                        <span class="d-block fw-bold text-{{ $task['tone'] }}">{{ $task['due_label'] }}</span>
                        <span class="d-block text-muted fs-8">{{ $task['due_detail'] }}</span>
                    </span>
                </a>
            @empty
                <div class="advisor-empty">
                    <div class="fw-bold mb-1">No hay pendientes en este filtro.</div>
                    <div>Cuando exista cobranza próxima, tickets, contratos o documentos por atender, aparecerán aquí.</div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
