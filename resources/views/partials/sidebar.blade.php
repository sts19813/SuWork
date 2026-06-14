@php
    $user = Auth::user();
    $name = trim($user->name);
    $nameParts = collect(preg_split('/\s+/', $name ?: '', -1, PREG_SPLIT_NO_EMPTY));
    $firstName = $nameParts->first() ?: $name;
    $initials = $nameParts
        ->map(fn($word) => mb_substr($word, 0, 1))
        ->join('');
    $isTenant = $user->hasRole('inquilino') || $user->hasRole('tenant');
    $isTechnician = $user->hasRole('tecnico') || $user->hasRole('technician');
    $canManageAccess = $user->can('usuarios.gestionar') || $user->hasRole('administrador') || $user->hasRole('admin');
    $canViewPropertyControl = $user->can('propiedades.control_ver') || $user->hasRole('administrador') || $user->hasRole('admin');
    $homeRoute = ($isTenant || $isTechnician) ? 'maintenance.index' : 'dashboard';
    $roleLabel = $isTenant ? 'Panel de inquilino' : ($isTechnician ? 'Panel técnico' : 'Panel SuWork');
    $currentHour = now()->hour;
    $greeting = $currentHour < 12 ? 'Buenos días' : ($currentHour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $menuItems = $isTenant
        ? [
            ['patterns' => ['charges.*'], 'route' => 'charges.index', 'label' => 'Cobranza', 'icon' => 'bi-wallet2'],
            ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Mantenimiento', 'icon' => 'bi-tools'],
            ['patterns' => ['profile.*'], 'route' => 'profile.index', 'label' => 'Perfil', 'icon' => 'bi-person-circle'],
        ]
        : ($isTechnician
            ? [
                ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Mantenimiento', 'icon' => 'bi-tools'],
                ['patterns' => ['storage_items.*'], 'route' => 'storage_items.index', 'label' => 'Almacén', 'icon' => 'bi-box-seam'],
                ['patterns' => ['profile.*'], 'route' => 'profile.index', 'label' => 'Perfil', 'icon' => 'bi-person-circle'],
            ]
            : [
                ['patterns' => ['dashboard'], 'route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
                ...($canViewPropertyControl ? [['patterns' => ['properties.control'], 'route' => 'properties.control', 'label' => 'Control propiedades', 'icon' => 'bi-clipboard-data']] : []),
                ['patterns' => ['properties.index', 'properties.create', 'properties.show', 'properties.edit', 'properties.inventory.edit', 'inventory-checks.*'], 'route' => 'properties.index', 'label' => 'Propiedades', 'icon' => 'bi-house-door'],
                ['patterns' => ['owners.*', 'dossiers.owners.*'], 'route' => 'owners.index', 'label' => 'Propietarios', 'icon' => 'bi-person-vcard'],
                ['patterns' => ['tenants.*', 'dossiers.tenants.*'], 'route' => 'tenants.index', 'label' => 'Inquilinos', 'icon' => 'bi-people'],
                ['patterns' => ['documents.*', 'dossiers.properties.*'], 'route' => 'documents.index', 'label' => 'Documentos', 'icon' => 'bi-folder2-open'],
                ['patterns' => ['charges.*'], 'route' => 'charges.index', 'label' => 'Cobranza', 'icon' => 'bi-wallet2'],
                ['patterns' => ['expenses.*'], 'route' => 'expenses.index', 'label' => 'Gastos', 'icon' => 'bi-receipt'],
                ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Mantenimiento', 'icon' => 'bi-tools'],
                ['patterns' => ['storage_items.*'], 'route' => 'storage_items.index', 'label' => 'Almacén', 'icon' => 'bi-box-seam'],
                ...($canManageAccess ? [['patterns' => ['access.*'], 'route' => 'access.index', 'label' => 'Usuarios y permisos', 'icon' => 'bi-shield-lock']] : []),
                ['patterns' => ['profile.*'], 'route' => 'profile.index', 'label' => 'Perfil', 'icon' => 'bi-person-circle'],
            ]);

    $mobilePrimaryItems = $isTenant
        ? [
            ['patterns' => ['charges.*'], 'route' => 'charges.index', 'label' => 'Cobranza', 'icon' => 'bi-wallet2'],
            ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Tickets', 'icon' => 'bi-tools'],
        ]
        : ($isTechnician
            ? [
                ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Tickets', 'icon' => 'bi-tools'],
                ['patterns' => ['storage_items.*'], 'route' => 'storage_items.index', 'label' => 'Almacén', 'icon' => 'bi-box-seam'],
            ]
            : [
                ['patterns' => ['properties.index', 'properties.create', 'properties.show', 'properties.edit', 'properties.inventory.edit', 'inventory-checks.*', 'dashboard'], 'route' => 'properties.index', 'label' => 'Propiedades', 'icon' => 'bi-house-door'],
                ['patterns' => ['charges.*'], 'route' => 'charges.index', 'label' => 'Cobranza', 'icon' => 'bi-wallet2'],
                ['patterns' => ['maintenance.*'], 'route' => 'maintenance.index', 'label' => 'Tickets', 'icon' => 'bi-tools'],
            ]);

    $mobileSecondaryItems = collect($menuItems)
        ->reject(function ($item) use ($mobilePrimaryItems) {
            return collect($mobilePrimaryItems)->contains(fn($primaryItem) => $primaryItem['route'] === $item['route']);
        })
        ->values();

    $currentSection = collect($menuItems)
        ->first(fn($item) => request()->routeIs(...$item['patterns']))['label'] ?? 'Tu espacio';

    $isMobileMoreActive = $mobileSecondaryItems->contains(
        fn($item) => request()->routeIs(...$item['patterns'])
    );
@endphp

<div class="su-mobile-topbar">
    <div class="su-mobile-topbar__content">
        <div class="su-mobile-topbar__copy">
            <span class="su-mobile-topbar__eyebrow">{{ $greeting }}, {{ $firstName }}</span>
            <strong class="su-mobile-topbar__title">{{ $currentSection }}</strong>
            <span class="su-mobile-topbar__subtitle">{{ $roleLabel }}</span>
        </div>

        <div class="su-mobile-topbar__actions">
            <button type="button" class="su-mobile-icon-btn is-disabled" aria-label="Notificaciones" disabled>
                <i class="bi bi-bell"></i>
            </button>

            <div class="dropdown">
                <button type="button" class="su-mobile-avatar" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir menú de perfil">
                    @if ($user->profile_photo)
                        <img src="{{ asset($user->profile_photo) }}" alt="user">
                    @else
                        <span>{{ $initials }}</span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm su-mobile-profile-menu">
                    <div class="px-4 py-3 border-bottom d-flex align-items-center">
                        <div class="symbol symbol-45px me-3">
                            @if ($user->profile_photo)
                                <img src="{{ asset($user->profile_photo) }}" class="symbol-label" alt="avatar">
                            @else
                                <div class="symbol-label fw-bold d-flex justify-content-center align-items-center text-white bg-primary">
                                    {{ $initials }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="fw-bold text-truncate">{{ $user->name }}</div>
                            <div class="text-muted small text-truncate">{{ $user->email }}</div>
                        </div>
                    </div>

                    <a href="{{ route('profile.index') }}" class="dropdown-item px-4 py-3">Mi perfil</a>

                    <div class="dropdown-divider my-0"></div>

                    <a href="#" class="dropdown-item text-danger px-4 py-3"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ki-outline ki-exit-right me-2"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<aside id="kt_app_sidebar" class="app-sidebar su-admin-sidebar">
    <div id="kt_app_sidebar_wrapper" class="app-sidebar-wrapper">
        <div class="sidebar-shell">
            <div class="sidebar-brand">
                <button id="kt_app_sidebar_toggle"
                    type="button"
                    class="sidebar-brand-toggle app-sidebar-toggle d-none d-lg-inline-flex"
                    data-kt-toggle="true"
                    data-kt-toggle-state="active"
                    data-kt-toggle-target="body"
                    data-kt-toggle-name="app-sidebar-minimize"
                    aria-label="Contraer menú">
                    <i class="ki-outline ki-menu fs-3"></i>
                </button>

                <a href="{{ route($homeRoute) }}" class="sidebar-brand-link text-decoration-none">
                    <span class="sidebar-brand-mark">SW</span>
                    <span class="sidebar-brand-wordmark">SuWork</span>
                </a>
            </div>

            <div class="sidebar-scroll">
                <div id="kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false"
                    class="app-sidebar-menu-primary menu menu-column">
                    <div class="menu-item pt-3">
                        <div class="menu-content">
                            <span class="menu-heading fw-bold text-uppercase fs-8">{{ $roleLabel }}</span>
                        </div>
                    </div>

                    @foreach ($menuItems as $item)
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs(...$item['patterns']) ? 'active' : '' }}"
                                href="{{ route($item['route']) }}">
                                <span class="menu-icon"><i class="bi {{ $item['icon'] }} fs-2"></i></span>
                                <span class="menu-title">{{ $item['label'] }}</span>
                            </a>
                            <div class="sidebar-hover-card">
                                <a href="{{ route($item['route']) }}" class="sidebar-hover-title">{{ $item['label'] }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="kt_app_sidebar_footer" class="app-sidebar-footer">
                <div class="sidebar-user-card">
                    <button type="button" class="sidebar-user-menu-trigger symbol symbol-circle border-0 p-0"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        aria-label="Abrir menú de perfil">
                        @if ($user->profile_photo)
                            <img src="{{ asset($user->profile_photo) }}" alt="{{ $user->name }}" class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                        @else
                            <span class="symbol-label bg-primary text-white fw-bold w-100 h-100 d-flex align-items-center justify-content-center">{{ $initials }}</span>
                        @endif
                    </button>

                    <div class="sidebar-user-details flex-grow-1">
                        <div class="sidebar-user-name text-truncate">{{ $user->name }}</div>
                        <div class="sidebar-user-email text-truncate">{{ $user->email }}</div>
                    </div>

                    <div class="sidebar-user-actions d-flex align-items-center gap-2">
                        <a href="{{ route('profile.index') }}"
                            class="sidebar-user-action"
                            aria-label="Mi perfil">
                            <i class="ki-outline ki-setting-4 fs-5"></i>
                        </a>
                        <a href="#"
                            class="sidebar-user-action is-danger"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            aria-label="Cerrar sesión">
                            <i class="ki-outline ki-exit-right fs-5"></i>
                        </a>
                    </div>

                    <div class="dropdown-menu p-0 shadow-sm sidebar-user-dropdown">
                        <div class="px-4 py-3 border-bottom d-flex align-items-center">
                            <div class="symbol symbol-45px me-3">
                                @if ($user->profile_photo)
                                    <img src="{{ asset($user->profile_photo) }}" class="symbol-label" alt="avatar">
                                @else
                                    <div class="symbol-label fw-bold d-flex justify-content-center align-items-center text-white bg-primary">
                                        {{ $initials }}
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold text-truncate">{{ $user->name }}</div>
                                <div class="text-muted small text-truncate">{{ $user->email }}</div>
                            </div>
                        </div>

                        <a href="{{ route('profile.index') }}" class="dropdown-item px-4 py-3">Mi perfil</a>
                        <button type="button" class="dropdown-item px-4 py-3" data-sidebar-theme-toggle>Modo</button>

                        <div class="dropdown-divider my-0"></div>

                        <a href="#" class="dropdown-item text-danger px-4 py-3"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="ki-outline ki-exit-right me-2"></i> Cerrar sesión
                        </a>
                    </div>

                    <div class="sidebar-user-hover-card">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="symbol symbol-45px">
                                @if ($user->profile_photo)
                                    <img src="{{ asset($user->profile_photo) }}" alt="{{ $user->name }}" class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                                @else
                                    <div class="symbol-label bg-primary text-white fw-bold fs-5">{{ $initials }}</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold text-gray-900 text-truncate">{{ $user->name }}</div>
                                <div class="text-muted fs-8 text-truncate">{{ $user->email }}</div>
                            </div>
                        </div>
                        <a href="{{ route('profile.index') }}" class="sidebar-hover-link">Mi perfil</a>
                        <button type="button" class="sidebar-hover-link sidebar-hover-button" data-sidebar-theme-toggle>
                            Modo
                        </button>
                        <button type="button" class="sidebar-hover-link sidebar-hover-button text-danger"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>

<div class="su-mobile-tabbar">
    <div class="su-mobile-tabbar__inner" style="--su-mobile-tab-count: {{ count($mobilePrimaryItems) + 1 }};">
        @foreach ($mobilePrimaryItems as $item)
            <a href="{{ route($item['route']) }}"
                class="su-mobile-tabbar__item {{ request()->routeIs(...$item['patterns']) ? 'is-active' : '' }}">
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <button type="button" class="su-mobile-tabbar__item {{ $isMobileMoreActive ? 'is-active' : '' }}"
            data-bs-toggle="offcanvas" data-bs-target="#suMobileMoreMenu" aria-controls="suMobileMoreMenu">
            <i class="bi bi-grid"></i>
            <span>Más</span>
        </button>
    </div>
</div>

<div class="offcanvas offcanvas-bottom su-mobile-more-sheet" tabindex="-1" id="suMobileMoreMenu"
    aria-labelledby="suMobileMoreMenuLabel">
    <div class="offcanvas-header">
        <div>
            <div class="su-mobile-more-sheet__eyebrow">{{ $roleLabel }}</div>
            <h5 class="offcanvas-title mb-0" id="suMobileMoreMenuLabel">Accesos</h5>
        </div>
        <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="offcanvas" aria-label="Cerrar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="offcanvas-body pt-0">
        <div class="su-mobile-sheet-links">
            @foreach ($mobileSecondaryItems as $item)
                <a href="{{ route($item['route']) }}"
                    class="su-mobile-sheet-link {{ request()->routeIs(...$item['patterns']) ? 'is-active' : '' }}">
                    <span class="su-mobile-sheet-link__icon">
                        <i class="bi {{ $item['icon'] ?? 'bi-circle' }}"></i>
                    </span>
                    <span class="su-mobile-sheet-link__label">{{ $item['label'] }}</span>
                    <i class="bi bi-chevron-right su-mobile-sheet-link__arrow"></i>
                </a>
            @endforeach

            <a href="#" class="su-mobile-sheet-link text-danger"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <span class="su-mobile-sheet-link__icon">
                    <i class="bi bi-box-arrow-right"></i>
                </span>
                <span class="su-mobile-sheet-link__label">Cerrar sesión</span>
                <i class="bi bi-chevron-right su-mobile-sheet-link__arrow"></i>
            </a>
        </div>
    </div>
</div>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
