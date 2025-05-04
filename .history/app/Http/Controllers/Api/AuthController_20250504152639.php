<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BiometricAuth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Generate password reset token
        $token = \Illuminate\Support\Str::random(60);

        // Store token in password_resets table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Get user
        $user = User::where('email', $request->email)->first();

        // Send email with reset link
        // In a real application, you would use Laravel's built-in password reset functionality
        // For this example, we'll just return the token

        return response()->json([
            'message' => 'Password reset link sent to your email',
            'token' => $token, // In production, don't return the token
            'email' => $request->email
        ]);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find token in database
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => 'Invalid token or email'
            ], 422);
        }

        // Verify token
        if (!Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'message' => 'Invalid token'
            ], 422);
        }

        // Check if token is expired (tokens valid for 60 minutes)
        if (Carbon\Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'message' => 'Token has expired'
            ], 422);
        }

        // Update user password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Password has been reset successfully'
        ]);
    }

    /**
     * Setup two-factor authentication
     */
    public function setup2FA(Request $request)
    {
        $user = $request->user();

        // Generate a secret key
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secretKey = $google2fa->generateSecretKey();

        // Store the secret key in the user's record
        $user->two_factor_secret = $secretKey;
        $user->two_factor_enabled = false; // Not enabled until verified
        $user->save();

        // Generate the QR code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secretKey
        );

        return response()->json([
            'secret' => $secretKey,
            'qr_code_url' => $qrCodeUrl
        ]);
    }

    /**
     * Verify and enable two-factor authentication
     */
    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = $request->user();

        // Verify the code
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 422);
        }

        // Enable 2FA for the user
        $user->two_factor_enabled = true;
        $user->save();

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully'
        ]);
    }
}
