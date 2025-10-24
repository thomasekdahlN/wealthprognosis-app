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

use App\Models\AssetType;
use App\Services\Tax\TaxFortuneService;
use App\Services\Utilities\HelperService;
use Illuminate\Support\Arr;

/**
 * YearlyProcessor
 *
 * Handles yearly post-processing calculations for individual assets.
 * Processes income, expenses, cashflow, assets, realization, potential, yield, and FIRE metrics.
 */
class YearlyProcessor
{
    public function __construct(
        private TaxFortuneService $taxfortune,
        private HelperService $helper
    ) {}

    /**
     * Process income for a specific year and asset path.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    public function processIncomeYearly(array &$dataH, string $path): void
    {
        // Currently empty - placeholder for future income processing logic
    }

    /**
     * Process expenses for a specific year and asset path.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    public function processExpenceYearly(array &$dataH, string $path): void
    {
        // Currently empty - placeholder for future expense processing logic
    }

    /**
     * Process fortune tax for a specific year and asset path.
     * Has to be done because a mortgage could potentially have extra downpayments making the fortune calculation wrong.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     * @param  int  $thisYear  Current year
     */
    public function processFortuneTaxYearly(array &$dataH, string $path, int $thisYear): void
    {
        [$assetname, $year, $type, $field] = $this->helper->pathToElements("$path.cashflow.beforeTaxAmount");
        [$assetname, $year, $taxGroup] = $this->getAssetMetaFromPath($dataH, $path, 'group');

        // derive tax type via asset_type
        [$assetname, $year, $assetType] = $this->getAssetMetaFromPath($dataH, $path, 'type');
        $taxType = 'none';
        try {
            $assetTypeO = AssetType::where('type', $assetType)->with('taxType')->first();
            $taxType = $assetTypeO?->taxType?->type ?? 'none';
        } catch (\Throwable $e) {
            $taxType = 'none';
        }

        $taxProperty = $this->ArrGet($dataH, "$path.meta.property");

        $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
        $taxableAmount = $this->ArrGet($dataH, "$path.asset.taxableAmount");
        $mortgageBalanceAmount = $this->ArrGet($dataH, "$path.mortgage.balanceAmount");

        [$taxAmount, $taxPercent, $taxableFortuneAmount, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $taxFortuneAmount, $explanation] = $this->taxfortune->taxCalculationFortune($taxGroup, $taxType, $taxProperty, $year, $marketAmount, $taxableAmount, $mortgageBalanceAmount, false);

        $this->ArrSet($dataH, "$path.asset.taxFortuneAmount", $taxFortuneAmount);
        $this->ArrSet($dataH, "$path.asset.taxablePropertyAmount", $taxablePropertyAmount);
        $this->ArrSet($dataH, "$path.asset.taxPropertyAmount", $taxPropertyAmount);

        // Recalculate cashflow after tax
        if ($year >= $thisYear) {
            $cashflowBeforeTaxAmount = $this->ArrGet($dataH, "$path.cashflow.beforeTaxAmount");
            $cashflowAfterTaxAmount = $cashflowBeforeTaxAmount - $taxFortuneAmount;
            $this->ArrSet($dataH, "$path.cashflow.afterTaxAmount", $cashflowAfterTaxAmount);
            $this->ArrSet($dataH, "$path.cashflow.taxAmount", $taxFortuneAmount);
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
        $prevYear = $year - 1;

        // Recalculating cashflow. Necessary if a mortgage is paid with extra amount.
        $cashflowBeforeTaxAmount =
            $this->ArrGet($dataH, "$path.income.amount")
            - $this->ArrGet($dataH, "$path.expence.amount")
            + $this->ArrGet($dataH, "$path.income.transferedAmount")
            - $this->ArrGet($dataH, "$path.expence.transferedAmount")
            - $this->ArrGet($dataH, "$path.mortgage.termAmount")
            - $this->ArrGet($dataH, "$path.mortgage.extraDownpaymentAmount")
            - $this->ArrGet($dataH, "$path.mortgage.gebyrAmount");

        $cashflowAfterTaxAmount =
            $cashflowBeforeTaxAmount
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
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    public function processAssetYearly(array &$dataH, string $path): void
    {
        [$assetname, $year, $type, $field] = $this->helper->pathToElements("$path.cashflow.beforeTaxAmount");
        $prevYear = $year - 1;

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
     * Process realization for a specific year and asset path.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    public function processRealizationYearly(array &$dataH, string $path): void
    {
        // Currently empty - placeholder for future realization processing logic
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
        // derive tax type via asset_type
        [$assetname, $year, $assetType] = $this->getAssetMetaFromPath($dataH, $path, 'type');
        $taxType = 'none';
        try {
            $assetTypeO = AssetType::where('type', $assetType)->with('taxType')->first();
            $taxType = $assetTypeO?->taxType?->type ?? 'none';
        } catch (\Throwable $e) {
            $taxType = 'none';
        }

        // If the tax type is 'salary' or 'pension', calculate the potential income and mortgage amounts.
        if ($taxType == 'salary' || $taxType == 'pension') {
            $incomeAmount = $this->ArrGet($dataH, "$path.income.amount");
            $mortgageAmount = $incomeAmount * 5;

            $this->ArrSet($dataH, "$path.potential.incomeAmount", $incomeAmount);
            $this->ArrSet($dataH, "$path.potential.mortgageAmount", $mortgageAmount);
        }
    }

    /**
     * Calculate yield percentages for a specific year and asset path.
     *
     * @param  array  $dataH  Reference to the main data structure
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
     * @param  array  $meta  Asset metadata
     * @param  array  $assetTypeSavingMap  Map of asset types that count as savings
     */
    public function processFireYearly(array &$dataH, string $assetname, int $year, array $meta, array $assetTypeSavingMap): void
    {
        $prevYear = $year - 1;
        $firePercent = 0;
        $fireAssetIncomeAmount = 0; // Only asset value
        $CashflowTaxableAmount = 0;

        $path = "$assetname.$year";
        $assetMarketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
        $incomeAmount = $this->ArrGet($dataH, "$path.income.amount");
        $expenceAmount = $this->ArrGet($dataH, "$path.expence.amount");
        $cashFlowAmount = $this->ArrGet($dataH, "$path.cashflow.afterTaxAmount");

        // Calculate FIRE income from sellable assets
        $assetType = Arr::get($meta, 'type');
        if (isset($assetTypeSavingMap[$assetType]) && $assetTypeSavingMap[$assetType]) {
            $fireAssetIncomeAmount = round($assetMarketAmount * 0.04);
        }

        $fireExpenceAmount = $expenceAmount;
        $fireCashFlowAmount = $cashFlowAmount;
        $fireSavingAmount = $incomeAmount - $expenceAmount;
        $fireSavingRateDecimal = 0;

        if ($incomeAmount > 0) {
            $fireSavingRateDecimal = $fireSavingAmount / $incomeAmount;
        }

        if ($fireExpenceAmount > 0) {
            $firePercent = round(($fireAssetIncomeAmount / $fireExpenceAmount) * 100);
        }

        $fireRatePercent = 0.04;

        $dataH[$assetname][$year]['fire'] = [
            'percent' => $firePercent,
            'incomeAmount' => $fireAssetIncomeAmount,
            'expenceAmount' => $fireExpenceAmount,
            'rateDecimal' => $fireRatePercent,
            'cashFlowAmount' => $fireCashFlowAmount,
            'savingAmount' => $fireSavingAmount,
            'savingRateDecimal' => $fireSavingRateDecimal,
        ];
    }

    /**
     * Helper method to get asset metadata from path.
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
     * Helper to get values from dataH with defaults.
     */
    private function ArrGet(array $dataH, string $path, mixed $default = null): mixed
    {
        if (str_contains($path, 'Amount') || str_contains($path, 'Decimal') || str_contains($path, 'Percent') ||
            str_contains($path, 'amount') || str_contains($path, 'decimal') || str_contains($path, 'percent') ||
            str_contains($path, 'factor')) {
            $default = 0;
        }

        return Arr::get($dataH, $path, $default);
    }

    /**
     * Helper to set values in dataH.
     */
    private function ArrSet(array &$dataH, string $path, mixed $value): void
    {
        Arr::set($dataH, $path, $value);
    }
}
