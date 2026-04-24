<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-information-circle"
        icon-color="info"
        :heading="__('This dashboard shows a linear projection')"
        :description="__('Charts below extend today\'s numbers forward using fixed growth and inflation rates. They do not account for market volatility, life events, tax changes, or different drawdown strategies.')"
    >
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-3">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ __('Run a simulation to get a richer picture of your financial future:') }}
                </p>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-success-500 shrink-0 mt-0.5" />
                        <span>{{ __('Model life events such as retirement, inheritance, property purchases or career changes year by year.') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-success-500 shrink-0 mt-0.5" />
                        <span>{{ __('Compare multiple scenarios side by side — conservative, realistic and optimistic growth rates.') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-success-500 shrink-0 mt-0.5" />
                        <span>{{ __('See year-by-year cash flow, net worth, tax impact and FIRE progression for every asset.') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-success-500 shrink-0 mt-0.5" />
                        <span>{{ __('Stress-test withdrawal strategies against sequence of returns risk and inflation shocks.') }}</span>
                    </li>
                </ul>
            </div>

            <div class="flex flex-col gap-3 lg:items-end lg:justify-center">
                @if ($simulationsUrl)
                    <x-filament::button
                        :href="$simulationsUrl"
                        tag="a"
                        icon="heroicon-o-calculator"
                        color="primary"
                        size="lg"
                    >
                        {{ __('Create a simulation') }}
                    </x-filament::button>
                @elseif ($createSimulationUrl)
                    <x-filament::button
                        :href="$createSimulationUrl"
                        tag="a"
                        icon="heroicon-o-calculator"
                        color="primary"
                        size="lg"
                    >
                        {{ __('Create a simulation') }}
                    </x-filament::button>
                @else
                    <x-filament::button
                        icon="heroicon-o-calculator"
                        color="primary"
                        size="lg"
                        :disabled="true"
                    >
                        {{ __('Select a configuration first') }}
                    </x-filament::button>
                @endif

                <p class="text-xs text-gray-500 dark:text-gray-400 lg:text-right">
                    {{ __('Simulations keep your actual data untouched — they are separate, editable scenarios you can revisit any time.') }}
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
