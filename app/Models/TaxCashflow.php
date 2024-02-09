<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TaxCashflow extends Model
{
    use HasFactory;

    public $taxH = [];

    //Will be rewritten to support yearly tax differences, just faking for now.
    //Should probably be a deep nested json structure.
    public function __construct($config, $startYear, $stopYear)
    {

        $file = config_path("tax/$config.json");
        $configH = File::json($file);
        echo "Leser: '$file'\n";

        foreach ($configH as $type => $typeH) {
            $this->taxH[$type] = $typeH;
        }
    }

    public function getTaxYearly($taxGroup, $taxType, $year)
    {

        return Arr::get($this->taxH, "$taxType.yearly", 0) / 100;
    }

    public function taxCalculationCashflow(bool $debug, string $taxGroup, string $taxType, int $year, ?float $income, ?float $expence)
    {

        //FIX - should probably not be pairs, but the same thing.
        $cashflowTaxPercent = $this->getTaxYearly($taxGroup, $taxType, $year); //FIX

        //Forskjell på hva man betaler skatt av
        $cashflowBeforeTaxAmount = 0;
        $cashflowAfterTaxAmount = 0;

        $cashflowTaxAmount = 0;

        if ($debug) {
            echo "\ntaxtype: $taxGroup.$taxType.$year: income: $income, expence: $expence, cashflowTaxPercent: $cashflowTaxPercent\n";
        }

        if ($taxType == 'salary') {
            $cashflowTaxAmount = $income * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'pension') {
            $cashflowTaxAmount = $income * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'income') {
            $cashflowTaxAmount = $income * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'house') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'cabin') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - 10000) * $cashflowTaxPercent; //Airbnb skatten
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'rental') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'property') {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'stock') {
            //Hm. Aksjer som selges skattes bare som formuesskatt og ved realisasjon
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            //FIX: Skjermingsfradrag
            //FIX: Stor forskjell på skattlegging mot privat 35.2%vs bedrift 0%?.
            $cashflowTaxAmount = 0;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'bondfund') {
            //Hm. bondfund i praksis bare eid i firmaer, alt privat i ASK og skattes bare ved realisasjon + formuesskatt
            $cashflowTaxAmount = 0;

        } elseif ($taxType == 'equityfund') {
            //Hm. equityfund i praksis bare eid i firmaer, alt privat i ASK og skattes bare ved realisasjon + formuesskatt
            $cashflowTaxAmount = 0;

        } elseif ($taxType == 'ask') {
            //Aksjesparekonto. TODO Fix. Kun skatt ved salg??? Ikke årlig
            $cashflowTaxAmount = 0; //Ikke årlig skatt på ASK
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'otp') {
            //Pensjonssparing fra arbeidsgiver
            $cashflowTaxAmount = 0; //Ikke årlig skatt på ASK
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'ips') {
            //Pensjonssparing fra arbeidsgiver
            $cashflowTaxAmount = 0; //Ikke årlig skatt på ASK
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'bank') {
            //ToDo: Man skal bare betale skatt av rentene
            $cashflowTaxAmount = $income * $cashflowTaxPercent; //ToDO FIX
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } elseif ($taxType == 'cash') {
            //ToDo: Man skal bare betale skatt av rentene
            $cashflowTaxAmount = $income * $cashflowTaxPercent; //ToDO FIX
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;

        } else {
            //Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $cashflowTaxAmount = ($income - $expence) * $cashflowTaxPercent;
            $cashflowAfterTaxAmount = $income - $expence - $cashflowTaxAmount;
        }

        if ($debug) {
            echo "$taxType.$year: cashflow: $cashflow, cashflowTaxAmount: $cashflowTaxAmount, cashflowTaxPercent: $cashflowTaxPercent, potentialIncomeAmount: $potentialIncomeAmount\n";
        }
        $cashflowBeforeTaxAmount = $income - $expence;

        //V kan ikke kalkulere videre på $fortuneTaxableAmount fordi det er summen av skatter som er for fradrag, vi kan ikke summere på dette tallet etterpå. Bunnfradraget må alltid gjøres på total summen. Denne regner det bare ut isolert sett for en asset.
        return [$cashflowTaxAmount, $cashflowTaxPercent];
    }
}
