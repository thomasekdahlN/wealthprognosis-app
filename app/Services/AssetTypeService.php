<?php

/* Copyright (C) 2025 Thomas Ekdahl
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace App\Services;

use App\Models\AssetType;

/**
 * AssetTypeService
 *
 * Centralized service for asset type metadata and capability checking.
 * Provides cached access to asset type properties to avoid N+1 queries.
 */
class AssetTypeService
{
    /** @var array<string, bool> */
    private array $liquidMap = [];

    /** @var array<string, bool> */
    private array $savingMap = [];

    /** @var array<string, bool> */
    private array $showStatisticsMap = [];

    /** @var array<string, array<string, bool>> */
    private array $capabilityCache = [];

    /**
     * Constructor - preloads commonly used maps to avoid repeated DB queries
     */
    public function __construct()
    {
        $this->preloadMaps();
    }

    /**
     * Preload commonly used maps from database
     */
    private function preloadMaps(): void
    {
        try {
            $this->liquidMap = AssetType::query()
                ->pluck('is_liquid', 'type')
                ->toArray();

            $this->savingMap = AssetType::query()
                ->pluck('is_saving', 'type')
                ->toArray();

            $this->showStatisticsMap = AssetType::query()
                ->pluck('show_statistics', 'type')
                ->toArray();
        } catch (\Throwable $e) {
            // Fallback for CLI/testing environments where DB might not be available
            $this->liquidMap = [];
            $this->savingMap = [];
            $this->showStatisticsMap = [];
        }
    }

    /**
     * Check if asset type is liquid (can be sold in parts for FIRE)
     *
     * @param  string  $assetType  The asset type code to check
     * @return bool True if the asset type is liquid, false otherwise
     */
    public function isLiquid(string $assetType): bool
    {
        return (bool) ($this->liquidMap[$assetType] ?? false);
    }

    /**
     * Check if asset type counts as savings for FIRE calculations
     *
     * @param  string  $assetType  The asset type code to check
     * @return bool True if the asset type is a saving type, false otherwise
     */
    public function isSavingType(string $assetType): bool
    {
        return (bool) ($this->savingMap[$assetType] ?? false);
    }

    /**
     * Check if asset type should be shown in statistics
     *
     * @param  string  $assetType  The asset type code to check
     * @return bool True if the asset type should be shown in statistics
     */
    public function isShownInStatistics(string $assetType): bool
    {
        return (bool) ($this->showStatisticsMap[$assetType] ?? false);
    }

    /**
     * Check if asset type is FIRE eligible
     * (can generate passive income through market value or income generation)
     *
     * @param  string  $assetType  The asset type code to check
     * @return bool True if the asset type is FIRE eligible
     */
    public function isFireEligible(string $assetType): bool
    {
        $capabilities = $this->getCapabilities($assetType);

        return ($capabilities['can_have_market_value'] ?? false)
            || ($capabilities['can_generate_income'] ?? false);
    }

    /**
     * Get a specific capability for an asset type
     *
     * @param  string  $assetType  The asset type code
     * @param  string  $capability  The capability name
     * @return bool True if the asset type has the capability
     */
    public function getCapability(string $assetType, string $capability): bool
    {
        $capabilities = $this->getCapabilities($assetType);

        return $capabilities[$capability] ?? false;
    }

    /**
     * Get all capabilities for an asset type (cached)
     *
     * @param  string  $assetType  The asset type code
     * @return array<string, bool> Array of capability flags
     */
    public function getCapabilities(string $assetType): array
    {
        // Return from cache if available
        if (isset($this->capabilityCache[$assetType])) {
            return $this->capabilityCache[$assetType];
        }

        // Fetch from database
        $assetTypeModel = AssetType::where('type', $assetType)->first();

        if (! $assetTypeModel) {
            return [];
        }

        // Cache the capabilities
        $this->capabilityCache[$assetType] = [
            'can_generate_income' => $assetTypeModel->can_generate_income,
            'can_generate_expenses' => $assetTypeModel->can_generate_expenses,
            'can_have_mortgage' => $assetTypeModel->can_have_mortgage,
            'can_have_market_value' => $assetTypeModel->can_have_market_value,
            'is_fire_sellable' => $assetTypeModel->is_fire_sellable,
            'is_tax_optimized' => $assetTypeModel->is_tax_optimized,
            'is_liquid' => $assetTypeModel->is_liquid,
            'is_saving' => $assetTypeModel->is_saving,
            'show_statistics' => $assetTypeModel->show_statistics,
        ];

        return $this->capabilityCache[$assetType];
    }

    /**
     * Clear all caches (useful after seeding/migrations)
     */
    public function clearCache(): void
    {
        $this->liquidMap = [];
        $this->savingMap = [];
        $this->showStatisticsMap = [];
        $this->capabilityCache = [];
        $this->preloadMaps();
    }
}

