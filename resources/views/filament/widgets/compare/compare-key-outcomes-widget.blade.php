<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🏆 Key Outcome Comparison
        </x-slot>

        <x-slot name="description">
            A/B test summary - which scenario wins and by how much?
        </x-slot>

        @if(count($outcomes) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-gray-100">Metric</th>
                            <th class="text-right py-3 px-4 font-semibold text-blue-900 dark:text-blue-100">Simulation A (Baseline)</th>
                            <th class="text-right py-3 px-4 font-semibold text-green-900 dark:text-green-100">Simulation B (Scenario)</th>
                            <th class="text-right py-3 px-4 font-semibold text-gray-900 dark:text-gray-100">Delta (Difference)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outcomes as $outcome)
                            @php
                                $valueA = $outcome['valueA'];
                                $valueB = $outcome['valueB'];
                                $format = $outcome['format'];
                                
                                // Calculate delta
                                $delta = null;
                                $deltaFormatted = 'N/A';
                                $deltaColor = 'text-gray-500';
                                
                                if ($valueA !== null && $valueB !== null) {
                                    $delta = $valueB - $valueA;
                                    
                                    if ($format === 'currency' || $format === 'currency_per_year') {
                                        $deltaFormatted = ($delta >= 0 ? '+' : '') . number_format($delta, 0, ',', ' ') . ' kr';
                                        if ($format === 'currency_per_year') {
                                            $deltaFormatted .= ' / year';
                                        }
                                        // For currency, positive is good (green), negative is bad (red)
                                        $deltaColor = $delta > 0 ? 'text-green-600 dark:text-green-400 font-semibold' : ($delta < 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500');
                                    } elseif ($format === 'year') {
                                        if ($outcome['metric'] === 'Year FIRE Achieved') {
                                            // For FIRE, lower year is better (achieved sooner)
                                            $deltaFormatted = $delta > 0 ? "+{$delta} years (Slower)" : ($delta < 0 ? abs($delta) . ' years (Faster)' : 'Same');
                                            $deltaColor = $delta < 0 ? 'text-green-600 dark:text-green-400 font-semibold' : ($delta > 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500');
                                        } elseif ($outcome['metric'] === 'Year Debt-Free') {
                                            // For debt-free, lower year is better (debt-free sooner)
                                            $deltaFormatted = $delta > 0 ? "+{$delta} years (Slower)" : ($delta < 0 ? abs($delta) . ' years (Faster)' : 'Same');
                                            $deltaColor = $delta < 0 ? 'text-green-600 dark:text-green-400 font-semibold' : ($delta > 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500');
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
                            @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900">
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-gray-100">{{ $outcome['metric'] }}</td>
                                <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300">{{ $formattedA }}</td>
                                <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300">{{ $formattedB }}</td>
                                <td class="py-3 px-4 text-right {{ $deltaColor }}">{{ $deltaFormatted }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                No data available for comparison.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

