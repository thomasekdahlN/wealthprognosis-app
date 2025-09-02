<x-filament-panels::page>
    @if($simulationConfiguration && $simulationAsset)
        <!-- Native Filament Header Section -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center space-x-2">
                    <x-filament::icon icon="heroicon-o-table-cells" class="w-5 h-5 text-primary-600" />
                    {{ $simulationAsset->name }}
                </div>
            </x-slot>

            <x-slot name="description">
                Detailed year-by-year breakdown for this asset in the {{ $simulationConfiguration->name }} simulation
            </x-slot>

            <!-- Native Filament Tabs -->
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="false"
                    tag="a"
                    :href="route('filament.admin.pages.simulation-dashboard', ['simulation_configuration_id' => $simulationConfiguration->id])"
                    icon="heroicon-o-chart-bar"
                >
                    Dashboard
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="false"
                    tag="a"
                    :href="route('filament.admin.pages.simulation-assets', ['simulation_configuration_id' => $simulationConfiguration->id])"
                    icon="heroicon-o-building-office-2"
                >
                    Assets
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="true"
                    icon="heroicon-o-table-cells"
                >
                    {{ $simulationAsset->name }} Years
                </x-filament::tabs.item>
            </x-filament::tabs>

            <!-- Single Row Info Display -->
            <div class="mt-6 overflow-x-auto">
                <div class="flex flex-nowrap items-center gap-6 whitespace-nowrap">
                    @if($simulationConfiguration->tax_country)
                        <div class="flex items-center space-x-2 shrink-0">
                            <x-filament::icon icon="heroicon-o-flag" class="w-4 h-4 text-gray-500" />
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Tax Country:</span>
                            <x-filament::badge color="primary">
                                {{ strtoupper($simulationConfiguration->tax_country) }}
                            </x-filament::badge>
                        </div>
                    @endif

                    @if($simulationConfiguration->prognosis_type)
                        <div class="flex items-center space-x-2 shrink-0">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="w-4 h-4 text-gray-500" />
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Scenario:</span>
                            <x-filament::badge color="success">
                                {{ ucfirst($simulationConfiguration->prognosis_type) }}
                            </x-filament::badge>
                        </div>
                    @endif

                    @if($simulationAsset->assetType)
                        <div class="flex items-center space-x-2 shrink-0">
                            <x-filament::icon :icon="$simulationAsset->assetType->icon ?: 'heroicon-o-building-office-2'" class="w-4 h-4 text-gray-500" />
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Asset Type:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $simulationAsset->assetType->name }}
                            </span>
                        </div>
                    @endif

                    @if($simulationAsset->group)
                        <div class="flex items-center space-x-2 shrink-0">
                            <x-filament::icon icon="heroicon-o-user-group" class="w-4 h-4 text-gray-500" />
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Group:</span>
                            <x-filament::badge color="gray">
                                {{ ucfirst($simulationAsset->group) }}
                            </x-filament::badge>
                        </div>
                    @endif

                    @if($simulationConfiguration->risk_tolerance)
                        <div class="flex items-center space-x-2 shrink-0">
                            <x-filament::icon icon="heroicon-o-shield-check" class="w-4 h-4 text-gray-500" />
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Risk:</span>
                            <x-filament::badge color="warning">
                                {{ $simulationConfiguration->risk_tolerance_label }}
                            </x-filament::badge>
                        </div>
                    @endif

                    <div class="flex items-center space-x-2 shrink-0">
                        <x-filament::icon icon="heroicon-o-calendar" class="w-4 h-4 text-gray-500" />
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Created:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $simulationConfiguration->created_at->format('M j, Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Native Filament Table Section -->
        <x-filament::section>
            <x-slot name="heading">
                Year-by-Year Data
            </x-slot>

            <x-slot name="description">
                Complete financial breakdown for {{ $simulationAsset->name }} across all simulation years
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    @endif
</x-filament-panels::page>
