<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id'];

    /**
     * Get the user that owns the cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items in the cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate the total amount for the cart.
     */
    public function getTotalAmount()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
    }

    /**
     * Get the count of items in the cart.
     */
    public function getTotalItems()
    {
        return $this->items->sum('quantity');
    }
}
