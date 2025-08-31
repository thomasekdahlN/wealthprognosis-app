<x-filament-panels::page>
    {{-- Chart Widget --}}
    <div class="mb-6">
        @livewire(\App\Filament\Widgets\ChangeRateChart::class, ['scenario' => $this->scenario, 'asset' => $this->asset])
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
