<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_number', 'status', 'total_amount',
        'tax_amount', 'shipping_amount', 'discount_amount',
        'shipping_name', 'shipping_address_line1', 'shipping_address_line2',
        'shipping_city', 'shipping_state', 'shipping_postal_code',
        'shipping_country', 'shipping_phone',
        'payment_method', 'payment_status', 'transaction_id', 'notes'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    
    // Generate a unique order number
    public static function generateOrderNumber(): string
    {
        $latestOrder = self::latest()->first();
        $orderNumber = 'ORD-' . date('Ymd') . '-';
        
        if (!$latestOrder) {
            return $orderNumber . '0001';
        }
        
        $lastOrderNumber = $latestOrder->order_number;
        $lastNumber = intval(substr($lastOrderNumber, -4));
        
        return $orderNumber . str_pad(($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
