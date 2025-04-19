<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\GroupChatController;
use App\Http\Controllers\Api\PersonalTaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\TeamTaskAssignmentController;
use App\Http\Controllers\Api\TeamTaskController;
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

    // Set password for Google-authenticated users
    Route::post('/auth/set-password', [GoogleAuthController::class, 'setPassword']);

    // Google account management
    Route::post('/auth/google/link', [GoogleAuthController::class, 'linkGoogleAccount']);
    Route::post('/auth/google/unlink', [GoogleAuthController::class, 'unlinkGoogleAccount']);
}); 