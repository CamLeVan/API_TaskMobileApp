<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Verify Google ID token and authenticate user
     */
    public function handleGoogleSignIn(Request $request)
    {
        try {
            Log::info('Google sign in attempt');

            $request->validate([
                'id_token' => 'required|string'
            ]);

            // Verify the Google ID token
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->id_token);

            Log::info('Google user info received', [
                'google_id' => $googleUser->id,
                'email' => $googleUser->email
            ]);

            // Find or create user
            $user = User::findOrCreateGoogleUser($googleUser);

            // Create token
            $token = $user->createToken('google-auth')->plainTextToken;

            Log::info('Google sign in successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Successfully logged in with Google'
            ]);

        } catch (Exception $e) {
            Log::error('Google authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Google authentication failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Link existing account with Google
     */
    public function linkGoogleAccount(Request $request)
    {
        try {
            Log::info('Google account linking attempt', [
                'user_id' => $request->user()->id
            ]);

            $request->validate([
                'id_token' => 'required|string'
            ]);

            $user = $request->user();
            
            // Verify the Google ID token
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->id_token);

            Log::info('Google user info received for linking', [
                'google_id' => $googleUser->id,
                'email' => $googleUser->email
            ]);

            // Check if Google account is already linked to another user
            $existingUser = User::where('google_id', $googleUser->id)
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                Log::warning('Google account already linked to another user', [
                    'google_id' => $googleUser->id,
                    'existing_user_id' => $existingUser->id,
                    'attempting_user_id' => $user->id
                ]);

                return response()->json([
                    'message' => 'This Google account is already linked to another user'
                ], 400);
            }

            // Link Google account
            $user->google_id = $googleUser->id;
            $user->avatar = $googleUser->avatar ?? $user->avatar;
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->save();

            Log::info('Google account linked successfully', [
                'user_id' => $user->id,
                'google_id' => $googleUser->id
            ]);

            return response()->json([
                'message' => 'Successfully linked Google account',
                'user' => $user
            ]);

        } catch (Exception $e) {
            Log::error('Failed to link Google account', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to link Google account',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Unlink Google account
     */
    public function unlinkGoogleAccount(Request $request)
    {
        try {
            $user = $request->user();

            Log::info('Google account unlink attempt', [
                'user_id' => $user->id
            ]);

            if (!$user->google_id) {
                Log::warning('No Google account to unlink', [
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'message' => 'No Google account linked'
                ], 400);
            }

            // Check if user has password set, if not, they can't unlink Google
            if (!$user->password) {
                Log::warning('Cannot unlink Google account - no password set', [
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'message' => 'Cannot unlink Google account without setting a password first'
                ], 400);
            }

            $user->google_id = null;
            $user->save();

            Log::info('Google account unlinked successfully', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Successfully unlinked Google account'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to unlink Google account', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to unlink Google account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set password for Google-authenticated user
     */
    public function setPassword(Request $request)
    {
        try {
            $request->validate([
                'password' => 'required|string|min:8|confirmed'
            ]);

            $user = $request->user();
            
            Log::info('Password set attempt for Google user', [
                'user_id' => $user->id
            ]);

            $user->password = bcrypt($request->password);
            $user->save();

            Log::info('Password set successfully for Google user', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Password set successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to set password', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to set password',
                'error' => $e->getMessage()
            ], 400);
        }
    }
} 