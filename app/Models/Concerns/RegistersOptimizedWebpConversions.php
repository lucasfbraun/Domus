<?php

namespace App\Models\Concerns;

trait RegistersOptimizedWebpConversions
{
    protected function registerOptimizedWebpConversion(string $name = 'webp'): void
    {
        $this->addMediaConversion($name)
            ->format('webp')
            ->optimize();
    }
}
