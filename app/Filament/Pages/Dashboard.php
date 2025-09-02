<?php

namespace App\Filament\Pages;

use App\Services\AssetConfigurationSessionService;
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
        $this->assetConfigurationId = AssetConfigurationSessionService::getActiveAssetConfigurationId();

        // Also check for URL parameter (for backwards compatibility)
        if (!$this->assetConfigurationId && $request->get('asset_configuration_id')) {
            $urlAssetConfigurationId = $request->get('asset_configuration_id');
            $assetConfiguration = \App\Models\AssetConfiguration::find($urlAssetConfigurationId);
            if ($assetConfiguration) {
                AssetConfigurationSessionService::setActiveAssetConfiguration($assetConfiguration);
                $this->assetConfigurationId = $urlAssetConfigurationId;
            }
        }
    }

    public function getHeading(): string
    {
        $assetConfiguration = AssetConfigurationSessionService::getActiveAssetConfiguration();
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
}
