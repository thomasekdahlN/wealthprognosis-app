<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 24px;">🏆</span>
                <span>Key Outcome Comparison</span>
            </div>
        </x-slot>

        <x-slot name="description">
            A/B test summary - which scenario wins and by how much?
        </x-slot>

        @if(count($outcomes) > 0)
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 8px 12px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280;">Metric</th>
                            <th style="padding: 8px 12px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; font-size: 9px; font-weight: bold; color: white; background: #2563eb; border-radius: 3px;">A</span>
                                    <span>Simulation A</span>
                                </div>
                            </th>
                            <th style="padding: 8px 12px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; font-size: 9px; font-weight: bold; color: white; background: #16a34a; border-radius: 3px;">B</span>
                                    <span>Simulation B</span>
                                </div>
                            </th>
                            <th style="padding: 8px 12px; text-align: right; font-size: 12px; font-weight: 600; color: #6b7280;">Delta</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Simulation Information Rows --}}
                        @if(isset($simulationInfo) && count($simulationInfo) > 0)
                            @foreach($simulationInfo as $info)
                                <tr style="border-bottom: 1px solid #f3f4f6; background: #fafafa;">
                                    <td style="padding: 10px 12px; font-size: 13px; font-weight: 600; color: #374151;">
                                        {{ $info['label'] }}
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; font-size: 13px; font-weight: 500; color: #1e3a8a;">
                                        {{ $info['valueA'] }}
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; font-size: 13px; font-weight: 500; color: #14532d;">
                                        {{ $info['valueB'] }}
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; font-size: 13px; color: #9ca3af;">
                                        {{-- Empty delta column for info rows --}}
                                    </td>
                                </tr>
                            @endforeach
                            {{-- Separator row --}}
                            <tr style="border-bottom: 2px solid #e5e7eb;">
                                <td colspan="4" style="padding: 0;"></td>
                            </tr>
                        @endif

                        {{-- Outcome Comparison Rows --}}
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

                                // Delta colors
                                $deltaTextColor = match($deltaColor) {
                                    'success' => '#15803d',
                                    'danger' => '#b91c1c',
                                    default => '#6b7280',
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 10px 12px; font-size: 13px; font-weight: 500; color: #111827;">
                                    {{ $outcome['metric'] }}
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-size: 13px; font-weight: 600; color: #1e3a8a;">
                                    @if($winnerA)
                                        <span style="margin-right: 4px;">🏆</span>
                                    @endif
                                    {{ $formattedA }}
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-size: 13px; font-weight: 600; color: #14532d;">
                                    @if($winnerB)
                                        <span style="margin-right: 4px;">🏆</span>
                                    @endif
                                    {{ $formattedB }}
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-size: 13px; font-weight: 600; color: {{ $deltaTextColor }};">
                                    @if($isBetter === true)
                                        <span style="margin-right: 2px; color: #16a34a;">↗</span>
                                    @elseif($isBetter === false && $delta !== 0)
                                        <span style="margin-right: 2px; color: #dc2626;">↘</span>
                                    @endif
                                    {{ $deltaFormatted }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align: center; padding: 48px 20px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📊</div>
                <p style="font-size: 14px; font-weight: 500; color: #111827; margin-bottom: 4px;">No data available</p>
                <p style="font-size: 12px; color: #6b7280;">Select two simulations to compare</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

