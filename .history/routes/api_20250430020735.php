<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\GroupChatController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PersonalTaskController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\TeamTaskAssignmentController;
use App\Http\Controllers\Api\TeamTaskController;
use App\Http\Controllers\Api\UserSettingController;
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
Route::middleware(['throttle:6,1'])->group(function () {
    Route::post('/auth/google', [GoogleAuthController::class, 'handleGoogleSignIn']);
    Route::post('/auth/google/link', [GoogleAuthController::class, 'linkGoogleAccount']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/biometric', [AuthController::class, 'biometricAuth']);
    Route::post('/auth/biometric/register', [AuthController::class, 'registerBiometric']);
    Route::delete('/auth/biometric', [AuthController::class, 'removeBiometric']);

    // Personal tasks
    Route::apiResource('personal-tasks', PersonalTaskController::class);
    Route::post('/personal-tasks/order', [PersonalTaskController::class, 'updateTaskOrder']);

    // Teams
    Route::apiResource('teams', TeamController::class);

    // Team members
    Route::get('/teams/{team}/members', [TeamMemberController::class, 'index']);
    Route::post('/teams/{team}/members', [TeamMemberController::class, 'store']);
    Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);

    // Team tasks
    Route::apiResource('teams.tasks', TeamTaskController::class);
    Route::post('/teams/{team}/tasks/order', [TeamTaskController::class, 'updateTaskOrder']);

    // Team task assignments
    Route::get('/teams/{team}/tasks/{task}/assignments', [TeamTaskAssignmentController::class, 'index']);
    Route::post('/teams/{team}/tasks/{task}/assignments', [TeamTaskAssignmentController::class, 'store']);
    Route::put('/teams/{team}/tasks/{task}/assignments/{assignment}', [TeamTaskAssignmentController::class, 'update']);
    Route::delete('/teams/{team}/tasks/{task}/assignments/{assignment}', [TeamTaskAssignmentController::class, 'destroy']);

    // Subtasks
    Route::get('/{taskType}/{taskId}/subtasks', [SubtaskController::class, 'index']);
    Route::post('/{taskType}/{taskId}/subtasks', [SubtaskController::class, 'store']);
    Route::put('/{taskType}/{taskId}/subtasks/{subtask}', [SubtaskController::class, 'update']);
    Route::delete('/{taskType}/{taskId}/subtasks/{subtask}', [SubtaskController::class, 'destroy']);
    Route::post('/{taskType}/{taskId}/subtasks/order', [SubtaskController::class, 'updateOrder']);

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

    // User settings
    Route::get('/settings', [UserSettingController::class, 'index']);
    Route::put('/settings', [UserSettingController::class, 'update']);

    // Analytics
    Route::get('/analytics/tasks', [AnalyticsController::class, 'getTaskStats']);
    Route::get('/analytics/productivity', [AnalyticsController::class, 'getProductivityScore']);
    Route::get('/analytics/team-performance', [AnalyticsController::class, 'getTeamPerformance']);

    // Calendar
    Route::get('/calendar/tasks', [CalendarController::class, 'getTasksByDateRange']);
    Route::get('/calendar/day', [CalendarController::class, 'getTasksByDate']);
    Route::put('/calendar/sync', [CalendarController::class, 'updateCalendarSync']);

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

    // Drafts
    Route::apiResource('drafts', DraftController::class);
});
