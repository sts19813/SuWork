@php
    $user = Auth::user();
    $name = $user->name;
    $initials = collect(explode(' ', $name))
        ->map(fn($w) => mb_substr($w, 0, 1))
        ->join('');

    $currentLocale = app()->getLocale();
    $currentLangName = $currentLocale === 'en' ? __('messages.language_english') : __('messages.language_spanish');
    $currentFlag = $currentLocale === 'en'
        ? 'assets/media/flags/united-states.svg'
        : 'assets/media/flags/mexico.svg';
@endphp

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container-fluid px-4">

        {{-- Logo --}}
        <a href="/" class="navbar-brand d-flex align-items-center">
            <img src="{{ asset('assets/img/Videre-Logo.svg') }}" alt="Videre" height="30">
        </a>

        {{-- Right --}}
        <div class="d-flex align-items-center ms-auto">

            {{-- User dropdown --}}
            <div class="dropdown d-flex align-items-center gap-3">

                {{-- Avatar clickable --}}
                <div class="cursor-pointer symbol symbol-circle symbol-40px" data-bs-toggle="dropdown"
                    aria-expanded="false">

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

                {{-- Nombre visible siempre --}}
                <span class="fw-semibold text-dark d-none d-md-inline">
                    {{ $user->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-white border fw-semibold text-dark">
                        Salir
                    </button>
                </form>

                {{-- Dropdown --}}
                <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm" style="width:280px">

                    {{-- User info --}}
                    <div class="px-4 py-3 border-bottom d-flex align-items-center">
                        <div class="symbol symbol-45px me-3">
                            @if ($user->profile_photo)
                                <img src="{{ asset($user->profile_photo) }}" class="symbol-label">
                            @else
                                <div class="symbol-label fw-bold d-flex justify-content-center align-items-center text-white"
                                    style="background:#0d6efd;">
                                    {{ $initials }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="fw-bold">
                                {{ $user->name }}
                                @if($user->is_admin)
                                    <span class="badge bg-success-subtle text-success ms-1">Admin</span>
                                @endif
                            </div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    </div>

                    {{-- Profile --}}
                    <a href="{{ route('profile.index') }}" class="dropdown-item px-4 py-2">
                        {{ __('messages.user_profile') }}
                    </a>

                    <div class="dropdown-divider"></div>

                    {{-- Theme mode --}}
                    <div class="px-4 py-2">
                        <div class="fw-semibold text-muted mb-2">{{ __('messages.mode') }}</div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-light btn-sm" onclick="setThemeMode('light')">
                                Light
                            </button>

                            <button class="btn btn-light btn-sm" onclick="setThemeMode('dark')">
                                Dark
                            </button>

                            <button class="btn btn-light btn-sm" onclick="setThemeMode('system')">
                                System
                            </button>

                        </div>
                    </div>

                    <div class="dropdown-divider"></div>

                    <!--

                    {{-- Language --}}
                    <div class="px-4 py-2">
                        <div class="fw-semibold text-muted mb-2">{{ __('messages.language') }}</div>

                        <a href="{{ route('lang.switch', 'en') }}"
                            class="dropdown-item d-flex align-items-center {{ $currentLocale == 'en' ? 'active' : '' }}">
                            <img src="{{ asset('/metronic/assets/media/flags/united-states.svg') }}" class="me-2"
                                width="20">
                            English
                        </a>

                        <a href="{{ route('lang.switch', 'es') }}"
                            class="dropdown-item d-flex align-items-center {{ $currentLocale == 'es' ? 'active' : '' }}">
                            <img src="{{ asset('/metronic/assets/media/flags/mexico.svg') }}" class="me-2" width="20">
                            Español
                        </a>
                    </div>
                    

                    <div class="dropdown-divider"></div>

                -->

                    {{-- Logout --}}
                    <a href="#" class="dropdown-item text-danger px-4 py-2"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ki-outline ki-exit-right me-2"></i>
                        {{ __('messages.logout') }}
                    </a>

                </div>
            </div>

            {{-- Logout form --}}
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
</nav>