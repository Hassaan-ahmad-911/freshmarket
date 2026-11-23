<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\DeliveryArea;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Browse / search / filter products across ALL approved suppliers.
 * Used by the catalogue page and by each store page (with supplierId set).
 */
class ProductSearch
{
    public function paginate(ProductFilters $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->query($filters)
            ->with(['supplier', 'category', 'images'])
            ->paginate($perPage)
            ->withQueryString(); // keep ?q=...&category=... on the page links
    }

    public function query(ProductFilters $filters): Builder
    {
        $query = Product::query()->visible();

        if ($filters->search !== null && $filters->search !== '') {
            // Escape % and _ so a search for "100%" doesn't act as a wildcard.
            // "!" is the escape character; an explicit ESCAPE clause behaves the
            // same on MySQL and SQLite (SQLite has no default escape character).
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters->search).'%';
            $query->where(fn (Builder $q) => $q
                ->whereRaw("products.name like ? escape '!'", [$term])
                ->orWhereRaw("products.description like ? escape '!'", [$term]));
        }

        if ($filters->categorySlug) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $filters->categorySlug));
        }

        if ($filters->supplierId) {
            $query->where('products.supplier_id', $filters->supplierId);
        }

        if ($filters->minPrice !== null) {
            $query->where('products.price', '>=', $filters->minPrice);
        }

        if ($filters->maxPrice !== null) {
            $query->where('products.price', '<=', $filters->maxPrice);
        }

        if ($filters->deliveryArea) {
            $area = DeliveryArea::normalize($filters->deliveryArea);
            $query->whereHas('supplier.deliveryAreas', fn (Builder $q) => $q->where('normalized', $area));
        }

        if ($filters->inStockOnly) {
            $query->where('products.stock', '>', 0);
        }

        match ($filters->sort) {
            'price_asc' => $query->orderBy('products.price'),
            'price_desc' => $query->orderByDesc('products.price'),
            'name' => $query->orderBy('products.name'),
            default => $query->latest('products.created_at')->orderByDesc('products.id'),
        };

        return $query;
    }
}
