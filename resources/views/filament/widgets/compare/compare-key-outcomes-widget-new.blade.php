<x-filament-widgets::widget>
    <x-filament::section
        heading="Key Outcome Comparison"
        description="A/B test summary - which scenario wins and by how much?"
        icon="heroicon-o-trophy"
        icon-color="warning"
    >
        @if(count($outcomes) > 0)
            <div class="grid gap-4">
                @foreach($outcomes as $outcome)
                    @php
                        $valueA = $outcome['valueA'];
                        $valueB = $outcome['valueB'];
                        $format = $outcome['format'];

                        // Calculate delta
                        $delta = null;
                        $deltaFormatted = 'N/A';
                        $deltaColor = 'gray';
                        $isBetter = null;

                        if ($valueA !== null && $valueB !== null) {
                            $delta = $valueB - $valueA;

                            if ($format === 'currency' || $format === 'currency_per_year') {
                                $deltaFormatted = ($delta >= 0 ? '+' : '') . number_format($delta, 0, ',', ' ') . ' kr';
                                if ($format === 'currency_per_year') {
                                    $deltaFormatted .= ' / year';
                                }
                                $isBetter = $delta > 0;
                                $deltaColor = $delta > 0 ? 'success' : ($delta < 0 ? 'danger' : 'gray');
                            } elseif ($format === 'year') {
                                if ($outcome['metric'] === 'Year FIRE Achieved') {
                                    $deltaFormatted = $delta > 0 ? "+{$delta} years" : ($delta < 0 ? abs($delta) . ' years earlier' : 'Same');
                                    $isBetter = $delta < 0;
                                    $deltaColor = $delta < 0 ? 'success' : ($delta > 0 ? 'danger' : 'gray');
                                } elseif ($outcome['metric'] === 'Year Debt-Free') {
                                    $deltaFormatted = $delta > 0 ? "+{$delta} years" : ($delta < 0 ? abs($delta) . ' years earlier' : 'Same');
                                    $isBetter = $delta < 0;
                                    $deltaColor = $delta < 0 ? 'success' : ($delta > 0 ? 'danger' : 'gray');
                                }
                            }
                        }

                        // Format values
                        $formattedA = 'N/A';
                        $formattedB = 'N/A';

                        if ($format === 'currency' || $format === 'currency_per_year') {
                            if ($valueA !== null) {
                                $formattedA = number_format($valueA, 0, ',', ' ') . ' kr';
                                if ($format === 'currency_per_year') {
                                    $formattedA .= ' / year';
                                }
                            }
                            if ($valueB !== null) {
                                $formattedB = number_format($valueB, 0, ',', ' ') . ' kr';
                                if ($format === 'currency_per_year') {
                                    $formattedB .= ' / year';
                                }
                            }
                        } elseif ($format === 'year') {
                            $formattedA = $valueA ?? 'Never';
                            $formattedB = $valueB ?? 'Never';
                        }

                        // Determine winner
                        $winnerA = false;
                        $winnerB = false;
                        if ($isBetter === true) {
                            $winnerB = true;
                        } elseif ($isBetter === false && $delta !== 0) {
                            $winnerA = true;
                        }

                        // Build CSS classes
                        $deltaBgClass = match($deltaColor) {
                            'success' => 'bg-success-50 dark:bg-success-900/20',
                            'danger' => 'bg-danger-50 dark:bg-danger-900/20',
                            default => 'bg-gray-50 dark:bg-gray-900/20',
                        };
                        $deltaTextClass = match($deltaColor) {
                            'success' => 'text-success-700 dark:text-success-400',
                            'danger' => 'text-danger-700 dark:text-danger-400',
                            default => 'text-gray-700 dark:text-gray-400',
                        };
                    @endphp

                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Metric Header -->
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $outcome['metric'] }}</h4>
                        </div>

                        <!-- Values Grid -->
                        <div class="grid grid-cols-3 gap-4 p-4 bg-white dark:bg-gray-900">
                            <!-- Simulation A -->
                            <div class="relative">
                                @if($winnerA)
                                    <div class="absolute -top-2 -right-2 z-10">
                                        <x-filament::icon icon="heroicon-m-trophy" class="w-5 h-5 text-warning-500" />
                                    </div>
                                @endif
                                <div class="rounded-lg bg-primary-50 dark:bg-primary-900/20 p-3 border border-primary-200 dark:border-primary-700">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-primary-600 rounded">A</span>
                                        <span class="text-xs font-medium text-primary-700 dark:text-primary-300">Simulation A</span>
                                    </div>
                                    <div class="text-sm font-bold text-primary-900 dark:text-primary-100">{{ $formattedA }}</div>
                                </div>
                            </div>

                            <!-- Simulation B -->
                            <div class="relative">
                                @if($winnerB)
                                    <div class="absolute -top-2 -right-2 z-10">
                                        <x-filament::icon icon="heroicon-m-trophy" class="w-5 h-5 text-warning-500" />
                                    </div>
                                @endif
                                <div class="rounded-lg bg-success-50 dark:bg-success-900/20 p-3 border border-success-200 dark:border-success-700">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-success-600 rounded">B</span>
                                        <span class="text-xs font-medium text-success-700 dark:text-success-300">Simulation B</span>
                                    </div>
                                    <div class="text-sm font-bold text-success-900 dark:text-success-100">{{ $formattedB }}</div>
                                </div>
                            </div>

                            <!-- Delta -->
                            <div class="rounded-lg {{ $deltaBgClass }} p-3 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-4 h-4 {{ $deltaTextClass }}" />
                                    <span class="text-xs font-medium {{ $deltaTextClass }}">Delta</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    @if($isBetter === true)
                                        <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-4 h-4 text-success-600 dark:text-success-400" />
                                    @elseif($isBetter === false && $delta !== 0)
                                        <x-filament::icon icon="heroicon-m-arrow-trending-down" class="w-4 h-4 text-danger-600 dark:text-danger-400" />
                                    @endif
                                    <div class="text-sm font-bold {{ $deltaTextClass }}">{{ $deltaFormatted }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <x-filament::icon icon="heroicon-o-chart-bar" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">No data available</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Select two simulations to compare</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

