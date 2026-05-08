<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()?->role === User::ROLE_ADMIN
        ? redirect()->route('admin.dashboard')
        : redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/teacher/{user}/detail', [DashboardController::class, 'teacherDetail'])->name('teacher.detail');
    Route::get('/admin/presence/{presence}/detail', [DashboardController::class, 'presenceDetail'])->name('presence.detail');
    Route::get('/admin/salary', [DashboardController::class, 'salary'])->name('admin.salary');
});

require __DIR__.'/auth.php';

