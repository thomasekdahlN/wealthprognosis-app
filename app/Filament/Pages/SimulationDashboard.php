<?php

namespace App\Filament\Pages;

use App\Models\SimulationConfiguration;
use App\Filament\Widgets\SimulationStatsOverviewWidget;
use App\Filament\Widgets\SimulationFireAnalysisWidget;
use App\Filament\Widgets\SimulationNetWorthChartWidget;
use App\Filament\Widgets\SimulationCashFlowChartWidget;
use App\Filament\Widgets\SimulationAssetAllocationChartWidget;
use App\Filament\Widgets\SimulationTaxAnalysisWidget;
use Filament\Pages\Dashboard;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class SimulationDashboard extends Dashboard
{
    protected static string $routePath = '/simulation-dashboard';

    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        $simulationConfigurationId = request()->query('simulation_configuration_id');

        // Validate that simulation_configuration_id parameter is provided
        if (!$simulationConfigurationId) {
            session()->flash('error', 'No simulation configuration ID provided. Please access this dashboard through the "Dashboard" button in the Simulations list.');

            // Redirect to simulations list instead of showing 404
            redirect()->route('filament.admin.resources.simulation-configurations.index');
            return;
        }

        // Validate that the ID is numeric
        if (!is_numeric($simulationConfigurationId)) {
            session()->flash('error', 'Invalid simulation configuration ID format. Please check the URL and try again.');
            throw new Halt(400);
        }

        // Try to find the simulation configuration
        $this->simulationConfiguration = SimulationConfiguration::with([
            'assetConfiguration',
            'simulationAssets.simulationAssetYears'
        ])->find($simulationConfigurationId);

        // Check if simulation configuration exists
        if (!$this->simulationConfiguration) {
            session()->flash('error', "Simulation configuration with ID {$simulationConfigurationId} not found. It may have been deleted or you may not have access to it.");
            throw new Halt(404);
        }

        // Check if user has access to this simulation
        if ($this->simulationConfiguration->user_id !== auth()->id()) {
            session()->flash('error', 'You do not have permission to view this simulation. Please check that you are logged in with the correct account.');
            throw new Halt(403);
        }

        // Validate that the simulation has data
        if ($this->simulationConfiguration->simulationAssets->isEmpty()) {
            session()->flash('warning', 'This simulation configuration has no assets configured. Please add assets to see meaningful data.');
        }
    }

    public function getTitle(): string|Htmlable
    {
        return $this->simulationConfiguration
            ? "Simulation Dashboard - {$this->simulationConfiguration->name}"
            : 'Simulation Dashboard';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->simulationConfiguration
            ? $this->simulationConfiguration->name
            : 'Simulation Dashboard';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (!$this->simulationConfiguration) {
            return null;
        }

        $config = $this->simulationConfiguration->assetConfiguration;
        $assetsCount = $this->simulationConfiguration->simulationAssets()->count();
        $yearsCount = $this->simulationConfiguration->simulationAssets()
            ->withCount('simulationAssetYears')
            ->get()
            ->sum('simulation_asset_years_count');

        return "Based on {$config->name} • {$assetsCount} assets • {$yearsCount} projections • Created " . $this->simulationConfiguration->created_at->diffForHumans();
    }

    public function getWidgets(): array
    {
        if (!$this->simulationConfiguration) {
            return [];
        }

        return [
            SimulationStatsOverviewWidget::class,
            SimulationFireAnalysisWidget::class,
            SimulationTaxAnalysisWidget::class,
            SimulationNetWorthChartWidget::class,
            SimulationCashFlowChartWidget::class,
            SimulationAssetAllocationChartWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getWidgetData(): array
    {
        return [
            'simulationConfiguration' => $this->simulationConfiguration,
        ];
    }



    public static function getRoutes(): array
    {
        return [
            '/simulation-dashboard' => static::class,
        ];
    }
}
