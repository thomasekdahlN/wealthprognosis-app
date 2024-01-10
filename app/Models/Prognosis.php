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
        'fond' => true,
        'stock' => false,
        'otp' => true,
        'ask' => true,
        'pension' => true,
    ];
//Dette er de asssett typene som regnes som inntekt i FIRE. Nedbetaling av lån regnes ikke som inntekt.
    public $fireSavingTypes = [
        'house' => true,
        'rental' => true,
        'cabin' => true,
        'crypto' => true,
        'fond' => true,
        'stock' => true,
        'otp' => true,
        'ask' => true,
        'pension' => true,
    ];

    public $assetSpreadTypes = [
        'boat' => true,
        'car' => true,
        'cash' => true,
        'house' => true,
        'rental' => true,
        'cabin' => true,
        'crypto' => true,
        'fond' => true,
        'stock' => true,
        'otp' => true,
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

                #echo "Expence adjust before: $assetname.$year, expenceAmount:$expenceAmount, expenceRule: $expenceRule\n";
                [$expenceAmount, $expenceDepositedAmount, $expenceRule, $explanation] = $this->applyRule(false, $year, $expenceAmount, 0, $expenceRule, $expenceTransfer, $expenceSource, $expenceFactor);
                #echo "Expence adjust after : $assetname.$year, expenceAmount:$expenceAmount, expenceRule: $expenceRule\n";
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
                [$incomeAmount, $incomeDepositedAmount, $incomeRule, $explanation] = $this->applyRule(false, $year, $incomeAmount, 0, $incomeRule, $incomeTransfer, $incomeSource, $incomeFactor);
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
                    echo "\n\nFirst time: $assetname.$year\n";
                } else {
                    $firsttime = false;
                }

                [$assetChangeratePercent, $assetChangerateDecimal, $assetChangerateAmount, $assetExplanation1] = $this->changerate->getChangerate(false, $assetChangerate, $year, $assetChangerateAmount);
                //print "$year: $assetChangeratePercent%\n";

                //print "\nAsset før: $assetname.$year assetPrevAmount:$assetMarketPrevAmount assetMarketAmount:$assetMarketAmount, assetRule:$assetRule\n";
                [$assetMarketAmount, $assetDiffAmount, $assetNewRule, $assetExplanation2] = $this->applyRule(false, $year, $assetMarketAmount, 0, $assetRule, $assetTransfer, $assetSource, 1);
                //print "Asset etter: $assetname.$year assetMarketAmount: $assetMarketAmount, paidExtraAmount: $paidExtraAmount, assetNewRule:$assetNewRule explanation: $explanation\n";

                if ($firsttime) {
                    //default values we only set on the first run

                    if ($assetAcquisitionAmount <= 0) {
                        //Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetAcquisitionAmount (not changerates), only real money.
                        $assetAcquisitionAmount = $assetMarketAmount;
                    }

                    echo "*** $year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetAcquisitionAmount, assetEquityAmount:$assetEquityAmount, assetPaidAmount: $assetPaidAmount, assetTaxableAmount:$assetTaxableAmount\n";

                    if ($assetEquityAmount <= 0) {
                        //Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetAcquisitionAmount (not changerates), only real money.
                        $assetEquityAmount = round($assetAcquisitionAmount - $this->ArrGet("$assetname.$year.mortgage.amount"));
                        echo "    Equity: $assetname.$year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetAcquisitionAmount, assetEquityAmount:$assetEquityAmount, assetPaidAmount: $assetPaidAmount, assetTaxableAmount:$assetTaxableAmount, termAmount: ".$this->ArrGet("$assetname.$year.mortgage.termAmount")."\n";
                    }

                    if ($assetPaidAmount <= 0) {
                        //Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetAcquisitionAmount (not changerates), only real money.
                        $assetPaidAmount = $assetEquityAmount;
                        echo "    Paid: $assetname.$year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetAcquisitionAmount, assetEquityAmount:$assetEquityAmount, assetPaidAmount: $assetPaidAmount, assetTaxableAmount:$assetTaxableAmount, termAmount: ".$this->ArrGet("$assetname.$year.mortgage.termAmount")."\n";
                    }

                    //If a mortgage is involved, the termAmount is a part of the Paid amount, since you also paid the term amount. The amount pais is usually the same ass the $assetEquityAmount
                    $assetMarketMortgageDeductedAmount = $assetMarketAmount - $this->ArrGet("$assetname.$year.mortgage.balanceAmount");

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
                    $assetMarketMortgageDeductedAmount = $assetMarketAmount - $this->ArrGet("$assetname.$year.mortgage.balanceAmount");
                }
                //#######################################################################################################
                //Values that can not go negative
                if ($assetMarketAmount < 0) {
                    $assetMarketAmount = 0; //Can not be negative
                }
                if ($assetAcquisitionAmount < 0) {
                    $assetAcquisitionAmount = 0; //Can not be negative
                }
                if ($assetEquityAmount < 0) {
                    $assetEquityAmount = 0; //Can not be negative
                }
                if ($assetPaidAmount < 0) {
                    $assetPaidAmount = 0; //Can not be negative
                }
                if ($assetTaxableAmount < 0) {
                    $assetTaxableAmount = 0; //Can not be negative
                }

                //print "$year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetAcquisitionAmount, assetEquityAmount:$assetEquityAmount, assetPaidAmount: $assetPaidAmount, assetTaxableAmount:$assetTaxableAmount, termAmount: " . $this->ArrGet("$assetname.$year.mortgage.termAmount") . "\n";

                //print "PAID: $assetname.$year.asset.paidAmount: " . $assetPaidAmount . " + curPaid: " . $this->ArrGet("$assetname.$year.asset.paidAmount") . " + prevPaid: " . $this->ArrGet("$assetname.$prevYear.asset.paidAmount") . " - assetEquityAmount: $assetEquityAmount\n";

                //#######################################################################################################
                //Tax calculations
                //print "$taxtype.$year incomeCurrentAmount: $incomeAmount, expenceCurrentAmount: $expenceAmount\n";
                //FIXXXX?????  $assetTaxableAmount = round($assetTaxableAmount * $assetChangerateDecimal); //We have to increase the taxable amount, but maybe it should follow another index than the asset market value. Anyway, this is quite good for now.
                [$assetTaxableAmount, $assetTaxAmount, $assetTaxableDecimal, $assetTaxDecimal] = $this->tax->taxCalculationFortune($taxtype, $year, $assetMarketAmount, $assetTaxableAmount, $assetTaxableAmountOverride);

                [$realizationTaxableAmount, $realizationTaxAmount, $realizationTaxPercent] = $this->tax->taxCalculationRealization(false, $taxtype, $year, $assetMarketAmount, $assetAcquisitionAmount, $assetFirstYear);
                $realizationAmount = $assetMarketAmount - $realizationTaxAmount; //Markedspris minus skatt ved salg.

                //Vi må trekke fra formuesskatten fra cashflow
                //$cashflow -= $assetTaxAmount;

                //#######################################################################################################
                //Store all data in the dataH structure
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

                //print_r($this->dataH[$assetname][$year]['income']);
                //Fix before and after tax cashflow calculations.

                $this->dataH[$assetname][$year]['asset'] = [
                    'marketAmount' => $assetMarketAmount,
                    'acquisitionAmount' => $assetAcquisitionAmount,
                    'marketMortgageDeductedAmount' => $assetMarketMortgageDeductedAmount,
                    'equityAmount' => $assetEquityAmount,
                    'paidAmount' => $assetPaidAmount,
                    'taxableDecimal' => $assetTaxableDecimal,
                    'taxableAmount' => $assetTaxableAmount,
                    'taxableAmountOverride' => $assetTaxableAmountOverride,
                    'taxDecimal' => $assetTaxDecimal,
                    'taxAmount' => $assetTaxAmount,
                    'changerate' => $assetChangerate,
                    'changeratePercent' => $assetChangeratePercent,
                    'rule' => $assetNewRule,
                    'transfer' => $assetTransfer,
                    'source' => $assetSource,
                    'repeat' => $assetRepeat,
                    'realizationAmount' => $realizationAmount,
                    'realizationTaxableAmount' => $realizationTaxableAmount,
                    'realizationTaxAmount' => $realizationTaxAmount,
                    'realizationTaxDecimal' => $realizationTaxPercent,
                    'description' => $this->ArrGetConfig("$assetname.$year.asset.description").' Asset rule:'.$assetRule.' '.$assetExplanation1.$assetExplanation2,
                ];

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
     * -- 10% - Gvies you 10% from amount - amount not changed
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

        if (! $factor) {
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

        //This is really just a fixed number, but it can appear at the same time as a rule.
        if (is_numeric($amount) && $amount != 0) {
            $explanation = 'Using current amount: '.round($amount)." * $factor";
            $amount = $calculatedNumericAmount = round($amount * $factor);
            //This is not a deposit
        }

        if ($debug) {
            echo "    applyRule INPUT($year, amount: $amount, rule: $rule, transfer: $transferTo, source: $source factor: $factor)\n";
        }

        //##############################################################################################################
        //Transfer value to another asset, has to update the datastructure of this asset directly
        if ($transferTo) {

            //$debug = true;

            if ($rule) {
                [$newAmount, $diffAmount, $rule, $explanation] = $this->helper->calculateRule($debug, $amount, $depositedAmount, $rule, $factor);
            }

            //Have to switch signs on $diffAmount
            $transferAmount = -$diffAmount;

            if ($transferAmount > 0) {
                $this->transfer($debug, $transferAmount, $transferTo);
                //$calculatedAmount = $amount - $newAmount; //Removes the transferred amount from this asset.
                //$depositedAmount = round($newAmount - $amount);
            }
        } elseif ($source && $rule) {
            //If we are not transfering the values to another resoruce, then we are adding it to the current resource
            //Do not run calculateRule here since it changes the rule, and are run in the sub procedure
            //###########################################################################################################

            [$diffAmount, $explanation] = $this->source($debug, $source, $rule);
            $newAmount = $amount + $diffAmount;
        } else {
            //No transfers or sourcing involved, just apply the rule to the amount if it exists
            if ($rule) {
                if ($debug) {
                    echo "  Normal rule\n";
                }
                [$newAmount, $diffAmount, $rule, $explanation] = $this->helper->calculateRule($debug, $amount, $depositedAmount, $rule, $factor);
            } else {
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

    //Transferes the amount to another asset.
    public function transfer(bool $debug, float $amount, string $path)
    {
        $paidAmount = 0;
        $explanation = " transfer $amount to $path";
        if ($debug) {
            echo "Transferto before: $path: ".Arr::get($this->dataH, $path, 0)."\n";
        }

        //FIX: Tax calculations here.
        Arr::set($this->dataH, $path, Arr::get($this->dataH, $path, 0) + $amount); //The real transfer from this asset to another takes place here, it is added to the already existing amount on the other asset
        //Arr::set($this->dataH, $path, Arr::get($this->dataH, $path, 0) + $amount); //FIX: Register the transferred amount on both sides.

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

    public function getAssetMetaFromPath($path, $type) {
        $value = null;
        $year = null;
        $assetname = null;

        if(preg_match('/(\w+).(\d+)/i', $path, $matchesH, PREG_OFFSET_CAPTURE)) {
            //print_r($matchesH);
            $year = $matchesH[2][0];
            $assetname = $matchesH[1][0];
            $value = $this->ArrGet("$assetname.meta.$type");
        } else {
            echo "ERROR with path: $path\n";
        }
        return [$assetname, $year, $value];
    }

    public function postProcessCashFlowYearly(string $path)
    {
        //post processing cashflow will ensure cashflow and tax is calculated correctly even after transfer/source/calculations out of sequence
        //echo "postProcessCashFlowYearly: $path\n";

        [$assetname, $year, $taxtype] = $this->getAssetMetaFromPath($path, 'tax');
        $prevYear = $year - 1;

        //Free money to spend
        [$cashflowTaxAmount, $cashflowTaxPercent] = $this->tax->taxCalculationCashflow(false, $taxtype, $year, $this->ArrGet("$path.income.amount"), $this->ArrGet("$path.expence.amount"));

        $cashflowBeforeTaxAmount =
            $this->ArrGet("$path.income.amount")
            - $this->ArrGet("$path.expence.amount");

        $cashflowAfterTaxAmount =
                $this->ArrGet("$path.income.amount")
                - $this->ArrGet("$path.expence.amount") //cashflow basis = inntekt - utgift.
                - $cashflowTaxAmount //Minus skatt på cashflow (Kan være både positiv og negativ)
                - $this->ArrGet("$path.asset.taxAmount") //Minus formuesskatt
                - $this->ArrGet("$path.asset.propertyTaxAmount") //Minus eiendomsskatt
                - $this->ArrGet("$path.mortgage.termAmount"); //Minus terminbetaling på lånet
        +$this->ArrGet("$path.mortgage.taxDeductableAmount"); //Plus skattefradrag på renter

        Arr::set($this->dataH, "$path.cashflow.beforeTaxAmount", $cashflowBeforeTaxAmount);
        Arr::set($this->dataH, "$path.cashflow.afterTaxAmount", $cashflowAfterTaxAmount);
        Arr::set($this->dataH, "$path.cashflow.beforeTaxAggregatedAmount", $cashflowBeforeTaxAmount + $this->ArrGet("$assetname.$prevYear.cashflow.beforeTaxAggregatedAmount"));  //FIX: Cashflow is not accumulated now
        Arr::set($this->dataH, "$path.cashflow.afterTaxAggregatedAmount", $cashflowAfterTaxAmount + $this->ArrGet("$assetname.$prevYear.cashflow.afterTaxAggregatedAmount"));  //FIX: Cashflow is not accumulated now
        Arr::set($this->dataH, "$path.cashflow.taxAmount", $cashflowTaxAmount);
        Arr::set($this->dataH, "$path.cashflow.taxDecimal", $cashflowTaxPercent);
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
     * Performs post-processing for the income potential as seen from a Bank
     * This function calculates the potential maximum loan a user can handle based on their income
     *
     * @param string $path The path to the asset in the data structure. The path is in the format 'assetname.year'.
     *
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
        Arr::set($this->dataH, "$path.potential.incomeAmount", $potentialIncomeAmount);

        // Calculate the potential mortgage amount (the bank will loan you 5 times the income) and set it in the data structure.
        Arr::set($this->dataH, "$path.potential.mortgageAmount", $potentialIncomeAmount * 5); //The bank will loan you 5 times the income.
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
            print "FIRE SAVING: $assetname: " . $meta['type'] . " : $incomeAmount \n";
            $fireSavingAmount = $incomeAmount - $this->ArrGet("$path.mortgage.interestAmount"); //Renter is not saving, but prinicpal is
        }

        //##############################################################
        //Calculate FIRE Savings rate
        //Sparerate = Det du nedbetaler i gjeld + det du sparer eller investerer på andre måter / total inntekt (etter skatt).
        $fireSavingRateDecimal = 0;
        //ToDo: Should this be income adjusted for deductions and tax?
        if ($incomeAmount > 0) {
            $fireSavingRateDecimal = ($fireSavingAmount / $incomeAmount) * 100;
            print "FIRE SAVING RATE: $fireSavingRateDecimal = $fireSavingAmount / $incomeAmount\n";
        }

        $this->dataH[$assetname][$year]['fire'] = [
            'percent' => $firePercent,
            'incomeAmount' => $fireIncomeAmount,
            'expenceAmount' => $fireExpenceAmount,
            'diffPercent' => $fireDiffPercent,
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
