<?php

//Asset,
//Mortgage,
//CashFlow



namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prognosis
{
    use HasFactory;
    public $periodStart;
    public $periodEnd;
    public $config;
    public $dataH = array();
    public $assetH = array();
    public $totalH = array();
    public $groupH = array();
    public $privateH = array();
    public $companyH = array();


    public function __construct($config)
    {
        $this->config = $config;

        $this->periodStart = (integer) Arr::get($this->config, 'period.start');
        $this->periodEnd  = (integer) Arr::get($this->config, 'period.end');

        foreach(Arr::get($this->config, 'assets') as $assetname => $asset) {

            #Store all metadata about the asset, the rest is yearly calculations
            $this->dataH[$assetname]['meta'] = $meta = $asset['meta'];
            if(!$meta['active']) continue; #Hopp over de inaktive
            $taxtype = Arr::get($meta, "tax", null);
            $PercentTaxableYearly = Arr::get($this->config, "tax." . $taxtype. ".yearly", 0) / 100;
            $PercentTaxableRealization = Arr::get($this->config, "tax." . $taxtype. ".realization", 0) / 100;
            $PercentDeductableYearly = Arr::get($this->config, "tax." . $taxtype. ".yearly", 0) / 100;
            $PercentDeductableRealization = Arr::get($this->config, "tax." . $taxtype. ".realization", 0) / 100;


            print "$assetname: $taxtype: PercentTaxableYearly: $PercentTaxableYearly, PercentTaxableRealization: $PercentTaxableRealization\n";

            $assetValue = 0;
            $firstAssetValue = 0;
            $prevAssetValue = 0;
            $assetChangerate = 1;
            $prevAssetChangerate = 1;
            $prevAssetRepeat = false;
            $assetRepeat = false;

            $income = 0;
            $prevIncome = 0;
            $incomeChangerate = 1;
            $prevIncomeChangerate = 1;
            $prevIncomeRepeat = false;
            $incomeRepeat = false;

            $expence = 0;
            $prevExpence = 0;
            $expenceChangerate = 1;
            $prevExpenceChangerate = 1;
            $prevExpenceRepeat = false;
            $expenceRepeat = false;

            $rest = 0;
            $restAccumulated = 0;

            for ($year = $this->periodStart; $year <= $this->periodEnd; $year++) {

                #####################################################
                #expence

                $expenceRepeat = Arr::get($asset, "expence.$year.repeat", null);
                if(isset($expenceRepeat)) {
                    $prevExpenceRepeat = $expenceRepeat;
                } else {
                    $expenceRepeat = $prevExpenceRepeat;
                }

                $expenceChangerate = Arr::get($asset, "expence.$year.changerate");
                if($expenceChangerate) {
                    $prevExpenceChangerate = $expenceChangerate;
                } else {
                    $expenceChangerate = $prevExpenceChangerate;
                }

                $expence = Arr::get($asset, "expence.$year.value", 0);
                if(!$expence) {
                    $expence = $prevExpence;
                }

                $this->dataH[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangerate,
                    'amount' => $expence,
                    'description' => Arr::get($asset, "expence.$year.description"),
                ];

                #####################################################
                #income
                $incomeRepeat = Arr::get($asset, "income.$year.repeat", null);
                if(isset($incomeRepeat)) {
                    $prevIncomeRepeat = $incomeRepeat;
                } else {
                    $incomeRepeat = $prevIncomeRepeat;
                }

                $incomeChangerate = Arr::get($asset, "income.$year.changerate");
                if($incomeChangerate) {
                    $prevIncomeChangerate = $incomeChangerate;
                } else {
                    $incomeChangerate = $prevIncomeChangerate;
                }

                $income = Arr::get($asset, "income.$year.value", 0);
                if(!$income) {
                    $income = $prevIncome;
                }

                $this->dataH[$assetname][$year]['income'] = [
                    'changerate' => $incomeChangerate,
                    'amount' => $income,
                    'description' => Arr::get($asset, "income.$year.description"),
                    ];

                #####################################################
                #Assett
                $assetRepeat = Arr::get($asset, "value.$year.repeat", null);
                if(isset($assetRepeat)) {
                    $prevAssetRepeat = $assetRepeat;
                } else {
                    $assetRepeat = $prevAssetRepeat;
                }

                $assetChangerate = Arr::get($asset, "value.$year.changerate");
                if($assetChangerate) {
                    $prevAssetChangerate = $assetChangerate;
                } else {
                    $assetChangerate = $prevAssetChangerate;
                }

                #print_r(Arr::get($asset, "value.$year.increase"));

                $assetValue = Arr::get($asset, "value.$year.value");
                if(!$assetValue) {
                    $assetValue = $prevAssetValue;
                } elseif(!$firstAssetValue) {
                    $firstAssetValue = $assetValue; #Remember the first assetvalue, used for tax calculations on realization
                }

                #print "$assetValue = $prevAssetValue = $assetValue * $assetChangerate\n";

                #print_r(Arr::get($asset, "assets.0.value.$year"));
                #exit;
                   $this->dataH[$assetname][$year]['asset'] = [
                        'amount' => $assetValue,
                        'amountLoanDeducted' => $assetValue,
                        'changerate' => $assetChangerate,
                        'description' => Arr::get($asset, "value.$year.description"),
                    ];

                ########################################################################################################
                if($income or $expence) {

                    $AmountDeductableYearly = 0; #Fratrekk klarer vi først når vi beregner lån
                    $AmountDeductableRealization = 0; #Fratrekk klarer vi først når vi beregner lån

                    #Forskjell på hva man betaler skatt av
                    if($taxtype == 'salary') {
                        $AmountTaxableYearly        = $income * $PercentTaxableYearly;
                        $AmountTaxableRealization   = ($assetValue - 0) * $PercentTaxableRealization;
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                    } elseif($taxtype == 'rental' || $taxtype == 'house') {
                        #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                        $AmountTaxableYearly        = ($income - $expence) * $PercentTaxableYearly;
                        $AmountTaxableRealization   = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                    } else {
                        #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                        $AmountTaxableYearly        = ($income - $expence) * $PercentTaxableYearly;
                        $AmountTaxableRealization   = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                    }
                    $restAccumulated += $cashflow;

                    $this->dataH[$assetname][$year]['cashflow'] = [
                        'amount' => $cashflow,
                        'amountAccumulated' => $restAccumulated,
                    ];

                    $this->dataH[$assetname][$year]['tax'] = [
                        'amountTaxableYearly' => -$AmountTaxableYearly,
                        'percentTaxableYearly' => $PercentTaxableYearly,
                        'amountDeductableYearly' => -$AmountDeductableYearly,
                        'percentDeductableYearly' => $PercentDeductableYearly,
                        'amountTaxableRealization' => -$AmountTaxableRealization,
                        'percentTaxableRealization' => $PercentTaxableRealization,
                        'amountDeductableRealization' => -$AmountDeductableRealization,
                        'percentDeductableRealization' => $PercentDeductableRealization,
                    ];

                    #print "i:$income - e:$expence, rest: $rest, restAccumulated: $restAccumulated\n";

                    #print_r($this->dataH[$assetname][$year]['cashflow']);

                }

                ########################################################################################################
                if($expenceRepeat) {
                    $expence    = $prevExpence      = $expence * $expenceChangerate;
                } else {
                    $expence    = $prevExpence      = null;
                }

                if($incomeRepeat) {
                    $income     = $prevIncome       = $income * $incomeChangerate;
                } else {
                    $income     = $prevIncome       = null;
                }

                if($assetRepeat) {
                    $assetValue = $prevAssetValue   = $assetValue * $assetChangerate;
                } else {
                    $assetValue = $prevAssetValue   = null;
                }
            }

            #####################################################
            #Loan
            //$this->collections = $this->collections->keyBy('year');
            #dd($this->dataH);

            $mortgage = Arr::get($asset, "mortgage", false);

            #print_r($mortgage);
            if($mortgage) {
                #Kjører bare dette om mortgage strukturen i json er utfylt
                $this->dataH = (new Amortization($this->dataH, $mortgage, $assetname))->get();
                #$this->dataH = new Amortization($this->dataH, $mortgage, $assetname);

                #dd($this->dataH);
            }

            //return $this->collections; #??????

        } #End loop over assets

        #print_r($this->dataH);
        $this->group();
    }

    public function add($assettname, $year, $type, $dataH){
        #$this->dataH[$assettname][$year][$type] = $dataH;
    }

    function group() {
        #dd($this->dataH);
        foreach($this->dataH as $assetname => $assetH) {
            if(!$assetH['meta']['active']) continue; #Hopp over de inaktive

            for ($year = $this->periodStart; $year <= $this->periodEnd; $year++) {
                $this->calculate($year, $assetH['meta'], $assetH[$year], "asset.amount");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "asset.amountLoanDeducted");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "income.amount");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "expence.amount");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "tax.amountTaxableYearly");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "tax.amountTaxableRealization");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "tax.amountDeductableYearly");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "tax.amountDeductableRealization");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "cashflow.amount");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "cashflow.amountAccumulated");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.payment");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.paymentExtra");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.interestAmount");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.principal");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.balance");
            }
        }
        #print "group\n";
        #print_r($this->groupH);
    }

    function calculate($year, $meta, $data, $dotpath) {

        if(Arr::get($data, $dotpath)) {

            #Just to create an empty object, if it has no values.
            Arr::set($this->totalH, "$year.$dotpath", Arr::get($this->totalH, "$year.$dotpath", 0) + Arr::get($data, $dotpath));

            #Company
            if (Arr::get($meta, 'group') == 'company') {
                Arr::set($this->companyH, "total.$year.$dotpath", Arr::get($this->companyH, "$year.$dotpath", 0) + Arr::get($data, $dotpath));
            }

            #Private
            if (Arr::get($meta, 'group') == 'private') {
                Arr::set($this->privateH, "total.$year.$dotpath", Arr::get($this->privateH, "$year.$dotpath", 0) + Arr::get($data, $dotpath));
            }

            #Grouping
            $grouppath = Arr::get($meta, 'group') . ".$year.$dotpath";
            $typepath = Arr::get($meta, 'type') . ".$year.$dotpath";
            Arr::set($this->groupH, $grouppath, Arr::get($this->groupH, $grouppath, 0) + Arr::get($data, $dotpath));
            Arr::set($this->groupH, $typepath, Arr::get($this->groupH, $typepath, 0) + Arr::get($data, $dotpath));
        }
    }
}
