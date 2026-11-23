<?php

namespace App\Domain\Payouts\Services;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Domain\Payouts\Models\Transfer;
use App\Domain\Shared\Money\Money;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

/** The numbers on a supplier's earnings page. */
class EarningsReport
{
    /** @return array{gross: Money, commission: Money, net: Money, transferred: Money, reversed: Money, waiting: Money} */
    public function totals(Supplier $supplier): array
    {
        $orders = SupplierOrder::query()
            ->toBase()
            ->where('supplier_id', $supplier->id)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('orders')
                ->whereColumn('orders.id', 'supplier_orders.order_id')
                ->whereIn('orders.status', [OrderStatus::Paid->value, OrderStatus::PartiallyRefunded->value, OrderStatus::Refunded->value]))
            ->selectRaw('coalesce(sum(total), 0) as gross, coalesce(sum(commission), 0) as commission, coalesce(sum(payout_amount), 0) as net')
            ->first();

        $transfers = Transfer::query()->toBase()->where('supplier_id', $supplier->id)
            ->selectRaw("coalesce(sum(case when status = 'paid' then amount else 0 end), 0) as transferred")
            ->selectRaw('coalesce(sum(reversed_amount), 0) as reversed')
            ->selectRaw("coalesce(sum(case when status in ('pending', 'blocked', 'failed') then amount else 0 end), 0) as waiting")
            ->first();

        return [
            'gross' => Money::of((int) $orders->gross),
            'commission' => Money::of((int) $orders->commission),
            'net' => Money::of((int) $orders->net),
            'transferred' => Money::of((int) $transfers->transferred),
            'reversed' => Money::of((int) $transfers->reversed),
            'waiting' => Money::of((int) $transfers->waiting),
        ];
    }

    public function transfers(Supplier $supplier): Builder
    {
        return Transfer::query()
            ->whereBelongsTo($supplier)
            ->whereNot('status', TransferStatus::NotRequired)
            ->with('supplierOrder')
            ->latest();
    }
}
