<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AssetType;
use Illuminate\Support\Facades\Log;

final class TaxTypeResolver
{
    /**
     * Resolve the tax type key (e.g., 'stock', 'income') for a given asset type.
     * Returns null if the asset type doesn’t exist or has no linked tax type.
     */
    public static function resolve(?string $assetType): ?string
    {
        if ($assetType === null || $assetType === '') {
            return null;
        }

        static $cache = [];
        if (array_key_exists($assetType, $cache)) {
            return $cache[$assetType];
        }

        try {
            $assetTypeO = AssetType::query()
                ->where('type', $assetType)
                ->with('taxType')
                ->first();

            return $cache[$assetType] = $assetTypeO?->taxType?->type;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve tax_type for asset_type', [
                'asset_type' => $assetType,
                'error' => $e->getMessage(),
            ]);

            return $cache[$assetType] = null;
        }
    }

    /**
     * Convenience method to write the resolved tax type into a nested ['meta'][$key]
     * array within the provided target-by-reference. Returns the resolved value.
     *
     * @param  array<string, mixed>  $target
     */
    public static function resolveIntoMeta(array &$target, string $key = 'tax_type', ?string $assetType = null): ?string
    {
        $resolved = self::resolve($assetType);
        if (! isset($target['meta']) || ! is_array($target['meta'])) {
            $target['meta'] = [];
        }
        $target['meta'][$key] = $resolved;

        return $resolved;
    }
}
