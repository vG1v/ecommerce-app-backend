<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_id',
        'product_id'
    ];

    /**
     * Get the wishlist that owns the item.
     */
    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    /**
     * Get the product associated with this wishlist item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
