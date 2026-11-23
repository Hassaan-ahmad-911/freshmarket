<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\ProductSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ProductSearchRequest;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(ProductSearchRequest $request, ProductSearch $search): View
    {
        return view('shop.products.index', [
            'products' => $search->paginate($request->filters()),
            'categories' => Category::active()->get(),
        ]);
    }

    public function show(Product $product): View
    {
        // Inactive products and products of pending/suspended stores look like they don't exist.
        abort_unless($product->isVisible(), 404);

        $product->load(['supplier.deliveryAreas', 'category', 'images']);

        return view('shop.products.show', [
            'product' => $product,
            'moreFromStore' => Product::visible()
                ->whereBelongsTo($product->supplier)
                ->whereKeyNot($product->id)
                ->with(['supplier', 'category', 'images'])
                ->inRandomOrder()
                ->limit(4)
                ->get(),
        ]);
    }
}
