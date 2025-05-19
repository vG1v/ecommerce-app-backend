<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'store_name',
        'slug',
        'description',
        'logo_path',
        'banner_path',
        'contact_email',
        'contact_phone', 
        'address_line1', 
        'address_line2', 
        'city', 
        'state', 
        'postal_code', 
        'country', 
        'business_registration_number', 
        'commission_rate', 
        'status', 
        'featured', 
        'average_rating', 
        'total_products', 
        'total_sales', 
    ];

    /**
     * Get the user that owns the vendor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for the vendor.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include active vendors.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include featured vendors.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
    
    /**
     * Generate a unique slug based on the store name.
     */
    public static function generateUniqueSlug($storeName)
    {
        $slug = \Illuminate\Support\Str::slug($storeName);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        
        return $count ? "{$slug}-{$count}" : $slug;
    }
}
