<?php

namespace App\Filament\Widgets\Configuration;

use App\Services\CurrentAssetConfiguration;
use Filament\Widgets\Widget;

class ConfigurationProjectionNoticeWidget extends Widget
{
    protected string $view = 'filament.widgets.configuration.configuration-projection-notice-widget';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public ?int $assetConfigurationId = null;

    public function mount(): void
    {
        $this->assetConfigurationId = app(CurrentAssetConfiguration::class)->id();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $configurationId = $this->assetConfigurationId;

        $simulationsUrl = null;
        $createSimulationUrl = null;

        if ($configurationId !== null) {
            try {
                $simulationsUrl = route('filament.admin.pages.config-simulations.pretty', [
                    'configuration' => $configurationId,
                ]);
            } catch (\Throwable $e) {
                $simulationsUrl = null;
            }
        }

        try {
            $createSimulationUrl = route('filament.admin.resources.simulation-configurations.create');
        } catch (\Throwable $e) {
            $createSimulationUrl = null;
        }

        return [
            'simulationsUrl' => $simulationsUrl,
            'createSimulationUrl' => $createSimulationUrl,
        ];
    }
}
