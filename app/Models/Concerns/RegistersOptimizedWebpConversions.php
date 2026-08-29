<?php

namespace App\Models\Concerns;

/**
 * Shared WebP conversion for every Media Library-backed model, per the
 * mandatory-conversion rule in docs/adr/0002-spatie-media-library-for-images.md.
 */
trait RegistersOptimizedWebpConversions
{
    protected function registerOptimizedWebpConversion(string $name = 'webp'): void
    {
        $this->addMediaConversion($name)
            ->format('webp')
            ->optimize();
    }
}
