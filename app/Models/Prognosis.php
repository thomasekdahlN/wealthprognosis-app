<?php

//Asset,
//Mortgage,
//CashFlow



namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Models\Helper;

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

    #FIX: Kanskje feil å regne inn otp her? Der kan man jo ikke velge.
    public $firePartSalePossibleTypes = [
        'crypto' => true,
        'fond' => true,
        'stock' => false,
        'otp' => true,
        'ask' => true,
        'pension' => true,
        ];

    public $fireSavingTypes = [
        'house' => true,
        'rental' => true,
        'cabin' => true,
        'crypto' => true,
        'fond' => true,
        'stock' => true,
        'otp' => false,
        'ask' => true,
        'pension' => true,
        ];


    public $assetSpreadTypes = [
        'house' => true,
        'rental' => true,
        'cabin' => true,
        'crypto' => true,
        'fond' => true,
        'stock' => true,
        'otp' => false,
        'ask' => true,
        'pension' => true,
    ];

    public function __construct(array $config, object $tax, object $changerate)
    {
        #$this->test();
        $this->config = $config;
        $this->tax = $tax;
        $this->changerate = $changerate;
        $this->helper = new \App\Models\Helper();

        $this->birthYear  = (integer) Arr::get($this->config, 'meta.birthYear');
        $this->economyStartYear = $this->birthYear + 16; #We look at economy from 16 years of age
        $this->thisYear  = now()->year;
        $this->deathYear  = (integer) $this->birthYear + Arr::get($this->config, 'meta.deathYear');

        foreach(Arr::get($this->config, 'assets') as $assetname => $assetconfig) {

            #Store all metadata about the asset, the rest is yearly calculations
            $this->dataH[$assetname]['meta'] = $meta = $assetconfig['meta'];
            if(!$meta['active']) continue; #Hopp over de inaktive
            $taxtype = Arr::get($meta, "tax", null);

            $assetFirstAmount = 0;
            $assetFirstYear = 0;
            $assetPrevAmount = null;
            $assetCurrentAmount = 0;
            $assetRule = null;
            $assetChangerateDecimal = 0;
            $assetChangerateAmount = "";
            $assetCurrentRepeat= false;
            $assetPrevRepeat= false;
            $assetCurrentTransferResource = null;
            $assetPrevTransferResource = null;
            $assetCurrentTransferRule = null;
            $assetPrevTransferRule = null;
            $assetAggregatedDepositedAmount = 0;

            $incomeCurrentAmount = 0;
            $incomePrevAmount = 0;
            $incomeChangerateDecimal = 0;
            $incomeChangerateAmount = "";
            $incomeRule = null;
            $incomePrevRepeat = false;
            $incomeCurrentRepeat = false;
            $incomeCurrentTransferResource = null;
            $incomePrevTransferResource = null;
            $incomeCurrentTransferRule = null;
            $incomePrevTransferRule = null;

            $expenceCurrentAmount = 0;
            $expencePrevAmount = 0;
            $expenceChangerateDecimal = 0;
            $expenceChangerateAmount = "";
            $expenceRule = null;
            $expenceCurrentRepeat = false;
            $expencePrevRepeat = false;
            $expenceCurrentTransferResource = null;
            $expencePrevTransferResource = null;
            $expenceCurrentTransferRule = null;
            $expencePrevTransferRule = null;

            $restAccumulated = 0;

            #dd($this->config['changerates']);

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

                $DecimalTaxableYearly = $this->tax->getTaxYearly($taxtype, $year);
                $DecimalTaxableRealization =  $this->tax->getTaxRealization($taxtype, $year);
                $DecimalDeductableYearly = $this->tax->getTaxYearly($taxtype, $year);
                $DecimalDeductableRealization = $this->tax->getTaxRealization($taxtype, $year);
                $DecimalTaxableFortune = $this->tax->getTaxableFortune($taxtype, $year);

                #####################################################
                #expence

                $expenceCurrentRepeat = Arr::get($assetconfig, "expence.$year.repeat", null);
                if(isset($expenceCurrentRepeat)) {
                    $expencePrevRepeat = $expenceCurrentRepeat;
                } else {
                    $expenceCurrentRepeat = $expencePrevRepeat;
                }

                list($expenceChangeratePercent, $expenceChangerateDecimal, $expenceChangerateAmount, $explanation) = $this->changerate->convertChangerate(0, Arr::get($assetconfig, "expence.$year.changerate", null), $year, $expenceChangerateAmount);

                $expenceCurrentAmount = Arr::get($assetconfig, "expence.$year.amount", 0); #Expence is added as a monthly repeat in config

                #print "Expence transfer before: $assetname.$year, expencePrevAmount:$expencePrevAmount, expenceCurrentAmount:$expenceCurrentAmount\n";
                list($expenceCurrentAmount, $expenceDepositedAmount, $expenceRule, $explanation) = $this->helper->adjustAmount(0, $expencePrevAmount, $expenceCurrentAmount, 0, $expenceRule,12);
                #print "Expence transfer after: $assetname.$year, expencePrevAmount:$expencePrevAmount, expenceCurrentAmount:$expenceCurrentAmount\n";

                $this->dataH[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangeratePercent / 100,
                    'amount' => $expenceCurrentAmount,
                    'description' => Arr::get($assetconfig, "expence.$year.description") . $explanation,
                ];

                #print "$year: expenceChangeratePercent = $expenceChangerateDecimal - expence * $expence\n";

                #####################################################
                #income
                $incomeCurrentTransferResource = Arr::get($assetconfig, "income.$year.transferResource", null);
                if(isset($incomeCurrentTransferResource)) {
                    $incomePrevTransferResource = $incomeCurrentTransferResource;
                } else {
                    $incomeCurrentTransferResource = $incomePrevTransferResource; #Remembers a transfer until zeroed out in config.
                }

                $incomeCurrentTransferRule = Arr::get($assetconfig, "income.$year.transferRule", null);
                if(isset($incomeCurrentTransferRule)) {
                    $incomePrevTransferRule = $incomeCurrentTransferRule;
                } else {
                    $incomeCurrentTransferRule = $incomePrevTransferRule; #Remembers a transfer until zeroed out in config.
                }



                $incomeCurrentRepeat = Arr::get($assetconfig, "income.$year.repeat", null);
                if(isset($incomeCurrentRepeat)) {
                    $incomePrevRepeat = $incomeCurrentRepeat;
                } else {
                    $incomeCurrentRepeat = $incomePrevRepeat;
                }

                list($incomeChangeratePercent, $incomeChangerateDecimal, $incomeChangerateAmount, $explanation) =  $this->changerate->convertChangerate(false, Arr::get($assetconfig, "income.$year.changerate", null), $year, $incomeChangerateAmount);

                $incomeCurrentAmount = Arr::get($assetconfig, "income.$year.amount", 0); #Income is added as a yearly repeat in config

                list($incomeCurrentAmount, $incomeDepositedAmount, $incomeRule, $explanation) = $this->helper->adjustAmount(false, $incomePrevAmount, $incomeCurrentAmount, 0, $incomeRule, 12);

                #print "Income transfer before: $assetname.$year, incomeCurrentAmount:$incomeCurrentAmount, incomeCurrentTransferResource:$incomeCurrentTransferResource, incomeCurrentTransferRule:$incomeCurrentTransferRule\n";
                list($incomeCurrentAmount, $incomeTransferedAmount, $incomePrevTransferRule, $explanation) = $this->transferAmount(false, $assetname, $year, $incomeCurrentAmount, 0, $incomeCurrentTransferResource, $incomeCurrentTransferRule, 12);
                #print "Income transfer after: $assetname.$year, incomeCurrentAmount:$incomeCurrentAmount, incomeTransferedAmount:$incomeTransferedAmount, incomePrevTransferRule:$incomePrevTransferRule, explanation: $explanation\n";

                $this->dataH[$assetname][$year]['income'] = [
                    'changerate' => $incomeChangeratePercent / 100,
                    'amount' => $incomeCurrentAmount + Arr::get($this->dataH, "$assetname.$year.income.amount", 0),
                    'description' => Arr::get($assetconfig, "income.$year.description") . $explanation,
                    ];

                ########################################################################################################
                #Assett
                $assetCurrentTransferResource = Arr::get($assetconfig, "asset.$year.transferResource", null);
                if(isset($assetCurrentTransferResource)) {
                    $assetPrevTransferResource = $assetCurrentTransferResource;
                } else {
                    $assetCurrentTransferResource = $assetPrevTransferResource; #Remembers a transfer until zeroed out in config.
                }

                $assetCurrentTransferRule = Arr::get($assetconfig, "asset.$year.transferRule", null);
                if(isset($assetCurrentTransferRule)) {
                    $assetPrevTransferRule = $assetCurrentTransferRule;
                } else {
                    $assetCurrentTransferRule = $assetPrevTransferRule; #Remembers a transfer until zeroed out in config.
                }

                $assetCurrentRepeat = Arr::get($assetconfig, "asset.$year.repeat", null);
                if(isset($assetCurrentRepeat)) {
                    $assetPrevRepeat= $assetCurrentRepeat;
                } else {
                    $assetCurrentRepeat = $assetPrevRepeat;
                }

                list($assetChangeratePercent, $assetChangerateDecimal, $assetChangerateAmount, $explanation) = $this->changerate->convertChangerate(false, Arr::get($assetconfig, "asset.$year.changerate", null), $year, $assetChangerateAmount);
                #print "$year: " . $this->changerate->decimalToDecimal($assetChangerateDecimal) . "\n";

                $assetCurrentAmount = Arr::get($assetconfig, "asset.$year.amount", 0);
                $assetCurrentTaxAmount = Arr::get($assetconfig, "asset.$year.taxvalue", 0);

                #print "Asset før: year: $year assetPrevAmount:$assetPrevAmount assetCurrentAmount:$assetCurrentAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount assetRule:$assetRule\n";
                list($assetCurrentAmount, $assetAggregatedDepositedAmount, $assetRule, $explanation) = $this->helper->adjustAmount(false, $assetPrevAmount, $assetCurrentAmount, $assetAggregatedDepositedAmount, $assetRule, 1);
                #print "Asset etter: year:$year assetCurrentAmount: $assetCurrentAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount, assetRule:$assetRule explanation: $explanation\n";

                #We must check if values has been set in the structure already and add them
                $assetCurrentAmount += Arr::get($this->dataH, "$assetname.$year.asset.amount", 0); #If current value is not an amount at this point, this will crash

                #print "Asset transfer before: $assetname.$year, assetCurrentAmount:$assetCurrentAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount, assetCurrenttransferResource:$assetCurrentTransferResource, AssetCurrentTransferRule:$assetCurrentTransferRule\n";
                list($assetCurrentAmount, $assetTransferedAmount, $assetAggregatedDepositedAmount, $assetPrevTransferRule, $explanation) = $this->transferAmount(false, $assetname, $year, $assetCurrentAmount, $assetAggregatedDepositedAmount, $assetCurrentTransferResource, $assetCurrentTransferRule, 1);
                #print "Asset transfer after: $assetname.$year, assetCurrentAmount:$assetCurrentAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount, assetTransferedAmount:$assetTransferedAmount, assetPrevTransferRule:$assetPrevTransferRule, explanation: $explanation\n";

                #FIX: The input diff has to be added to FIRE calculations.

                if(!$assetFirstAmount && $assetCurrentAmount > 0) {
                    $assetFirstAmount = $assetCurrentAmount; #FIX: Ta vare på den første verdien vi ser på en asset, da den brukes til skatteberegning ved salg. Må også akkumulere alle innskudd, men ikke verdiøkning.
                    $assetFirstYear = $year; #Ta vare på den første året vi ser en asset, da den brukes til skatteberegning ved salg for å se hvor lenge man har eid den.
                };


                ########################################################################################################
                #Tax calculations
                #print "$taxtype.$year incomeCurrentAmount: $incomeCurrentAmount, expenceCurrentAmount: $expenceCurrentAmount\n";
                list($cashflow, $potentialIncome, $CashflowTaxableAmount, $fortuneTaxableAmount, $fortuneTaxAmount, $fortuneTaxablePercent, $fortuneTaxPercent, $AmountTaxableRealization, $AmountDeductableYearly, $AmountDeductableRealization) = $this->tax->taxCalculation(false, $taxtype, $year, $incomeCurrentAmount, $expenceCurrentAmount, $assetCurrentAmount, $assetCurrentTaxAmount, $assetFirstAmount, $assetFirstYear);


                #FIX: Why is it called asset.amount here anf not value? Why does this work?
                $this->dataH[$assetname][$year]['asset'] = [
                    'amount' => $assetCurrentAmount,
                    'amountDeposited' => $assetAggregatedDepositedAmount,
                    'amountLoanDeducted' => $assetCurrentAmount,
                    'changerate' => $assetChangeratePercent / 100,
                    'percentTaxable' => $fortuneTaxablePercent,
                    'amountTaxable' => $fortuneTaxableAmount,
                    'percentTax' => $fortuneTaxPercent,
                    'amountTax' => $fortuneTaxAmount,
                    'description' => Arr::get($assetconfig, "asset.$year.description") . " Asset rule " . $assetRule . $explanation,
                ];


                $this->dataH[$assetname][$year]['tax'] = [
                    'amountTaxableYearly' => -$CashflowTaxableAmount,
                    'percentTaxableYearly' => $DecimalTaxableYearly,
                    'amountDeductableYearly' => -$AmountDeductableYearly,
                    'percentDeductableYearly' => $DecimalDeductableYearly,
                    'amountTaxableRealization' => $AmountTaxableRealization,
                    'percentTaxableRealization' => $DecimalTaxableRealization,
                    'amountDeductableRealization' => $AmountDeductableRealization,
                    'percentDeductableRealization' => $DecimalDeductableRealization,
                ];

                #Calculate the potential max loan you can handle base on income, tax adjusted - as seen from the bank.
                #print "$assetname - p:$potentialIncome = i:$incomeCurrentAmount - t:$CashflowTaxableAmount\n";
                $this->dataH[$assetname][$year]['potential'] = [
                    'income' => $potentialIncome,
                    'loan' => $potentialIncome * 5
                ];

                ########################################################################################################
                if($expenceCurrentRepeat) {
                    $expencePrevAmount      = $expenceCurrentAmount * $expenceChangerateDecimal;
                } else {
                    $expenceCurrentAmount        = 0;
                    $expencePrevAmount           = 0;
                    $expenceChangerateAmount     = 0;
                    $expenceChangerateDecimal   = null;
                    $expenceChangeratePercent   = null;
                    $expenceCurrentTransferResource = null;
                    $expenceCurrentTransferRule   = null;
                    $expencePrevTransferResource  = null;
                    $expencePrevTransferRule   = null;
                }

                if($incomeCurrentRepeat) {
                    $incomePrevAmount       = $incomeCurrentAmount * $incomeChangerateDecimal;
                } else {
                    $incomeCurrentAmount         = 0;
                    $incomePrevAmount            = 0;
                    $incomeChangerateAmount      = 0;
                    $incomeChangerateDecimal    = null;
                    $incomeChangeratePercent    = null;
                    $incomeCurrentTransferResource     = null;
                    $incomeCurrentTransferRule   = null;
                    $incomePrevTransferResource     = null;
                    $incomePrevTransferRule   = null;

                }

                if($assetCurrentRepeat) {
                    $assetPrevAmount   = round($assetCurrentAmount * $assetChangerateDecimal);
                    $assetPrevTaxAmount = round($assetCurrentTaxAmount * $assetChangerateDecimal);
                } else {
                    $assetCurrentAmount          = 0;
                    $assetPrevAmount             = 0;
                    $assetPrevTaxAmount          = 0;
                    $assetChangerateAmount       = 0;
                    $assetChangerateDecimal     = null;
                    $assetChangeratePercent     = null;
                    $assetRule                  = null;
                    $assetCurrentTransferResource = null;
                    $assetCurrentTransferRule     = null;
                    $assetPrevTransferResource = null;
                    $assetPrevTransferRule     = null;
                    $assetAggregatedDepositedAmount = 0;

                }
            } #Year loop finished here.

            #####################################################
            #Loan
            //$this->collections = $this->collections->keyBy('year');
            #dd($this->dataH);
            $mortgage = Arr::get($assetconfig, "mortgage", false);

            #print_r($mortgage);
            if($mortgage) {
                #Kjører bare dette om mortgage strukturen i json er utfylt
                $this->dataH = (new Amortization($this->config, $this->changerate, $this->dataH, $mortgage, $assetname))->get();
                #$this->dataH = new Amortization($this->dataH, $mortgage, $assetname);

                #dd($this->dataH['Smørbukkveien 3']);
            }

            //return $this->collections; #??????
            $this->postProcess();

        } #End loop over assets

        $this->postProcess();
        $this->group();
    }


    #Either creates an amount based on another amount or transfers parts of an amount to another amount.
    #Example
    #transferResource": "salary.$year.income.amount",  #Example OPT creatwes 5% of income as OTP without reducing OTP
    #tranferRule: add&5%. Plus does not reduce the asset, it just adds to the asset you are transferring to.
    #transferResource": "income.$year.income.amount",  #Example using OTP until death reduces the OTP value accordingly
    #tranferRule: transfer&-1/12 (always compared to the asset you are in). Minus reduces the asset.
    #transferRule add|subtract|transfer&calculations
    #available calculations: 5% (percent), 1000 (fix amount), 1/12 (divisor)
    #add=adds to the resource calculated from the current value, but does not change the current value
    #subtract=subtracts from the resource calculated from the current value, but does not change the current value
    #transfer=adds to the resource calculated from the current value, and subtracts this value from the current resource

    #calculations are always done on value input into the function
    public function transferAmount(bool $debug, string $assetname, int $year,  int $amount, ?int $depositedAmount, ?string $transferResource, ?string $transferRule, int $factor = 1)
    {

        $newAmount = 0;
        $newAssetAmount = 0;
        $explanation = null;
        $rule = null;

        if ($transferResource && $transferRule) {

            $transferResource = str_replace(
                ['$year'],
                [$year],
                $transferResource);

            $ruleH      = explode('&', $transferRule);
            $resourceH  = explode('.', $transferResource);

            if ($debug) {
                print "**** $assetname.$year: amount: $amount, transferResource: $transferResource, transferRule: $transferRule\n";
                #print " rule: " . $ruleH[0] . " " . $ruleH[1] . "\n";
            }

            list($newAmount, $depositedAmount, $rulepart, $explanation) = $this->helper->calculateRule(true, $amount, $depositedAmount, $ruleH[1]);
            $rule = $ruleH[0] . "&$rulepart";
            #ToDo check that an asset can not go into negative value?

            if ($newAmount) {
                if ($ruleH[0] == 'transfer') {
                    ############################################################################################################
                    #Transfer value to another asset, has to update the datastructure of this asset directly

                    $explanation .= " transfer $newAmount to $transferResource";
                    if ($debug) {
                        print "Transfer before: $transferResource: " . Arr::get($this->dataH, $transferResource, 0) . "\n";
                    }
                    Arr::set($this->dataH, $transferResource, Arr::get($this->dataH, $transferResource, 0) + ($newAmount * $factor)); #The real transfer from this asset to another takes place here

                    $depositedAmount - ($newAmount * $factor);
                    if($depositedAmount < 0) {
                        $depositedAmount = 0; #Deposited amount can not go negative.
                    }

                    #Reduce deposited amount until zero
                    /*$transferDeposit = str_replace(
                        ['amount'],
                        ['amountDeposit'],
                        $transferResource);
                    print "transferDeposit: $transferDeposit\n";
                    $currentAmountDeposited = Arr::get($this->dataH, $transferDeposit, 0);
                    $amountDeposited = $currentAmountDeposited - ($newAmount * $factor);
                    if($amountDeposited > 0) {
                        #FIX: Should it fix all years after also??
                        Arr::set($this->dataH, $transferDeposit, Arr::get($this->dataH, $transferResource, 0) + ($newAmount * $factor));
                    } else {
                        Arr::set($this->dataH, $transferDeposit, 0); #Deposited amount can not go below zero
                    }
                    */

                    if ($debug) {
                        print "Transfer after: $transferResource: " . Arr::get($this->dataH, $transferResource, 0) . "\n";
                    }

                    ############################################################################################################
                    #reduce value from this assetAmount
                    $explanation .= " reduce $assetname.$year by $newAmount\n";
                    $newAssetAmount = $amount - $newAmount;

                } elseif ($ruleH[0] == 'add') {

                    #print_r($resourceH);

                    if ($debug) {
                        print "Transfer before: $transferResource: " . Arr::get($this->dataH, $transferResource, 0) . "\n";
                    }
                    $explanation .= " transfer add $newAmount to $transferResource";
                    Arr::set($this->dataH, $transferResource, Arr::get($this->dataH, $transferResource, 0) + ($newAmount)); #The real transfer from this asset to another takes place here
                    $newAssetAmount = $amount;
                    if ($debug) {
                        print "Transfer after: $transferResource: " . Arr::get($this->dataH, $transferResource, 0) . "\n";
                    }


                    if($debug) {
                        #print_r($this->dataH[$resourceH[0]][$year]);
                    }

                } elseif ($ruleH[0] == 'subtract') {

                    $explanation .= " transfer subtract $newAmount to $transferResource";
                    Arr::set($this->dataH, $transferResource, Arr::get($this->dataH, $transferResource, 0) - ($newAmount)); #The real transfer from this asset to another takes place here
                    $newAssetAmount = $amount;

                }
            }
        } else {
            $explanation .= " no transferResource && transferRule\n";
            $newAssetAmount = $amount;
        }

        if ($debug) {
            print "####--- explanation: $explanation\n";
        }

        return [$newAssetAmount, $newAmount, $depositedAmount, $rule, $explanation];
    }

    #Do all post processing on already calculated data
    function postProcess() {
        foreach($this->dataH as $assetname => $assetH) {

            print_r($assetH);
            $meta = $assetH['meta'];
            if(!$meta['active']) continue; #Hopp over de inaktive

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

                $datapath = "$assetname.$year";
                $this->postProcessTaxYearly($datapath);
                $this->postProcessCashFlowYearly($datapath);
                $this->postProcessAssetYearly($datapath);
                $this->postProcessFireYearly($assetname, $year, $meta);
            }
        }
    }

    #Do all calculations that should be done as the last thing, and requires that all other calculations is already done.
    #Special Arr get that onlye gets data from dataH to make cleaner code.
    function ArrGet(string $path){
        return Arr::get($this->dataH, $path, 0);
    }

    /**
     * Performs post-processing for the tax calculation of a given year and assett name.
     *
     * @param int $year The year for which the tax calculation is being processed.
     * @param string $assettname The name of the assett for which the tax is being calculated.
     *
     * @return void
     */
    function postProcessTaxYearly(string $path)
    {
        Arr::set($this->dataH, "$path.tax.amountDeductableYearly", $this->ArrGet("$path.tax.interestAmount") * 0.22); #ToDo: Remove hardcoded percentage later to read from tax config
    }

    /**
     * Modifies the yearly cash flow for a given asset and year.
     *
     * @param int $year The year for which to modify the cash flow.
     * @param string $assetName The name of the asset for which to modify the cash flow.
     *
     * @return void
     */
    function postProcessCashFlowYearly(string $path)
    {
        #Free money to spend
        $cashflowAmount = $this->ArrGet("$path.income.amount") - $this->ArrGet("$path.expence.amount") + $this->ArrGet("$path.tax.amountDeductableYearly") - $this->ArrGet("$path.mortgage.payment");

        Arr::set($this->dataH, "$path.cashflow.amount", $cashflowAmount);
        Arr::set($this->dataH, "$path.cashflow.amountAccumulated",$cashflowAmount);  #ToDo: Cashflow is not accumulated now
    }

    /**
     * Post-processes asset yearly data.
     *
     * @param int $year The year of the asset.
     * @param string $assetname The name of the asset.
     *
     * @return void
     */
    function postProcessAssetYearly(string $path) {

        Arr::set($this->dataH,"$path.asset.amountLoanDeducted",  $this->ArrGet("$path.tax.amountLoanDeducted") - $this->ArrGet("$path.mortgage.balance"));  #Cashflow accumulated må reberegnes til slutt???
        Arr::set($this->dataH,"$path.asset.amountDeposited",     $this->ArrGet("$path.asset.amount") - $this->ArrGet("$path.mortgage.balance"));

        if ($this->ArrGet("$path.mortgage.balance") > 0 && $this->ArrGet("$path.asset.amount") > 0) {
            Arr::set($this->dataH,"$path.asset.loanPercentage", $this->ArrGet("$path.mortgage.balance") / $this->ArrGet("$path.asset.amount"));  #Cashflow accumulated må reberegnes til slutt???
        } else {
            Arr::set($this->dataH,"$path.asset.loanPercentage", 0);
        }
    }

    /**
     * Perform post-processing calculations for the FIRE (Financial Independence, Retire Early) calculation on a yearly basis.
     * Achievement er hvor mye du mangler for å nå målet? Feil navn?
     * amount = assetverdi - lån i beregningene + inntekt? (Hvor mye er 4% av de reelle kostnadene + inntekt (sannsynligvis kunn inntekt fra utleie)
     * @param int $year The year for which the calculations are performed.
     * @param string $assetname The name of the asset for which the calculations are performed.
     * @return void
     */
    function postProcessFireYearly(string $assetname, int $year, array $meta) {

        $firePercent            = 0;
        $fireAssetIncomeAmount  = 0; #Only asset value
        $CashflowTaxableAmount  = 0;

        $path = "$assetname.$year";
        $assetAmount = $this->ArrGet("$path.asset.amount");
        $incomeAmount = $this->ArrGet("$path.income.amount");
        $expenceAmount = $this->ArrGet("$path.expence.amount");

        #FIX: Something is wrong with this classification, and automatically calculating sales of everything not in this list.
        if(Arr::get($this->firePartSalePossibleTypes, $meta['type'])) {
            #Her kan vi selge biter av en asset (meta tagge opp det istedenfor tro?
            $firePercent            = 0.04; #ToDo: 4% av en salgbar asset verdi. FIX: Konfigurerbart FIRE tall.
            $fireAssetIncomeAmount  = $assetAmount * $firePercent; #Only asset value
            $CashflowTaxableAmount  = $fireAssetIncomeAmount * $this->ArrGet("$path.tax.percentTaxableYearly"); #ToDo: Legger til skatt på utbytte fra salg av en % av disse eiendelene her (De har neppe en inntekt, men det skal også fikses fint)
            #print "ATY: $CashflowTaxableAmount        += TFI:$fireAssetIncomeAmount * PTY:$DecimalTaxableYearly;\n";
            #ToDo: Det er ulik skatt på de ulike typene.
        }

        #NOTE - Lån telles som FIRE inntekt.
        $fireIncomeAmount   = $fireAssetIncomeAmount + $incomeAmount + $this->ArrGet("$path.mortgage.principal") + $this->ArrGet("$path.tax.amountDeductableYearly");; #Percent of asset value + income from asset. HUSK KUN INNTEKTER her
        $fireExpenceAmount  = $expenceAmount + $CashflowTaxableAmount + $this->ArrGet("$path.mortgage.interestAmount");
        $fireCashFlowAmount = round($fireIncomeAmount - $fireExpenceAmount); #Hvor lang er man unna fire målet

        #print "$assetname - FTI: $fireIncomeAmount = FI:$fireCurrentIncome + I:$incomeCurrentAmount + D:$AmountDeductableYearly\n"; #Percent of asset value + income from asset

        ###############################################################
        #Calculate FIRE percent diff
        if($fireExpenceAmount > 0) {
            $firePercentDiff = $fireIncomeAmount / $fireExpenceAmount; #Hvor mange % unna er inntektene å dekke utgiftene.
        } else {
            $firePercentDiff = 1;
        }

        ###############################################################
        #Calculate FIRE Savings amount
        $fireSavingsAmount = 0;
        if(Arr::get($this->fireSavingTypes, $meta['type'])) {
            $fireSavingsAmount = $incomeAmount; #If this asset is a valid saving asset, we add it to the saving amount.
        }

        ###############################################################
        #Calculate FIRE Savings rate
        #Sparerate = Det du nedbetaler i gjeld + det du sparer eller investerer på andre måter / total inntekt (etter skatt).
        $fireSavingsRate = 0;
        #ToDo: Should this be income adjusted for deductions and tax?
        if($incomeAmount > 0) {
            $fireSavingsRate = $fireSavingsAmount / $incomeAmount;
        }

        $this->dataH[$assetname][$year]['fire'] = [
            'percent' => $firePercent,
            'amountIncome' => $fireIncomeAmount,
            'amountExpence' => $fireExpenceAmount,
            'percentDiff' => $firePercentDiff,
            'cashFlow' => $fireCashFlowAmount,
            'savingAmount' => $fireSavingsAmount,
            'savingRate' => $fireSavingsRate,
        ];
    }

    function group() {
        #dd($this->dataH);
        $this->initGroups();


        foreach($this->dataH as $assetname => $assetH) {
            print_r($assetH);
            $meta = $assetH['meta'];
            if(!$meta['active']) continue; #Hopp over de inaktive

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {
                #print "$year\n";
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.amountLoanDeducted");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.amountTaxable");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.amountDeposited");

                $this->setToGroup($year, $meta, $assetH[$year], "asset.percentTax");

                $this->additionToGroup($year, $meta, $assetH[$year], "income.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "expence.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "tax.amountTaxableYearly");
                $this->additionToGroup($year, $meta, $assetH[$year], "tax.amountTaxableRealization");
                $this->additionToGroup($year, $meta, $assetH[$year], "tax.amountDeductableYearly");
                $this->additionToGroup($year, $meta, $assetH[$year], "tax.amountDeductableRealization");
                $this->additionToGroup($year, $meta, $assetH[$year], "cashflow.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "cashflow.amountAccumulated");

                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.payment");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.paymentExtra");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.interestAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.principal");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.balance");

                $this->additionToGroup($year, $meta, $assetH[$year], "potential.income"); #Beregnet potensiell inntekt slik bankene ser det.
                $this->additionToGroup($year, $meta, $assetH[$year], "potential.loan"); #Beregner maks potensielt lån på 5 x inntekt.

                $this->additionToGroup($year, $meta, $assetH[$year], "fire.amountIncome");
                $this->additionToGroup($year, $meta, $assetH[$year], "fire.amountExpence");
                $this->additionToGroup($year, $meta, $assetH[$year], "fire.savingAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "fire.cashFlow");
            }
        }

        #More advanced calculations on numbers other than amount that can not just be added and all additions are done in advance so we work on complete numbers
        #FireSavingrate as a calculation of totals,
        #FIX: tax calculations/deductions where a fixed deduxtion is uses, or a deduction based on something

        for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

            $this->groupFireSaveRate($year);
            $this->groupFirePercentDiff($year);
            $this->groupDebtCapacity($year);
            $this->groupFortuneTax($year);
            #FIX, later correct tax handling on the totals ums including deductions
        }

        #print "group\n";
        $this->assetTypeSpread();
    }

    private function assetTypeSpread() {

        foreach ($this->groupH as $type => $asset) {
            if(Arr::get($this->assetSpreadTypes, $type)) {
                #print "$type\n";
                foreach ($asset as $year => $data) {
                    $amount = round(Arr::get($data, "asset.amount", 0));
                    #print "$type:$year:$amount\n";
                    $this->statisticsH[$year][$type]['amount'] = $amount;
                    $this->statisticsH[$year]['total']['amount'] = Arr::get($this->statisticsH, "$year.total.amount", 0) + $amount;
                }

                #Generate % spread
                foreach ($this->statisticsH as $year => $typeH) {
                    foreach ($typeH as $typename => $data) {
                        if($typeH['total']['amount'] > 0) {
                            $this->statisticsH[$year][$typename]['percent'] = round(($data['amount'] / $typeH['total']['amount'])*100);
                        } else {
                            $this->statisticsH[$year][$typename]['percent'] = 0;
                        }
                        #print_r($data);
                        #print "$year=" . $data['amount'] . "\n";
                    }
                }
            }
        }
        #print_r($this->statisticsH);
    }

    private function groupFortuneTax(int $year)
    {
        #ToDo - fortune tax sybtraction level support.

        list($fortuneTaxAmount, $fortuneTaxPercent) = $this->tax->fortuneTaxGroupCalculation('total', Arr::get($this->totalH, "$year.fortune.taxableAmount", 0), $year);
        Arr::set($this->totalH, "$year.fortune.taxAmount", $fortuneTaxAmount);

        list($fortuneTaxAmount, $fortuneTaxPercent) = $this->tax->fortuneTaxGroupCalculation('company', Arr::get($this->companyH, "$year.fortune.taxableAmount", 0), $year);
        Arr::set($this->companyH, "$year.fortune.taxAmount", $fortuneTaxAmount);

        list($fortuneTaxAmount, $fortuneTaxPercent) = $this->tax->fortuneTaxGroupCalculation('private', Arr::get($this->privateH, "$year.fortune.taxableAmount", 0), $year);
        Arr::set($this->privateH, "$year.fortune.taxAmount", $fortuneTaxAmount);
    }



    private function groupDebtCapacity(int $year)
    {
        Arr::set($this->totalH, "$year.potential.debtCapacity", Arr::get($this->totalH, "$year.potential.loan", 0) - Arr::get($this->totalH, "$year.mortgage.balance", 0));

        Arr::set($this->companyH, "$year.potential.debtCapacity", Arr::get($this->companyH, "$year.potential.loan", 0) - Arr::get($this->companyH, "$year.mortgage.balance", 0));

        Arr::set($this->privateH, "$year.potential.debtCapacity", Arr::get($this->privateH, "$year.potential.loan", 0) - Arr::get($this->privateH, "$year.mortgage.balance", 0));
    }

    #Calculates on data that is summed up in the group
    #FIX: Much better if we could use calculus here to reduce number of methods, but to advanced for the moment.
    function groupFireSaveRate(int $year){
        if(Arr::get($this->totalH, "$year.fire.amountIncome", 0) > 0) {
            Arr::set($this->totalH, "$year.fire.savingRate", Arr::get($this->totalH, "$year.fire.cashFlow", 0) / Arr::get($this->totalH, "$year.fire.amountIncome", 0));
        }
        if(Arr::get($this->companyH, "$year.fire.amountIncome", 0) > 0) {
            Arr::set($this->companyH, "$year.fire.savingRate", Arr::get($this->companyH, "$year.fire.cashFlow", 0) / Arr::get($this->companyH, "$year.fire.amountIncome", 0));
        }
        if(Arr::get($this->privateH, "$year.fire.amountIncome", 0) > 0) {
            Arr::set($this->privateH, "$year.fire.savingRate", Arr::get($this->privateH, "$year.fire.cashFlow", 0) / Arr::get($this->privateH, "$year.fire.amountIncome", 0));
        }
        #FIX: Loop this out for groups.
        #foreach($this->groupH){
            #$this->groupH;
        #}
    }

    private function groupFirePercentDiff(int $year){


        if(Arr::get($this->totalH, "$year.fire.amountExpence", 0) > 0) {
            Arr::set($this->totalH, "$year.fire.percentDiff", Arr::get($this->totalH, "$year.fire.amountIncome", 0) / Arr::get($this->totalH, "$year.fire.amountExpence", 0));
        }
        if(Arr::get($this->companyH, "$year.fire.amountExpence", 0) > 0) {
            Arr::set($this->companyH, "$year.fire.percentDiff", Arr::get($this->companyH, "$year.fire.amountIncome", 0) / Arr::get($this->companyH, "$year.fire.amountExpence", 0));
        }
        if(Arr::get($this->privateH, "$year.fire.amountExpence", 0) > 0) {
            Arr::set($this->privateH, "$year.fire.percentDiff", Arr::get($this->privateH, "$year.fire.amountIncome", 0) / Arr::get($this->privateH, "$year.fire.amountExpence", 0));
        }
        #FIX: Loop this out for groups.
        #foreach($this->groupH){
        #$this->groupH;
        #}
    }

    private function additionToGroup(int $year, array $meta, array $data, string $dotpath) {
        #"fortune.taxableAmount"
        #if(Arr::get($data, $dotpath)) {

            #Just to create an empty object, if it has no values.
            Arr::set($this->totalH, "$year.$dotpath", Arr::get($this->totalH, "$year.$dotpath", 0) + Arr::get($data, $dotpath,0));
            #print "Addtogroup:  " . Arr::get($this->totalH, "$year.$dotpath") . " = " . Arr::get($this->totalH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";


            #Company
            if (Arr::get($meta, 'group') == 'company') {
                #Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene
                Arr::set($this->companyH, "$year.$dotpath", Arr::get($this->companyH, "$year.$dotpath", 0) + Arr::get($data, $dotpath,0));
            }

            #Private
            if (Arr::get($meta, 'group') == 'private') {
                #Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene.
                Arr::set($this->privateH, "$year.$dotpath", Arr::get($this->privateH, "$year.$dotpath", 0) + Arr::get($data, $dotpath,0));
                #print "private: $year.$dotpath :  " . Arr::get($this->privateH, "$year.$dotpath") . " = " . Arr::get($this->privateH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";
            }

            #Grouping
            $grouppath = Arr::get($meta, 'group') . ".$year.$dotpath";
            $typepath = Arr::get($meta, 'type') . ".$year.$dotpath";
            Arr::set($this->groupH, $grouppath, Arr::get($this->groupH, $grouppath, 0) + Arr::get($data, $dotpath,0));
            Arr::set($this->groupH, $typepath, Arr::get($this->groupH, $typepath, 0) + Arr::get($data, $dotpath,0));
        #} elseif($dotpath == 'fortune.taxableAmount') {
        #    print "additionToGroup($year, $dotpath) empty\n";
        #}
    }

    private function setToGroup(int $year, array $meta, array $data, string $dotpath) {

        if(Arr::get($data, $dotpath)) {

            #Just to create an empty object, if it has no values.
            Arr::set($this->totalH, "$year.$dotpath", Arr::get($data, $dotpath));
            #print "Addtogroup:  " . Arr::get($this->totalH, "$year.$dotpath") . " = " . Arr::get($this->totalH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";


            #Company
            if (Arr::get($meta, 'group') == 'company') {
                #Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene
                Arr::set($this->companyH, "$year.$dotpath", Arr::get($data, $dotpath));
            }

            #Private
            if (Arr::get($meta, 'group') == 'private') {
                #Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene.
                Arr::set($this->privateH, "$year.$dotpath", Arr::get($data, $dotpath));
                #print "private: $year.$dotpath :  " . Arr::get($this->privateH, "$year.$dotpath") . " = " . Arr::get($this->privateH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";
            }

            #Grouping
            $grouppath = Arr::get($meta, 'group') . ".$year.$dotpath";
            $typepath = Arr::get($meta, 'type') . ".$year.$dotpath";
            Arr::set($this->groupH, $grouppath, Arr::get($data, $dotpath));
            Arr::set($this->groupH, $typepath, Arr::get($data, $dotpath));
        }
    }

    private function initGroups() {
        #Just to get the sorting right, its bettert to start with an emplty structure in correct yearly order

        for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {
            Arr::set($this->privateH, "$year.asset.amount", 0);
            Arr::set($this->companyH, "$year.asset.amount", 0);
        }

        #FIX: Loop over groups also
    }
}
