<?php

namespace App\Http\Requests\Supplier;

use App\Domain\Catalog\Models\Product;
use App\Http\Requests\Concerns\ParsesMoney;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Used for both "create product" and "edit product". */
class ProductRequest extends FormRequest
{
    use ParsesMoney;

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? $this->user()->can('update', $product)
            : $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', $this->moneyRule(), 'not_regex:/^0+(\.0+)?$/'],
            'unit' => ['nullable', 'string', 'max:30'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:'.Product::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.regex' => 'Enter a price like 4 or 4.99.',
            'price.not_regex' => 'The price must be more than zero.',
        ];
    }

    /** A product can't end up with more than MAX_IMAGES images in total. */
    public function after(): array
    {
        return [function ($validator) {
            $existing = $this->route('product')?->images()->count() ?? 0;

            if ($existing + count($this->file('images', [])) > Product::MAX_IMAGES) {
                $validator->errors()->add('images', 'A product can have at most '.Product::MAX_IMAGES.' images.');
            }
        }];
    }

    /** Clean data ready for the SaveProduct action (price already in cents). */
    public function productData(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'category_id' => $this->integer('category_id'),
            'description' => $this->input('description'),
            'price' => $this->cents('price'),
            'unit' => $this->input('unit'),
            'stock' => $this->integer('stock'),
            'low_stock_threshold' => $this->integer('low_stock_threshold'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
