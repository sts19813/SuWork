<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/lang/{lang}', [LocaleController::class, 'switch'])->name('lang.switch');

Route::middleware(['auth'])
    ->group(function () {
        Route::get('/perfil', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/perfil/actualizar', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
        Route::post('/perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');

        Route::get('/propiedades', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/propiedades/nueva', [PropertyController::class, 'create'])->name('properties.create');
        Route::post('/propiedades', [PropertyController::class, 'store'])->name('properties.store');
        Route::get('/propiedades/{property}', [PropertyController::class, 'show'])->name('properties.show');
    });
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
