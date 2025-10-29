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

namespace App\Services\Processing;

use App\Services\AssetTypeService;
use App\Services\Tax\TaxFortuneService;
use App\Services\Utilities\HelperService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * YearlyProcessor
 *
 * Handles yearly post-processing calculations for individual assets.
 * Processes fortune tax, cashflow, assets, potential, yield, and FIRE metrics.
 */
class YearlyProcessor
{
    public function __construct(
        private TaxFortuneService $taxfortune,
        private HelperService $helper,
        private AssetTypeService $assetTypeService
    ) {}

    /**
     * Process fortune tax for a specific year and asset path.
     * Has to be done because a mortgage could potentially have extra downpayments making the fortune calculation wrong.
     * Has to be run before cashflow calculations to be correct.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     * @param  int  $thisYear  Current year
     */
    public function processFortuneTaxYearly(array &$dataH, string $path, int $thisYear): void
    {
        [$assetname, $year, $taxGroup] = $this->getAssetMetaFromPath($dataH, $path, 'group');
        [$assetname, $year, $assetType] = $this->getAssetMetaFromPath($dataH, $path, 'type');

        if ($year >= $thisYear) { // For efficiensy, not neccessarry to calculate previous tax
            $taxType = $this->assetTypeService->getTaxType($assetType);

            $taxProperty = $this->ArrGet($dataH, "$path.meta.property"); // FIX - Check TaxProperty.

            $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
            $taxableAmount = $this->ArrGet($dataH, "$path.asset.taxableAmount");
            $mortgageBalanceAmount = $this->ArrGet($dataH, "$path.mortgage.balanceAmount");

            [$taxAmount, $taxPercent, $taxableFortuneAmount, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $taxFortuneAmount, $explanation] = $this->taxfortune->taxCalculationFortune($taxGroup, $taxType, $taxProperty, $year, $marketAmount, $taxableAmount, $mortgageBalanceAmount, false);

            $this->ArrSet($dataH, "$path.asset.taxableFortuneAmount", $taxableFortuneAmount);
            $this->ArrSet($dataH, "$path.asset.taxablePropertyAmount", $taxablePropertyAmount);
            $this->ArrSet($dataH, "$path.asset.taxFortuneAmount", $taxFortuneAmount);
            $this->ArrSet($dataH, "$path.asset.taxPropertyAmount", $taxPropertyAmount);
        }
    }

    /**
     * Process cashflow for a specific year and asset path.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     * @param  int  $thisYear  Current year
     */
    public function processCashFlowYearly(array &$dataH, string $path, int $thisYear): void
    {
        [$assetname, $year, $type, $field] = $this->helper->pathToElements("$path.cashflow.beforeTaxAmount");
        $prevYear = (int) $year - 1;

        // Recalculating cashflow. Necessary if a mortgage is paid with extra amount.
        $cashflowBeforeTaxAmount =
            $this->ArrGet($dataH, "$path.income.amount")
            + $this->ArrGet($dataH, "$path.income.transferedAmount")
            - $this->ArrGet($dataH, "$path.expence.amount")
            - $this->ArrGet($dataH, "$path.expence.transferedAmount")
            - $this->ArrGet($dataH, "$path.mortgage.termAmount")
            - $this->ArrGet($dataH, "$path.mortgage.extraDownpaymentAmount")
            - $this->ArrGet($dataH, "$path.mortgage.gebyrAmount");

        $cashflowAfterTaxAmount =
            $cashflowBeforeTaxAmount
            + $this->ArrGet($dataH, "$path.mortgage.taxDeductableAmount")
            - $this->ArrGet($dataH, "$path.cashflow.taxAmount")
            - $this->ArrGet($dataH, "$path.asset.taxFortuneAmount")
            - $this->ArrGet($dataH, "$path.asset.taxPropertyAmount");

        $this->ArrSet($dataH, "$path.cashflow.beforeTaxAmount", $cashflowBeforeTaxAmount);
        $this->ArrSet($dataH, "$path.cashflow.afterTaxAmount", $cashflowAfterTaxAmount);

        if ($year >= $thisYear) {
            $this->ArrSet($dataH, "$path.cashflow.beforeTaxAggregatedAmount", $cashflowBeforeTaxAmount + $this->ArrGet($dataH, "$assetname.$prevYear.cashflow.beforeTaxAggregatedAmount"));
            $this->ArrSet($dataH, "$path.cashflow.afterTaxAggregatedAmount", $cashflowAfterTaxAmount + $this->ArrGet($dataH, "$assetname.$prevYear.cashflow.afterTaxAggregatedAmount"));
        }
    }

    /**
     * Post-processes asset yearly data.
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    public function processAssetYearly(array &$dataH, string $path): void
    {
        [$assetname, $year, $type, $field] = $this->helper->pathToElements("$path.cashflow.beforeTaxAmount");

        $marketAmount = $this->ArrGet($dataH, "$assetname.$year.asset.marketAmount");
        if ($marketAmount <= 0) {
            // If the market value is gone, we zero out everything.
            $this->ArrSet($dataH, "$path.asset.acquisitionAmount", 0);
            $this->ArrSet($dataH, "$path.asset.equityAmount", 0);
            $this->ArrSet($dataH, "$path.asset.paidAmount", 0);
            $this->ArrSet($dataH, "$path.asset.taxableAmount", 0);
            $this->ArrSet($dataH, "$path.asset.marketMortgageDeductedAmount", 0);
        } else {
            // Recalculate equity
            $mortgageBalanceAmount = $this->ArrGet($dataH, "$path.mortgage.balanceAmount");
            $equityAmount = $marketAmount - $mortgageBalanceAmount;
            $this->ArrSet($dataH, "$path.asset.equityAmount", $equityAmount);
            $this->ArrSet($dataH, "$path.asset.marketMortgageDeductedAmount", $equityAmount);
        }
    }

    /**
     * Performs post-processing for the income potential as seen from a Bank.
     * This function calculates the potential maximum loan a user can handle based on their income.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  The path to the asset in the data structure. The path is in the format 'assetname.year'.
     */
    public function processPotentialYearly(array &$dataH, string $path): void
    {
        // Retrieve the year and tax type from the asset metadata.
        [$assetname, $year, $assetType] = $this->getAssetMetaFromPath($dataH, $path, 'type');
        $taxType = $this->assetTypeService->getTaxType($assetType);

        // If the tax type is 'salary' or 'pension', calculate the potential income and mortgage amounts.
        if ($taxType == 'salary' || $taxType == 'pension') {
            $incomeAmount = $this->ArrGet($dataH, "$path.income.amount");
            $mortgageAmount = $incomeAmount * 5; // 5x rule for mortgage in norwegian banks.

            $this->ArrSet($dataH, "$path.potential.incomeAmount", $incomeAmount);
            $this->ArrSet($dataH, "$path.potential.mortgageAmount", $mortgageAmount);
        }
    }

    /**
     * Calculate yield percentages for a specific year and asset path.
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    public function processYieldYearly(array &$dataH, string $path): void
    {
        $bruttoPercent = 0;
        $nettoPercent = 0;

        if ($this->ArrGet($dataH, "$path.asset.acquisitionAmount") > 1) {
            // Calculate the brutto yield percentage
            $bruttoPercent = round(($this->ArrGet($dataH, "$path.income.amount") / $this->ArrGet($dataH, "$path.asset.acquisitionAmount")) * 100, 1);

            // Calculate the netto yield percentage
            $nettoPercent = round((($this->ArrGet($dataH, "$path.income.amount") - $this->ArrGet($dataH, "$path.expence.amount")) / $this->ArrGet($dataH, "$path.asset.acquisitionAmount")) * 100, 1);
        }

        $this->ArrSet($dataH, "$path.yield.bruttoPercent", $bruttoPercent);
        $this->ArrSet($dataH, "$path.yield.nettoPercent", $nettoPercent);
    }

    /**
     * Process FIRE metrics for a specific year and asset.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $assetname  Asset name
     * @param  int  $year  Year to process
     * @param  array<string, mixed>  $meta  Asset metadata
     */
    public function processFireYearly(array &$dataH, string $assetname, int $year, array $meta): void
    {
        $fireIncomeAmount = 0;
        $fireExpenceAmount = 0;
        $fireSavingAmount = 0;
        $fireRate = 0;
        $firePercent = 0;
        $fireSavingRate = 0;
        $path = "$assetname.$year";

        $assetMarketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
        $fireExpenceAmount = $this->ArrGet($dataH, "$path.expence.amount");
        $cashFlowAmount = $this->ArrGet($dataH, "$path.cashflow.afterTaxAmount");

        // Calculate FIRE income from sellable assets
        $assetType = Arr::get($meta, 'type');
        if ($this->assetTypeService->isLiquid($assetType)) {
            $fireIncomeAmount = round($assetMarketAmount * 0.04); // 4% rule mulig inntekt på likvide assets
        }

        if ($this->assetTypeService->isSavingType($assetType)) {
            $fireSavingAmount = $cashFlowAmount; // Beløpet for assets som teller som FIRE sparing.
        }

        $fireCashFlowAmount = $fireIncomeAmount - $fireExpenceAmount;

        if ($fireIncomeAmount > 0) {
            // Denne regner ikke med avdrag (ikke renter). Må sjekke matematikken bedre her. Må se hva som hensyntas i cashflow som den kommer fra.
            $fireSavingRate = $fireSavingAmount / $fireIncomeAmount;
        }

        if ($fireExpenceAmount > 0) {
            $fireRate = round(($fireIncomeAmount / $fireExpenceAmount));
            $firePercent = $fireRate * 100;
        }

        $dataH[$assetname][$year]['fire'] = [

            'incomeAmount' => $fireIncomeAmount,
            'expenceAmount' => $fireExpenceAmount,
            'cashFlowAmount' => $fireCashFlowAmount,
            'savingAmount' => $fireSavingAmount,
            'rate' => $fireRate,
            'percent' => $firePercent,
            'savingRate' => $fireSavingRate,
        ];
    }

    /**
     * Helper method to get asset metadata from path.
     *
     * @param  array<string, mixed>  $dataH
     * @return array{0: string|null, 1: string|null, 2: mixed}
     */
    private function getAssetMetaFromPath(array $dataH, string $path, string $field): array
    {
        $value = null;
        $year = null;
        $assetname = null;

        if (preg_match('/(\w+).(\d+)/i', $path, $matchesH, PREG_OFFSET_CAPTURE)) {
            $assetname = $matchesH[1][0];
            $year = (int) $matchesH[2][0];
            $value = Arr::get($dataH, "$assetname.meta.$field");
        }

        return [$assetname, $year, $value];
    }

    /**
     * Helper to get values from dataH with defaults. Almost duplicate with same function in PrognosisService
     *
     * @param  array<string, mixed>  $dataH
     */
    private function ArrGet(array $dataH, string $path, mixed $default = null): mixed
    {
        $default = null;
        if (Str::contains($path, ['Amount', 'Decimal', 'Percent', 'amount', 'decimal', 'percent', 'factor'])) {
            $default = 0;
        }

        return Arr::get($dataH, $path, $default);
    }

    /**
     * Helper to set values in dataH. Almost duplicate with same function in PrognosisService
     *
     * @param  array<string, mixed>  $dataH
     */
    private function ArrSet(array &$dataH, string $path, mixed $value): void
    {
        Arr::set($dataH, $path, $value);
    }
}
