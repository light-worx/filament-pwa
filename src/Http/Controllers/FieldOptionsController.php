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
     * Query parameters (both optional):
     *   ?search=foo   — forwarded to the resolver for filtered/AJAX results
     *   ?value=123    — requests a targeted lookup of a single saved value
     *                   so the UI can restore its label after a page refresh.
     *                   The resolver receives the value as the $search argument
     *                   prefixed with "id:" so it can distinguish the two modes:
     *
     *                   PwaFieldOptions::register('circuit_id', fn(?string $search) =>
     *                       Circuit::when(
     *                           $search && str_starts_with($search, 'id:'),
     *                           fn($q) => $q->whereKey(ltrim($search, 'id:')),
     *                           fn($q) => $q->when($search, fn($q2) =>
     *                               $q2->where('circuit', 'like', "%{$search}%")
     *                           )
     *                       )->limit(50)->pluck('circuit', 'id')->toArray()
     *                   );
     *
     *                   For resolvers that don't handle the "id:" prefix, the
     *                   controller falls back to fetching all options (no search)
     *                   and finding the match client-side — same as before.
     *
     * Response: { "options": [{ "value": "1", "label": "Circuit Name" }, ...] }
     */
    public function __invoke(Request $request, string $key): JsonResponse
    {
        if (!$this->registry->has($key)) {
            return response()->json([
                'message' => "No options resolver registered for field '{$key}'. "
                           . "Register one using PwaFieldOptions::register('{$key}', ...).",
            ], 404);
        }

        // ?value= mode: restore label for a single saved value after page refresh.
        // We pass "id:{value}" as the search string so resolvers can detect it
        // and do a targeted WHERE IN / whereKey lookup instead of a LIKE search.
        if ($request->filled('value')) {
            $savedValue = $request->string('value')->toString();
            $options    = $this->registry->resolve($key, 'id:' . $savedValue);

            // If the resolver didn't handle the id: prefix (returned everything),
            // filter down to just the matching item so the response is minimal.
            $match = collect($options)->first(
                fn($o) => (string) $o['value'] === (string) $savedValue
            );

            return response()->json([
                'options' => $match ? [$match] : [],
            ]);
        }

        // Normal ?search= mode (or no params → return full/default list)
        $search  = $request->string('search')->toString() ?: null;
        $options = $this->registry->resolve($key, $search);

        return response()->json(['options' => $options]);
    }
}