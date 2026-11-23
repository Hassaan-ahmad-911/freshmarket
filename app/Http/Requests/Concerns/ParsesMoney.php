<?php

namespace App\Http\Requests\Concerns;

use App\Domain\Shared\Money\Money;

/** Helpers for forms where people type amounts like "12.50". */
trait ParsesMoney
{
    /** Validation rule: a non-negative amount with up to 2 decimals. */
    protected function moneyRule(): string
    {
        return 'regex:'.Money::DECIMAL_PATTERN;
    }

    /** "12.50" → 1250. Returns null when the field was left empty. */
    protected function cents(string $key): ?int
    {
        $value = $this->input($key);

        return ($value === null || $value === '') ? null : Money::fromDecimal((string) $value)->amount;
    }
}
