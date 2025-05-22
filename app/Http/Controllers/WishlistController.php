<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    //Display the user's wishlist with items.
    public function index()
    {
        $user = Auth::user();
        $wishlist = $user->wishlist;
        
        if (!$wishlist) {
            return response()->json([
                'items' => []
            ]);
        }
        
        $wishlist->load('items.product.vendor');
        
        return response()->json([
            'items' => $wishlist->items
        ]);
    }
    
    // Add an item to the wishlist.
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);
        
        $user = Auth::user();
        $wishlist = $user->wishlist;
        
        // Create wishlist if it doesn't exist
        if (!$wishlist) {
            $wishlist = Wishlist::create(['user_id' => $user->id]);
            $user->wishlist()->save($wishlist);
        }
        
        // Check if item already exists in wishlist
        $exists = $wishlist->items()->where('product_id', $request->product_id)->exists();
        
        if (!$exists) {
            // Create new wishlist item
            $wishlistItem = new WishlistItem([
                'product_id' => $request->product_id
            ]);
            $wishlist->items()->save($wishlistItem);
            
            return response()->json([
                'message' => 'Item added to wishlist',
                'wishlist_item' => $wishlistItem->load('product')
            ]);
        }
        
        return response()->json([
            'message' => 'Item already in wishlist'
        ]);
    }
    
    // Remove an item from the wishlist.
    public function removeItem($id)
    {
        $user = Auth::user();
        $wishlistItem = WishlistItem::findOrFail($id);
        
        // Check if item belongs to user's wishlist
        if ($wishlistItem->wishlist->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $wishlistItem->delete();
        
        return response()->json([
            'message' => 'Item removed from wishlist'
        ]);
    }
    
    // Clear all items from the wishlist.
    public function clear()
    {
        $user = Auth::user();
        $wishlist = $user->wishlist;
        
        if ($wishlist) {
            $wishlist->items()->delete();
        }
        
        return response()->json([
            'message' => 'Wishlist cleared'
        ]);
    }
    
    //  Check if a product is in the user's wishlist.
    public function checkItem($productId)
    {
        $user = Auth::user();
        $wishlist = $user->wishlist;
        
        $inWishlist = false;
        if ($wishlist) {
            $inWishlist = $wishlist->items()->where('product_id', $productId)->exists();
        }
        
        return response()->json([
            'in_wishlist' => $inWishlist
        ]);
    }
    
    // Get the count of items in the wishlist.
    public function getCount()
    {
        $user = Auth::user();
        $wishlist = $user->wishlist;
        
        $count = 0;
        if ($wishlist) {
            $count = $wishlist->getItemCount();
        }
        
        return response()->json([
            'count' => $count
        ]);
    }
}
