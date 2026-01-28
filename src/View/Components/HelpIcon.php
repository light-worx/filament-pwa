<?php

namespace LightWorx\FilamentIssues\View\Components;

use Illuminate\View\Component;
use LightWorx\FilamentIssues\Models\HelpDocument;

class HelpIcon extends Component
{
    public ?HelpDocument $helpDocument = null;

    public function __construct()
    {
        $this->helpDocument = $this->getContextualHelp();
    }

    protected function getContextualHelp(): ?HelpDocument
    {
        $currentRoute = request()->route()?->getName();
        
        if (!$currentRoute) {
            return null;
        }

        // Try exact route match first
        $helpDoc = HelpDocument::where('slug', $currentRoute)
            ->where('is_published', true)
            ->first();

        if ($helpDoc) {
            return $helpDoc;
        }

        // Try partial matching for resource routes
        // e.g., 'filament.admin.resources.users.index' -> 'users.index'
        $routeParts = explode('.', $currentRoute);
        
        // Try various slug patterns
        $slugPatterns = [
            implode('.', array_slice($routeParts, -2)), // Last 2 parts
            implode('.', array_slice($routeParts, -3)), // Last 3 parts
            end($routeParts), // Last part only
        ];

        foreach ($slugPatterns as $pattern) {
            $helpDoc = HelpDocument::where('slug', $pattern)
                ->where('is_published', true)
                ->first();
                
            if ($helpDoc) {
                return $helpDoc;
            }
        }

        return null;
    }

    public function render()
    {
        return view('filament-pwa::components.help-icon');
    }
}