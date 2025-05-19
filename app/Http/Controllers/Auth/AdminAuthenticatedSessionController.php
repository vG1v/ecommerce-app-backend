<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthenticatedSessionController extends Controller
{
    /**
     * Handle an admin API authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->first();
        
        // Check if user has admin role
        if (!$user->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        // Create token with admin ability
        $token = $user->createToken('admin_token', ['admin'])->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Admin login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Log the admin out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Admin successfully logged out',
        ]);
    }
    
    /**
     * Get the authenticated admin user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get roles for inclusion in the response
        $user->load('roles');
        
        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }
    
    /**
     * Check if the admin token is valid.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAuth(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Token is valid',
            'authenticated' => true
        ]);
    }
    
    /**
     * Register a new admin user (protected, only accessible by existing admins).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Get the admin role
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin role not found'
            ], 500);
        }
        
        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);
        
        // Assign the admin role
        $user->roles()->attach($adminRole);
        
        // Create token
        $token = $user->createToken('admin_token', ['admin'])->plainTextToken;
        
        return response()->json([
            'status' => 'success',
            'message' => 'Admin user registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}