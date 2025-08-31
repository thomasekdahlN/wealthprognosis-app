<?php

namespace App\Helpers;

use App\Models\AssetType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AssetTypeValidator
{
    /**
     * Cache key for valid asset types
     */
    private const CACHE_KEY = 'valid_asset_types';

    /**
     * Cache duration in seconds (1 hour)
     */
    private const CACHE_DURATION = 3600;

    /**
     * Get all valid asset type codes from the database
     */
    public static function getValidAssetTypes(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            return AssetType::active()
                ->pluck('type')
                ->toArray();
        });
    }

    /**
     * Validate if a given asset type code is valid
     */
    public static function isValid(?string $assetType): bool
    {
        if (empty($assetType)) {
            return false;
        }

        return in_array($assetType, self::getValidAssetTypes(), true);
    }

    /**
     * Get asset type details from database
     */
    public static function getAssetTypeDetails(string $assetType): ?AssetType
    {
        return AssetType::active()
            ->where('type', $assetType)
            ->first();
    }

    /**
     * Get suggestions for similar asset type names
     */
    public static function getSuggestions(string $invalidAssetType): array
    {
        $suggestions = [];
        $invalidAssetType = strtolower($invalidAssetType);
        $validTypes = self::getValidAssetTypes();

        foreach ($validTypes as $validType) {
            // Check if the invalid type is contained in the valid type name
            if (strpos(strtolower($validType), $invalidAssetType) !== false) {
                $suggestions[] = $validType;
            }
        }

        // Also check by similarity (Levenshtein distance)
        if (empty($suggestions)) {
            foreach ($validTypes as $validType) {
                $distance = levenshtein(strtolower($invalidAssetType), strtolower($validType));
                if ($distance <= 2) { // Allow up to 2 character differences
                    $suggestions[] = $validType;
                }
            }
        }

        return array_slice($suggestions, 0, 3); // Return max 3 suggestions
    }

    /**
     * Validate and sanitize an asset type, with warning if invalid
     */
    public static function validateAndSanitize(?string $assetType, string $context = 'asset'): ?string
    {
        if (empty($assetType)) {
            return null;
        }

        // Trim whitespace and convert to lowercase for comparison
        $cleanAssetType = trim($assetType);

        if (self::isValid($cleanAssetType)) {
            return $cleanAssetType;
        }

        // Asset type is invalid, provide suggestions
        $suggestions = self::getSuggestions($cleanAssetType);
        $suggestionText = empty($suggestions) ? '' : ' Suggestions: '.implode(', ', $suggestions);

        Log::warning("Invalid asset type '{$cleanAssetType}' for {$context}. Setting to null.{$suggestionText}");
        echo "  ⚠️  Invalid asset type '{$cleanAssetType}' for {$context}. Setting to null.{$suggestionText}\n";

        return null;
    }

    /**
     * Get all asset types with their details (for dropdowns, etc.)
     */
    public static function getAssetTypesForSelect(): array
    {
        return Cache::remember(self::CACHE_KEY.'_select', self::CACHE_DURATION, function () {
            return AssetType::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name', 'type')
                ->toArray();
        });
    }

    /**
     * Get asset types by category
     */
    public static function getAssetTypesByCategory(): array
    {
        return Cache::remember(self::CACHE_KEY.'_by_category', self::CACHE_DURATION, function () {
            return AssetType::active()
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->groupBy('category')
                ->map(function ($types) {
                    return $types->pluck('name', 'type')->toArray();
                })
                ->toArray();
        });
    }

    /**
     * Clear the asset types cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'_select');
        Cache::forget(self::CACHE_KEY.'_by_category');
    }

    /**
     * Get asset type capabilities
     */
    public static function getAssetTypeCapabilities(string $assetType): array
    {
        $assetTypeModel = self::getAssetTypeDetails($assetType);

        if (! $assetTypeModel) {
            return [];
        }

        return [
            'can_generate_income' => $assetTypeModel->can_generate_income,
            'can_generate_expenses' => $assetTypeModel->can_generate_expenses,
            'can_have_mortgage' => $assetTypeModel->can_have_mortgage,
            'can_have_market_value' => $assetTypeModel->can_have_market_value,
            'is_fire_sellable' => $assetTypeModel->is_fire_sellable,
            'is_tax_optimized' => $assetTypeModel->is_tax_optimized,
        ];
    }

    /**
     * Check if asset type supports a specific capability
     */
    public static function supportsCapability(string $assetType, string $capability): bool
    {
        $capabilities = self::getAssetTypeCapabilities($assetType);

        return $capabilities[$capability] ?? false;
    }
}
