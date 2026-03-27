# Dynamic User Field Options

When a custom `select` field in the user settings panel needs its options populated
from an Eloquent model or any runtime data source, use the `PwaFieldOptions` facade
to register a **resolver** in your `AppServiceProvider`.

---

## 1. Register a resolver

```php
// app/Providers/AppServiceProvider.php

use Lightworx\FilamentPwa\Facades\PwaFieldOptions;

public function boot(): void
{
    // ── Closure resolver (simplest approach) ─────────────────────────────
    PwaFieldOptions::register('region', fn() =>
        Region::orderBy('name')->pluck('name', 'id')->toArray()
    );

    // ── Closure with search support (for searchable/AJAX fields) ─────────
    PwaFieldOptions::register('product', fn(?string $search) =>
        Product::when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(50)
                ->pluck('name', 'id')
                ->toArray()
    );

    // ── Class-based resolver (supports constructor injection) ─────────────
    PwaFieldOptions::register('depot', DepotOptionsResolver::class);
}
```

The key passed to `register()` must match the `'key'` in your `pwa.user_fields` config.

---

## 2. Declare the field in config

```php
// config/pwa.php

'user_fields' => [

    // ── Static list — no resolver needed ─────────────────────────────────
    ['type' => 'select', 'key' => 'colour', 'label' => 'Favourite colour',
     'options' => ['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue']],

    // ── Dynamic (resolver, rendered server-side) ──────────────────────────
    // Options are resolved at render time and embedded in the HTML.
    // Best for lists under ~200 items that don't need search.
    ['type' => 'select', 'key' => 'region', 'label' => 'Region',
     'options' => 'dynamic'],

    // ── Dynamic + searchable (AJAX) ───────────────────────────────────────
    // Renders a search input. Fetches /app/field-options/product?search=…
    // on each keystroke (debounced 280 ms). Good for large lists.
    ['type' => 'select', 'key' => 'product', 'label' => 'Product',
     'options' => 'dynamic', 'searchable' => true,
     'placeholder' => 'Search products…'],

],
```

---

## 3. Class-based resolvers

Implement `FieldOptionsResolverInterface` for resolvers that need dependency injection
or more complex logic:

```php
use Lightworx\FilamentPwa\FieldOptions\FieldOptionsResolverInterface;

class DepotOptionsResolver implements FieldOptionsResolverInterface
{
    public function __construct(
        private DepotRepository $depots,
        private TenantService   $tenant,
    ) {}

    /**
     * @param  string|null $search  Supplied by searchable selects; null at render time.
     * @return array  ['id' => 'Depot name', ...]
     */
    public function resolve(?string $search = null): array
    {
        return $this->depots
            ->forTenant($this->tenant->current())
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(100)
            ->pluck('name', 'id')
            ->toArray();
    }
}
```

The class is resolved from Laravel's container, so constructor dependencies are
injected automatically. Register it by class name:

```php
PwaFieldOptions::register('depot', DepotOptionsResolver::class);
```

---

## 4. How options are returned

Resolvers may return either format — the registry normalises both:

```php
// Associative (most common with Eloquent pluck)
return ['1' => 'Western Cape', '2' => 'Gauteng'];

// Already-normalised (if building manually)
return [
    ['value' => '1', 'label' => 'Western Cape'],
    ['value' => '2', 'label' => 'Gauteng'],
];
```

---

## 5. The AJAX endpoint

Searchable selects call:

```
GET /app/field-options/{key}?search=cape
```

Response:
```json
{
    "options": [
        { "value": "1", "label": "Western Cape" },
        { "value": "4", "label": "Cape Town Metro" }
    ]
}
```

You can also call this endpoint directly from your own JS if you need options
in a custom `@stack('pwa-user-fields')` component.

**404** is returned when no resolver is registered for the key — this prevents
silent failures and makes misconfiguration obvious during development.

---

## 6. Saved values and label restoration

When the user settings panel opens, saved `custom_settings` values are restored.
For searchable selects, the package fetches the options list once more (with no
search term) to find the label for the saved value and display it in the widget.

For large lists where returning all options is expensive, have your resolver return
just the matching item when `$search` is `null` and a saved value needs to be shown.
One way to handle this:

```php
PwaFieldOptions::register('product', function (?string $search) {
    // If no search, return the full active list (max 200 items)
    // The JS will find the saved value's label from this list.
    return Product::active()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->orderBy('name')
        ->limit($search ? 50 : 200)
        ->pluck('name', 'id')
        ->toArray();
});
```