<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Order status constants that match your database enum
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';

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
    
    // Get all available order statuses
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_DECLINED,
            self::STATUS_CANCELLED,
        ];
    }
    
    // Check if order can be cancelled
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING
        ]);
    }
    
    // Cancel the order
    public function cancel(): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }
        
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }
    
    // Check if order is completed
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
    
    // Get human-readable status label
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
        
        return $labels[$this->status] ?? ucfirst($this->status);
    }
}
