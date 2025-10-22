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

use App\Models\PrognosisChangeRate;

/**
 * Class Changerate
 *
 * Lazy-loading repository for change rate data with in-memory caching.
 * Cache structure: [scenarioType][assetType][year] => [percent, decimal]
 */
class Changerate
{
    /**
     * In-memory cache: [scenarioType][assetType][year] => [percent, decimal]
     *
     * @var array<string, array<string, array<int, array{0: float|int, 1: float}>>>
     */
    private array $cache = [];

    /**
     * Scenario type (aka prognosis code) used to fetch change rates from DB.
     */
    private string $scenarioType;

    /**
     * Constructor - only stores the scenario type, does not preload data.
     *
     * @param  string  $scenarioType  The scenario/prognosis type (e.g., 'realistic', 'baseline')
     */
    public function __construct(string $scenarioType = 'realistic')
    {
        $this->scenarioType = $scenarioType;
    }

    /**
     * Retrieves the change rate and its decimal equivalent for a given type and year.
     * If the exact year doesn't exist, finds the closest year that is less than the requested year.
     *
     * @param  string  $type  The asset type for which to retrieve the rate.
     * @param  int  $year  The year for which to retrieve the rate.
     * @return array{0: float|int, 1: float} An array containing the percentage rate and its decimal equivalent.
     */
    public function getChangerateValues(string $type, int $year): array
    {
        // Check cache first
        if (isset($this->cache[$this->scenarioType][$type][$year])) {
            return $this->cache[$this->scenarioType][$type][$year];
        }

        // Load from database
        $percent = $this->loadChangeRate($type, $year);
        $decimal = $this->convertPercentToDecimal($percent);

        // Cache and return
        return $this->cache[$this->scenarioType][$type][$year] = [$percent, $decimal];
    }

    /**
     * Load the change rate for a specific scenario type, asset type, and year from the database.
     * If the exact year doesn't exist, finds the closest year that is less than the requested year.
     *
     * @param  string  $type  The asset type
     * @param  int  $year  The requested year
     * @return float The change rate percentage
     */
    private function loadChangeRate(string $type, int $year): float
    {
        try {
            $changeRate = PrognosisChangeRate::query()
                ->active()
                ->forScenario($this->scenarioType)
                ->where('asset_type', $type)
                ->where('year', '<=', $year)
                ->orderBy('year', 'desc')
                ->value('change_rate');

            return $changeRate !== null ? (float) $changeRate : 0.0;
        } catch (\Throwable $e) {
            // In test contexts without migrations, safely fall back to 0
            return 0.0;
        }
    }

    public function convertPercentToDecimal(float|int $percent): float
    {
        if ($percent > 0) {
            $decimal = 1 + ((float) $percent / 100);
        } elseif ($percent < 0) {
            $decimal = 1 - (abs((float) $percent) / 100);
        } else {
            $decimal = 1.0;
        }

        return $decimal;
    }

    // Should be moved to helper?
    // Percent is either a percentage integer or a text refering to the chanegrate structure dynamically like "equityfund" - It will look up equityfund changerates for that year.
    /**
     * Converts the change rate to its decimal equivalent and retrieves associated information.
     *
     * @param  bool  $debug  Indicates whether to enable debug mode.
     * @param  string|null  $original  The original value to be converted.
     * @param  int  $year  The year for which to retrieve the rate.
     * @param  string|null  $type  The variable name to be used for substitution.
     * @return array{0: float|int, 1: float, 2: ?string, 3: string} An array containing the percentage rate, its decimal equivalent, the variable name, and an explanation.
     */
    public function getChangerate(bool $debug, ?string $original, int $year, ?string $type): array
    {
        $percent = 0;
        $decimal = 1.0;
        $explanation = '';

        if ($debug) {
            echo "    getChangerateStart($original, $year, $type)\n";
        }

        // Hvis den originale verdien er satt, da må vi ikke huske eller bruke $type lenger
        if ($original != null || $type != null) {
            if ($original != null) {
                $type = null;

                if (is_numeric($original)) { // Just a percentage number, use it directly
                    $percent = (float) $original;
                    $decimal = $this->convertPercentToDecimal($percent);
                } else { // Allow to read the changerate from the DB-backed structure using the type name
                    $type = $original; // This is a variable name, not a number, we keep it to repeat
                    preg_match('/changerates.(\w*)/i', $original, $matches, PREG_OFFSET_CAPTURE);
                    [$percent, $decimal] = $this->getChangerateValues($matches[1][0], $year);
                }
            } elseif ($type) {
                // Hvis original ikke er satt men type er satt, da bruker vi den inntil end repeat
                // Her er vi sikre på at type er et navn og ikke en integer.
                preg_match('/changerates.(\w*)/i', $type, $matches, PREG_OFFSET_CAPTURE);
                [$percent, $decimal] = $this->getChangerateValues($matches[1][0], $year);
            }

            if ($debug) {
                echo "    getChangerateReturn($percent, $decimal, $type, $explanation)\n";
            }
        }

        return [$percent, $decimal, $type, $explanation];
    }
}
