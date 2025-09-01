<div class="fi-sidebar-item-button relative">
    <button
        wire:click="toggleDropdown"
        type="button"
        class="fi-sidebar-item-button flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-start outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
        :class="{ 'bg-gray-50 dark:bg-white/5': @js($showDropdown) }"
    >
        <div class="fi-sidebar-item-icon flex h-6 w-6 items-center justify-center">
            @if($this->selectedAssetConfiguration)
                @if($this->selectedAssetConfiguration->icon)
                    <x-filament::icon
                        :icon="$this->selectedAssetConfiguration->icon"
                        class="h-6 w-6 text-gray-400 dark:text-gray-500"
                        :style="'color: ' . ($this->selectedAssetConfiguration->color ?? 'inherit')"
                    />
                @else
                    <x-filament::icon
                        icon="heroicon-o-user"
                        class="h-6 w-6 text-gray-400 dark:text-gray-500"
                    />
                @endif
            @else
                <x-filament::icon
                    icon="heroicon-o-user-plus"
                    class="h-6 w-6 text-gray-400 dark:text-gray-500"
                />
            @endif
        </div>

        <div class="fi-sidebar-item-label flex-1 truncate text-sm font-bold text-left text-gray-700 dark:text-gray-200" style="font-weight: 700 !important; text-align: left !important;">
            {{ $this->selectedAssetConfiguration ? $this->selectedAssetConfiguration->name : 'Select Asset Configuration' }}
        </div>

        <x-filament::icon
            icon="heroicon-m-chevron-down"
            class="h-4 w-4 text-gray-400 transition-transform duration-200"
            :class="$showDropdown ? 'rotate-180' : ''"
        />
    </button>

    @if($showDropdown)
        <div class="fi-dropdown-panel absolute left-0 top-full z-50 mt-1 overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="min-width: 320px; width: max-content; max-width: 500px;">
            <!-- Search Input -->
            <div class="p-3 border-b border-gray-200 dark:border-white/10">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Search asset configurations..."
                        class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                        @if($showDropdown) autofocus @endif
                    />
                    <x-filament::icon
                        icon="heroicon-m-magnifying-glass"
                        class="absolute right-3 top-2.5 h-4 w-4 text-gray-400"
                    />
                </div>
            </div>
            <div class="fi-dropdown-list max-h-60 overflow-auto py-1">
                @if($this->assetConfigurations->count() > 0)
                    @foreach($this->assetConfigurations as $assetConfiguration)
                        <button
                            wire:click="selectAssetConfiguration({{ $assetConfiguration->id }})"
                            type="button"
                            class="fi-dropdown-list-item flex w-full items-center gap-x-3 px-3 py-2 text-start text-sm outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                            @class([
                                'bg-gray-50 dark:bg-white/5' => $selectedAssetOwnerId === $assetConfiguration->id,
                            ])
                        >
                            <div class="flex h-6 w-6 items-center justify-center">
                                @if($assetConfiguration->icon)
                                    <x-filament::icon
                                        :icon="$assetConfiguration->icon"
                                        class="h-5 w-5"
                                        :style="'color: ' . ($assetConfiguration->color ?? 'inherit')"
                                    />
                                @else
                                    <x-filament::icon
                                        icon="heroicon-o-user"
                                        class="h-5 w-5 text-gray-400 dark:text-gray-500"
                                    />
                                @endif
                            </div>

                            <div class="flex-1">
                                <div class="font-bold text-left text-gray-950 dark:text-white" style="font-weight: 700 !important; text-align: left !important;">
                                    {{ $assetConfiguration->name }}
                                </div>
                                @if($assetConfiguration->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ Str::limit($assetConfiguration->description, 50) }}
                                    </div>
                                @endif
                            </div>
                        </button>
                    @endforeach

                    <div class="border-t border-gray-200 dark:border-white/10"></div>
                    <button
                        wire:click="selectAssetConfiguration(null)"
                        type="button"
                        class="fi-dropdown-list-item flex w-full items-center gap-x-3 px-3 py-2 text-start text-sm outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 text-gray-500 dark:text-gray-400"
                    >
                        <x-filament::icon
                            icon="heroicon-o-x-mark"
                            class="h-5 w-5"
                        />
                        Clear Selection
                    </button>
                @else
                    <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                        No asset owners available
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
