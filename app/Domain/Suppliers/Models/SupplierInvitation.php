<?php

namespace App\Domain\Suppliers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supplier_id', 'invited_by', 'email', 'token_hash', 'expires_at', 'accepted_at'])]
class SupplierInvitation extends Model
{
    public const VALID_FOR_DAYS = 7;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /** Look an invitation up by the token from the email link. */
    public static function findByToken(string $plainToken): ?self
    {
        return static::query()->where('token_hash', self::hashToken($plainToken))->first();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }
}
