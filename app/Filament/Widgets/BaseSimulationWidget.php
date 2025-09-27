<?php

namespace App\Filament\Widgets;

use App\Models\SimulationConfiguration;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

abstract class BaseSimulationWidget extends Widget
{
    protected ?int $simulationConfigurationId = null;

    protected ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        // Prefer route param, fallback to legacy query param for compatibility
        $this->simulationConfigurationId = request()->route('simulation');

        if ($this->simulationConfigurationId) {
            $this->simulationConfiguration = SimulationConfiguration::with([
                'assetConfiguration',
                'simulationAssets.simulationAssetYears',
            ])
                ->where('user_id', Auth::id())
                ->find($this->simulationConfigurationId);
        }
    }

    protected function getSimulationConfiguration(): ?SimulationConfiguration
    {
        return $this->simulationConfiguration;
    }

    protected function getSimulationConfigurationId(): ?int
    {
        return $this->simulationConfigurationId;
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
        $this->simulationConfigurationId = $simulationConfiguration->id;
    }
}
