<?php

namespace App\Services;

use App\Models\AssetConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AssetConfigurationSessionService
{
    private const SESSION_KEY = 'active_asset_owner_id';

    /**
     * Get the currently active asset owner
     */
    public static function getActiveAssetOwner(): ?AssetConfiguration
    {
        $assetOwnerId = Session::get(self::SESSION_KEY);

        if (!$assetOwnerId) {
            return null;
        }

        return AssetConfiguration::find($assetOwnerId);
    }

    /**
     * Set the active asset owner
     */
    public static function setActiveAssetOwner(?AssetConfiguration $assetOwner): void
    {
        if ($assetOwner) {
            Session::put(self::SESSION_KEY, $assetOwner->id);
        } else {
            Session::forget(self::SESSION_KEY);
        }
    }

    /**
     * Get the active asset owner ID
     */
    public static function getActiveAssetOwnerId(): ?int
    {
        return Session::get(self::SESSION_KEY);
    }

    /**
     * Get the active asset configuration name for display
     */
    public static function getActiveAssetOwnerName(): string
    {
        $assetConfiguration = self::getActiveAssetOwner();

        return $assetConfiguration ? $assetConfiguration->name : 'No Asset Configuration Selected';
    }

    /**
     * Check if an asset configuration is currently active
     */
    public static function hasActiveAssetOwner(): bool
    {
        return Session::has(self::SESSION_KEY) && self::getActiveAssetOwner() !== null;
    }

    /**
     * Get all available asset configurations for the current user
     */
    public static function getAvailableAssetOwners(): \Illuminate\Database\Eloquent\Collection
    {
        if (!Auth::check()) {
            return collect();
        }

        return AssetConfiguration::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Clear the active asset owner session
     */
    public static function clearActiveAssetOwner(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
