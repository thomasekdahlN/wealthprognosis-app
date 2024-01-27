<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class TaxRealization extends Model
{
    use HasFactory;

    public $taxH = [];

    //Will be rewritten to support yearly tax differences, just faking for now.
    //Should probably be a deep nested json structure.
    public function __construct($config, $startYear, $stopYear)
    {

        $file = "tax/$config.json";
        $configH = json_decode(Storage::disk('local')->get($file), true);

        foreach ($configH as $type => $typeH) {
            $this->taxH[$type] = $typeH;
        }
    }

    public function getTaxRealization($taxGroup, $taxType, $year)
    {
        if($taxGroup == 'company') {
            //A company does not pay realization tax
            return 22/100; #FIX: Hardcoded tax of all company assets, 22%
        }

        return Arr::get($this->taxH, "$taxType.realization", 0) / 100;
    }

    public function getTaxShieldRealization($taxGroup, $taxType, $year)
    {
        $percent = 0;

        //Note: Not all assets types has tax shield
        if (Arr::get($this->taxShieldTypes, $taxType)) {
            //Note: All Tax shield percentage are changed by the government yearly.
            $percent = Arr::get($this->taxH, "shareholdershield.$year", null);
            if (! isset($percent)) {
                //Fallback to our prognosis for the comming years if no percentage curve is given
                $percent = Arr::get($this->taxH, 'shareholdershield.all', 23);
            }
            //print "   shareholdershield.$year: $percent%\n";
            $percent = $percent / 100;
        }

        return $percent;
    }

    public function taxCalculationRealization(bool $debug, bool $transfer, string $taxGroup, string $taxType, int $year, float $amount, float $acquisitionAmount = 0, float $assetDiffAmount, float $taxShieldPrevAmount = 0, ?int $acquisitionYear = 0)
    {
        $numberOfYears = $year - $acquisitionYear;

        $realizationTaxPercent = $this->getTaxRealization($taxGroup, $taxType, $year);
        $realizationTaxShieldPercent = $this->getTaxShieldRealization($taxGroup, $taxType, $year);

        //Forskjell på hva man betaler skatt av
        $realizationTaxableAmount = 0; //The amount to pay tax from. Often calculated as taxof(MarketAmount - acquisitionAmount). We assume we always sell to market value
        $realizationTaxAmount = 0;
        $realizationTaxShieldAmount = 0;

        //Skjermingsfradrag
        if ($realizationTaxShieldPercent > 0) {
            //TaxShield is calculated on an assets value from 1/1 each year, and accumulated until used.
            $realizationTaxShieldAmount = round(($amount * $realizationTaxShieldPercent) + $taxShieldPrevAmount); //Tax shield accumulates over time, until you actually transfer an amount, then it is reduced accordigly until zero.
            //print "    Skjermingsfradrag: acquisitionAmount: $acquisitionAmount, realizationTaxShieldAmount: $realizationTaxShieldAmount, realizationTaxShieldPercent: $realizationTaxShieldPercent\n";
        } else {
            $realizationTaxShieldAmount = $taxShieldPrevAmount;
        }
        if ($realizationTaxShieldAmount < 0) { //Tax shield can not go below zero.
            $realizationTaxShieldAmount = 0;
        }

        if ($debug && $amount != 0) {
            echo "\n  taxCalculationRealizationStart $taxGroup.$taxType.$year: amount: $amount, acquisitionAmount: $acquisitionAmount, taxShieldPrevAmount: $taxShieldPrevAmount, acquisitionYear: $acquisitionYear, realizationTaxPercent: $realizationTaxPercent, realizationTaxShieldAmount:$realizationTaxShieldAmount, realizationTaxShieldPercent:$realizationTaxShieldPercent\n";
        }

        if ($taxType == 'salary') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxType == 'pension') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxType == 'income') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxType == 'house') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;  //Salg av eget hus er alltid skattefritt om man har bodd der minst ett år siste 2 år (regne på det?)

        } elseif ($taxType == 'cabin') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;  //Men må ha hatt hytta mer enn 5 eller 8 år for å bli skattefritt. (regne på det?)

        } elseif ($taxType == 'car') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxType == 'boat') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxType == 'property') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = $realizationTaxableAmount * $realizationTaxPercent;  //verdien nå minus inngangsverdien skal skattes ved salg
            }

        } elseif ($taxType == 'rental') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien skal skattes ved salg
            }

        } elseif ($taxType == 'stock') {

            if ($taxGroup == 'company') {
                //Fritaksmodellen
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = 0;
                    $realizationTaxAmount = 0;
                }
            } else {
                if ($amount - $acquisitionAmount > 0) {
                    $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                    $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien skal skattes ved salg?
                }
            }

        } elseif ($taxType == 'bondfund') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxType == 'equityfund') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxType == 'ask') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxType == 'otp') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxType == 'ips') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxType == 'crypto') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxType == 'gold') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxType == 'bank') {
            $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
            $realizationTaxAmount = 0;  //Ingen skatt ved salg.

        } elseif ($taxType == 'cash') {
            $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
            $realizationTaxAmount = 0;  //Ingen skatt ved salg.

        } else {

            if ($amount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
        }

        ################################################################################################################
        //TaxShield handling
        //Skjermingsfradrag FIX: Trekker fra skjermingsfradraget fra skatten, men usikker på om det burde vært regnet ut i en ny kolonne igjen..... Litt inkonsekvent.
        $realizationBeforeShieldTaxAmount = $realizationTaxAmount;

        if($transfer && $taxGroup == 'private') {
            //tax shield is only used when tansfering between private assets or from company to private asset - never between company assets.
            //We run simulations for every year that should not change the Shield, only a real transfer reduces the shield, all other activity increases the shield
            if ($realizationTaxAmount >= $realizationTaxShieldAmount) {
                //print "REDUCING TAX SHIELD1\n";
                $realizationTaxAmount -= $realizationTaxShieldAmount; //Reduce the tax amount by the taxShieldAmount
                $realizationTaxShieldAmount = 0; //Then taxShieldAmount is used and has to go to zero.
            } else {
                //print "REDUCING TAX SHIELD2\n";
                $realizationTaxShieldAmount -= $realizationTaxAmount; //We reduce it by the amount we used
                $realizationTaxAmount = 0; //Then taxAmount is zero, since the entire emount was taxShielded.
            }
        }

        if ($realizationTaxAmount < 0) {
            $realizationTaxAmount = 0; //Skjermingsfradraget kan ikke være større enn skatten
        }
        $acquisitionAmount -= $amount; //We remove the transfered amount from the acquisitionAmount
        if ($acquisitionAmount < 0) {
            $acquisitionAmount = 0; //Kjøpsbeløpet kan ikke være negativt.
        }

        if ($debug) {
            echo "  taxCalculationRealizationEnd $taxGroup.$taxType.$year: realizationTaxableAmount: $realizationTaxableAmount, realizationBeforeShieldTaxAmount: $realizationBeforeShieldTaxAmount, realizationTaxAmount: $realizationTaxAmount, acquisitionAmount: $acquisitionAmount, realizationTaxPercent: $realizationTaxPercent, realizationTaxShieldAmount:$realizationTaxShieldAmount, realizationTaxShieldPercent:$realizationTaxShieldPercent\n";
        }

        //V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        return [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent];
    }
}
