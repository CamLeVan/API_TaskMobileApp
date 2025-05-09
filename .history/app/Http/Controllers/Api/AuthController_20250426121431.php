<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use App\Models\RefreshToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        Log::info('Login attempt:', ['email' => $request->email]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            Log::warning('Login failed: Invalid credentials', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        Log::info('User authenticated:', ['user_id' => $user->id, 'email' => $user->email]);
        
        if (!$user) {
            Log::error('User not found after authentication');
            return response()->json(['error' => 'User not found after authentication'], 500);
        }

        try {
            Log::info('Attempting to create token for user:', ['user_id' => $user->id]);
            
            // Kiểm tra xem user có tồn tại trong database không
            $dbUser = User::find($user->id);
            if (!$dbUser) {
                Log::error('User not found in database', ['user_id' => $user->id]);
                return response()->json(['error' => 'User not found in database'], 500);
            }

            // Tạo token mới
            $token = $dbUser->createToken('auth_token');
            
            if (!$token) {
                Log::error('Token creation failed', ['user_id' => $user->id]);
                return response()->json(['error' => 'Token creation failed'], 500);
            }

            Log::info('Token created successfully for user:', ['user_id' => $user->id]);
            
            return response()->json([
                'user' => $user,
                'token' => $token->plainTextToken,
            ]);
        } catch (\Exception $e) {
            Log::error('Token creation error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id
            ]);
            return response()->json(['error' => 'Could not create token: ' . $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function refresh(Request $request)
    {
        // Validate refresh token
        $request->validate([
            'refresh_token' => 'required|string'
        ]);
        
        $refreshToken = $request->refresh_token;
        
        // Verify refresh token
        $tokenRecord = RefreshToken::where('token', $refreshToken)
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$tokenRecord) {
            return response()->json([
                'error' => [
                    'code' => 'invalid_refresh_token',
                    'message' => 'The refresh token is invalid or expired'
                ]
            ], 401);
        }
        
        // Revoke old access tokens
        $user = $tokenRecord->user;
        $user->tokens()->delete();
        
        // Generate new tokens
        $accessToken = $user->createToken('api')->plainTextToken;
        $newRefreshToken = Str::random(64);
        
        // Update refresh token
        $tokenRecord->update([
            'token' => $newRefreshToken,
            'expires_at' => now()->addDays(30)
        ]);
        
        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => 60 * 24 * 60 * 60 // 60 days in seconds
        ]);
    }
}
