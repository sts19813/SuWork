@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')


    <div class="card-header">
        <h3 class="card-title">Mi Perfil</h3>
    </div>

    <div class="card-body">

        {{-- MENSAJES --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">

            <!-- FOTO -->
            <div class="col-md-3 text-center">
                <div style="width: 140px; height: 140px; overflow: hidden; margin: auto;" class="rounded-circle mb-3">
                    <img src="{{ $user->profile_photo ? asset($user->profile_photo) : asset('assets/media/avatars/300-1.jpg') }}"
                        style="width: 100%; height: 100%; object-fit: cover;" alt="Foto de perfil">
                </div>


                <form action="{{ route('profile.update.photo') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" class="form-control mb-2" name="profile_photo" required>
                    <button class="btn btn-sm btn-primary">Actualizar Foto</button>
                </form>
            </div>

            <!-- DATOS -->
            <div class="col-md-9">

                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf

                    <div class="mb-5">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Correo</label>
                        <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                    </div>

                    <button class="btn btn-primary">Guardar Cambios</button>
                </form>

                <hr class="my-10">

                <h5>Cambiar Contraseña</h5>

                <form action="{{ route('profile.update.password') }}" method="POST">
                    @csrf

                    <div class="mb-5">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password_confirmation" required>
                    </div>

                    <button class="btn btn-primary">Actualizar Contraseña</button>
                </form>
            </div>
        </div>
    </div>


    @if(session('success'))
        <script>
            toastr.success("{{ session('success') }}");
        </script>
    @endif

    @if(session('error'))
        <script>
            toastr.error("{{ session('error') }}");
        </script>
    @endif

    @if ($errors->any())
        <script>
            @foreach ($errors->all() as $error)
                toastr.error("{{ $error }}");
            @endforeach
        </script>
    @endif



@endsection