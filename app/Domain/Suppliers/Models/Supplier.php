<?php

namespace App\Domain\Suppliers\Models;

use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use App\Domain\Suppliers\Enums\SupplierStatus;
use App\Models\User;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * An independent store on the marketplace.
 *
 * @property SupplierStatus $status
 * @property Money $min_order_amount
 */
#[UseFactory(SupplierFactory::class)]
#[Fillable(['owner_id', 'name', 'slug', 'description', 'logo_path', 'status', 'min_order_amount', 'commission_bps', 'approved_at', 'suspended_at'])]
class Supplier extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SupplierStatus::class,
            'min_order_amount' => MoneyCast::class,
            'commission_bps' => 'integer',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Everyone who works for this store: the owner plus staff. */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function staff(): HasMany
    {
        return $this->members()->whereKeyNot($this->owner_id);
    }

    public function deliveryAreas(): HasMany
    {
        return $this->hasMany(DeliveryArea::class)->orderBy('name');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(SupplierInvitation::class);
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', SupplierStatus::Approved);
    }

    public function isApproved(): bool
    {
        return $this->status === SupplierStatus::Approved;
    }

    public function isPending(): bool
    {
        return $this->status === SupplierStatus::Pending;
    }

    public function isSuspended(): bool
    {
        return $this->status === SupplierStatus::Suspended;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function hasMember(User $user): bool
    {
        return $user->supplier_id === $this->id;
    }

    /** The commission this store pays, in basis points (falls back to the platform default). */
    public function commissionBps(): int
    {
        return $this->commission_bps ?? (int) config('freshmarket.default_commission_bps');
    }

    public function deliversTo(string $area): bool
    {
        return $this->deliveryAreas()->where('normalized', DeliveryArea::normalize($area))->exists();
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /** Two-letter fallback shown when there is no logo, e.g. "GV". */
    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}
