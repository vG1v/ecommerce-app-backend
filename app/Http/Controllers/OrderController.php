<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        $user = Auth::user();
        $orders = $user->orders()->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($orders);
    }

    // Display recent orders.
    public function getRecentOrders()
    {
        $user = Auth::user();
        $recentOrders = $user->orders()
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'recent_orders' => $recentOrders
        ]);
    }

    // Display the user's order statistics.
    public function getOrderStats()
    {
        $user = Auth::user();
        $totalOrders = $user->orders()->count();
        $totalSpent = $user->getTotalSpent();

        return response()->json([
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent
        ]);
    }

    // Store a newly created order from cart.
    public function store(Request $request)
    {
        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_address_line1' => 'required|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'required|string|max:100',
            'shipping_postal_code' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:100',
            'shipping_phone' => 'required|string|max:20',
            'payment_method' => 'required|string|max:50',
            'notes' => 'nullable|string'
        ]);

        $user = Auth::user();
        $cart = $user->cart;

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Calculate totals
            $totalAmount = 0;
            $taxAmount = 0;
            $shippingAmount = 0;

            // Base the tax and shipping on your business logic
            if ($cart->getTotalAmount() > 100) {
                $shippingAmount = 0;
            } else {
                $shippingAmount = 10;
            }

            $taxAmount = $cart->getTotalAmount() * 0.1; // 10% tax
            $totalAmount = $cart->getTotalAmount() + $taxAmount + $shippingAmount;

            // Create the order
            $order = new Order([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'shipping_name' => $request->shipping_name,
                'shipping_address_line1' => $request->shipping_address_line1,
                'shipping_address_line2' => $request->shipping_address_line2,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_postal_code' => $request->shipping_postal_code,
                'shipping_country' => $request->shipping_country,
                'shipping_phone' => $request->shipping_phone,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'notes' => $request->notes,
            ]);

            $order->save();

            // Create order items and reduce stock
            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;

                // Check if we have enough stock
                if ($product->stock_quantity < $cartItem->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Not enough stock for product: {$product->name}"
                    ], 400);
                }
                $orderItem = new OrderItem([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $cartItem->quantity * $product->price
                ]);

                $order->items()->save($orderItem);
                $product->stock_quantity -= $cartItem->quantity;
                $product->save();
            }
            $cart->items()->delete();

            DB::commit();

            // Return the created order with items
            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load('items')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Display the specified order.
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)
            ->where('id', $id)
            ->with('items.product')
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json($order);
    }

    // Cancel an order if it's still pending.
    public function cancel($id)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled'
            ], 400);
        }

        try {
            DB::beginTransaction();
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->stock_quantity += $item->quantity;
                    $product->save();
                }
            }

            $order->status = 'cancelled';
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error cancelling order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', Order::getStatuses())
        ]);

        $user = Auth::user();
        $order = Order::where('id', $id)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        // Store old status for potential inventory adjustments
        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Perform status-specific validations
        if ($newStatus === Order::STATUS_CANCELLED && !$order->canBeCancelled()) {
            return response()->json([
                'message' => 'This order cannot be cancelled at its current status'
            ], 400);
        }

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
            }

            // Update the order status
            $order->status = $newStatus;
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order->load('items')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error updating order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}