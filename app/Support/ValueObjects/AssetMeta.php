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

namespace App\Support\ValueObjects;

/**
 * Value object representing asset metadata extracted from the prognosis data structure.
 *
 * This object encapsulates all metadata fields for an asset, providing type-safe
 * access to asset properties used throughout the prognosis calculations.
 */
readonly class AssetMeta
{
    public function __construct(
        public string $assetname,
        public int $year,
        public string $type,
        public string $group,
        public ?string $name = null,
        public ?string $description = null,
        public bool $active = true,
        public ?string $taxProperty = null,
        public ?string $taxCountry = null,
        public ?string $taxType = null,
        public bool $debug = false,
        public ?string $icon = null,
        public ?string $color = null,
    ) {}

    /**
     * Create an AssetMeta instance from a path and dataH structure.
     *
     * @param  array<string, mixed>  $dataH  The main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     * @return self|null Returns null if path is invalid
     */
    public static function fromPath(array $dataH, string $path): ?self
    {
        if (! preg_match('/(\w+)\.(\d+)/i', $path, $matches)) {
            return null;
        }

        $assetname = $matches[1];
        $year = (int) $matches[2];

        $meta = $dataH[$assetname]['meta'] ?? [];

        return new self(
            assetname: $assetname,
            year: $year,
            type: $meta['type'] ?? '',
            group: $meta['group'] ?? 'private',
            name: $meta['name'] ?? null,
            description: $meta['description'] ?? null,
            active: $meta['active'] ?? true,
            taxProperty: $meta['taxProperty'] ?? null,
            taxCountry: $meta['taxCountry'] ?? null,
            taxType: $meta['tax_type'] ?? null,
            debug: $meta['debug'] ?? false,
            icon: $meta['icon'] ?? null,
            color: $meta['color'] ?? null,
        );
    }

    /**
     * Check if the asset is active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Check if debug mode is enabled for this asset.
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Check if the asset has property tax configured.
     */
    public function hasTaxProperty(): bool
    {
        return $this->taxProperty !== null && $this->taxProperty !== '';
    }

    /**
     * Check if the asset belongs to the company group.
     */
    public function isCompany(): bool
    {
        return $this->group === 'company';
    }

    /**
     * Check if the asset belongs to the private group.
     */
    public function isPrivate(): bool
    {
        return $this->group === 'private';
    }
}
