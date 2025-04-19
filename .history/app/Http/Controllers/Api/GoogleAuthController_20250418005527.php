<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Verify Google ID token and authenticate user
     */
    public function handleGoogleSignIn(Request $request)
    {
        try {
            $request->validate([
                'id_token' => 'required|string'
            ]);

            // Verify the Google ID token
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->id_token);

            // Find or create user
            $user = User::findOrCreateGoogleUser($googleUser);

            // Create token
            $token = $user->createToken('google-auth')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Successfully logged in with Google'
            ]);

        } catch (Exception $e) {
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
            $request->validate([
                'id_token' => 'required|string'
            ]);

            $user = $request->user();
            
            // Verify the Google ID token
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->id_token);

            // Check if Google account is already linked to another user
            $existingUser = User::where('google_id', $googleUser->id)->first();
            if ($existingUser && $existingUser->id !== $user->id) {
                return response()->json([
                    'message' => 'This Google account is already linked to another user'
                ], 400);
            }

            // Link Google account
            $user->google_id = $googleUser->id;
            $user->avatar = $googleUser->avatar;
            $user->save();

            return response()->json([
                'message' => 'Successfully linked Google account',
                'user' => $user
            ]);

        } catch (Exception $e) {
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
        $user = $request->user();

        if (!$user->google_id) {
            return response()->json([
                'message' => 'No Google account linked'
            ], 400);
        }

        $user->google_id = null;
        $user->save();

        return response()->json([
            'message' => 'Successfully unlinked Google account'
        ]);
    }
} 