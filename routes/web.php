<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->action(HomeController::class);
    }

    return view('welcome');
});

Route::get('/home', HomeController::class)
    ->middleware(['auth', 'password.changed'])
    ->name('home');

Route::redirect('/dashboard', '/home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Módulo Access Control
require __DIR__.'/modules/access.php';

// Panel plataforma (Súper Admin)
require __DIR__.'/modules/admin.php';

// Panel empresa (Fase 0)
require __DIR__.'/modules/company.php';

// Panel conjunto / censo (Fase 1)
require __DIR__.'/modules/client.php';

require __DIR__.'/auth.php';
