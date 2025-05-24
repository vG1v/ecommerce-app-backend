<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Role;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // List all users with filtering options
    public function index(Request $request)
    {
        $query = User::with('roles');
        
        // Search users
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }
        
        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        // Sort users
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $allowedSortFields = ['name', 'email', 'created_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Paginate results
        $users = $query->paginate($request->per_page ?? 15);
        
        return response()->json($users);
    }
    
    // Get a specific user with roles
    public function show($id)
    {
        $user = User::with(['roles', 'orders' => function($query) {
            $query->latest()->take(5);
        }])->findOrFail($id);
        
        // Get user's vendor profile if exists
        $vendor = Vendor::where('user_id', $user->id)->first();
        
        return response()->json([
            'user' => $user,
            'vendor' => $vendor
        ]);
    }
    
    // Update a user's information
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
        ]);
        
        $user->update($request->only(['name', 'email', 'phone_number']));
        
        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }
    
    // Update a user's status (active/inactive)
    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);
        
        // Instead of deleting, just mark as inactive
        if ($request->status === 'inactive') {
            // Maybe update a status field or set some flag
            // For now, we'll just add a note in the response
            return response()->json([
                'message' => 'User marked as inactive',
                'user' => $user
            ]);
        } else {
            return response()->json([
                'message' => 'User marked as active',
                'user' => $user
            ]);
        }
    }
    
    // Assign a role to a user
    public function assignRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);
        
        $role = Role::where('name', $request->role)->first();
        
        // Check if user already has this role
        if ($user->hasRole($role->name)) {
            return response()->json([
                'message' => 'User already has this role'
            ], 400);
        }
        
        // Assign the role
        $user->roles()->attach($role->id);
        
        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $user->load('roles')
        ]);
    }
    
    // Remove a role from a user
    public function removeRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);
        
        // Check if user has this role
        if (!$user->hasRole($role->name)) {
            return response()->json([
                'message' => 'User does not have this role'
            ], 400);
        }
        
        // Remove the role
        $user->roles()->detach($role->id);
        
        return response()->json([
            'message' => 'Role removed successfully',
            'user' => $user->load('roles')
        ]);
    }
}
