<x-filament-panels::page>
    @if($this->simulationConfiguration)
        <div class="space-y-4">
            <!-- Header with Tabs and Organized Info -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <!-- Tab Navigation -->
                <div class="px-6 pt-4 pb-3 border-b border-blue-200 dark:border-blue-700">
                    <x-filament::tabs>
                        <x-filament::tabs.item
                            :active="true"
                            icon="heroicon-o-chart-bar"
                        >
                            Dashboard
                        </x-filament::tabs.item>

                        <x-filament::tabs.item
                            :active="false"
                            tag="a"
                            :href="route('filament.admin.pages.simulation-assets', ['simulation_configuration_id' => $this->simulationConfiguration->id])"
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
                                @if($this->simulationConfiguration->tax_country)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Tax Country:</span>
                                        <x-filament::badge color="primary">
                                            {{ strtoupper($this->simulationConfiguration->tax_country) }}
                                        </x-filament::badge>
                                    </div>
                                @endif

                                @if($this->simulationConfiguration->prognosis_type)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Scenario:</span>
                                        <x-filament::badge color="success">
                                            {{ ucfirst($this->simulationConfiguration->prognosis_type) }}
                                        </x-filament::badge>
                                    </div>
                                @endif

                                @if($this->simulationConfiguration->risk_tolerance)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Risk Tolerance:</span>
                                        <x-filament::badge color="warning">
                                            {{ $this->simulationConfiguration->risk_tolerance_label }}
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
                                        {{ $this->simulationConfiguration->created_at->format('M j, Y') }}
                                    </span>
                                </div>

                                @if($this->simulationConfiguration->birth_year)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Birth Year:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $this->simulationConfiguration->birth_year }}
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
                                @if($this->simulationConfiguration->description)
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <div class="line-clamp-2">
                                            {!! Str::limit(strip_tags($this->simulationConfiguration->description), 100) !!}
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

            <!-- Dashboard Content -->
            <div class="space-y-6">
                @php
                    $headerWidgets = $this->getHeaderWidgets();
                    $widgets = $this->getWidgets();
                    $footerWidgets = $this->getFooterWidgets();
                @endphp

                @if (count($headerWidgets) > 0)
                    <x-filament-widgets::widgets
                        :columns="$this->getColumns()"
                        :data="
                            [
                                ...$this->getWidgetData(),
                                ...$this->getViewData(),
                            ]
                        "
                        :widgets="$headerWidgets"
                    />
                @endif

                @if (count($widgets) > 0)
                    <x-filament-widgets::widgets
                        :columns="$this->getColumns()"
                        :data="
                            [
                                ...$this->getWidgetData(),
                                ...$this->getViewData(),
                            ]
                        "
                        :widgets="$widgets"
                    />
                @endif

                @if (count($footerWidgets) > 0)
                    <x-filament-widgets::widgets
                        :columns="$this->getColumns()"
                        :data="
                            [
                                ...$this->getWidgetData(),
                                ...$this->getViewData(),
                            ]
                        "
                        :widgets="$footerWidgets"
                    />
                @endif
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
