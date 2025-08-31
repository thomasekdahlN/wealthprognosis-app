<x-filament-panels::page>
    @if($simulationConfiguration)
        <div class="space-y-6">
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600">
                            {{ number_format($summary['total_start_value'], 0) }} NOK
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Starting Value
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600">
                            {{ number_format($summary['total_end_value'], 0) }} NOK
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Projected End Value
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $summary['net_growth'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ number_format($summary['net_growth'], 0) }} NOK
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Net Growth
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $summary['net_income'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                            {{ number_format($summary['net_income'], 0) }} NOK
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Net Income
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Simulation Details --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-filament::section>
                    <x-slot name="heading">
                        Simulation Overview
                    </x-slot>

                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Configuration:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $simulationConfiguration->assetConfiguration->name }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Simulation Type:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $simulationConfiguration->prognosis_type ?? 'N/A') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Asset Scope:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $simulationConfiguration->asset_scope ?? 'N/A') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Time Period:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                @if($summary['years_span']['start'] && $summary['years_span']['end'])
                                    {{ $summary['years_span']['start'] }} - {{ $summary['years_span']['end'] }} ({{ $summary['years_span']['duration'] }} years)
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Assets Simulated:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $summary['assets_count'] }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Created:</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $simulationConfiguration->created_at->format('M j, Y \a\t g:i A') }}</span>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        Financial Summary
                    </x-slot>

                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Income:</span>
                            <span class="text-sm text-success-600 font-medium">{{ number_format($summary['total_income'], 0) }} NOK</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Expenses:</span>
                            <span class="text-sm text-danger-600 font-medium">{{ number_format($summary['total_expenses'], 0) }} NOK</span>
                        </div>

                        <hr class="border-gray-200 dark:border-gray-700">

                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Net Cash Flow:</span>
                            <span class="text-sm font-medium {{ $summary['net_income'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                {{ number_format($summary['net_income'], 0) }} NOK
                            </span>
                        </div>

                        @if($summary['years_span']['duration'] > 0)
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Annual Average Growth:</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ number_format($summary['net_growth'] / $summary['years_span']['duration'], 0) }} NOK/year
                                </span>
                            </div>
                        @endif

                        @if($summary['total_start_value'] > 0)
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Return:</span>
                                <span class="text-sm font-medium {{ $summary['net_growth'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ number_format(($summary['net_growth'] / $summary['total_start_value']) * 100, 1) }}%
                                </span>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            </div>

            {{-- Asset Breakdown --}}
            <x-filament::section>
                <x-slot name="heading">
                    Simulated Assets
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Asset</th>
                                <th class="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Type</th>
                                <th class="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Start Value</th>
                                <th class="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-300">End Value</th>
                                <th class="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Growth</th>
                                <th class="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Years</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($simulationConfiguration->simulationAssets as $asset)
                                @php
                                    $assetYears = $asset->simulationAssetYears->sortBy('year');
                                    $firstYear = $assetYears->first();
                                    $lastYear = $assetYears->last();
                                    $startValue = $firstYear->asset_market_amount ?? 0;
                                    $endValue = $lastYear->asset_market_amount ?? 0;
                                    $growth = $endValue - $startValue;
                                    $yearsCount = $assetYears->count();
                                @endphp
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 px-3">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $asset->name }}</div>
                                        @if($asset->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($asset->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-right">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                            {{ ucfirst(str_replace('_', ' ', $asset->asset_type)) }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($startValue, 0) }} NOK
                                    </td>
                                    <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ number_format($endValue, 0) }} NOK
                                    </td>
                                    <td class="py-2 px-3 text-right">
                                        <span class="{{ $growth >= 0 ? 'text-success-600' : 'text-danger-600' }} font-medium">
                                            {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 0) }} NOK
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-400">
                                        {{ $yearsCount }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
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
