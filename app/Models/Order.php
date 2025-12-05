<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'buyer_id',
        'shop_id',
        'listing_id',
        'seller_id',
        'quantity',
        'unit_price',
        'total_amount',
        'shipping_fee',
        'discount_amount',
        'tax_amount',
        'final_amount',
        'status',
        'payment_method',
        'payment_status',
        'shipping_address',
        'note',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancel_reason',
        'coupon_code',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Relationships
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id')->orderBy('created_at', 'desc');
    }

    /**
     * Scopes
     */
    public function scopeByBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    public function scopeBySeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Helper Methods
     */
    public function canCancel()
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing']);
    }

    public function canUpdate()
    {
        return !in_array($this->status, ['completed', 'cancelled', 'refunded']);
    }

    public function isDelivered()
    {
        return in_array($this->status, ['delivered', 'completed']);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber()
    {
        $date = now()->format('Ymd');
        $lastOrder = self::whereDate('created_at', now())->latest('id')->first();
        $sequence = $lastOrder ? (intval(substr($lastOrder->order_number, -4)) + 1) : 1;
        
        return 'ORD-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate final amount
     */
    public function calculateFinalAmount()
    {
        $this->final_amount = $this->total_amount + $this->shipping_fee + $this->tax_amount - $this->discount_amount;
        return $this->final_amount;
    }
}
