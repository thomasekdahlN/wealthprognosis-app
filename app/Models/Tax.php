<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class Tax extends Model
{
    use HasFactory;

    public $taxH = [];

    //Tax types where we check for propertyTax
    public $taxPropertyTypes = [
        'house' => true,
        'rental' => true,
        'cabin' => true,
        'property' => true,
    ];

    //Asset types that will automatically be calculated with tax shield
    public $taxShieldTypes = [
        'stock' => true,
        'equityfund' => true,
        'bondfund' => true,
        'ask' => true,
        'loantocompany' => true, //lån til andre, fradrag om det er låm
        'soleproprietorship' => true, //Enkeltpersonforetak
    ];

    //Will be rewritten to support yearly tax differences, just faking for now.
    //Should probably be a deep nested json structure.
    public function __construct($config, $startYear, $stopYear)
    {

        $file = "tax/$config.json";
        $configH = json_decode(Storage::disk('local')->get($file), true);
        echo "Leser: '$file'\n";

        foreach ($configH as $type => $typeH) {
            $prevTaxH = [];
            //for ($year = $startYear; $year <= $stopYear; $year++) {
            //print "$type.$year = " . Arr::get($configH, "$type.$year", null) . "\n";

            //$taxH = Arr::get($configH, "$type.$year", null);
            //if(isset($taxH)) {
            //    $prevTaxH = $taxH;
            //} else {
            //    $taxH = $prevTaxH;
            //}
            $this->taxH[$type] = $typeH;
            //}
        }
        //dd( $this->taxH);
    }

    public function getTaxYearly($taxtype, $year)
    {

        return Arr::get($this->taxH, "$taxtype.yearly", 0) / 100;
    }

    public function getTaxRealization($taxtype, $year)
    {

        return Arr::get($this->taxH, "$taxtype.realization", 0) / 100;
    }

    public function getTaxShieldRealization($taxtype, $year)
    {
        $percent = 0;

        //Note: Not all assets types has tax shield
        if (Arr::get($this->taxShieldTypes, $taxtype)) {
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

    //Returnerer hvor stor del av formuen som blir skattlagt
    public function getTaxableFortune($taxtype, $year)
    {

        return Arr::get($this->taxH, "$taxtype.fortune", 0) / 100;
    }

    //Returnerer formuesskatten i %
    public function getFortuneTax($year)
    {

        return Arr::get($this->taxH, 'fortune.yearly', 0) / 100;
    }

    public function getFortuneTaxStandardDeduction($year)
    {

        return Arr::get($this->taxH, 'fortune.standardDeduction', 0);
    }

    public function getPropertyTaxable($year)
    {
        return Arr::get($this->taxH, 'property.fortune', 0) / 100;
    }

    public function getPropertyTaxStandardDeduction($year)
    {
        return Arr::get($this->taxH, 'property.standardDeduction', 0);
    }

    public function getPropertyTax($year)
    {
        return Arr::get($this->taxH, 'property.yearly', 0) / 100;
    }

    //Only run on totals. Difficult to run on separate assets as you cannot add them together.
    //ToDo different tax'es for companies?
    public function fortuneTaxGroupCalculation($group, $fortuneTaxableAmount, $year)
    {

        $fortuneTaxPercent = $this->getFortuneTax($year);
        $taxStandardDeductionAmount = $this->getFortuneTaxStandardDeduction($year);

        if ($group == 'private' || $group == 'total') {
            //ToDo: Check if bunnfradrag bare er for private
            if ($fortuneTaxableAmount <= $taxStandardDeductionAmount) {
                $fortuneTaxableAmount = 0; //Det betales ikke formuesskatt på skattbar formue under 1.7 mill. FIX lese fra confid, støtte årlige forskjeller. Nesten i mål. Tar år som input, men ignorerer år enn så lenge.
            } else {
                $fortuneTaxableAmount -= $taxStandardDeductionAmount;
            }
        }

        $fortuneTaxAmount = $fortuneTaxableAmount * $fortuneTaxPercent; //Calculate the tax you shall pay from the taxable fortune

        return [$fortuneTaxAmount, $fortuneTaxPercent];
    }

    public function taxCalculationFortune(string $taxtype, int $year, ?int $marketAmount = 0, ?int $taxableAmount = 0, ?bool $taxableAmountOverride = false)
    {
        $taxAmount = 0;
        $taxStandardDeductionAmount = $this->getFortuneTaxStandardDeduction($year);
        $taxPercent = $this->getFortuneTax($year);
        $taxablePercent = $this->getTaxableFortune($taxtype, $year);
        $taxablePropertyAmount = 0;
        $taxPropertyAmount = 0;
        $taxablePropertyPercent = $this->getPropertyTaxable($year);
        $taxPropertyPercent = $this->getPropertyTax($year);
        $taxPropertyStandardDeductionAmount = $this->getPropertyTaxStandardDeduction($year);

        if ($taxableAmountOverride && $taxableAmount > 0) {
            $taxablePercent = 0; //If $fortuneTaxableAmount is set, we ignore the $fortuneTaxablePercent since that should be calculated from the market value and when $fortuneTaxableAmount is set, we do not releate tax to market value anymore.
        //echo "   taxableAmount ovveride: $taxableAmount\n";
        } else {
            $taxableAmount = $marketAmount * $taxablePercent; //Calculate the amount from wich the tax is calculated from the market value if $fortuneTaxableAmount is not set
            //echo "   taxableAmount normal: $taxableAmount\n";
        }

        //Only fortune tax on more than 1.7million pr 2023
        if ($taxableAmount > $taxStandardDeductionAmount) { //FIX: Should be read from config
            $taxAmount = ($taxableAmount - $taxStandardDeductionAmount) * $taxPercent; //Calculate the tax you shall pay from the taxable fortune
        }
        //print "$AmountTaxableFortune, $taxAmount, $taxPercent\n";

        if (Arr::get($this->taxPropertyTypes, $taxtype)) {
            $taxablePropertyAmount = ($marketAmount - $taxPropertyStandardDeductionAmount) * $taxablePropertyPercent;
            if ($taxablePropertyAmount > 0 && $taxPropertyPercent > 0) {
                $taxPropertyAmount = $taxablePropertyAmount * $taxPropertyPercent;
            } else {
                $taxablePropertyPercent = 0;
            }
            //print "   taxablePropertyAmount: $taxablePropertyAmount, taxPropertyAmount: $taxPropertyAmount\n";
        }

        return [$taxableAmount, $taxablePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent];
    }

    public function taxCalculationCashflow(bool $debug, string $taxtype, int $year, ?float $income, ?float $expence)
    {

        //FIX - should probably not be pairs, but the same thing.
        $cashflowTaxPercent = $this->getTaxYearly($taxtype, $year); //FIX

        //Forskjell på hva man betaler skatt av
        $cashflowBeforeTaxAmount = 0;
        $cashflowAfterTaxAmount = 0;

        $cashflowTaxAmount = 0;

        if ($debug) {
            echo "\ntaxtype: $taxtype.$year: income: $income, expence: $expence, cashflowTaxPercent: $cashflowTaxPercent\n";
        }

        if ($taxtype == 'salary') {
            $cashflowTaxAmount = $income * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'pension') {
            $cashflowTaxAmount = $income * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'income') {
            $cashflowTaxAmount = $income * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'house') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'cabin') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - 10000) * $cashflowTaxPercent; //Airbnb skatten
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'rental') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'property') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'stock') {
            //Hm. Aksjer som selges skattes bare som formuesskatt og ved realisasjon
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            //FIX: Skjermingsfradrag
            //FIX: Stor forskjell på skattlegging mot privat 35.2%vs bedrift 0%?.
            $cashflowTaxAmount = 0;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'bondfund') {
            //Hm. bondfund i praksis bare eid i firmaer, alt privat i ASK og skattes bare ved realisasjon + formuesskatt
            $cashflowTaxAmount = 0;

        } elseif ($taxtype == 'equityfund') {
            //Hm. equityfund i praksis bare eid i firmaer, alt privat i ASK og skattes bare ved realisasjon + formuesskatt
            $cashflowTaxAmount = 0;

        } elseif ($taxtype == 'ask') {
            //Aksjesparekonto. TODO Fix. Kun skatt ved salg??? Ikke årlig
            $cashflowTaxAmount = 0; //Ikke årlig skatt på ASK
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'otp') {
            //Pensjonssparing fra arbeidsgiver
            $cashflowTaxAmount = 0; //Ikke årlig skatt på ASK
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'ips') {
            //Pensjonssparing fra arbeidsgiver
            $cashflowTaxAmount = 0; //Ikke årlig skatt på ASK
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'bank') {
            //ToDo: Man skal bare betale skatt av rentene
            $cashflowTaxAmount = $income * $cashflowTaxPercent; //ToDO FIX
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxtype == 'cash') {
            //ToDo: Man skal bare betale skatt av rentene
            $cashflowTaxAmount = $income * $cashflowTaxPercent; //ToDO FIX
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } else {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;
        }

        if ($debug) {
            echo "$taxtype.$year: cashflow: $cashflow, cashflowTaxAmount: $cashflowTaxAmount, cashflowTaxPercent: $cashflowTaxPercent, potentialIncomeAmount: $potentialIncomeAmount\n";
        }
        $cashflowBeforeTaxAmount = $income - $expence;

        //V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        return [$cashflowTaxAmount, $cashflowTaxPercent];
    }

    public function taxCalculationRealization(bool $debug, bool $transfer, string $taxtype, int $year, float $amount, float $acquisitionAmount = 0, float $assetDiffAmount, float $taxShieldPrevAmount = 0, ?int $acquisitionYear = 0)
    {
        $numberOfYears = $year - $acquisitionYear;

        $realizationTaxPercent = $this->getTaxRealization($taxtype, $year);
        $realizationTaxShieldPercent = $this->getTaxShieldRealization($taxtype, $year);

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
            echo "\n  taxCalculationRealizationStart $taxtype.$year: amount: $amount, acquisitionAmount: $acquisitionAmount, taxShieldPrevAmount: $taxShieldPrevAmount, acquisitionYear: $acquisitionYear, realizationTaxPercent: $realizationTaxPercent, realizationTaxShieldAmount:$realizationTaxShieldAmount, realizationTaxShieldPercent:$realizationTaxShieldPercent\n";
        }

        if ($taxtype == 'salary') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxtype == 'pension') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxtype == 'income') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxtype == 'house') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;  //Salg av eget hus er alltid skattefritt om man har bodd der minst ett år siste 2 år (regne på det?)

        } elseif ($taxtype == 'cabin') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;  //Men må ha hatt hytta mer enn 5 eller 8 år for å bli skattefritt. (regne på det?)

        } elseif ($taxtype == 'car') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxtype == 'boat') {
            $realizationTaxableAmount = 0;
            $realizationTaxAmount = 0;

        } elseif ($taxtype == 'property') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = $realizationTaxableAmount * $realizationTaxPercent;  //verdien nå minus inngangsverdien skal skattes ved salg
            }

        } elseif ($taxtype == 'rental') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien skal skattes ved salg
            }

        } elseif ($taxtype == 'stock') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien skal skattes ved salg?
            }

        } elseif ($taxtype == 'bondfund') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'equityfund') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'ask') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'otp') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'ips') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'crypto') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'gold') {
            if ($amount - $acquisitionAmount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
        } elseif ($taxtype == 'bank') {
            $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
            $realizationTaxAmount = 0;  //Ingen skatt ved salg.

        } elseif ($taxtype == 'cash') {
            $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
            $realizationTaxAmount = 0;  //Ingen skatt ved salg.

        } else {

            if ($amount > 0) {
                $realizationTaxableAmount = $amount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = round($realizationTaxableAmount * $realizationTaxPercent);  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
        }

        //Skjermingsfradrag FIX: Trekker fra skjermingsfradraget fra skatten, men usikker på om det burde vært regnet ut i en ny kolonne igjen..... Litt inkonsekvent.
        $realizationBeforeShieldTaxAmount = $realizationTaxAmount;

        if($transfer) {
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
            echo "  taxCalculationRealizationEnd   $taxtype.$year: realizationTaxableAmount: $realizationTaxableAmount, realizationBeforeShieldTaxAmount: $realizationBeforeShieldTaxAmount, realizationTaxAmount: $realizationTaxAmount, acquisitionAmount: $acquisitionAmount, realizationTaxPercent: $realizationTaxPercent, realizationTaxShieldAmount:$realizationTaxShieldAmount, realizationTaxShieldPercent:$realizationTaxShieldPercent\n";
        }

        //V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        return [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent];
    }
}
