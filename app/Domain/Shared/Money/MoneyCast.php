<?php

namespace App\Domain\Shared\Money;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Lets an Eloquent model expose an integer "cents" column as a Money object.
 *
 *   protected function casts(): array { return ['subtotal' => MoneyCast::class]; }
 *
 *   $order->subtotal          // Money
 *   $order->subtotal = 1250;  // ints are accepted too
 *
 * @implements CastsAttributes<Money, Money|int>
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::of((int) $value, $attributes['currency'] ?? null);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return match (true) {
            $value === null => null,
            $value instanceof Money => $value->amount,
            is_int($value) => $value,
            default => throw new InvalidArgumentException("[$key] must be an integer number of cents or a Money instance."),
        };
    }
}
