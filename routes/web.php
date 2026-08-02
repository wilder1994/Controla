<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class);

Route::get('/home', HomeController::class)
    ->middleware(['auth', 'password.changed'])
    ->name('home');

Route::redirect('/dashboard', '/home');

require __DIR__.'/modules/public.php';

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

// Checkout billing (simulador local / futuro pasarela)
require __DIR__.'/modules/billing.php';

// Panel conjunto / censo (Fase 1)
require __DIR__.'/modules/client.php';

// Portal residente (Fase 4)
require __DIR__.'/modules/resident.php';

require __DIR__.'/auth.php';
