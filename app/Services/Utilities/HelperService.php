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

namespace App\Services\Utilities;

/**
 * Class HelperService
 *
 * Utility class for parsing asset path strings used in the prognosis system.
 * Provides methods to extract components from dot-notation asset paths.
 */
class HelperService
{
    /**
     * Parse an asset path string into its component elements.
     *
     * Parses paths in the format: "assetname.year.type.field"
     * Example: "fund.2022.asset.marketAmount" returns ['fund', '2022', 'asset', 'marketAmount']
     *
     * @param  string  $path  The dot-notation path to parse (e.g., "fund.2022.asset.marketAmount")
     * @return array{0: string, 1: string, 2: string, 3: string} [assetname, year, type, field]
     *
     * @throws \Exception If the path format is invalid
     */
    public function pathToElements(string $path): array
    {
        if (preg_match('/(\w+)\.(\w+)\.(\w+)\.(\w+)/i', $path, $matchesH, PREG_OFFSET_CAPTURE)) {
            return [
                $matchesH[1][0],
                $matchesH[2][0],
                $matchesH[3][0],
                $matchesH[4][0],
            ];
        }

        throw new \Exception("Invalid path format: $path");
    }

    /**
     * Normalize factor input to an integer multiplier.
     *
     * Accepts various input formats:
     * - Integer values (1, 12, etc.)
     * - Numeric strings ("1", "12", etc.)
     * - String keywords ("monthly", "yearly", "annually", etc.)
     *
     * Returns:
     * - 12 for monthly factors
     * - 1 for yearly/annual factors
     * - 1 as default for unknown input
     *
     * @param  mixed  $factor  The factor value to normalize
     * @return int The normalized multiplier (1 or 12)
     */
    public function normalizeFactor(mixed $factor): int
    {
        if (is_int($factor)) {
            return $factor > 0 ? $factor : 1;
        }

        if (is_numeric($factor)) {
            $n = (int) $factor;

            return $n > 0 ? $n : 1;
        }

        $map = [
            'monthly' => 12,
            'month' => 12,
            'yearly' => 1,
            'year' => 1,
            'annually' => 1,
            'annual' => 1,
        ];

        if (is_string($factor)) {
            $key = strtolower(trim($factor));
            if (isset($map[$key])) {
                return $map[$key];
            }
        }

        return 1;
    }

    /**
     * Convert factor input to enum value ('monthly' or 'yearly').
     *
     * Accepts various input formats:
     * - Integer values (1, 12, etc.)
     * - Numeric strings ("1", "12", etc.)
     * - String keywords ("monthly", "yearly", "annually", etc.)
     *
     * Returns:
     * - 'monthly' for factors that equal 12
     * - 'yearly' for all other values (default)
     *
     * @param  mixed  $factor  The factor value to convert
     * @return string The enum value ('monthly' or 'yearly')
     */
    public function factorToEnum(mixed $factor): string
    {
        // If already a valid enum string, return it
        if (is_string($factor) && in_array($factor, ['monthly', 'yearly'])) {
            return $factor;
        }

        // Convert to multiplier and then to enum
        $multiplier = $this->normalizeFactor($factor);

        return $multiplier === 12 ? 'monthly' : 'yearly';
    }
}
