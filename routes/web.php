<?php

use App\Http\Livewire\Demo\Welcome;
use Illuminate\Support\Facades\Route;

Route::get('/', Welcome::class)->name('home');

// Placeholder T1.03: membuktikan guard `auth` + `verified`. Diganti shell UI (T1.06) dan dashboard (M5).
Route::view('/dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');
