<?php

namespace App\Domain\Suppliers\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('supplier_delivery_areas')]
#[Fillable(['supplier_id', 'name', 'normalized'])]
class DeliveryArea extends Model
{
    /** " Green  Park " and "green park" must match, so we compare a normalised form. */
    public static function normalize(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)));
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
