<?php

namespace App\Domain\Catalog\Services;

/**
 * A plain, typed bag of search options (a "DTO" — data transfer object).
 *
 * The HTTP layer builds it from the query string; the ProductSearch service
 * only ever sees this object, so search logic knows nothing about requests.
 * Prices are already converted to cents here.
 */
final readonly class ProductFilters
{
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'name'];

    public function __construct(
        public ?string $search = null,
        public ?string $categorySlug = null,
        public ?int $supplierId = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public ?string $deliveryArea = null,
        public bool $inStockOnly = false,
        public string $sort = 'newest',
    ) {}
}
