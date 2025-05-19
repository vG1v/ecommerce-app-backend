<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vendors = Vendor::with('user')->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendors
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_registration_number' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction();
            
            // Get current authenticated user
            $user = Auth::user();
            
            // Generate slug from store name
            $slug = Str::slug($request->store_name);
            $count = Vendor::where('slug', 'LIKE', "{$slug}%")->count();
            if ($count > 0) {
                $slug = "{$slug}-{$count}";
            }
            
            // Create the vendor profile
            $vendor = Vendor::create([
                'user_id' => $user->id,
                'store_name' => $request->store_name,
                'slug' => $slug,
                'description' => $request->description,
                'contact_email' => $request->contact_email,
                'contact_phone' => $request->contact_phone,
                'address_line1' => $request->address_line1,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'business_registration_number' => $request->business_registration_number,
                'status' => 'pending', // All new vendors start with pending status
            ]);
            
            // Assign vendor role to user
            $vendorRole = Role::where('name', 'vendor')->first();
            if ($vendorRole) {
                $user->roles()->syncWithoutDetaching([$vendorRole->id]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor profile created successfully',
                'data' => $vendor
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create vendor profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Vendor $vendor)
    {
        // Load related user data
        $vendor->load('user');
        
        return response()->json([
            'status' => 'success',
            'data' => $vendor
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vendor $vendor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vendor $vendor)
    {
        // Check if user owns this vendor profile or is admin
        if (Auth::id() !== $vendor->user_id && !Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'store_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'sometimes|required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_registration_number' => 'nullable|string|max:50',
        ]);

        // If store name is being updated, update slug too
        if ($request->has('store_name') && $request->store_name !== $vendor->store_name) {
            $slug = Str::slug($request->store_name);
            $count = Vendor::where('slug', 'LIKE', "{$slug}%")
                ->where('id', '!=', $vendor->id)
                ->count();
            if ($count > 0) {
                $slug = "{$slug}-{$count}";
            }
            $request->merge(['slug' => $slug]);
        }

        $vendor->update($request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor profile updated successfully',
            'data' => $vendor
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vendor $vendor)
    {
        // Check if user owns this vendor profile or is admin
        if (Auth::id() !== $vendor->user_id && !Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $vendor->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor profile deleted successfully'
        ]);
    }
    
    /**
     * Get vendor dashboard stats
     */
    public function dashboard()
    {
        $user = Auth::user();
        $vendor = Vendor::where('user_id', $user->id)->first();
        
        if (!$vendor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vendor profile not found'
            ], 404);
        }
        
        // Get basic stats
        $stats = [
            'total_products' => $vendor->total_products,
            'total_sales' => $vendor->total_sales,
            'average_rating' => $vendor->average_rating,
            'status' => $vendor->status
        ];
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'vendor' => $vendor,
                'stats' => $stats
            ]
        ]);
    }
}
