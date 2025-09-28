<span class="relative inline-flex" style="display:inline-flex;flex-direction:row;align-items:center;vertical-align:middle;white-space:nowrap;">
    <x-filament::dropdown teleport focus-on-open placement="bottom-start" offset="8">
        <x-slot name="trigger">
    <button


        type="button"
        class="relative inline-flex flex-row flex-nowrap items-center whitespace-nowrap rounded-lg bg-white dark:bg-gray-900 px-3 pr-9 py-2 text-start outline-none overflow-hidden transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800 max-w-[24rem]" style="display:inline-flex;flex-direction:row;flex-wrap:nowrap;align-items:center;white-space:nowrap;line-height:1;"


    >
        <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center align-middle">
            @if($this->selectedAssetConfiguration)
                @if($this->selectedAssetConfiguration->icon)
                    <x-filament::icon
                        :icon="$this->selectedAssetConfiguration->icon"
                        class="h-14 w-14 text-gray-400 dark:text-gray-500"
                        :style="'color: ' . ($this->selectedAssetConfiguration->color ?? 'inherit')"
                    />
                @else
                    <x-filament::icon
                        icon="heroicon-o-user"
                        class="h-14 w-14 text-gray-400 dark:text-gray-500"
                    />
                @endif
            @else
                <x-filament::icon
                    icon="heroicon-o-user-plus"
                    class="h-14 w-14 text-gray-400 dark:text-gray-500"
                />
            @endif
        </span>

        <span aria-hidden="true" class="inline-block" style="width: 1ch;"></span>
            <span class="flex-auto min-w-0 truncate text-xl font-bold text-left text-gray-700 dark:text-gray-200" style="font-weight: 700 !important; text-align: left !important;">
            {{ $this->selectedAssetConfiguration ? $this->selectedAssetConfiguration->name : 'Select Asset Configuration' }}
        </span>

        <x-filament::icon
            icon="heroicon-m-chevron-down"
            class="absolute right-3 top-1/2 -translate-y-1/2 h-6 w-6 text-gray-400 transition-transform duration-200"

        />
    </button>


        </x-slot>
        <x-filament::dropdown.list class="min-w-[64rem] w-[64rem] max-w-[95vw] text-left bg-white dark:bg-gray-900 bg-opacity-100 dark:bg-opacity-100 border border-gray-200 dark:border-white/10 rounded-xl shadow-xl ring-1 ring-gray-950/5 dark:ring-white/10" style="background-color:#fff;">
            <div class="p-2 border-b border-gray-200 dark:border-white/10 sticky top-0 z-10 bg-white dark:bg-gray-900">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Search asset configurations..."
                        class="w-full px-3 pr-10 py-2 text-sm bg-white text-gray-900 placeholder-gray-500 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:border-gray-600"
                    />
                    <x-filament::icon
                        icon="heroicon-m-magnifying-glass"
                        class="absolute right-3 top-1/2 -translate-y-1/2 h-6 w-6 text-gray-400 pointer-events-none"
                    />
                </div>
            </div>

            @forelse($this->assetConfigurations as $assetConfiguration)
                <button
                    type="button"
                    wire:click="selectAssetConfiguration({{ $assetConfiguration->id }})"
                    class="fi-dropdown-list-item flex w-full items-center justify-start gap-x-3 px-3 py-2 text-start text-sm outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800"
                >
                    <div class="flex h-7 w-7 items-center justify-center">
                        @if($assetConfiguration->icon)
                            <x-filament::icon :icon="$assetConfiguration->icon" class="h-6 w-6" :style="'color: ' . ($assetConfiguration->color ?? 'inherit')" />
                        @else
                            <x-filament::icon icon="heroicon-o-user" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="text-left text-gray-950 dark:text-white font-bold" style="font-weight: 700 !important;">
                            {{ $assetConfiguration->name }}
                        </div>
                        @if($assetConfiguration->description)
                            <div class="text-left text-xs text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::limit(strip_tags((string) $assetConfiguration->description), 50) }}
                            </div>
                        @endif
                    </div>
                </button>
            @empty
                <div class="px-3 py-2 text-sm text-left text-gray-500 dark:text-gray-400">
                    No asset configurations available
                </div>
            @endforelse

            <div class="my-1 border-t border-gray-200 dark:border-white/10"></div>

            <button type="button" wire:click="selectAssetConfiguration(null)" class="fi-dropdown-list-item flex w-full items-center gap-x-3 px-3 py-2 text-start text-sm outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800 text-gray-600 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                Clear Selection
            </button>
        </x-filament::dropdown.list>

        </x-filament::dropdown>
</span>
