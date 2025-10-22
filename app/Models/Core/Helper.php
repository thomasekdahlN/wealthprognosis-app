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

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Helper
 *
 * Utility class for parsing asset path strings used in the prognosis system.
 * Provides methods to extract components from dot-notation asset paths.
 */
class Helper extends Model
{
    use HasFactory;

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
    public function pathToElements($path)
    {
        $assetname = null;
        $year = null;
        $type = null;
        $field = null;

        if (preg_match('/(\w+)\.(\w+)\.(\w+)\.(\w+)/i', $path, $matchesH, PREG_OFFSET_CAPTURE)) {
            $assetname = $matchesH[1][0];
            $year = $matchesH[2][0];
            $type = $matchesH[3][0];
            $field = $matchesH[4][0];
        } else {
            throw new \Exception("Invalid path format: $path");
        }

        return [$assetname, $year, $type, $field];
    }
}
