<?php

namespace App\Filament\Widgets;

use App\Services\AssetConfigurationSessionService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class BaseAssetOwnerWidget extends Widget
{
    protected ?int $assetOwnerId = null;

    public function mount(): void
    {
        // Get asset_owner_id from the session service for consistency
        $this->assetOwnerId = AssetConfigurationSessionService::getActiveAssetOwnerId();

        // Also check for request parameter (for backwards compatibility)
        if (!$this->assetOwnerId) {
            $this->assetOwnerId = request()->get('asset_owner_id');
        }
    }

    protected function getAssetOwnerId(): ?int
    {
        // Always get the most current value from session
        return AssetConfigurationSessionService::getActiveAssetOwnerId() ?? $this->assetOwnerId;
    }

    protected function getFilteredAssetQuery(): Builder
    {
        $query = \App\Models\Asset::where('user_id', Auth::id());

        // Apply team filtering (handled by global scope)
        // Apply asset owner filtering if specified
        if ($this->assetOwnerId) {
            $query->where('asset_configuration_id', $this->assetOwnerId);
        }

        return $query;
    }

    protected function getFilteredAssetOwnerQuery(): Builder
    {
        $query = \App\Models\AssetConfiguration::where('user_id', Auth::id());

        // Apply team filtering (handled by global scope)
        // Apply asset owner filtering if specified
        if ($this->assetOwnerId) {
            $query->where('id', $this->assetOwnerId);
        }

        return $query;
    }

    protected function getFilteredAssetYearQuery(): Builder
    {
        $query = \App\Models\AssetYear::where('user_id', Auth::id());

        // Apply team filtering (handled by global scope)
        // Apply asset owner filtering if specified
        if ($this->assetOwnerId) {
            $query->whereHas('asset', function (Builder $assetQuery) {
                $assetQuery->where('asset_configuration_id', $this->assetOwnerId);
            });
        }

        return $query;
    }

    protected function getCurrentUser()
    {
        return Auth::user();
    }

    protected function hasAssetOwnerFilter(): bool
    {
        return $this->assetOwnerId !== null;
    }

    protected function getAssetOwnerName(): ?string
    {
        if (! $this->assetOwnerId) {
            return null;
        }

        $assetOwner = \App\Models\AssetConfiguration::find($this->assetOwnerId);

        return $assetOwner?->name;
    }
}
