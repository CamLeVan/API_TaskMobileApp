<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupChatController;
use App\Http\Controllers\Api\PersonalTaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\TeamTaskAssignmentController;
use App\Http\Controllers\Api\TeamTaskController;
use Illuminate\Support\Facades\Route;
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});
// Auth routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

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
}); 