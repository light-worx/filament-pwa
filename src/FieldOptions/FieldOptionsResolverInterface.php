<?php

namespace Lightworx\FilamentPwa\FieldOptions;

/**
 * Implement this interface to create a class-based field options resolver.
 *
 * Example:
 *
 *   class RegionOptionsResolver implements FieldOptionsResolverInterface
 *   {
 *       public function __construct(private RegionRepository $regions) {}
 *
 *       public function resolve(?string $search = null): array
 *       {
 *           return $this->regions
 *               ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
 *               ->orderBy('name')
 *               ->pluck('name', 'id')
 *               ->toArray();
 *       }
 *   }
 *
 *   // Registration in AppServiceProvider:
 *   PwaFieldOptions::register('region', RegionOptionsResolver::class);
 */
interface FieldOptionsResolverInterface
{
    /**
     * Return the options for this field.
     *
     * @param  string|null $search  Optional search term supplied by the AJAX endpoint.
     *                              Null when called at render time (no search).
     * @return array  Associative ['value' => 'Label', ...] or
     *                normalised   [['value' => '', 'label' => ''], ...]
     */
    public function resolve(?string $search = null): array;
}