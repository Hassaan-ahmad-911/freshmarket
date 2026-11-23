<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Creates or updates a product together with any newly uploaded images.
 *
 * Files are written to disk first; if the database part then fails we delete
 * those files again, so no orphan images are left behind.
 */
class SaveProduct
{
    /**
     * @param  array{category_id: int, name: string, description: ?string, price: int, unit: ?string, stock: int, low_stock_threshold: int, is_active: bool}  $data
     * @param  list<UploadedFile>  $images
     */
    public function create(Supplier $supplier, array $data, array $images = []): Product
    {
        return $this->withImages($images, $supplier, fn (array $paths) => DB::transaction(function () use ($supplier, $data, $paths) {
            $product = Product::create($data + [
                'supplier_id' => $supplier->id,
                // A random suffix keeps slugs unique across all stores ("apples-x7k2q").
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            ]);
            $this->attachImages($product, $paths);

            return $product;
        }));
    }

    /** @param  list<UploadedFile>  $images */
    public function update(Product $product, array $data, array $images = []): Product
    {
        return $this->withImages($images, $product->supplier, function (array $paths) use ($product, $data) {
            DB::transaction(function () use ($product, $data, $paths) {
                $product->update($data);
                $this->attachImages($product, $paths);
            });

            return $product->refresh();
        });
    }

    /**
     * @param  list<UploadedFile>  $images
     * @param  callable(list<string>): Product  $callback
     */
    private function withImages(array $images, Supplier $supplier, callable $callback): Product
    {
        $paths = array_map(
            fn (UploadedFile $file) => $file->store("products/{$supplier->id}", 'public'),
            $images,
        );

        try {
            return $callback($paths);
        } catch (Throwable $e) {
            Storage::disk('public')->delete($paths);
            throw $e;
        }
    }

    /** @param  list<string>  $paths */
    private function attachImages(Product $product, array $paths): void
    {
        $nextPosition = (int) $product->images()->max('sort_order') + 1;

        foreach ($paths as $i => $path) {
            $product->images()->create(['path' => $path, 'sort_order' => $nextPosition + $i]);
        }
    }
}
