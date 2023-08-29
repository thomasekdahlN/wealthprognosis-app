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

    public function test() {

        #Just for testing
        $prevValue = 1000;
        $thisValue = "-50%";
        $thisValue = "+50%";
        $thisValue = "100";
        $thisValue = "+100";
        $thisValue = "-100";
        $thisValue = "+1/10";
        #$thisValue = "-1/10";
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);
        list($prevValue, $thisValue) = $this->valueAdjustment($prevValue, $thisValue);

    }

    public function __construct($config, $tax, $changerate)
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
            $PercentTaxableYearly = Arr::get($this->tax, $taxtype. ".yearly", 0) / 100;
            $PercentTaxableRealization = Arr::get($this->tax, $taxtype. ".realization", 0) / 100;
            $PercentDeductableYearly = Arr::get($this->tax, $taxtype. ".yearly", 0) / 100;
            $PercentDeductableRealization = Arr::get($this->tax, $taxtype. ".realization", 0) / 100;
            $PercentTaxableFortune = Arr::get($this->tax, $taxtype. ".fortune", 0) / 100;


            #print "$assetname: $taxtype: PercentTaxableYearly: $PercentTaxableYearly, PercentTaxableRealization: $PercentTaxableRealization\n";

            $assetValue = 0;
            $firstAssetValue = null;
            $firstAssetYear = null;
            $prevAssetValue = null;
            $prevAssetRule = null;
            $prevAssetChangerate = 1;
            $prevAssetRepeat = false;
            $assetTransfer = null;
            $prevAssetTransfer = null;

            $income = 0;
            $prevIncome = 0;
            $prevIncomeChangerate = 1;
            $prevIncomeRule = null;
            $prevIncomeRepeat = false;

            $expence = 0;
            $prevExpence = 0;
            $prevExpenceChangerate = 1;
            $prevExpenceRule = null;
            $prevExpenceRepeat = false;

            $restAccumulated = 0;

            #dd($this->config['changerates']);

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

                #####################################################
                #expence

                $expenceRepeat = Arr::get($asset, "expence.$year.repeat", null);
                if(isset($expenceRepeat)) {
                    $prevExpenceRepeat = $expenceRepeat;
                } else {
                    $expenceRepeat = $prevExpenceRepeat;
                }

                $expenceChangerate = $this->percentToDecimal(Arr::get($asset, "expence.$year.changerate", null));
                if($expenceChangerate > 0) {
                    #print "Fant: $expenceChangerate\n";
                    $prevExpenceChangerate = $expenceChangerate;
                } else {
                    $expenceChangerate = $prevExpenceChangerate;
                    #print "Fant ikke\n";
                }

                $expenceThis = Arr::get($asset, "expence.$year.value", null); #Expence is added as a monthly repeat in config

                list($expence, $prevExpenceRule, $explanation) = $this->valueAdjustment($assetname, $year, 'expence',$prevExpence, $expenceThis, $prevExpenceRule, null,12);

                $this->dataH[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangerate,
                    'amount' => $expence,
                    'description' => Arr::get($asset, "expence.$year.description") . $explanation,
                ];

                #print "$year: expenceChangerate = $expenceChangerate - expence * $expence\n";

                #####################################################
                #income
                $incomeRepeat = Arr::get($asset, "income.$year.repeat", null);
                if(isset($incomeRepeat)) {
                    $prevIncomeRepeat = $incomeRepeat;
                } else {
                    $incomeRepeat = $prevIncomeRepeat;
                }

                $incomeChangerate = $this->percentToDecimal(Arr::get($asset, "income.$year.changerate", null));
                if($incomeChangerate) {
                    $prevIncomeChangerate = $incomeChangerate;
                } else {
                    $incomeChangerate = $prevIncomeChangerate;
                }

                $incomeThis = Arr::get($asset, "income.$year.value", null); #Income is added as a yearly repeat in config

                list($income, $prevIncomeRule, $explanation) = $this->valueAdjustment($assetname, $year, 'income', $prevIncome, $incomeThis, $prevIncomeRule, null, 12);

                $this->dataH[$assetname][$year]['income'] = [
                    'changerate' => $incomeChangerate,
                    'amount' => $income,
                    'description' => Arr::get($asset, "income.$year.description") . $explanation,
                    ];

                #####################################################
                #Assett
                $assetTransfer = Arr::get($asset, "value.$year.transfer", null);
                if(isset($assetTransfer)) {
                    $prevAssetTransfer = $assetTransfer;
                } else {
                    $assetTransfer = $prevAssetTransfer; #Remembers a transfer until zeroed out in config.
                }

                $assetRepeat = Arr::get($asset, "value.$year.repeat", null);
                if(isset($assetRepeat)) {
                    $prevAssetRepeat = $assetRepeat;
                } else {
                    $assetRepeat = $prevAssetRepeat;
                }

                $assetChangerate = $this->percentToDecimal(Arr::get($asset, "value.$year.changerate", null));
                if($assetChangerate) {
                    $prevAssetChangerate = $assetChangerate;
                } else {
                    $assetChangerate = $prevAssetChangerate;
                }

                $assetThis = Arr::get($asset, "value.$year.value", null); #Income is added as a monthly repeat in config
                if($assetThis) { #FIX: Må sjekke asset repeat her
                    $prevAssetThis = $assetThis;
                } elseif($assetRepeat) {
                    $assetThis = $prevAssetThis;
                }

                #print "Asset før: year: $year prevAssetValue:$prevAssetValue assetThis:$assetThis prevAssetRule:$prevAssetRule assetTransfer:$assetTransfer\n";
                list($assetValue, $prevAssetRule, $explanation) = $this->valueAdjustment($assetname, $year, 'asset', $prevAssetValue, $assetThis, $prevAssetRule, $assetTransfer, 1);
                #print "Asset etter: year:$year assetValue:$assetValue, prevAssetRule:$prevAssetRule explanation:$explanation\n";

                #FIX: The input diff has to be added to FIRE calculations.

                if(!$firstAssetValue && $assetValue > 0) {
                    $firstAssetValue = $assetValue; #FIX: Ta vare på den første verdien vi ser på en asset, da den brukes til skatteberegning ved salg. Må også akkumulere alle innskudd, men ikke verdiøkning.
                    $firstAssetYear = $year; #Ta vare på den første året vi ser en asset, da den brukes til skatteberegning ved salg for å se hvor lenge man har eid den.
                }

               $this->dataH[$assetname][$year]['asset'] = [
                    'amount' => $assetValue,
                    'amountLoanDeducted' => $assetValue,
                    'changerate' => $assetChangerate,
                    'description' => Arr::get($asset, "value.$year.description") . " Asset rule " . $prevAssetRule . $explanation,
                ];

                #Tax calculations
                list($cashflow, $potentialIncome, $AmountTaxableYearly, $AmountTaxableRealization, $AmountDeductableYearly, $AmountDeductableRealization) = $this->taxCalculation($taxtype, $year, $income, $expence, $assetValue, $firstAssetValue, $firstAssetYear, $PercentTaxableYearly, $PercentTaxableRealization, $PercentDeductableYearly, $PercentDeductableRealization);

                $this->dataH[$assetname][$year]['tax'] = [
                    'amountTaxableYearly' => -$AmountTaxableYearly,
                    'percentTaxableYearly' => $PercentTaxableYearly,
                    'amountDeductableYearly' => -$AmountDeductableYearly,
                    'percentDeductableYearly' => $PercentDeductableYearly,
                    'amountTaxableRealization' => $AmountTaxableRealization,
                    'percentTaxableRealization' => $PercentTaxableRealization,
                    'amountDeductableRealization' => $AmountDeductableRealization,
                    'percentDeductableRealization' => $PercentDeductableRealization,
                ];

                #Hmm. Fortune burde kanskje hete asset, men da blandes det med andre asset ting.....
                $AmountTaxableFortune = $assetValue * $PercentTaxableFortune;
                if($AmountTaxableFortune <= 1700000) {
                    $AmountTaxableFortune = 0; #Det betales ikke formuesskatt på skattbar formue under 1.7 mill. FIX lese fra confid, støtte årlige forskjeller.
                } else {
                    $AmountTaxableFortune = $AmountTaxableFortune - 1700000;
                }
                $fortuneTaxPercent = Arr::get($this->tax, "fortune.yearly", 0) / 100;
                $fortuneTaxAmount = $AmountTaxableFortune * $fortuneTaxPercent;

                $this->dataH[$assetname][$year]['fortune'] = [
                    'taxableAmount' => $AmountTaxableFortune,
                    'taxPercent' => $fortuneTaxPercent,
                    'taxAmount' => $fortuneTaxAmount,
                ];

                #Calculate the potential max loan you can handle base on income, tax adjusted - as seen from the bank.
                #print "$assetname - p:$potentialIncome = i:$income - t:$AmountTaxableYearly\n";
                $this->dataH[$assetname][$year]['potential'] = [
                    'income' => $potentialIncome,
                    'loan' => $potentialIncome * 5
                ];

                #Vi må trekke fra formuesskatten fra cashflow
                $cashflow -= $fortuneTaxAmount;
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
                    $ThisFirePercent = 0.04; #4% av en salgbar asset verdi. FIX: Konfigurerbart FIRE tall.
                    $ThisFireIncome = $assetValue * $ThisFirePercent; #Only asset value
                    $AmountTaxableYearly        += $ThisFireIncome * $PercentTaxableYearly; #Legger til skatt på utbytte fra salg av en % av disse eiendelene her (De har neppe en inntekt, men det skal også fikses fint)
                    #print "ATY: $AmountTaxableYearly        += TFI:$ThisFireIncome * PTY:$PercentTaxableYearly;\n";
                    #FIX: Det er ulik skatt på de ulike typene.

                } else {
                    #Kan ikke selge biter av en slik asset.
                    $ThisFirePercent = 0;
                    $ThisFireIncome = 0; #Only asset value
                }

                #NOTE - Deductable yarly blir bare satt i låneberegningen, så den må legges til globalt der.
                $ThisFireTotalIncome = $ThisFireIncome + $income + $AmountDeductableYearly; #Percent of asset value + income from asset. HUSK KUN INNTEKTER her
                $ThisFireTotalExpence = $expence + $AmountTaxableYearly;
                #print "$assetname - FTI: $ThisFireTotalIncome = FI:$ThisFireIncome + I:$income + D:$AmountDeductableYearly\n"; #Percent of asset value + income from asset

                $ThisFireCashFlow = $ThisFireTotalIncome - $ThisFireTotalExpence; #Hvor lang er man unna fire målet

                if($expence > 0) {
                    $ThisFirePercentDiff = $ThisFireTotalIncome / $ThisFireTotalExpence; #Hvor mange % unna er inntektene å dekke utgiftene.
                } else {
                    $ThisFirePercentDiff = 1;
                }

                #Sparerate = Det du nedbetaler i gjeld + det du sparer eller investerer på andre måter / total inntekt (etter skatt).
                $ThisFireSavingAmount = 0;
                if(Arr::get($this->fireSavingTypes, $meta['type'])) {
                    $ThisFireSavingAmount = $income; #If this asset is a valid saving asset, we add it to the saving amount.
                }

                $ThisFireSavingRate = 0;
                #FIX: Should this be income adjusted for deductions and tax?
                if($income > 0) {
                    $ThisFireSavingRate = $ThisFireSavingAmount / $income;
                }

                $this->dataH[$assetname][$year]['fire'] = [
                    'percent' => $ThisFirePercent,
                    'amountIncome' => $ThisFireTotalIncome,
                    'amountExpence' => $ThisFireTotalExpence,
                    'percentDiff' => $ThisFirePercentDiff,
                    'cashFlow' => $ThisFireCashFlow,
                    'savingAmount' => $ThisFireSavingAmount,
                    'savingRate' => $ThisFireSavingRate,
                ];

                #print "********\n";
                #print_r($this->tax);
                #print_r($this->dataH[$assetname][$year]['fortune']);

                #print "i:$income - e:$expence, rest: $rest, restAccumulated: $restAccumulated\n";

                #print_r($this->dataH[$assetname][$year]['cashflow']);

                ########################################################################################################
                if($expenceRepeat) {
                    $prevExpence      = $expence * $expenceChangerate;
                } else {
                    $prevExpence      = null;
                }

                if($incomeRepeat) {
                    $prevIncome       = $income * $incomeChangerate;
                } else {
                    $prevIncome       = null;
                }

                if($assetRepeat) {
                    $prevAssetValue   = $assetValue * $assetChangerate;
                } else {
                    $prevAssetValue   = null;
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

        #dd($this->dataH['nordnet']);
        $this->group();
    }

    public function add($assettname, $year, $type, $dataH){
        #$this->dataH[$assettname][$year][$type] = $dataH;
    }

    public function percentToDecimal($percent){

        #print "** percent: $percent\n";
        if($percent != null && !is_numeric($percent)) { #Allow to read the changerate from the changerate config

            #Remove the "changerates." from the text
            preg_match('/changerates.(\w*)/i', $percent, $matches, PREG_OFFSET_CAPTURE);
            $percent = Arr::get($this->changerate, $matches[1][0], null);
            #print "## percent: '$percent'\n";
        }
        #print "-- percent: '$percent'\n";


#        if($percent <> 0 && is_numeric($percent)) { #Allow numbers directly
        if($percent <> 0) { #Allow numbers directly
            #$percent = ($percent / 100) + 1;
            #print "## return: '$percent'\n";
            return ($percent / 100) + 1;
        } else {
            return 0; #We need zero in return for tests to be correct, if we found no value
        }
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
    public function valueAdjustment($assetname, $year, $type, $prevValue, $thisValue, $rule = NULL, $transfer, $factor = 1){
        #Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor has memory for now.
        $value = null;
        $match = null;
        $diff = 0; #The difference between old and new value, used for transfer
        $transferValue = null; #Rules on how to transfer value from other assets, is added after new value is calculated
        $explanation = '';

        #print "INNKOMMENDE: $assetname.$year.$type, PV: $prevValue, TV: $thisValue, rule: $rule, transfer: $transfer, factor: $factor\n";

        if($rule){
            if(preg_match('/(\+|\-)(\d*)\/(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #Divison ex 1/12
                list($value, $rule, $match) = $this->divisor($prevValue, $matches);
                $diff = $prevValue - $value;
                $explanation = "Addingrule: $rule";
            }
        }
        elseif($thisValue == null) {
            #Set it to the previous value
            $value = $prevValue; #Previous value is already factored, only new values has to be factored
            $diff = 0;
            if($value != null) {
                #print "value: $value\n";
                $explanation = "Using previous value: '$value'";
            }
        } elseif(preg_match('/(\+|\-)(\d*)(\%)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)) {
            #Percentages
            if($matches[1][0] == '-') {
                $value = $prevValue * $factor * ((-$matches[2][0] / 100) + 1);
            } else {
                $value = $prevValue * $factor * (($matches[2][0] / 100) + 1);
            }
            $diff = $value - $prevValue;
            $explanation = "Percent: " . $matches[2][0] . "%";
        } elseif(preg_match('/(\+|\-)(\d*)\/(\d*)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)){
            #Divison ex 1/12
            list($value, $rule, $match) = $this->divisor($prevValue, $matches);
            $diff = $prevValue - $value;
            $explanation = "Addingrule: $rule";

        } elseif(preg_match('/(\+|\-)(\d*)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)) {
            #number that starts with + or - (to be added or subtracted
            $diff = $thisValue * $factor;
            $value = $prevValue + $diff; #Should fix both + and -
            #$rule = $thisValue; #New
            $explanation = "Addingdiff: $diff";

        } elseif(preg_match('/(\=)(\d*)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)) {
            #number that starts with = Fixed number override
            $value = $matches[2][0] * $factor; #Should fix both + and -
            $diff = 0;
            $explanation = "Fixed number override: $value";

        } elseif(is_numeric($thisValue) && !preg_match('/(\+|\-)(\d*)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)){
            #A normal value will override all logic from earlier years, also rules and divisors. A normal number is a number without leading + or -, it will just be set to the number

            #Its a normal number
            $value = $thisValue * $factor; #Set it to the given value
            $diff = 0;
            $explanation = "Fixed number override: $value";
        }

        #Check if any value transfers should be done between assets
        if($transfer) {
            #print "$transfer\n";
        }
        if(preg_match('/(\w+\.\$\w+\.\w+\.\w+)(\*|=)([0-9]*[.]?[0-9]+|value|diff)/i', $transfer, $matches, PREG_OFFSET_CAPTURE)) {
        #if(preg_match('/(\w+\.\$\w+\.\w+\.\w+)\*|=(value|amount)/i', $transfer, $matches, PREG_OFFSET_CAPTURE)) {

            #match this pattern:         "transfer": "salary.$year.income.value*0.05",
            $dotpath = str_replace(
                ['$year'],
                [$year],
                $matches[1][0]);

            #dd($matches);
            #print "PV: $prevValue, TV: $thisValue,  value: $value = rule: $rule - match: $match\n";

            if($matches[2][0] == '=') {
                #Add the value deducted here to the dotpath (transfers value from an value asset to typical income)

                if($matches[3][0] == 'diff' && $diff <> 0) { #Transfer the deducted diff from the amount
                    $explanation .= " transfer diff " . number_format($diff, 2, ',', ' ') . " to $dotpath\n";

                    Arr::set($this->dataH, $dotpath, Arr::get($this->dataH, $dotpath, 0) + $diff); #The real transfer from this asset to another takes place here

                } elseif($matches[3][0] == 'value' && $value <> 0)  { #Tramsfer the entire value
                    $explanation .= " transfer value " . number_format($value, 2, ',', ' ') . " to $dotpath, exisiting value: " . Arr::get($this->dataH, $dotpath, 0)  . "\n";
                    Arr::set($this->dataH, $dotpath, Arr::get($this->dataH, $dotpath, 0) + $value); #The real transfer from this asset to another takes place here
                    $value = 0; #We have moved it, nothing left here.
                }
            } else {
                #Just calculate a value to be added (nothing subtracted)
                $transferValue = Arr::get($this->dataH, $dotpath, 0) * $matches[3][0];
                $explanation .=  " increasing value with " . $matches[3][0] . " of $dotpath $transferValue";
            }
            #print "$year - $explanation\n";
        }

        #Note: This only works correctly if the value we are retrieving from is processed before the one we are calculationg, so ut realy should check if it was processed before or not, for correct handling.
        $orgValue = Arr::get($this->dataH, $assetname . "." . $year . "." . $type . ".amount", 0); #We have to add values transferred in the datahH structure from earlier. The original stored value. Note this is sequenze sensitive.
        $value += $transferValue + $orgValue;

        if($orgValue > 0) {
            #print "$assetname.$year.$type - Transfer: $transferValue, PV: $prevValue, TV: $thisValue,  value: $value, orgValue=$orgValue rule: $rule - match: $match\n";
        }

        if($type != 'asset') {
            #print "value:$value, rule:$rule thisValue:$thisValue prevValue:$prevValue diff:$diff factor:$factor type: $type, explanation:$explanation\n";
        }
        return [$value, $rule, $explanation]; #Rule is adjusted if it is a divisor, it has to be remembered to the next round
    }

    public function divisor($prevValue, $matches) {

        $rule = null;

        if($matches[1][0] == '-') {
            $value = $prevValue - ($prevValue / $matches[3][0]);
        } else {
            $value = $prevValue + ($prevValue / $matches[3][0]);
        }
        $matches[3][0]--; #We reduce the divisor each time we run it, so its the same as the remaining runs.
        if($matches[3][0] > 0) {
            $rule = $matches[1][0] . $matches[2][0] . "/" . $matches[3][0];
        }
        $match = "Divisor";

        return [$value, $rule, $match];
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
        #print_r($this->groupH);
    }

    private function groupFortuneTax($year)
    {
        Arr::set($this->totalH, "$year.fortune.taxAmount", $this->fortuneTaxCalculation(Arr::get($this->totalH, "$year.fortune.taxableAmount", 0)));

        Arr::set($this->companyH, "$year.fortune.taxAmount", $this->fortuneTaxCalculation(Arr::get($this->companyH, "$year.fortune.taxableAmount", 0)));

        Arr::set($this->privateH, "$year.fortune.taxAmount", $this->fortuneTaxCalculation(Arr::get($this->privateH, "$year.fortune.taxableAmount", 0)));
    }

    private function fortuneTaxCalculation($AmountTaxableFortune) {

        if ($AmountTaxableFortune <= 1700000) {
            return 0; #Det betales ikke formuesskatt på skattbar formue under 1.7 mill. FIX lese fra confid, støtte årlige forskjeller.
        } else {
            return $AmountTaxableFortune - 1700000;
        }
    }

    private function groupDebtCapacity($year)
    {
        Arr::set($this->totalH, "$year.potential.debtCapacity", Arr::get($this->totalH, "$year.potential.loan", 0) - Arr::get($this->totalH, "$year.mortgage.balance", 0));

        Arr::set($this->companyH, "$year.potential.debtCapacity", Arr::get($this->companyH, "$year.potential.loan", 0) - Arr::get($this->companyH, "$year.mortgage.balance", 0));

        Arr::set($this->privateH, "$year.potential.debtCapacity", Arr::get($this->privateH, "$year.potential.loan", 0) - Arr::get($this->privateH, "$year.mortgage.balance", 0));
    }

    #Calculates on data that is summed up in the group
    #FIX: Much better if we could use calculus here to reduce number of methods, but to advanced for the moment.
    function groupFireSaveRate($year){
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

    private function groupFirePercentDiff($year){


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

    private function additionToGroup($year, $meta, $data, $dotpath) {

        if(Arr::get($data, $dotpath)) {

            #Just to create an empty object, if it has no values.
            Arr::set($this->totalH, "$year.$dotpath", Arr::get($this->totalH, "$year.$dotpath", 0) + Arr::get($data, $dotpath));
            #print "Addtogroup:  " . Arr::get($this->totalH, "$year.$dotpath") . " = " . Arr::get($this->totalH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";


            #Company
            if (Arr::get($meta, 'group') == 'company') {
                #Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene
                Arr::set($this->companyH, "$year.$dotpath", Arr::get($this->companyH, "$year.$dotpath", 0) + Arr::get($data, $dotpath));
            }

            #Private
            if (Arr::get($meta, 'group') == 'private') {
                #Skaper trøbbel med sorteringsrekkefølgen at vi hopper over rekkene.
                Arr::set($this->privateH, "$year.$dotpath", Arr::get($this->privateH, "$year.$dotpath", 0) + Arr::get($data, $dotpath));
                #print "private: $year.$dotpath :  " . Arr::get($this->privateH, "$year.$dotpath") . " = " . Arr::get($this->privateH, "$year.$dotpath", 0) . " + " . Arr::get($data, $dotpath) . "\n";
            }

            #Grouping
            $grouppath = Arr::get($meta, 'group') . ".$year.$dotpath";
            $typepath = Arr::get($meta, 'type') . ".$year.$dotpath";
            Arr::set($this->groupH, $grouppath, Arr::get($this->groupH, $grouppath, 0) + Arr::get($data, $dotpath));
            Arr::set($this->groupH, $typepath, Arr::get($this->groupH, $typepath, 0) + Arr::get($data, $dotpath));
        }
    }

    private function setToGroup($year, $meta, $data, $dotpath) {

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

    public function replace_in_array($find, $replace, &$array) {
        array_walk_recursive($array, function(&$array) use($find, $replace) {

            #print "$array = $find\n";
            if($array === $find) {
                #dd($array);
                $array= $replace;
            }
        });
        return $array;
    }

    private function taxCalculation($taxtype, $year, $income, $expence, $assetValue, $firstAssetValue, $firstAssetYear, $PercentTaxableYearly, $PercentTaxableRealization, $PercentDeductableYearly, $PercentDeductableRealization) {
        #Forskjell på hva man betaler skatt av
        $cashflow = 0;
        $potentialIncome = 0;
        $AmountTaxableYearly = 0;
        $AmountTaxableRealization = 0;
        $AmountDeductableYearly = 0;
        $AmountDeductableRealization = 0;
        $numberOfYears = $year - $firstAssetYear;

        #print "$year: numberOfYears: $numberOfYears\n";

        if ($taxtype == 'salary') {
            $AmountTaxableYearly = $income * $PercentTaxableYearly;
            $AmountTaxableRealization = 0;
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            $potentialIncome = $income;

        } elseif ($taxtype == 'house') {
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $AmountTaxableYearly = ($income - $expence) * $PercentTaxableYearly;
            $AmountTaxableRealization = 0;  #Salg av eget hus er alltid skattefritt om man har bodd der minst ett år siste 2 år (regne på det?)
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            $potentialIncome = $income;

        } elseif ($taxtype == 'cabin') {
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $AmountTaxableYearly = ($income - 10000) * $PercentTaxableYearly; #Airbnb skatten
            $AmountTaxableRealization = 0;  #Men må ha hatt hytta mer enn 5 eller 8 år for å bli skattefritt. (regne på det?)
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            $potentialIncome = $income; #Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

        } elseif ($taxtype == 'rental') {
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $AmountTaxableYearly = ($income - $expence) * $PercentTaxableYearly;
            if($assetValue > 0) {
                $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien skal skattes ved salg
            }
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            #$potentialIncome = (($income - $AmountTaxableYearly) / 12) * 10; #Bank beregning, ikke sunn fornuft, ikke med skatt
            $potentialIncome = $income; #Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

        } elseif ($taxtype == 'stock') {
            #Hm. Aksjer som selges skattes bare som formuesskatt og ved realisasjon
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            #NOTE: Skjermingsfradrag
            #NOTE: Stor forskjell på skattlegging mot privat 35.2%vs bedrift 0%?.
            $AmountTaxableYearly = 0;
            if($assetValue > 0) {
                $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien skal skattes ved salg?
            }
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            $potentialIncome = $income - $AmountTaxableYearly;

        } elseif ($taxtype == 'fond') {
            #Hm. fond i praksis bare eid i firmaer, alt privat i ASK og skattes bare ved realisasjon + formuesskatt
            $AmountTaxableYearly = 0;
            if($assetValue > 0) {
                $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            $potentialIncome = $income - $AmountTaxableYearly;

        } elseif ($taxtype == 'ask') {
            #Aksjesparekonto. TODO Fix. Kun skatt ved salg??? Ikke årlig
            $AmountTaxableYearly = 0; #Ikke årlig skatt på ASK
            if($assetValue > 0) {
                $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            $potentialIncome = $income;


        } elseif ($taxtype == 'cash') {
            #ToDo: Man skal bare betale skatt av rentene
            $AmountTaxableYearly = $income * $PercentTaxableYearly; #ToDO FIX
            $AmountTaxableRealization = 0;  #Ingen skatt ved salg.
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            $potentialIncome = $income - $AmountTaxableYearly;

        } else {
            #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
            $AmountTaxableYearly = ($income - $expence) * $PercentTaxableYearly;
            if($assetValue > 0) {
                $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
            }
            $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
            $potentialIncome = 0;  #For nå antar vi ingen inntekt fra annet enn lønn eller utleie, men utbytte vil også telle.
        }

        #print "$taxtype: PercentTaxableYearly: $PercentTaxableYearly, PercentTaxableRealization: $PercentTaxableRealization, AmountTaxableRealization: $AmountTaxableRealization\n";

        return [$cashflow, $potentialIncome, $AmountTaxableYearly, $AmountTaxableRealization, $AmountDeductableYearly, $AmountDeductableRealization];
    }
}
