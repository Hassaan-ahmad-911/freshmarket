<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Support\Facades\Storage;

/**
 * Copies the demo photos (database/seeders/images, free-licence photos from
 * Wikimedia Commons — see docs/IMAGE_CREDITS.md) into the public storage disk
 * and attaches them to the seeded records.
 *
 * Products and supplier logos use the app's real fields (product_images,
 * suppliers.logo_path). Category photos and supplier cover photos are display-only
 * and live at a conventional path (categories/{slug}.jpg, suppliers/covers/{slug}.jpg).
 */
class DemoImages
{
    private static function source(string $relative): ?string
    {
        $path = database_path("seeders/images/{$relative}");

        return is_file($path) ? $path : null;
    }

    private static function publish(string $relative, string $destination): ?string
    {
        $source = self::source($relative);

        if ($source === null) {
            return null;
        }

        Storage::disk('public')->put($destination, file_get_contents($source));

        return $destination;
    }

    public static function product(Product $product, string $imageSlug): void
    {
        $path = self::publish("products/{$imageSlug}.jpg", "products/{$product->supplier_id}/{$imageSlug}.jpg");

        if ($path) {
            $product->images()->create(['path' => $path, 'sort_order' => 1]);
        }
    }

    public static function supplier(Supplier $supplier): void
    {
        if ($logo = self::publish("suppliers/{$supplier->slug}-logo.jpg", "suppliers/logos/{$supplier->slug}.jpg")) {
            $supplier->update(['logo_path' => $logo]);
        }

        self::publish("suppliers/{$supplier->slug}-cover.jpg", "suppliers/covers/{$supplier->slug}.jpg");
    }

    public static function categories(): void
    {
        foreach (glob(database_path('seeders/images/categories/*.jpg')) as $file) {
            self::publish('categories/'.basename($file), 'categories/'.basename($file));
        }
    }
}
