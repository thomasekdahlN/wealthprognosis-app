<?php

//Asset,
//Mortgage,
//CashFlow

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Prognosis
{
    use HasFactory;

    public $thisYear;

    public $economyStartYear;

    public $deathYear;

    public $config;

    public $tax;

    public $changerate;

    public $dataH = [];

    public $assetH = [];

    public $totalH = [];

    public $groupH = [];

    public $privateH = [];

    public $companyH = [];

    public $statisticsH = [];

    //FIX: Kanskje feil å regne inn otp her? Der kan man jo ikke velge.
    //Assets som man kan selge deler av hvert år for å finansiere FIRE. Huset ditt kan du f.eks ikke selge deler av. Dette brukes for å beregne potensiell inntekt fra salg av disse assets.
    public $firePartSalePossibleTypes = [
        'crypto' => true,
        'bondfund' => true,
        'equityfund' => true,
        'stock' => false,
        'otp' => true,
        'ask' => true,
        'ips' => true,
        'cash' => true,
        'bank' => true
    ];

    //Dette er de asssett typene som regnes som inntekt i FIRE. Nedbetaling av lån regnes ikke som inntekt.
    public $fireSavingTypes = [
        'house' => true,
        'rental' => true,
        'cabin' => true,
        'crypto' => true,
        'bondfund' => true,
        'equityfund' => true,
        'stock' => true,
        'otp' => true,
        'ask' => true,
        'cash' => true,
        'bank' => true,
        'ips' => true,
        'pension' => true,
    ];

    //Assetene vi viser frem i statistikken (i % av investeringene)
    public $assetSpreadTypes = [
        'boat' => true,
        'bank' => true,
        'car' => true,
        'cash' => true,
        'house' => true,
        'rental' => true,
        'cabin' => true,
        'crypto' => true,
        'bondfund' => true,
        'equityfund' => true,
        'stock' => true,
        'otp' => true,
        'ask' => true,
        'ips' => true,
        'pension' => true,
    ];

    public function __construct(array $config, object $tax, object $changerate)
    {
        //$this->test();
        $this->config = $config;
        $this->tax = $tax;
        $this->changerate = $changerate;
        $this->helper = new \App\Models\Helper();

        $this->birthYear = (int) Arr::get($this->config, 'meta.birthYear');
        $this->economyStartYear = $this->birthYear + 16; //We look at economy from 16 years of age
        $this->thisYear = now()->year;
        $this->deathYear = (int) $this->birthYear + Arr::get($this->config, 'meta.deathYear');

        foreach ($this->config as $assetname => $assetconfig) {

            if ($assetname == 'meta') {
                //print "--- Jump over meta $assetname\n";
                continue;
            } //Hopp over metadata, reserved keyword meta.
            //echo "*** Asset: $assetname\n";

            //Store all metadata about the asset, the rest is yearly calculations
            $this->dataH[$assetname]['meta'] = $this->ArrGetConfig("$assetname.meta"); //Copy metadata into dataH

            if (! $this->ArrGetConfig("$assetname.meta.active")) {
                //print "--- Asset $assetname is not active\n";
                continue;
            } //Jump past inactive assets

            $taxtype = $this->ArrGetConfig("$assetname.meta.tax"); //How tax is to be calculated for this asset

            $firsttime = false; //Only set to true on the first time we see a configuration on this asset.
            $assetMarketAmount = 0;
            $assetEquityAmount = 0;
            $assetPaidAmount = 0;
            $assetAcquisitionAmount = 0;
            $realizationTaxAmount = 0;
            $assetFirstYear = 0;
            $assetRule = null;
            $assetChangerateDecimal = 0;
            $assetChangeratePercent = 0;
            $assetChangerateAmount = '';
            $assetRepeat = false;
            $assetTransfer = null;
            $assetRule = null;
            $assetAggregatedDepositedAmount = 0;

            $incomeAmount = 0;
            $incomeChangerateDecimal = 0;
            $incomeChangeratePercent = 0;
            $incomeChangerateAmount = '';
            $incomeRule = null;
            $incomeRepeat = false;
            $incomeTransfer = null;
            $incomeRule = null;

            $expenceAmount = 0;
            $expenceTaxDeductableAmount = 0;
            $expenceChangerateDecimal = 0;
            $expenceChangeratePercent = 0;
            $expenceChangerateAmount = '';
            $expenceRule = null;
            $expenceRepeat = false;
            $expenceTransfer = null;
            $expenceRule = null;

            $restAccumulated = 0;

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

                $prevYear = $year - 1;
                $path = "$assetname.$year";
                //echo "$path\n";

                //#######################################################################################################
                //Expence
                $expenceAmount = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'amount');
                $expenceFactor = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'factor'); //We do not store this in dataH, we only use it to upscale amounts once to yearly amounts
                $expenceRule = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'rule');
                $expenceTransfer = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'transfer');
                $expenceSource = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'source');
                $expenceRepeat = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'repeat');
                $expenceChangerate = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'changerate');

                //echo "Expence adjust before: $assetname.$year, expenceAmount:$expenceAmount, expenceRule: $expenceRule\n";
                [$expenceAmount, $expenceDepositedAmount, $expenceRule, $explanation] = $this->applyRule(false, "$path.expence.amount", $expenceAmount, 0, $expenceRule, $expenceTransfer, $expenceSource, $expenceFactor);
                //echo "Expence adjust after : $assetname.$year, expenceAmount:$expenceAmount, expenceRule: $expenceRule\n";
                //print "$year: expenceChangeratePercent = $expenceChangerateDecimal - expence * $expence\n";

                [$expenceChangeratePercent, $expenceChangerateDecimal, $expenceChangerateAmount, $expenceExplanation] = $this->changerate->getChangerate(false, $expenceChangerate, $year, $expenceChangerateAmount);
                $expenceAmount = $expenceAmount * $expenceChangerateDecimal;

                //#######################################################################################################
                //Income
                $incomeAmount = $this->configOrPrevValue(false, $assetname, $year, 'income', 'amount');
                $incomeFactor = $this->configOrPrevValue(false, $assetname, $year, 'income', 'factor'); //We do not store this in dataH, we only use it to upscale amounts once to yearly amounts
                $incomeRule = $this->configOrPrevValue(false, $assetname, $year, 'income', 'rule');
                $incomeTransfer = $this->configOrPrevValue(false, $assetname, $year, 'income', 'transfer');
                $incomeSource = $this->configOrPrevValue(false, $assetname, $year, 'income', 'source');
                $incomeRepeat = $this->configOrPrevValue(false, $assetname, $year, 'income', 'repeat');
                $incomeChangerate = $this->configOrPrevValue(false, $assetname, $year, 'income', 'changerate');

                //print "Income adjust before: $assetname.$year, incomeAmount:$incomeAmount, incomeRule:$incomeRule, incomeTransfer:$incomeTransfer, incomeSource: $incomeSource, incomeRepeat: #incomeRepeat\n";
                [$incomeAmount, $incomeDepositedAmount, $incomeRule, $explanation] = $this->applyRule(false, "$path.income.amount", $incomeAmount, 0, $incomeRule, $incomeTransfer, $incomeSource, $incomeFactor);
                //print "Income adjust after: $assetname.$year, incomeAmount:$incomeAmount\n";

                [$incomeChangeratePercent, $incomeChangerateDecimal, $incomeChangerateAmount, $incomeExplanation] = $this->changerate->getChangerate(false, $incomeChangerate, $year, $incomeChangerateAmount);
                $incomeAmount = $incomeAmount * $incomeChangerateDecimal;

                //######################################################################################################
                //Mortage - has to be calculated before asset, since we use data from mortgage to calculate asset values correctly.
                $mortgage = $this->ArrGetConfig("$assetname.$year.mortgage"); //Note that Mortgage will be processed from all years frome here to the end - at once in this step. It process the entire mortage not only this year. It will be overwritten be a new mortgage config at a later year.

                if ($mortgage) {
                    //Kjører bare dette om mortgage strukturen i json er utfylt
                    $this->dataH = (new Amortization($this->config, $this->changerate, $this->dataH, $mortgage, $assetname, $year))->get();
                }

                //######################################################################################################
                //Assett
                //Finn ut om det er det første året med konfig vi har sett på denne asset, vi gjør det ved å se om det finnes noen markedsverdi for forrige år i dataH.
                $assetMarketAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'marketAmount');
                $assetAcquisitionAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'acquisitionAmount');
                $assetEquityAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'equityAmount');
                $assetPaidAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'paidAmount'); //When paid is retrieved from a config, it is often because of inheritance that you have not paid the market value.
                $assetTaxableAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'taxableAmount'); //Read from config, because taxable Amount is not related to the assetMarketAmount - typically a cabin is not taxable on a percent of the market value, but a much lower value
                $assetTaxableAmountOverride = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'taxableAmountOverride');
                $assetRule = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'rule');
                $assetTransfer = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'transfer');
                $assetSource = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'source');
                $assetRepeat = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'repeat');
                $assetChangerate = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'changerate');

                if ($this->ArrGet("$assetname.$prevYear.asset.marketAmount") <= 0 && $assetMarketAmount > 0) {
                    $assetFirstYear = $year;
                    $firsttime = true;
                //echo "\n\nFirst time: $assetname.$year\n";
                } else {
                    $firsttime = false;
                }

                [$assetChangeratePercent, $assetChangerateDecimal, $assetChangerateAmount, $assetExplanation1] = $this->changerate->getChangerate(false, $assetChangerate, $year, $assetChangerateAmount);
                //print "$year: $assetChangeratePercent%\n";

                //print "\nAsset før: $assetname.$year assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount: $assetAcquisitionAmount, assetRule:$assetRule\n";
                //FIX: Trouble sending in $assetAcquisitionAmount here, since it is recalculated in the step after.... chicken and egg problem.
                [$assetMarketAmount, $assetDiffAmount, $assetNewRule, $assetExplanation2] = $this->applyRule(false, "$path.asset.marketAmount", $assetMarketAmount, $assetAcquisitionAmount, $assetRule, $assetTransfer, $assetSource, 1);
                //print "Asset etter: $assetname.$year assetMarketAmount: $assetMarketAmount, assetAcquisitionAmount: $assetAcquisitionAmount, assetNewRule:$assetNewRule explanation: $explanation\n";

                if ($firsttime) {
                    //default values we only set on the first run

                    if ($assetAcquisitionAmount <= 0) {
                        //Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetAcquisitionAmount (not changerates), only real money.
                        $assetAcquisitionAmount = $assetMarketAmount;
                    }

                    //echo "*** $year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetAcquisitionAmount, assetEquityAmount:$assetEquityAmount, assetPaidAmount: $assetPaidAmount, assetTaxableAmount:$assetTaxableAmount\n";

                    if ($assetEquityAmount <= 0) {
                        //Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetAcquisitionAmount (not changerates), only real money.
                        $assetEquityAmount = round($assetAcquisitionAmount - $this->ArrGet("$assetname.$year.mortgage.amount"));
                        //echo "    Equity: $assetname.$year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetAcquisitionAmount, assetEquityAmount:$assetEquityAmount, assetPaidAmount: $assetPaidAmount, assetTaxableAmount:$assetTaxableAmount, termAmount: ".$this->ArrGet("$assetname.$year.mortgage.termAmount")."\n";
                    }

                    if ($assetPaidAmount <= 0) {
                        //Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetAcquisitionAmount (not changerates), only real money.
                        $assetPaidAmount = $assetEquityAmount;
                        //echo "    Paid: $assetname.$year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetAcquisitionAmount, assetEquityAmount:$assetEquityAmount, assetPaidAmount: $assetPaidAmount, assetTaxableAmount:$assetTaxableAmount, termAmount: ".$this->ArrGet("$assetname.$year.mortgage.termAmount")."\n";
                    }

                    if ($assetTaxableAmount <= 0) {
                        //Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetAcquisitionAmount (not changerates), only real money.
                        $assetTaxableAmount = $assetMarketAmount;
                    } else {
                        //Since it is set from before, we have an override situation.
                        $assetTaxableAmountOverride = true;
                    }
                } else {
                    //This happens everytime after the first time.

                    //Calculation of the changerate asset has to be done after paidAmount, equityAmount but before we calculate the Taxes.
                    $assetMarketAmount = round($assetMarketAmount * $assetChangerateDecimal);
                    $assetTaxableAmount = round(($assetTaxableAmount + $assetDiffAmount) * $assetChangerateDecimal); //FIX: Trouble with override special case destrous all marketAmounts after it is set the first time. Does not neccessarily be more taxable if you put more money into it. Special case with house/cabin/rental.
                    $assetPaidAmount += $this->ArrGet("$assetname.$year.mortgage.termAmount") + $assetDiffAmount; //Recalculated every year.
                    $assetAcquisitionAmount += $assetDiffAmount; //Add/subtract amounts that are added by rule, transfer or source. Not changerate. Recalculated every year.
                }

                //print "Asset LENGE etter: $assetname.$year assetMarketAmount: $assetMarketAmount, assetAcquisitionAmount: $assetAcquisitionAmount, assetNewRule:$assetNewRule explanation: $explanation\n\n";

                //print "$year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetAcquisitionAmount, assetEquityAmount:$assetEquityAmount, assetPaidAmount: $assetPaidAmount, assetTaxableAmount:$assetTaxableAmount, termAmount: " . $this->ArrGet("$assetname.$year.mortgage.termAmount") . "\n";

                //print "PAID: $assetname.$year.asset.paidAmount: " . $assetPaidAmount . " + curPaid: " . $this->ArrGet("$assetname.$year.asset.paidAmount") . " + prevPaid: " . $this->ArrGet("$assetname.$prevYear.asset.paidAmount") . " - assetEquityAmount: $assetEquityAmount\n";

                //#######################################################################################################
                //Asset tax calculations
                //print "$taxtype.$year incomeCurrentAmount: $incomeAmount, expenceCurrentAmount: $expenceAmount\n";
                //FIXXXX?????  $assetTaxableAmount = round($assetTaxableAmount * $assetChangerateDecimal); //We have to increase the taxable amount, but maybe it should follow another index than the asset market value. Anyway, this is quite good for now.
                [$assetTaxableAmount, $assetTaxableDecimal, $assetTaxAmount, $assetTaxDecimal, $assetTaxablePropertyAmount, $assetTaxablePropertyPercent, $assetTaxPropertyAmount, $assetTaxPropertyDecimal] = $this->tax->taxCalculationFortune($taxtype, $year, $assetMarketAmount, $assetTaxableAmount, $assetTaxableAmountOverride);

                //#######################################################################################################
                //Check if we have any transfers from the cashflow - have to do it as the last thing.
                //We have to calculate it before we can transfer from it. Could have been before asset in the sequence?
                $cashflowRule = $this->configOrPrevValue(false, $assetname, $year, 'cashflow', 'rule');
                $cashflowTransfer = $this->configOrPrevValue(false, $assetname, $year, 'cashflow', 'transfer');
                $cashflowSource = $this->configOrPrevValue(false, $assetname, $year, 'cashflow', 'source');
                $cashflowRepeat = $this->configOrPrevValue(false, $assetname, $year, 'cashflow', 'repeat');

                [$cashflowTaxAmount, $cashflowTaxPercent] = $this->tax->taxCalculationCashflow(false, $taxtype, $year, $incomeAmount, $expenceAmount);

                $cashflowBeforeTaxAmount =
                    $incomeAmount
                    - $expenceAmount;

                $cashflowAfterTaxAmount =
                    $incomeAmount
                    - $expenceAmount //cashflow basis = inntekt - utgift.
                    - $cashflowTaxAmount //Minus skatt på cashflow (Kan være både positiv og negativ)
                    - $assetTaxAmount //Minus formuesskatt
                    - $assetTaxPropertyAmount //Minus eiendomsskatt
                    - $this->ArrGet("$path.mortgage.termAmount"); //Minus terminbetaling på lånet
                +$this->ArrGet("$path.mortgage.taxDeductableAmount"); //Plus skattefradrag på renter

                $cashflowNewRule = null;
                if ($cashflowTransfer && $cashflowRule && $cashflowBeforeTaxAmount > 0) {
                    //print "  Cashflow-start: $assetname.$year, transferOrigin: $path.cashflow.afterTaxAmount, cashflowTransfer:$cashflowTransfer, cashflowRule:$cashflowRule, cashflowAfterTaxAmount: $cashflowAfterTaxAmount \n";
                    [$cashflowAfterTaxAmount, $cashflowDiffAmount, $cashflowNewRule, $cashflowExplanation] = $this->applyRule(true, "$path.cashflow.afterTaxAmount", $cashflowAfterTaxAmount, 0, $cashflowRule, $cashflowTransfer, $cashflowSource, 1);
                    print "  Cashflow-end  : $assetname.$year, cashflowAfterTaxAmount: $cashflowAfterTaxAmount, cashflowDiffAmount: $cashflowDiffAmount, cashflowRule:$cashflowRule, cashflowAfterTaxAmount: $cashflowAfterTaxAmount \n";
                    //Amounts will probably be transfered to Assets here. So need to do a new calculation.
                }

                //#######################################################################################################
                //If we sell the asset, how much money is left for us after tax? In sequence has to be after cashflow.
                $assetMarketAmount += ($this->ArrGet("$path.asset.transferedAmount") * $assetChangerateDecimal);
                $assetAcquisitionAmount += $this->ArrGet("$path.asset.transferedAmount");
                $realizationPrevTaxShieldAmount = $this->ArrGet("$assetname.$prevYear.realization.taxShieldAmount");
                [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldDecimal] = $this->tax->taxCalculationRealization(true, $taxtype, $year, $assetMarketAmount, $assetAcquisitionAmount, $realizationPrevTaxShieldAmount, $assetFirstYear);
                $realizationAmount = $assetMarketAmount - $realizationTaxAmount; //Markedspris minus skatt ved salg.

                //If a mortgage is involved, the termAmount is a part of the Paid amount, since you also paid the term amount. The amount pais is usually the same ass the $assetEquityAmount
                $assetMarketMortgageDeductedAmount = $assetMarketAmount - $this->ArrGet("$assetname.$year.mortgage.balanceAmount");

                //#######################################################################################################
                //Values that can not go negative
                if ($assetMarketAmount < 0) {
                    $assetMarketAmount = 0; //Can not be negative
                }
                if ($assetAcquisitionAmount < 0) {
                    $assetAcquisitionAmount = 0; //Can not be negative
                }
                $assetEquityAmount += $this->ArrGet("$path.asset.transferedAmount");
                if ($assetEquityAmount < 0) {
                    $assetEquityAmount = 0; //Can not be negative
                }
                $assetPaidAmount += $this->ArrGet("$path.asset.transferedAmount");
                if ($assetPaidAmount < 0) {
                    $assetPaidAmount = 0; //Can not be negative
                }
                if ($assetTaxableAmount < 0) {
                    $assetTaxableAmount = 0; //Can not be negative
                }

                //#######################################################################################################
                //Store all data in the dataH structure
                if ($incomeAmount > 0) {
                    $this->dataH[$assetname][$year]['income'] = [
                        'changerate' => $incomeChangerate,
                        'changeratePercent' => $incomeChangeratePercent,
                        'rule' => $incomeRule,
                        'transfer' => $incomeTransfer,
                        'source' => $incomeSource,
                        'repeat' => $incomeRepeat,
                        'amount' => $incomeAmount,
                        'description' => $this->ArrGetConfig("$assetname.$year.income.description").$incomeExplanation,
                    ];
                }

                if ($expenceAmount > 0) {
                    $this->dataH[$assetname][$year]['expence'] = [
                        'changerate' => $expenceChangerate,
                        'changeratePercent' => $expenceChangeratePercent,
                        'rule' => $expenceRule,
                        'transfer' => $expenceTransfer,
                        'source' => $expenceSource,
                        'repeat' => $expenceRepeat,
                        'amount' => $expenceAmount,
                        'description' => $this->ArrGetConfig("$assetname.$year.expence.description").$expenceExplanation,
                    ];
                }

                //print_r($this->dataH[$assetname][$year]['income']);
                //Fix before and after tax cashflow calculations.

                if ($assetMarketAmount > 0) {
                    $this->ArrSet("$path.asset.marketAmount", $assetMarketAmount);
                    $this->ArrSet("$path.asset.acquisitionAmount", $assetAcquisitionAmount);
                    $this->ArrSet("$path.asset.marketMortgageDeductedAmount", $assetMarketMortgageDeductedAmount);
                    $this->ArrSet("$path.asset.equityAmount", $assetEquityAmount);
                    $this->ArrSet("$path.asset.paidAmount", $assetPaidAmount);
                    $this->ArrSet("$path.asset.taxableDecimal", $assetTaxableDecimal);
                    $this->ArrSet("$path.asset.taxableAmount", $assetTaxableAmount);
                    $this->ArrSet("$path.asset.taxableAmountOverride", $assetTaxableAmountOverride);
                    $this->ArrSet("$path.asset.taxDecimal", $assetTaxDecimal);
                    $this->ArrSet("$path.asset.taxAmount", $assetTaxAmount);
                    if ($assetTaxablePropertyAmount > 0) {
                        $this->ArrSet("$path.asset.taxablePropertyDecimal", $assetTaxablePropertyPercent);
                        $this->ArrSet("$path.asset.taxablePropertyAmount", $assetTaxablePropertyAmount);
                    }
                    if ($assetTaxPropertyAmount > 0) {
                        $this->ArrSet("$path.asset.taxPropertyDecimal", $assetTaxPropertyDecimal);
                        $this->ArrSet("$path.asset.taxPropertyAmount", $assetTaxPropertyAmount);
                    }
                    $this->ArrSet("$path.asset.changerate", $assetChangerate);
                    $this->ArrSet("$path.asset.changeratePercent", $assetChangeratePercent);
                    if ($assetNewRule) {
                        $this->ArrSet("$path.asset.rule", $assetNewRule);
                    }
                    if ($assetTransfer) {
                        $this->ArrSet("$path.asset.transfer", $assetTransfer);
                    }
                    if ($assetSource) {
                        $this->ArrSet("$path.asset.source", $assetSource);
                    }
                    $this->ArrSet("$path.asset.repeat", $assetRepeat);

                    $this->ArrSet("$path.realization.taxShieldDecimal", $realizationTaxShieldDecimal);
                    $this->ArrSet("$path.realization.taxShieldAmount", $realizationTaxShieldAmount);
                    $this->ArrSet("$path.realization.amount", $realizationAmount);
                    $this->ArrSet("$path.realization.taxableAmount", $realizationTaxableAmount);
                    $this->ArrSet("$path.realization.taxAmount", $realizationTaxAmount);
                    $this->ArrSet("$path.realization.taxDecimal", $realizationTaxPercent);
                    $this->ArrSet("$path.asset.description", $this->ArrGetConfig("$assetname.$year.asset.description").$this->ArrGet("$assetname.$year.asset.description").' Asset rule:'.$assetRule.' '.$assetExplanation1.$assetExplanation2);
                }

                //Try to no process the same here as in the post processing step
                $this->ArrSet("$path.cashflow.beforeTaxAmount", $cashflowBeforeTaxAmount);
                $this->ArrSet("$path.cashflow.afterTaxAmount", $cashflowAfterTaxAmount);
                $this->ArrSet("$path.cashflow.taxAmount", $cashflowTaxAmount);
                $this->ArrSet("$path.cashflow.taxDecimal", $cashflowTaxPercent);
                $this->ArrSet("$path.cashflow.rule", $cashflowNewRule);
                $this->ArrSet("$path.cashflow.transfer", $cashflowTransfer);
                $this->ArrSet("$path.cashflow.source", $cashflowSource);
                $this->ArrSet("$path.cashflow.repeat", $cashflowRepeat);
                $this->ArrSet("$path.cashflow.description", $this->ArrGetConfig("$assetname.$year.cashflow.description"));

            } //Year loop finished here.

        } //End loop over assets

        $this->postProcess();
        $this->group();
        //print_r($this->dataH);
    }

    /**
     * rule can contain:
     * -- +10% - Adds 10% to amount
     * -- -10% - Subtracts 10% from amount
     * -- 10% - Gvies you 10% from amount - amount not changed
     * -- +1000 - Adds 1000 to amount
     * -- -1000 - Subtracts 1000 from amount
     * -- +1/10 - Adds 1 tenth of the amount yearly
     * -- -1/10 - Subtracts 1 tenth of the amount yearly
     * -- +1|10 - Adds 1 tenth of the amount yearly, and subtracts nevner with one(so next value is 1/9, then 1/8, 1/7 etc)
     * -- -1|10 - Subtracts 1 tenth of the amount yearly. Then subtracts nevner with one. (so next value is 1/9, then 1/8, 1/7 etc). Perfect for usage to i.e empty an asset over 10 years.
     *
     * @path string - The path to what triggered this function. The origin.
     */
    public function applyRule(bool $debug, string $transferOrigin, float $amount, float $acquisitionAmount, ?string $rule, ?string $transferTo, ?string $source, int $factor = 1)
    {
        //Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor should have memory.

        [$originAssetname, $originYear, $originType, $originField] = $this->helper->pathToElements($transferOrigin);
        $transferedOriginAmount = "$originAssetname.$originYear.$originType.transferedAmount";

        //print "  transferOrigin: $originAssetname.$originYear.$originType.acquisitionAmount: $acquisitionAmount\n";

        $newAmount = 0;
        $diffAmount = 0;
        $explanation = '';

        if (! $factor) {
            $factor = 1;
        }

        $transferTo = str_replace(
            ['$year'],
            [$originYear],
            $transferTo);

        $source = str_replace(
            ['$year'],
            [$originYear],
            $source);

        //This is really just a fixed number, but it can appear at the same time as a rule.
        if (is_numeric($amount) && $amount != 0) {
            $explanation = 'Using current amount: '.round($amount)." * $factor";
            $amount = $calculatedNumericAmount = round($amount * $factor);
            //This is not a deposit
        }

        if ($debug) {
            echo "    applyRule INPUT($originYear, amount: $amount, rule: $rule, transfer: $transferTo, source: $source factor: $factor)\n";
        }

        //##############################################################################################################
        //Transfer value to another asset, has to update the datastructure of this asset directly
        if ($transferTo) {

            //$debug = true;

            if ($rule) {
                [$newAmount, $diffAmount, $rule, $explanation] = $this->helper->calculateRule($debug, $amount, $acquisitionAmount, $rule, $factor);
            }

            //Have to switch signs on $diffAmount
            $transferAmount = -$diffAmount;

            if ($transferAmount > 0) {
                $this->transfer($debug, $transferOrigin, $transferTo, $transferAmount, $acquisitionAmount);
                //$calculatedAmount = $amount - $newAmount; //Removes the transferred amount from this asset.
                //$acquisitionAmount = round($newAmount - $amount);
            }
        } elseif ($source && $rule) {
            //If we are not transfering the values to another resoruce, then we are adding it to the current resource
            //Do not run calculateRule here since it changes the rule, and are run in the sub procedure
            //###########################################################################################################

            [$diffAmount, $explanation] = $this->source($debug, $source, $rule);
            $newAmount = $amount + $diffAmount;

            $this->ArrSet($transferedOriginAmount, Arr::get($this->dataH, $transferedOriginAmount, 0) + $diffAmount); //The amount we transfered to - for later reference and calculation

        } else {
            //No transfers or sourcing involved, just apply the rule to the amount if it exists
            if ($rule) {
                if ($debug) {
                    echo "  Normal rule\n";
                }
                [$newAmount, $diffAmount, $rule, $explanation] = $this->helper->calculateRule($debug, $amount, $acquisitionAmount, $rule, $factor);
                $this->ArrSet($transferedOriginAmount, Arr::get($this->dataH, $transferedOriginAmount, 0) + $diffAmount); //The amount we transfered to - for later reference and calculation

            } else {
                //No changes here
                $newAmount = $amount;
                $diffAmount = 0;
                $rule = '';
            }
        }

        if ($debug) {
            echo "    applyRule OUTPUT(newAmount: $newAmount, diffAmount: $diffAmount, rule: $rule, explanation: $explanation)\n";
        }

        //print "return amountAdjustment($newAmount, $rule, $explanation)\n";
        return [$newAmount, $diffAmount, $rule, $explanation]; //Rule is adjusted if it is a divisor, it has to be remembered to the next round
    }

    //Transferes the amount to another asset. This actualle has to change variables like assetEquityAmount, assetPaidAmount, realizationShieldAmount etc. Others are only simulations, not happening.
    public function transfer(bool $debug, string $transferOrigin, string $transferTo, float $amount, float $acquisitionAmount = 0)
    {

        $realizationTaxableAmount = 0;
        $realizationTaxAmount = 0;
        $taxShieldPrevAmount = 0;
        $realizationTaxPercent = 0;

        [$originAssetname, $originYear, $originType, $originField] = $this->helper->pathToElements($transferOrigin);

        $paidAmount = 0;
        $explanation = " transfer $amount to $transferTo";
        if ($debug) {
            echo "Transferto before: $transferTo: ".Arr::get($this->dataH, $transferTo, 0)."\n";
        }

        [$toAssetname, $toYear, $toType, $toField] = $this->helper->pathToElements($transferTo);
        $transferedToAmount = "$toAssetname.$toYear.$toType.transferedAmount";
        $transferedToDescription = "$toAssetname.$toYear.$toType.description";

        $transferedOriginAmount = "$originAssetname.$originYear.$originType.transferedAmount";

        //Realisation tax calculations here, because we have to realize a transfered asset.
        [$taxAssetname, $taxYear, $taxType] = $this->getAssetMetaFromPath($transferOrigin, 'tax');
        //print "    Tax asset: $taxAssetname, year: $taxYear, type: $taxType\n";

        if ($originType == 'asset') {
            //It is only calculated tax when realizing assets, not when transfering to an asset (buying)
            [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmount, $realizationTaxShieldPercent] = $this->tax->taxCalculationRealization(false, $taxType, $originYear, $amount, $acquisitionAmount, $taxShieldPrevAmount, $originYear);
        } else {
            //It is probably income, expence or cashflow transfered to an asset. No tax calculations needed.
        }

        //print "    Realization amount: $amount, acquisitionAmount: $acquisitionAmount, realizationTaxableAmount: $realizationTaxableAmount, realizationTaxAmount: $realizationTaxAmount, realizationTaxPercent: $realizationTaxPercent\n";

        $transferedAmount = $amount - $realizationTaxAmount; //The amnount we transfer minus the realizationTax

        //The transfer happens here.
        $this->ArrSet($transferTo, $this->ArrGet($transferTo) + $transferedAmount); //Changes asset value. The real transfer from this asset to another takes place here, it is added to the already existing amount on the other asset
        $this->ArrSet($transferedToAmount, $this->ArrGet($transferedToAmount) + $transferedAmount); //The amount we transfered to - for later reference and calculation
        $this->ArrSet($transferedToDescription, $this->ArrGet($transferedToDescription)."transfered $amount - $realizationTaxAmount (tax) = $transferedAmount from $transferOrigin "); //The amount we transfered including the tax - for later reference and calculation
        $this->ArrSet($transferedOriginAmount, $this->ArrGet($transferedOriginAmount) - $amount); //The amount we transfered including the tax - for later reference and calculation

        //print "      transferTo ex tax: $transferTo=" . $this->ArrGet($transferTo) . " \n";
        //print "      transferedToAmount ex tax: $transferedToAmount=" . $this->ArrGet($transferedToAmount) . " \n";
        //print "      transferedOriginAmount inc tax: $transferedOriginAmount=" . $this->ArrGet($transferedOriginAmount) . " \n";
        //dd($this->dataH[$toAssetname][$toYear][$toType]);
        //dd($this->dataH[$originAssetname][$originYear][$originType]);

        //FIX: Should add explanation also on the asset transfered to for easier debug.
        $paidAmount -= $amount;
        if ($paidAmount < 0) {
            $paidAmount = 0; //Deposited amount can not go negative.
        }

        if ($debug) {
            echo "Transferto after: $transferTo: ".Arr::get($this->dataH, $transferTo, 0)."\n";
        }

        //###########################################################################################################
        //reduce value from this assetAmount
        $explanation .= " reduce by $amount\n";

        return [$paidAmount, $explanation];
    }

    /**
     * This function retrieves a value from the configuration or from the previous year's data.
     * It checks if the value is an amount and if so, it adds any transferred amount to this year to the previous year's amount.
     *
     * @param  bool  $debug Indicates whether debugging is enabled.
     * @param  string  $assetname The name of the asset.
     * @param  int  $year The year for which the value is being retrieved.
     * @param  string  $type The type of the value being retrieved (e.g., 'income', 'expense', etc.).
     * @param  string  $variable The specific variable within the type being retrieved.
     * @return mixed The retrieved value.
     */
    public function configOrPrevValue(bool $debug, string $assetname, int $year, string $type, string $variable)
    {
        $prevYear = $year - 1;
        $value = $this->ArrGetConfig("$assetname.$year.$type.$variable");

        $repeat = $this->ArrGetConfig("$assetname.$year.$type.repeat");
        if (! isset($repeat)) { //Check if repeat is set in the config
            $repeat = $this->ArrGet("$assetname.$prevYear.$type.repeat"); //Check if we stopped repeating the previous year.
        }
        if ($debug) {
            echo "      configOrPrevValueConfig: $assetname.$year.$type.$variable: $value\n";
        }

        //Trouble with bool handling here, and with amounts that are 0.0 (since amounts is set default to 0 so calculations shall work.
        //Isset is false if value is null, but it is true if value is 0 - thats why we need to check it it is numeric, and then check if it is 0.- then we try to get data from the dataH
        //FIX: Problem with amount reset to zero, if repeat=true. Because we do not know the difference if it is not set or if it is really set o 0, since we default to zero, but need it always returning integer/float for calculations
        if ((! isset($value) && $repeat) || (is_numeric($value) && $value == 0 && $repeat)) {
            $value = $this->ArrGet("$assetname.$prevYear.$type.$variable"); //Retrive value from dataH previous year only if repeat is true
            if ($debug) {
                echo "      configOrPrevValueData prev year: $assetname.$year.$type.$variable: $value\n";
            }
        }

        if (Str::contains("$assetname.$year.$type.$variable", ['Amount', 'amount'])) {
            //If it is an amount, we check if we have a transferred amount to this year, and add it to the previous years amount
            //$value += $this->ArrGet("$assetname.$year.$type.$variable");
        }

        if ($debug) {
            echo "      configOrPrevValueReturn: $assetname.$year.$type.$variable: $value\n";
        }

        return $value;
    }

    //Do post processing on already calculated data
    public function postProcess()
    {
        foreach ($this->dataH as $assetname => $assetH) {

            //print "PostProcess: $assetname\n";
            //print_r($assetH);
            $meta = $assetH['meta'];
            if (! $meta['active']) {
                continue;
            } //Hopp over de inaktive

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

                $datapath = "$assetname.$year";
                $this->postProcessTaxYearly($datapath);
                $this->postProcessIncomeYearly($datapath);
                $this->postProcessExpenceYearly($datapath);
                $this->postProcessCashFlowYearly($datapath);
                $this->postProcessAssetYearly($datapath);
                $this->postProcessRealizationYearly($datapath);
                $this->postProcessPotentialYearly($datapath);
                $this->postProcessFireYearly($assetname, $year, $meta);
            }
        }
    }

    //Do all calculations that should be done as the last thing, and requires that all other calculations is already done.
    //Special Arr get that onlye gets data from dataH to make cleaner code.
    public function ArrGet(string $path)
    {
        $default = null;
        if (Str::contains($path, ['Amount', 'Decimal', 'Percent', 'amount', 'decimal', 'percent', 'factor'])) {
            $default = 0;
        }
        //print "ArrGet: $path - default: $default\n";

        return Arr::get($this->dataH, $path, $default);
    }

    public function ArrSet(string $path, $value)
    {
        $debug = false;
        if (Str::contains($path, ['marketAmountX', 'afterTaxAmountX'])) {
            $debug = true;
        }

        if ($debug) {
            echo "ArrSet: $path:$value\n";
        }

        return Arr::set($this->dataH, $path, $value);
    }

    //Special Arr get that onlye gets data from configH to make cleaner code.
    public function ArrGetConfig(string $path)
    {
        $default = null;
        if (Str::contains($path, ['Amount', 'Decimal', 'Percent', 'amount', 'decimal', 'percent', 'factor'])) {
            $default = 0;
        }

        return Arr::get($this->config, $path, $default);
    }

    /**
     * Performs post-processing for the tax calculation of a given year and assett name.
     *
     * @param  int  $year The year for which the tax calculation is being processed.
     * @param  string  $assettname The name of the assett for which the tax is being calculated.
     * @return void
     */
    public function postProcessTaxYearly(string $path)
    {
    }

    /**
     * Modifies the yearly cash flow for a given asset and year.
     *
     * @param  int  $year The year for which to modify the cash flow.
     * @param  string  $assetName The name of the asset for which to modify the cash flow.
     * @return void
     */
    public function postProcessIncomeYearly(string $path)
    {
    }

    public function postProcessExpenceYearly(string $path)
    {
    }

    public function getAssetMetaFromPath($path, $type)
    {
        $value = null;
        $year = null;
        $assetname = null;

        if (preg_match('/(\w+).(\d+)/i', $path, $matchesH, PREG_OFFSET_CAPTURE)) {
            //print_r($matchesH);
            $year = $matchesH[2][0];
            $assetname = $matchesH[1][0];
            $value = $this->ArrGet("$assetname.meta.$type");
        } else {
            echo "ERROR with path: $path\n";
        }

        return [$assetname, $year, $value];
    }

    //Calculates an amount based on the value of another asset
    public function source(bool $debug, string $path, string $rule)
    {
        $paidAmount = 0;
        $amount = $this->ArrGet($path); //Retrive the amount from another asset. Do not change the other asset.

        [$newAmount, $diffAmount, $rule, $explanation] = $this->helper->calculateRule($debug, $amount, 0, $rule, 1);
        $explanation = " source $rule of $path $amount = $diffAmount\n";

        if ($debug) {
            echo "  Source: path: $path=$amount, $explanation\n";
        }

        return [$diffAmount, $explanation];
    }

    public function postProcessCashFlowYearly(string $path)
    {
        [$assetname, $year, $type, $field] = $this->helper->pathToElements("$path.cashflow.beforeTaxAmount");
        $prevYear = $year - 1;
        //echo "postProcessCashFlowYearly: $path\n";
        $this->ArrSet("$path.cashflow.beforeTaxAggregatedAmount", $this->ArrGet("$path.cashflow.beforeTaxAmount") + $this->ArrGet("$assetname.$prevYear.cashflow.beforeTaxAggregatedAmount"));  //FIX: Cashflow is not accumulated now
        $this->ArrSet("$path.cashflow.afterTaxAggregatedAmount", $this->ArrGet("$path.cashflow.afterTaxAmount") + $this->ArrGet("$assetname.$prevYear.cashflow.afterTaxAggregatedAmount"));  //FIX: Cashflow is not accumulated now
    }

    /**
     * Post-processes asset yearly data.
     *
     * @param  int  $year The year of the asset.
     * @param  string  $assetname The name of the asset.
     * @return void
     */
    public function postProcessAssetYearly(string $path)
    {

        if ($this->ArrGet("$path.mortgage.balanceAmount") > 0 && $this->ArrGet("$path.asset.marketAmount") > 0) {
            $this->ArrSet("$path.asset.mortageRateDecimal", $this->ArrGet("$path.mortgage.balanceAmount") / $this->ArrGet("$path.asset.marketAmount"));
        } else {
            $this->ArrSet("$path.asset.mortageRateDecimal", 0);
        }
    }

    public function postProcessRealizationYearly(string $path)
    {
    }


    /**
     * Performs post-processing for the income potential as seen from a Bank
     * This function calculates the potential maximum loan a user can handle based on their income
     *
     * @param  string  $path The path to the asset in the data structure. The path is in the format 'assetname.year'.
     * @return void
     */
    public function postProcessPotentialYearly(string $path)
    {
        // Retrieve the year and tax type from the asset metadata.
        [$assetname, $year, $taxtype] = $this->getAssetMetaFromPath($path, 'tax');

        // Retrieve the income amount for the asset.
        $potentialIncomeAmount = $this->ArrGet("$path.income.amount");

        // If the tax type is 'rental', the potential income is calculated for 10 months only.
        if ($taxtype == 'rental') {
            $potentialIncomeAmount = $potentialIncomeAmount / 12 * 10; //Only get calculated for 10 months income on rental
        }
        $potentialIncomeAmount -= $this->ArrGet("$path.mortgage.termAmount"); //Minus låne utgifter
        // All other income counts as income, no tax or expense deducted.

        // Set the potential income amount in the data structure.
        $this->ArrSet("$path.potential.incomeAmount", $potentialIncomeAmount);

        // Calculate the potential mortgage amount (the bank will loan you 5 times the income) and set it in the data structure.
        $this->ArrSet("$path.potential.mortgageAmount", $potentialIncomeAmount * 5); //The bank will loan you 5 times the income.
    }

    /**
     * Perform post-processing calculations for the FIRE (Financial Independence, Retire Early) calculation on a yearly basis.
     * Achievement er hvor mye du mangler for å nå målet? Feil navn?
     * FIREIncome er hvor mye du har i inntekt fra assets som kan brukes til å dekke utgifter.
     * //FIX - Should this be tax/mortgage adjusted amount? Percent of asset value.
     * //FIX - Hva regnes egentlig som sparing. Er det visse typer assets, ikke all inntekt????
     * amount = assetverdi - lån i beregningene + inntekt? (Hvor mye er 4% av de reelle kostnadene + inntekt (sannsynligvis kunn inntekt fra utleie)
     *
     * @param  int  $year The year for which the calculations are performed.
     * @param  string  $assetname The name of the asset for which the calculations are performed.
     * @return void
     */
    public function postProcessFireYearly(string $assetname, int $year, array $meta)
    {
        $prevYear = $year - 1;
        $firePercent = 0;
        $fireAssetIncomeAmount = 0; //Only asset value
        $CashflowTaxableAmount = 0;

        $path = "$assetname.$year";
        $assetMarketAmount = $this->ArrGet("$path.asset.marketAmount");
        $incomeAmount = $this->ArrGet("$path.income.amount");
        $expenceAmount = $this->ArrGet("$path.expence.amount");

        //FIX: Something is wrong with this classification, and automatically calculating sales of everything not in this list.

        if (Arr::get($this->firePartSalePossibleTypes, $meta['type'])) {
            //Her kan vi selge biter av en asset (meta tagge opp det istedenfor tro?
            //FIRE income in this context is how much income can we generate from our FIRE assets. This is ignored for now since I like paying dividends on 1/penisonWishYear-detahYear better. Done in config.
            $firePercent = 0.04; //ToDo: 4% av en salgbar asset verdi. FIX: Konfigurerbart FIRE tall.
            $fireAssetIncomeAmount = $assetMarketAmount * $firePercent; //Only asset value
            $CashflowTaxableAmount = $fireAssetIncomeAmount * $this->ArrGet("$path.income.taxableDecimal"); //ToDo: Legger til skatt på utbytte fra salg av en % av disse eiendelene her (De har neppe en inntekt, men det skal også fikses fint)
            //print "ATY: $CashflowTaxableAmount        += TFI:$fireAssetIncomeAmount * PTY:$DecimalTaxableYearly;\n";
            //FIX: Det er ulik skatt på de ulike typene.
        }

        //Endring i egenkapital mellon i år og i fjor, telles enten som inntekt eller utgift.Da dette er skjedd enten via en transfer eller en rule. Er det feil å telle om det er skjedd via en transfer? Nei, det påvirker begge assets i motsatte retninger, så påvirker ikke totalen
        $acquisitionChangeAmount = $this->ArrGet("$path.asset.acquisitionAmount") - $this->ArrGet("$assetname.$prevYear.asset.acquisitionAmount"); //Regn ut differansen i egenkapital mellom i år og i fjor. Differansen teller som FIRE inntekt.
        //print "acquisitionChangeAmount: $acquisitionChangeAmount\n";

        $incomeAmount = $incomeAmount + $acquisitionChangeAmount; //FIX - Should this be tax/mortgage adjusted amount? Percent of asset value.
        $fireExpenceAmount = $expenceAmount; //FIX - Should this be tax/mortgage adjusted amount? Percent of asset value.
        $fireCashFlowAmount = round($fireAssetIncomeAmount - $fireExpenceAmount); //Hvor lang er man unna FIRE målet

        //print "$assetname - FTI: $fireIncomeAmount = FI:$fireCurrentIncome + I:$incomeAmount + D:$deductableYearlyAmount\n"; #Percent of asset value + income from asset

        //##############################################################
        //Calculate FIRE percent diff
        if ($fireExpenceAmount > 0) {
            $fireRatePercent = $fireAssetIncomeAmount / $fireExpenceAmount; //Hvor mange % unna er inntektene å dekke utgiftene.
        } else {
            $fireRatePercent = 1;
        }

        //##############################################################
        //Calculate FIRE Savings amount
        $fireSavingAmount = 0;
        if (Arr::get($this->fireSavingTypes, $meta['type'])) {
            //print "FIRE SAVING: $assetname: " . $meta['type'] . " : $incomeAmount \n";
            $fireSavingAmount = $acquisitionChangeAmount - $this->ArrGet("$path.mortgage.interestAmount"); //Renter is not saving, but prinicpal is
        }

        //##############################################################
        //Calculate FIRE Savings rate
        //Sparerate = Det du nedbetaler i gjeld + det du sparer eller investerer på andre måter / total inntekt (etter skatt).
        $fireSavingRateDecimal = 0;
        //ToDo: Should this be income adjusted for deductions and tax?
        if ($incomeAmount > 0) {
            $fireSavingRateDecimal = ($fireSavingAmount / $incomeAmount) * 100;
            //print "FIRE SAVING RATE: $fireSavingRateDecimal = $fireSavingAmount / $incomeAmount\n";
        }

        $this->dataH[$assetname][$year]['fire'] = [
            'percent' => $firePercent,
            'incomeAmount' => $fireAssetIncomeAmount,
            'expenceAmount' => $fireExpenceAmount,
            'rateDecimal' => $fireRatePercent,
            'cashFlowAmount' => $fireCashFlowAmount,
            'savingAmount' => $fireSavingAmount,
            'savingRateDecimal' => $fireSavingRateDecimal,
        ];
    }

    public function group()
    {
        //dd($this->dataH);
        $this->initGroups();

        foreach ($this->dataH as $assetname => $assetH) {
            //print_r($assetH);
            $meta = $assetH['meta'];
            if (! $meta['active']) {
                continue;
            } //Hopp over de inaktive

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {
                //print "$year\n";

                //$this->setToGroup($year, $meta, $assetH[$year], "asset.taxDecimal");
                //FIX: Everything with amount in the name should be aggregated on group level, so this could be smarter than the hardcoding here.

                $this->additionToGroup($year, $meta, $assetH[$year], 'income.amount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'income.transferedAmount');

                $this->additionToGroup($year, $meta, $assetH[$year], 'expence.amount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'expence.transferedAmount');

                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.beforeTaxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.afterTaxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.beforeTaxAggregatedAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.afterTaxAggregatedAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.taxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.transferedAmount');

                $this->additionToGroup($year, $meta, $assetH[$year], 'mortgage.amount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'mortgage.termAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'mortgage.interestAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'mortgage.principalAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'mortgage.balanceAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'mortgage.extraDownpaymentAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'mortgage.gebyrAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'mortgage.taxDeductableAmount');

                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.marketAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.marketMortgageDeductedAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.acquisitionAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.equityAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.paidAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.transferedAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.taxableAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.taxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.taxablePropertyAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.taxPropertyAmount');

                $this->additionToGroup($year, $meta, $assetH[$year], 'realization.amount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'realization.taxableAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'realization.taxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'realization.taxShieldAmount');

                $this->additionToGroup($year, $meta, $assetH[$year], 'potential.incomeAmount'); //Beregnet potensiell inntekt slik bankene ser det.
                $this->additionToGroup($year, $meta, $assetH[$year], 'potential.mortgageAmount'); //Beregner maks potensielt lån på 5 x inntekt.

                $this->additionToGroup($year, $meta, $assetH[$year], 'fire.incomeAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'fire.expenceAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'fire.savingAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'fire.cashFlowAmount');
            }
        }

        //More advanced calculations on numbers other than amount that can not just be added and all additions are done in advance so we work on complete numbers
        //FireSavingrate as a calculation of totals,
        //FIX: tax calculations/deductions where a fixed deduxtion is uses, or a deduction based on something

        for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

            $this->groupFireSaveRate($year);
            $this->groupFirediffPercent($year);
            $this->groupFortuneTax($year);
            //FIX, later correct tax handling on the totals ums including deductions
        }

        //print "group\n";
        $this->assetTypeSpread();
    }

    private function assetTypeSpread()
    {

        foreach ($this->groupH as $type => $asset) {
            if (Arr::get($this->assetSpreadTypes, $type)) {
                //print "$type\n";
                foreach ($asset as $year => $data) {
                    $amount = round(Arr::get($data, 'asset.marketAmount', 0));
                    //print "$type:$year:$amount\n";
                    $this->statisticsH[$year][$type]['amount'] = $amount;
                    $this->statisticsH[$year]['total']['amount'] = Arr::get($this->statisticsH, "$year.total.amount", 0) + $amount;
                }

                //Generate % spread
                foreach ($this->statisticsH as $year => $typeH) {
                    foreach ($typeH as $typename => $data) {
                        if ($typeH['total']['amount'] > 0) {
                            $this->statisticsH[$year][$typename]['decimal'] = round(($data['amount'] / $typeH['total']['amount']) * 100);
                        } else {
                            $this->statisticsH[$year][$typename]['decimal'] = 0;
                        }
                        //print_r($data);
                        //print "$year=" . $data['amount'] . "\n";
                    }
                }
            }
        }
        //print_r($this->statisticsH);
    }

    private function groupFortuneTax(int $year)
    {
        //ToDo - fortune tax sybtraction level support.

        [$assetTaxAmount, $fortuneTaxDecimal] = $this->tax->fortuneTaxGroupCalculation('total', Arr::get($this->totalH, "$year.asset.taxableAmount", 0), $year);
        Arr::set($this->totalH, "$year.asset.taxAmount", $assetTaxAmount);

        [$assetTaxAmount, $fortuneTaxDecimal] = $this->tax->fortuneTaxGroupCalculation('company', Arr::get($this->companyH, "$year.asset.taxableAmount", 0), $year);
        Arr::set($this->companyH, "$year.asset.taxAmount", $assetTaxAmount);

        [$assetTaxAmount, $fortuneTaxDecimal] = $this->tax->fortuneTaxGroupCalculation('private', Arr::get($this->privateH, "$year.asset.taxableAmount", 0), $year);
        Arr::set($this->privateH, "$year.asset.taxAmount", $assetTaxAmount);
    }

    //Calculates on data that is summed up in the group
    //FIX: Much better if we could use calculus here to reduce number of methods, but to advanced for the moment.
    public function groupFireSaveRate(int $year)
    {
        if (Arr::get($this->totalH, "$year.fire.savingAmount", 0) > 0) {
            Arr::set($this->totalH, "$year.fire.savingRate", Arr::get($this->totalH, "$year.fire.incomeAmount", 0) / Arr::get($this->totalH, "$year.fire.savingAmount", 0));
        }
        if (Arr::get($this->companyH, "$year.fire.savingAmount", 0) > 0) {
            Arr::set($this->companyH, "$year.fire.savingRate", Arr::get($this->companyH, "$year.fire.incomeAmount", 0) / Arr::get($this->companyH, "$year.fire.savingAmount", 0));
        }
        if (Arr::get($this->privateH, "$year.fire.savingAmount", 0) > 0) {
            Arr::set($this->privateH, "$year.fire.savingRate", Arr::get($this->privateH, "$year.fire.incomeAmount", 0) / Arr::get($this->privateH, "$year.fire.savingAmount", 0));
        }
        //FIX: Loop this out for groups.
        //foreach($this->groupH){
        //$this->groupH;
        //}
    }

    private function groupFirediffPercent(int $year)
    {

        if (Arr::get($this->totalH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($this->totalH, "$year.fire.diffDecimal", Arr::get($this->totalH, "$year.fire.incomeAmount", 0) / Arr::get($this->totalH, "$year.fire.expenceAmount", 0));
        }
        if (Arr::get($this->companyH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($this->companyH, "$year.fire.diffDecimal", Arr::get($this->companyH, "$year.fire.incomeAmount", 0) / Arr::get($this->companyH, "$year.fire.expenceAmount", 0));
        }
        if (Arr::get($this->privateH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($this->privateH, "$year.fire.diffDecimal", Arr::get($this->privateH, "$year.fire.incomeAmount", 0) / Arr::get($this->privateH, "$year.fire.expenceAmount", 0));
        }
        //FIX: Loop this out for groups.
        //foreach($this->groupH){
        //$this->groupH;
        //}
    }

    private function additionToGroup(int $year, array $meta, array $data, string $dotpath)
    {
        //"fortune.taxableAmount"
        //if(Arr::get($data, $dotpath)) {

        //Just to create an empty object, if it has no values.
        Arr::set($this->totalH, "$year.$dotpath", Arr::get($this->totalH, "$year.$dotpath", 0) + Arr::get($data, $dotpath, 0));
        //print "Addtogroup:  " . Arr::get($this->totalH, "$year.$dotpath") . " = " . Arr::get($this->totalH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";

        //Company
        if (Arr::get($meta, 'group') == 'company') {
            //Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene
            Arr::set($this->companyH, "$year.$dotpath", Arr::get($this->companyH, "$year.$dotpath", 0) + Arr::get($data, $dotpath, 0));
        }

        //Private
        if (Arr::get($meta, 'group') == 'private') {
            //Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene.
            Arr::set($this->privateH, "$year.$dotpath", Arr::get($this->privateH, "$year.$dotpath", 0) + Arr::get($data, $dotpath, 0));
            //print "private: $year.$dotpath :  " . Arr::get($this->privateH, "$year.$dotpath") . " = " . Arr::get($this->privateH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";
        }

        //Grouping
        $grouppath = Arr::get($meta, 'group').".$year.$dotpath";
        $typepath = Arr::get($meta, 'type').".$year.$dotpath";
        Arr::set($this->groupH, $grouppath, Arr::get($this->groupH, $grouppath, 0) + Arr::get($data, $dotpath, 0));
        Arr::set($this->groupH, $typepath, Arr::get($this->groupH, $typepath, 0) + Arr::get($data, $dotpath, 0));
        //} elseif($dotpath == 'fortune.taxableAmount') {
        //    print "additionToGroup($year, $dotpath) empty\n";
        //}
    }

    private function setToGroup(int $year, array $meta, array $data, string $dotpath)
    {

        if (Arr::get($data, $dotpath)) {

            //Just to create an empty object, if it has no values.
            Arr::set($this->totalH, "$year.$dotpath", Arr::get($data, $dotpath));
            //print "Addtogroup:  " . Arr::get($this->totalH, "$year.$dotpath") . " = " . Arr::get($this->totalH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";

            //Company
            if (Arr::get($meta, 'group') == 'company') {
                //Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene
                Arr::set($this->companyH, "$year.$dotpath", Arr::get($data, $dotpath));
            }

            //Private
            if (Arr::get($meta, 'group') == 'private') {
                //Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene.
                Arr::set($this->privateH, "$year.$dotpath", Arr::get($data, $dotpath));
                //print "private: $year.$dotpath :  " . Arr::get($this->privateH, "$year.$dotpath") . " = " . Arr::get($this->privateH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";
            }

            //Grouping
            $grouppath = Arr::get($meta, 'group').".$year.$dotpath";
            $typepath = Arr::get($meta, 'type').".$year.$dotpath";
            Arr::set($this->groupH, $grouppath, Arr::get($data, $dotpath));
            Arr::set($this->groupH, $typepath, Arr::get($data, $dotpath));
        }
    }

    private function initGroups()
    {
        //Just to get the sorting right, its bettert to start with an emplty structure in correct yearly order

        for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {
            Arr::set($this->privateH, "$year.asset.marketAmount", 0);
            Arr::set($this->companyH, "$year.asset.marketAmount", 0);
        }

        //FIX: Loop over groups also
    }
}
