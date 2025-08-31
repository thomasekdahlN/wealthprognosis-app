<x-filament-panels::page>
    @if($simulationConfiguration)
        <div class="space-y-6">
            {{-- Summary Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">
                            {{ $totalAssets }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total Assets
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">
                            {{ $totalYearEntries }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Year Projections
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info-600">
                            {{ $assetsByType->count() }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Asset Types
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Assets by Type --}}
            @foreach($assetsByType as $assetType => $assets)
                <x-filament::section>
                    <x-slot name="heading">
                        {{ ucfirst(str_replace('_', ' ', $assetType)) }} Assets ({{ $assets->count() }})
                    </x-slot>

                    <div class="space-y-4">
                        @foreach($assets as $asset)
                            @php
                                $assetYears = $asset->simulationAssetYears;
                                $firstYear = $assetYears->first();
                                $lastYear = $assetYears->last();
                                $startValue = $firstYear->asset_market_amount ?? 0;
                                $endValue = $lastYear->asset_market_amount ?? 0;
                                $growth = $endValue - $startValue;
                                $totalIncome = $assetYears->sum('income_amount');
                                $totalExpenses = $assetYears->sum('expence_amount');
                            @endphp

                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $asset->name }}</h4>
                                        @if($asset->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $asset->description }}</p>
                                        @endif
                                        <div class="flex gap-2 mt-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                                {{ ucfirst($asset->group) }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                                {{ ucfirst(str_replace('_', ' ', $asset->tax_type)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $assetYears->count() }} years</div>
                                        @if($firstYear && $lastYear && $firstYear->year !== $lastYear->year)
                                            <div class="text-xs text-gray-500 dark:text-gray-500">
                                                {{ $firstYear->year }} - {{ $lastYear->year }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Financial Summary --}}
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500 uppercase tracking-wide">Start Value</div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ number_format($startValue, 0) }} NOK
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500 uppercase tracking-wide">End Value</div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ number_format($endValue, 0) }} NOK
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500 uppercase tracking-wide">Growth</div>
                                        <div class="text-sm font-medium {{ $growth >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                            {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 0) }} NOK
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500 uppercase tracking-wide">Net Income</div>
                                        <div class="text-sm font-medium {{ ($totalIncome - $totalExpenses) >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                            {{ number_format($totalIncome - $totalExpenses, 0) }} NOK
                                        </div>
                                    </div>
                                </div>

                                {{-- Year-by-Year Data (collapsed by default) --}}
                                @if($assetYears->count() > 0)
                                    <details class="mt-4">
                                        <summary class="cursor-pointer text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                            View Year-by-Year Projections ({{ $assetYears->count() }} years)
                                        </summary>
                                        <div class="mt-3 overflow-x-auto">
                                            <table class="w-full text-xs">
                                                <thead>
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <th class="text-left py-1 px-2 font-medium text-gray-700 dark:text-gray-300">Year</th>
                                                        <th class="text-right py-1 px-2 font-medium text-gray-700 dark:text-gray-300">Market Value</th>
                                                        <th class="text-right py-1 px-2 font-medium text-gray-700 dark:text-gray-300">Income</th>
                                                        <th class="text-right py-1 px-2 font-medium text-gray-700 dark:text-gray-300">Expenses</th>
                                                        <th class="text-right py-1 px-2 font-medium text-gray-700 dark:text-gray-300">Net</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($assetYears as $year)
                                                        <tr class="border-b border-gray-100 dark:border-gray-800">
                                                            <td class="py-1 px-2 font-medium">{{ $year->year }}</td>
                                                            <td class="py-1 px-2 text-right">{{ number_format($year->asset_market_amount ?? 0, 0) }}</td>
                                                            <td class="py-1 px-2 text-right text-success-600">{{ number_format($year->income_amount ?? 0, 0) }}</td>
                                                            <td class="py-1 px-2 text-right text-danger-600">{{ number_format($year->expence_amount ?? 0, 0) }}</td>
                                                            <td class="py-1 px-2 text-right font-medium">
                                                                @php $net = ($year->income_amount ?? 0) - ($year->expence_amount ?? 0); @endphp
                                                                <span class="{{ $net >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                                                    {{ $net >= 0 ? '+' : '' }}{{ number_format($net, 0) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endforeach

            {{-- Back to Dashboard --}}
            <div class="flex justify-center">
                <x-filament::button
                    tag="a"
                    :href="route('filament.admin.pages.simulation-dashboard', ['simulation_configuration_id' => $simulationConfiguration->id])"
                    icon="heroicon-o-chart-bar"
                    color="primary"
                >
                    Back to Dashboard
                </x-filament::button>
            </div>
        </div>
    @else
        <x-filament::section>
            <div class="text-center py-12">
                <div class="text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-4" />
                    <h3 class="text-lg font-medium mb-2">Simulation Not Found</h3>
                    <p>The requested simulation configuration could not be found or you don't have access to it.</p>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
