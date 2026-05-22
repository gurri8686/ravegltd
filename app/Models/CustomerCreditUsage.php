<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCreditUsage extends Model
{
    protected $table = 'customer_credit_usages';

    protected $fillable = [
        'customer_id',
        'customer_payment_id',
        'amount',
    ];

    /**
     * Total credit earned by customer (sum of all return amounts).
     */
    public static function totalEarned($customer_id)
    {
        return StockProduct::where('event', 'customer_return')
            ->where('is_archived', 0)
            ->where('type', 'customer')
            ->whereHas('customerInvoice', function ($q) use ($customer_id) {
                $q->where('customer_id', $customer_id);
            })
            ->get()
            ->sum(fn($r) => abs($r->stock) * $r->price);
    }

    /**
     * Total credit used by customer.
     */
    public static function totalUsed($customer_id)
    {
        return self::where('customer_id', $customer_id)->sum('amount');
    }

    /**
     * Available credit balance for customer.
     */
    public static function available($customer_id)
    {
        return max(0, round(self::totalEarned($customer_id) - self::totalUsed($customer_id), 2));
    }
}
