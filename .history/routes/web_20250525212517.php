<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\AuthController;

// Redirect root to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected web routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tasks
    Route::get('/tasks', [DashboardController::class, 'tasks'])->name('tasks');
    Route::get('/tasks/personal', [DashboardController::class, 'personalTasks'])->name('tasks.personal');

    // Teams
    Route::get('/teams', [DashboardController::class, 'teams'])->name('teams');
    Route::get('/teams/{team}', [DashboardController::class, 'teamDetail'])->name('teams.show');
    Route::get('/teams/{team}/chat', [DashboardController::class, 'teamChat'])->name('teams.chat');
    Route::get('/teams/{team}/tasks', [DashboardController::class, 'teamTasks'])->name('teams.tasks');
    Route::get('/teams/{team}/kanban', [DashboardController::class, 'teamKanban'])->name('teams.kanban');

    // Documents
    Route::get('/documents', [DashboardController::class, 'documents'])->name('documents');
    Route::get('/teams/{team}/documents', [DashboardController::class, 'teamDocuments'])->name('teams.documents');

    // Analytics
    Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');

    // Profile
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
});
