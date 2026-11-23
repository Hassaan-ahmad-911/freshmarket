<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\ProductSearch;
use App\Domain\Suppliers\Models\Supplier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ProductSearchRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $stores = Supplier::approved()
            ->with('deliveryAreas')
            ->orderBy('name')
            ->paginate(12);

        // Count only products customers can actually see, per store.
        $counts = Product::visible()
            ->whereIn('supplier_id', $stores->pluck('id'))
            ->selectRaw('supplier_id, count(*) as aggregate')
            ->groupBy('supplier_id')
            ->pluck('aggregate', 'supplier_id');

        return view('shop.stores.index', compact('stores', 'counts'));
    }

    /** A store page is just the product search, locked to one supplier. */
    public function show(ProductSearchRequest $request, Supplier $supplier, ProductSearch $search): View
    {
        abort_unless($supplier->isApproved(), 404);

        return view('shop.stores.show', [
            'supplier' => $supplier->load('deliveryAreas'),
            'products' => $search->paginate($request->filters(supplierId: $supplier->id)),
            'categories' => Category::active()
                ->whereHas('products', fn (Builder $q) => $q->where('supplier_id', $supplier->id)->where('is_active', true))
                ->get(),
        ]);
    }
}
