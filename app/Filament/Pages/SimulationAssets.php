<?php

namespace App\Filament\Pages;

use App\Models\SimulationAsset;
use App\Models\SimulationConfiguration;
use Filament\Pages\Page;
use App\Filament\Concerns\HasWideTable;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class SimulationAssets extends Page
{
    use HasWideTable;



    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        $simulationConfigurationId = request()->query('simulation_configuration_id') ?? request()->route('record');

        if (!$simulationConfigurationId) {
            throw new Halt(404);
        }

        $this->simulationConfiguration = SimulationConfiguration::with([
            'assetConfiguration',
            'simulationAssets.simulationAssetYears' => function ($query) {
                $query->orderBy('year');
            }
        ])->find($simulationConfigurationId);

        if (!$this->simulationConfiguration) {
            throw new Halt(404);
        }

        // Check if user has access to this simulation
        if ($this->simulationConfiguration->user_id !== auth()->id()) {
            throw new Halt(403);
        }

        // Enable page-wide horizontal scrolling for wide tables
        $this->enableWideTable();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->simulationConfiguration
            ? "Simulation Assets - {$this->simulationConfiguration->name}"
            : 'Simulation Assets';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->simulationConfiguration
            ? "Assets in {$this->simulationConfiguration->name}"
            : 'Simulation Assets';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (!$this->simulationConfiguration) {
            return 'Read-Only Simulation Data';
        }

        $config = $this->simulationConfiguration->assetConfiguration;
        $assetsCount = $this->simulationConfiguration->simulationAssets()->count();

        return "Based on {$config->name} • {$assetsCount} assets • Read-Only Simulation Data • Created " . $this->simulationConfiguration->created_at->diffForHumans();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\SimulationAssetsTable::class,
        ];
    }



    protected function getHeaderActions(): array
    {
        if (!$this->simulationConfiguration) {
            return [];
        }

        return [
            Action::make('dashboard')
                ->label(__('simulation.dashboard'))
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->url(route('filament.admin.pages.simulation-dashboard', [
                    'simulation_configuration_id' => $this->simulationConfiguration->id
                ])),

            Action::make('export_excel')
                ->label(__('export.export_excel'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $path = \App\Services\SimulationExportService::export($this->simulationConfiguration);
                    $url = \URL::signedRoute('download.analysis', ['file' => basename($path)]);
$this->dispatch('download-file', url: $url, filename: basename($path));
                }),

            Action::make('back_to_simulations')
                ->label(__('simulation.back_to_simulations'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(route('filament.admin.resources.simulation-configurations.index')),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        $breadcrumbs[route('filament.admin.resources.simulation-configurations.index')] = __('simulation.simulations');

        if ($this->simulationConfiguration) {
            $breadcrumbs[route('filament.admin.pages.simulation-dashboard', [
                'simulation_configuration_id' => $this->simulationConfiguration->id
            ])] = $this->simulationConfiguration->name;
        }

        $breadcrumbs[] = 'Assets'; // Current page (no URL)

        return $breadcrumbs;
    }



    public static function getRoutes(): array
    {
        return [
            '/simulation-assets' => static::class,
        ];
    }
}
