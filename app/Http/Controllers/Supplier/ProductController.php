<?php

namespace App\Http\Controllers\Supplier;

use App\Domain\Catalog\Actions\SaveProduct;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\ProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        // whereBelongsTo(...) = "WHERE supplier_id = my store". Suppliers only ever list their own products.
        $products = Product::query()
            ->whereBelongsTo($request->user()->supplier)
            ->with(['category', 'images'])
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('supplier.products.index', compact('products'));
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('supplier.products.create', [
            'product' => new Product(['is_active' => true, 'stock' => 0, 'low_stock_threshold' => 5]),
            'categories' => Category::active()->get(),
        ]);
    }

    public function store(ProductRequest $request, SaveProduct $saveProduct): RedirectResponse
    {
        $product = $saveProduct->create($request->user()->supplier, $request->productData(), $request->file('images', []));

        return redirect()->route('supplier.products.edit', $product)->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('supplier.products.edit', [
            'product' => $product->load('images'),
            'categories' => Category::active()->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product, SaveProduct $saveProduct): RedirectResponse
    {
        $saveProduct->update($product, $request->productData(), $request->file('images', []));

        return back()->with('status', 'Product saved.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete(); // soft delete — past orders still reference it

        return redirect()->route('supplier.products.index')->with('status', "\"{$product->name}\" deleted.");
    }

    /** Quick on/off switch from the product list. */
    public function toggle(Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', $product->is_active ? 'Product is now visible.' : 'Product hidden from customers.');
    }
}
