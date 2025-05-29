<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        // Get products with vendor information
        $products = Product::with('vendor')->paginate(12);
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    // Show the form for creating a new resource.
    public function create()
    {
        //
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'main_image_path' => 'nullable|string',
        ]);

        // Check if user has a vendor account
        $user = Auth::user();
        $vendor = Vendor::where('user_id', $user->id)->first();
        
        if (!$vendor) {
            return response()->json([
                'status' => 'error',
                'message' => 'You need to create a vendor account first'
            ], 403);
        }
        
        // Check if vendor is active
        if ($vendor->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Your vendor account is not active yet'
            ], 403);
        }
        
        try {
            DB::beginTransaction();
            
            // Generate slug from product name
            $slug = Str::slug($request->name);
            $count = Product::where('slug', 'LIKE', "{$slug}%")->count();
            if ($count > 0) {
                $slug = "{$slug}-{$count}";
            }
            
            // Create the product
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'price' => $request->price,
                'stock_quantity' => $request->stock_quantity,
                'main_image_path' => $request->main_image_path,
                'status' => 'published',
            ]);
            
            // Update vendor stats
            $vendor->increment('total_products');
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Display the specified resource.
    public function show($id)
    {
        $product = Product::with('vendor')->find($id);
        
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    // Show the form for editing the specified resource.
    public function edit(Product $product)
    {
        //
    }

    // Update the specified resource in storage.
    public function update(Request $request, Product $product)
    {
        // Check if user owns this product
        $user = Auth::user();
        $vendor = Vendor::where('user_id', $user->id)->first();
        
        if (!$vendor || $product->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this product'
            ], 403);
        }
        
        // Validate the request
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'main_image_path' => 'nullable|string',
        ]);
        
        try {
            // If name is being updated, update slug too
            if ($request->has('name') && $request->name !== $product->name) {
                $slug = Str::slug($request->name);
                $count = Product::where('slug', 'LIKE', "{$slug}%")
                    ->where('id', '!=', $product->id)
                    ->count();
                if ($count > 0) {
                    $slug = "{$slug}-{$count}";
                }
                $request->merge(['slug' => $slug]);
            }
            
            $product->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Remove the specified resource from storage.
    public function destroy(Product $product)
    {
        // Check if user owns this product
        $user = Auth::user();
        $vendor = Vendor::where('user_id', $user->id)->first();
        
        if (!$vendor || $product->vendor_id !== $vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this product'
            ], 403);
        }
        
        try {
            DB::beginTransaction();
            
            // Decrease vendor product count
            $vendor->decrement('total_products');
            
            // Delete the product
            $product->delete();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Get vendor's products
    public function vendorProducts()
    {
        $user = Auth::user();
        $vendor = Vendor::where('user_id', $user->id)->first();
        
        if (!$vendor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vendor profile not found'
            ], 404);
        }
        
        $products = Product::where('vendor_id', $vendor->id)->paginate(12);
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
    
    /**
     * Search and filter products
     */
    public function search(Request $request)
    {
        $query = Product::query()->with('vendor')->where('status', 'published');
        
        // Search by keyword/term
        if ($request->has('q')) {
            $searchTerm = $request->q;
            $query->where(function($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%")
                      ->orWhere('short_description', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        // Filter by vendor
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        // Filter by featured status
        if ($request->has('featured')) {
            $query->where('featured', true);
        }
        
        // Filter by stock status
        if ($request->has('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }
        
        // Sort products
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $allowedSortFields = ['name', 'price', 'created_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Paginate results
        $products = $query->paginate($request->per_page ?? 12);
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
    
    /**
     * Get featured products
     */
    public function featured()
    {
        $featuredProducts = Product::where('featured', true)
                              ->where('status', 'published')
                              ->with('vendor')
                              ->take(8)
                              ->get();
    
        return response()->json([
            'status' => 'success',
            'data' => $featuredProducts
        ]);
    }
    
    /**
     * Get related products
     */
    public function related(Product $product)
    {
        // Get products from the same vendor
        $relatedProducts = Product::where('vendor_id', $product->vendor_id)
                            ->where('id', '!=', $product->id)
                            ->where('status', 'published')
                            ->take(8)
                            ->get();
    
        return response()->json([
            'status' => 'success',
            'data' => $relatedProducts
        ]);
    }
}
