<?php

namespace App\Http\Requests\Shop;

use App\Domain\Catalog\Services\ProductFilters;
use App\Http\Requests\Concerns\ParsesMoney;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates the catalogue's query string (?q=apples&category=fruit&max_price=5). */
class ProductSearchRequest extends FormRequest
{
    use ParsesMoney;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', $this->moneyRule()],
            'max_price' => ['nullable', $this->moneyRule()],
            'area' => ['nullable', 'string', 'max:100'],
            'in_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(ProductFilters::SORTS)],
        ];
    }

    public function filters(?int $supplierId = null): ProductFilters
    {
        return new ProductFilters(
            search: $this->string('q')->trim()->value() ?: null,
            categorySlug: $this->input('category') ?: null,
            supplierId: $supplierId,
            minPrice: $this->cents('min_price'),
            maxPrice: $this->cents('max_price'),
            deliveryArea: $this->string('area')->trim()->value() ?: null,
            inStockOnly: $this->boolean('in_stock'),
            sort: $this->input('sort') ?: 'newest',
        );
    }
}
