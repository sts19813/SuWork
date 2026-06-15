@extends('layouts.app')

@section('title', 'Control de Propiedades | SuWork')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/assets/css/propiedades.css') }}">
    <style>
        .property-control-module {
            --pc-surface: #ffffff;
            --pc-bg: #f5f7fb;
            --pc-ink: #172033;
            --pc-text: #334155;
            --pc-muted: #7b879d;
            --pc-line: #e5eaf3;
            --pc-accent: #b54708;
            --pc-accent-strong: #9a3412;
            --pc-accent-soft: #fff1e8;
            --pc-success: #15803d;
            --pc-success-soft: #edfdf3;
            --pc-danger: #c2410c;
            --pc-danger-soft: #fff3ee;
            --pc-warning: #b45309;
            --pc-warning-soft: #fff7e8;
            --pc-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            color: var(--pc-text);
        }

        .property-control-module .property-tabs-wrap {
            border-color: var(--pc-line);
            box-shadow: var(--pc-shadow);
        }

        .property-control-module .property-tabs-nav {
            gap: 12px;
        }

        .property-control-module .property-tabs-nav .nav-link {
            background: #f8fafc;
            color: var(--pc-text);
            border: 1px solid transparent;
            padding: 12px 18px;
        }

        .property-control-module .property-tabs-nav .nav-link:hover {
            background: var(--pc-accent-soft);
            color: var(--pc-accent);
            border-color: rgba(181, 71, 8, 0.15);
        }

        .property-control-module .property-tabs-nav .nav-link.active {
            background: var(--pc-accent);
            color: #fff !important;
            box-shadow: 0 12px 28px rgba(181, 71, 8, 0.22);
        }

        .property-control-module .property-tabs-nav__count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            border-radius: 999px;
            padding: 0 8px;
            background: rgba(15, 23, 42, 0.08);
            color: inherit;
            font-size: 12px;
            font-weight: 700;
        }

        .property-control-module .property-tabs-nav .nav-link.active .property-tabs-nav__count {
            background: rgba(255, 255, 255, 0.18);
        }

        .property-control-hero {
            border: 0;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 34%),
                linear-gradient(135deg, #111827 0%, #9a3412 100%);
            box-shadow: var(--pc-shadow);
        }

        .property-control-hero .card-body {
            position: relative;
        }

        .property-control-hero__eyebrow {
            color: var(--pc-muted);
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .property-control-hero__percent {
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
            padding: 10px 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.12);
            color: var(--pc-muted);
            line-height: 1;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        .property-control-hero__percent strong {
            font-size: clamp(2.6rem, 5vw, 4.35rem);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .property-control-hero__percent span {
            font-size: 1.25rem;
            font-weight: 700;
            opacity: 0.82;
        }

        .property-control-hero__description {
            color: var(--pc-muted);
            font-size: 1rem;
            max-width: 22rem;
        }

        .property-control-hero__ring {
            width: 108px;
            height: 108px;
            border-radius: 50%;
            border: 8px solid rgba(255, 255, 255, 0.18);
            border-top-color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            color: var(--pc-muted);
            font-weight: 800;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .property-control-summary-card {
            border: 1px solid var(--pc-line);
            border-radius: 22px;
            background: var(--pc-surface);
            box-shadow: var(--pc-shadow);
        }

        .property-control-summary-card.is-success {
            background: linear-gradient(180deg, #ffffff 0%, #f4fbf7 100%);
            border-color: rgba(21, 128, 61, 0.18);
        }

        .property-control-summary-card.is-danger {
            background: linear-gradient(180deg, #ffffff 0%, #fff7f2 100%);
            border-color: rgba(194, 65, 12, 0.18);
        }

        .property-control-summary-card__label {
            color: var(--pc-muted);
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .property-control-summary-card__value {
            color: var(--pc-ink);
            font-size: clamp(2rem, 3vw, 2.8rem);
            font-weight: 800;
            line-height: 1;
        }

        .property-control-summary-card__note {
            color: var(--pc-muted);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .property-control-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
        }

        .property-control-search {
            position: relative;
            min-width: min(100%, 360px);
            flex: 1 1 300px;
        }

        .property-control-search i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--pc-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .property-control-search .form-control {
            height: 52px;
            padding-left: 46px;
            border-radius: 16px;
            border: 1px solid var(--pc-line);
            background: #fbfcfe;
            color: var(--pc-ink);
            font-weight: 600;
            box-shadow: none;
        }

        .property-control-search .form-control:focus {
            border-color: rgba(181, 71, 8, 0.35);
            box-shadow: 0 0 0 4px rgba(181, 71, 8, 0.08);
        }

        .property-control-results {
            color: var(--pc-muted);
            font-size: 1rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .property-control-table-card {
            margin-top: 20px;
            border: 1px solid var(--pc-line);
            border-radius: 20px;
            overflow: hidden;
            background: var(--pc-surface);
        }

        .property-control-table-card .table-responsive {
            overflow-x: auto;
        }

        .property-control-table-card table.dataTable {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .property-control-table-card thead th {
            padding-top: 20px;
            padding-bottom: 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--pc-line) !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            letter-spacing: 0.08em;
        }

        .property-control-row {
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .property-control-row td {
            padding-top: 22px;
            padding-bottom: 22px;
            border-top: 1px solid var(--pc-line) !important;
            vertical-align: middle;
            background: #fff;
        }

        .property-control-row:hover td {
            background: #fcf8f6;
        }

        .property-control-row.is-expanded td {
            background: #fff8f2;
        }

        .property-control-property {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .property-control-expander {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: var(--pc-muted);
            border: 1px solid var(--pc-line);
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .property-control-row:hover .property-control-expander,
        .property-control-row.is-expanded .property-control-expander {
            color: var(--pc-accent);
            border-color: rgba(181, 71, 8, 0.18);
            background: var(--pc-accent-soft);
        }

        .property-control-row.is-expanded .property-control-expander i {
            transform: rotate(90deg);
        }

        .property-control-expander i {
            transition: transform 0.2s ease;
        }

        .property-control-property__title {
            color: var(--pc-ink);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .property-control-property__address {
            color: var(--pc-muted);
            font-size: 0.88rem;
            margin-top: 4px;
            line-height: 1.4;
        }

        .property-control-inline-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .property-control-inline-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f8fafc;
            color: var(--pc-text);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .property-control-party__label {
            color: var(--pc-muted);
            font-size: 0.73rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .property-control-party__value {
            color: var(--pc-ink);
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .property-control-party__subvalue {
            color: var(--pc-muted);
            font-size: 0.84rem;
            margin-top: 4px;
        }

        .property-control-party__value.is-missing {
            color: var(--pc-danger);
        }

        .property-control-progress__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .property-control-progress__fraction {
            color: var(--pc-ink);
            font-size: 1rem;
            font-weight: 800;
        }

        .property-control-progress .progress {
            height: 10px;
            border-radius: 999px;
            background: #edf2f7;
        }

        .property-control-progress .progress-bar {
            border-radius: inherit;
        }

        .property-control-progress__meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 8px;
            color: var(--pc-muted);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .property-control-progress__meta strong {
            color: var(--pc-text);
            font-size: 0.86rem;
        }

        .property-control-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .property-control-badge.is-success {
            color: var(--pc-success);
            background: var(--pc-success-soft);
        }

        .property-control-badge.is-warning {
            color: var(--pc-warning);
            background: var(--pc-warning-soft);
        }

        .property-control-badge.is-danger {
            color: var(--pc-danger);
            background: var(--pc-danger-soft);
        }

        .property-control-missing {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .property-control-missing__tag {
            display: inline-flex;
            align-items: center;
            padding: 7px 10px;
            border-radius: 10px;
            background: #fff7ed;
            color: var(--pc-danger);
            font-size: 0.76rem;
            font-weight: 700;
        }

        .property-control-missing__tag.is-clear {
            color: var(--pc-success);
            background: var(--pc-success-soft);
        }

        .property-control-actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .property-control-actions .btn {
            border-radius: 12px;
            font-weight: 700;
            min-width: 76px;
        }

        .property-control-child-row td {
            padding: 0 !important;
            border-top: 0 !important;
            background: #fffaf5 !important;
        }

        .property-control-child {
            padding: 0 28px 24px;
        }

        .property-control-detail {
            border: 1px solid rgba(181, 71, 8, 0.12);
            border-radius: 20px;
            background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
            padding: 24px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
        }

        .property-control-detail__head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .property-control-detail__eyebrow {
            color: var(--pc-accent);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .property-control-detail__title {
            color: var(--pc-ink);
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .property-control-detail__description {
            color: var(--pc-muted);
            max-width: 48rem;
            margin: 0;
        }

        .property-control-detail__stats {
            margin-bottom: 20px;
        }

        .property-control-detail-stat {
            height: 100%;
            border: 1px solid var(--pc-line);
            border-radius: 16px;
            background: #fff;
            padding: 18px;
        }

        .property-control-detail-stat span {
            display: block;
            color: var(--pc-muted);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .property-control-detail-stat strong {
            display: block;
            color: var(--pc-ink);
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .property-control-detail-stat small {
            display: block;
            color: var(--pc-muted);
            font-size: 0.85rem;
            margin-top: 8px;
        }

        .property-control-check {
            border: 1px solid transparent;
        }

        .property-control-check i {
            font-size: 1rem;
        }

        .property-control-check.is-complete {
            border-color: rgba(21, 128, 61, 0.08);
        }

        .property-control-check.is-missing {
            border-color: rgba(148, 163, 184, 0.14);
            color: var(--pc-text);
        }

        .property-control-pending-panel {
            margin-top: 20px;
            border-top: 1px solid var(--pc-line);
            padding-top: 18px;
        }

        .property-control-pending-panel__title {
            color: var(--pc-muted);
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .property-control-pending-panel__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .property-control-pending-panel__chips span {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(181, 71, 8, 0.12);
            color: var(--pc-accent-strong);
            font-size: 0.8rem;
            font-weight: 700;
        }

        .property-control-pending-panel__chips span.is-clear {
            border-color: rgba(21, 128, 61, 0.18);
            background: var(--pc-success-soft);
            color: var(--pc-success);
        }

        .property-control-table-card .dataTables_info,
        .property-control-table-card .dataTables_paginate {
            padding: 18px 28px 0;
            color: var(--pc-muted) !important;
            font-weight: 700;
        }

        .property-control-table-card .dataTables_paginate .pagination {
            gap: 6px;
        }

        .property-control-table-card .page-link {
            border-radius: 10px !important;
            border-color: var(--pc-line) !important;
            color: var(--pc-text) !important;
            min-width: 38px;
            text-align: center;
            font-weight: 700;
        }

        .property-control-table-card .page-item.active .page-link {
            background: var(--pc-accent) !important;
            border-color: var(--pc-accent) !important;
            color: #fff !important;
        }

        @media (max-width: 991px) {
            .property-control-hero__ring {
                width: 86px;
                height: 86px;
                font-size: 1rem;
            }

            .property-control-detail {
                padding: 18px;
            }

            .property-control-child {
                padding: 0 14px 16px;
            }

            .property-control-table-card .dataTables_info,
            .property-control-table-card .dataTables_paginate {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="py-10 property-control-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-8">
            <div>
                <h1 class="mb-1 fw-bold text-dark">Control de Alta de Propiedades</h1>
                <div class="text-muted fs-6">Seguimiento de configuración operativa y documental</div>
            </div>
            <div class="text-muted fw-semibold">{{ now()->translatedFormat('d M Y') }}</div>
        </div>

        <div class="row g-5 mb-8">
            <div class="col-xl-5">
                <div class="card h-100 property-control-hero">
                    <div class="card-body p-8 p-xl-10">
                        <div class="property-control-hero__eyebrow mb-4">Avance general del sistema</div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-6">
                            <div>
                                <div class="property-control-hero__percent mb-4">
                                    <strong>{{ $summary['overall_progress'] }}</strong>
                                    <span>%</span>
                                </div>
                                <div class="property-control-hero__description">
                                    {{ $summary['complete'] }} de {{ $summary['total'] }} propiedades ya están configuradas correctamente.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100 property-control-summary-card">
                    <div class="card-body p-7">
                        <div class="property-control-summary-card__label mb-3">Total propiedades</div>
                        <div class="property-control-summary-card__value">{{ $summary['total'] }}</div>
                        <div class="property-control-summary-card__note mt-3">Base operativa auditada</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-2">
                <div class="card h-100 property-control-summary-card is-success">
                    <div class="card-body p-7">
                        <div class="property-control-summary-card__label mb-3">Completas</div>
                        <div class="property-control-summary-card__value text-success">{{ $summary['complete'] }}</div>
                        <div class="property-control-summary-card__note mt-3">Sin pendientes críticos</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 property-control-summary-card is-danger">
                    <div class="card-body p-7">
                        <div class="property-control-summary-card__label mb-3">Incompletas</div>
                        <div class="property-control-summary-card__value text-danger">{{ $summary['incomplete'] }}</div>
                        <div class="property-control-summary-card__note mt-3">
                            {{ $summary['without_advisor'] }} propiedades aún sin asesor asignado
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="property-tabs-wrap">
            <div class="property-control-toolbar">
                <label class="property-control-search mb-0" for="property_control_search">
                    <i class="bi bi-search"></i>
                    <input
                        id="property_control_search"
                        type="search"
                        class="form-control"
                        value="{{ $filters['q'] }}"
                        placeholder="Buscar propiedad, dirección, asesor o inquilino..."
                        autocomplete="off">
                </label>

                <div id="propertyControlResultCount" class="property-control-results">{{ $resultCount }} resultados</div>
            </div>

            <ul class="nav property-tabs-nav" id="propertyControlStatusTabs" role="tablist">
                @foreach ($statusOptions as $value => $label)
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link {{ $filters['status'] === $value ? 'active' : '' }}"
                            type="button"
                            role="tab"
                            aria-selected="{{ $filters['status'] === $value ? 'true' : 'false' }}"
                            data-status-filter="{{ $value }}">
                            <span>{{ $label }}</span>
                            <span class="property-tabs-nav__count">{{ $statusCounts[$value] ?? 0 }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="property-tab-pane pt-6">
                <div class="property-control-table-card">
                    <div class="table-responsive">
                        <table id="property_control_table" class="table table-row-bordered align-middle mb-0">
                            <thead>
                                <tr class="fw-bold text-muted text-uppercase fs-8">
                                    <th class="ps-7 min-w-280px">Propiedad</th>
                                    <th class="min-w-220px">Responsables</th>
                                    <th class="min-w-240px">Avance</th>
                                    <th class="min-w-220px">Pendientes clave</th>
                                    <th class="min-w-160px text-end pe-7">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($snapshots as $row)
                                    @php
                                        $property = $row['property'];
                                        $rowFilters = ['all'];

                                        if ($row['is_complete']) {
                                            $rowFilters[] = 'complete';
                                        } else {
                                            $rowFilters[] = 'incomplete';
                                        }

                                        if (!($row['checks']['advisor'] ?? false)) {
                                            $rowFilters[] = 'no_advisor';
                                        }

                                        if (!($row['checks']['contract'] ?? false)) {
                                            $rowFilters[] = 'no_contract';
                                        }

                                        if (!($row['checks']['charges'] ?? false)) {
                                            $rowFilters[] = 'no_charges';
                                        }

                                        if ($row['has_dossier_gap']) {
                                            $rowFilters[] = 'no_dossier';
                                        }

                                        $progressBarClass = match ($row['status_tone']) {
                                            'success' => 'bg-success',
                                            'warning' => 'bg-warning',
                                            default => 'bg-danger',
                                        };

                                        $missingPreview = collect($row['missing_labels'])->take(2)->all();
                                        $remainingMissing = max(count($row['missing_labels']) - count($missingPreview), 0);
                                    @endphp
                                    <tr
                                        class="property-control-row"
                                        data-status-filters="{{ implode(' ', $rowFilters) }}"
                                        tabindex="0"
                                        role="button"
                                        aria-expanded="false">
                                        <td class="ps-7">
                                            <div class="property-control-property">
                                                <span class="property-control-expander">
                                                    <i class="bi bi-chevron-right"></i>
                                                </span>

                                                <div class="property-control-property__body">
                                                    <div class="property-control-property__title">{{ $property->internal_name }}</div>
                                                    <div class="property-control-property__address">{{ $property->full_address }}</div>

                                                    <div class="property-control-inline-meta">
                                                        @if ($property->internal_reference)
                                                            <span>
                                                                <i class="bi bi-hash"></i>
                                                                {{ $property->internal_reference }}
                                                            </span>
                                                        @endif

                                                        <span>
                                                            <i class="bi bi-person-badge"></i>
                                                            {{ $row['tenant_name'] ?: 'Sin inquilino' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="property-control-party__label">Asesor responsable</div>
                                            <div class="property-control-party__value {{ $row['advisor_name'] ? '' : 'is-missing' }}">
                                                {{ $row['advisor_name'] ?: 'Sin asesor asignado' }}
                                            </div>
                                            <div class="property-control-party__subvalue">
                                                {{ $property->advisor?->email ?: 'Revisa asignación operativa y seguimiento comercial' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="property-control-progress">
                                                <div class="property-control-progress__head">
                                                    <span class="property-control-progress__fraction">
                                                        {{ $row['completed_checks'] }}/{{ $row['total_checks'] }}
                                                    </span>
                                                    <span class="property-control-badge is-{{ $row['status_tone'] }}">
                                                        {{ $row['status_label'] }}
                                                    </span>
                                                </div>

                                                <div class="progress">
                                                    <div class="progress-bar {{ $progressBarClass }}" style="width: {{ $row['progress_percent'] }}%;"></div>
                                                </div>

                                                <div class="property-control-progress__meta">
                                                    <span>{{ $row['progress_percent'] }}% completado</span>
                                                    <strong>{{ count($row['missing_labels']) }} pendientes</strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="property-control-missing">
                                                @if (count($row['missing_labels']) === 0)
                                                    <span class="property-control-missing__tag is-clear">Sin pendientes</span>
                                                @else
                                                    @foreach ($missingPreview as $missing)
                                                        <span class="property-control-missing__tag">{{ $missing }}</span>
                                                    @endforeach

                                                    @if ($remainingMissing > 0)
                                                        <span class="property-control-missing__tag">+{{ $remainingMissing }} más</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end pe-7">
                                            <div class="property-control-actions">
                                                <a href="{{ route('properties.show', $property) }}"
                                                    class="btn btn-sm btn-primary js-property-control-action">
                                                    Ver
                                                </a>
                                                <a href="{{ route('properties.edit', $property) }}"
                                                    class="btn btn-sm btn-light-primary js-property-control-action">
                                                    Editar
                                                </a>
                                            </div>

                                            <script type="text/template" class="js-property-control-detail-template">
                                                <div class="property-control-detail">
                                                    <div class="property-control-detail__head">
                                                        <div>
                                                            <div class="property-control-detail__eyebrow">Checklist de configuración</div>
                                                            <div class="property-control-detail__title">{{ $property->internal_name }}</div>
                                                            <p class="property-control-detail__description">
                                                                @if (count($row['missing_labels']) === 0)
                                                                    La propiedad ya cumple con todos los requisitos operativos y documentales.
                                                                @else
                                                                    Faltan {{ count($row['missing_labels']) }} puntos para completar el alta correctamente.
                                                                @endif
                                                            </p>
                                                        </div>

                                                        <div class="property-control-actions">
                                                            <a href="{{ route('properties.show', $property) }}" class="btn btn-sm btn-primary js-property-control-action">Ver propiedad</a>
                                                            <a href="{{ route('properties.edit', $property) }}" class="btn btn-sm btn-light-primary js-property-control-action">Editar configuración</a>
                                                        </div>
                                                    </div>

                                                    <div class="row g-4 property-control-detail__stats">
                                                        <div class="col-md-4">
                                                            <div class="property-control-detail-stat">
                                                                <span>Avance</span>
                                                                <strong>{{ $row['progress_percent'] }}%</strong>
                                                                <small>{{ $row['completed_checks'] }} de {{ $row['total_checks'] }} puntos completos</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="property-control-detail-stat">
                                                                <span>Pendientes</span>
                                                                <strong>{{ count($row['missing_labels']) }}</strong>
                                                                <small>{{ count($row['missing_labels']) ? 'Aún requieren atención' : 'No hay pendientes activos' }}</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="property-control-detail-stat">
                                                                <span>Expedientes</span>
                                                                <strong>{{ $row['has_dossier_gap'] ? 'Incompleto' : 'Completo' }}</strong>
                                                                <small>Propiedad, propietario e inquilino</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-4">
                                                        @foreach ($checkLabels as $key => $label)
                                                            <div class="col-md-6 col-xl-4">
                                                                <div class="property-control-check {{ ($row['checks'][$key] ?? false) ? 'is-complete' : 'is-missing' }}">
                                                                    <i class="bi {{ ($row['checks'][$key] ?? false) ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                                                    <span>{{ $label }}</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <div class="property-control-pending-panel">
                                                        <div class="property-control-pending-panel__title">Pendientes detectados</div>
                                                        <div class="property-control-pending-panel__chips">
                                                            @forelse ($row['missing_labels'] as $missing)
                                                                <span>{{ $missing }}</span>
                                                            @empty
                                                                <span class="is-clear">Sin pendientes</span>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </div>
                                            </script>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" data-empty-row="true" class="text-center text-muted py-15">
                                            No hay propiedades disponibles para mostrar.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const tableElement = document.getElementById('property_control_table');
            const searchInput = document.getElementById('property_control_search');
            const resultCountElement = document.getElementById('propertyControlResultCount');
            const tabButtons = Array.from(document.querySelectorAll('#propertyControlStatusTabs [data-status-filter]'));

            if (!tableElement || typeof $ === 'undefined' || !$.fn.DataTable) {
                return;
            }

            const emptyCell = tableElement.querySelector('td[data-empty-row="true"]');
            if (emptyCell) {
                emptyCell.closest('tr')?.remove();
            }

            let activeStatus = @json($filters['status'] ?: 'all');
            let openRow = null;
            let searchDebounce = null;

            const updateUrlState = () => {
                const nextUrl = new URL(window.location.href);
                const searchValue = searchInput?.value.trim() || '';

                if (searchValue) {
                    nextUrl.searchParams.set('q', searchValue);
                } else {
                    nextUrl.searchParams.delete('q');
                }

                if (activeStatus && activeStatus !== 'all') {
                    nextUrl.searchParams.set('status', activeStatus);
                } else {
                    nextUrl.searchParams.delete('status');
                }

                window.history.replaceState({}, '', nextUrl);
            };

            const closeExpandedRow = () => {
                if (!openRow) {
                    return;
                }

                const rowApi = dataTable.row(openRow);
                if (rowApi?.child?.isShown()) {
                    rowApi.child.hide();
                }

                openRow.classList.remove('is-expanded');
                openRow.setAttribute('aria-expanded', 'false');
                openRow = null;
            };

            const syncResultCount = () => {
                if (!resultCountElement) {
                    return;
                }

                const count = dataTable.rows({ filter: 'applied' }).count();
                resultCountElement.textContent = `${count} resultados`;
            };

            const rowMatchesStatus = (rowNode) => {
                if (activeStatus === 'all') {
                    return true;
                }

                const filters = (rowNode?.dataset.statusFilters || '').split(/\s+/).filter(Boolean);
                return filters.includes(activeStatus);
            };

            $.fn.dataTable.ext.search.push((settings, data, dataIndex) => {
                if (settings.nTable !== tableElement) {
                    return true;
                }

                const rowNode = settings.aoData[dataIndex]?.nTr;
                return rowMatchesStatus(rowNode);
            });

            const dataTable = $(tableElement).DataTable({
                dom: "rt<'row align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
                pageLength: 10,
                lengthChange: false,
                ordering: false,
                info: true,
                searching: true,
                autoWidth: false,
                language: {
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ propiedades',
                    infoEmpty: 'Mostrando 0 a 0 de 0 propiedades',
                    paginate: {
                        first: 'Primera',
                        last: 'Última',
                        next: 'Siguiente',
                        previous: 'Anterior',
                    },
                    emptyTable: 'Aún no hay propiedades registradas.',
                    zeroRecords: 'No se encontraron coincidencias con este filtro.',
                },
                columnDefs: [
                    {
                        targets: [4],
                        orderable: false,
                        searchable: false,
                    },
                ],
            });

            const toggleRowDetail = (rowNode) => {
                if (!rowNode) {
                    return;
                }

                const rowApi = dataTable.row(rowNode);
                const template = rowNode.querySelector('.js-property-control-detail-template');
                if (!template) {
                    return;
                }

                if (rowApi.child.isShown()) {
                    rowApi.child.hide();
                    rowNode.classList.remove('is-expanded');
                    rowNode.setAttribute('aria-expanded', 'false');
                    openRow = null;
                    return;
                }

                closeExpandedRow();
                rowApi.child(`<div class="property-control-child">${template.innerHTML}</div>`, 'property-control-child-row').show();
                rowNode.classList.add('is-expanded');
                rowNode.setAttribute('aria-expanded', 'true');
                openRow = rowNode;
            };

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (activeStatus === button.dataset.statusFilter) {
                        return;
                    }

                    activeStatus = button.dataset.statusFilter || 'all';

                    tabButtons.forEach((tabButton) => {
                        const isActive = tabButton === button;
                        tabButton.classList.toggle('active', isActive);
                        tabButton.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    closeExpandedRow();
                    dataTable.draw();
                    syncResultCount();
                    updateUrlState();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    window.clearTimeout(searchDebounce);
                    searchDebounce = window.setTimeout(() => {
                        closeExpandedRow();
                        dataTable.search(searchInput.value).draw();
                        syncResultCount();
                        updateUrlState();
                    }, 120);
                });
            }

            tableElement.querySelector('tbody')?.addEventListener('click', (event) => {
                if (event.target.closest('.js-property-control-action')) {
                    return;
                }

                const rowNode = event.target.closest('tr.property-control-row');
                if (!rowNode) {
                    return;
                }

                toggleRowDetail(rowNode);
            });

            tableElement.querySelector('tbody')?.addEventListener('keydown', (event) => {
                const rowNode = event.target.closest('tr.property-control-row');
                if (!rowNode || event.target.closest('.js-property-control-action')) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggleRowDetail(rowNode);
                }
            });

            dataTable.on('draw', () => {
                closeExpandedRow();
                syncResultCount();
            });

            if (searchInput?.value) {
                dataTable.search(searchInput.value);
            }

            dataTable.draw();
            syncResultCount();
            updateUrlState();
        })();
    </script>
@endpush
