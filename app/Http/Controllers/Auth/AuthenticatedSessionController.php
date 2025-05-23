<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    // Display the login view.
    
    public function create(): View
    {
        return view('auth.login');
    }

    // Handle an incoming authentication request.
    
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    // Destroy an authenticated session.
     
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    // Handle an API authentication request.
  
    public function apiLogin(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'],  // Can be email or phone
            'password' => ['required', 'string'],
        ]);

        // Determine if login is email or phone
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        
        $credentials = [
            $loginField => $request->login,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where($loginField, $request->login)->first();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }


//    Logout the user from API access.

    public function apiLogout(Request $request): JsonResponse
    {
        // Revoke the token that was used for authentication
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out'
        ]);
    }
}
