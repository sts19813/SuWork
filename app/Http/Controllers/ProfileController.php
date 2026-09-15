<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Mail\PasswordUpdatedMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        return view('profile.index', compact('user'));
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill($validated);
        if ($user->email !== $user->getOriginal('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($request->is('profile')) {
            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }

        return back()
            ->with('status', 'profile-updated')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'profile_photo' => 'required|image|max:2048',
        ]);

        $user = Auth::user();
        $previousPhoto = $user->profile_photo;
        $path = $request->file('profile_photo')->store('profile_photos', 'public');

        $user->update(['profile_photo' => $path]);
        $this->deleteProfilePhoto($previousPhoto);

        return back()->with('success', 'Foto de perfil actualizada.');
    }

    private function deleteProfilePhoto(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);

            return;
        }

        $legacyPublicPath = public_path($path);
        if (file_exists($legacyPublicPath) && is_file($legacyPublicPath)) {
            unlink($legacyPublicPath);
        }
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
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

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $profilePhoto = $user->profile_photo;

        Auth::logout();
        $user->delete();
        $this->deleteProfilePhoto($profilePhoto);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
