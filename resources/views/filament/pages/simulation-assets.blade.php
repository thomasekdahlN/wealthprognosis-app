<x-filament-panels::page>
    @if($simulationConfiguration)
        <div class="space-y-4">
            <!-- Header with Tabs and Organized Info -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <!-- Tab Navigation -->
                <div class="px-6 pt-4 pb-3 border-b border-blue-200 dark:border-blue-700">
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
                            :active="true"
                            icon="heroicon-o-building-office-2"
                        >
                            Assets
                        </x-filament::tabs.item>
                    </x-filament::tabs>
                </div>

                <!-- Organized Info Sections -->
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Simulation Settings -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                <x-filament::icon icon="heroicon-o-cog-6-tooth" class="w-4 h-4 mr-2 text-blue-600" />
                                Settings
                            </h4>
                            <div class="space-y-2">
                                @if($simulationConfiguration->tax_country)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Tax Country:</span>
                                        <x-filament::badge color="primary">
                                            {{ strtoupper($simulationConfiguration->tax_country) }}
                                        </x-filament::badge>
                                    </div>
                                @endif

                                @if($simulationConfiguration->prognosis_type)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Scenario:</span>
                                        <x-filament::badge color="success">
                                            {{ ucfirst($simulationConfiguration->prognosis_type) }}
                                        </x-filament::badge>
                                    </div>
                                @endif

                                @if($simulationConfiguration->risk_tolerance)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Risk Tolerance:</span>
                                        <x-filament::badge color="warning">
                                            {{ $simulationConfiguration->risk_tolerance_label }}
                                        </x-filament::badge>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4 mr-2 text-purple-600" />
                                Timeline
                            </h4>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Created:</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $simulationConfiguration->created_at->format('M j, Y') }}
                                    </span>
                                </div>

                                @if($simulationConfiguration->birth_year)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Birth Year:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $simulationConfiguration->birth_year }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Details -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                <x-filament::icon icon="heroicon-o-information-circle" class="w-4 h-4 mr-2 text-indigo-600" />
                                Details
                            </h4>
                            <div class="space-y-2">
                                @if($simulationConfiguration->description)
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <div class="line-clamp-2">
                                            {!! Str::limit(strip_tags($simulationConfiguration->description), 100) !!}
                                        </div>
                                    </div>
                                @else
                                    <div class="text-sm text-gray-500 dark:text-gray-500 italic">
                                        No description provided
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notice about read-only data -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-filament::icon icon="heroicon-o-information-circle" class="w-5 h-5 text-blue-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Read-Only Simulation Data
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <p>This data is from a simulation and is read-only. All values are calculated based on the simulation parameters and cannot be modified.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assets Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                {{ $this->table }}
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Simulation not found</h3>
            <p class="text-gray-500 dark:text-gray-400">The requested simulation could not be found.</p>
        </div>
    @endif
</x-filament-panels::page>



@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        // Listen for download-file events dispatched from Livewire actions
        window.addEventListener('download-file', (event) => {
            const detail = event?.detail || {};
            const url = detail.url || event.url; // support both shapes
            const filename = detail.filename || event.filename || '';
            if (!url) return;

            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>
@endpush
