<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    // Dashboard statistics
    public function dashboardStats()
    {
        // Calculate statistics for the dashboard
        $totalUsers = User::count();
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $totalVendors = Vendor::count();
        $pendingOrders = Order::where('status', Order::STATUS_PENDING)->count();
        
        // Calculate revenue
        $revenue = Order::whereIn('status', [
            Order::STATUS_COMPLETED, 
            Order::STATUS_PROCESSING
        ])->sum('total_amount');
        
        // Get counts by order status
        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
            
        // Popular products (most ordered)
        $popularProducts = DB::table('order_items')
            ->select('products.id', 'products.name', 'products.main_image_path', 
                DB::raw('SUM(order_items.quantity) as total_quantity'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->groupBy('products.id', 'products.name', 'products.main_image_path')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();
        
        return response()->json([
            'total_users' => $totalUsers,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_vendors' => $totalVendors,
            'pending_orders' => $pendingOrders,
            'revenue' => $revenue,
            'orders_by_status' => $ordersByStatus,
            'popular_products' => $popularProducts
        ]);
    }
    
    // Recent orders for dashboard
    public function recentOrders()
    {
        $recentOrders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        return response()->json($recentOrders);
    }
    
    // Recent users for dashboard
    public function recentUsers()
    {
        $recentUsers = User::orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        return response()->json($recentUsers);
    }
    
    // Sales chart data
    public function salesChart(Request $request)
    {
        $period = $request->period ?? 'week';
        $now = Carbon::now();
        
        switch ($period) {
            case 'day':
                $startDate = Carbon::now()->startOfDay();
                $endDate = Carbon::now();
                $groupBy = 'hour';
                $format = 'H:00';
                break;
                
            case 'week':
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                $endDate = Carbon::now();
                $groupBy = 'date';
                $format = 'M d';
                break;
                
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now();
                $groupBy = 'date';
                $format = 'M d';
                break;
                
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now();
                $groupBy = 'month';
                $format = 'M';
                break;
                
            default:
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                $endDate = Carbon::now();
                $groupBy = 'date';
                $format = 'M d';
        }
        
        // Get sales data
        $sales = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Format data for chart
        $labels = [];
        $data = [];
        
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            $labels[] = $date->format($format);
            
            $saleAmount = 0;
            foreach ($sales as $sale) {
                if ($sale->date === $formattedDate) {
                    $saleAmount = $sale->total;
                    break;
                }
            }
            
            $data[] = $saleAmount;
        }
        
        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }
}
