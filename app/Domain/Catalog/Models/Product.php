<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use App\Domain\Suppliers\Enums\SupplierStatus;
use App\Domain\Suppliers\Models\Supplier;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Money $price
 */
#[UseFactory(ProductFactory::class)]
#[Fillable(['supplier_id', 'category_id', 'name', 'slug', 'description', 'price', 'unit', 'stock', 'low_stock_threshold', 'is_active'])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const MAX_IMAGES = 6;

    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class,
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * What customers may see: active products of approved stores.
     * Every public query goes through this one scope, so the rule lives in one place.
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('products.is_active', true)
            ->whereHas('supplier', fn (Builder $q) => $q->where('status', SupplierStatus::Approved));
    }

    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    public function isVisible(): bool
    {
        return $this->is_active && ! $this->trashed() && $this->supplier->isApproved();
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }

    public function mainImageUrl(): ?string
    {
        return $this->images->first()?->url();
    }
}
