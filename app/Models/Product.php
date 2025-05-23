<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'sale_price',
        'on_sale',
        'sku',  // Required field that was missing
        'stock_quantity',
        'low_stock_threshold',
        'stock_status',
        'manage_stock',
        'main_image_path',
        'weight',
        'length',
        'width',
        'height',
        'featured',
        'is_digital',
        'is_downloadable',
        'download_limit',
        'download_file_path',
        'status',
        'average_rating',
        'review_count',
    ];

    /**
     * Get the vendor that owns the product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the cart items for the product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the wishlist items for the product.
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * Check if product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Get all images for the product.
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable')
            ->orderBy('sort_order');
    }

    /**
     * Get the main image for the product.
     */
    public function mainImage()
    {
        return $this->morphOne(Image::class, 'imageable')
            ->where('type', 'main');
    }

    /**
     * Get gallery images for the product.
     */
    public function galleryImages()
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('type', 'gallery')
            ->orderBy('sort_order');
    }
}
