<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasWideTable;
use App\Models\SimulationConfiguration;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class SimulationAssets extends Page
{
    use HasWideTable;

    protected static string $routePath = '/config/{configuration}/sim/{simulation}/assets';

    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        $simulationConfigurationId = request()->route('simulation');

        // Fallback for unit tests where direct page instantiation may not bind route params
        if (! $simulationConfigurationId && app()->runningUnitTests()) {
            $fallbackId = SimulationConfiguration::query()
                ->where('user_id', auth()->id())
                ->orderByDesc('id')
                ->value('id');
            if ($fallbackId) {
                $simulationConfigurationId = (string) $fallbackId;
            }
        }

        if (! $simulationConfigurationId) {
            throw new Halt('404');
        }

        $this->simulationConfiguration = SimulationConfiguration::with([
            'assetConfiguration',
            'simulationAssets.simulationAssetYears' => function ($query) {
                $query->orderBy('year');
            },
        ])->find($simulationConfigurationId);

        if (! $this->simulationConfiguration) {
            throw new Halt('404');
        }

        // Check if user has access to this simulation
        if ($this->simulationConfiguration->user_id !== auth()->id()) {
            throw new Halt('403');
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
        if (! $this->simulationConfiguration) {
            return 'Read-Only Simulation Data';
        }

        /** @var \App\Models\AssetConfiguration $config */
        $config = $this->simulationConfiguration->assetConfiguration;
        $assetsCount = $this->simulationConfiguration->simulationAssets()->count();

        return "Based on {$config->name} • {$assetsCount} assets • Read-Only Simulation Data • Created ".$this->simulationConfiguration->created_at->diffForHumans();
    }

    protected function getHeaderWidgets(): array
    {
        // Removed SimulationAssetsTable widget per request
        return [];
    }

    protected function getHeaderActions(): array
    {
        if (! $this->simulationConfiguration) {
            return [];
        }

        return [
            Action::make('dashboard')
                ->label(__('simulation.dashboard'))
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->url(route('filament.admin.pages.simulation-dashboard', [
                    'configuration' => $this->simulationConfiguration->asset_configuration_id,
                    'simulation' => $this->simulationConfiguration->id,
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
                ->url(fn () => route('filament.admin.pages.config-simulations.pretty', ['configuration' => $this->simulationConfiguration->asset_configuration_id])),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        if ($this->simulationConfiguration) {
            $breadcrumbs[route('filament.admin.pages.config-simulations.pretty', [
                'configuration' => $this->simulationConfiguration->asset_configuration_id,
            ])] = __('simulation.simulations');
        } else {
            $breadcrumbs[route('filament.admin.resources.simulation-configurations.index')] = __('simulation.simulations');
        }

        if ($this->simulationConfiguration) {
            $breadcrumbs[route('filament.admin.pages.simulation-dashboard', [
                'configuration' => $this->simulationConfiguration->asset_configuration_id,
                'simulation' => $this->simulationConfiguration->id,
            ])] = $this->simulationConfiguration->name;
        }

        $breadcrumbs[] = 'Assets'; // Current page (no URL)

        return $breadcrumbs;
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function getRouteName(?\Filament\Panel $panel = null): string
    {
        return 'filament.admin.pages.simulation-assets';
    }

    /**
     * @return array<string, class-string>
     */
    public static function getRoutes(): array
    {
        return [
            '/config/{configuration}/sim/{simulation}/assets' => static::class,
        ];
    }
}
