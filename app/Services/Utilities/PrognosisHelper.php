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
 * Class Helper
 *
 * Utility class for parsing asset path strings used in the prognosis system.
 * Provides methods to extract components from dot-notation asset paths.
 */
class PrognosisHelper
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
}
