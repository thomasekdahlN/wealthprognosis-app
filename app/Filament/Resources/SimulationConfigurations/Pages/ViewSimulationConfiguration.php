<?php

namespace App\Filament\Resources\SimulationConfigurations\Pages;

use App\Filament\Resources\SimulationConfigurations\SimulationConfigurationResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewSimulationConfiguration extends ViewRecord
{
    protected static string $resource = SimulationConfigurationResource::class;

    protected string $view = 'filament.resources.simulation-configurations.pages.view-simulation-configuration';

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->getRecord()->description;
    }

    public $activeTab = 'dashboard';

    public function mount(string|int $record): void
    {
        parent::mount($record);

        // Redirect to the appropriate tab page based on the activeTab parameter
        $activeTab = request()->query('activeTab', 'dashboard');

        if ($activeTab === 'assets') {
            $this->redirect(route('filament.admin.pages.simulation-assets', [
                'configuration' => $this->getRecord()->asset_configuration_id,
                'simulation' => $this->getRecord()->id,
            ]));

            return;
        }

        // Default to dashboard
        $this->redirect(route('filament.admin.pages.simulation-dashboard', [
            'configuration' => $this->getRecord()->asset_configuration_id,
            'simulation' => $this->getRecord()->id,
        ]));
    }

    protected function getSimulationAssets()
    {
        return $this->getRecord()->simulationAssets()
            ->with(['simulationAssetYears' => function ($query) {
                $query->orderBy('year');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function getSimulationMetrics(): array
    {
        $simulationAssets = $this->getSimulationAssets();
        $currentYear = (int) date('Y');

        // Calculate total assets and current year values
        $totalAssets = $simulationAssets->count();
        $totalCurrentValue = 0;
        $totalYearEntries = 0;

        foreach ($simulationAssets as $asset) {
            $totalYearEntries += $asset->simulationAssetYears->count();

            // Get current year value
            $currentYearData = $asset->simulationAssetYears
                ->where('year', $currentYear)
                ->first();

            if ($currentYearData) {
                $totalCurrentValue += $currentYearData->asset_market_amount ?? 0;
            }
        }

        return [
            'totalAssets' => $totalAssets,
            'totalCurrentValue' => $totalCurrentValue,
            'totalYearEntries' => $totalYearEntries,
            'simulationAssets' => $simulationAssets,
        ];
    }

    protected function getViewData(): array
    {
        $metrics = $this->getSimulationMetrics();

        return [
            'simulationConfiguration' => $this->getRecord(),
            'simulationAssets' => $metrics['simulationAssets'],
            'totalAssets' => $metrics['totalAssets'],
            'totalCurrentValue' => $metrics['totalCurrentValue'],
            'totalYearEntries' => $metrics['totalYearEntries'],
            'assetsByType' => $metrics['simulationAssets']->groupBy('asset_type'),
            'currentYear' => (int) date('Y'),
        ];
    }
}
