<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierCreditUsage extends Model
{
    protected $table = 'supplier_credit_usages';

    protected $fillable = [
        'supplier_id',
        'supplier_payment_id',
        'amount',
    ];

    /**
     * Total credit earned by supplier (sum of all return amounts).
     */
    public static function totalEarned($supplier_id)
    {
        return StockProduct::where('event', 'supplier_return')
            ->where('is_archived', 0)
            ->where('type', 'supplier')
            ->where('supplier_id', $supplier_id)
            ->get()
            ->sum(fn($r) => abs($r->stock) * $r->price);
    }

    /**
     * Total credit used by supplier.
     */
    public static function totalUsed($supplier_id)
    {
        return self::where('supplier_id', $supplier_id)->sum('amount');
    }

    /**
     * Available credit balance for supplier.
     */
    public static function available($supplier_id)
    {
        return max(0, round(self::totalEarned($supplier_id) - self::totalUsed($supplier_id), 2));
    }
}
