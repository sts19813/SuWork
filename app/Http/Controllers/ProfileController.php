<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordUpdatedMail;
class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only('name', 'email'));

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|max:2048',
        ]);

        $user = Auth::user();

        // Carpeta donde se guardará dentro de /public/
        $destination = public_path('profile_photos');

        // Crear carpeta si no existe
        if (!file_exists($destination)) {
            mkdir($destination, 0777, true);
        }

        // Eliminar foto anterior si existe en public
        if ($user->profile_photo && file_exists(public_path($user->profile_photo))) {
            unlink(public_path($user->profile_photo));
        }

        // Preparar nombre del archivo
        $file = $request->file('profile_photo');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();

        // Mover la foto a /public/profile_photos/
        $file->move($destination, $filename);

        // Guardar ruta relativa (para usar con asset())
        $relativePath = 'profile_photos/' . $filename;

        $user->update(['profile_photo' => $relativePath]);

        return back()->with('success', 'Foto de perfil actualizada.');
    }


    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'La contraseña actual no es correcta.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // NOTIFICACIÓN EMAIL
        Mail::to($user->email)->send(
            new PasswordUpdatedMail($user)
        );



        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

}
