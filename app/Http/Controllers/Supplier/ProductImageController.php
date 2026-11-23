<?php

namespace App\Http\Controllers\Supplier;

use App\Domain\Catalog\Actions\DeleteProductImage;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductImage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProductImageController extends Controller
{
    /**
     * The route uses "scoped bindings": Laravel only finds {image} if it
     * belongs to {product}, so /products/1/images/99 can't delete product 2's image.
     */
    public function destroy(Product $product, ProductImage $image, DeleteProductImage $deleteImage): RedirectResponse
    {
        Gate::authorize('update', $product);

        $deleteImage->handle($image);

        return back()->with('status', 'Image removed.');
    }
}
