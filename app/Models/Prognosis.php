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
    public $periodStart;
    public $periodEnd;
    public $config;
    public $dataH = array();
    public $assetH = array();
    public $totalH = array();
    public $groupH = array();
    public $privateH = array();
    public $companyH = array();


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

    public function __construct($config)
    {

        #$this->test();
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
            $PercentTaxableFortune = Arr::get($this->config, "tax." . $taxtype. ".fortune", 0) / 100;


            #print "$assetname: $taxtype: PercentTaxableYearly: $PercentTaxableYearly, PercentTaxableRealization: $PercentTaxableRealization\n";

            $firstAssetValue = 0;
            $prevAssetValue = 0;
            $prevAssetRule = null;
            $prevAssetChangerate = 1;
            $prevAssetRepeat = false;

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

            for ($year = $this->periodStart; $year <= $this->periodEnd; $year++) {

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

                $expenceThis = Arr::get($asset, "expence.$year.value", null) * 12; #Expence is added as a monthly repeat in config

                #list($expence, $prevExpenceRule) = $this->valueAdjustment($prevExpence, $expenceThis, $prevExpenceRule);

                $this->dataH[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangerate,
                    'amount' => $expence,
                    'description' => Arr::get($asset, "expence.$year.description"),
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

                $incomeThis = Arr::get($asset, "income.$year.value", null) * 12; #Income is added as a monthly repeat in config

                #list($income, $prevIncomeRule) = $this->valueAdjustment($prevIncome, $incomeThis, $prevIncomeRule);

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

                $assetChangerate = $this->percentToDecimal(Arr::get($asset, "value.$year.changerate", null));
                if($assetChangerate) {
                    $prevAssetChangerate = $assetChangerate;
                } else {
                    $assetChangerate = $prevAssetChangerate;
                }

                $assetThis = Arr::get($asset, "value.$year.value", null); #Income is added as a monthly repeat in config

                list($assetValue, $prevAssetValue, $prevAssetRule) = $this->valueAdjustment($prevAssetValue, $assetThis, $prevAssetRule);

               $this->dataH[$assetname][$year]['asset'] = [
                    'amount' => $assetValue,
                    'amountLoanDeducted' => $assetValue,
                    'changerate' => $assetChangerate,
                    'description' => Arr::get($asset, "value.$year.description") . " Asset rule " . $prevAssetRule,
                ];


                $AmountDeductableYearly = 0; #Fratrekk klarer vi først når vi beregner lån
                $AmountDeductableRealization = 0; #Fratrekk klarer vi først når vi beregner lån
                $AmountTaxableFortune  = 0; #Den skattemessige formuen. Dvs den formuen det betales formuesskatt av.
                $AmountTaxableYearly = 0;
                $AmountTaxableRealization = 0;

                    #Forskjell på hva man betaler skatt av
                    $potentialIncome = 0;
                    if ($taxtype == 'salary') {
                        $AmountTaxableYearly = $income * $PercentTaxableYearly;
                        $AmountTaxableRealization = ($assetValue - 0) * $PercentTaxableRealization;
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                        $potentialIncome = $income;

                    } elseif ($taxtype == 'house') {
                        #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                        $AmountTaxableYearly = ($income - $expence) * $PercentTaxableYearly;
                        $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                        $potentialIncome = $income; #Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

                    } elseif ($taxtype == 'cabin') {
                        #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                        $AmountTaxableYearly = $income * $PercentTaxableYearly;
                        $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                        $potentialIncome = $income; #Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

                    } elseif ($taxtype == 'rental') {
                        #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                        $AmountTaxableYearly = ($income - $expence) * $PercentTaxableYearly;
                        $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                        #$potentialIncome = (($income - $AmountTaxableYearly) / 12) * 10; #Bank beregning, ikke sunn fornuft, ikke med skatt
                        $potentialIncome = $income; #Bank beregning, ikke sunn fornuft, kan bare bergne inn 10 av 12 mnd som utleie. Usikker på om skatt trekkes fra

                    } elseif ($taxtype == 'stock' || $taxtype == 'fond') {
                        #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                        $AmountTaxableYearly = ($income - $expence) * $PercentTaxableYearly;
                        $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                        $potentialIncome = $income - $AmountTaxableYearly;

                    } else {
                        #Antar det er vanligst å skatte av fortjenesten etter at utgifter er trukket fra
                        $AmountTaxableYearly = ($income - $expence) * $PercentTaxableYearly;
                        $AmountTaxableRealization = ($assetValue - $firstAssetValue) * $PercentTaxableRealization;  #verdien nå minus inngangsverdien....... Så må ta vare på inngangsverdien
                        $cashflow = $income - $expence - $AmountTaxableYearly + $AmountDeductableYearly;
                        $potentialIncome = 0;  #For nå antar vi ingen inntekt fra annet enn lønn eller utleie, men utbytte vil også telle.
                    }

                    $AmountTaxableFortune = $assetValue * $PercentTaxableFortune;
                    $restAccumulated += $cashflow;


                    #Free money to spend
                    $this->dataH[$assetname][$year]['cashflow'] = [
                        'amount' => $cashflow,
                        'amountAccumulated' => $restAccumulated,
                    ];


                    #Calculate the potential max loan you can handle base on income, tax adjusted - as seen from the bank.
                    #print "$assetname - p:$potentialIncome = i:$income - t:$AmountTaxableYearly\n";
                    $this->dataH[$assetname][$year]['potential'] = [
                        'income' => $potentialIncome,
                        'loan' => $potentialIncome * 5
                    ];
                #}

                #FIRE - Financial Independence Retire Early - beregninger på assets
                #Achievement er hvor mye du mangler for å nå målet? Feil navn?
                # amount = assetverdi - lån i beregningene + inntekt? (Hvor mye er 4% av de reelle kostnadene + inntekt (sannsynligvis kunn inntekt fra utleie)
                # amountAchievement = amount - expences
                #NOTE: Svakhet at den ikke hensyntar lån som utgift!!!!!!!!!!
                if($taxtype == 'house' || $taxtype == 'rental' || $taxtype == 'cabin'|| $taxtype == 'car' || $taxtype == 'boat'|| $taxtype == 'salary') {
                    #Kan ikke selge biter av en asset her, her regnes kun inntekt
                    $ThisFirePercent = 0;
                    $ThisFireIncome = 0; #Only asset value
                } else {
                    #Her kan vi selge biter av en asset (meta tagge opp det istedenfor tro?
                    $ThisFirePercent = 0.04; #4% av en salgbar asset verdi
                    $ThisFireIncome = $assetValue * $ThisFirePercent; #Only asset value
                    $AmountTaxableYearly        += $ThisFireIncome * $PercentTaxableYearly; #Legger til skatt på utbytte fra salg av en % av disse eiendelene her (De har neppe en inntekt, men det skal også fikses fint)
                    #print "ATY: $AmountTaxableYearly        += TFI:$ThisFireIncome * PTY:$PercentTaxableYearly;\n";
                }

                #NOTE - Deductable yarly blir bare satt i låneberegningen, så den må legges til globalt der.
                $ThisFireTotalIncome = $ThisFireIncome + $income + $AmountDeductableYearly; #Percent of asset value + income from asset. HUSK KUN INNTEKTER her
                $ThisFireTotalExpence = $expence + $AmountTaxableYearly;
                #print "$assetname - FTI: $ThisFireTotalIncome = FI:$ThisFireIncome + I:$income + D:$AmountDeductableYearly\n"; #Percent of asset value + income from asset

                $ThisFireAmountDiff = $ThisFireTotalIncome - $ThisFireTotalExpence; #Hvor lang er man unna fire målet

                if($expence > 0) {
                    $ThisFirePercentDiff = $ThisFireTotalIncome / $ThisFireTotalExpence; #Hvor mange % unna er inntektene å dekke utgiftene.
                } else {
                    $ThisFirePercentDiff = 1;
                }
                $this->dataH[$assetname][$year]['fire'] = [
                    'percent' => $ThisFirePercent,
                    'amountIncome' => $ThisFireTotalIncome,
                    'amountExpence' => $ThisFireTotalExpence,
                    'percentDiff' => $ThisFirePercentDiff,
                    'amountDiff' => $ThisFireAmountDiff
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
                    'amountFortune' => $AmountTaxableFortune
                ];

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
                $this->dataH = (new Amortization($this->config, $this->dataH, $mortgage, $assetname))->get();
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

    public function percentToDecimal($percent){

        #print "** percent: $percent\n";
        if($percent != null && !is_numeric($percent)) { #Allow to read the numbers from a config

            $percent = Arr::get($this->config, $percent, null);
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
        -- "1000" - Value is set to 1000.
        -- "+10%" - Adds 10% to value (Supported now, but syntax : 10)
        -- "+1000" - Adds 1000 to value
        -- "-10%" - Subtracts 10% from value
        -- "-1000" - Subtracts 1000 from value (Supported now - same syntax)
        -- =+1/10" - Adds 1 tenth of the amount yearly
        -- =-1/10" - Subtracts 1 tenth of the amount yearly (To simulate i.e OTP payment). The rest amount will be zero after 10
         */
    public function valueAdjustment($prevValue, $thisValue, $rule = NULL){
        #Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor has memory for now.
        $value = null;
        $match = null;

        #print "INNKOMMENDE: PV: $prevValue, TV: $thisValue, rule: $rule\n";

        if($rule){
            if(preg_match('/(\+|\-)(\d*)\/(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #Divison ex 1/12
                list($value, $rule, $match) = $this->divisor($prevValue, $matches);
            }
        }
        elseif($thisValue == null) {
            #Set it to the previous value
            $value = $prevValue;
            $match = "Previous value";
        } elseif(preg_match('/(\+|\-)(\d*)(\%)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)) {
            #Percentages
            if($matches[1][0] == '-') {
                $value = $prevValue * ((-$matches[2][0] / 100) + 1);
            } else {
                $value = $prevValue * (($matches[2][0] / 100) + 1);
            }
            $match = "Percent";
        } elseif(preg_match('/(\+|\-)(\d*)\/(\d*)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)){
            #Divison ex 1/12
            list($value, $rule, $match) = $this->divisor($prevValue, $matches);

        } elseif(preg_match('/(\+|\-)(\d*)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)) {
            #number that starts with + or - (to be added or subtracted
            $value = $prevValue + $thisValue; #Should fix both + and -
            $match = "Adding or subtracting a number";

        }

        #A normal value will override all logic from earlier years, also rules and divisors. A normal number is a number without leading + or -, it will just be set to the number
        if(is_numeric($thisValue) && !preg_match('/(\+|\-)(\d*)/i', $thisValue, $matches, PREG_OFFSET_CAPTURE)){
            #Its a normal number
            $match = "Fixed number override";
            $value = $thisValue; #Set it to the given value
        }

        #print "PV: $prevValue, TV: $thisValue,  value: $value = rule: $rule - match: $match\n";

        return [$value, $prevValue, $rule]; #Rule is adjusted if it is a divisor, it has to be remembered to the next round
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
                $this->calculate($year, $assetH['meta'], $assetH[$year], "tax.amountFortune");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "cashflow.amount");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "cashflow.amountAccumulated");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.payment");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.paymentExtra");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.interestAmount");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.principal");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "mortgage.balance");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "potential.income"); #Beregnet potensiell inntekt slik bankene ser det.
                $this->calculate($year, $assetH['meta'], $assetH[$year], "potential.loan"); #Beregner maks potensielt lån på 5 x inntekt.
                $this->calculate($year, $assetH['meta'], $assetH[$year], "fire.amountIncome");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "fire.amountExpence");
                $this->calculate($year, $assetH['meta'], $assetH[$year], "fire.amountDiff");
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
