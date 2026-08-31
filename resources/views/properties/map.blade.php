@extends('layouts.app')

@section('title', 'Mapa | SuWork')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .property-map-module {
            --map-border: #e5e7eb;
            --map-muted: #6b7280;
            --map-ink: #111827;
            --map-panel: #ffffff;
            --map-soft: #f8fafc;
            padding: 24px 0 10px;
        }

        .property-map-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        .property-map-title {
            margin: 0;
            color: var(--map-ink);
            font-size: 1.85rem;
            font-weight: 800;
        }

        .property-map-subtitle {
            color: var(--map-muted);
            font-size: .95rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .property-map-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .property-map-stat {
            min-width: 118px;
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: var(--map-panel);
            padding: 12px 14px;
            text-align: right;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
        }

        .property-map-stat strong {
            display: block;
            color: var(--map-ink);
            font-size: 1.25rem;
            line-height: 1;
        }

        .property-map-stat span {
            display: block;
            color: var(--map-muted);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .02em;
            margin-top: 6px;
            text-transform: uppercase;
        }

        .property-map-workspace {
            position: relative;
            min-height: 720px;
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: var(--map-soft);
            overflow: hidden;
            box-shadow: 0 16px 36px rgba(15, 23, 42, .06);
        }

        .property-map-toolbar {
            position: absolute;
            z-index: 1100;
            top: 16px;
            left: 16px;
            right: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            pointer-events: none;
        }

        .property-map-search,
        .property-map-filters,
        .property-map-results {
            pointer-events: auto;
            border: 1px solid rgba(226, 232, 240, .95);
            background: rgba(255, 255, 255, .94);
            backdrop-filter: blur(14px);
            box-shadow: 0 14px 32px rgba(15, 23, 42, .10);
        }

        .property-map-search {
            width: min(390px, 36vw);
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 8px;
            padding: 0 12px;
        }

        .property-map-search i {
            color: #64748b;
            font-size: 1rem;
        }

        .property-map-search input {
            width: 100%;
            height: 44px;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--map-ink);
            font-weight: 600;
        }

        .property-map-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            border-radius: 8px;
            padding: 6px;
        }

        .property-map-filter {
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #475569;
            font-size: .78rem;
            font-weight: 800;
            padding: 8px 10px;
            white-space: nowrap;
        }

        .property-map-filter.active {
            background: #111827;
            color: #ffffff;
        }

        .property-map-results {
            margin-left: auto;
            border-radius: 8px;
            color: #334155;
            font-size: .78rem;
            font-weight: 800;
            padding: 12px 14px;
            white-space: nowrap;
        }

        #properties-map {
            width: 100%;
            min-height: 720px;
            height: calc(100vh - 210px);
        }

        .su-map-marker {
            position: relative;
            width: 22px;
            height: 22px;
            border: 3px solid #ffffff;
            border-radius: 999px;
            background: #64748b;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .28);
        }

        .su-map-marker-shell {
            background: transparent;
            border: 0;
        }

        .su-map-marker::after {
            position: absolute;
            inset: -9px;
            border: 2px solid currentColor;
            border-radius: 999px;
            content: "";
            opacity: .18;
        }

        .su-map-marker.available { background: #f59e0b; color: #f59e0b; }
        .su-map-marker.occupied,
        .su-map-marker.rented { background: #16a34a; color: #16a34a; }
        .su-map-marker.blocked { background: #dc2626; color: #dc2626; }
        .su-map-marker.in_process { background: #0284c7; color: #0284c7; }
        .su-map-marker.draft { background: #64748b; color: #64748b; }

        .property-map-popup {
            width: 245px;
        }

        .property-map-popup img {
            width: 100%;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
        }

        .property-map-popup-title {
            color: var(--map-ink);
            font-size: .98rem;
            font-weight: 800;
            margin-top: 10px;
        }

        .property-map-popup-meta {
            color: var(--map-muted);
            font-size: .78rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .property-map-popup-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .property-map-drawer {
            position: absolute;
            z-index: 1200;
            top: 0;
            right: 0;
            width: min(430px, 100%);
            height: 100%;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            border-left: 1px solid var(--map-border);
            box-shadow: -18px 0 38px rgba(15, 23, 42, .14);
            transform: translateX(104%);
            transition: transform .22s ease;
        }

        .property-map-drawer.is-open {
            transform: translateX(0);
        }

        .property-map-drawer__image {
            position: relative;
            height: 210px;
            background: #e5e7eb;
        }

        .property-map-drawer__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .property-map-drawer__close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 999px;
            background: rgba(15, 23, 42, .78);
            color: #ffffff;
            display: grid;
            place-items: center;
        }

        .property-map-drawer__body {
            min-height: 0;
            flex: 1;
            overflow: auto;
            padding: 22px;
        }

        .property-map-drawer__title {
            color: var(--map-ink);
            font-size: 1.35rem;
            font-weight: 850;
            line-height: 1.2;
            margin: 0;
        }

        .property-map-drawer__address {
            color: var(--map-muted);
            font-size: .88rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .property-map-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin: 20px 0;
        }

        .property-map-step {
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: #ffffff;
            color: #475569;
            font-size: .78rem;
            font-weight: 800;
            padding: 10px 8px;
        }

        .property-map-step.active {
            border-color: #111827;
            background: #111827;
            color: #ffffff;
        }

        .property-map-section {
            display: none;
        }

        .property-map-section.active {
            display: block;
        }

        .property-map-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .property-map-info {
            border: 1px solid var(--map-border);
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px;
        }

        .property-map-info span {
            display: block;
            color: #64748b;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .property-map-info strong {
            display: block;
            color: var(--map-ink);
            font-size: .94rem;
            font-weight: 800;
            margin-top: 5px;
            word-break: break-word;
        }

        .property-map-owner-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .property-map-owner-list span {
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
            font-size: .78rem;
            font-weight: 800;
            padding: 7px 10px;
        }

        .property-map-empty {
            position: absolute;
            z-index: 1250;
            inset: 0;
            display: grid;
            place-items: center;
            background: #f8fafc;
            text-align: center;
            padding: 24px;
        }

        .property-map-empty[hidden] {
            display: none;
        }

        .property-map-spinner {
            width: 38px;
            height: 38px;
            margin: 0 auto 14px;
            border: 4px solid #dbeafe;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: property-map-spin .8s linear infinite;
        }

        @keyframes property-map-spin {
            to { transform: rotate(360deg); }
        }

        .leaflet-container {
            font: inherit;
        }

        .property-map-empty i {
            color: #94a3b8;
            font-size: 3rem;
        }

        .property-map-empty h2 {
            color: var(--map-ink);
            font-size: 1.2rem;
            font-weight: 800;
            margin: 12px 0 6px;
        }

        @media (max-width: 991.98px) {
            .property-map-head,
            .property-map-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .property-map-stats {
                justify-content: stretch;
            }

            .property-map-stat {
                flex: 1;
                text-align: left;
            }

            .property-map-toolbar {
                position: static;
                padding: 12px;
                background: #ffffff;
                border-bottom: 1px solid var(--map-border);
            }

            .property-map-search {
                width: 100%;
            }

            .property-map-results {
                margin-left: 0;
            }

            #properties-map {
                height: 640px;
                min-height: 640px;
            }

            .property-map-workspace {
                min-height: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="property-map-module">
        <div class="property-map-head">
            <div>
                <h1 class="property-map-title">Mapa</h1>
                <div class="property-map-subtitle">Vista geografica de propiedades con link de ubicacion registrado.</div>
            </div>

            <div class="property-map-stats">
                <div class="property-map-stat">
                    <strong data-map-pin-count>{{ $markers->count() }}</strong>
                    <span>Con pin</span>
                </div>
                <div class="property-map-stat">
                    <strong data-map-pending-count>{{ $pendingCoordinatesCount }}</strong>
                    <span>Por resolver</span>
                </div>
                <div class="property-map-stat">
                    <strong>{{ $totalWithMapUrl }}</strong>
                    <span>Con link</span>
                </div>
            </div>
        </div>

        <div class="property-map-workspace" data-property-map-module>
            <div class="property-map-toolbar">
                <label class="property-map-search" for="propertyMapSearch">
                    <i class="bi bi-search"></i>
                    <input id="propertyMapSearch" type="search" autocomplete="off"
                        placeholder="Buscar propiedad, zona, inquilino...">
                </label>

                <div class="property-map-filters" aria-label="Filtrar por estatus">
                    <button type="button" class="property-map-filter active" data-status-filter="all">Todos</button>
                    <button type="button" class="property-map-filter" data-status-filter="available">Disponibles</button>
                    <button type="button" class="property-map-filter" data-status-filter="occupied">Ocupadas</button>
                    <button type="button" class="property-map-filter" data-status-filter="blocked">Bloqueadas</button>
                    <button type="button" class="property-map-filter" data-status-filter="in_process">En proceso</button>
                </div>

                <div class="property-map-results" data-map-result-count>{{ $markers->count() }} propiedades</div>
            </div>

            <div id="properties-map"></div>

            <div class="property-map-empty" data-map-empty hidden>
                <div>
                    <i class="bi bi-map"></i>
                    <h2 data-map-empty-title>No hay coordenadas disponibles</h2>
                    <p class="text-muted fw-semibold mb-0" data-map-empty-message>Agrega o corrige los links de ubicación de las propiedades.</p>
                </div>
            </div>

            <div class="property-map-empty" data-map-sync hidden>
                <div>
                    <div class="property-map-spinner"></div>
                    <h2 data-map-sync-title>Preparando ubicaciones</h2>
                    <p class="text-muted fw-semibold mb-0" data-map-sync-message>Buscando coordenadas para las propiedades nuevas.</p>
                </div>
            </div>

            <aside class="property-map-drawer" data-map-drawer aria-live="polite">
                <div class="property-map-drawer__image">
                    <img src="" alt="" data-drawer-photo>
                    <button type="button" class="property-map-drawer__close" data-drawer-close aria-label="Cerrar detalle">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="property-map-drawer__body">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                        <div>
                            <h2 class="property-map-drawer__title" data-drawer-name></h2>
                            <div class="property-map-drawer__address" data-drawer-address></div>
                        </div>
                        <span class="badge" data-drawer-status></span>
                    </div>

                    <div class="property-map-steps">
                        <button type="button" class="property-map-step active" data-detail-step="summary">Resumen</button>
                        <button type="button" class="property-map-step" data-detail-step="operation">Operacion</button>
                        <button type="button" class="property-map-step" data-detail-step="location">Ubicacion</button>
                    </div>

                    <section class="property-map-section active" data-detail-section="summary">
                        <div class="property-map-info-grid">
                            <div class="property-map-info">
                                <span>Tipo</span>
                                <strong data-drawer-type></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Zona</span>
                                <strong data-drawer-zone></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Renta</span>
                                <strong data-drawer-rent></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Referencia</span>
                                <strong data-drawer-reference></strong>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="text-muted fw-bold fs-8 text-uppercase mb-2">Propietarios</div>
                            <div class="property-map-owner-list" data-drawer-owners></div>
                        </div>
                    </section>

                    <section class="property-map-section" data-detail-section="operation">
                        <div class="property-map-info-grid">
                            <div class="property-map-info">
                                <span>Inquilino</span>
                                <strong data-drawer-tenant></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Asesor</span>
                                <strong data-drawer-advisor></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Cargos pendientes</span>
                                <strong data-drawer-charges></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Tickets abiertos</span>
                                <strong data-drawer-tickets></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Inicio contrato</span>
                                <strong data-drawer-contract-start></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Fin contrato</span>
                                <strong data-drawer-contract-end></strong>
                            </div>
                        </div>
                    </section>

                    <section class="property-map-section" data-detail-section="location">
                        <div class="property-map-info-grid">
                            <div class="property-map-info">
                                <span>Latitud</span>
                                <strong data-drawer-latitude></strong>
                            </div>
                            <div class="property-map-info">
                                <span>Longitud</span>
                                <strong data-drawer-longitude></strong>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <a href="#" target="_blank" rel="noopener" class="btn btn-light-primary fw-bold" data-drawer-map-url>
                                <i class="bi bi-geo-alt me-2"></i> Abrir ubicacion
                            </a>
                            <a href="#" class="btn btn-primary fw-bold" data-drawer-show-url>
                                <i class="bi bi-house-door me-2"></i> Ver propiedad
                            </a>
                            <a href="#" class="btn btn-light fw-bold" data-drawer-edit-url>
                                <i class="bi bi-pencil-square me-2"></i> Editar propiedad
                            </a>
                        </div>
                    </section>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        (() => {
            const properties = @json($markers);
            const initialPendingCount = Number(@json($pendingCoordinatesCount));
            const syncUrl = @json(route('properties.map.sync-pending'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const defaultCenter = [20.9674, -89.5926];
            const mapElement = document.getElementById('properties-map');

            if (!mapElement || typeof L === 'undefined') {
                return;
            }

            const statusMeta = {
                draft: { label: 'Borrador' },
                available: { label: 'Disponible' },
                in_process: { label: 'En proceso' },
                blocked: { label: 'Bloqueada' },
                occupied: { label: 'Ocupada' },
                rented: { label: 'Rentada' },
            };

            const map = L.map(mapElement, {
                zoomControl: true,
            }).setView(defaultCenter, 12);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);

            const markerLayer = L.layerGroup().addTo(map);
            const markerById = new Map();
            const searchInput = document.getElementById('propertyMapSearch');
            const resultCount = document.querySelector('[data-map-result-count]');
            const pinCount = document.querySelector('[data-map-pin-count]');
            const pendingCount = document.querySelector('[data-map-pending-count]');
            const filterButtons = Array.from(document.querySelectorAll('[data-status-filter]'));
            const drawer = document.querySelector('[data-map-drawer]');
            const emptyState = document.querySelector('[data-map-empty]');
            const syncState = document.querySelector('[data-map-sync]');
            let activeStatus = 'all';

            function text(value) {
                return String(value || '').trim();
            }

            function escapeHtml(value) {
                return text(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function searchableText(property) {
                return [
                    property.name,
                    property.reference,
                    property.address,
                    property.zone,
                    property.type,
                    property.tenant,
                    property.advisor,
                    ...(property.owner_names || []),
                    property.status_label,
                ].map(text).join(' ').toLowerCase();
            }

            function popupFor(property) {
                const statusLabel = statusMeta[property.status]?.label || property.status_label || 'Sin estatus';

                return `
                    <div class="property-map-popup">
                        <img src="${escapeHtml(property.photo_url)}" alt="${escapeHtml(property.name)}">
                        <div class="property-map-popup-title">${escapeHtml(property.name)}</div>
                        <div class="property-map-popup-meta">${escapeHtml(property.zone || property.address || '-')}</div>
                        <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
                            <span class="badge ${escapeHtml(property.status_badge_class || 'badge-light-secondary')}">${escapeHtml(statusLabel)}</span>
                            <a href="${escapeHtml(property.show_url)}" class="btn btn-sm btn-primary">Ir</a>
                        </div>
                    </div>
                `;
            }

            function withDuplicateOffset(items) {
                const seen = new Map();

                return items.map(function (property) {
                    const key = `${Number(property.latitude).toFixed(6)},${Number(property.longitude).toFixed(6)}`;
                    const index = seen.get(key) || 0;
                    seen.set(key, index + 1);

                    if (index === 0) {
                        return { ...property, displayLatitude: property.latitude, displayLongitude: property.longitude };
                    }

                    const angle = index * 0.85;
                    const radius = 0.00009 * Math.ceil(index / 6);

                    return {
                        ...property,
                        displayLatitude: Number(property.latitude) + Math.sin(angle) * radius,
                        displayLongitude: Number(property.longitude) + Math.cos(angle) * radius,
                    };
                });
            }

            function filteredProperties() {
                const query = text(searchInput?.value).toLowerCase();

                return properties.filter(function (property) {
                    const statusMatches = activeStatus === 'all' || property.status === activeStatus;
                    const textMatches = query === '' || searchableText(property).includes(query);

                    return statusMatches && textMatches;
                });
            }

            function renderMarkers(fitBounds = false) {
                const visible = withDuplicateOffset(filteredProperties());
                markerLayer.clearLayers();
                markerById.clear();
                const bounds = L.latLngBounds();

                visible.forEach(function (property) {
                    const marker = L.marker([Number(property.displayLatitude), Number(property.displayLongitude)], {
                        title: property.name,
                        icon: L.divIcon({
                            className: 'su-map-marker-shell',
                            html: `<div class="su-map-marker ${escapeHtml(property.status)}"></div>`,
                            iconSize: [22, 22],
                            iconAnchor: [11, 11],
                        }),
                    }).bindPopup(popupFor(property));

                    marker.on('click', function () {
                        openDrawer(property);
                    });
                    marker.addTo(markerLayer);
                    bounds.extend(marker.getLatLng());
                    markerById.set(property.id, marker);
                });

                if (resultCount) {
                    resultCount.textContent = `${visible.length} ${visible.length === 1 ? 'propiedad' : 'propiedades'}`;
                }

                if (fitBounds && visible.length > 0) {
                    map.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });
                } else if (visible.length === 0) {
                    closeDrawer();
                }
            }

            function setText(selector, value, fallback = '-') {
                const element = document.querySelector(selector);

                if (element) {
                    element.textContent = text(value) || fallback;
                }
            }

            function setLink(selector, href) {
                const element = document.querySelector(selector);

                if (element) {
                    element.setAttribute('href', href || '#');
                }
            }

            function openDrawer(property) {
                if (!drawer) {
                    return;
                }

                const photo = drawer.querySelector('[data-drawer-photo]');
                const status = drawer.querySelector('[data-drawer-status]');
                const owners = drawer.querySelector('[data-drawer-owners]');

                if (photo) {
                    photo.src = property.photo_url;
                    photo.alt = property.name;
                }

                if (status) {
                    status.className = `badge ${property.status_badge_class || 'badge-light-secondary'}`;
                    status.textContent = property.status_label || 'Sin estatus';
                }

                setText('[data-drawer-name]', property.name);
                setText('[data-drawer-address]', property.address);
                setText('[data-drawer-type]', property.type);
                setText('[data-drawer-zone]', property.zone);
                setText('[data-drawer-rent]', property.rent ? `$${property.rent}` : null);
                setText('[data-drawer-reference]', property.reference);
                setText('[data-drawer-tenant]', property.tenant);
                setText('[data-drawer-advisor]', property.advisor);
                setText('[data-drawer-charges]', property.pending_charges_count);
                setText('[data-drawer-tickets]', property.open_tickets_count);
                setText('[data-drawer-contract-start]', property.contract_starts_at);
                setText('[data-drawer-contract-end]', property.contract_expires_at);
                setText('[data-drawer-latitude]', Number(property.latitude).toFixed(7));
                setText('[data-drawer-longitude]', Number(property.longitude).toFixed(7));
                setLink('[data-drawer-map-url]', property.map_url);
                setLink('[data-drawer-show-url]', property.show_url);
                setLink('[data-drawer-edit-url]', property.edit_url);

                if (owners) {
                    owners.innerHTML = '';
                    const names = property.owner_names && property.owner_names.length ? property.owner_names : ['Sin propietario'];
                    names.forEach(function (name) {
                        const item = document.createElement('span');
                        item.textContent = name;
                        owners.appendChild(item);
                    });
                }

                drawer.classList.add('is-open');
            }

            function closeDrawer() {
                if (drawer) {
                    drawer.classList.remove('is-open');
                }
            }

            function showSync(visible, title, message) {
                if (!syncState) {
                    return;
                }

                syncState.hidden = !visible;
                syncState.querySelector('[data-map-sync-title]').textContent = title;
                syncState.querySelector('[data-map-sync-message]').textContent = message;
            }

            function showEmpty(visible, title, message) {
                if (!emptyState) {
                    return;
                }

                emptyState.hidden = !visible;
                emptyState.querySelector('[data-map-empty-title]').textContent = title;
                emptyState.querySelector('[data-map-empty-message]').textContent = message;
            }

            function updateCounts(remaining) {
                if (pinCount) pinCount.textContent = properties.length;
                if (pendingCount) pendingCount.textContent = remaining;
            }

            async function syncPendingLocations() {
                let remaining = initialPendingCount;
                if (remaining === 0) {
                    showEmpty(properties.length === 0, 'No hay coordenadas disponibles', 'Agrega un link de ubicación a una propiedad para verla aquí.');
                    return;
                }

                showSync(true, 'Preparando ubicaciones', `Buscando coordenadas para ${remaining} ${remaining === 1 ? 'propiedad nueva' : 'propiedades nuevas'}.`);

                try {
                    while (remaining > 0) {
                        const response = await fetch(syncUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                        });
                        if (!response.ok) {
                            throw new Error('No se pudieron actualizar las ubicaciones.');
                        }

                        const result = await response.json();
                        properties.push(...(result.markers || []));
                        remaining = Number(result.remaining || 0);
                        updateCounts(remaining);
                        renderMarkers(properties.length === (result.markers || []).length);

                        if (remaining > 0) {
                            showSync(true, 'Preparando ubicaciones', `Aún faltan ${remaining} ${remaining === 1 ? 'ubicación' : 'ubicaciones'}.`);
                        }
                        if (Number(result.processed || 0) === 0) {
                            break;
                        }
                    }

                    showSync(false, '', '');
                    showEmpty(properties.length === 0, 'No hay coordenadas disponibles', 'No fue posible obtener coordenadas de los links registrados.');
                } catch (error) {
                    showSync(false, '', '');
                    showEmpty(properties.length === 0, 'No se pudieron cargar las ubicaciones', 'Intenta recargar la página en unos momentos.');
                }
            }

            filterButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    activeStatus = button.dataset.statusFilter || 'all';
                    filterButtons.forEach((item) => item.classList.toggle('active', item === button));
                    renderMarkers(false);
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => renderMarkers(false));
            }

            document.addEventListener('click', function (event) {
                if (event.target.closest('[data-drawer-close]')) closeDrawer();

                const stepButton = event.target.closest('[data-detail-step]');
                if (stepButton) {
                    const step = stepButton.dataset.detailStep;
                    document.querySelectorAll('[data-detail-step]').forEach((button) => button.classList.toggle('active', button === stepButton));
                    document.querySelectorAll('[data-detail-section]').forEach((section) => section.classList.toggle('active', section.dataset.detailSection === step));
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeDrawer();
            });

            renderMarkers(properties.length > 0);
            syncPendingLocations();
        })();
    </script>
@endpush
