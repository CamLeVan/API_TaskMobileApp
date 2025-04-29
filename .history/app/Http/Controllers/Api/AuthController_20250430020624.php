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

    /**
     * Authenticate using biometric
     */
    public function biometricAuth(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'device_id' => 'required',
            'biometric_token' => 'required'
        ]);

        // Xác thực token sinh trắc học
        $biometricAuth = BiometricAuth::where('user_id', $request->user_id)
            ->where('device_id', $request->device_id)
            ->where('biometric_token', $request->biometric_token)
            ->first();

        if (!$biometricAuth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Cập nhật thời gian sử dụng cuối
        $biometricAuth->update(['last_used_at' => now()]);

        // Tạo token mới
        $user = User::find($request->user_id);
        $token = $user->createToken('biometric-auth')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Register biometric authentication
     */
    public function registerBiometric(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'biometric_token' => 'required|string'
        ]);

        $user = $request->user();

        // Lưu hoặc cập nhật thông tin sinh trắc học
        $biometricAuth = BiometricAuth::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $request->device_id
            ],
            [
                'biometric_token' => $request->biometric_token,
                'last_used_at' => now()
            ]
        );

        return response()->json([
            'message' => 'Biometric registered successfully',
            'biometric_auth' => $biometricAuth
        ]);
    }

    /**
     * Remove biometric authentication
     */
    public function removeBiometric(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string'
        ]);

        $user = $request->user();

        // Xóa thông tin sinh trắc học
        $deleted = BiometricAuth::where('user_id', $user->id)
            ->where('device_id', $request->device_id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Biometric removed successfully']);
        } else {
            return response()->json(['message' => 'Biometric not found'], 404);
        }
    }
}
