<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    // Update order status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', Order::getStatuses())
        ]);

        $order = Order::with('items.product')->findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->status;
        
        try {
            DB::beginTransaction();
            
            // Handle inventory adjustments based on status changes
            if ($oldStatus === Order::STATUS_CANCELLED && in_array($newStatus, [Order::STATUS_PENDING, Order::STATUS_PROCESSING])) {
                // Re-deduct inventory if uncancelling an order
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $product->stock_quantity -= $item->quantity;
                        
                        // Prevent negative stock
                        if ($product->stock_quantity < 0) {
                            DB::rollBack();
                            return response()->json([
                                'message' => "Not enough stock to restore order for product: {$product->name}"
                            ], 400);
                        }
                        
                        $product->save();
                    }
                }
            } else if ($newStatus === Order::STATUS_CANCELLED && $oldStatus !== Order::STATUS_CANCELLED) {
                // Return inventory when cancelling
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $product->stock_quantity += $item->quantity;
                        $product->save();
                    }
                }
            } else if ($newStatus === Order::STATUS_DECLINED && $oldStatus !== Order::STATUS_DECLINED) {
                // Return inventory for declined orders too
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $product->stock_quantity += $item->quantity;
                        $product->save();
                    }
                }
            }
            
            // Update the order status
            $order->status = $newStatus;
            $order->save();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order->fresh()->load('items')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error updating order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // List all orders with filtering options
    public function index(Request $request)
    {
        $query = Order::with('user', 'items');
        
        // Filter by status if provided
        if ($request->has('status') && in_array($request->status, Order::getStatuses())) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        // Search by order number or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }
        
        // Sort orders
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $allowedSortFields = ['created_at', 'order_number', 'total_amount', 'status'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Paginate results
        $orders = $query->paginate($request->per_page ?? 15);
        
        return response()->json($orders);
    }

    // Get a specific order with detailed information
    public function show($id)
    {
        $order = Order::with(['user', 'items.product'])->findOrFail($id);
        
        return response()->json($order);
    }
}
