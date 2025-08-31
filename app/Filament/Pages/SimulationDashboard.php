<?php

namespace App\Filament\Pages;

use App\Models\SimulationConfiguration;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class SimulationDashboard extends Page
{
    protected string $view = 'filament.pages.simulation-dashboard';

    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        $simulationConfigurationId = request()->query('simulation_configuration_id');

        if (!$simulationConfigurationId) {
            throw new Halt(404);
        }

        $this->simulationConfiguration = SimulationConfiguration::with([
            'assetConfiguration',
            'simulationAssets.simulationAssetYears'
        ])->find($simulationConfigurationId);

        if (!$this->simulationConfiguration) {
            throw new Halt(404);
        }

        // Check if user has access to this simulation
        if ($this->simulationConfiguration->user_id !== auth()->id()) {
            throw new Halt(403);
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

    protected function getViewData(): array
    {
        if (!$this->simulationConfiguration) {
            return [];
        }

        // Calculate summary statistics
        $simulationAssets = $this->simulationConfiguration->simulationAssets;
        $totalStartValue = 0;
        $totalEndValue = 0;
        $totalIncome = 0;
        $totalExpenses = 0;

        foreach ($simulationAssets as $asset) {
            $assetYears = $asset->simulationAssetYears;
            if ($assetYears->isNotEmpty()) {
                $firstYear = $assetYears->sortBy('year')->first();
                $lastYear = $assetYears->sortBy('year')->last();

                $totalStartValue += $firstYear->asset_market_amount ?? 0;
                $totalEndValue += $lastYear->asset_market_amount ?? 0;

                foreach ($assetYears as $year) {
                    $totalIncome += $year->income_amount ?? 0;
                    $totalExpenses += $year->expence_amount ?? 0;
                }
            }
        }

        $netGrowth = $totalEndValue - $totalStartValue;
        $netIncome = $totalIncome - $totalExpenses;

        return [
            'simulationConfiguration' => $this->simulationConfiguration,
            'summary' => [
                'total_start_value' => $totalStartValue,
                'total_end_value' => $totalEndValue,
                'net_growth' => $netGrowth,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'assets_count' => $simulationAssets->count(),
                'years_span' => $this->getYearsSpan(),
            ]
        ];
    }

    protected function getYearsSpan(): array
    {
        if (!$this->simulationConfiguration) {
            return ['start' => null, 'end' => null, 'duration' => 0];
        }

        $allYears = $this->simulationConfiguration->simulationAssets
            ->flatMap(fn($asset) => $asset->simulationAssetYears->pluck('year'))
            ->unique()
            ->sort();

        if ($allYears->isEmpty()) {
            return ['start' => null, 'end' => null, 'duration' => 0];
        }

        $startYear = $allYears->first();
        $endYear = $allYears->last();

        return [
            'start' => $startYear,
            'end' => $endYear,
            'duration' => $endYear - $startYear + 1,
        ];
    }

    public static function getRoutes(): array
    {
        return [
            '/simulation-dashboard' => static::class,
        ];
    }
}
