<?php

/* Copyright (C) 2025 Thomas Ekdahl
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace App\Services\Prognosis;

use App\Services\AssetTypeService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PrognosisService
{
    public int $thisYear;

    public int $economyStartYear;

    public int $deathYear;

    /** @var array<string, mixed> */
    public array $config;

    /** @var array<string, mixed> */
    public array $tax;

    public object $changerate;

    /** @var array<string, mixed> */
    public array $dataH = [];

    /** @var array<string, mixed> */
    public array $assetH = [];

    /** @var array<string, mixed> */
    public array $totalH = [];

    /** @var array<string, mixed> */
    public array $groupH = [];

    /** @var array<string, mixed> */
    public array $privateH = [];

    /** @var array<string, mixed> */
    public array $companyH = [];

    /** @var array<string, mixed> */
    public array $statisticsH = [];

    /** @var array<string, bool> */
    private array $assetTypeShowStatisticsMap = [];

    public object $postProcessor;

    private AssetTypeService $assetTypeService;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // Get singletons from the service container
        $this->taxincome = app(\App\Services\Tax\TaxIncomeService::class);
        $this->taxfortune = app(\App\Services\Tax\TaxFortuneService::class);
        $this->taxrealization = app(\App\Services\Tax\TaxRealizationService::class);
        $this->changerate = app(\App\Services\Prognosis\ChangerateService::class);
        $this->helper = app(\App\Services\Utilities\HelperService::class);
        $this->rules = app(\App\Services\Utilities\RulesService::class);
        $this->postProcessor = app(\App\Services\Processing\PostProcessorService::class);
        $this->assetTypeService = app(AssetTypeService::class);

        $this->birthYear = (int) Arr::get($this->config, 'meta.birthYear');
        $this->economyStartYear = $this->birthYear + 16; // We look at economy from 16 years of age
        $this->thisYear = now()->year;
        $this->deathYear = (int) $this->birthYear + Arr::get($this->config, 'meta.deathAge');
        // dd($this->config);

        // Preload asset type statistics visibility flags (now using AssetTypeService)
        $this->assetTypeShowStatisticsMap = [];

        foreach ($this->config as $assetname => $assetconfig) {

            if ($assetname == 'meta') {
                Log::info('Skipping meta configuration', ['asset' => $assetname]);
                if (app()->runningInConsole()) {
                    echo "--- Jump over meta $assetname\n";
                }

                continue;
            } // Hopp over metadata, reserved keyword meta.

            if (! $this->ArrGetConfig("$assetname.meta.active")) {
                // print "--- Asset $assetname is not active\n";
                continue;
            } // Jump past inactive assets

            // Get debug flag for this asset from metadata
            $debug = (bool) $this->ArrGetConfig("$assetname.meta.debug");

            Log::info('Processing asset', ['asset' => $assetname, 'debug' => $debug]);
            if (app()->runningInConsole()) {
                echo "#################### Asset: $assetname\n";
            }

            // Store all metadata about the asset, the rest is yearly calculations
            $this->dataH[$assetname]['meta'] = $this->ArrGetConfig("$assetname.meta"); // Copy metadata into dataH
            $assetType = $this->ArrGetConfig("$assetname.meta.type");

            // Resolve tax type from asset type using centralized helper
            $this->config[$assetname]['meta']['tax_type'] = \App\Support\TaxTypeResolver::resolve($assetType);

            $taxGroup = $this->ArrGetConfig("$assetname.meta.group"); // How tax is to be calculated for this asset
            $taxProperty = $this->ArrGetConfig("$assetname.meta.taxProperty"); // How tax is to be calculated for this asset
            // Derive tax type via asset type relation and default to 'none' when missing
            $taxType = $this->ArrGetConfig("$assetname.meta.tax_type");

            $firsttime = false; // Only set to true on the first time we see a configuration on this asset.
            $assetMarketAmount = 0;
            $assetInitialEquityAmount = 0;
            $assetInitialPaidAmount = 0;
            $assetInitialAcquisitionAmount = 0;
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

            Log::info('Processing years for asset', [
                'asset' => $assetname,
                'economy_start_year' => $this->economyStartYear,
                'death_year' => $this->deathYear,
            ]);
            if (app()->runningInConsole()) {
                echo "economyStartYear: $this->economyStartYear, deathYear: $this->deathYear\n";
            }

            for ($year = $this->economyStartYear; $year <= $this->deathYear; $year++) {

                $prevYear = $year - 1;
                $path = "$assetname.$year";

                Log::debug('Processing year', ['path' => $path]);
                if (app()->runningInConsole()) {
                    echo "$path\n";
                }

                // #######################################################################################################
                // Expence
                $expenceAmount = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'expence', 'amount');
                $expenceFactor = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'expence', 'factor'); // We do not store this in dataH, we only use it to upscale amounts once to yearly amounts
                $expenceFactor = $this->helper->normalizeFactor($expenceFactor);
                $expenceRule = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'expence', 'rule');
                $expenceTransfer = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'expence', 'transfer');
                $expenceSource = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'expence', 'source');
                $expenceRepeat = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'expence', 'repeat');
                $expenceChangerate = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'expence', 'changerate');

                // echo "Expence adjust before: $assetname.$year, expenceAmount:$expenceAmount, expenceRule: $expenceRule\n";
                [$expenceAmount, $expenceDepositedAmount, $taxShieldAmountX, $expenceRule, $explanation] = $this->applyRule($debug, "$path.expence.amount", $expenceAmount, 0, 0, $expenceRule, $expenceTransfer, $expenceSource, $expenceFactor);
                // echo "Expence adjust after : $assetname.$year, expenceAmount:$expenceAmount, expenceRule: $expenceRule\n";
                // print "$year: expenceChangeratePercent = $expenceChangerateDecimal - expence * $expence\n";

                [$expenceChangeratePercent, $expenceChangerateDecimal, $expenceChangerateAmount, $expenceExplanation] = $this->changerate->getChangerate($debug, $expenceChangerate, $year, $expenceChangerateAmount);
                $expenceAmount = $expenceAmount * $expenceChangerateDecimal;

                // #######################################################################################################
                // Income
                $incomeAmount = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'income', 'amount');
                $incomeFactor = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'income', 'factor'); // We do not store this in dataH, we only use it to upscale amounts once to yearly amounts
                $incomeFactor = $this->helper->normalizeFactor($incomeFactor);
                $incomeRule = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'income', 'rule');
                $incomeTransfer = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'income', 'transfer');
                $incomeSource = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'income', 'source');
                $incomeRepeat = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'income', 'repeat');
                $incomeChangerate = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'income', 'changerate');

                // print "Income adjust before: $assetname.$year, incomeAmount:$incomeAmount, incomeRule:$incomeRule, incomeTransfer:$incomeTransfer, incomeSource: $incomeSource, incomeRepeat: #incomeRepeat\n";
                [$incomeAmount, $incomeDepositedAmount, $taxShieldAmountX, $incomeRule, $explanation] = $this->applyRule($debug, "$path.income.amount", $incomeAmount, 0, 0, $incomeRule, $incomeTransfer, $incomeSource, $incomeFactor);
                // print "Income adjust after: $assetname.$year, incomeAmount:$incomeAmount\n";

                [$incomeChangeratePercent, $incomeChangerateDecimal, $incomeChangerateAmount, $incomeExplanation] = $this->changerate->getChangerate($debug, $incomeChangerate, $year, $incomeChangerateAmount);
                $incomeAmount = $incomeAmount * $incomeChangerateDecimal;

                // ######################################################################################################
                // Mortage - has to be calculated before asset, since we use data from mortgage to calculate asset values correctly.
                $mortgage = $this->ArrGetConfig("$assetname.$year.mortgage"); // Note that Mortgage will be processed from all years frome here to the end - at once in this step. It process the entire mortage not only this year. It will be overwritten be a new mortgage config at a later year.

                if ($mortgage) {
                    // Kjører bare dette om mortgage strukturen i json er utfylt
                    $this->dataH = (new \App\Services\Prognosis\AmortizationService($debug, $this->config, $this->changerate, $this->dataH, $mortgage, $assetname, $year))->get();
                }

                // ######################################################################################################
                // Assett
                // Finn ut om det er det første året med konfig vi har sett på denne asset, vi gjør det ved å se om det finnes noen markedsverdi for forrige år i dataH.
                $assetMarketInitialAmount = $this->configOrPrevValue($debug, $assetname, $year, 'asset', 'marketAmount');
                $assetTaxableInitialAmount = $this->configOrPrevValue($debug, $assetname, $year, 'asset', 'taxableInitialAmount'); // Read from config, because taxable Amount is not related to the assetMarketAmount - typically a cabin is not taxable on a percent of the market value, but a much lower value
                $assetTaxableAmountOverride = $this->configOrPrevValue($debug, $assetname, $year, 'asset', 'taxableAmountOverride');
                $assetChangerate = $this->configOrPrevValue($debug, $assetname, $year, 'asset', 'changerate');

                $assetInitialAcquisitionAmount = $this->ArrGetConfig("$assetname.$year.asset.acquisitionAmount");
                $assetInitialEquityAmount = $this->ArrGetConfig("$assetname.$year.asset.equityAmount");
                $assetInitialPaidAmount = $this->ArrGetConfig("$assetname.$year.asset.paidAmount"); // When paid is retrieved from a config, it is often because of inheritance that you have not paid the market value.

                $assetRule = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'asset', 'rule');
                $assetTransfer = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'asset', 'transfer');
                $assetSource = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'asset', 'source');
                $assetRepeat = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'asset', 'repeat');

                if ($this->ArrGet("$assetname.$prevYear.asset.marketAmount") <= 0 && $assetMarketInitialAmount > 0) {
                    $assetFirstYear = $year;
                    $firsttime = true;
                    // echo "\n\nFirst time: $assetname.$year\n";
                } else {
                    $firsttime = false;
                }

                [$assetChangeratePercent, $assetChangerateDecimal, $assetChangerateAmount, $assetExplanation1] = $this->changerate->getChangerate($debug, $assetChangerate, $year, $assetChangerateAmount);
                // print "$year: $assetChangeratePercent%\n";

                // print "\nAsset1: $assetname.$year assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount: $assetInitialAcquisitionAmount, assetRule:$assetRule\n";
                // FIX: Trouble sending in $assetInitialAcquisitionAmount here, since it is recalculated in the step after.... chicken and egg problem.
                $realizationPrevTaxShieldAmount = $this->ArrGet("$assetname.$prevYear.realization.taxShieldAmount");

                if (isset($this->dataH[$assetname][$prevYear]['realization'])) {
                    // print_r($this->dataH[$assetname][$prevYear]['realization']);
                }

                [$assetMarketInitialAmount, $assetDiffAmount, $realizationTaxShieldAmount, $assetNewRule, $assetExplanation2] = $this->applyRule($debug, "$path.asset.marketAmount", $assetMarketInitialAmount, $assetInitialAcquisitionAmount, $realizationPrevTaxShieldAmount, $assetRule, $assetTransfer, $assetSource, 1);
                if ($assetDiffAmount > 0) {
                    // $assetMarketAmount -= $assetDiffAmount; //EXPERIMENTAL.
                }
                // print "Asset2: $assetname.$year assetMarketAmount: $assetMarketAmount, assetDiffAmount:$assetDiffAmount, assetAcquisitionAmount: $assetInitialAcquisitionAmount, assetNewRule:$assetNewRule explanation: $explanation\n";

                if ($firsttime) {
                    // default values we only set on the first run

                    // echo "*** $assetname.$year.start.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetInitialAcquisitionAmount, assetEquityAmount:$assetInitialEquityAmount, assetPaidAmount: $assetInitialPaidAmount, assetTaxableInitialAmount:$assetTaxableInitialAmount\n";

                    if ($assetInitialAcquisitionAmount <= 0) {
                        // Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetInitialAcquisitionAmount (not changerates), only real money.
                        $assetInitialAcquisitionAmount = $assetMarketInitialAmount;
                    }

                    if ($assetInitialEquityAmount <= 0) {
                        // Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetInitialAcquisitionAmount (not changerates), only real money.
                        $assetInitialEquityAmount = round($assetInitialAcquisitionAmount - $this->ArrGet("$assetname.$year.mortgage.amount"));
                        // echo "    Equity: $assetname.$year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetInitialAcquisitionAmount, assetEquityAmount:$assetInitialEquityAmount, assetPaidAmount: $assetInitialPaidAmount, assetTaxableInitialAmount:$assetTaxableInitialAmount, termAmount: ".$this->ArrGet("$assetname.$year.mortgage.termAmount")."\n";
                    }

                    if ($assetInitialPaidAmount <= 0) {
                        // Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetInitialAcquisitionAmount (not changerates), only real money.
                        $assetInitialPaidAmount = $assetInitialEquityAmount;
                        // echo "    Paid: $assetname.$year.assetMarketAmount:$assetMarketAmount, assetAcquisitionAmount:$assetInitialAcquisitionAmount, assetEquityAmount:$assetInitialEquityAmount, assetPaidAmount: $assetInitialPaidAmount, assetTaxableInitialAmount:$assetTaxableInitialAmount, termAmount: ".$this->ArrGet("$assetname.$year.mortgage.termAmount")."\n";
                    }

                    if ($assetTaxableInitialAmount <= 0) {
                        // Only to be set on first run here, not to be recalculated. But if rules or transfers add money, they are added to $assetInitialAcquisitionAmount (not changerates), only real money.
                        $assetTaxableInitialAmount = $assetMarketInitialAmount + $assetDiffAmount;
                    } else {
                        // Since it is set from before, we have an override situation.
                        $assetTaxableAmountOverride = true;
                    }
                }

                if ($assetInitialAcquisitionAmount > 0) {
                    // If it actually is set it is either the first time or a override later in time
                    $this->ArrSet("$path.asset.acquisitionInitialAmount", $assetInitialAcquisitionAmount);
                }
                if ($assetInitialEquityAmount > 0) {
                    // If it actually is set it is either the first time or a override later in time
                    $this->ArrSet("$path.asset.equityInitialAmount", $assetInitialEquityAmount);
                }
                if ($assetInitialPaidAmount > 0) {
                    // If it actually is set it is either the first time or a override later in time
                    $this->ArrSet("$path.asset.paidInitialAmount", $assetInitialPaidAmount);
                }

                // Calculation of the changerate asset has to be done after paidAmount, equityAmount but before we calculate the Taxes.
                $transferedAmount = $this->ArrGet("$path.asset.transferedAmount");

                $assetMarketAmount = ($assetMarketInitialAmount + $transferedAmount) * $assetChangerateDecimal;
                $assetTaxableInitialAmount = round(($assetTaxableInitialAmount + $transferedAmount) * $assetChangerateDecimal); // FIX: Trouble with override special case destrous all marketAmounts after it is set the first time. Does not neccessarily be more taxable if you put more money into it. Special case with house/cabin/rental.

                // print "Asset3: $assetname.$year .assetMarketAmount:$assetMarketAmount, assetTaxableInitialAmount:$assetTaxableInitialAmount, assetAcquisitionAmount:$assetInitialAcquisitionAmount, assetEquityAmount:$assetInitialEquityAmount, assetPaidAmount: $assetInitialPaidAmount, termAmount: " . $this->ArrGet("$assetname.$year.mortgage.termAmount") . "\n";

                // print "PAID: $assetname.$year.asset.curPaid: " . $this->ArrGet("$assetname.$year.asset.paidAmount") . " + prevPaid: " . $this->ArrGet("$assetname.$prevYear.asset.paidAmount") . " - assetEquityAmount: $assetInitialEquityAmount\n";

                // #######################################################################################################
                // Asset tax calculations
                if ($assetname == 'xxx') {
                    // echo "TaxFortuneBefore $assetname.$year, taxType:$taxType, taxProperty:$taxProperty, assetMarketAmount:$assetMarketAmount, assetTaxableInitialAmount:$assetTaxableInitialAmount, balanceAmount:".$this->ArrGet("$assetname.$year.mortgage.balanceAmount")."\n";
                }
                // FIXXXX?????  $assetTaxableAmount = round($assetTaxableAmount * $assetChangerateDecimal); //We have to increase the taxable amount, but maybe it should follow another index than the asset market value. Anyway, this is quite good for now.
                [$assetTaxableAmount, $assetTaxableDecimal, $assetTaxFortuneAmount, $assetTaxFortuneDecimal, $assetTaxablePropertyAmount, $assetTaxablePropertyPercent, $assetTaxPropertyAmount, $assetTaxPropertyDecimal] = $this->taxfortune->taxCalculationFortune($taxGroup, $taxType, $taxProperty, $year, (int) $assetMarketAmount, (int) $assetTaxableInitialAmount, $this->ArrGet("$assetname.$year.mortgage.balanceAmount"), $assetTaxableAmountOverride);
                if ($assetname == 'xxx') {
                    // echo "   TaxFortuneAfter: $assetname.$year assetTaxableInitialAmount:$assetTaxableInitialAmount, assetTaxableAmount:$assetTaxableAmount, assetTaxAmount:$assetTaxFortuneAmount,assetTaxAmount:$assetTaxFortuneAmount\n";
                }

                // #######################################################################################################
                // Check if we have any transfers from the cashflow - have to do it as the last thing.
                // We have to calculate it before we can transfer from it. Could have been before asset in the sequence?
                $cashflowRule = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'cashflow', 'rule');
                $cashflowTransfer = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'cashflow', 'transfer');
                $cashflowSource = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'cashflow', 'source');
                $cashflowRepeat = $this->configOrPrevValueRespectRepeat($debug, $assetname, $year, 'cashflow', 'repeat');

                // ######################################################################################################
                $interestAmount = round($assetMarketAmount - $assetMarketInitialAmount); // ToDo - subtract the changerated amount from the initial amount to find the interest amount

                Log::debug('Tax calculation income input', [
                    'tax_group' => $taxGroup,
                    'tax_type' => $taxType,
                    'year' => $year,
                    'income_amount' => $incomeAmount,
                    'expence_amount' => $expenceAmount,
                    'interest_amount' => $interestAmount,
                ]);
                if (app()->runningInConsole()) {
                    echo "***********taxCalculationIncome taxGroup:$taxGroup, taxType:$taxType, year:$year, incomeAmount:$incomeAmount, expenceAmount:$expenceAmount, interestAmount:$interestAmount\n";
                }

                [$cashflowTaxAmount, $cashflowTaxPercent, $cashflowDescription] = $this->taxincome->taxCalculationIncome($debug, $taxGroup, $taxType, $year, $incomeAmount, $expenceAmount, $interestAmount);

                $cashflowBeforeTaxAmount =
                    $incomeAmount
                    + $this->ArrGet("$path.income.transferedAmount")
                    - $expenceAmount // cashflow basis = inntekt - utgift.
                    - $this->ArrGet("$path.mortgage.termAmount"); // Minus terminbetaling på lånet

                $cashflowAfterTaxAmount =
                    $incomeAmount
                    + $this->ArrGet("$path.mortgage.taxDeductableAmount") // Plus skattefradrag på renter
                    + $this->ArrGet("$path.income.transferedAmount")
                    - $expenceAmount // cashflow basis = inntekt - utgift.
                    - $cashflowTaxAmount // Minus skatt på cashflow (Kan være både positiv og negativ)
                    - $assetTaxFortuneAmount // Minus formuesskatt
                    - $assetTaxPropertyAmount // Minus eiendomsskatt
                    - $this->ArrGet("$path.mortgage.termAmount"); // Minus terminbetaling på lånet


                Log::info('Cashflow calculation result', [
                    'asset' => $assetname,
                    'year' => $year,
                    'income_amount' => $incomeAmount,
                    'expence_amount' => $expenceAmount,
                    'interest_amount' => $interestAmount,
                    'cashflow_tax_amount' => $cashflowTaxAmount,
                    'cashflow_before_tax_amount' => $cashflowBeforeTaxAmount,
                    'cashflow_after_tax_amount' => $cashflowAfterTaxAmount,
                    'transferred_amount' => $this->ArrGet("$path.income.transferedAmount"),
                ]);

                $cashflowNewRule = null;
                if ($cashflowTransfer && $cashflowRule && $cashflowBeforeTaxAmount > 0) {
                    // print "  Cashflow-start: $assetname.$year, transferOrigin: $path.cashflow.afterTaxAmount, cashflowTransfer:$cashflowTransfer, cashflowRule:$cashflowRule, cashflowAfterTaxAmount: $cashflowAfterTaxAmount \n";
                    [$cashflowAfterTaxAmount, $cashflowDiffAmount, $taxShieldAmountX, $cashflowNewRule, $cashflowExplanation] = $this->applyRule($debug, "$path.cashflow.afterTaxAmount", $cashflowAfterTaxAmount, 0, 0, $cashflowRule, $cashflowTransfer, $cashflowSource, 1);
                    $cashflowAfterTaxAmount = $cashflowAfterTaxAmount - $cashflowDiffAmount;
                    // print "  Cashflow-end  : $assetname.$year, cashflowDiffAmount: $cashflowDiffAmount, cashflowRule:$cashflowRule, cashflowAfterTaxAmount: $cashflowAfterTaxAmount \n";
                    // Amounts will probably be transfered to Assets here. So need to do a new calculation.
                }

                // #######################################################################################################
                // If we sell the asset, how much money is left for us after tax? In sequence has to be after cashflow.
                // print "Asset4: $assetname.$year .assetMarketAmount:$assetMarketAmount, assetTaxableAmount:$assetTaxableAmount, assetAcquisitionAmount:$assetInitialAcquisitionAmount, assetEquityAmount:$assetInitialEquityAmount, assetPaidAmount: $assetInitialPaidAmount termAmount: " . $this->ArrGet("$assetname.$year.mortgage.termAmount") . "\n";

                $transferedCashfLowAmount = $this->ArrGet("$path.cashflow.transferedAmount");
                $cashflowAfterTaxAmount += $transferedCashfLowAmount;
                $cashflowBeforeTaxAmount += $transferedCashfLowAmount;

                // print "   TaxRealization1: $assetname.$year .assetMarketAmount:$assetMarketAmount, assetTaxableAmount:$assetTaxableAmount, assetAcquisitionAmount:$assetInitialAcquisitionAmount, assetEquityAmount:$assetInitialEquityAmount, assetPaidAmount: $assetInitialPaidAmount, termAmount: " . $this->ArrGet("$assetname.$year.mortgage.termAmount") . "\n";

                [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $realizationTaxShieldAmountSimulation, $realizationTaxShieldDecimal] = $this->taxrealization->taxCalculationRealization($debug, false, $taxGroup, $taxType, $year, $assetMarketAmount, $assetInitialAcquisitionAmount, $assetDiffAmount, $realizationPrevTaxShieldAmount, $assetFirstYear);
                $realizationAmount = $assetMarketAmount - $realizationTaxAmount; // Markedspris minus skatt ved salg.

                // print "   TaxRealization2: $assetname.$year .assetMarketAmount:$assetMarketAmount, transferedAmount:$transferedAmount, transferedChangerateAmount:$transferedChangerateAmount, assetTaxableAmount:$assetTaxableAmount, assetAcquisitionAmount:$assetInitialAcquisitionAmount, assetEquityAmount:$assetInitialEquityAmount, assetPaidAmount: $assetInitialPaidAmount, assetTaxableAmount:$assetTaxableAmount, termAmount: " . $this->ArrGet("$assetname.$year.mortgage.termAmount") . "\n";

                if ($realizationTaxShieldAmount == $realizationPrevTaxShieldAmount) {
                    // If $realizationTaxShieldAmount is not changed (lowered), then we are in an accumulating situation.
                    // print "ACCUMULATING SHIELD: $realizationTaxShieldAmount == $realizationPrevTaxShieldAmount\n";
                    $realizationTaxShieldAmount = $realizationTaxShieldAmountSimulation;

                } else {
                    // print "REDUCING SHIELD: $realizationTaxShieldAmount\n";
                }

                // #######################################################################################################
                // Store all data in the dataH structure
                if ($incomeAmount > 0) {
                    $this->dataH[$assetname][$year]['income'] = [
                        'changerate' => $incomeChangerate,
                        'changeratePercent' => $incomeChangeratePercent,
                        'rule' => $incomeRule,
                        'transfer' => $incomeTransfer,
                        'source' => $incomeSource,
                        'repeat' => $incomeRepeat,
                        'amount' => $incomeAmount,
                        'description' => $this->ArrGetConfig("$path.income.description").$incomeExplanation.$this->ArrGet("$path.income.description"),
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
                        'description' => $this->ArrGetConfig("$path.expence.description").$expenceExplanation.$this->ArrGet("$path.expence.description"),
                    ];
                }

                // print_r($this->dataH[$assetname][$year]['income']);
                // Fix before and after tax cashflow calculations.

                if ($assetMarketAmount > 0) {
                    $this->ArrSet("$path.asset.marketAmount", $assetMarketAmount);

                    $this->ArrSet("$path.asset.taxableDecimal", $assetTaxableDecimal);
                    $this->ArrSet("$path.asset.taxableAmount", $assetTaxableAmount);
                    $this->ArrSet("$path.asset.taxableInitialAmount", $assetTaxableInitialAmount);
                    $this->ArrSet("$path.asset.taxableAmountOverride", $assetTaxableAmountOverride);

                    if ($assetTaxFortuneAmount > 0) {
                        $this->ArrSet("$path.asset.taxFortuneDecimal", $assetTaxFortuneDecimal);
                        $this->ArrSet("$path.asset.taxFortuneAmount", $assetTaxFortuneAmount);
                    }

                    if ($assetTaxablePropertyAmount > 0) {
                        $this->ArrSet("$path.asset.taxablePropertyDecimal", $assetTaxablePropertyPercent);
                        $this->ArrSet("$path.asset.taxablePropertyAmount", $assetTaxablePropertyAmount);
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
                    $this->ArrSet("$path.asset.description", $this->ArrGetConfig("$path.asset.description").$this->ArrGet("$path.asset.description").' Asset rule:'.$assetRule.' '.$assetExplanation1.$assetExplanation2);
                    // print_r($this->dataH[$assetname][$year]['realization']);
                }

                // Try to no process the same here as in the post processing step
                $this->ArrSet("$path.cashflow.beforeTaxAmount", $cashflowBeforeTaxAmount);
                $this->ArrSet("$path.cashflow.afterTaxAmount", $cashflowAfterTaxAmount);
                $this->ArrSet("$path.cashflow.taxAmount", $this->ArrGet("$path.cashflow.taxAmount") + $cashflowTaxAmount);
                $this->ArrSet("$path.cashflow.taxDecimal", $cashflowTaxPercent);
                $this->ArrSet("$path.cashflow.rule", $cashflowNewRule);
                $this->ArrSet("$path.cashflow.transfer", $cashflowTransfer);
                $this->ArrSet("$path.cashflow.source", $cashflowSource);
                $this->ArrSet("$path.cashflow.repeat", $cashflowRepeat);
                $this->ArrSet("$path.cashflow.description", $cashflowDescription.$this->ArrGetConfig("$path.cashflow.description").$this->ArrGet("$path.cashflow.description"));

            } // Year loop finished here.

        } // End loop over assets

        // Delegate post-processing and grouping to PostProcessorService
        $this->postProcessor->process(
            $this->dataH,
            $this->totalH,
            $this->companyH,
            $this->privateH,
            $this->groupH,
            $this->statisticsH,
            $this->economyStartYear,
            $this->deathYear,
            $this->thisYear,
            fn ($type) => $this->isShownInStatistics($type)
        );
        // print_r($this->dataH);
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
     *
     * @return array{0: float, 1: float, 2: float, 3: string, 4: string}
     */
    public function applyRule(bool $debug, string $transferOrigin, float $amount, float $acquisitionAmount, float $taxShieldAmount, ?string $rule, ?string $transferTo, ?string $source, int $factor = 1): array
    {
        // Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor should have memory.

        [$originAssetname, $originYear, $originType, $originField] = $this->helper->pathToElements($transferOrigin);
        $transferedOriginAmount = "$originAssetname.$originYear.$originType.transferedAmount";
        $transferedOriginDescription = "$originAssetname.$originYear.$originType.description";

        // print "  transferOrigin: $originAssetname.$originYear.$originType.acquisitionAmount: $acquisitionAmount\n";

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

        // This is really just a fixed number, but it can appear at the same time as a rule.
        if ($amount != 0) {
            // $explanation = 'Using current amount: '.round($amount)." * $factor ";
            $amount = $calculatedNumericAmount = round($amount * $factor);
            // This is not a deposit
        }

        if ($debug) {
            Log::debug('Apply rule input', [
                'origin_year' => $originYear,
                'amount' => $amount,
                'acquisition_amount' => $acquisitionAmount,
                'tax_shield_amount' => $taxShieldAmount,
                'rule' => $rule,
                'transfer_to' => $transferTo,
                'source' => $source,
                'factor' => $factor,
            ]);
            if (app()->runningInConsole()) {
                echo "    applyRule INPUT($originYear, amount: $amount, acquisitionAmount: $acquisitionAmount, taxShieldAmount: $taxShieldAmount, transfer $rule of $transferTo, source: $source factor: $factor)\n";
            }
        }

        // ##############################################################################################################
        // Transfer value to another asset, has to update the datastructure of this asset directly
        if ($transferTo) {

            // $debug = true;
            // echo "    @@@@ transferTo set\n";
            if ($rule) {
                [$newAmount, $transferAmount, $rule, $explanation] = $this->rules->calculateRule(false, $amount, $acquisitionAmount, $rule, $factor);
                // echo "    **** rule: $rule, transferAmount: $transferAmount --------------------------\n\n\n";

                if ($transferAmount > 0) {
                    // echo "    #### transferAmount > 0\n";
                    [$XpaidAmount, $notTransferedAmount, $taxShieldAmount, $Xexplanation] = $this->transfer($debug, $transferOrigin, $transferTo, $transferAmount, $acquisitionAmount, $taxShieldAmount, $explanation);
                    $diffAmount = $transferAmount - $notTransferedAmount;
                    // $newAmount -= $diffAmount; //THe transfer will also be added later in the prosess, but since a transfer can come from multiple assets we do not know the difference between addition here and later.
                }
            }
        } elseif ($source && $rule) {
            // If we are not transfering the values to another resoruce, then we are adding it to the current resource
            // Do not run calculateRule here since it changes the rule, and are run in the sub procedure
            // ###########################################################################################################

            [$diffAmount, $explanation] = $this->source($debug, $source, $rule);
            $newAmount = $amount + $diffAmount;
            $this->ArrSet($transferedOriginAmount, Arr::get($this->dataH, $transferedOriginAmount, 0) + $diffAmount); // The amount we transfered to - for later reference and calculation

        } elseif ($rule) {

            // A rule without a transfer adds money to an asset without removing it from another asset. It is treated as a deposit.
            if ($debug) {
                Log::debug('Applying normal rule (deposit)');
                if (app()->runningInConsole()) {
                    echo "  Normal rule\n";
                }
            }
            [$newAmount, $diffAmount, $rule, $explanation] = $this->rules->calculateRule(false, $amount, $acquisitionAmount, $rule, $factor);
            $this->ArrSet($transferedOriginAmount, Arr::get($this->dataH, $transferedOriginAmount, 0) + $diffAmount); // The amount we transfered to - for later reference and calculation
            $this->ArrSet($transferedOriginDescription, Arr::get($this->dataH, $transferedOriginDescription, 0)." added $diffAmount from rule $rule"); // The amount we transfered to - for later reference and calculation
            $newAmount = $amount; // Since we started putting the transfer in the data structure, we can not add it here, because it is then added twice.

        } else {
            // No changes here
            $newAmount = $amount;
            $diffAmount = 0;
            $rule = '';
        }

        if ($debug) {
            Log::debug('Apply rule output', [
                'origin_year' => $originYear,
                'new_amount' => $newAmount,
                'diff_amount' => $diffAmount,
                'tax_shield_amount' => $taxShieldAmount,
                'rule' => $rule,
                'explanation' => $explanation,
            ]);
            if (app()->runningInConsole()) {
                echo "    applyRule OUTPUT($originYear, newAmount: $newAmount, diffAmount: $diffAmount, taxShieldAmount: $taxShieldAmount, rule: $rule, explanation: $explanation)\n";
            }
        }

        // print "return amountAdjustment($newAmount, $rule, $explanation)\n";
        return [$newAmount, $diffAmount, $taxShieldAmount, $rule, $explanation]; // Rule is adjusted if it is a divisor, it has to be remembered to the next round
    }

    protected function isShownInStatistics(string $assetType): bool
    {
        return (bool) ($this->assetTypeShowStatisticsMap[$assetType] ?? false);
    }

    // Transferes the amount to another asset. This actualle has to change variables like assetEquityAmount, assetPaidAmount, realizationShieldAmount etc. Others are only simulations, not happening.
    /**
     * @return array{0: float, 1: float, 2: float, 3: string}
     */
    public function transfer(bool $debug, string $transferOrigin, string $transferTo, float $amount, float $acquisitionAmount, float $taxShieldAmount, string $explanation): array
    {

        $realizationTaxableAmount = 0;
        $realizationTaxAmount = 0;
        $realizationTaxPercent = 0;
        $notTransferedAmount = 0;

        $transferedFromAmount = $amount; // The amount we transfer from is the original amount, wich is equalt to the $transferedToAmount + Taxes.

        [$originAssetname, $originYear, $originType, $originField] = $this->helper->pathToElements($transferOrigin);

        $paidAmount = 0;
        $explanation = " transfer $amount ($explanation) to $transferTo ";
        if ($debug) {
            Log::debug('Transfer before', [
                'transfer_to' => $transferTo,
                'explanation' => $explanation,
                'current_value' => Arr::get($this->dataH, $transferTo, 0),
            ]);
            if (app()->runningInConsole()) {
                echo "        Transferto before: $transferTo ($explanation): ".Arr::get($this->dataH, $transferTo, 0)."\n";
            }
        }

        [$toAssetname, $toYear, $toType, $toField] = $this->helper->pathToElements($transferTo);
        $transferedToPathAmount = "$toAssetname.$toYear.$toType.transferedAmount";
        $transferedToPathTaxAmount = "$toAssetname.$toYear.cashflow.taxAmount";
        $transferedToPathDescription = "$toAssetname.$toYear.$toType.description";
        $transferedOriginPathAmount = "$originAssetname.$originYear.$originType.transferedAmount";
        $transferedOriginPathDescription = "$originAssetname.$originYear.$originType.description";

        // Realisation tax calculations here, because we have to realize a transfered asset.
        // Derive origin tax type via asset_type instead of meta.tax
        [$taxAssetname, $taxYear, $taxOriginGroup] = $this->getAssetMetaFromPath($transferOrigin, 'group');
        [$originAssetnameMeta, $originYearMeta, $originAssetType] = $this->getAssetMetaFromPath($transferOrigin, 'type');
        $taxOriginType = $this->assetTypeService->getTaxType($originAssetType);

        [$taxToAssetname, $taxToYear, $taxToGroup] = $this->getAssetMetaFromPath($transferTo, 'group');

        // print "    Tax asset: $taxAssetname, year: $taxYear, type: $taxType\n";

        if ($originType == 'asset') {
            // It is only calculated tax when realizing assets, not when transfering to an asset (buying)
            [$realizationTaxableAmount, $realizationTaxAmount, $acquisitionAmount, $realizationTaxPercent, $taxShieldAmount, $realizationTaxShieldPercent] = $this->taxrealization->taxCalculationRealization($debug, true, $taxOriginGroup, $taxOriginType, $originYear, $amount, $acquisitionAmount, $amount, $taxShieldAmount, $originYear);

            // print "@@@@ Asset transfer - taxOriginGroup:$taxOriginGroup, taxToGroup:$taxToGroup\n";

            if ($taxOriginGroup == 'company' && $taxToGroup == 'private') {
                // If a transfer is from a company to a private group, then the normal realization tax has to be paid and the tax of the dividend has to be added
                // How much is left after realization tax, no tax shield on company,FIX: But tax shield on private......
                $amount = $amount - $realizationTaxAmount;

                // Calculate the tax of the divident/utbytte to private
                $dividendTaxPercent = 0.378; // FIX: Should be a variable, not a fixed number
                $dividendTaxAmount = $amount * $dividendTaxPercent;

                // print "TRANSFER FROM COMPANY TO PRIVATE: amount: $amount, realizationTaxAmount: $realizationTaxAmount ($realizationTaxPercent), dividendTaxAmount: $dividendTaxAmount ($dividendTaxPercent)\n\n\n";
                $explanation .= " from company to private with dividend tax by $dividendTaxAmount ($dividendTaxPercent) \n";

                $realizationTaxAmount += $dividendTaxAmount; // We add the taxes together

            }

        } else {
            // It is probably income, expence or cashflow transfered to an asset. No tax calculations needed.
        }

        // print "    Realization amount: $amount, acquisitionAmount: $acquisitionAmount, realizationTaxableAmount: $realizationTaxableAmount, realizationTaxAmount: $realizationTaxAmount, realizationTaxPercent: $realizationTaxPercent\n";

        $transferedToAmount = $amount;

        if (Str::contains($transferTo, ['mortgage.extraDownpaymentAmount']) && $transferedToAmount > 0) {
            // We see it is an extra $extraDownpaymentAmount for the mortgage, then we recalculate it.
            // Will also handle if we try to transfer to a non existing mortgage, not transfering anything.
            [$notTransferedAmount, $mortgageExplanation] = $this->mortgageExtraDownPayment($toAssetname, $toYear, $transferedToAmount);
            $transferedToAmount = $transferedToAmount - $notTransferedAmount;
            if ($transferedToAmount > 0) {
                $this->ArrSet($transferedToPathDescription, $this->ArrGet($transferedToPathDescription)."extraDownpaymentAmount $transferedToAmount from $transferOrigin "); // The amount we transfered including the tax - for later reference and calculation
            }
            if ($notTransferedAmount > 0) {
                $this->ArrSet($transferedOriginPathAmount, $notTransferedAmount); // The amount we transfered including the tax - for later reference and calculation
            }

        } else {

            // The transfer happens here.
            $this->ArrSet($transferTo, $this->ArrGet($transferTo) + $transferedToAmount); // Changes asset value. The real transfer from this asset to another takes place here, it is added to the already existing amount on the other asset
            $this->ArrSet($transferedToPathTaxAmount, $this->ArrGet($transferedToPathTaxAmount) + $realizationTaxAmount);
            $this->ArrSet($transferedToPathDescription, $this->ArrGet($transferedToPathDescription)."transfered $amount with $realizationTaxAmount (tax) from $transferOrigin $explanation");
            $this->ArrSet($transferedOriginPathDescription, $this->ArrGet($transferedOriginPathDescription)."transfered $amount with $realizationTaxAmount (tax) to $transferTo $explanation");
            // echo "#### Transfer from: $transferedOriginPathDescription :" . $this->ArrGet($transferedOriginPathDescription) . "\n";
        }
        if ($transferedToAmount > 0) {
            // Could happen if downpayment of mortgage is finished.
            $this->ArrSet($transferedToPathAmount, $this->ArrGet($transferedToPathAmount) + $transferedToAmount); // The amount we transfered to - for later reference and calculation
            $this->ArrSet($transferedOriginPathAmount, $this->ArrGet($transferedOriginPathAmount) - $transferedFromAmount); // The amount we transfered including the tax - for later reference and calculation
            // echo "#### Transfer from: $transferedOriginPathAmount:" . $this->ArrGet($transferedOriginPathAmount) . "\n";
        }
        // FIX: Should add explanation also on the asset transfered to for easier debug.
        $paidAmount -= $amount;
        if ($paidAmount < 0) {
            $paidAmount = 0; // Deposited amount can not go negative.
        }

        if ($debug) {
            Log::debug('Transfer after', [
                'transfer_to' => $transferTo,
                'new_value' => Arr::get($this->dataH, $transferTo, 0),
            ]);
            if (app()->runningInConsole()) {
                echo "        Transferto after: $transferTo: ".Arr::get($this->dataH, $transferTo, 0)."\n";
            }
        }

        // ###########################################################################################################
        // reduce value from this assetAmount
        $explanation .= " reduce by $amount \n";

        return [$paidAmount, $notTransferedAmount, $taxShieldAmount, $explanation];
    }

    /**
     * @return array{0: float|int, 1: string|null}
     */
    public function mortgageExtraDownPayment(string $assetname, int $year, float|int $extraDownPaymentAmount): array
    {

        $description = null;
        $notUsedExtraAmount = 0;
        $mortgage = [];
        // We have to recalculate it from the next year, we can not change the run of this year without big problems....
        $year++;
        // We see it is an extra $extraDownpaymentAmount for the mortgage, then we recalculate it.
        // Mortage - has to be calculated before asset, since we use data from mortgage to calculate asset values correctly.
        // How can we ensure we are transfering to a valid mortgage, it could have been finished already.

        Log::info('Mortgage extra down payment', [
            'asset' => $assetname,
            'year' => $year,
            'extra_down_payment_amount' => $extraDownPaymentAmount,
        ]);
        if (app()->runningInConsole()) {
            echo "@@@@ $assetname.mortgageExtraDownPayment:$year extraDownPaymentAmount:$extraDownPaymentAmount\n";
        }

        $mortgageBalanceAmount = $this->ArrGet("$assetname.$year.mortgage.balanceAmount");
        $mortgage['amount'] = $mortgageBalanceAmount - $extraDownPaymentAmount; // Vi reberegner lånet minus ekstra innbetaliungen - basert på gjenværende lånebeløp dette året.
        if ($mortgage['amount'] > 0) {

            // This will only happen if we already have processed the mortgage of the asset in the sequenze

            Log::info('Recalculating mortgage with extra payment', [
                'original_balance' => $mortgageBalanceAmount,
                'year' => $year,
                'extra_payment' => $extraDownPaymentAmount,
                'new_amount' => $mortgage['amount'],
            ]);
            if (app()->runningInConsole()) {
                echo "*** Reberegner opprinnelig lån $mortgageBalanceAmount med ekstra innbetaling $year: $extraDownPaymentAmount = ny lånesum: ".$mortgage['amount']."\n";
            }

            // The mortgage has a remaining balance after extra payment, we recalculate on this amount.
            $mortgage['years'] = $this->ArrGet("$assetname.$year.mortgage.years"); // Vi reberegner slik at lånet er ferdig på samme år som det opprinnelige lånet
            $mortgage['interest'] = $this->ArrGet("$assetname.$year.mortgage.interest"); // Vi reberegner med den opprinnelige rentebanen
            $mortgage['extraDownpaymentAmount'] = $this->ArrGet("$assetname.$year.mortgage.extraDownpaymentAmount");
            $mortgage['interestOnlyYears'] = $this->ArrGet("$assetname.$year.mortgage.interestOnlyYears"); // Vi reberegner med gjenværende avdragsfritt lån
            $mortgage['gebyrAmount'] = $this->ArrGet("$assetname.$year.mortgage.gebyrAmount"); // Vi reberegner med samme gebyr som opprinnelig (FIX: ikke støttet uansett)

            $this->removeMortgageFrom($assetname, $year); // Clean up all mortage from dataH even from this year before recalculating it back into the array.
            // Recalculate the mortgage from this year an onwards.
            print_r($mortgage);
            $this->dataH = (new \App\Services\Prognosis\AmortizationService($this->debug, $this->config, $this->changerate, $this->dataH, $mortgage, $assetname, $year))->get();

        } else {

            // This can  happen if we have not processed the mortgage of the asset in the sequenze, it is coming later. We really need to know the difference to get this right

            // If we after the extraDownpayment have money left, the remaining mortgage has to be removed.
            $this->removeMortgageFrom($assetname, $year); // Clean up all mortage from dataH even from this year before recalculating it back into the array.

            // FIX: Do we have to reset some variables here since we were not able to use the money..... Should be checked before we started the transfer......
            // The mortgage have been payd in full, it may be some $extraDownPaymentAmount left to return and not transfer. We only transfer what we need to pay the mortgage
            // This will happen for all transfers for the length of the asset from the first extra down payment has happened when transfering extra money.
            $notUsedExtraAmount = abs($mortgage['amount']); // The remaining amount after the mortgage has been payed.
            $mortgageBalanceAmount = 0; // Loan is emptied
            $this->ArrSet("$assetname.$year.mortgage.extraDownpaymentAmount", $notUsedExtraAmount); // FIX::The extra payment. #FIX if this is not used or there is a leftower amount........

            // FIX: The remaining extrapayment not neccessary for the mortgage downpayment has to get back into the asset and not deducted..........

            Log::info('Unused extra payment going back to cashflow', [
                'not_used_extra_amount' => $notUsedExtraAmount,
            ]);
            if (app()->runningInConsole()) {
                echo "    notUsedExtraAmount: $notUsedExtraAmount - going back into cashflow\n";
            }
        }

        return [$notUsedExtraAmount, $description];
    }

    // FIX: This method is also in the Mortgage class
    public function removeMortgageFrom(string $assetname, int $fromYear): void
    {
        $toYear = $fromYear + 80;
        // print "    removeMortgageFrom($this->$assetname, $fromYear)\n";

        for ($year = $fromYear; $year <= $toYear; $year++) {
            // print "    Removing mortgage from dataH[$year]\n";
            unset($this->dataH[$assetname][$year]['mortgage']);
        }
    }

    /**
     * This function retrieves a value from the configuration or from the previous year's data.
     * It checks if the value is an amount and if so, it adds any transferred amount to this year to the previous year's amount.
     *
     * @param  bool  $debug  Indicates whether debugging is enabled.
     * @param  string  $assetname  The name of the asset.
     * @param  int  $year  The year for which the value is being retrieved.
     * @param  string  $type  The type of the value being retrieved (e.g., 'income', 'expense', etc.).
     * @param  string  $variable  The specific variable within the type being retrieved.
     * @return mixed The retrieved value.
     */
    public function configOrPrevValueRespectRepeat(bool $debug, string $assetname, int $year, string $type, string $variable)
    {
        $prevYear = $year - 1;
        $value = $this->ArrGetConfig("$assetname.$year.$type.$variable");

        $repeat = $this->ArrGetConfig("$assetname.$prevYear.$type.repeat"); // We repeat the current year if repeat was the previous years value, but not the next
        if (! isset($repeat)) { // Check if repeat is set in the config
            $repeat = $this->ArrGet("$assetname.$prevYear.$type.repeat"); // Check if we stopped repeating the previous year.
        }
        if ($debug) {
            Log::debug('Config or prev value - from config', [
                'asset' => $assetname,
                'year' => $year,
                'type' => $type,
                'variable' => $variable,
                'value' => $value,
            ]);
        }

        // Trouble with bool handling here, and with amounts that are 0.0 (since amounts is set default to 0 so calculations shall work.
        // Isset is false if value is null, but it is true if value is 0 - thats why we need to check it it is numeric, and then check if it is 0.- then we try to get data from the dataH
        // FIX: Problem with amount reset to zero, if repeat=true. Because we do not know the difference if it is not set or if it is really set o 0, since we default to zero, but need it always returning integer/float for calculations
        if ((! isset($value) && $repeat) || (is_numeric($value) && $value == 0 && $repeat)) {
            $value = $this->ArrGet("$assetname.$prevYear.$type.$variable"); // Retrive value from dataH previous year only if repeat is true
            if ($debug) {
                Log::debug('Config or prev value - from data (prev year)', [
                    'asset' => $assetname,
                    'year' => $year,
                    'type' => $type,
                    'variable' => $variable,
                    'value' => $value,
                ]);
                if (app()->runningInConsole()) {
                    // echo "      configOrPrevValueData prev year: $assetname.$year.$type.$variable: $value\n";
                }
            }
        }

        if (Str::contains("$assetname.$year.$type.$variable", ['Amount', 'amount'])) {
            // If it is an amount, we check if we have a transferred amount to this year, and add it to the previous years amount
            // $value += $this->ArrGet("$assetname.$year.$type.$variable");
        }

        if ($debug) {
            Log::debug('Config or prev value - return', [
                'asset' => $assetname,
                'year' => $year,
                'type' => $type,
                'variable' => $variable,
                'value' => $value,
            ]);
            if (app()->runningInConsole()) {
                // echo "      configOrPrevValueReturn: $assetname.$year.$type.$variable: $value\n";
            }
        }

        return $value;
    }

    // We ignore the no repeat for these values
    public function configOrPrevValue(bool $debug, string $assetname, int $year, string $type, string $variable): mixed
    {
        $prevYear = $year - 1;
        $value = $this->ArrGetConfig("$assetname.$year.$type.$variable");

        if (! isset($value) || (is_numeric($value) && $value == 0)) {
            $value = $this->ArrGet("$assetname.$prevYear.$type.$variable"); // Retrive value from dataH previous year only if repeat is true
            if ($debug) {
                Log::debug('Config or prev value repeat - from data (prev year)', [
                    'asset' => $assetname,
                    'year' => $year,
                    'type' => $type,
                    'variable' => $variable,
                    'value' => $value,
                ]);
                if (app()->runningInConsole()) {
                    // echo "      configOrPrevValueRepeatData prev year: $assetname.$year.$type.$variable: $value\n";
                }
            }
        }

        if (Str::contains("$assetname.$year.$type.$variable", ['Amount', 'amount'])) {
            // If it is an amount, we check if we have a transferred amount to this year, and add it to the previous years amount
            // $value += $this->ArrGet("$assetname.$year.$type.$variable");
        }

        if ($debug) {
            Log::debug('Config or prev value repeat - return', [
                'asset' => $assetname,
                'year' => $year,
                'type' => $type,
                'variable' => $variable,
                'value' => $value,
            ]);
            if (app()->runningInConsole()) {
                // echo "      configOrPrevValueRepeat: $assetname.$year.$type.$variable: $value\n";
            }
        }

        return $value;
    }

    // Do all calculations that should be done as the last thing, and requires that all other calculations is already done.
    // Special Arr get that onlye gets data from dataH to make cleaner code.
    public function ArrGet(string $path): mixed
    {
        $default = null;
        if (Str::contains($path, ['Amount', 'Decimal', 'Percent', 'amount', 'decimal', 'percent', 'factor'])) {
            $default = 0;
        }

        return Arr::get($this->dataH, $path, $default);
    }

    public function ArrSet(string $path, mixed $value): void
    {
        Arr::set($this->dataH, $path, $value);
    }

    // Special Arr get that onlye gets data from configH to make cleaner code.
    public function ArrGetConfig(string $path): mixed
    {
        $default = null;
        if (Str::contains($path, ['Amount', 'Decimal', 'Percent', 'amount', 'decimal', 'percent', 'factor'])) {
            $default = 0;
        }

        return Arr::get($this->config, $path, $default);

    }

    /**
     * @return array{0: string|null, 1: string|null, 2: mixed}
     */
    public function getAssetMetaFromPath(string $path, string $field): array
    {
        $value = null;
        $year = null;
        $assetname = null;

        if (preg_match('/(\w+).(\d+)/i', $path, $matchesH, PREG_OFFSET_CAPTURE)) {
            // print "$path\n";
            // print_r($matchesH);
            $year = $matchesH[2][0];
            $assetname = $matchesH[1][0];
            $value = $this->ArrGetConfig("$assetname.meta.$field");
            // print_r($this->ArrGetConfig("$assetname.meta"));
        } else {
            Log::error('Invalid path format', ['path' => $path]);
            if (app()->runningInConsole()) {
                echo "ERROR with path: $path\n";
            }
        }

        return [$assetname, $year, $value];
    }

    // Calculates an amount based on the value of another asset
    public function source(bool $debug, string $path, string $rule): float
    {
        $paidAmount = 0;
        $amount = $this->ArrGet($path); // Retrive the amount from another asset. Do not change the other asset.

        [$newAmount, $diffAmount, $rule, $explanation] = $this->rules->calculateRule($debug, $amount, 0, $rule, 1);
        $explanation = " source $rule of $path $amount = $diffAmount\n";

        if ($debug) {
            Log::debug('Source calculation', [
                'path' => $path,
                'amount' => $amount,
                'explanation' => $explanation,
            ]);
            if (app()->runningInConsole()) {
                echo "  Source: path: $path=$amount, $explanation\n";
            }
        }

        return [$diffAmount, $explanation];
    }
}
