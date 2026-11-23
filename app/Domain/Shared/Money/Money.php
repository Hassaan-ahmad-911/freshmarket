<?php

namespace App\Domain\Shared\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An amount of money, stored as an integer number of the smallest unit (cents).
 *
 * Why not floats? 0.1 + 0.2 === 0.30000000000000004 in PHP (and every language
 * using IEEE floats). Over thousands of orders those tiny errors add up and the
 * books stop balancing. Integers are exact, and Stripe's API also uses cents.
 *
 * The object is immutable: add() / subtract() return a NEW Money and never
 * change the original, so a value can't be modified behind your back.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    private const SYMBOLS = ['usd' => '$', 'eur' => '€', 'gbp' => '£', 'cad' => 'CA$', 'aud' => 'A$'];

    private function __construct(
        public int $amount,
        public string $currency,
    ) {}

    public static function of(int $cents, ?string $currency = null): self
    {
        return new self($cents, strtolower($currency ?? config('freshmarket.currency', 'usd')));
    }

    public static function zero(?string $currency = null): self
    {
        return self::of(0, $currency);
    }

    /** @param iterable<Money> $items */
    public static function sum(iterable $items, ?string $currency = null): self
    {
        $total = self::zero($currency);

        foreach ($items as $item) {
            $total = $total->add($item);
        }

        return $total;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $quantity): self
    {
        return new self($this->amount * $quantity, $this->currency);
    }

    /**
     * A percentage of this amount, given in basis points (1 bp = 0.01%).
     *
     * 10%  = 1000 bps, 12.5% = 1250 bps. Basis points let us express
     * fractional percentages with integers only. The result is rounded
     * half-up to the nearest cent: 15% of 333¢ = 49.95¢ → 50¢.
     */
    public function percentage(int $basisPoints): self
    {
        if ($basisPoints < 0) {
            throw new InvalidArgumentException('Basis points cannot be negative.');
        }

        $sign = $this->amount < 0 ? -1 : 1;
        $rounded = intdiv(abs($this->amount) * $basisPoints + 5000, 10000);

        return new self($sign * $rounded, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency && $this->amount === $other->amount;
    }

    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    /** "$1,234.50" — formatted with integer maths only, no float conversion. */
    public function format(): string
    {
        $absolute = abs($this->amount);
        $major = number_format(intdiv($absolute, 100));
        $minor = str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
        $symbol = self::SYMBOLS[$this->currency] ?? strtoupper($this->currency).' ';

        return ($this->amount < 0 ? '-' : '').$symbol.$major.'.'.$minor;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function jsonSerialize(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency, 'formatted' => $this->format()];
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot combine {$this->currency} with {$other->currency}.");
        }
    }
}
