<?php

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Money $unit_price
 * @property Money $line_total
 * @property Money $commission
 */
#[Fillable(['order_id', 'supplier_order_id', 'product_id', 'product_name', 'unit', 'unit_price', 'quantity', 'line_total', 'commission'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::class,
            'line_total' => MoneyCast::class,
            'commission' => MoneyCast::class,
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
