<?php

//Asset,
//Mortgage,
//CashFlow



namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

        $this->birthYear  = (integer) Arr::get($this->config, 'meta.birthYear');
        $this->economyStartYear = $this->birthYear + 16; #We look at economy from 16 years of age
        $this->thisYear  = now()->year;
        $this->deathYear  = (integer) $this->birthYear + Arr::get($this->config, 'meta.deathYear');

        foreach(Arr::get($this->config, 'assets') as $assetname => $asset) {

            #Store all metadata about the asset, the rest is yearly calculations
            $this->dataH[$assetname]['meta'] = $meta = $asset['meta'];
            if(!$meta['active']) continue; #Hopp over de inaktive
            $taxtype = Arr::get($meta, "tax", null);

            $assetFirstValue = 0;
            $assetFirstYear = 0;
            $assetPrevValue = null;
            $assetCurrentValue = 0;
            $assetRule = null;
            $assetChangerateDecimal = 0;
            $assetChangerateValue = "";
            $assetCurrentRepeat= false;
            $assetPrevRepeat= false;
            $assetCurrentTransferTo = null;
            $assetPrevTransferTo = null;
            $assetCurrentTransferRule = null;
            $assetPrevTransferRule = null;

            $incomeCurrentValue = 0;
            $incomePrevValue = 0;
            $incomeChangerateDecimal = 0;
            $incomeChangerateValue = "";
            $incomeRule = null;
            $incomePrevRepeat = false;
            $incomeCurrentRepeat = false;

            $expenceCurrentValue = 0;
            $expencePrevValue = 0;
            $expenceChangerateDecimal = 0;
            $expenceChangerateValue = "";
            $expenceRule = null;
            $expenceCurrentRepeat = false;
            $expencePrevRepeat = false;

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

                $expenceCurrentRepeat = Arr::get($asset, "expence.$year.repeat", null);
                if(isset($expenceCurrentRepeat)) {
                    $expencePrevRepeat = $expenceCurrentRepeat;
                } else {
                    $expenceCurrentRepeat = $expencePrevRepeat;
                }

                list($expenceChangeratePercent, $expenceChangerateDecimal, $expenceChangerateValue, $explanation) = $this->changerate->convertChangerate(0, Arr::get($asset, "expence.$year.changerate", null), $year, $expenceChangerateValue);

                $expenceCurrentValue = Arr::get($asset, "expence.$year.value", 0); #Expence is added as a monthly repeat in config

                list($expenceCurrentValue, $expenceRule, $explanation) = $this->valueAdjustment(0, $assetname, $year, 'expence',$expencePrevValue, $expenceCurrentValue, $expenceRule,12);

                $this->dataH[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangeratePercent / 100,
                    'amount' => $expenceCurrentValue,
                    'description' => Arr::get($asset, "expence.$year.description") . $explanation,
                ];

                #print "$year: expenceChangeratePercent = $expenceChangerateDecimal - expence * $expence\n";

                #####################################################
                #income
                $incomeCurrentRepeat = Arr::get($asset, "income.$year.repeat", null);
                if(isset($incomeCurrentRepeat)) {
                    $incomePrevRepeat = $incomeCurrentRepeat;
                } else {
                    $incomeCurrentRepeat = $incomePrevRepeat;
                }

                list($incomeChangeratePercent, $incomeChangerateDecimal, $incomeChangerateValue, $explanation) =  $this->changerate->convertChangerate(false, Arr::get($asset, "income.$year.changerate", null), $year, $incomeChangerateValue);

                $incomeCurrentValue = Arr::get($asset, "income.$year.value", 0); #Income is added as a yearly repeat in config

                list($incomeCurrentValue, $incomeRule, $explanation) = $this->valueAdjustment(true, $assetname, $year, 'income', $incomePrevValue, $incomeCurrentValue, $incomeRule, 12);

                $this->dataH[$assetname][$year]['income'] = [
                    'changerate' => $incomeChangeratePercent / 100,
                    'amount' => $incomeCurrentValue,
                    'description' => Arr::get($asset, "income.$year.description") . $explanation,
                    ];

                ########################################################################################################
                #Assett
                $assetCurrentTransferTo = Arr::get($asset, "value.$year.transferTo", null);
                if(isset($assetCurrentTransferTo)) {
                    $assetPrevTransferTo = $assetCurrentTransferTo;
                } else {
                    $assetCurrentTransferTo = $assetPrevTransferTo; #Remembers a transfer until zeroed out in config.
                }

                $assetCurrentTransferRule = Arr::get($asset, "value.$year.transferRule", null);
                if(isset($assetCurrentTransferRule)) {
                    $assetPrevTransferRule = $assetCurrentTransferRule;
                } else {
                    $assetCurrentTransferRule = $assetPrevTransferRule; #Remembers a transfer until zeroed out in config.
                }

                $assetCurrentRepeat = Arr::get($asset, "value.$year.repeat", null);
                if(isset($assetCurrentRepeat)) {
                    $assetPrevRepeat= $assetCurrentRepeat;
                } else {
                    $assetCurrentRepeat = $assetPrevRepeat;
                }

                list($assetChangeratePercent, $assetChangerateDecimal, $assetChangerateValue, $explanation) = $this->changerate->convertChangerate(0, Arr::get($asset, "value.$year.changerate", null), $year, $assetChangerateValue);
                #print "$year: " . $this->changerate->decimalToDecimal($assetChangerateDecimal) . "\n";

                $assetCurrentValue = Arr::get($asset, "value.$year.value", 0);
                $assetCurrentTaxValue = Arr::get($asset, "value.$year.taxvalue", 0);

                if($this->isRule($assetCurrentValue)) {
                    #$assetPrevValue can only be a number, not a rule, if the current $assetCurrentValue is a config, it will be put in $assetRule to calculate on the numeric value of the current asset.
                    $assetPrevValue = $assetCurrentValue;
                    $assetPrevTaxValue = $assetCurrentTaxValue;

                } elseif($assetCurrentRepeat && !$assetCurrentValue) {
                    #We can not overwrite current value with previous if it is currently set (logical but confusing)
                    $assetCurrentValue = $assetPrevValue;
                    $assetCurrentTaxValue = $assetPrevTaxValue;
                }

                #print "Asset før: year: $year assetPrevValue:$assetPrevValue assetCurrentValue:$assetCurrentValue assetRule:$assetRule\n";
                list($assetCurrentValue, $assetRule, $explanation) = $this->valueAdjustment(false, $assetname, $year, 'asset', $assetPrevValue, $assetCurrentValue, $assetRule, 1);
                #print "Asset etter: year:$year assetCurrentValue: $assetCurrentValue, assetRule:$assetRule explanation: $explanation\n";

                #print "Transfer before: $assetname.$year, assetCurrentValue:$assetCurrentValue, assetCurrentTransferTo:$assetCurrentTransferTo, AssetCurrentTransferRule:$assetCurrentTransferRule\n";
                list($assetCurrentValue, $assetTransferedValue, $assetPrevTransferRule, $explanation) = $this->valueTransfer(false, $assetname, $year, $assetCurrentValue, $assetCurrentTransferTo, $assetCurrentTransferRule);
                #print "Transfer after: $assetname.$year, assetCurrentValue:$assetCurrentValue, assetTransferedValue:$assetTransferedValue, assetPrevTransferRule:$assetPrevTransferRule, explanation: $explanation\n";

                #FIX: The input diff has to be added to FIRE calculations.

                if(!$assetFirstValue && $assetCurrentValue > 0) {
                    $assetFirstValue = $assetCurrentValue; #FIX: Ta vare på den første verdien vi ser på en asset, da den brukes til skatteberegning ved salg. Må også akkumulere alle innskudd, men ikke verdiøkning.
                    $assetFirstYear = $year; #Ta vare på den første året vi ser en asset, da den brukes til skatteberegning ved salg for å se hvor lenge man har eid den.
                }
               $this->dataH[$assetname][$year]['asset'] = [
                    'amount' => $assetCurrentValue,
                    'amountLoanDeducted' => $assetCurrentValue,
                    'changerate' => $assetChangeratePercent / 100,
                    'description' => Arr::get($asset, "value.$year.description") . " Asset rule " . $assetRule . $explanation,
                ];

                ########################################################################################################
                #Tax calculations
                #print "$taxtype.$year incomeCurrentValue: $incomeCurrentValue, expenceCurrentValue: $expenceCurrentValue\n";
                list($cashflow, $potentialIncome, $CashflowTaxableAmount, $fortuneTaxableAmount, $fortuneTaxAmount, $fortuneTaxablePercent, $fortuneTaxPercent, $AmountTaxableRealization, $AmountDeductableYearly, $AmountDeductableRealization) = $this->tax->taxCalculation(false, $taxtype, $year, $incomeCurrentValue, $expenceCurrentValue, $assetCurrentValue, $assetCurrentTaxValue, $assetFirstValue, $assetFirstYear);

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

                $this->dataH[$assetname][$year]['fortune'] = [
                    'taxablePercent' => $fortuneTaxablePercent,
                    'taxableAmount' => $fortuneTaxableAmount,
                    'taxPercent' => $fortuneTaxPercent,
                    'taxAmount' => $fortuneTaxAmount,
                ];

                #Calculate the potential max loan you can handle base on income, tax adjusted - as seen from the bank.
                #print "$assetname - p:$potentialIncome = i:$incomeCurrentValue - t:$CashflowTaxableAmount\n";
                $this->dataH[$assetname][$year]['potential'] = [
                    'income' => $potentialIncome,
                    'loan' => $potentialIncome * 5
                ];

                $restAccumulated += $cashflow;

                #Free money to spend
                $this->dataH[$assetname][$year]['cashflow'] = [
                    'amount' => $cashflow,
                    'amountAccumulated' => $restAccumulated,
                ];

                #FIRE - Financial Independence Retire Early - beregninger på assets
                #Achievement er hvor mye du mangler for å nå målet? Feil navn?
                # amount = assetverdi - lån i beregningene + inntekt? (Hvor mye er 4% av de reelle kostnadene + inntekt (sannsynligvis kunn inntekt fra utleie)
                # amountAchievement = amount - expences
                #FIX: Something is wrong with this classification, and automatically calculating sales of everything not in this list.
                if(Arr::get($this->firePartSalePossibleTypes, $meta['type'])) {
                    #Her kan vi selge biter av en asset (meta tagge opp det istedenfor tro?
                    $fireCurrentPercent = 0.04; #4% av en salgbar asset verdi. FIX: Konfigurerbart FIRE tall.
                    $fireCurrentIncome = $assetCurrentValue * $fireCurrentPercent; #Only asset value
                    $CashflowTaxableAmount        += $fireCurrentIncome * $DecimalTaxableYearly; #Legger til skatt på utbytte fra salg av en % av disse eiendelene her (De har neppe en inntekt, men det skal også fikses fint)
                    #print "ATY: $CashflowTaxableAmount        += TFI:$fireCurrentIncome * PTY:$DecimalTaxableYearly;\n";
                    #FIX: Det er ulik skatt på de ulike typene.

                } else {
                    #Kan ikke selge biter av en slik asset.
                    $fireCurrentPercent = 0;
                    $fireCurrentIncome = 0; #Only asset value
                }

                #NOTE - Deductable yarly blir bare satt i låneberegningen, så den må legges til globalt der.
                $fireCurrentTotalIncome = $fireCurrentIncome + $incomeCurrentValue + $AmountDeductableYearly; #Percent of asset value + income from asset. HUSK KUN INNTEKTER her
                $fireCurrentTotalExpence = $expenceCurrentValue + $CashflowTaxableAmount;
                #print "$assetname - FTI: $fireCurrentTotalIncome = FI:$fireCurrentIncome + I:$incomeCurrentValue + D:$AmountDeductableYearly\n"; #Percent of asset value + income from asset

                $ThisFireCashFlow = $fireCurrentTotalIncome - $fireCurrentTotalExpence; #Hvor lang er man unna fire målet

                if($expenceCurrentValue > 0) {
                    $fireCurrentPercentDiff = $fireCurrentTotalIncome / $fireCurrentTotalExpence; #Hvor mange % unna er inntektene å dekke utgiftene.
                } else {
                    $fireCurrentPercentDiff = 1;
                }

                #Sparerate = Det du nedbetaler i gjeld + det du sparer eller investerer på andre måter / total inntekt (etter skatt).
                $ThisFireSavingAmount = 0;
                if(Arr::get($this->fireSavingTypes, $meta['type'])) {
                    $ThisFireSavingAmount = $incomeCurrentValue; #If this asset is a valid saving asset, we add it to the saving amount.
                }

                $ThisFireSavingRate = 0;
                #FIX: Should this be income adjusted for deductions and tax?
                if($incomeCurrentValue > 0) {
                    $ThisFireSavingRate = $ThisFireSavingAmount / $incomeCurrentValue;
                }

                $this->dataH[$assetname][$year]['fire'] = [
                    'percent' => $fireCurrentPercent,
                    'amountIncome' => $fireCurrentTotalIncome,
                    'amountExpence' => $fireCurrentTotalExpence,
                    'percentDiff' => $fireCurrentPercentDiff,
                    'cashFlow' => round($ThisFireCashFlow),
                    'savingAmount' => round($ThisFireSavingAmount),
                    'savingRate' => $ThisFireSavingRate,
                ];

                ########################################################################################################
                if($expenceCurrentRepeat) {
                    $expencePrevValue      = $expenceCurrentValue * $expenceChangerateDecimal;
                } else {
                    $expencePrevValue           = null;
                    $expenceChangerateValue     = null;
                    $expenceChangerateDecimal   = null;
                    $expenceChangeratePercent   = null;
                }

                if($incomeCurrentRepeat) {
                    $incomePrevValue       = $incomeCurrentValue * $incomeChangerateDecimal;
                } else {
                    $prevIncome                 = null;
                    $incomeChangerateValue      = null;
                    $incomeChangerateDecimal    = null;
                    $incomeChangeratePercent    = null;
                }

                if($assetCurrentRepeat) {
                    $assetPrevValue   = round($assetCurrentValue * $assetChangerateDecimal);
                    $assetPrevTaxValue = round($assetCurrentTaxValue * $assetChangerateDecimal);
                } else {
                    $assetPrevValue             = null;
                    $assetPrevTaxValue          = null;
                    $assetChangerateValue       = null;
                    $assetChangerateDecimal     = null;
                    $assetChangeratePercent     = null;
                    $assetRule                  = null;
                    $assetCurrentTransferTo     = null;
                    $assetCurrentTransferRule   = null;

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
                $this->dataH = (new Amortization($this->config, $this->changerate, $this->dataH, $mortgage, $assetname))->get();
                #$this->dataH = new Amortization($this->dataH, $mortgage, $assetname);

                #dd($this->dataH['Smørbukkveien 3']);
            }

            //return $this->collections; #??????

        } #End loop over assets

        $this->group();
    }

    public function isRule(?string $value)
    {
        #print "isRule: $value : ";
        if (preg_match('/(\+|\-|\%|\/|\=)/i', $value, $matches, PREG_OFFSET_CAPTURE)) {
            #print "yes\n";
            return true;
        } elseif(!is_numeric($value)) {
            #Catches text strings, like variable confirguration
            #print "yes\n";
            return true;
        }

        #print "no\n";
        return false;
    }

    public function isTransfer(?string $transfer) {
        if(preg_match('/(\w+\.\$\w+\.\w+\.\w+)(\*|=)([0-9]*[.]?[0-9]+|value|diff)/i', $transfer, $matches, PREG_OFFSET_CAPTURE)) {
            return $matches;
        }
        return false;
    }

    public function add(string $assettname, int $year, string $type, array $dataH){
        #$this->dataH[$assettname][$year][$type] = $dataH;
    }

        /**
        -- "=1000" - Sets the value equal to this value
        -- "1000" - Adds 1000 to the value (evaluates to same as +1000
        -- "+10%" - Adds 10% to value (Supported now, but syntax : 10)
        -- "+1000" - Adds 1000 to value
        -- "-10%" - Subtracts 10% from value
        -- "-1000" - Subtracts 1000 from value (Supported now - same syntax)
        -- =+1/10" - Adds 1 tenth of the amount yearly
        -- =-1/10" - Subtracts 1 tenth of the amount yearly (To simulate i.e OTP payment). The rest amount will be zero after 10
         */
    public function valueAdjustment(bool $debug, string $assetname, int $year, string $type, ?string $prevValue, ?string $currentValue, string $rule = NULL, int $factor = 1){
        #Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor should have memory.

        $newValue = 0;
        $explanation = '';

        if($debug) {
            print "INPUT( $assetname.$year.$type, PV: $prevValue, CV: $currentValue, rule: $rule, factor: $factor)\n";
        }

        if($this->isRule($currentValue)) {
            print "** Value looks like rule\n";

            #Previous value has to be an integer from a previous calculation in this case. Only divisor should remember a rule
            list($newValue, $rule, $explanation) = $this->calculateRule($debug, $prevValue, $currentValue);

        } elseif($rule){
                print "** rule is set\n";
                list($newValue, $rule, $explanation) = $this->calculateRule( $debug, $prevValue, $rule);

        } elseif($currentValue == null) {

            #Set it to the previous value
            $newValue = $prevValue; #Previous value is already factored, only new values has to be factored
            if ($newValue != null) {
                #print "value: $value\n";
                $explanation = "Using previous value: " . round($newValue);
            }
        } elseif(is_numeric($currentValue)) {
            $newValue = $currentValue;
        } else {
            print "ERROR: currentValue: $currentValue not catched by logic\n";
        }

        if($debug) {
            print "OUTPUT( newValue: $newValue, rule: $rule, explanation: $explanation)\n";
        }
        return [$newValue, $rule, $explanation]; #Rule is adjusted if it is a divisor, it has to be remembered to the next round
    }


    #Either creates an amount based on another amount or transfers parts of an amount to another amount.
    #Example
    #transferTo": "salary.$year.income.value",  #Example OPT creatwes 5% of income as OTP without reducing OTP
    #tranferRule: +5%. Plus does not reduce the asset, it just adds to the asset you are transferring to.
    #transferTo": "income.$year.income.amount",  #Example using OTP until death reduces the OTP value accordingly
    #tranferRule: -1/12 (always compared to the asset you are in). Minus reduces the asset.

    public function valueTransfer(bool $debug, string $assetname, int $year,  $assetValue, $transferTo, $transferRule)
    {

        $newAssetValue = 0;
        $transferedValue = 0;
        $explanation = null;
        $rule = null;

        if ($transferTo) {

            $transferTo = str_replace(
                ['$year'],
                [$year],
                $transferTo);

            if($debug) {
                print "**** $assetname.$year: assetValue: $assetValue, transferTo: $transferTo, transferRule: $transferRule\n";
            }

            list($newAssetValue, $rule, $explanation) = $this->calculateRule(true, $assetValue, $transferRule);
            $transferedValue = round($assetValue - $newAssetValue); #This is the amount that is reduced or increased
            if($debug) {
                print "#### $assetname.$year: assetValue: $assetValue, newAssetValue: $newAssetValue, transferedValue: $transferedValue, rule: $rule, explanation: $explanation\n";
            }

            #ToDo check that an asset can not go into negative value?

            ############################################################################################################
            #Transfer value to another asset, has to update the datastructure of this asset directly
            if ($transferedValue > 0) {
                $explanation .= " transfer $transferedValue to $transferTo with $transferRule";
                Arr::set($this->dataH, $transferTo, Arr::get($this->dataH, $transferTo, 0) + $transferedValue); #The real transfer from this asset to another takes place here
            }

            ############################################################################################################
            #reduce value from this assetValue (Only if minus sign in rule)
            if(preg_match('/(\-)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                $explanation .= " reduce $assetname.$year\n";
                $newAssetValue = $assetValue - $transferedValue;
            }

        } else {
            $newAssetValue = $assetValue;
        }

        return [$newAssetValue, $transferedValue, $rule, $explanation];
    }

    //$rule has to be a rule, plus, minus, percent, divisor
    public function calculateRule(bool $debug, int $value, string $rule) {

        if(preg_match('/(\+|\-)(\d*)(\%)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
            #Percentages
            list($newValue, $rule, $explanation) = $this->calculationPercentage($debug, $value, $matches);

        } elseif(preg_match('/(\+|\-)(\d*)\/(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)){
            #Divison ex 1/12
            #NOTE: Only division should have the rule remembered!!!!
            list($newValue, $rule, $explanation) = $this->calculationDivisor($debug, $value, $matches);
            #print "--- divisor: ( $newValue, $rule, $explanation ) \n";

        } elseif(preg_match('/(\+)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
            #number that starts with + to be added
            list($newValue, $rule, $explanation) = $this->calculationAddition($debug, $value, $rule);

        } elseif(preg_match('/(\-)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
            #number that starts with - to be subtracted
            list($newValue, $rule, $explanation) = $this->calculationSubtraction($debug, $value, $rule);

        } elseif(preg_match('/(\=)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
            #number that starts with = Fixed number override
            list($newValue, $rule, $explanation) = $this->calculationFixed($debug, $value, $matches);
        } elseif(is_numeric($rule)) {
            #number that is positive, really starts with a + and should be added when it ends up in rule
            list($newValue, $rule, $explanation) = $this->calculationAddition($debug, $value, $rule);
        } else {
            print "ERROR: calculateRule $rule not supported\n";
        }

        return [$newValue, $rule, $explanation];
    }

    public function calculationDivisor(bool $debug, string $value, array $matches) {
        $rule = null;

        $divisorValue = $value / $matches[3][0];

        if($matches[1][0] == '-') {
            $newValue = $value - $divisorValue;
        } else {
            $newValue = $value + $divisorValue;
        }
        $matches[3][0]--; #We reduce the divisor each time we run it, so its the same as the remaining runs.
        if($matches[3][0] > 0) {
            $rule = $matches[1][0] . $matches[2][0] . "/" . $matches[3][0]; #ToDo: Note rule is rewritten for each iteration, will make problems.....
        }
        $explanation = "divisorValue: $divisorValue, Adjusted divisor rule: $rule";

        return [$newValue, $rule, $explanation];
    }

    public function calculationPercentage(bool $debug, string $value, array $matches) {

        $percent = $matches[2][0];
        if($matches[1][0] == '-') {
            $newValue = $value  * ((-$percent  / 100) + 1);
            $rule = "-$percent%";
        } else {
            $newValue = $value *  (($percent  / 100) + 1);
            $rule = "+$percent%";
        }

        $explanation = "Percent: " . $percent  . "%";


        return [$newValue, $rule, $explanation];
    }

    public function calculationAddition(bool $debug, int $value, int $add) {
        $rule = $add;

        $newValue = $value + $add; #Should fix both + and -
        $explanation = "Adding: $add";

        return [$newValue, $rule, $explanation];
    }

    public function calculationSubtraction(bool $debug, int $value, int $subtract) {
        $rule = $subtract;

        $newValue = $value + $subtract; #Should fix both + and -
        $explanation = "Subtracting: $subtract";

        if($newValue < 0) {
            $newValue = $value;
            $explanation = "Not subtracting asset gets negative";
        }

        return [$newValue, $rule, $explanation];
    }

    public function calculationFixed(bool $debug, string $value, array $matches) {
        $rule = null;

        $newValue = round($matches[2][0]); #Should fix both + and -
        $explanation = "Fixed number override =$newValue";

        return [$newValue, $rule, $explanation];
    }

    function group() {
        #dd($this->dataH);
        $this->initGroups();


        foreach($this->dataH as $assetname => $assetH) {
            $meta = $assetH['meta'];
            if(!$meta['active']) continue; #Hopp over de inaktive

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {
                #print "$year\n";
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.fortune");
                $this->additionToGroup($year, $meta, $assetH[$year], "asset.amountLoanDeducted");
                $this->additionToGroup($year, $meta, $assetH[$year], "income.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "expence.amount");
                $this->additionToGroup($year, $meta, $assetH[$year], "tax.amountTaxableYearly");
                $this->additionToGroup($year, $meta, $assetH[$year], "tax.amountTaxableRealization");
                $this->additionToGroup($year, $meta, $assetH[$year], "tax.amountDeductableYearly");
                $this->additionToGroup($year, $meta, $assetH[$year], "tax.amountDeductableRealization");
                $this->additionToGroup($year, $meta, $assetH[$year], "fortune.taxableAmount");

                $this->setToGroup($year, $meta, $assetH[$year], "fortune.taxPercent");
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
