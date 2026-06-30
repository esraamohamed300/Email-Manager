<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\ProfileController;

// ── Public routes (no login needed) ─────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// ── Protected routes (must be logged in) ────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Inbox — main page
    Route::get('/inbox', [EmailController::class, 'index'])->name('inbox');

    // Compose — send a new email
    Route::post('/emails', [EmailController::class, 'store'])->name('emails.store');

    // Reply — add a message to an existing chat
    Route::post('/emails/{chat}/reply', [EmailController::class, 'reply'])->name('emails.reply');

    // Delete — remove a chat and all its messages
    Route::delete('/emails/{chat}', [EmailController::class, 'destroy'])->name('emails.destroy');

    // Read — fetch all emails as JSON (used by AJAX)
    Route::get('/emails', [EmailController::class, 'read'])->name('emails.read');

    // Weather API — proxies the third-party API call
    Route::get('/api/weather', [WeatherController::class, 'fetch'])->name('weather.fetch');

    Route::post('/upload', [EmailController::class, 'upload'])->name('upload.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dashboard', function () {
    return redirect('/inbox');
})->middleware(['auth'])->name('dashboard');

// ── Breeze auth routes (login, register, logout) ─────────────────────────────
require __DIR__.'/auth.php';