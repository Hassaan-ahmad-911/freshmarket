<?php

namespace App\Domain\Shared;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Base class for every domain module's service provider.
 *
 * Each module (Catalog, Orders, Payments...) wires up ITS OWN policies,
 * event listeners and container bindings in its own provider. That way a
 * later phase adds behaviour by editing its module only — it never has to
 * reach into AppServiceProvider or another module.
 *
 * Laravel's ServiceProvider already understands the public $bindings and
 * $singletons arrays; this class adds $policies and $listen.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> Model class => Policy class */
    protected array $policies = [];

    /** @var array<class-string, list<class-string>> Event class => Listener classes */
    protected array $listen = [];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        $this->bootModule();
    }

    /** Hook for module-specific boot logic (routes, macros, etc). */
    protected function bootModule(): void {}
}
