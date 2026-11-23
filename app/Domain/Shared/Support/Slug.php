<?php

namespace App\Domain\Shared\Support;

use Closure;
use Illuminate\Support\Str;

final class Slug
{
    /**
     * Turn "Green Valley Farm" into "green-valley-farm", adding "-2", "-3"...
     * while $exists says the slug is already taken.
     *
     * @param  Closure(string): bool  $exists
     */
    public static function unique(string $value, Closure $exists): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $counter = 2;

        while ($exists($slug)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
