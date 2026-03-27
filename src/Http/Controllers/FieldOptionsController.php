<?php

namespace Lightworx\FilamentPwa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lightworx\FilamentPwa\FieldOptions\FieldOptionsRegistry;

class FieldOptionsController extends Controller
{
    public function __construct(private FieldOptionsRegistry $registry) {}

    /**
     * GET /app/field-options/{key}
     *
     * Two modes, both optional:
     *
     *   ?search=foo   Forwarded directly to the resolver as $search.
     *                 Used for live filtering as the user types.
     *
     *   ?value=123    Label-restore mode. Called once on panel open to recover
     *                 the display label for a previously saved value.
     *
     *                 Strategy:
     *                 1. Call resolver with null — returns the default list.
     *                 2. If the saved value is in that list, return it. Done.
     *                 3. If not found (large list with limit()), call resolver
     *                    again with the raw value as $search so the developer's
     *                    resolver can do a targeted lookup if it wants to.
     *                 4. Return whichever match is found, or empty options.
     *
     *                 This means resolvers work with no changes for small lists.
     *                 For large lists, the developer can optionally handle the
     *                 case where $search is a numeric ID string:
     *
     *                   PwaFieldOptions::register('circuit_id', fn(?string $search) =>
     *                       Circuit::when(
     *                           is_numeric($search),
     *                           fn($q) => $q->whereKey($search),
     *                           fn($q) => $q->when($search,
     *                               fn($q2) => $q2->where('circuit', 'like', "%{$search}%")
     *                           )
     *                       )->limit(50)->pluck('circuit', 'id')->toArray()
     *                   );
     */
    public function __invoke(Request $request, string $key): JsonResponse
    {
        if (!$this->registry->has($key)) {
            return response()->json([
                'message' => "No options resolver registered for field '{$key}'. "
                           . "Register one using PwaFieldOptions::register('{$key}', ...).",
            ], 404);
        }

        // ── ?value= label-restore mode ────────────────────────────────────────
        if ($request->filled('value')) {
            $savedValue = (string) $request->input('value');

            // Step 1: call resolver with no search (returns default/full list)
            $options = $this->registry->resolve($key, null);
            $match   = collect($options)->first(
                fn($o) => (string) $o['value'] === $savedValue
            );

            // Step 2: not in default list — call resolver with the raw value
            // so it can do a targeted whereKey/find if it handles numeric IDs
            if (!$match) {
                $options = $this->registry->resolve($key, $savedValue);
                $match   = collect($options)->first(
                    fn($o) => (string) $o['value'] === $savedValue
                );
            }

            return response()->json([
                'options' => $match ? [$match] : [],
            ]);
        }

        // ── Normal ?search= or bare call ──────────────────────────────────────
        $search  = $request->filled('search')
            ? (string) $request->input('search')
            : null;

        $options = $this->registry->resolve($key, $search);

        return response()->json(['options' => $options]);
    }
}