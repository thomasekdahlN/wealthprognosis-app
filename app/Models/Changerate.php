<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class Changerate extends Model
{
    use HasFactory;

    public $changerateH = [];

    #All chanegrates are stored as percentages, as this is the same as input. Can be retrieved as Decimal for easier calculation
    #Fill the changerate structure with prognosis for all years, so we never get an empty answer.
    public function __construct(string $prognosis, int $startYear, int $stopYear)
    {

        $startYear = 1970; #Since its so much trouble if we miss a sequenze

        $file = "prognosis/$prognosis.json";
        $configH = json_decode(Storage::disk('local')->get($file), true);
        print "Leser: '$file'\n";

        foreach($configH as $type => $typeH) {
            $prevChangerate = 0;
            for ($year = $startYear; $year <= $stopYear; $year++) {
                #print "$type.$year = " . Arr::get($configH, "$type.$year", null) . "\n";

                $changerate = Arr::get($configH, "$type.$year", null);
                if(isset($changerate)) {
                    $prevChangerate = $changerate;
                } else {
                    $changerate = $prevChangerate;
                }
                $this->changerateH[$type][$year] = $changerate;
            }
         }
    }

    public function getChangerate(string $type, int $year)
    {
        $percent = $this->changerateH[$type][$year];
        $decimal = $this->convertPercentToDecimal($percent);
        return [$percent, $decimal];
    }

    public function convertPercentToDecimal(int $percent) {

        if($percent > 0) {
            $explanation = 'percent > 0';
            $decimal = 1 + ($percent / 100);
        } elseif($percent < 0) {
            $explanation = 'percent < 0';
            $decimal = 1 - (abs($percent) / 100);
        } else {
            $explanation = 'percent = 0';
            $decimal = 1;
        }

        #print "**** convertPercentToDecimal($percent) = $decimal - expl: $explanation\n";

        return $decimal;
    }

    #Really to Excel.
    public function decimalToDecimal(int $value){

        if($value > 0) {
            $value = $value - 1;
        } else {
            $value = null;
        }
        return $value;
    }

    #Should be moved to helper?
    #Percent is either a percentage integer 7 or a text refering to the chanegrate structure dynamically like "fond" - It will look up fond changerates for that year.
    public function convertChangerate(bool $debug, ?string $original, int $year, ?string $variablename){

        $percent = 0;
        $decimal = 1;
        $explanation = '';

        #Hvis den originale verdien er satt, da må vi ikke huske eller bruke $variablename lenger
        if($original != null || $variablename != null) {
            if ($original != null) {

                $variablename = null;

                if (is_numeric($original)) { #Just a percentage integer, use it directly
                    $percent = $original;
                    $decimal = $this->convertPercentToDecimal($percent);
                    $explanation = "original er satt til percent: $original, decimal: $decimal";

                } else { #Allow to read the changerate from the changerate yearly config as a variable name subsituted for its value
                    #print "Remove the changerates from the text: $original\n";
                    $variablename = $original; #THis is a variable name, not a number, wee keep it to repeat
                    preg_match('/changerates.(\w*)/i', $original, $matches, PREG_OFFSET_CAPTURE);
                    list($percent, $decimal) = $this->getChangerate($matches[1][0], $year);
                    $explanation = "original er satt til en variabel: $original = $percent% = $decimal";

                }
            } elseif ($variablename) {
                #Hvis original ikke er satt men variablename er satt, da bruker vi den inntil end repeat
                #Her er vi sikre på at det er et variabelnavn og ikke en integer.
                preg_match('/changerates.(\w*)/i', $variablename, $matches, PREG_OFFSET_CAPTURE);
                list($percent, $decimal) = $this->getChangerate($matches[1][0], $year);
                $explanation = "variablename er satt: $variablename = $percent% = $decimal";
            }

            if ($debug) {
                print "-- convertChangerate($original, $year, $variablename) = $percent% = $decimal, $variablename, $explanation\n";
                #exit;
            }
        }

        return [$percent, $decimal, $variablename, $explanation];
    }
}
