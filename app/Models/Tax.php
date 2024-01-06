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

    public function getTaxYearly($type, $year)
    {

        return Arr::get($this->taxH, "$type.yearly", 0) / 100;
    }

    public function getTaxRealization($type, $year)
    {

        return Arr::get($this->taxH, "$type.realization", 0) / 100;
    }

    //Returnerer hvor stor del av formuen som blir skattlagt
    public function getTaxableFortune($type, $year)
    {

        return Arr::get($this->taxH, "$type.fortune", 0) / 100;
    }

    //Returnerer formuesskatten i %
    public function getFortuneTax($year)
    {

        return Arr::get($this->taxH, 'fortune.yearly', 0) / 100;
    }

    //Only run on totals. Difficult to run on separate assets as you cannot add them together.
    //ToDo different tax'es for companies?
    public function fortuneTaxGroupCalculation($group, $fortuneTaxableAmount, $year)
    {

        $fortuneTaxPercent = $this->getFortuneTax($year);

        if ($group == 'private' || $group == 'total') {
            //ToDo: Check if bunnfradrag bare er for private
            if ($fortuneTaxableAmount <= 1700000) {
                $fortuneTaxableAmount = 0; //Det betales ikke formuesskatt på skattbar formue under 1.7 mill. FIX lese fra confid, støtte årlige forskjeller. Nesten i mål. Tar år som input, men ignorerer år enn så lenge.
            } else {
                $fortuneTaxableAmount -= 1700000;
            }
        }

        $fortuneTaxAmount = $fortuneTaxableAmount * $fortuneTaxPercent; //Calculate the tax you shall pay from the taxable fortune

        return [$fortuneTaxAmount, $fortuneTaxPercent];
    }

    public function taxCalculationFortune(string $taxtype, int $year, ?int $amount = 0, ?int $taxAmount = 0)
    {

        $fortuneTaxPercent = $this->getFortuneTax($year);
        $fortuneTaxablePercent = $this->getTaxableFortune($taxtype, $year);

        if ($taxAmount > 0) {
            $fortuneTaxableAmount = $taxAmount; //If the taxable fortune is different than the marketprice, we adjust it accordingly
        } else {
            $fortuneTaxableAmount = $amount * $fortuneTaxablePercent; //Calculate the amount from wich the tax is calculated from the market value
        }
        $fortuneTaxAmount = $fortuneTaxableAmount * $fortuneTaxPercent; //Calculate the tax you shall pay from the taxable fortune

        //print "$AmountTaxableFortune, $fortuneTaxAmount, $fortuneTaxPercent\n";

        return [$fortuneTaxableAmount, $fortuneTaxAmount, $fortuneTaxablePercent, $fortuneTaxPercent];
    }

    public function taxCalculationCashflow(bool $debug, string $taxtype, int $year, ?float $income, ?float $expence)
    {

        //FIX - should probably not be pairs, but the same thing.
        $cashflowTaxPercent = $this->getTaxYearly($taxtype, $year); //FIX

        //Forskjell på hva man betaler skatt av
        $cashflow = 0;
        $cashflowTaxAmount = 0;
        $potentialIncomeAmount = 0;
        if ($debug) {
            echo "\ntaxtype: $taxtype.$year: income: $income, expence: $expence, cashflowTaxPercent: $cashflowTaxPercent\n";
        }

        if ($taxtype == 'salary') {
            $cashflowTaxAmount = $income * $cashflowTaxPercent;
            $cashflow = $income - $expence - $cashflowTaxAmount;
            $potentialIncomeAmount = $income;
        } elseif ($taxtype == 'income') {
            $cashflowTaxAmount = $income * $cashflowTaxPercent;
            $cashflow = $income - $expence - $cashflowTaxAmount;
            $potentialIncomeAmount = $income;

        } elseif ($taxtype == 'house') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflow = $income - $expence - $cashflowTaxAmount;
            $potentialIncomeAmount = $income;

        } elseif ($taxtype == 'cabin') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - 10000) * $cashflowTaxPercent; //Airbnb skatten
            $cashflow = $income - $expence - $cashflowTaxAmount;
            $potentialIncomeAmount = $income; //Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

        } elseif ($taxtype == 'rental') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflow = $income - $expence - $cashflowTaxAmount;
            //$potentialIncomeAmount = (($income - $cashflowTaxAmount) / 12) * 10; #Bank beregning, ikke sunn fornuft, ikke med skatt
            $potentialIncomeAmount = $income; //Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

        } elseif ($taxtype == 'stock') {
            //Hm. Aksjer som selges skattes bare som formuesskatt og ved realisasjon
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            //NOTE: Skjermingsfradrag
            //NOTE: Stor forskjell på skattlegging mot privat 35.2%vs bedrift 0%?.
            $cashflowTaxAmount = 0;
            $cashflow = $income - $expence - $cashflowTaxAmount;
            $potentialIncomeAmount = $income - $cashflowTaxAmount;

        } elseif ($taxtype == 'fond') {
            //Hm. fond i praksis bare eid i firmaer, alt privat i ASK og skattes bare ved realisasjon + formuesskatt
            $cashflowTaxAmount = 0;

        } elseif ($taxtype == 'ask') {
            //Aksjesparekonto. TODO Fix. Kun skatt ved salg??? Ikke årlig
            $cashflowTaxAmount = 0; //Ikke årlig skatt på ASK
            $cashflow = $income - $expence - $cashflowTaxAmount;
            $potentialIncomeAmount = $income;

        } elseif ($taxtype == 'cash') {
            //ToDo: Man skal bare betale skatt av rentene
            $cashflowTaxAmount = $income * $cashflowTaxPercent; //ToDO FIX
            $cashflow = $income - $expence - $cashflowTaxAmount;
            $potentialIncomeAmount = $income - $cashflowTaxAmount;

        } else {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflow = $income - $expence - $cashflowTaxAmount;
            $potentialIncomeAmount = 0;  //For nå antar vi ingen inntekt fra annet enn lønn eller utleie, men utbytte vil også telle.
        }

        if ($debug) {
            echo "$taxtype.$year: cashflow: $cashflow, cashflowTaxAmount: $cashflowTaxAmount, cashflowTaxPercent: $cashflowTaxPercent, potentialIncomeAmount: $potentialIncomeAmount\n";
        }

        //V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        return [$cashflow, $cashflowTaxAmount, $cashflowTaxPercent, $potentialIncomeAmount];
    }

    public function taxCalculationRealization(bool $debug, string $taxtype, int $year, int $assetMarketAmount, int $acquisitionAmount = 0, ?int $acquisitionYear = 0)
    {

        //FIX - should probably not be pairs, but the same thing.
        $realizationTaxPercent = $this->getTaxRealization($taxtype, $year); //FIX
        $realizationDeductablePercent = $this->getTaxRealization($taxtype, $year); //FIX

        //Forskjell på hva man betaler skatt av
        $realizationTaxableAmount = 0; //The amount to pay tax from. Often calculated as taxof(MarketAmount - acquisitionAmount). We assume we always sell to market value
        $realizationTaxAmount = 0;
        $numberOfYears = $year - $acquisitionYear;

        if ($debug) {
            echo "\n$taxtype.$year: assetMarketAmount: $assetMarketAmount, acquisitionYear: $acquisitionYear, realizationTaxPercent: $realizationTaxPercent\n";
        }

        if ($taxtype == 'salary') {
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

        } elseif ($taxtype == 'rental') {
            if ($assetMarketAmount > 0) {
                $realizationTaxableAmount = $assetMarketAmount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = $realizationTaxableAmount * $realizationTaxPercent;  //verdien nå minus inngangsverdien skal skattes ved salg
            }

        } elseif ($taxtype == 'stock') {
            if ($assetMarketAmount > 0) {
                $realizationTaxableAmount = $assetMarketAmount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = $realizationTaxableAmount * $realizationTaxPercent;  //verdien nå minus inngangsverdien skal skattes ved salg?
            }

        } elseif ($taxtype == 'fond') {
            if ($assetMarketAmount > 0) {
                $realizationTaxableAmount = $assetMarketAmount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = $realizationTaxableAmount * $realizationTaxPercent;  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'ask') {
            if ($assetMarketAmount > 0) {
                $realizationTaxableAmount = $assetMarketAmount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = $realizationTaxableAmount * $realizationTaxPercent;  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'cash') {
            $realizationTaxableAmount = $assetMarketAmount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
            $realizationTaxAmount = 0;  //Ingen skatt ved salg.

        } else {

            if ($assetMarketAmount > 0) {
                $realizationTaxableAmount = $assetMarketAmount - $acquisitionAmount;  //verdien nå minus inngangsverdien skal skattes ved salg
                $realizationTaxAmount = $realizationTaxableAmount * $realizationTaxPercent;  //verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
        }

        if ($debug) {
            echo "$taxtype.$year: realizationTaxableAmount: $realizationTaxableAmount, realizationTaxAmount: $realizationTaxAmount, realizationTaxPercent: $realizationTaxPercent\n";
        }

        //V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        return [$realizationTaxableAmount, $realizationTaxAmount, $realizationTaxPercent];
    }
}
