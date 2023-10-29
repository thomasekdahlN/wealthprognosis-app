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

    #Will be rewritten to support yearly tax differences, just faking for now.
    #Should probably be a deep nested json structure.
    public function __construct($config, $startYear, $stopYear)
    {

        $file = "tax/$config.json";
        $configH = json_decode(Storage::disk('local')->get($file), true);
        print "Leser: '$file'\n";

        foreach($configH as $type => $typeH) {
            $prevTaxH = [];
            #for ($year = $startYear; $year <= $stopYear; $year++) {
                #print "$type.$year = " . Arr::get($configH, "$type.$year", null) . "\n";

                #$taxH = Arr::get($configH, "$type.$year", null);
                #if(isset($taxH)) {
                #    $prevTaxH = $taxH;
                #} else {
                #    $taxH = $prevTaxH;
                #}
                $this->taxH[$type] = $typeH;
            #}
        }
        #dd( $this->taxH);
    }

    public function getTaxYearly($type, $year) {

        return Arr::get($this->taxH, "$type.yearly", 0) / 100;
    }

    public function getTaxRealization($type, $year) {

        return Arr::get($this->taxH, "$type.realization", 0) / 100;
    }

    #Returnerer hvor stor del av formuen som blir skattlagt
    public function getTaxableFortune($type, $year) {

        return Arr::get($this->taxH, "$type.fortune", 0) / 100;
    }

    #Returnerer formuesskatten i %
    public function getFortuneTax($year) {

        return Arr::get($this->taxH, "fortune.yearly", 0) / 100;
    }

    #Only run on totals. Difficult to run on separate assets as you cannot add them together.
    #ToDo different tax'es for companies?
    public function fortuneTaxGroupCalculation($group, $fortuneTaxableAmount, $year) {

        $fortuneTaxPercent = $this->getFortuneTax($year);

        if($group == 'private' || $group == 'total' ) {
            #ToDo: Check if bunnfradrag bare er for private
            if ($fortuneTaxableAmount <= 1700000) {
                $fortuneTaxableAmount = 0; #Det betales ikke formuesskatt på skattbar formue under 1.7 mill. FIX lese fra confid, støtte årlige forskjeller. Nesten i mål. Tar år som input, men ignorerer år enn så lenge.
            } else {
                $fortuneTaxableAmount -= 1700000;
            }
        }

        $fortuneTaxAmount = $fortuneTaxableAmount * $fortuneTaxPercent; #Calculate the tax you shall pay from the taxable fortune

        return [$fortuneTaxAmount, $fortuneTaxPercent];
    }

    public function fortuneTaxTypeCalculation(string $taxtype, int $year, ?int $amount = 0, ?int $taxAmount = 0)
    {

        $fortuneTaxPercent = $this->getFortuneTax($year);
        $fortuneTaxablePercent = $this->getTaxableFortune($taxtype, $year);

        if ($taxAmount > 0) {
            $fortuneTaxableAmount = $taxAmount; #If the taxable fortune is different than the marketprice, we adjust it accordingly
        } else {
            $fortuneTaxableAmount = $amount * $fortuneTaxablePercent; #Calculate the amount from wich the tax is calculated from the market value
        }
        $fortuneTaxAmount = $fortuneTaxableAmount * $fortuneTaxPercent; #Calculate the tax you shall pay from the taxable fortune

        #print "$AmountTaxableFortune, $fortuneTaxAmount, $fortuneTaxPercent\n";

        return [$fortuneTaxableAmount, $fortuneTaxAmount, $fortuneTaxablePercent, $fortuneTaxPercent];
    }

    public function taxCalculation(bool $debug = false, string $taxtype, int $year, ?int $income, ?int $expence, ?int $assetAmount, ?int $taxAmount = 0, ?int $firstAssetAmount = 0, ?int $firstAssetYear = 0) {

        $PercentCashflowTaxableYearly = $this->getTaxYearly($taxtype, $year);
        $PercentCashflowTaxableRealization = $this->getTaxRealization($taxtype, $year);
        $PercentCashflowDeductableYearly = $this->getTaxYearly($taxtype, $year);
        $PercentCashflowDeductableRealization = $this->getTaxRealization($taxtype, $year);


        #Forskjell på hva man betaler skatt av
        $cashflow = 0;
        $potentialIncome = 0;
        $CashflowTaxableAmount = 0;
        $FortuneTaxableYearly = 0;
        $AmountTaxableRealization = 0;
        $AmountDeductableYearly = 0;
        $AmountDeductableRealization = 0;
        $numberOfYears = $year - $firstAssetYear;

        if($debug) {
            print "\n$taxtype.$year: income: $income, expence: $expence, assetAmount: $assetAmount,  taxAmount: $taxAmount, firstAssetYear: $firstAssetYear, PercentTaxableYearly: $PercentCashflowTaxableYearly, PercentTaxableRealization: $PercentCashflowTaxableRealization,PercentDeductableYearly: $PercentCashflowDeductableYearly, PercentDeductableRealization: $PercentCashflowDeductableRealization \n";
        }

        if ($taxtype == 'salary') {
            $CashflowTaxableAmount = $income * $PercentCashflowTaxableYearly;
            $AmountTaxableRealization = 0;
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            $potentialIncome = $income;
        }
        elseif ($taxtype == 'income') {
            $CashflowTaxableAmount = $income * $PercentCashflowTaxableYearly;
            $AmountTaxableRealization = 0;
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            $potentialIncome = $income;

        } elseif ($taxtype == 'house') {
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $CashflowTaxableAmount = ($income - $expence) * $PercentCashflowTaxableYearly;
            $AmountTaxableRealization = 0;  #Salg av eget hus er alltid skattefritt om man har bodd der minst ett år siste 2 år (regne på det?)
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            $potentialIncome = $income;

        } elseif ($taxtype == 'cabin') {
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $CashflowTaxableAmount = ($income - 10000) * $PercentCashflowTaxableYearly; #Airbnb skatten
            $AmountTaxableRealization = 0;  #Men må ha hatt hytta mer enn 5 eller 8 år for å bli skattefritt. (regne på det?)
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            $potentialIncome = $income; #Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

        } elseif ($taxtype == 'rental') {
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $CashflowTaxableAmount = ($income - $expence) * $PercentCashflowTaxableYearly;
            if($assetAmount > 0) {
                $AmountTaxableRealization = ($assetAmount - $firstAssetAmount) * $PercentCashflowTaxableRealization;  #verdien nå minus inngangsverdien skal skattes ved salg
            }
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            #$potentialIncome = (($income - $CashflowTaxableAmount) / 12) * 10; #Bank beregning, ikke sunn fornuft, ikke med skatt
            $potentialIncome = $income; #Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

        } elseif ($taxtype == 'stock') {
            #Hm. Aksjer som selges skattes bare som formuesskatt og ved realisasjon
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            #NOTE: Skjermingsfradrag
            #NOTE: Stor forskjell på skattlegging mot privat 35.2%vs bedrift 0%?.
            $CashflowTaxableAmount = 0;
            if($assetAmount > 0) {
                $AmountTaxableRealization = ($assetAmount - $firstAssetAmount) * $PercentCashflowTaxableRealization;  #verdien nå minus inngangsverdien skal skattes ved salg?
            }
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            $potentialIncome = $income - $CashflowTaxableAmount;

        } elseif ($taxtype == 'fond') {
            #Hm. fond i praksis bare eid i firmaer, alt privat i ASK og skattes bare ved realisasjon + formuesskatt
            $CashflowTaxableAmount = 0;
            if($assetAmount > 0) {
                $AmountTaxableRealization = ($assetAmount - $firstAssetAmount) * $PercentCashflowTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }

        } elseif ($taxtype == 'ask') {
            #Aksjesparekonto. TODO Fix. Kun skatt ved salg??? Ikke årlig
            $CashflowTaxableAmount = 0; #Ikke årlig skatt på ASK
            if($assetAmount > 0) {
                $AmountTaxableRealization = ($assetAmount - $firstAssetAmount) * $PercentCashflowTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            $potentialIncome = $income;


        } elseif ($taxtype == 'cash') {
            #ToDo: Man skal bare betale skatt av rentene
            $CashflowTaxableAmount = $income * $PercentCashflowTaxableYearly; #ToDO FIX
            $AmountTaxableRealization = 0;  #Ingen skatt ved salg.
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            $potentialIncome = $income - $CashflowTaxableAmount;

        } else {
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $CashflowTaxableAmount = ($income - $expence) * $PercentCashflowTaxableYearly;
            if($assetAmount > 0) {
                $AmountTaxableRealization = ($assetAmount - $firstAssetAmount) * $PercentCashflowTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
            $cashflow = $income - $expence - $CashflowTaxableAmount + $AmountDeductableYearly;
            $potentialIncome = 0;  #For nå antar vi ingen inntekt fra annet enn lønn eller utleie, men utbytte vil også telle.
        }

        ################################################################################################################
        #Beregning av formuesskatt
        if($debug) {
            print "Before fortuneTaxTypeCalculation($taxtype, $assetAmount, $taxAmount, $year)\n";
        }
        list($fortuneTaxableAmount, $fortuneTaxAmount, $fortuneTaxablePercent, $fortuneTaxPercent) = $this->fortuneTaxTypeCalculation($taxtype, $year, $assetAmount, $taxAmount);

        #Vi må trekke fra formuesskatten fra cashflow
        $cashflow -= $fortuneTaxAmount;

        if($debug) {
            print "$taxtype.$year: cashflow: $cashflow, potentialIncome: $potentialIncome, CashflowTaxableYearly: $CashflowTaxableAmount, fortuneTaxableAmount: $fortuneTaxableAmount, fortuneTaxAmount: $fortuneTaxAmount, fortuneTaxablePercent: $fortuneTaxablePercent, fortuneTaxPercent: $fortuneTaxPercent, AmountTaxableRealization: $AmountTaxableRealization, AmountDeductableYearly: $AmountDeductableYearly, AmountDeductableRealization: $AmountDeductableRealization\n";
        }

        #V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        return [$cashflow, $potentialIncome, $CashflowTaxableAmount, $fortuneTaxableAmount, $fortuneTaxAmount, $fortuneTaxablePercent, $fortuneTaxPercent, $AmountTaxableRealization, $AmountDeductableYearly, $AmountDeductableRealization];
    }
}
