<?php

use App\Domain\Shared\Money\Money;

it('adds, subtracts and multiplies without floats', function () {
    $price = Money::of(250, 'usd');                 // $2.50

    expect($price->multiply(3)->amount)->toBe(750)
        ->and($price->add(Money::of(99, 'usd'))->amount)->toBe(349)
        ->and($price->subtract(Money::of(300, 'usd'))->amount)->toBe(-50);
});

it('is immutable', function () {
    $original = Money::of(1000, 'usd');
    $original->add(Money::of(500, 'usd'));

    expect($original->amount)->toBe(1000);
});

it('calculates a percentage in basis points with half-up rounding', function (int $cents, int $bps, int $expected) {
    expect(Money::of($cents, 'usd')->percentage($bps)->amount)->toBe($expected);
})->with([
    '10% of $25.00' => [2500, 1000, 250],
    '15% of $3.33 (49.95¢ rounds up)' => [333, 1500, 50],
    '12.5% of $10.01 (125.125¢ rounds down)' => [1001, 1250, 125],
    '0% of anything' => [999, 0, 0],
    '100% of $7.77' => [777, 10000, 777],
    'negative amounts round symmetrically' => [-333, 1500, -50],
]);

it('sums a list of amounts', function () {
    $total = Money::sum([Money::of(100, 'usd'), Money::of(250, 'usd'), Money::of(5, 'usd')], 'usd');

    expect($total->amount)->toBe(355);
});

it('refuses to mix currencies', function () {
    Money::of(100, 'usd')->add(Money::of(100, 'eur'));
})->throws(InvalidArgumentException::class);

it('formats using integer maths', function (int $cents, string $currency, string $expected) {
    expect(Money::of($cents, $currency)->format())->toBe($expected);
})->with([
    [123456, 'usd', '$1,234.56'],
    [5, 'usd', '$0.05'],
    [-1999, 'eur', '-€19.99'],
    [100, 'jpy', 'JPY 1.00'],
]);

it('parses what people type into a form, without floats', function (string $input, int $cents) {
    expect(Money::fromDecimal($input, 'usd')->amount)->toBe($cents);
})->with([
    ['12', 1200],
    ['12.5', 1250],
    ['12.50', 1250],
    ['0.07', 7],
    [' 3.99 ', 399],
    ['19.99', 1999], // (int) (19.99 * 100) would give 1998 with floats!
]);

it('rejects malformed amounts', function (string $input) {
    Money::fromDecimal($input, 'usd');
})->throws(InvalidArgumentException::class)->with(['-1', '1.999', 'abc', '1,000', '']);

it('converts back to a form value', function () {
    expect(Money::of(1250, 'usd')->toDecimal())->toBe('12.50')
        ->and(Money::of(5, 'usd')->toDecimal())->toBe('0.05');
});

it('uses the configured default currency', function () {
    config(['freshmarket.currency' => 'gbp']);

    expect(Money::of(100)->currency)->toBe('gbp');
});
