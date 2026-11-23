<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Supplier pages always work on "the store you belong to". If a supplier
 * account isn't linked to a store (shouldn't happen, but be safe), stop here.
 */
class EnsureUserBelongsToSupplier
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->supplier === null, 403, 'Your account is not linked to a store.');

        return $next($request);
    }
}
