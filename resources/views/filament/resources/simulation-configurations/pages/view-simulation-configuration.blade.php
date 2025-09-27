<x-filament-panels::page>
    <div x-data="{ activeTab: '{{ $this->activeTab }}' }" class="space-y-6">
        <!-- Simulation Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center space-x-4">
                @if($simulationConfiguration->icon)
                    <x-filament::icon
                        :icon="$simulationConfiguration->icon"
                        class="w-12 h-12"
                        :style="$simulationConfiguration->color ? 'color: ' . $simulationConfiguration->color : ''"
                    />
                @endif

                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $simulationConfiguration->name }}
                    </h1>

                    @if($simulationConfiguration->description)
                        <div class="mt-2 text-gray-600 dark:text-gray-400 prose prose-sm max-w-none">
                            {!! $simulationConfiguration->description !!}
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                        @if($simulationConfiguration->birth_year)
                            <span>Birth Year: {{ $simulationConfiguration->birth_year }}</span>
                        @endif

                        @if($simulationConfiguration->prognosis_type)
                            <span>Scenario: {{ ucfirst($simulationConfiguration->prognosis_type) }}</span>
                        @endif

                        @if($simulationConfiguration->tax_country)
                            <span>Tax Country: {{ strtoupper($simulationConfiguration->tax_country) }}</span>
                        @endif

                        @if($simulationConfiguration->group)
                            <span>Group: {{ ucfirst($simulationConfiguration->group) }}</span>
                        @endif
                    </div>

                    @php
                        $tags = $simulationConfiguration->tags;
                        if (is_string($tags)) {
                            $tags = json_decode($tags, true) ?: [];
                        }
                        if (!is_array($tags)) {
                            $tags = [];
                        }
                    @endphp
                    @if($tags && count($tags) > 0)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="text-right">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Total Assets: <span class="font-semibold text-gray-900 dark:text-white">{{ $totalAssets }}</span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Current Value: <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($totalCurrentValue, 0, ',', ' ') }} NOK</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="$this->activeTab === 'dashboard'"
                    x-on:click="activeTab = 'dashboard'"
                    icon="heroicon-o-chart-bar"
                >
                    Dashboard
                    <x-slot name="badge">
                        {{ $totalAssets }}
                    </x-slot>
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$this->activeTab === 'assets'"
                    x-on:click="activeTab = 'assets'"
                    icon="heroicon-o-building-office-2"
                >
                    Assets
                    <x-slot name="badge">
                        {{ $simulationAssets->count() }}
                    </x-slot>
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>

        <!-- Tab Content -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div x-show="activeTab === 'dashboard'" x-transition>
                <!-- Dashboard Tab Content -->
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Simulation Dashboard</h2>

                    <!-- Dashboard Widgets will be loaded here -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Summary Cards -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Assets</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalAssets }}</p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Portfolio Value</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalCurrentValue, 0, ',', ' ') }} NOK</p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Year Entries</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalYearEntries }}</p>
                        </div>
                    </div>

                    <!-- Asset Type Breakdown -->
                    @if($assetsByType->count() > 0)
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Assets by Type</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($assetsByType as $type => $assets)
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 capitalize">{{ $type }}</h4>
                                        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $assets->count() }} assets</p>
                                        <div class="mt-2 space-y-1">
                                            @foreach($assets->take(3) as $asset)
                                                <div class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $asset->name }}</div>
                                            @endforeach
                                            @if($assets->count() > 3)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">+{{ $assets->count() - 3 }} more</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div x-show="activeTab === 'assets'" x-transition>
                <!-- Assets Tab Content -->
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Simulation Assets</h2>

                    @if($simulationAssets->count() > 0)
                        <!-- Assets Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Asset</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Group</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tax Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Current Value</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Years</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($simulationAssets as $asset)
                                        @php
                                            $currentYearData = $asset->simulationAssetYears->where('year', $currentYear)->first();
                                            $currentValue = $currentYearData ? $currentYearData->asset_market_amount : 0;
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                            onclick="window.location.href='{{ route('filament.admin.pages.simulation-asset-years', ['configuration' => $simulationConfiguration->asset_configuration_id, 'simulation' => $simulationConfiguration->id, 'asset' => $asset->id]) }}'">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $asset->name }}</div>
                                                @if($asset->description)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $asset->description }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 capitalize">
                                                    {{ $asset->asset_type }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 capitalize">
                                                    {{ $asset->group }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white capitalize">
                                                {{ $asset->tax_type }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                {{ number_format($currentValue, 0, ',', ' ') }} NOK
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $asset->simulationAssetYears->count() }} years
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('filament.admin.pages.simulation-asset-years', ['configuration' => $simulationConfiguration->asset_configuration_id, 'simulation' => $simulationConfiguration->id, 'asset' => $asset->id]) }}"
                                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <x-filament::icon icon="heroicon-o-building-office-2" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">No assets found</h3>
                            <p class="text-gray-500 dark:text-gray-400">This simulation doesn't have any assets yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
