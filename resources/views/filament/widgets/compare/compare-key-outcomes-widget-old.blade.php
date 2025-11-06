<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-warning-400 to-warning-600 rounded-xl shadow-lg">
                    <x-filament::icon icon="heroicon-o-trophy" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Key outcome comparison</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">A/B test summary - which scenario wins and by how much?</p>
                </div>
            </div>
        </x-slot>

        @if(count($outcomes) > 0)
            <div class="space-y-4">
                @foreach($outcomes as $outcome)
                    @php
                        $valueA = $outcome['valueA'];
                        $valueB = $outcome['valueB'];
                        $format = $outcome['format'];

                        // Calculate delta
                        $delta = null;
                        $deltaFormatted = 'N/A';
                        $deltaColor = 'text-gray-500';
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
                    @endphp

                    <div class="relative group">
                        <!-- Gradient border effect -->
                        <div class="absolute inset-0 bg-gradient-to-r from-primary-500/20 via-success-500/20 to-primary-500/20 rounded-2xl blur-sm group-hover:blur-md transition-all"></div>

                        <div class="relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-lg transition-all">
                            <!-- Metric Header -->
                            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-1.5 h-8 bg-gradient-to-b from-primary-500 via-warning-500 to-success-500 rounded-full shadow-sm"></div>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $outcome['metric'] }}</h4>
                                </div>
                            </div>

                            <!-- Values Grid -->
                            <div class="grid grid-cols-3 gap-4 p-6">
                                <!-- Simulation A -->
                                <div class="relative">
                                    @if($winnerA)
                                        <div class="absolute -top-2 -right-2 z-10">
                                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-warning-400 to-warning-600 rounded-full shadow-lg animate-pulse">
                                                <x-filament::icon icon="heroicon-m-trophy" class="w-4 h-4 text-white" />
                                            </div>
                                        </div>
                                    @endif
                                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-800/20 p-4 border-2 {{ $winnerA ? 'border-warning-400 shadow-lg shadow-warning-500/20' : 'border-primary-200 dark:border-primary-700' }} transition-all">
                                        <div class="absolute top-0 right-0 w-20 h-20 bg-primary-500/10 rounded-full -mr-10 -mt-10"></div>
                                        <div class="relative">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="inline-flex items-center justify-center w-7 h-7 text-xs font-bold text-white bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg shadow-md">A</span>
                                                <span class="text-xs font-semibold text-primary-700 dark:text-primary-300 uppercase tracking-wide">Simulation A</span>
                                            </div>
                                            <div class="font-mono text-lg font-bold text-primary-900 dark:text-primary-100 break-words">{{ $formattedA }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Simulation B -->
                                <div class="relative">
                                    @if($winnerB)
                                        <div class="absolute -top-2 -right-2 z-10">
                                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-warning-400 to-warning-600 rounded-full shadow-lg animate-pulse">
                                                <x-filament::icon icon="heroicon-m-trophy" class="w-4 h-4 text-white" />
                                            </div>
                                        </div>
                                    @endif
                                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-success-50 to-success-100 dark:from-success-900/30 dark:to-success-800/20 p-4 border-2 {{ $winnerB ? 'border-warning-400 shadow-lg shadow-warning-500/20' : 'border-success-200 dark:border-success-700' }} transition-all">
                                        <div class="absolute top-0 right-0 w-20 h-20 bg-success-500/10 rounded-full -mr-10 -mt-10"></div>
                                        <div class="relative">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="inline-flex items-center justify-center w-7 h-7 text-xs font-bold text-white bg-gradient-to-br from-success-500 to-success-700 rounded-lg shadow-md">B</span>
                                                <span class="text-xs font-semibold text-success-700 dark:text-success-300 uppercase tracking-wide">Simulation B</span>
                                            </div>
                                            <div class="font-mono text-lg font-bold text-success-900 dark:text-success-100 break-words">{{ $formattedB }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delta -->
                                @php
                                    $deltaBgClasses = match($deltaColor) {
                                        'success' => 'bg-gradient-to-br from-success-50 to-success-100 dark:from-success-900/30 dark:to-success-800/20 border-success-200 dark:border-success-700',
                                        'danger' => 'bg-gradient-to-br from-danger-50 to-danger-100 dark:from-danger-900/30 dark:to-danger-800/20 border-danger-200 dark:border-danger-700',
                                        default => 'bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900/30 dark:to-gray-800/20 border-gray-200 dark:border-gray-700',
                                    };
                                    $deltaCircleClasses = match($deltaColor) {
                                        'success' => 'bg-success-500/10',
                                        'danger' => 'bg-danger-500/10',
                                        default => 'bg-gray-500/10',
                                    };
                                    $deltaIconClasses = match($deltaColor) {
                                        'success' => 'text-success-600 dark:text-success-400',
                                        'danger' => 'text-danger-600 dark:text-danger-400',
                                        default => 'text-gray-600 dark:text-gray-400',
                                    };
                                    $deltaLabelClasses = match($deltaColor) {
                                        'success' => 'text-success-700 dark:text-success-300',
                                        'danger' => 'text-danger-700 dark:text-danger-300',
                                        default => 'text-gray-700 dark:text-gray-300',
                                    };
                                    $deltaValueClasses = match($deltaColor) {
                                        'success' => 'text-success-900 dark:text-success-100',
                                        'danger' => 'text-danger-900 dark:text-danger-100',
                                        default => 'text-gray-900 dark:text-gray-100',
                                    };
                                @endphp
                                <div class="relative overflow-hidden rounded-xl {{ $deltaBgClasses }} p-4 border-2 transition-all">
                                    <div class="absolute top-0 right-0 w-20 h-20 {{ $deltaCircleClasses }} rounded-full -mr-10 -mt-10"></div>
                                    <div class="relative">
                                        <div class="flex items-center gap-2 mb-2">
                                            <x-filament::icon icon="heroicon-o-arrows-right-left" class="w-5 h-5 {{ $deltaIconClasses }}" />
                                            <span class="text-xs font-semibold {{ $deltaLabelClasses }} uppercase tracking-wide">Delta</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if($isBetter === true)
                                                <x-filament::icon icon="heroicon-m-arrow-trending-up" class="w-5 h-5 text-success-600 dark:text-success-400 flex-shrink-0" />
                                            @elseif($isBetter === false && $delta !== 0)
                                                <x-filament::icon icon="heroicon-m-arrow-trending-down" class="w-5 h-5 text-danger-600 dark:text-danger-400 flex-shrink-0" />
                                            @endif
                                            <div class="font-mono text-lg font-bold {{ $deltaValueClasses }} break-words">{{ $deltaFormatted }}</div>
                                        </div>
                                    </div>
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

