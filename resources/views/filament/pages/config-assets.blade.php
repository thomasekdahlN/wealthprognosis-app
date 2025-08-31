<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->record)
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-4">
                <div class="flex items-center gap-3">
                    @if($this->record->icon)
                        <x-filament::icon
                            :icon="$this->record->icon"
                            class="h-8 w-8"
                            :style="'color: ' . ($this->record->color ?? 'inherit')"
                        />
                    @else
                        <x-filament::icon
                            icon="heroicon-o-user"
                            class="h-8 w-8 text-gray-400 dark:text-gray-500"
                        />
                    @endif
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ $this->record->name }}
                        </h2>
                        @if($this->record->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $this->record->description }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-lg shadow">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
