<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\GroupChatController;
use App\Http\Controllers\Api\PersonalTaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\TeamTaskAssignmentController;
use App\Http\Controllers\Api\TeamTaskController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TeamActivityController;
use App\Http\Controllers\Api\TeamExportController;
use App\Http\Controllers\Api\PersonalTaskExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

// Auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Google Auth routes
Route::post('/auth/google', [GoogleAuthController::class, 'handleGoogleSignIn']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Personal tasks
    Route::apiResource('personal-tasks', PersonalTaskController::class);

    // Teams
    Route::apiResource('teams', TeamController::class);

    // Team members
    Route::get('/teams/{team}/members', [TeamMemberController::class, 'index']);
    Route::post('/teams/{team}/members', [TeamMemberController::class, 'store']);
    Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);

    // Team tasks
    Route::apiResource('teams.tasks', TeamTaskController::class);

    // Team task assignments
    Route::get('/teams/{team}/tasks/{task}/assignments', [TeamTaskAssignmentController::class, 'index']);
    Route::post('/teams/{team}/tasks/{task}/assignments', [TeamTaskAssignmentController::class, 'store']);
    Route::put('/teams/{team}/tasks/{task}/assignments/{assignment}', [TeamTaskAssignmentController::class, 'update']);
    Route::delete('/teams/{team}/tasks/{task}/assignments/{assignment}', [TeamTaskAssignmentController::class, 'destroy']);

    // Group chat
    Route::get('/teams/{team}/chat', [GroupChatController::class, 'index']);
    Route::post('/teams/{team}/chat', [GroupChatController::class, 'store']);
    Route::put('/teams/{team}/chat/{message}/read', [GroupChatController::class, 'markAsRead']);
    Route::get('/teams/{team}/chat/unread', [GroupChatController::class, 'getUnreadCount']);
    Route::post('/teams/{team}/chat/typing', [GroupChatController::class, 'updateTypingStatus']);
    Route::post('/teams/{team}/chat/retry/{clientTempId}', [GroupChatController::class, 'retry']);
    
    // New chat features
    Route::put('/teams/{team}/chat/{message}', [GroupChatController::class, 'update']);
    Route::delete('/teams/{team}/chat/{message}', [GroupChatController::class, 'destroy']);
    Route::post('/teams/{team}/chat/{message}/react', [GroupChatController::class, 'react']);

    // Set password for Google-authenticated users
    Route::post('/auth/set-password', [GoogleAuthController::class, 'setPassword']);

    // Google account management
    Route::post('/auth/google/link', [GoogleAuthController::class, 'linkGoogleAccount']);
    Route::post('/auth/google/unlink', [GoogleAuthController::class, 'unlinkGoogleAccount']);
    
    // Notification routes
    Route::post('/notifications/register-device', [NotificationController::class, 'registerDevice']);
    Route::delete('/notifications/unregister-device', [NotificationController::class, 'unregisterDevice']);
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    
    // Sync routes
    Route::post('/sync/initial', [SyncController::class, 'initialSync']);
    Route::post('/sync/quick', [SyncController::class, 'quickSync']);
    Route::post('/sync/push', [SyncController::class, 'push']);
    
    // File upload
    Route::post('/upload', [FileController::class, 'upload']);

    // Lấy và cập nhật hồ sơ
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/user/profile', [UserController::class, 'updateProfile']);
    
    // Đổi mật khẩu
    Route::post('/user/password', [UserController::class, 'changePassword']);
    
    // Quản lý thiết bị
    Route::get('/user/devices', [DeviceController::class, 'index']);
    Route::delete('/user/devices/{device}', [DeviceController::class, 'destroy']);

    // Search routes
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/teams/{team}/tasks/search', [TeamTaskController::class, 'search']);
    Route::get('/teams/{team}/chat/search', [GroupChatController::class, 'search']);

    // Stats routes
    Route::get('/stats', [StatsController::class, 'index']);
    Route::get('/teams/{team}/activity', [TeamActivityController::class, 'index']);

    // Export routes
    Route::get('/teams/{team}/export', [TeamExportController::class, 'export']);
    Route::get('/personal-tasks/export', [PersonalTaskExportController::class, 'export']);
});

// Quên/reset mật khẩu
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::prefix('v1')->group(function() {
    // Auth routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function() {
        // User routes
        Route::get('/user', [UserController::class, 'show']);
        
        // All other routes...
    });
}); 