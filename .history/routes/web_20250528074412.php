<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminTeamController;

Route::get('/', function () {
    return view('welcome');
});

// Default login route (redirect to admin login)
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Admin routes
Route::prefix('admin')->group(function () {
    // Admin login routes (không cần auth)
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login']);

    // Protected admin routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

        // User management
        Route::get('users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
        Route::post('users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('admin.users.toggle-status');

        // Team management
        Route::get('teams', [AdminTeamController::class, 'index'])->name('admin.teams.index');
        Route::get('teams/{team}', [AdminTeamController::class, 'show'])->name('admin.teams.show');
    });
});
