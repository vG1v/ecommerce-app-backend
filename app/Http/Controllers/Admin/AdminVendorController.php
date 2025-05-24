<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVendorController extends Controller
{
    // List all vendors with filtering options
    public function index(Request $request)
    {
        $query = Vendor::with('user');
        
        // Search vendors
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Sort vendors
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $allowedSortFields = ['store_name', 'created_at', 'total_products'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Paginate results
        $vendors = $query->paginate($request->per_page ?? 15);
        
        return response()->json($vendors);
    }
    
    // Get a specific vendor with details
    public function show($id)
    {
        $vendor = Vendor::with(['user', 'products' => function($query) {
            $query->take(5);
        }])->findOrFail($id);
        
        // Get vendor statistics
        $totalOrders = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.vendor_id', $vendor->id)
            ->distinct()
            ->count('orders.id');
            
        $totalRevenue = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.vendor_id', $vendor->id)
            ->sum(DB::raw('order_items.quantity * order_items.unit_price'));
            
        return response()->json([
            'vendor' => $vendor,
            'statistics' => [
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue
            ]
        ]);
    }
    
    // Update vendor status
    public function updateStatus(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,active,suspended'
        ]);
        
        $vendor->status = $request->status;
        $vendor->save();
        
        return response()->json([
            'message' => 'Vendor status updated successfully',
            'vendor' => $vendor
        ]);
    }
    
    // Get vendor products
    public function products($id)
    {
        $vendor = Vendor::findOrFail($id);
        
        $products = Product::where('vendor_id', $vendor->id)
            ->paginate(15);
            
        return response()->json($products);
    }
}
