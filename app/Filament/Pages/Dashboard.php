<?php

namespace App\Filament\Pages;

use App\Services\AssetConfigurationSessionService;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Http\Request;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public ?int $assetOwnerId = null;

    public function mount(Request $request): void
    {
        // Use session service for consistency
        $this->assetOwnerId = AssetConfigurationSessionService::getActiveAssetOwnerId();

        // Also check for URL parameter (for backwards compatibility)
        if (!$this->assetOwnerId && $request->get('asset_owner_id')) {
            $urlAssetOwnerId = $request->get('asset_owner_id');
            $assetOwner = \App\Models\AssetConfiguration::find($urlAssetOwnerId);
            if ($assetOwner) {
                AssetConfigurationSessionService::setActiveAssetOwner($assetOwner);
                $this->assetOwnerId = $urlAssetOwnerId;
            }
        }
    }

    public function getHeading(): string
    {
        $assetOwner = AssetConfigurationSessionService::getActiveAssetOwner();
        if ($assetOwner) {
            return 'Dashboard - '.$assetOwner->name;
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
            'asset_owner_id' => $this->assetOwnerId,
        ];
    }

    public function getAssetOwnerId(): ?int
    {
        return $this->assetOwnerId;
    }
}
