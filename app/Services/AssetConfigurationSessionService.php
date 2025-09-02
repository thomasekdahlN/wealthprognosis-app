<?php

namespace App\Services;

use App\Models\AssetConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AssetConfigurationSessionService
{
    private const NEW_SESSION_KEY = 'active_asset_configuration_id';

    // New API (preferred)
    public static function getActiveAssetConfiguration(): ?AssetConfiguration
    {
        $id = Session::get(self::NEW_SESSION_KEY);
        return $id ? AssetConfiguration::find($id) : null;
    }

    public static function setActiveAssetConfiguration(?AssetConfiguration $assetConfiguration): void
    {
        if ($assetConfiguration) {
            Session::put(self::NEW_SESSION_KEY, $assetConfiguration->id);

        } else {
            Session::forget(self::NEW_SESSION_KEY);

        }
    }

    public static function getActiveAssetConfigurationId(): ?int
    {
        return Session::get(self::NEW_SESSION_KEY);
    }

    public static function getActiveAssetConfigurationName(): string
    {
        $assetConfiguration = self::getActiveAssetConfiguration();
        return $assetConfiguration ? $assetConfiguration->name : 'No Asset Configuration Selected';
    }

    public static function hasActiveAssetConfiguration(): bool
    {
        return Session::has(self::NEW_SESSION_KEY) && self::getActiveAssetConfiguration() !== null;
    }

    public static function getAvailableAssetConfigurations(): \Illuminate\Database\Eloquent\Collection
    {
        if (! Auth::check()) {
            return collect();
        }

        return AssetConfiguration::query()->orderBy('name')->get();
    }

    public static function clearActiveAssetConfiguration(): void
    {
        Session::forget(self::NEW_SESSION_KEY);
    }


}
