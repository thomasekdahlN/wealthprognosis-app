<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📋 Scenario Assumptions & Summary
        </x-slot>

        <x-slot name="description">
            Context and key differences between the two simulations
        </x-slot>

        @if($simulationA && $simulationB)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Simulation A -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-blue-50 dark:bg-blue-950">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        Simulation A: "{{ $simulationA->name }}"
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>Summary:</strong> {{ $summaryA }}
                    </p>
                </div>

                <!-- Simulation B -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-green-50 dark:bg-green-950">
                    <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">
                        Simulation B: "{{ $simulationB->name }}"
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        <strong>Summary:</strong> {{ $summaryB }}
                    </p>
                    <div class="mt-3 pt-3 border-t border-green-200 dark:border-green-800">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            <strong>Changes:</strong> {{ $changesB }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                Please select two simulations to compare.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

