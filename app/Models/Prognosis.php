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

        foreach($this->config as $assetname => $assetconfig) {

            if ($assetname == 'meta') {
                continue;
            }; #Hopp over metadata, reserved keyword meta.
            print "Asset: $assetname\n";

            #Store all metadata about the asset, the rest is yearly calculations
            $this->dataH[$assetname]['meta'] = $this->ArrGetConfig("$assetname.meta"); #Copy metadata into dataH

            if(!$this->ArrGetConfig("$assetname.meta.active")) continue; #Jump past inactive assets

            $taxtype = $this->ArrGetConfig("$assetname.meta.tax"); #How tax is to be calculated for this asset

            $assetMarketAmount = 0;
            $assetMarketPrevAmount = 0;
            $assetEquityAmount = 0;
            $assetPaidAmount = 0;
            $assetAcquisitionAmount = 0;
            $realizationTaxAmount = 0;
            $assetFirstYear = 0;
            $assetRule = null;
            $assetChangerateDecimal = 0;
            $assetChangeratePercent = 0;
            $assetChangerateAmount = "";
            $assetRepeat= false;
            $assetPrevRepeat= false;
            $assetTransfer = null;
            $assetPrevTransfer = null;
            $assetRule = null;
            $assetPrevRule = null;
            $assetAggregatedDepositedAmount = 0;

            $incomeAmount = 0;
            $incomePrevAmount = 0;
            $incomeChangerateDecimal = 0;
            $incomeChangeratePercent = 0;
            $incomeChangerateAmount = "";
            $incomeRule = null;
            $incomePrevRepeat = false;
            $incomeRepeat = false;
            $incomeTransfer = null;
            $incomePrevTransfer = null;
            $incomeRule = null;
            $incomePrevRule = null;

            $expenceAmount = 0;
            $expencePrevAmount = 0;
            $expenceTaxDeductableAmount = 0;
            $expenceChangerateDecimal = 0;
            $expenceChangeratePercent = 0;
            $expenceChangerateAmount = "";
            $expenceRule = null;
            $expenceRepeat = false;
            $expencePrevRepeat = false;
            $expenceTransfer = null;
            $expencePrevTransfer = null;
            $expenceRule = null;
            $expencePrevRule = null;

            $restAccumulated = 0;

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

                $prevYear = $year - 1;

                ########################################################################################################
                #TAX
                $taxDecimal = $this->tax->getTaxYearly($taxtype, $year);
                $taxRealizationDecimal =  $this->tax->getTaxRealization($taxtype, $year);
                $taxDeductableDecimal = $this->tax->getTaxYearly($taxtype, $year);
                $taxRealizationDeductableDecimal = $this->tax->getTaxRealization($taxtype, $year);
                $taxableFortuneDecimal = $this->tax->getTaxableFortune($taxtype, $year);

                ########################################################################################################
                #Expence
                $expenceTransfer = $this->repeatValue($assetname, $year, 'expence', 'transfer');
                $expenceRule = $this->repeatValue($assetname, $year, 'expence', 'rule');
                $expenceRepeat = $this->repeatValue($assetname, $year, 'expence', 'repeat');
                $expenceChangerate = $this->repeatValue($assetname, $year, 'expence', 'changerate');

                list($expenceChangeratePercent, $expenceChangerateDecimal, $expenceChangerateAmount, $expenceExplanation) = $this->changerate->convertChangerate(false, $expenceChangerate, $year, $expenceChangerateAmount);

                $expenceAmount = $this->ArrGetConfig("$assetname.$year.expence.amount"); #Expence is added as a monthly repeat in config

                #print "Expence transfer before: $assetname.$year, expencePrevAmount:$expencePrevAmount, expenceAmount:$expenceAmount\n";
                list($expenceAmount, $expenceDepositedAmount, $expenceRule, $explanation) = $this->helper->adjustAmount(0, $expencePrevAmount, $expenceAmount, 0, $expenceRule,12);
                #print "Expence transfer after: $assetname.$year, expencePrevAmount:$expencePrevAmount, expenceAmount:$expenceAmount\n";
                #print "$year: expenceChangeratePercent = $expenceChangerateDecimal - expence * $expence\n";

                ########################################################################################################
                #Income
                $incomeTransfer = $this->repeatValue($assetname, $year, 'income', 'transfer');
                $incomeRule = $this->repeatValue($assetname, $year, 'income', 'rule');
                $incomeRepeat = $this->repeatValue($assetname, $year, 'income', 'repeat');
                $incomeChangerate = $this->repeatValue($assetname, $year, 'income', 'changerate');

                list($incomeChangeratePercent, $incomeChangerateDecimal, $incomeChangerateAmount, $incomeExplanation) =  $this->changerate->convertChangerate(false, $incomeChangerate, $year, $incomeChangerateAmount);

                $incomeAmount = $this->ArrGetConfig("$assetname.$year.income.amount"); #Income is added as a yearly repeat in config


                list($incomeAmount, $incomeDepositedAmount, $incomeRule, $explanation) = $this->helper->adjustAmount(false, $incomePrevAmount, $incomeAmount, 0, $incomeRule, 12);

                #print "Income transfer before: $assetname.$year, incomeAmount:$incomeAmount, incomeTransfer:$incomeTransfer, incomeRule:$incomeRule\n";
                list($incomeAmount, $incomeTransferedAmount, $incomePrevRule, $incomeExplanation) = $this->transferAmount(false, $assetname, $year, $incomeAmount, 0, $incomeTransfer, $incomeRule, 12);
                #print "Income transfer after: $assetname.$year, incomeAmount:$incomeAmount, incomeTransferedAmount:$incomeTransferedAmount, incomePrevRule:$incomePrevRule, explanation: $incomeExplanation\n";

                ########################################################################################################
                #Mortage - has to be calculated before asset, since we use data from mortgage to calculate asset values correctly.
                $mortgage = $this->ArrGetConfig("$assetname.$year.mortgage"); #Note that Mortgage will be processed from all years frome here to the end - at once in this step. It process the entire mortage not only this year. It will be overwritten be a new mortgage config at a later year.

                if($mortgage) {
                    #Kjører bare dette om mortgage strukturen i json er utfylt
                    $this->dataH = (new Amortization($this->config, $this->changerate, $this->dataH, $mortgage, $assetname, $year))->get();
                }

                ########################################################################################################
                #Assett
                $assetTransfer = $this->repeatValue($assetname, $year, 'asset', 'transfer');
                $assetRule = $this->repeatValue($assetname, $year, 'asset', 'rule');
                $assetRepeat = $this->repeatValue($assetname, $year, 'asset', 'repeat');
                $assetChangerate = $this->repeatValue($assetname, $year, 'asset', 'changerate');
                $assetEquityAmount = $this->repeatValue($assetname, $year, 'asset', 'equityAmount');

                list($assetChangeratePercent, $assetChangerateDecimal, $assetChangerateAmount, $assetExplanation1) = $this->changerate->convertChangerate(false,  $assetChangerate, $year, $assetChangerateAmount);
                #print "$year: " . $this->changerate->decimalToDecimal($assetChangerateDecimal) . "\n";

                #When reading the vaues from config we have to take into consideration that data could already have been transfered to this asset and it exists data in the main data storage.
                $assetMarketAmount      = $this->ArrGetConfig("$assetname.$year.asset.marketAmount") + $this->ArrGet("$assetname.$year.asset.marketAmount");
                $assetAcquisitionAmount = $this->repeatValue($assetname, $year, 'asset', 'acquisitionAmount');

                if($assetAcquisitionAmount <= 0) {
                    $assetAcquisitionAmount = $assetMarketAmount; #If no acquisition amount is set, we assume it is the same as the market amount
                }

                $assetPaidAmount        = round(  $this->ArrGetConfig("$assetname.$year.asset.paidAmount") + $this->ArrGet("$assetname.$year.asset.paidAmount") + $this->ArrGet("$assetname.$prevYear.asset.paidAmount") + $this->ArrGet("$assetname.$year.mortgage.termAmount"));
                if($assetPaidAmount <= 0) {
                    $assetPaidAmount = $assetMarketAmount; #If no paid amount is set, we assume it is the same as the market amount
                }

                if($assetEquityAmount <= 0 && $assetAcquisitionAmount > 0) {
                    $assetEquityAmount = round($assetAcquisitionAmount - $this->ArrGet("$assetname.$year.mortgage.balanceAmount")); #We only calculate the start equity, it should not be recalculated each year.
                    if($assetEquityAmount > 0) {
                        #This happens only once, at start.
                        print "Only once\n";
                        $assetPaidAmount += $assetEquityAmount; #We add the equity to the paid amount, since it is part of the paid amount.
                    }
                }

                $assetTaxAmount = $this->ArrGetConfig("$assetname.$year.asset.taxvalue"); ## FIX ?????

                #print "Asset før: year: $year assetPrevAmount:$assetMarketPrevAmount assetMarketAmount:$assetMarketAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount assetRule:$assetRule\n";
                list($assetMarketAmount, $assetAggregatedDepositedAmount, $assetRule, $assetExplanation2) = $this->helper->adjustAmount(false, $assetMarketPrevAmount, $assetMarketAmount, $assetAggregatedDepositedAmount, $assetRule, 1);
                #print "Asset etter: year:$year assetMarketAmount: $assetMarketAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount, assetRule:$assetRule explanation: $explanation\n";

                #print "Asset transfer before: $assetname.$year, assetMarketAmount:$assetMarketAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount, assetCurrenttransferResource:$assetTransfer, AssetCurrentTransferRule:$assetRule\n";
                list($assetMarketAmount, $assetTransferedAmount, $assetAggregatedDepositedAmount, $assetPrevRule, $assetExplanation3) = $this->transferAmount(false, $assetname, $year, $assetMarketAmount, $assetAggregatedDepositedAmount, $assetTransfer, $assetRule, 1);
                #print "Asset transfer after: $assetname.$year, assetMarketAmount:$assetMarketAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount, assetTransferedAmount:$assetTransferedAmount, assetPrevTransferRule:$assetPrevRule, explanation: $explanation\n";

                if(!$assetAcquisitionAmount && $assetMarketAmount > 0) { #FIX, må lese inn fra konfig hva som er anskaffelseskostnaden
                    $assetAcquisitionAmount = $assetMarketAmount; #FIX: Ta vare på den første verdien vi ser på en asset, da den brukes til skatteberegning ved salg. Må også akkumulere alle innskudd, men ikke verdiøkning.
                    $assetFirstYear = $year; #Ta vare på den første året vi ser en asset, da den brukes til skatteberegning ved salg for å se hvor lenge man har eid den.
                };

                $assetMarketMortgageDeductedAmount = $assetMarketAmount - $this->ArrGet("$assetname.$year.mortgage.balanceAmount");
                #print "PAID: $assetname.$year.asset.paidAmount: " . $assetPaidAmount . " + curPaid: " . $this->ArrGet("$assetname.$year.asset.paidAmount") . " + prevPaid: " . $this->ArrGet("$assetname.$prevYear.asset.paidAmount") . " - assetEquityAmount: $assetEquityAmount\n";

                ########################################################################################################
                #Tax calculations
                #print "$taxtype.$year incomeCurrentAmount: $incomeAmount, expenceCurrentAmount: $expenceAmount\n";
                list($cashflowAmount, $cashflowTaxAmount, $cashflowTaxPercent, $potentialIncomeAmount) = $this->tax->taxCalculationCashflow(true, $taxtype, $year, $incomeAmount, $expenceAmount, $assetMarketAmount, $assetTaxAmount, $assetAcquisitionAmount, $assetFirstYear);
                list( $assetTaxableAmount, $assetTaxAmount, $assetTaxableDecimal, $assetTaxDecimal) = $this->tax->taxCalculationFortune($taxtype, $year, $assetMarketAmount, $assetTaxAmount);
                list($realizationTaxableAmount, $realizationTaxAmount, $realizationTaxPercent) = $this->tax->taxCalculationRealization(false, $taxtype, $year, $assetMarketAmount, $assetAcquisitionAmount, $assetFirstYear);


                #Vi må trekke fra formuesskatten fra cashflow
                #$cashflow -= $assetTaxAmount;

                ########################################################################################################
                #Store all data in the dataH structure
                $this->dataH[$assetname][$year]['income'] = [
                    'changerate' => $incomeChangeratePercent,
                    'rule' => $incomeRule,
                    'transfer' => $incomeTransfer,
                    'repeat' => $incomeRepeat,
                    'amount' => $incomeAmount + Arr::get($this->dataH, "$assetname.$year.income.amount", 0),  //Have to add since an amount can be set in the structure already
                    'description' => $this->ArrGetConfig("$assetname.$year.income.description") . $incomeExplanation,

                ];

                $this->dataH[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangeratePercent,
                    'rule' => $expenceRule,
                    'transfer' => $expenceTransfer,
                    'repeat' => $expenceRepeat,
                    'amount' => $expenceAmount + Arr::get($this->dataH, "$assetname.$year.expence.amount", 0), //Have to add since an amount can be set in the structure already
                    'description' => $this->ArrGetConfig("$assetname.$year.expence.description") . $expenceExplanation,
                ];

                $this->dataH[$assetname][$year]['cashflow'] = [
                    'beforeTaxAmount' => $cashflowAmount,
                    'afterTaxAmount' => $cashflowAmount,
                    'beforeTaxAggregatedAmount' => $cashflowAmount,
                    'afterTaxAggregatedAmount' => $cashflowAmount,
                    'taxAmount' => $cashflowTaxAmount,
                    'taxDecimal' => $cashflowTaxPercent,
                ];
                #Fix before and after tax cashflow calculations.

                $this->dataH[$assetname][$year]['asset'] = [
                    'marketAmount' => $assetMarketAmount,
                    'acquisitionAmount' => $assetAcquisitionAmount,
                    'marketMortgageDeductedAmount' => $assetMarketMortgageDeductedAmount,
                    'equityAmount' => $assetEquityAmount,
                    'paidAmount' => $assetPaidAmount,
                    'taxableDecimal' => $assetTaxableDecimal,
                    'taxableAmount' => $assetTaxableAmount,
                    'taxDecimal' => $assetTaxDecimal,
                    'taxAmount' => $assetTaxAmount,
                    'changerate' => $assetChangeratePercent,
                    'rule' => $assetRule,
                    'transfer' => $assetTransfer,
                    'repeat' => $assetRepeat,
                    'realizationTaxableAmount' => $realizationTaxableAmount,
                    'realizationTaxAmount' => $realizationTaxAmount,
                    'realizationTaxDecimal' => $realizationTaxPercent,
                    'description' => $this->ArrGetConfig("$assetname.$year.asset.description") . " Asset rule " . $assetRule . $assetExplanation1 . $assetExplanation2 . $assetExplanation3,
                ];

                #Calculate the potential max loan you can handle base on income, tax adjusted - as seen from the bank.
                #print "$assetname - p:$potentialIncome = i:$incomeAmount - t:$CashflowTaxableAmount\n";
                #FIX - Move potential to post processing.
                $this->dataH[$assetname][$year]['potential'] = [
                    'incomeAmount' => $potentialIncomeAmount,
                    'mortgageAmount' => $potentialIncomeAmount * 5
                ];

                #FIX Mortgage tax calculation.
                #$taxDeductableAmount' => -$expenceTaxDeductableAmount,
                #$taxDeductableDecimal' => $taxDeductableDecimal,

                ########################################################################################################
                if($expenceRepeat) {
                    $expencePrevAmount      = $expenceAmount * $expenceChangerateDecimal;
                    #print "EXPENCE REPEAT: $assetname.$year: expenceAmount: $expenceAmount, expencePrevAmount: $expencePrevAmount * $expenceChangerateDecimal\n";
                } else {
                    $expenceAmount              = 0;
                    $expencePrevAmount          = 0;
                    $expenceChangerateAmount    = 0;
                    $expenceChangerateDecimal   = null;
                    $expenceChangeratePercent   = null;
                    $expenceRule                = null;
                    $expenceTransfer            = null;
                }

                if($incomeRepeat) {
                    $incomePrevAmount       = $incomeAmount * $incomeChangerateDecimal;
                } else {
                    $incomeAmount               = 0;
                    $incomePrevAmount           = 0;
                    $incomeChangerateAmount     = 0;
                    $incomeChangerateDecimal    = null;
                    $incomeChangeratePercent    = null;
                    $incomeTransfer             = null;
                    $incomeRule                 = null;
                }

                if($assetRepeat) {
                    $assetMarketPrevAmount   = round($assetMarketAmount * $assetChangerateDecimal);
                    $assetPrevTaxAmount = round($assetTaxAmount * $assetChangerateDecimal);
                    #print "REPEAT: $assetname.$year: assetMarketAmount: $assetMarketAmount, assetMarketPrevAmount: $assetMarketPrevAmount * $assetChangerateDecimal\n";
                } else {
                    $assetMarketAmount          = 0;
                    $assetMarketPrevAmount      = 0;
                    $assetPrevTaxAmount         = 0;
                    $assetChangerateAmount      = 0;
                    $assetChangerateDecimal     = null;
                    $assetChangeratePercent     = null;
                    $assetRule                  = null;
                    $assetTransfer              = null;
                    $assetAggregatedDepositedAmount = 0;

                }

            } #Year loop finished here.


            $this->postProcess();

        } #End loop over assets

        $this->postProcess();
        $this->group();
        #print_r($this->dataH);
    }


    public function repeatValue(string $assetname, int $year, string $type, string $variable)
    {
        $prevYear = $year - 1;

        $value = Arr::get($this->config, "$assetname.$year.$type.$variable", null); //Retrieve value from config current year
        if (!isset($value)) {
            $value = $this->ArrGet("$assetname.$prevYear.$type.$variable"); //Retrive value from dataH (processed data, previous year
        }

        #print "retrieveValue: $assetname.$year.$type.$variable: $value\n";

        return $value;
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
    public function transferAmount(bool $debug, string $assetname, int $year,  ?int $amount, ?int $depositedAmount, ?string $transferResource, ?string $transferRule, int $factor = 1)
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

            #print_r($assetH);
            $meta = $assetH['meta'];
            if(!$meta['active']) continue; #Hopp over de inaktive

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

                $datapath = "$assetname.$year";
                $this->postProcessTaxYearly($datapath);
                $this->postProcessIncomeYearly($datapath);
                $this->postProcessExpenceYearly($datapath);
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

    #Special Arr get that onlye gets data from configH to make cleaner code.
    function ArrGetConfig(string $path){
        return Arr::get($this->config, $path, 0);
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
    }

    /**
     * Modifies the yearly cash flow for a given asset and year.
     *
     * @param int $year The year for which to modify the cash flow.
     * @param string $assetName The name of the asset for which to modify the cash flow.
     *
     * @return void
     */
    function postProcessIncomeYearly(string $path)
    {
    }

    function postProcessExpenceYearly(string $path)
    {
        Arr::set($this->dataH, "$path.expence.taxDeductableAmount", $this->ArrGet("$path.mortgage.interestAmount") * 0.22); #FIX. This has to do with Mortgage: Remove hardcoded percentage later to read from tax config
    }

    function postProcessCashFlowYearly(string $path)
    {
        #Free money to spend
        $cashflowAmount = $this->ArrGet("$path.cashflow.incomeAmount") - $this->ArrGet("$path.cashflow.expenceAmount") + $this->ArrGet("$path.expence.taxDeductableAmount") - $this->ArrGet("$path.mortgage.termAmount");

        Arr::set($this->dataH, "$path.cashflow.beforeTaxAmount", $cashflowAmount);
        Arr::set($this->dataH, "$path.cashflow.afterTaxAmount",$cashflowAmount);
        Arr::set($this->dataH, "$path.cashflow.beforeTaxAggregatedAmount",$cashflowAmount);  #FIX: Cashflow is not accumulated now
        Arr::set($this->dataH, "$path.cashflow.afterTaxAggregatedAmount",$cashflowAmount);  #FIX: Cashflow is not accumulated now
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

        if ($this->ArrGet("$path.mortgage.balanceAmount") > 0 && $this->ArrGet("$path.asset.marketAmount") > 0) {
            Arr::set($this->dataH,"$path.asset.mortageRateDecimal", $this->ArrGet("$path.mortgage.balanceAmount") / $this->ArrGet("$path.asset.marketAmount"));
        } else {
            Arr::set($this->dataH,"$path.asset.mortageRateDecimal", 0);
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
        $assetMarketAmount = $this->ArrGet("$path.asset.marketAmount");
        $incomeAmount = $this->ArrGet("$path.income.amount");
        $expenceAmount = $this->ArrGet("$path.expence.amount");

        #FIX: Something is wrong with this classification, and automatically calculating sales of everything not in this list.
        if(Arr::get($this->firePartSalePossibleTypes, $meta['type'])) {
            #Her kan vi selge biter av en asset (meta tagge opp det istedenfor tro?
            $firePercent            = 0.04; #ToDo: 4% av en salgbar asset verdi. FIX: Konfigurerbart FIRE tall.
            $fireAssetIncomeAmount  = $assetMarketAmount * $firePercent; #Only asset value
            $CashflowTaxableAmount  = $fireAssetIncomeAmount * $this->ArrGet("$path.income.taxableDecimal"); #ToDo: Legger til skatt på utbytte fra salg av en % av disse eiendelene her (De har neppe en inntekt, men det skal også fikses fint)
            #print "ATY: $CashflowTaxableAmount        += TFI:$fireAssetIncomeAmount * PTY:$DecimalTaxableYearly;\n";
            #ToDo: Det er ulik skatt på de ulike typene.
        }

        #NOTE - Lån telles som FIRE inntekt.
        $fireIncomeAmount   = $fireAssetIncomeAmount + $incomeAmount + $this->ArrGet("$path.mortgage.principalAmount") + $this->ArrGet("$path.expence.taxDeductableAmount"); #Percent of asset value + income from asset. HUSK KUN INNTEKTER her
        $fireExpenceAmount  = $expenceAmount + $CashflowTaxableAmount + $this->ArrGet("$path.mortgage.interestAmount");
        $fireCashFlowAmount = round($fireIncomeAmount - $fireExpenceAmount); #Hvor lang er man unna fire målet

        #print "$assetname - FTI: $fireIncomeAmount = FI:$fireCurrentIncome + I:$incomeAmount + D:$deductableYearlyAmount\n"; #Percent of asset value + income from asset

        ###############################################################
        #Calculate FIRE percent diff
        if($fireExpenceAmount > 0) {
            $fireDiffPercent = $fireIncomeAmount / $fireExpenceAmount; #Hvor mange % unna er inntektene å dekke utgiftene.
        } else {
            $fireDiffPercent = 1;
        }

        ###############################################################
        #Calculate FIRE Savings amount
        $fireSavingAmount = 0;
        if(Arr::get($this->fireSavingTypes, $meta['type'])) {
            $fireSavingAmount = $incomeAmount; #If this asset is a valid saving asset, we add it to the saving amount.
        }

        ###############################################################
        #Calculate FIRE Savings rate
        #Sparerate = Det du nedbetaler i gjeld + det du sparer eller investerer på andre måter / total inntekt (etter skatt).
        $fireSavingRate = 0;
        #ToDo: Should this be income adjusted for deductions and tax?
        if($incomeAmount > 0) {
            $fireSavingRate = $fireSavingAmount / $incomeAmount;
        }

        $this->dataH[$assetname][$year]['fire'] = [
            'percent' => $firePercent,
            'incomeAmount' => $fireIncomeAmount,
            'expenceAmount' => $fireExpenceAmount,
            'diffPercent' => $fireDiffPercent,
            'cashFlowAmount' => $fireCashFlowAmount,
            'savingAmount' => $fireSavingAmount,
            'savingRate' => $fireSavingRate,
        ];
    }

    function group() {
        #dd($this->dataH);
        $this->initGroups();


        foreach($this->dataH as $assetname => $assetH) {
            #print_r($assetH);
            $meta = $assetH['meta'];
            if(!$meta['active']) continue; #Hopp over de inaktive

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {
                #print "$year\n";

                #$this->setToGroup($year, $meta, $assetH[$year], "asset.taxDecimal");

                $this->additionToGroup($year, $meta, $assetH[$year], "income.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "income.taxAmount");

                $this->additionToGroup($year, $meta, $assetH[$year], "expence.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "expence.taxDeductableAmount");

                $this->additionToGroup($year, $meta, $assetH[$year], "cashflow.beforeTaxAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "cashflow.afterTaxAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "cashflow.beforeTaxAggregatedAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "cashflow.afterTaxAggregatedAmount");

                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.interestAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.principalAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.balanceAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.extraDownpaymentAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "mortgage.gebyrAmount");

                $this->additionToGroup($year, $meta, $assetH[$year], "asset.marketAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.mortgageDeductedAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.aggregatedDepositedAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.originalAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.taxableAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.taxAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.realizationTaxableAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.realizationTaxDeductableAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.originalAmount");

                $this->additionToGroup($year, $meta, $assetH[$year], "potential.incomeAmount"); #Beregnet potensiell inntekt slik bankene ser det.
                $this->additionToGroup($year, $meta, $assetH[$year], "potential.mortgageAmount"); #Beregner maks potensielt lån på 5 x inntekt.

                $this->additionToGroup($year, $meta, $assetH[$year], "fire.incomeAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "fire.expenceAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "fire.savingAmount");
                $this->additionToGroup($year, $meta, $assetH[$year], "fire.cashFlowAmount");
            }
        }

        #More advanced calculations on numbers other than amount that can not just be added and all additions are done in advance so we work on complete numbers
        #FireSavingrate as a calculation of totals,
        #FIX: tax calculations/deductions where a fixed deduxtion is uses, or a deduction based on something

        for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

            $this->groupFireSaveRate($year);
            $this->groupFirediffPercent($year);
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
                    $amount = round(Arr::get($data, "asset.marketAmount", 0));
                    #print "$type:$year:$amount\n";
                    $this->statisticsH[$year][$type]['amount'] = $amount;
                    $this->statisticsH[$year]['total']['amount'] = Arr::get($this->statisticsH, "$year.total.amount", 0) + $amount;
                }

                #Generate % spread
                foreach ($this->statisticsH as $year => $typeH) {
                    foreach ($typeH as $typename => $data) {
                        if($typeH['total']['amount'] > 0) {
                            $this->statisticsH[$year][$typename]['decimal'] = round(($data['amount'] / $typeH['total']['amount'])*100);
                        } else {
                            $this->statisticsH[$year][$typename]['decimal'] = 0;
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

        list($assetTaxAmount, $fortuneTaxDecimal) = $this->tax->fortuneTaxGroupCalculation('total', Arr::get($this->totalH, "$year.asset.taxableAmount", 0), $year);
        Arr::set($this->totalH, "$year.asset.taxAmount", $assetTaxAmount);

        list($assetTaxAmount, $fortuneTaxDecimal) = $this->tax->fortuneTaxGroupCalculation('company', Arr::get($this->companyH, "$year.asset.taxableAmount", 0), $year);
        Arr::set($this->companyH, "$year.asset.taxAmount", $assetTaxAmount);

        list($assetTaxAmount, $fortuneTaxDecimal) = $this->tax->fortuneTaxGroupCalculation('private', Arr::get($this->privateH, "$year.asset.taxableAmount", 0), $year);
        Arr::set($this->privateH, "$year.asset.taxAmount", $assetTaxAmount);
    }



    private function groupDebtCapacity(int $year)
    {
        Arr::set($this->totalH, "$year.potential.mortgageAmount", Arr::get($this->totalH, "$year.potential.mortgageAmount", 0) - Arr::get($this->totalH, "$year.mortgage.balance", 0));

        Arr::set($this->companyH, "$year.potential.mortgageAmount", Arr::get($this->companyH, "$year.potential.mortgageAmount", 0) - Arr::get($this->companyH, "$year.mortgage.balance", 0));

        Arr::set($this->privateH, "$year.potential.mortgageAmount", Arr::get($this->privateH, "$year.potential.mortgageAmount", 0) - Arr::get($this->privateH, "$year.mortgage.balance", 0));
    }

    #Calculates on data that is summed up in the group
    #FIX: Much better if we could use calculus here to reduce number of methods, but to advanced for the moment.
    function groupFireSaveRate(int $year){
        if(Arr::get($this->totalH, "$year.fire.incomeAmount", 0) > 0) {
            Arr::set($this->totalH, "$year.fire.savingRate", Arr::get($this->totalH, "$year.fire.cashFlowAmount", 0) / Arr::get($this->totalH, "$year.fire.incomeAmount", 0));
        }
        if(Arr::get($this->companyH, "$year.fire.incomeAmount", 0) > 0) {
            Arr::set($this->companyH, "$year.fire.savingRate", Arr::get($this->companyH, "$year.fire.cashFlowAmount", 0) / Arr::get($this->companyH, "$year.fire.incomeAmount", 0));
        }
        if(Arr::get($this->privateH, "$year.fire.incomeAmount", 0) > 0) {
            Arr::set($this->privateH, "$year.fire.savingRate", Arr::get($this->privateH, "$year.fire.cashFlowAmount", 0) / Arr::get($this->privateH, "$year.fire.incomeAmount", 0));
        }
        #FIX: Loop this out for groups.
        #foreach($this->groupH){
            #$this->groupH;
        #}
    }

    private function groupFirediffPercent(int $year){


        if(Arr::get($this->totalH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($this->totalH, "$year.fire.diffDecimal", Arr::get($this->totalH, "$year.fire.incomeAmount", 0) / Arr::get($this->totalH, "$year.fire.expenceAmount", 0));
        }
        if(Arr::get($this->companyH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($this->companyH, "$year.fire.diffDecimal", Arr::get($this->companyH, "$year.fire.incomeAmount", 0) / Arr::get($this->companyH, "$year.fire.expenceAmount", 0));
        }
        if(Arr::get($this->privateH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($this->privateH, "$year.fire.diffDecimal", Arr::get($this->privateH, "$year.fire.incomeAmount", 0) / Arr::get($this->privateH, "$year.fire.expenceAmount", 0));
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
            Arr::set($this->privateH, "$year.asset.marketAmount", 0);
            Arr::set($this->companyH, "$year.asset.marketAmount", 0);
        }

        #FIX: Loop over groups also
    }
}
