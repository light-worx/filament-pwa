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
     * GET /app/field-options/{key}?search=foo
     *
     * Returns the resolved options for the given field key as JSON.
     * The optional `search` query parameter is forwarded to the resolver,
     * allowing server-side filtering for large lists.
     *
     * Response shape:
     *   {
     *     "options": [
     *       { "value": "1", "label": "Western Cape" },
     *       { "value": "2", "label": "Gauteng" }
     *     ]
     *   }
     *
     * 404 when no resolver is registered for the key — this prevents
     * probing for arbitrary keys and gives a clear error during development.
     */
    public function __invoke(Request $request, string $key): JsonResponse
    {
        if (!$this->registry->has($key)) {
            return response()->json([
                'message' => "No options resolver registered for field '{$key}'. "
                           . "Register one using PwaFieldOptions::register('{$key}', ...).",
            ], 404);
        }

        $search  = $request->string('search')->toString() ?: null;
        $options = $this->registry->resolve($key, $search);

        return response()->json(['options' => $options]);
    }
}