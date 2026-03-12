@php
    $user = Auth::user();
    $name = $user->name;
    $initials = collect(explode(' ', $name))
        ->map(fn($word) => mb_substr($word, 0, 1))
        ->join('');
@endphp

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-0">
    <div class="container-fluid px-4">
        <a href="{{ route('properties.index') }}" class="d-flex align-items-center py-2 me-8">
            <img src="{{ asset('assets/img/Logo.png') }}" alt="Logo SuWork" height="45">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainHeaderNav"
            aria-controls="mainHeaderNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainHeaderNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ request()->routeIs('dashboard') ? 'active text-primary' : 'text-gray-700' }}"
                        href="{{ route('dashboard') }}">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ request()->routeIs('properties.*') ? 'active text-primary' : 'text-gray-700' }}"
                        href="{{ route('properties.index') }}">
                        Propiedades
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ request()->routeIs('owners.*') ? 'active text-primary' : 'text-gray-700' }}"
                        href="{{ route('owners.index') }}">
                        Propietarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ request()->routeIs('profile.*') ? 'active text-primary' : 'text-gray-700' }}"
                        href="{{ route('profile.index') }}">
                        Perfil
                    </a>
                </li>
            </ul>

            <div class="dropdown d-flex align-items-center gap-3">
                <div class="cursor-pointer symbol symbol-circle symbol-40px" data-bs-toggle="dropdown" aria-expanded="false">
                    @if ($user->profile_photo)
                        <img src="{{ asset($user->profile_photo) }}" alt="user" class="symbol-label"
                            style="object-fit: cover;">
                    @else
                        <div class="symbol-label fw-bold d-flex justify-content-center align-items-center text-white"
                            style="background:#0d6efd;">
                            {{ $initials }}
                        </div>
                    @endif
                </div>

                <span class="fw-semibold text-dark d-none d-md-inline">{{ $user->name }}</span>

                <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm" style="width:280px">
                    <div class="px-4 py-3 border-bottom d-flex align-items-center">
                        <div class="symbol symbol-45px me-3">
                            @if ($user->profile_photo)
                                <img src="{{ asset($user->profile_photo) }}" class="symbol-label" alt="avatar">
                            @else
                                <div class="symbol-label fw-bold d-flex justify-content-center align-items-center text-white"
                                    style="background:#0d6efd;">
                                    {{ $initials }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="fw-bold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    </div>

                    <a href="{{ route('profile.index') }}" class="dropdown-item px-4 py-2">Mi perfil</a>

                    <div class="dropdown-divider"></div>

                    <a href="#" class="dropdown-item text-danger px-4 py-2"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ki-outline ki-exit-right me-2"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
