<?php

namespace App\Filament\Pages;

use App\Services\CurrentAssetConfiguration;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Http\Request;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public ?int $assetConfigurationId = null;

    public function mount(Request $request): void
    {
        // Use session service for consistency
        $this->assetConfigurationId = app(CurrentAssetConfiguration::class)->id();

        // Also check for URL parameter (for backwards compatibility)
        if (!$this->assetConfigurationId && $request->get('asset_configuration_id')) {
            $urlAssetConfigurationId = $request->get('asset_configuration_id');
            $assetConfiguration = \App\Models\AssetConfiguration::find($urlAssetConfigurationId);
            if ($assetConfiguration) {
                app(CurrentAssetConfiguration::class)->set($assetConfiguration);
                $this->assetConfigurationId = $urlAssetConfigurationId;
            }
        }
    }

    public function getHeading(): string
    {
        $assetConfiguration = app(CurrentAssetConfiguration::class)->get();
        if ($assetConfiguration) {
            return 'Dashboard - '.$assetConfiguration->name;
        }

        return 'Wealthprognosis '.now()->year;
    }

    public function getTitle(): string
    {
        return $this->getHeading();
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard'; // No year in navigation menu
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        return [
            'heading' => $this->getHeading(),
            'asset_configuration_id' => $this->assetConfigurationId,
        ];
    }

    public function getAssetConfigurationId(): ?int
    {
        return $this->assetConfigurationId;
    }

    public function getColumns(): int
    {
        // Use a 12-column grid so allocation charts (columnSpan=4) sit on the same row
        return 12;
    }
}

