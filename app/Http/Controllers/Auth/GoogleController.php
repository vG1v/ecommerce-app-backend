<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function handleGoogleCallback(Request $request)
    {
        // We expect an 'access_token' from the frontend
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Use the token to get user details from Google
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->access_token);

            // Find or create the user
            $user = User::updateOrCreate(
                [
                    'google_id' => $googleUser->id,
                ],
                [
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    // Set a random password as it's required but won't be used for login
                    'password' => Hash::make(str()->random(24)),
                ]
            );

            // Create a Sanctum token for the user to use for API authentication
            $token = $user->createToken('auth-token-'.$user->id)->plainTextToken;

            // Return the user and the API token as JSON
            return response()->json([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            Log::error('Google Auth Callback Error: ' . $e->getMessage());
            return response()->json(['error' => 'Login failed. Please try again.'], 401);
        }
    }
}