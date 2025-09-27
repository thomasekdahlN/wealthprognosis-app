<?php

namespace App\Filament\Widgets;

use App\Services\CurrentAssetConfiguration;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

abstract class BaseAssetConfigurationWidget extends Widget
{
    protected ?int $assetConfigurationId = null;

    public function mount(): void
    {
        // Get asset_configuration_id from the session service for consistency
        $this->assetConfigurationId = app(CurrentAssetConfiguration::class)->id();

    }

    protected function getAssetConfigurationId(): ?int
    {
        // Always get the most current value from session
        return app(CurrentAssetConfiguration::class)->id() ?? $this->assetConfigurationId;
    }

    protected function getFilteredAssetQuery(): Builder
    {
        $query = \App\Models\Asset::where('user_id', Auth::id());

        // Apply team filtering (handled by global scope)
        // Apply asset configuration filtering if specified
        if ($this->assetConfigurationId) {
            $query->where('asset_configuration_id', $this->assetConfigurationId);
        }

        return $query;
    }

    protected function getFilteredAssetConfigurationQuery(): Builder
    {
        $query = \App\Models\AssetConfiguration::where('user_id', Auth::id());

        // Apply team filtering (handled by global scope)
        // Apply asset configuration filtering if specified
        if ($this->assetConfigurationId) {
            $query->where('id', $this->assetConfigurationId);
        }

        return $query;
    }

    protected function getFilteredAssetYearQuery(): Builder
    {
        $query = \App\Models\AssetYear::where('user_id', Auth::id());

        // Apply team filtering (handled by global scope)
        // Apply asset configuration filtering if specified
        if ($this->assetConfigurationId) {
            $query->whereHas('asset', function (Builder $assetQuery) {
                $assetQuery->where('asset_configuration_id', $this->assetConfigurationId);
            });
        }

        return $query;
    }

    protected function getCurrentUser()
    {
        return Auth::user();
    }

    protected function hasAssetConfigurationFilter(): bool
    {
        return $this->assetConfigurationId !== null;
    }

    protected function getAssetConfigurationName(): ?string
    {
        if (! $this->assetConfigurationId) {
            return null;
        }

        $assetConfiguration = \App\Models\AssetConfiguration::find($this->assetConfigurationId);

        return $assetConfiguration?->name;
    }
}
