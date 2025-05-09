<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\GroupChatController;
use App\Http\Controllers\Api\KanbanController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PersonalTaskController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamInvitationController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\TeamRoleController;
use App\Http\Controllers\Api\TeamRoleHistoryController;
use App\Http\Controllers\Api\TeamTaskAssignmentController;
use App\Http\Controllers\Api\TeamTaskController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentFolderController;
use App\Http\Controllers\Api\DocumentVersionController;
use App\Http\Controllers\Api\DocumentSyncController;
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
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

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
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/biometric', [AuthController::class, 'biometricAuth']);
    Route::post('/auth/biometric/register', [AuthController::class, 'registerBiometric']);
    Route::delete('/auth/biometric', [AuthController::class, 'removeBiometric']);

    // Two-factor authentication
    Route::post('/auth/2fa/setup', [AuthController::class, 'setup2FA']);
    Route::post('/auth/2fa/verify', [AuthController::class, 'verify2FA']);

    // Personal tasks
    Route::apiResource('personal-tasks', PersonalTaskController::class);
    Route::post('/personal-tasks/order', [PersonalTaskController::class, 'updateTaskOrder']);
    Route::get('/personal-tasks/filter', [PersonalTaskController::class, 'filter']);
    Route::get('/personal-tasks/search', [PersonalTaskController::class, 'search']);

    // Teams
    Route::apiResource('teams', TeamController::class);

    // Team members
    Route::get('/teams/{team}/members', [TeamMemberController::class, 'index']);
    Route::post('/teams/{team}/members', [TeamMemberController::class, 'store']);
    Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);

    // Team roles and permissions
    Route::get('/teams/{team}/roles', [TeamRoleController::class, 'index']);
    Route::post('/teams/{team}/roles', [TeamRoleController::class, 'store']);
    Route::put('/teams/{team}/members/{user}/role', [TeamRoleController::class, 'updateMemberRole']);
    Route::put('/teams/{team}/members/{user}/permissions', [TeamRoleController::class, 'updateMemberPermissions']);

    // Team role history
    Route::get('/teams/{team}/role-history', [TeamRoleHistoryController::class, 'index']);
    Route::get('/teams/{team}/members/{user}/role-history', [TeamRoleHistoryController::class, 'userHistory']);

    // Team invitations
    Route::get('/teams/{team}/invitations', [TeamInvitationController::class, 'index']);
    Route::post('/teams/{team}/invitations', [TeamInvitationController::class, 'store']);
    Route::post('/invitations/accept', [TeamInvitationController::class, 'accept']);
    Route::post('/invitations/reject', [TeamInvitationController::class, 'reject']);
    Route::delete('/teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy']);

    // Team tasks
    Route::apiResource('teams.tasks', TeamTaskController::class);
    Route::post('/teams/{team}/tasks/order', [TeamTaskController::class, 'updateTaskOrder']);
    Route::get('/teams/{team}/tasks/filter', [TeamTaskController::class, 'filter']);
    Route::get('/teams/{team}/tasks/search', [TeamTaskController::class, 'search']);

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
    Route::get('/teams/{team}/chat/search', [GroupChatController::class, 'search']);

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
    Route::get('/analytics/export', [AnalyticsController::class, 'export']);

    // Calendar
    Route::get('/calendar/tasks', [CalendarController::class, 'getTasksByDateRange']);
    Route::get('/calendar/day', [CalendarController::class, 'getTasksByDate']);
    Route::put('/calendar/sync', [CalendarController::class, 'updateCalendarSync']);
    Route::get('/calendar/export', [CalendarController::class, 'export']);

    // Set password for Google-authenticated users
    Route::post('/auth/set-password', [GoogleAuthController::class, 'setPassword']);

    // Google account management
    Route::post('/auth/google/link', [GoogleAuthController::class, 'linkGoogleAccount']);
    Route::post('/auth/google/unlink', [GoogleAuthController::class, 'unlinkGoogleAccount']);

    // Notification routes
    Route::post('/notifications/register-device', [NotificationController::class, 'registerDevice']);
    Route::delete('/notifications/unregister-device', [NotificationController::class, 'unregisterDevice']);
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::put('/notifications/settings', [NotificationController::class, 'updateSettings']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);

    // Sync routes
    Route::post('/sync/initial', [SyncController::class, 'initialSync']);
    Route::post('/sync/quick', [SyncController::class, 'quickSync']);
    Route::post('/sync/push', [SyncController::class, 'push']);
    Route::post('/sync/selective', [SyncController::class, 'selective']);
    Route::post('/sync/resolve-conflicts', [SyncController::class, 'resolveConflicts']);

    // File upload
    Route::post('/upload', [FileController::class, 'upload']);

    // Drafts
    Route::apiResource('drafts', DraftController::class);

    // Kanban board
    Route::get('/teams/{team}/kanban', [KanbanController::class, 'getTeamKanban']);
    Route::put('/teams/{team}/kanban/tasks/{task}/move', [KanbanController::class, 'moveTask']);
    Route::put('/teams/{team}/kanban/column-order', [KanbanController::class, 'updateColumnOrder']);
});
