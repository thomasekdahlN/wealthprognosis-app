<?php

namespace App\Filament\Pages;

use App\Models\SimulationConfiguration;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class SimulationAssets extends Page
{
    protected string $view = 'filament.pages.simulation-assets';

    protected static bool $shouldRegisterNavigation = false;

    public ?SimulationConfiguration $simulationConfiguration = null;

    public function mount(): void
    {
        $recordId = request()->query('record') ?? request()->route('record');

        if (!$recordId) {
            throw new Halt(404);
        }

        $this->simulationConfiguration = SimulationConfiguration::with([
            'assetConfiguration',
            'simulationAssets.simulationAssetYears' => function ($query) {
                $query->orderBy('year');
            }
        ])->find($recordId);

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
            return null;
        }

        $config = $this->simulationConfiguration->assetConfiguration;
        $assetsCount = $this->simulationConfiguration->simulationAssets()->count();

        return "Based on {$config->name} • {$assetsCount} assets • Created " . $this->simulationConfiguration->created_at->diffForHumans();
    }

    protected function getViewData(): array
    {
        if (!$this->simulationConfiguration) {
            return [];
        }

        $simulationAssets = $this->simulationConfiguration->simulationAssets()
            ->with(['simulationAssetYears' => function ($query) {
                $query->orderBy('year');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Group assets by type for better organization
        $assetsByType = $simulationAssets->groupBy('asset_type');

        return [
            'simulationConfiguration' => $this->simulationConfiguration,
            'simulationAssets' => $simulationAssets,
            'assetsByType' => $assetsByType,
            'totalAssets' => $simulationAssets->count(),
            'totalYearEntries' => $simulationAssets->sum(fn($asset) => $asset->simulationAssetYears->count()),
        ];
    }

    public static function getRoutes(): array
    {
        return [
            '/simulation-assets' => static::class,
        ];
    }
}
