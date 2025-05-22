<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = ['user_id'];

    /**
     * Get the user that owns the wishlist.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items in the wishlist.
     */
    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Get the count of items in the wishlist.
     */
    public function getItemCount()
    {
        return $this->items->count();
    }
}
