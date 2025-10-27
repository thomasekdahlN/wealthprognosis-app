<?php

namespace App\Filament\Concerns;

trait HasWideTable
{
    protected function enableWideTable(): void
    {
        // Livewire v3 browser event to enable page-wide horizontal scrolling
        $this->dispatch('wide-table-enable');
    }
}
