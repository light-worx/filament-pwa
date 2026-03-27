<?php

namespace Lightworx\FilamentPwa\Facades;

use Illuminate\Support\Facades\Facade;
use Lightworx\FilamentPwa\FieldOptions\FieldOptionsRegistry;

/**
 * Facade for registering dynamic user-field option resolvers.
 *
 * ── Usage in AppServiceProvider::boot() ──────────────────────────────────────
 *
 *   use Lightworx\FilamentPwa\Facades\PwaFieldOptions;
 *
 *   // Closure (simple, for small lists)
 *   PwaFieldOptions::register('region', fn() =>
 *       Region::orderBy('name')->pluck('name', 'id')->toArray()
 *   );
 *
 *   // Closure with search support (used when 'searchable' => true in config)
 *   PwaFieldOptions::register('product', fn(?string $search) =>
 *       Product::when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
 *               ->orderBy('name')
 *               ->limit(50)
 *               ->pluck('name', 'id')
 *               ->toArray()
 *   );
 *
 *   // Class-based resolver (resolved via the container — supports DI)
 *   PwaFieldOptions::register('region', RegionOptionsResolver::class);
 *
 * @method static void register(string $key, \Closure|\Lightworx\FilamentPwa\FieldOptions\FieldOptionsResolverInterface|string $resolver)
 * @method static bool has(string $key)
 * @method static array resolve(string $key, ?string $search = null)
 *
 * @see FieldOptionsRegistry
 */
class PwaFieldOptions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FieldOptionsRegistry::class;
    }
}