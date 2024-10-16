<?php
/* Copyright (C) 2024 Thomas Ekdahl
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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

/**
 * Class Changerate
 */
class Changerate extends Model
{
    use HasFactory;

    public $changerateH = [];

    //All chanegrates are stored as percentages, as this is the same as input. Can be retrieved as Decimal for easier calculation
    //Fill the changerate structure with prognosis for all years, so we never get an empty answer.
    public function __construct(string $prognosis, int $startYear, int $stopYear)
    {

        $startYear = 1950; //Since its so much trouble if we miss a sequenze

        $file = config_path("prognosis/$prognosis.json");
        $configH = File::json($file);
        echo "Leser prognose fra : '$file'\n";

        foreach ($configH as $type => $typeH) {

            $prevChangerate = 0;
            for ($year = $startYear; $year <= $stopYear; $year++) {
                $changerate = Arr::get($configH, "$type.$year", null);

                if ($type == 'rrental') {
                    echo "$type.$year = ".Arr::get($configH, "$type.$year", null)."\n";
                }

                if (isset($changerate)) {
                    $prevChangerate = $changerate;
                } else {
                    $changerate = $prevChangerate;
                }
                if ($type == 'rrental') {
                    echo "$type.$year = $changerate\n";
                }

                $this->changerateH[$type][$year] = $changerate;
            }
        }
        //dd($this->changerateH['rental']);
    }

    /**
     * Retrieves the change rate and its decimal equivalent for a given type and year.
     *
     * @param  string  $type  The type of change for which to retrieve the rate.
     * @param  int  $year  The year for which to retrieve the rate.
     * @return array An array containing the percentage rate and its decimal equivalent.
     */
    public function getChangerateValues(string $type, int $year)
    {
        $percent = $this->changerateH[$type][$year];
        $decimal = $this->convertPercentToDecimal($percent);

        return [$percent, $decimal];
    }

    public function convertPercentToDecimal(int $percent)
    {

        if ($percent > 0) {
            $explanation = 'percent > 0';
            $decimal = 1 + ($percent / 100);
        } elseif ($percent < 0) {
            $explanation = 'percent < 0';
            $decimal = 1 - (abs($percent) / 100);
        } else {
            $explanation = 'percent = 0';
            $decimal = 1;
        }

        //print "**** convertPercentToDecimal($percent) = $decimal - expl: $explanation\n";

        return $decimal;
    }

    //Should be moved to helper?
    //Percent is either a percentage integer 7 or a text refering to the chanegrate structure dynamically like "equityfund" - It will look up equityfund changerates for that year.
    /**
     * Converts the change rate to its decimal equivalent and retrieves associated information.
     *
     * @param  bool  $debug  Indicates whether to enable debug mode.
     * @param  string|null  $original  The original value to be converted.
     * @param  int  $year  The year for which to retrieve the rate.
     * @param  string|null  $variablename  The variable name to be used for substitution.
     * @return array An array containing the percentage rate, its decimal equivalent, the variable name, and an explanation.
     */
    public function getChangerate(bool $debug, ?string $original, int $year, ?string $variablename)
    {

        $percent = 0;
        $decimal = 1;
        $explanation = '';

        if ($debug) {
            echo "    getChangerateStart($original, $year, $variablename)\n";
            //exit;
        }

        //Hvis den originale verdien er satt, da må vi ikke huske eller bruke $variablename lenger
        if ($original != null || $variablename != null) {
            if ($original != null) {

                $variablename = null;

                if (is_numeric($original)) { //Just a percentage integer, use it directly
                    $percent = $original;
                    $decimal = $this->convertPercentToDecimal($percent);
                    //$explanation = "original er satt til percent: $original, decimal: $decimal";

                } else { //Allow to read the changerate from the changerate yearly config as a variable name subsituted for its amount
                    //print "Remove the changerates from the text: $original\n";
                    $variablename = $original; //THis is a variable name, not a number, wee keep it to repeat
                    preg_match('/changerates.(\w*)/i', $original, $matches, PREG_OFFSET_CAPTURE);
                    [$percent, $decimal] = $this->getChangerateValues($matches[1][0], $year);
                    //$explanation = "original er satt til en variabel: $original = $percent% = $decimal";

                }
            } elseif ($variablename) {
                //Hvis original ikke er satt men variablename er satt, da bruker vi den inntil end repeat
                //Her er vi sikre på at det er et variabelnavn og ikke en integer.
                preg_match('/changerates.(\w*)/i', $variablename, $matches, PREG_OFFSET_CAPTURE);
                [$percent, $decimal] = $this->getChangerateValues($matches[1][0], $year);
                //$explanation = "variablename er satt: $variablename = $percent% = $decimal";
            }

            if ($debug) {
                echo "    getChangerateReturn($percent, $decimal, $variablename, $explanation)\n";
                //exit;
            }
        }

        return [$percent, $decimal, $variablename, $explanation];
    }
}
