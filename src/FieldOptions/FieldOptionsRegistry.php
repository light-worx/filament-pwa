<?php

namespace Lightworx\FilamentPwa\FieldOptions;

use Closure;
use InvalidArgumentException;

/**
 * Registry for dynamic user-field option resolvers.
 *
 * Resolvers can be:
 *   - A Closure:              fn() => ['value' => 'Label', ...]
 *   - A class name (string):  MyRegionOptionsResolver::class
 *   - An object instance:     new MyRegionOptionsResolver()
 *
 * Any class used as a resolver must implement FieldOptionsResolverInterface,
 * which requires a single resolve(): array method.
 *
 * ── Registration (in your AppServiceProvider::boot) ──────────────────────────
 *
 *   // Closure — simplest, fine for small lists
 *   PwaFieldOptions::register('region', fn() =>
 *       Region::orderBy('name')->pluck('name', 'id')->toArray()
 *   );
 *
 *   // Class name — resolved from the container, good for injecting dependencies
 *   PwaFieldOptions::register('region', RegionOptionsResolver::class);
 *
 *   // Object — when you need to pass constructor args
 *   PwaFieldOptions::register('region', new RegionOptionsResolver($tenantId));
 *
 * ── Config (pwa.php) ─────────────────────────────────────────────────────────
 *
 *   'user_fields' => [
 *       // Inline static list — no resolver needed
 *       ['type' => 'select', 'key' => 'colour', 'label' => 'Colour',
 *        'options' => ['red' => 'Red', 'blue' => 'Blue']],
 *
 *       // Dynamic — resolved at render time (embedded in HTML)
 *       ['type' => 'select', 'key' => 'region', 'label' => 'Region',
 *        'options' => 'dynamic'],
 *
 *       // Dynamic + searchable — fetched via AJAX on panel open,
 *       // supports server-side search for large lists
 *       ['type' => 'select', 'key' => 'product', 'label' => 'Product',
 *        'options' => 'dynamic', 'searchable' => true, 'placeholder' => 'Search products…'],
 *   ],
 */
class FieldOptionsRegistry
{
    /** @var array<string, Closure|FieldOptionsResolverInterface|string> */
    private array $resolvers = [];

    /**
     * Register a resolver for a field key.
     *
     * @param string $key      Must match the 'key' in pwa.user_fields config.
     * @param Closure|FieldOptionsResolverInterface|string $resolver
     */
    public function register(string $key, Closure|FieldOptionsResolverInterface|string $resolver): void
    {
        $this->resolvers[$key] = $resolver;
    }

    /**
     * Check whether a resolver has been registered for a key.
     */
    public function has(string $key): bool
    {
        return isset($this->resolvers[$key]);
    }

    /**
     * Resolve options for a field key.
     * Returns ['value' => 'Label', ...] or [['value'=>'', 'label'=>''], ...].
     * Returns an empty array if no resolver is registered.
     *
     * @param  string      $key
     * @param  string|null $search  Optional search term for filtered/AJAX results.
     * @return array
     */
    public function resolve(string $key, ?string $search = null): array
    {
        if (!$this->has($key)) {
            return [];
        }

        $resolver = $this->resolvers[$key];

        // Instantiate class-name resolvers via the container
        if (is_string($resolver)) {
            $resolver = app($resolver);
        }

        if ($resolver instanceof Closure) {
            // Pass $search only if the closure accepts a parameter
            $rf = new \ReflectionFunction($resolver);
            $options = $rf->getNumberOfParameters() > 0
                ? $resolver($search)
                : $resolver();
        } elseif ($resolver instanceof FieldOptionsResolverInterface) {
            $options = $resolver->resolve($search);
        } else {
            throw new InvalidArgumentException(
                "Resolver for field '{$key}' must be a Closure, class name, or FieldOptionsResolverInterface instance."
            );
        }

        return $this->normalise($options);
    }

    /**
     * Normalise options into a consistent [['value'=>, 'label'=>], ...] format,
     * accepting both associative arrays and already-normalised arrays.
     */
    private function normalise(array $options): array
    {
        $out = [];
        foreach ($options as $value => $label) {
            if (is_array($label)) {
                // Already in ['value' => ..., 'label' => ...] shape
                $out[] = [
                    'value' => $label['value'] ?? $value,
                    'label' => $label['label'] ?? $label['name'] ?? (string) $value,
                ];
            } else {
                $out[] = ['value' => (string) $value, 'label' => (string) $label];
            }
        }
        return $out;
    }
}