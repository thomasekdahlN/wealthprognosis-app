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
            $assetCurrentTransferResource = null;
            $assetPrevTransferResource = null;
            $assetCurrentTransferRule = null;
            $assetPrevTransferRule = null;

            $incomeCurrentValue = 0;
            $incomePrevValue = 0;
            $incomeChangerateDecimal = 0;
            $incomeChangerateValue = "";
            $incomeRule = null;
            $incomePrevRepeat = false;
            $incomeCurrentRepeat = false;
            $incomeCurrentTransferResource = null;
            $incomePrevTransferResource = null;
            $incomeCurrentTransferRule = null;
            $incomePrevTransferRule = null;

            $expenceCurrentValue = 0;
            $expencePrevValue = 0;
            $expenceChangerateDecimal = 0;
            $expenceChangerateValue = "";
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

                $expenceCurrentRepeat = Arr::get($asset, "expence.$year.repeat", null);
                if(isset($expenceCurrentRepeat)) {
                    $expencePrevRepeat = $expenceCurrentRepeat;
                } else {
                    $expenceCurrentRepeat = $expencePrevRepeat;
                }

                list($expenceChangeratePercent, $expenceChangerateDecimal, $expenceChangerateValue, $explanation) = $this->changerate->convertChangerate(0, Arr::get($asset, "expence.$year.changerate", null), $year, $expenceChangerateValue);

                $expenceCurrentValue = Arr::get($asset, "expence.$year.value", 0); #Expence is added as a monthly repeat in config

                list($expenceCurrentValue, $expenceRule, $explanation) = $this->helper->valueAdjustment(0, $expencePrevValue, $expenceCurrentValue, $expenceRule,12);

                $this->dataH[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangeratePercent / 100,
                    'amount' => $expenceCurrentValue * 12,
                    'description' => Arr::get($asset, "expence.$year.description") . $explanation,
                ];

                #print "$year: expenceChangeratePercent = $expenceChangerateDecimal - expence * $expence\n";

                #####################################################
                #income
                $incomeCurrentTransferResource = Arr::get($asset, "income.$year.transferResource", null);
                if(isset($incomeCurrentTransferResource)) {
                    $incomePrevTransferResource = $incomeCurrentTransferResource;
                } else {
                    $incomeCurrentTransferResource = $incomePrevTransferResource; #Remembers a transfer until zeroed out in config.
                }

                $incomeCurrentTransferRule = Arr::get($asset, "income.$year.transferRule", null);
                if(isset($incomeCurrentTransferRule)) {
                    $incomePrevTransferRule = $incomeCurrentTransferRule;
                } else {
                    $incomeCurrentTransferRule = $incomePrevTransferRule; #Remembers a transfer until zeroed out in config.
                }



                $incomeCurrentRepeat = Arr::get($asset, "income.$year.repeat", null);
                if(isset($incomeCurrentRepeat)) {
                    $incomePrevRepeat = $incomeCurrentRepeat;
                } else {
                    $incomeCurrentRepeat = $incomePrevRepeat;
                }

                list($incomeChangeratePercent, $incomeChangerateDecimal, $incomeChangerateValue, $explanation) =  $this->changerate->convertChangerate(false, Arr::get($asset, "income.$year.changerate", null), $year, $incomeChangerateValue);

                $incomeCurrentValue = Arr::get($asset, "income.$year.value", 0); #Income is added as a yearly repeat in config

                list($incomeCurrentValue, $incomeRule, $explanation) = $this->helper->valueAdjustment(false, $incomePrevValue, $incomeCurrentValue, $incomeRule, 12);

                #print "Income transfer before: $assetname.$year, incomeCurrentValue:$incomeCurrentValue, incomeCurrentTransferResource:$incomeCurrentTransferResource, incomeCurrentTransferRule:$incomeCurrentTransferRule\n";
                list($incomeCurrentValue, $incomeTransferedValue, $incomePrevTransferRule, $explanation) = $this->valueTransfer(false, $assetname, $year, $incomeCurrentValue, $incomeCurrentTransferResource, $incomeCurrentTransferRule, 12);
                #print "Income transfer after: $assetname.$year, incomeCurrentValue:$incomeCurrentValue, incomeTransferedValue:$incomeTransferedValue, incomePrevTransferRule:$incomePrevTransferRule, explanation: $explanation\n";

                #Maybe the asset is added to late, but it is at least not multiplied by 12
                $this->dataH[$assetname][$year]['income'] = [
                    'changerate' => $incomeChangeratePercent / 100,
                    'amount' => $incomeCurrentValue + Arr::get($this->dataH, "$assetname.$year.income.amount", 0),
                    'description' => Arr::get($asset, "income.$year.description") . $explanation,
                    ];

                ########################################################################################################
                #Assett
                $assetCurrentTransferResource = Arr::get($asset, "value.$year.transferResource", null);
                if(isset($assetCurrentTransferResource)) {
                    $assetPrevTransferResource = $assetCurrentTransferResource;
                } else {
                    $assetCurrentTransferResource = $assetPrevTransferResource; #Remembers a transfer until zeroed out in config.
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

                list($assetChangeratePercent, $assetChangerateDecimal, $assetChangerateValue, $explanation) = $this->changerate->convertChangerate(false, Arr::get($asset, "value.$year.changerate", null), $year, $assetChangerateValue);
                #print "$year: " . $this->changerate->decimalToDecimal($assetChangerateDecimal) . "\n";

                $assetCurrentValue = Arr::get($asset, "value.$year.value", 0);
                $assetCurrentTaxValue = Arr::get($asset, "value.$year.taxvalue", 0);

                #print "Asset før: year: $year assetPrevValue:$assetPrevValue assetCurrentValue:$assetCurrentValue assetRule:$assetRule\n";
                list($assetCurrentValue, $assetRule, $explanation) = $this->helper->valueAdjustment(false, $assetPrevValue, $assetCurrentValue, $assetRule, 1);
                #print "Asset etter: year:$year assetCurrentValue: $assetCurrentValue, assetRule:$assetRule explanation: $explanation\n";

                #We must check if values has been set in the structure already and add them

                $assetCurrentValue += Arr::get($this->dataH, "$assetname.$year.value.value", 0); #If current value is not an amount at this point, this will crash

                #print "Asset transfer before: $assetname.$year, assetCurrentValue:$assetCurrentValue, assetCurrenttransferResource:$assetCurrentTransferResource, AssetCurrentTransferRule:$assetCurrentTransferRule\n";
                list($assetCurrentValue, $assetTransferedValue, $assetPrevTransferRule, $explanation) = $this->valueTransfer(false, $assetname, $year, $assetCurrentValue, $assetCurrentTransferResource, $assetCurrentTransferRule, 1);
                #print "Asset transfer after: $assetname.$year, assetCurrentValue:$assetCurrentValue, assetTransferedValue:$assetTransferedValue, assetPrevTransferRule:$assetPrevTransferRule, explanation: $explanation\n";

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
                    $expenceCurrentValue        = 0;
                    $expencePrevValue           = 0;
                    $expenceChangerateValue     = 0;
                    $expenceChangerateDecimal   = null;
                    $expenceChangeratePercent   = null;
                    $expenceCurrentTransferResource = null;
                    $expenceCurrentTransferRule   = null;
                    $expencePrevTransferResource  = null;
                    $expencePrevTransferRule   = null;
                }

                if($incomeCurrentRepeat) {
                    $incomePrevValue       = $incomeCurrentValue * $incomeChangerateDecimal;
                } else {
                    $incomeCurrentValue         = 0;
                    $incomePrevValue            = 0;
                    $incomeChangerateValue      = 0;
                    $incomeChangerateDecimal    = null;
                    $incomeChangeratePercent    = null;
                    $incomeCurrentTransferResource     = null;
                    $incomeCurrentTransferRule   = null;
                    $incomePrevTransferResource     = null;
                    $incomePrevTransferRule   = null;

                }

                if($assetCurrentRepeat) {
                    $assetPrevValue   = round($assetCurrentValue * $assetChangerateDecimal);
                    $assetPrevTaxValue = round($assetCurrentTaxValue * $assetChangerateDecimal);
                } else {
                    $assetCurrentValue          = 0;
                    $assetPrevValue             = 0;
                    $assetPrevTaxValue          = 0;
                    $assetChangerateValue       = 0;
                    $assetChangerateDecimal     = null;
                    $assetChangeratePercent     = null;
                    $assetRule                  = null;
                    $assetCurrentTransferResource = null;
                    $assetCurrentTransferRule     = null;
                    $assetPrevTransferResource = null;
                    $assetPrevTransferRule     = null;

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

    #Either creates an amount based on another amount or transfers parts of an amount to another amount.
    #Example
    #transferResource": "salary.$year.income.value",  #Example OPT creatwes 5% of income as OTP without reducing OTP
    #tranferRule: add&5%. Plus does not reduce the asset, it just adds to the asset you are transferring to.
    #transferResource": "income.$year.income.amount",  #Example using OTP until death reduces the OTP value accordingly
    #tranferRule: transfer&-1/12 (always compared to the asset you are in). Minus reduces the asset.
    #transferRule add|subtract|transfer&calculations
    #available calculations: 5% (percent), 1000 (fix amount), 1/12 (divisor)
    #add=adds to the resource calculated from the current value, but does not change the current value
    #subtract=subtracts from the resource calculated from the current value, but does not change the current value
    #transfer=adds to the resource calculated from the current value, and subtracts this value from the current resource

    #calculations are always done on value input into the function
    public function valueTransfer(bool $debug, string $assetname, int $year,  int $value, ?string $transferResource, ?string $transferRule, int $factor = 1)
    {

        $newValue = 0;
        $newAssetValue = 0;
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
                print "**** $assetname.$year: value: $value, transferResource: $transferResource, transferRule: $transferRule\n";
                #print " rule: " . $ruleH[0] . " " . $ruleH[1] . "\n";
            }

            list($newValue, $rulepart, $explanation) = $this->helper->calculateRule(true, $value, $ruleH[1]);
            $rule = $ruleH[0] . "&$rulepart";
            #ToDo check that an asset can not go into negative value?

            if ($newValue) {
                if ($ruleH[0] == 'transfer') {
                    ############################################################################################################
                    #Transfer value to another asset, has to update the datastructure of this asset directly

                    $explanation .= " transfer $newValue to $transferResource";
                    Arr::set($this->dataH, $transferResource, Arr::get($this->dataH, $transferResource, 0) + ($newValue * $factor)); #The real transfer from this asset to another takes place here

                    ############################################################################################################
                    #reduce value from this assetValue
                    $explanation .= " reduce $assetname.$year by $newValue\n";
                    $newAssetValue = $value - $newValue;

                } elseif ($ruleH[0] == 'add') {

                    #print_r($resourceH);

                    $explanation .= " transfer add $newValue to $transferResource";
                    Arr::set($this->dataH, $transferResource, Arr::get($this->dataH, $transferResource, 0) + ($newValue * $factor)); #The real transfer from this asset to another takes place here
                    $newAssetValue = $value;

                    if($debug) {
                        print_r($this->dataH[$resourceH[0]][$year]);
                    }

                } elseif ($ruleH[0] == 'subtract') {

                    $explanation .= " transfer subtract $newValue to $transferResource";
                    Arr::set($this->dataH, $transferResource, Arr::get($this->dataH, $transferResource, 0) - ($newValue * $factor)); #The real transfer from this asset to another takes place here
                    $newAssetValue = $value;

                }
            }
        } else {
            $explanation .= " no transferResource && transferRule\n";
            $newAssetValue = $value;
        }

        if ($debug) {
            print "####--- explanation: $explanation\n";
        }

        return [$newAssetValue, $newValue, $rule, $explanation];
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
