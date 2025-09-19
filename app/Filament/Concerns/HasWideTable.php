<?php

namespace App\Filament\Concerns;

trait HasWideTable
{
    protected function enableWideTable(): void
    {
        // Livewire v3 browser event to enable page-wide horizontal scrolling
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('wide-table-enable');
        }
    }
}

