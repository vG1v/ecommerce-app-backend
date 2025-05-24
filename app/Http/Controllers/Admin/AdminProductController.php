<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    // List all products with filtering options
    public function index(Request $request)
    {
        $query = Product::with('vendor');
        
        // Search products
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        // Filter by vendor
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by featured status
        if ($request->has('featured')) {
            $featured = $request->featured === 'true' || $request->featured === '1';
            $query->where('featured', $featured);
        }
        
        // Sort products
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $allowedSortFields = ['name', 'price', 'created_at', 'stock_quantity'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Paginate results
        $products = $query->paginate($request->per_page ?? 15);
        
        return response()->json($products);
    }
    
    // Get a specific product with details
    public function show($id)
    {
        $product = Product::with(['vendor', 'images'])->findOrFail($id);
        
        return response()->json($product);
    }
    
    // Store a new product
    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'featured' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ]);
        
        $slug = Str::slug($request->name);
        $count = Product::where('slug', 'LIKE', "{$slug}%")->count();
        
        if ($count > 0) {
            $slug = "{$slug}-{$count}";
        }
        
        $product = new Product();
        $product->vendor_id = $request->vendor_id;
        $product->name = $request->name;
        $product->slug = $slug;
        $product->sku = $request->sku;
        $product->description = $request->description;
        $product->short_description = $request->short_description ?? substr($request->description, 0, 100);
        $product->price = $request->price;
        $product->sale_price = $request->sale_price;
        $product->stock_quantity = $request->stock_quantity;
        $product->stock_status = $request->stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
        $product->featured = $request->featured ?? false;
        $product->status = $request->status;
        $product->save();
        
        // Update vendor's product count
        $vendor = Vendor::find($request->vendor_id);
        $vendor->total_products = Product::where('vendor_id', $vendor->id)->count();
        $vendor->save();
        
        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }
    
    // Update a product
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'sometimes|required|string',
            'short_description' => 'nullable|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'featured' => 'boolean',
            'status' => 'sometimes|required|in:draft,published,archived',
        ]);
        
        // Update slug if name changed
        if ($request->has('name') && $product->name !== $request->name) {
            $slug = Str::slug($request->name);
            $count = Product::where('slug', 'LIKE', "{$slug}%")
                ->where('id', '!=', $product->id)
                ->count();
            
            if ($count > 0) {
                $slug = "{$slug}-{$count}";
            }
            
            $product->slug = $slug;
        }
        
        // Update other fields
        $product->fill($request->only([
            'name', 'sku', 'description', 'short_description',
            'price', 'sale_price', 'stock_quantity', 'featured', 'status'
        ]));
        
        // Update stock_status based on quantity
        if ($request->has('stock_quantity')) {
            $product->stock_status = $request->stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
        }
        
        $product->save();
        
        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }
    
    // Delete a product
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $vendorId = $product->vendor_id;
        
        // First check if product has any orders
        $hasOrders = DB::table('order_items')
            ->where('product_id', $product->id)
            ->exists();
            
        if ($hasOrders) {
            // If product has orders, just mark as archived instead of deleting
            $product->status = 'archived';
            $product->save();
            
            return response()->json([
                'message' => 'Product archived successfully (cannot be deleted due to existing orders)'
            ]);
        }
        
        // If no orders, proceed with deletion
        $product->delete();
        
        // Update vendor's product count
        $vendor = Vendor::find($vendorId);
        $vendor->total_products = Product::where('vendor_id', $vendor->id)->count();
        $vendor->save();
        
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
    
    // Update product status
    public function updateStatus(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:draft,published,archived'
        ]);
        
        $product->status = $request->status;
        $product->save();
        
        return response()->json([
            'message' => 'Product status updated successfully',
            'product' => $product
        ]);
    }
    
    // Toggle featured status
    public function toggleFeatured($id)
    {
        $product = Product::findOrFail($id);
        
        $product->featured = !$product->featured;
        $product->save();
        
        return response()->json([
            'message' => $product->featured ? 'Product marked as featured' : 'Product unmarked as featured',
            'product' => $product
        ]);
    }
}
