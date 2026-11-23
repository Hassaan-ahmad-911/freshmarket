<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class DeleteProductImage
{
    public function handle(ProductImage $image): void
    {
        $image->delete();
        Storage::disk('public')->delete($image->path);
    }
}
