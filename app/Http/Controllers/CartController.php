<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // Display the user's cart with items.
    public function index()
    {
        $user = Auth::user();
        $cart = $user->cart;
        
        if (!$cart) {
            return response()->json([
                'items' => [],
                'total' => 0
            ]);
        }
        
        $cart->load('items.product.vendor');
        
        return response()->json([
            'items' => $cart->items,
            'total' => $cart->getTotalAmount()
        ]);
    }
    
    // Add an item to the cart.
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        $user = Auth::user();
        $cart = $user->cart;
        
        // Create cart if it doesn't exist
        if (!$cart) {
            $cart = Cart::create(['user_id' => $user->id]);
            $user->cart()->save($cart);
        }
        
        // Check if product exists and is in stock
        $product = Product::findOrFail($request->product_id);
        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'message' => 'Not enough stock available'
            ], 400);
        }
        
        // Check if item already exists in cart
        $cartItem = $cart->items()->where('product_id', $request->product_id)->first();
        
        if ($cartItem) {
            // Update quantity if item exists
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = new CartItem([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
            $cart->items()->save($cartItem);
        }
        
        return response()->json([
            'message' => 'Item added to cart',
            'cart_item' => $cartItem->load('product')
        ]);
    }
    
    // Update the quantity of a cart item.
    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);
        
        $user = Auth::user();
        $cartItem = CartItem::findOrFail($id);
        
        // Check if item belongs to user's cart
        if ($cartItem->cart->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Check stock
        if ($cartItem->product->stock_quantity < $request->quantity) {
            return response()->json([
                'message' => 'Not enough stock available'
            ], 400);
        }
        
        $cartItem->quantity = $request->quantity;
        $cartItem->save();
        
        return response()->json([
            'message' => 'Cart item updated',
            'cart_item' => $cartItem->load('product')
        ]);
    }
    
    // Remove an item from the cart.
    public function removeItem($id)
    {
        $user = Auth::user();
        $cartItem = CartItem::findOrFail($id);
        
        // Check if item belongs to user's cart
        if ($cartItem->cart->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $cartItem->delete();
        
        return response()->json([
            'message' => 'Item removed from cart'
        ]);
    }
    
    // Clear all items from the cart.
    public function clear()
    {
        $user = Auth::user();
        $cart = $user->cart;
        
        if ($cart) {
            $cart->items()->delete();
        }
        
        return response()->json([
            'message' => 'Cart cleared'
        ]);
    }
    
    // Get the count of items in the cart.
    public function getCount()
    {
        $user = Auth::user();
        $cart = $user->cart;
        
        $count = 0;
        if ($cart) {
            $count = $cart->getTotalItems();
        }
        
        return response()->json([
            'count' => $count
        ]);
    }
}
