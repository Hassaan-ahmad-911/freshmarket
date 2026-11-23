<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('shop.home', [
            'categories' => Category::active()->get(),
            'latestProducts' => Product::visible()->with(['supplier', 'category', 'images'])->latest()->limit(8)->get(),
            'stores' => Supplier::approved()->latest('approved_at')->limit(4)->get(),
        ]);
    }
}
