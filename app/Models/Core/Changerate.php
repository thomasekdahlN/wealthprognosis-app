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
 */
class Changerate
{
    /**
     * Precomputed change rates map: [asset_type][year] => percent
     *
     * @var array<string, array<int, float|int>>
     */
    public $changerateH = [];

    /**
     * Simple in-memory cache for value lookups: [asset_type][year] => [percent, decimal]
     *
     * @var array<string, array<int, array{0: float|int, 1: float}>>
     */
    private array $valueCache = [];

    /**
     * Scenario type (aka prognosis code) used to fetch change rates from DB.
     */
    private string $scenarioType;

    // All chanegrates are stored as percentages, as this is the same as input. Can be retrieved as Decimal for easier calculation
    // Fill the changerate structure with prognosis for all years, so we never get an empty answer.
    public function __construct(string $prognosis, int $startYear, int $stopYear)
    {
        $this->scenarioType = $prognosis;

        // Always start at 1950 to avoid gaps as legacy behavior
        $startYear = 1950;

        // Preload all active change rates for this scenario from DB, but be resilient if table is missing
        try {
            $rows = PrognosisChangeRate::query()
                ->active()
                ->forScenario($this->scenarioType)
                ->orderBy('asset_type')
                ->orderBy('year')
                ->get(['asset_type', 'year', 'change_rate']);
        } catch (\Throwable $e) {
            // In test contexts without migrations, safely fall back to an empty set
            $rows = collect();
        }

        // Group by asset type and build a per-year map with fallback to previous years
        $byType = [];
        foreach ($rows as $row) {
            $byType[$row->asset_type][(int) $row->year] = (float) $row->change_rate;
        }

        foreach ($byType as $type => $yearMap) {
            $prevChangerate = 0.0;
            for ($year = $startYear; $year <= $stopYear; $year++) {
                $changerate = $yearMap[$year] ?? null;
                if ($changerate !== null) {
                    $prevChangerate = $changerate;
                } else {
                    $changerate = $prevChangerate; // fallback to previous known rate
                }
                $this->changerateH[$type][$year] = $changerate;
            }
        }
    }

    /**
     * Retrieves the change rate and its decimal equivalent for a given type and year.
     *
     * @param  string  $type  The type of change for which to retrieve the rate.
     * @param  int  $year  The year for which to retrieve the rate.
     * @return array{0: float|int, 1: float} An array containing the percentage rate and its decimal equivalent.
     */
    public function getChangerateValues(string $type, int $year): array
    {
        if (isset($this->valueCache[$type][$year])) {
            return $this->valueCache[$type][$year];
        }

        $percent = $this->changerateH[$type][$year] ?? 0;
        $decimal = $this->convertPercentToDecimal($percent);

        return $this->valueCache[$type][$year] = [$percent, $decimal];
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
    // Percent is either a percentage integer 7 or a text refering to the chanegrate structure dynamically like "equityfund" - It will look up equityfund changerates for that year.
    /**
     * Converts the change rate to its decimal equivalent and retrieves associated information.
     *
     * @param  bool  $debug  Indicates whether to enable debug mode.
     * @param  string|null  $original  The original value to be converted.
     * @param  int  $year  The year for which to retrieve the rate.
     * @param  string|null  $variablename  The variable name to be used for substitution.
     * @return array{0: float|int, 1: float, 2: ?string, 3: string} An array containing the percentage rate, its decimal equivalent, the variable name, and an explanation.
     */
    public function getChangerate(bool $debug, ?string $original, int $year, ?string $variablename): array
    {
        $percent = 0;
        $decimal = 1.0;
        $explanation = '';

        if ($debug) {
            echo "    getChangerateStart($original, $year, $variablename)\n";
        }

        // Hvis den originale verdien er satt, da må vi ikke huske eller bruke $variablename lenger
        if ($original != null || $variablename != null) {
            if ($original != null) {
                $variablename = null;

                if (is_numeric($original)) { // Just a percentage number, use it directly
                    $percent = (float) $original;
                    $decimal = $this->convertPercentToDecimal($percent);
                } else { // Allow to read the changerate from the DB-backed structure using the variable name
                    $variablename = $original; // This is a variable name, not a number, we keep it to repeat
                    preg_match('/changerates.(\w*)/i', $original, $matches, PREG_OFFSET_CAPTURE);
                    [$percent, $decimal] = $this->getChangerateValues($matches[1][0], $year);
                }
            } elseif ($variablename) {
                // Hvis original ikke er satt men variablename er satt, da bruker vi den inntil end repeat
                // Her er vi sikre på at det er et variabelnavn og ikke en integer.
                preg_match('/changerates.(\w*)/i', $variablename, $matches, PREG_OFFSET_CAPTURE);
                [$percent, $decimal] = $this->getChangerateValues($matches[1][0], $year);
            }

            if ($debug) {
                echo "    getChangerateReturn($percent, $decimal, $variablename, $explanation)\n";
            }
        }

        return [$percent, $decimal, $variablename, $explanation];
    }
}
