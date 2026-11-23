<?php

namespace App\Http\Controllers\Supplier;

use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $supplier = $request->user()->supplier;
        $products = Product::query()->whereBelongsTo($supplier);

        return view('supplier.dashboard', [
            'supplier' => $supplier,
            'productCount' => (clone $products)->count(),
            'activeCount' => (clone $products)->where('is_active', true)->count(),
            'lowStock' => (clone $products)->lowStock()->orderBy('stock')->limit(10)->get(),
            'staffCount' => $supplier->staff()->count(),
        ]);
    }
}
