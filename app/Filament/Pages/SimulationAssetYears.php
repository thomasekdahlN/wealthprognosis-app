<?php

namespace App\Filament\Pages;

use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Panel;









use Illuminate\Contracts\Support\Htmlable;



class SimulationAssetYears extends Page
{




    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;
    public ?SimulationAsset $simulationAsset = null;

    public function mount(): void
    {
        $simulationConfigurationId = request()->query('simulation_configuration_id')
            ?? request()->query('simulation_configuration')
            ?? request()->route('simulation_configuration')
            ?? request()->route('record');

        $assetId = request()->query('asset') ?? request()->route('asset');

        if ($simulationConfigurationId) {
            $this->simulationConfiguration = SimulationConfiguration::find($simulationConfigurationId);
        }

        if ($assetId) {
            $this->simulationAsset = SimulationAsset::find($assetId);
        }

        // Ensure user has access
        if ($this->simulationConfiguration && $this->simulationConfiguration->user_id !== auth()->id()) {
            abort(403);
        }
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->simulationAsset && $this->simulationConfiguration) {
            return "Simulation Asset Years: {$this->simulationAsset->name}";
        }

        return 'Simulation Asset Years';
    }

    public function getHeading(): string|Htmlable
    {
        if ($this->simulationConfiguration) {
            return "Asset Years for {$this->simulationConfiguration->name}";
        }

        return 'Asset Years';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->simulationConfiguration) {
            return "Simulation: {$this->simulationConfiguration->name}";
        }

        return null;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\SimulationAssetYearsTable::class,
        ];
    }



    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->simulationConfiguration) {
            $actions[] = Action::make('back_to_simulation')
                ->label('Back to Simulation')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(route('filament.admin.resources.simulation-configurations.view', [
                    'record' => $this->simulationConfiguration->id,
                    'activeTab' => 'assets'
                ]));
        }

        return $actions;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        $breadcrumbs[route('filament.admin.resources.simulation-configurations.index')] = 'Simulations';

        if ($this->simulationConfiguration) {
            $breadcrumbs[route('filament.admin.pages.simulation-dashboard', [
                'simulation_configuration_id' => $this->simulationConfiguration->id
            ])] = $this->simulationConfiguration->name;

            $breadcrumbs[route('filament.admin.pages.simulation-assets', [
                'simulation_configuration_id' => $this->simulationConfiguration->id
            ])] = 'Assets';
        }

        if ($this->simulationAsset) {
            $breadcrumbs[] = $this->simulationAsset->name; // Current page (no URL)
        }

        return $breadcrumbs;
    }

    protected function getViewData(): array
    {
        return [
            'simulationConfiguration' => $this->simulationConfiguration,
            'simulationAsset' => $this->simulationAsset,
        ];
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        return 'filament.admin.pages.simulation-asset-years';
    }
}
