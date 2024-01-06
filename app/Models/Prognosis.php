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
                continue;
            } //Hopp over metadata, reserved keyword meta.
            echo "Asset: $assetname\n";

            //Store all metadata about the asset, the rest is yearly calculations
            $this->dataH[$assetname]['meta'] = $this->ArrGetConfig("$assetname.meta"); //Copy metadata into dataH

            if (! $this->ArrGetConfig("$assetname.meta.active")) {
                continue;
            } //Jump past inactive assets

            $taxtype = $this->ArrGetConfig("$assetname.meta.tax"); //How tax is to be calculated for this asset

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
            $assetChangerateAmount = '';
            $assetRepeat = false;
            $assetPrevRepeat = false;
            $assetTransfer = null;
            $assetPrevTransfer = null;
            $assetRule = null;
            $assetPrevRule = null;
            $assetAggregatedDepositedAmount = 0;

            $incomeAmount = 0;
            $incomePrevAmount = 0;
            $incomeChangerateDecimal = 0;
            $incomeChangeratePercent = 0;
            $incomeChangerateAmount = '';
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
            $expenceChangerateAmount = '';
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

                //#######################################################################################################
                //TAX
                $taxDecimal = $this->tax->getTaxYearly($taxtype, $year);
                $taxRealizationDecimal = $this->tax->getTaxRealization($taxtype, $year);
                $taxDeductableDecimal = $this->tax->getTaxYearly($taxtype, $year);
                $taxRealizationDeductableDecimal = $this->tax->getTaxRealization($taxtype, $year);
                $taxableFortuneDecimal = $this->tax->getTaxableFortune($taxtype, $year);

                //#######################################################################################################
                //Expence
                $expenceAmount = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'amount');
                $expenceFactor = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'factor'); //We do not store this in dataH, we only use it to upscale amounts once to yearly amounts
                $expenceRule = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'rule');
                $expenceTransfer = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'transfer');
                $expenceSource = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'source');
                $expenceRepeat = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'repeat');
                $expenceChangerate = $this->configOrPrevValue(false, $assetname, $year, 'expence', 'changerate');

                #print "Expence adjust before: $assetname.$year, expencePrevAmount:$expencePrevAmount, expenceAmount:$expenceAmount\n";
                [$expenceAmount, $expenceDepositedAmount, $expenceRule, $explanation] = $this->applyRule(false, $year, $expenceAmount, 0, $expenceRule, $expenceTransfer, $expenceSource, $expenceFactor);
                #print "Expence adjust after: $assetname.$year, expencePrevAmount:$expencePrevAmount, expenceAmount:$expenceAmount\n";
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

                #print "Income adjust before: $assetname.$year, incomeAmount:$incomeAmount\n";
                [$incomeAmount, $incomeDepositedAmount, $incomeRule, $explanation] = $this->applyRule(false, $year, $incomeAmount, 0, $incomeRule, $incomeTransfer, $incomeSource, $incomeFactor);
                #print "Income adjust after: $assetname.$year, incomeAmount:$incomeAmount\n";

                //print "Income transfer before: $assetname.$year, incomeAmount:$incomeAmount, incomeTransfer:$incomeTransfer, incomeRule:$incomeRule\n";
                //[$incomeAmount, $incomeTransferedAmount, $incomePrevRule, $incomeExplanation] = $this->transferAmount(false, $assetname, $year, $incomeAmount, 0, $incomeRule, $incomeTransfer, $incomeSource, $incomeFactor);
                //print "Income transfer after: $assetname.$year, incomeAmount:$incomeAmount, incomeTransferedAmount:$incomeTransferedAmount, incomePrevRule:$incomePrevRule, explanation: $incomeExplanation\n";

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
                $assetMarketAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'marketAmount');
                $assetEquityAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'equityAmount');
                $assetPaidAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'paidAmount');
                $assetRule = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'rule');
                $assetTransfer = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'transfer');
                $assetSource = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'source');
                $assetRepeat = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'repeat');
                $assetChangerate = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'changerate');

                [$assetChangeratePercent, $assetChangerateDecimal, $assetChangerateAmount, $assetExplanation1] = $this->changerate->getChangerate(false, $assetChangerate, $year, $assetChangerateAmount);
                //print "$year: " . $this->changerate->decimalToDecimal($assetChangerateDecimal) . "\n";

                #print "\nAsset før: $assetname.$year assetPrevAmount:$assetMarketPrevAmount assetMarketAmount:$assetMarketAmount, assetRule:$assetRule\n";
                [$assetMarketAmount, $paidExtraAmount, $assetRule, $assetExplanation2] = $this->applyRule(false, $year, $assetMarketAmount, 0, $assetRule, $assetTransfer, $assetSource, 1);
                #print "Asset etter: $assetname.$year assetMarketAmount: $assetMarketAmount, paidExtraAmount: $paidExtraAmount, assetRule:$assetRule explanation: $explanation\n";

                $assetMarketAmount = round($assetMarketAmount * $assetChangerateDecimal);

                //When reading the vaues from config we have to take into consideration that data could already have been transfered to this asset and it exists data in the main data storage.
                $assetMarketAmount = round($assetMarketAmount + $this->ArrGet("$assetname.$year.asset.marketAmount")); //FIX - is it to late to add transfered data here, should it be before applyRule+ Sequence problem?

                $assetAcquisitionAmount = $this->configOrPrevValue(false, $assetname, $year, 'asset', 'acquisitionAmount');

                if ($assetAcquisitionAmount <= 0) {
                    $assetAcquisitionAmount = $assetMarketAmount; //If no acquisition amount is set, we assume it is the same as the market amount
                }

                $assetPaidAmount = round($this->ArrGetConfig("$assetname.$year.asset.paidAmount") + $this->ArrGet("$assetname.$year.asset.paidAmount") + $this->ArrGet("$assetname.$prevYear.asset.paidAmount") + $this->ArrGet("$assetname.$year.mortgage.termAmount") + $paidExtraAmount);
                print "assetPaidAmount: $assetPaidAmount, prevYear.asset.paidAmount: " . $this->ArrGet("$assetname.$prevYear.asset.paidAmount") . "\n";

                if ($assetPaidAmount < 0) {
                    $assetPaidAmount = 0; //Can not be negative
                }

                if ($assetEquityAmount <= 0 && $assetAcquisitionAmount > 0) {
                    $assetEquityAmount = round($assetAcquisitionAmount - $this->ArrGet("$assetname.$year.mortgage.amount"));

                    if ($assetEquityAmount > 0) {
                        //This happens only once, at start.
                        $assetPaidAmount = $assetEquityAmount + $this->ArrGet("$assetname.$year.mortgage.termAmount"); //We add the equity to the paid amount, since it is part of the paid amount.
                        echo "Only once: assetPaidAmount: $assetPaidAmount\n";
                    }
                }

                if ($assetMarketAmount <= 0) {
                    $assetEquityAmount = 0;
                    //FIX. Maybe not neccessary when runnong cpountdown on transfers? Paid should be reduced accordingly until zero.
                    $assetAcquisitionAmount = 0;
                    $assetPaidAmount = 0;
                }
                $assetTaxAmount = $this->ArrGetConfig("$assetname.$year.asset.taxvalue"); //# FIX ?????

                #echo "Asset transfer before: $assetname.$year, assetMarketAmount:$assetMarketAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount, assetTransfer:$assetTransfer, assetRule:$assetRule\n";
                //[$assetMarketAmount, $assetTransferedAmount, $assetAggregatedDepositedAmount, $assetRule, $assetExplanation3] = $this->transferAmount(true, $assetname, $year, $assetMarketAmount, $assetAggregatedDepositedAmount, $assetRule, $assetTransfer, $assetSource , 1);
                #echo "Asset transfer after: $assetname.$year, assetMarketAmount:$assetMarketAmount, assetAggregatedDepositedAmount: $assetAggregatedDepositedAmount, assetTransferedAmount:$assetTransferedAmount, assetRule:$assetRule, explanation: $explanation\n";

                if (! $assetAcquisitionAmount && $assetMarketAmount > 0) { //FIX, må lese inn fra konfig hva som er anskaffelseskostnaden
                    $assetAcquisitionAmount = $assetMarketAmount; //FIX: Ta vare på den første verdien vi ser på en asset, da den brukes til skatteberegning ved salg. Må også akkumulere alle innskudd, men ikke verdiøkning.
                    $assetFirstYear = $year; //Ta vare på den første året vi ser en asset, da den brukes til skatteberegning ved salg for å se hvor lenge man har eid den.
                }

                $assetMarketMortgageDeductedAmount = $assetMarketAmount - $this->ArrGet("$assetname.$year.mortgage.balanceAmount");
                //print "PAID: $assetname.$year.asset.paidAmount: " . $assetPaidAmount . " + curPaid: " . $this->ArrGet("$assetname.$year.asset.paidAmount") . " + prevPaid: " . $this->ArrGet("$assetname.$prevYear.asset.paidAmount") . " - assetEquityAmount: $assetEquityAmount\n";

                //#######################################################################################################
                //Tax calculations
                //print "$taxtype.$year incomeCurrentAmount: $incomeAmount, expenceCurrentAmount: $expenceAmount\n";
                [$cashflowAmount, $cashflowTaxAmount, $cashflowTaxPercent, $potentialIncomeAmount] = $this->tax->taxCalculationCashflow(false, $taxtype, $year, $incomeAmount, $expenceAmount);
                [$assetTaxableAmount, $assetTaxAmount, $assetTaxableDecimal, $assetTaxDecimal] = $this->tax->taxCalculationFortune($taxtype, $year, $assetMarketAmount, $assetTaxAmount);
                [$realizationTaxableAmount, $realizationTaxAmount, $realizationTaxPercent] = $this->tax->taxCalculationRealization(false, $taxtype, $year, $assetMarketAmount, $assetAcquisitionAmount, $assetFirstYear);

                //Vi må trekke fra formuesskatten fra cashflow
                //$cashflow -= $assetTaxAmount;

                //#######################################################################################################
                //Store all data in the dataH structure
                $this->dataH[$assetname][$year]['income'] = [
                    'changerate' => $incomeChangeratePercent,
                    'rule' => $incomeRule,
                    'transfer' => $incomeTransfer,
                    'source' => $incomeSource,
                    'repeat' => $incomeRepeat,
                    'amount' => $incomeAmount + Arr::get($this->dataH, "$assetname.$year.income.amount", 0),  //Have to add since an amount can be set in the structure already
                    'description' => $this->ArrGetConfig("$assetname.$year.income.description").$incomeExplanation,

                ];

                $this->dataH[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangeratePercent,
                    'rule' => $expenceRule,
                    'transfer' => $expenceTransfer,
                    'source' => $expenceSource,
                    'repeat' => $expenceRepeat,
                    'amount' => $expenceAmount + Arr::get($this->dataH, "$assetname.$year.expence.amount", 0), //Have to add since an amount can be set in the structure already
                    'description' => $this->ArrGetConfig("$assetname.$year.expence.description").$expenceExplanation,
                ];

                $this->dataH[$assetname][$year]['cashflow'] = [
                    'beforeTaxAmount' => $cashflowAmount,
                    'afterTaxAmount' => $cashflowAmount,
                    'beforeTaxAggregatedAmount' => $cashflowAmount,
                    'afterTaxAggregatedAmount' => $cashflowAmount,
                    'taxAmount' => $cashflowTaxAmount,
                    'taxDecimal' => $cashflowTaxPercent,
                ];

                //print_r($this->dataH[$assetname][$year]['cashflow']);
                //Fix before and after tax cashflow calculations.

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
                    'source' => $assetSource,
                    'repeat' => $assetRepeat,
                    'realizationTaxableAmount' => $realizationTaxableAmount,
                    'realizationTaxAmount' => $realizationTaxAmount,
                    'realizationTaxDecimal' => $realizationTaxPercent,
                    'description' => $this->ArrGetConfig("$assetname.$year.asset.description").' Asset rule '.$assetRule.$assetExplanation1.$assetExplanation2,
                ];

                //Calculate the potential max loan you can handle base on income, tax adjusted - as seen from the bank.
                //print "$assetname - p:$potentialIncome = i:$incomeAmount - t:$CashflowTaxableAmount\n";
                //FIX - Move potential to post processing.
                $this->dataH[$assetname][$year]['potential'] = [
                    'incomeAmount' => $potentialIncomeAmount,
                    'mortgageAmount' => $potentialIncomeAmount * 5,
                ];

                //#######################################################################################################
                if (!$expenceRepeat) {
                    $expenceAmount = 0;
                    $expencePrevAmount = 0;
                    $expenceChangerateAmount = 0;
                    $expenceChangerateDecimal = null;
                    $expenceChangeratePercent = null;
                    $expenceRule = null;
                    $expenceTransfer = null;
                    $expenceSource = null;
                }

                if (!$incomeRepeat) {
                    $incomeAmount = 0;
                    $incomeChangerateAmount = 0;
                    $incomeChangerateDecimal = null;
                    $incomeChangeratePercent = null;
                    $incomeRule = null;
                    $incomeTransfer = null;
                    $incomeSource = null;
                }

                if ($assetRepeat) {
                    $assetMarketAmount = 0;
                    $assetChangerateAmount = 0;
                    $assetChangerateDecimal = null;
                    $assetChangeratePercent = null;
                    $assetRule = null;
                    $assetTransfer = null;
                    $assetSource = null;
                    $assetAggregatedDepositedAmount = 0;
                }

            } //Year loop finished here.

            $this->postProcess();

        } //End loop over assets

        $this->postProcess();
        $this->group();
        //print_r($this->dataH);
    }


    /**
     * rule can contain:
     * -- +10% - Adds 10% to amount
     * -- -10% - Subtracts 10% from amount
     * -- +1000 - Adds 1000 to amount
     * -- -1000 - Subtracts 1000 from amount
     * -- +1/10 - Adds 1 tenth of the amount yearly
     * -- -1/10 - Subtracts 1 tenth of the amount yearly
     * -- +1|10 - Adds 1 tenth of the amount yearly, and subtracts nevner with one(so next value is 1/9, then 1/8, 1/7 etc)
     * -- -1|10 - Subtracts 1 tenth of the amount yearly. Then subtracts nevner with one. (so next value is 1/9, then 1/8, 1/7 etc). Perfect for usage to i.e empty an asset over 10 years.
     */
    public function applyRule(bool $debug, int $year, float $amount, float $depositedAmount, ?string $rule, ?string $transferTo, ?string $source, int $factor = 1)
    {
        //Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor should have memory.

        $newAmount = 0;
        $diffAmount = 0;
        $explanation = '';

        if(!$factor) {
            $factor = 1;
        }

        $transferTo = str_replace(
            ['$year'],
            [$year],
            $transferTo);

        $source = str_replace(
            ['$year'],
            [$year],
            $source);

        if ($debug) {
            echo "applyRule INPUT(year: $year, amount: $amount, rule: $rule, transferTo: $transferTo, source: $source factor: $factor)\n";
        }

        #This is really just a fixed number, but it can appear at the same time as a rule.
        if (is_numeric($amount) && $amount != 0) {
            $explanation = 'Using current amount: '.round($amount)." * $factor";
            $amount = $calculatedNumericAmount = round( $amount * $factor);
            #This is not a deposit
        }



        if ($debug) {
            echo "  applyRule (year: $year, newAmount: $newAmount, diffAmount: $diffAmount, transferTo: $transferTo, source: $source)\n";
        }


        //##############################################################################################################
        //Transfer value to another asset, has to update the datastructure of this asset directly
        if ($transferTo) {

            if ($rule) {
                [$newAmount, $diffAmount, $rule, $explanation] = $this->helper->calculateRule($debug, $amount, $depositedAmount, $rule, $factor);
            }

            #Have to switch signs on $diffAmount
            $transferAmount = -$diffAmount;

            if($transferAmount > 0) {
                $this->transfer(false, $transferAmount, $transferTo);
                //$calculatedAmount = $amount - $newAmount; //Removes the transferred amount from this asset.
                //$depositedAmount = round($newAmount - $amount);
            }
        } elseif($source && $rule) {
            #If we are not transfering the values to another resoruce, then we are adding it to the current resource
            #Do not run calculateRule here since it changes the rule, and are run in the sub procedure
            //###########################################################################################################

            [$diffAmount, $explanation] = $this->source(false, $source, $rule);
            $newAmount = $newAmount + $diffAmount;
        } else {
            //No transfers or sourcing involved
            if ($rule) {
                [$newAmount, $diffAmount, $rule, $explanation] = $this->helper->calculateRule($debug, $amount, $depositedAmount, $rule, $factor);
            }

            $newAmount = $amount;
            $diffAmount = 0;
            $rule = null;
        }

        if ($debug) {
            echo "applyRule OUTPUT(newAmount: $newAmount, diffAmount: $diffAmount, rule: $rule, explanation: $explanation)\n";
        }

        //print "return amountAdjustment($newAmount, $rule, $explanation)\n";
        return [$newAmount, $diffAmount, $rule, $explanation]; //Rule is adjusted if it is a divisor, it has to be remembered to the next round
    }

    //Transferes the amount to another asset.
    public function transfer(bool $debug, float $amount, string $path) {
        $paidAmount = 0;
        $explanation = " transfer $amount to $path";
        if ($debug) {
            echo "Transferto before: $path: ".Arr::get($this->dataH, $path, 0)."\n";
        }

        #FIX: Tax calculations here.
        Arr::set($this->dataH, $path, Arr::get($this->dataH, $path, 0) + $amount); //The real transfer from this asset to another takes place here, it is added to the already existing amount on the other asset
        //FIX: Should add explanation also on the asset transfered to for easier debug.
        $paidAmount -= $amount;
        if ($paidAmount < 0) {
            $paidAmount = 0; //Deposited amount can not go negative.
        }

        if ($debug) {
            echo "Transferto after: $path: ".Arr::get($this->dataH, $path, 0)."\n";
        }

        //###########################################################################################################
        //reduce value from this assetAmount
        $explanation .= " reduce by $amount\n";


        return [$paidAmount, $explanation];
    }

    //Calculates an amount based on the value of another asset
    public function source(bool $debug, string $path, string $rule) {
        $paidAmount = 0;
        $amount = $this->ArrGet($path); //Retrive the amount from another asset. Do not change the other asset.

        [$newAmount, $diffAmount, $rule, $explanation] = $this->helper->calculateRule($debug, $amount, 0, $rule, 1);
        $explanation = " source $rule of $path $amount = $diffAmount\n";

        if ($debug) {
            echo "  Source before: $explanation\n";
        }

        return [$diffAmount, $explanation];
    }

    public function configOrPrevValue(bool $debug, string $assetname, int $year, string $type, string $variable)
    {
        $prevYear = $year - 1;

        $value = Arr::get($this->config, "$assetname.$year.$type.$variable", null); //Retrieve value from config current year
        if ($debug) {
            echo "configOrPrevValueConfig: $assetname.$year.$type.$variable: $value\n";
        }
        if (! isset($value)) {
            $value = $this->ArrGet("$assetname.$prevYear.$type.$variable"); //Retrive value from dataH (processed data, previous year
            if ($debug) {
                echo "configOrPrevValueData prev year: $assetname.$year.$type.$variable: $value\n";
            }
        }

        return $value;
    }

    //Do all post processing on already calculated data
    public function postProcess()
    {
        foreach ($this->dataH as $assetname => $assetH) {

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
                $this->postProcessFireYearly($assetname, $year, $meta);
            }
        }
    }

    //Do all calculations that should be done as the last thing, and requires that all other calculations is already done.
    //Special Arr get that onlye gets data from dataH to make cleaner code.
    public function ArrGet(string $path)
    {
        return Arr::get($this->dataH, $path, 0);
    }

    //Special Arr get that onlye gets data from configH to make cleaner code.
    public function ArrGetConfig(string $path)
    {
        return Arr::get($this->config, $path, 0);
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
        Arr::set($this->dataH, "$path.expence.taxDeductableAmount", $this->ArrGet("$path.mortgage.interestAmount") * 0.22); //FIX. This has to do with Mortgage: Remove hardcoded percentage later to read from tax config
    }

    public function postProcessCashFlowYearly(string $path)
    {
        //Free money to spend
        //$cashflowAmount = $this->ArrGet("$path.cashflow.incomeAmount") - $this->ArrGet("$path.cashflow.expenceAmount") + $this->ArrGet("$path.expence.taxDeductableAmount") - $this->ArrGet("$path.mortgage.termAmount");

        //Arr::set($this->dataH, "$path.cashflow.beforeTaxAmount", $cashflowAmount);
        //Arr::set($this->dataH, "$path.cashflow.afterTaxAmount",$cashflowAmount);
        //Arr::set($this->dataH, "$path.cashflow.beforeTaxAggregatedAmount",$cashflowAmount);  #FIX: Cashflow is not accumulated now
        //Arr::set($this->dataH, "$path.cashflow.afterTaxAggregatedAmount",$cashflowAmount);  #FIX: Cashflow is not accumulated now
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
            Arr::set($this->dataH, "$path.asset.mortageRateDecimal", $this->ArrGet("$path.mortgage.balanceAmount") / $this->ArrGet("$path.asset.marketAmount"));
        } else {
            Arr::set($this->dataH, "$path.asset.mortageRateDecimal", 0);
        }
    }

    /**
     * Perform post-processing calculations for the FIRE (Financial Independence, Retire Early) calculation on a yearly basis.
     * Achievement er hvor mye du mangler for å nå målet? Feil navn?
     * amount = assetverdi - lån i beregningene + inntekt? (Hvor mye er 4% av de reelle kostnadene + inntekt (sannsynligvis kunn inntekt fra utleie)
     *
     * @param  int  $year The year for which the calculations are performed.
     * @param  string  $assetname The name of the asset for which the calculations are performed.
     * @return void
     */
    public function postProcessFireYearly(string $assetname, int $year, array $meta)
    {

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
            $firePercent = 0.04; //ToDo: 4% av en salgbar asset verdi. FIX: Konfigurerbart FIRE tall.
            $fireAssetIncomeAmount = $assetMarketAmount * $firePercent; //Only asset value
            $CashflowTaxableAmount = $fireAssetIncomeAmount * $this->ArrGet("$path.income.taxableDecimal"); //ToDo: Legger til skatt på utbytte fra salg av en % av disse eiendelene her (De har neppe en inntekt, men det skal også fikses fint)
            //print "ATY: $CashflowTaxableAmount        += TFI:$fireAssetIncomeAmount * PTY:$DecimalTaxableYearly;\n";
            //ToDo: Det er ulik skatt på de ulike typene.
        }

        //NOTE - Lån telles som FIRE inntekt.
        $fireIncomeAmount = $fireAssetIncomeAmount + $incomeAmount + $this->ArrGet("$path.mortgage.principalAmount") + $this->ArrGet("$path.expence.taxDeductableAmount"); //Percent of asset value + income from asset. HUSK KUN INNTEKTER her
        $fireExpenceAmount = $expenceAmount + $CashflowTaxableAmount + $this->ArrGet("$path.mortgage.interestAmount");
        $fireCashFlowAmount = round($fireIncomeAmount - $fireExpenceAmount); //Hvor lang er man unna fire målet

        //print "$assetname - FTI: $fireIncomeAmount = FI:$fireCurrentIncome + I:$incomeAmount + D:$deductableYearlyAmount\n"; #Percent of asset value + income from asset

        //##############################################################
        //Calculate FIRE percent diff
        if ($fireExpenceAmount > 0) {
            $fireDiffPercent = $fireIncomeAmount / $fireExpenceAmount; //Hvor mange % unna er inntektene å dekke utgiftene.
        } else {
            $fireDiffPercent = 1;
        }

        //##############################################################
        //Calculate FIRE Savings amount
        $fireSavingAmount = 0;
        if (Arr::get($this->fireSavingTypes, $meta['type'])) {
            $fireSavingAmount = $incomeAmount; //If this asset is a valid saving asset, we add it to the saving amount.
        }

        //##############################################################
        //Calculate FIRE Savings rate
        //Sparerate = Det du nedbetaler i gjeld + det du sparer eller investerer på andre måter / total inntekt (etter skatt).
        $fireSavingRate = 0;
        //ToDo: Should this be income adjusted for deductions and tax?
        if ($incomeAmount > 0) {
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

                $this->additionToGroup($year, $meta, $assetH[$year], 'expence.amount');

                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.beforeTaxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.afterTaxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.beforeTaxAggregatedAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.afterTaxAggregatedAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'cashflow.taxAmount');

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
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.taxableAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.taxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.realizationTaxableAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.realizationTaxAmount');
                $this->additionToGroup($year, $meta, $assetH[$year], 'asset.realizationTaxDeductableAmount');

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
            $this->groupDebtCapacity($year);
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

    private function groupDebtCapacity(int $year)
    {
        Arr::set($this->totalH, "$year.potential.mortgageAmount", Arr::get($this->totalH, "$year.potential.mortgageAmount", 0) - Arr::get($this->totalH, "$year.mortgage.balance", 0));

        Arr::set($this->companyH, "$year.potential.mortgageAmount", Arr::get($this->companyH, "$year.potential.mortgageAmount", 0) - Arr::get($this->companyH, "$year.mortgage.balance", 0));

        Arr::set($this->privateH, "$year.potential.mortgageAmount", Arr::get($this->privateH, "$year.potential.mortgageAmount", 0) - Arr::get($this->privateH, "$year.mortgage.balance", 0));
    }

    //Calculates on data that is summed up in the group
    //FIX: Much better if we could use calculus here to reduce number of methods, but to advanced for the moment.
    public function groupFireSaveRate(int $year)
    {
        if (Arr::get($this->totalH, "$year.fire.incomeAmount", 0) > 0) {
            Arr::set($this->totalH, "$year.fire.savingRate", Arr::get($this->totalH, "$year.fire.cashFlowAmount", 0) / Arr::get($this->totalH, "$year.fire.incomeAmount", 0));
        }
        if (Arr::get($this->companyH, "$year.fire.incomeAmount", 0) > 0) {
            Arr::set($this->companyH, "$year.fire.savingRate", Arr::get($this->companyH, "$year.fire.cashFlowAmount", 0) / Arr::get($this->companyH, "$year.fire.incomeAmount", 0));
        }
        if (Arr::get($this->privateH, "$year.fire.incomeAmount", 0) > 0) {
            Arr::set($this->privateH, "$year.fire.savingRate", Arr::get($this->privateH, "$year.fire.cashFlowAmount", 0) / Arr::get($this->privateH, "$year.fire.incomeAmount", 0));
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
