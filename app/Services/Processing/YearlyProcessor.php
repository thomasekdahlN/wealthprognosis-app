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
use App\Services\Tax\TaxCashflowService;
use App\Services\Tax\TaxFortuneService;
use App\Services\Utilities\HelperService;
use App\Support\ValueObjects\AssetMeta;
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
        private TaxCashflowService $taxCashflow,
        private HelperService $helper,
        private AssetTypeService $assetTypeService
    ) {}

    /**
     * Process fortune tax for a specific year and asset path.
     * Has to be done because a mortgage could potentially have extra downpayments making the fortune calculation wrong.
     * Has to be run before cashflow calculations to be correct.
     *
     * Individual assets calculate fortune tax WITHOUT the standard deduction (deduct=false) to show
     * the full fortune tax cost of each asset. The group-level calculation will apply the deduction.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     * @param  int  $thisYear  Current year
     */
    public function processFortuneTaxYearly(array &$dataH, string $path, int $thisYear): void
    {
        $meta = $this->getAssetMetaFromPath($dataH, $path);

        if ($meta->year >= $thisYear) { // For efficiensy, not neccessarry to calculate previous tax
            $taxType = $this->assetTypeService->getTaxType($meta->type);

            // Skip tax calculation if tax_type is null
            if ($taxType === null) {
                return;
            }

            $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
            $taxableAmount = $this->ArrGet($dataH, "$path.asset.taxableAmount");
            $mortgageBalanceAmount = $this->ArrGet($dataH, "$path.mortgage.balanceAmount");

            // Calculate fortune tax for individual asset
            // Note: We pass the mortgage balance here so it's deducted from the taxable base
            // The taxableFortuneAmount will be (marketAmount * taxableRate) - mortgageBalance
            $fortuneTaxResult = $this->taxfortune->taxCalculationFortune($meta->group, $taxType, $meta->year, $marketAmount, $taxableAmount, $mortgageBalanceAmount, false);

            // Store the taxable fortune amount (after mortgage deduction)
            $this->ArrSet($dataH, "$path.asset.taxableFortuneAmount", $fortuneTaxResult->taxableFortuneAmount);
            $this->ArrSet($dataH, "$path.asset.taxableFortunePercent", $fortuneTaxResult->taxableFortunePercent);
            $this->ArrSet($dataH, "$path.asset.taxableFortuneRate", $fortuneTaxResult->taxableFortuneRate);

            // Store the fortune tax amount (calculated without standard deduction for individual assets)
            $this->ArrSet($dataH, "$path.asset.taxFortuneAmount", $fortuneTaxResult->taxFortuneAmount);
            $this->ArrSet($dataH, "$path.asset.taxFortunePercent", $fortuneTaxResult->taxFortunePercent);
            $this->ArrSet($dataH, "$path.asset.taxFortuneRate", $fortuneTaxResult->taxFortuneRate);

            // Store gjeldsfradrag (mortgage balance used for fortune tax deduction)
            $this->ArrSet($dataH, "$path.asset.gjeldsfradragAmount", $mortgageBalanceAmount);
        }
    }

    /**
     * Process property tax for a specific year and asset path.
     * Has to be run before cashflow calculations to be correct.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     * @param  int  $thisYear  Current year
     */
    public function processPropertyTaxYearly(array &$dataH, string $path, int $thisYear): void
    {
        $meta = $this->getAssetMetaFromPath($dataH, $path);

        if ($meta->year >= $thisYear && $meta->taxProperty) { // For efficiensy, not neccessarry to calculate previous tax
            $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");

            // Calculate property tax
            $propertyTaxService = app(\App\Services\Tax\TaxPropertyService::class);
            $propertyTaxResult = $propertyTaxService->calculatePropertyTax($meta->year, $meta->group, $meta->taxProperty, (float) $marketAmount);
            $this->ArrSet($dataH, "$path.asset.taxablePropertyAmount", $propertyTaxResult->taxablePropertyAmount);
            $this->ArrSet($dataH, "$path.asset.taxPropertyAmount", $propertyTaxResult->taxPropertyAmount);
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
        // Use the dedicated service to recalculate cashflow
        $this->taxCashflow->recalculateCashflow($dataH, $path, $thisYear);
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
        $meta = $this->getAssetMetaFromPath($dataH, $path);

        $taxType = $this->assetTypeService->getTaxType($meta->type);

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
        $netPercent = 0;
        $netRate = 0;

        $grossPercent = 0;
        $grossRate = 0;

        $capPercent = 0;
        $capRate = 0;

        $acquisitionAmount = $this->ArrGet($dataH, "$path.asset.acquisitionAmount");
        if (! $acquisitionAmount) {
            $acquisitionAmount = $this->ArrGet($dataH, "$path.asset.marketAmount"); // FIX: Is this an assumption that will make problems?
        }

        if ($acquisitionAmount > 1) {
            // Gross Rental Yield (GRY)
            // This provides a fast preliminary measure of the potential return on investment.
            $grossRate = round(($this->ArrGet($dataH, "$path.income.amount") / $acquisitionAmount), 2);
            $grossPercent = $grossRate * 100;

            // Net Rental Yield (NRY)
            // This offers a more accurate reflection of the property's profitability by factoring in operating expenses, propertyTax and Mortgage.termAmount (FIX?: Some may say it is only the interestAmount that should be used.
            $expence = $this->ArrGet($dataH, "$path.expence.amount") + $this->ArrGet($dataH, "$path.asset.propertyTaxAmount") + $this->ArrGet($dataH, "$path.mortgage.termAmount");
            $netRate = round((($this->ArrGet($dataH, "$path.income.amount") - $expence) / $acquisitionAmount), 2);
            $netPercent = $netRate * 100;

            // Capitalization (CAP) Rate (Cap Rate), which is closely related to the Net Rental Yield but uses a different measure of income:
            // Net Operating Income (NOI) is the Annual Rental Income minus Operating Expenses (same as NRY expenses, but explicitly excludes mortgage interest, principal payments, and depreciation).
            // The Cap Rate is used to evaluate the property's performance independent of the financing method.
            $netOperatingIncome = $this->ArrGet($dataH, "$path.income.amount") - $this->ArrGet($dataH, "$path.expence.amount") - $this->ArrGet($dataH, "$path.asset.propertyTaxAmount");
            $capRate = round(($netOperatingIncome / $acquisitionAmount), 2);
            $capPercent = $capRate * 100;

        }
        $this->ArrSet($dataH, "$path.yield.grossPercent", $grossPercent);
        $this->ArrSet($dataH, "$path.yield.grossRate", $grossRate);

        $this->ArrSet($dataH, "$path.yield.netPercent", $netPercent);
        $this->ArrSet($dataH, "$path.yield.netRate", $netRate);

        $this->ArrSet($dataH, "$path.yield.capPercent", $capPercent);
        $this->ArrSet($dataH, "$path.yield.capRate", $capRate);
    }

    /**
     * Calculate financial metrics for a specific year and asset path.
     * Orchestrates all metric calculations by calling specialized methods.
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    public function processFinancialMetricsYearly(array &$dataH, string $path): void
    {
        $this->processInvestmentReturns($dataH, $path);
        $this->processPropertyMetrics($dataH, $path);
        $this->processLeverageMetrics($dataH, $path);
        $this->processProfitabilityRatios($dataH, $path);
        $this->processValuationMetrics($dataH, $path);
        $this->processLiquidityMetrics($dataH, $path);
    }

    /**
     * Calculate investment return metrics (ROI, Total Return, CoC).
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    private function processInvestmentReturns(array &$dataH, string $path): void
    {
        $acquisitionAmount = $this->ArrGet($dataH, "$path.asset.acquisitionAmount");
        $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
        $paidAmount = $this->ArrGet($dataH, "$path.asset.paidAmount");
        $cashflowAfterTax = $this->ArrGet($dataH, "$path.cashflow.afterTaxAmount");

        // ROI (Return on Investment) - Total gain/loss relative to acquisition cost
        $roiPercent = 0;
        $roiRate = 0;
        if ($acquisitionAmount > 0) {
            $totalGain = ($marketAmount - $acquisitionAmount) + $cashflowAfterTax;
            $roiRate = round($totalGain / $acquisitionAmount, 4);
            $roiPercent = round($roiRate * 100, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.roiRate", $roiRate);
        $this->ArrSet($dataH, "$path.metrics.roiPercent", $roiPercent);

        // Total Return - Market value change + income received
        $totalReturnAmount = ($marketAmount - $acquisitionAmount) + $cashflowAfterTax;
        $totalReturnPercent = 0;
        $totalReturnRate = 0;
        if ($acquisitionAmount > 0) {
            $totalReturnRate = round($totalReturnAmount / $acquisitionAmount, 4);
            $totalReturnPercent = round($totalReturnRate * 100, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.totalReturnAmount", round($totalReturnAmount, 2));
        $this->ArrSet($dataH, "$path.metrics.totalReturnRate", $totalReturnRate);
        $this->ArrSet($dataH, "$path.metrics.totalReturnPercent", $totalReturnPercent);

        // Cash-on-Cash Return (CoC) - Annual cash flow relative to cash invested
        $cocPercent = 0;
        $cocRate = 0;
        if ($paidAmount > 0) {
            $cocRate = round($cashflowAfterTax / $paidAmount, 4);
            $cocPercent = round($cocRate * 100, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.cocRate", $cocRate);
        $this->ArrSet($dataH, "$path.metrics.cocPercent", $cocPercent);
    }

    /**
     * Calculate property-specific metrics (NOI, GRM).
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    private function processPropertyMetrics(array &$dataH, string $path): void
    {
        $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
        $incomeAmount = $this->ArrGet($dataH, "$path.income.amount");
        $expenceAmount = $this->ArrGet($dataH, "$path.expence.amount");
        $propertyTaxAmount = $this->ArrGet($dataH, "$path.asset.taxPropertyAmount");

        // Calculate Net Operating Income (NOI)
        $noi = $incomeAmount - $expenceAmount - $propertyTaxAmount;
        $this->ArrSet($dataH, "$path.metrics.noi", round($noi, 2));

        // Gross Rent Multiplier (GRM) - Market value / Annual rental income
        $grm = 0;
        if ($incomeAmount > 0) {
            $grm = round($marketAmount / $incomeAmount, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.grm", $grm);
    }

    /**
     * Calculate leverage metrics (DSCR, LTV, D/E).
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    private function processLeverageMetrics(array &$dataH, string $path): void
    {
        $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
        $equityAmount = $this->ArrGet($dataH, "$path.asset.equityAmount");
        $incomeAmount = $this->ArrGet($dataH, "$path.income.amount");
        $expenceAmount = $this->ArrGet($dataH, "$path.expence.amount");
        $propertyTaxAmount = $this->ArrGet($dataH, "$path.asset.taxPropertyAmount");
        $mortgageBalance = $this->ArrGet($dataH, "$path.mortgage.balanceAmount");
        $mortgageTerm = $this->ArrGet($dataH, "$path.mortgage.termAmount");

        // Calculate NOI for DSCR
        $noi = $incomeAmount - $expenceAmount - $propertyTaxAmount;

        // Debt Service Coverage Ratio (DSCR) - NOI / Total debt service
        $dscr = 0;
        if ($mortgageTerm > 0) {
            $dscr = round($noi / $mortgageTerm, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.dscr", $dscr);

        // Loan-to-Value (LTV) Ratio - Mortgage balance / Market value
        $ltvPercent = 0;
        $ltvRate = 0;
        if ($marketAmount > 0) {
            $ltvRate = round($mortgageBalance / $marketAmount, 4);
            $ltvPercent = round($ltvRate * 100, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.ltvRate", $ltvRate);
        $this->ArrSet($dataH, "$path.metrics.ltvPercent", $ltvPercent);

        // Debt-to-Equity (D/E) Ratio - Total debt / Total equity
        $deRatio = 0;
        if ($equityAmount > 0) {
            $deRatio = round($mortgageBalance / $equityAmount, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.deRatio", $deRatio);
    }

    /**
     * Calculate profitability ratios (ROE, ROA).
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    private function processProfitabilityRatios(array &$dataH, string $path): void
    {
        $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
        $equityAmount = $this->ArrGet($dataH, "$path.asset.equityAmount");
        $cashflowAfterTax = $this->ArrGet($dataH, "$path.cashflow.afterTaxAmount");

        // Return on Equity (ROE) - Net income / Equity
        $roePercent = 0;
        $roeRate = 0;
        if ($equityAmount > 0) {
            $roeRate = round($cashflowAfterTax / $equityAmount, 4);
            $roePercent = round($roeRate * 100, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.roeRate", $roeRate);
        $this->ArrSet($dataH, "$path.metrics.roePercent", $roePercent);

        // Return on Assets (ROA) - Net income / Total assets
        $roaPercent = 0;
        $roaRate = 0;
        if ($marketAmount > 0) {
            $roaRate = round($cashflowAfterTax / $marketAmount, 4);
            $roaPercent = round($roaRate * 100, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.roaRate", $roaRate);
        $this->ArrSet($dataH, "$path.metrics.roaPercent", $roaPercent);
    }

    /**
     * Calculate valuation metrics (P/B, EV/EBITDA).
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    private function processValuationMetrics(array &$dataH, string $path): void
    {
        $marketAmount = $this->ArrGet($dataH, "$path.asset.marketAmount");
        $equityAmount = $this->ArrGet($dataH, "$path.asset.equityAmount");
        $incomeAmount = $this->ArrGet($dataH, "$path.income.amount");
        $expenceAmount = $this->ArrGet($dataH, "$path.expence.amount");
        $propertyTaxAmount = $this->ArrGet($dataH, "$path.asset.taxPropertyAmount");
        $mortgageBalance = $this->ArrGet($dataH, "$path.mortgage.balanceAmount");

        // Calculate NOI for EV/EBITDA
        $noi = $incomeAmount - $expenceAmount - $propertyTaxAmount;

        // Price-to-Book (P/B) Ratio - Market value / Equity (book value)
        $pbRatio = 0;
        if ($equityAmount > 0) {
            $pbRatio = round($marketAmount / $equityAmount, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.pbRatio", $pbRatio);

        // Enterprise Value/EBITDA (EV/EBITDA) - (Market value + Debt) / EBITDA
        // For real estate, we use NOI as a proxy for EBITDA
        $evEbitda = 0;
        if ($noi > 0) {
            $enterpriseValue = $marketAmount + $mortgageBalance;
            $evEbitda = round($enterpriseValue / $noi, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.evEbitda", $evEbitda);
    }

    /**
     * Calculate liquidity metrics (Current Ratio).
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     */
    private function processLiquidityMetrics(array &$dataH, string $path): void
    {
        $equityAmount = $this->ArrGet($dataH, "$path.asset.equityAmount");
        $cashflowAfterTax = $this->ArrGet($dataH, "$path.cashflow.afterTaxAmount");
        $mortgageTerm = $this->ArrGet($dataH, "$path.mortgage.termAmount");

        // Current Ratio - Current assets / Current liabilities
        // For real estate: (Cash flow + Equity) / Annual debt service
        $currentRatio = 0;
        if ($mortgageTerm > 0) {
            $currentAssets = abs($cashflowAfterTax) + $equityAmount;
            $currentRatio = round($currentAssets / $mortgageTerm, 2);
        }
        $this->ArrSet($dataH, "$path.metrics.currentRatio", $currentRatio);
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
     * @return AssetMeta|null Returns null if path is invalid
     */
    private function getAssetMetaFromPath(array $dataH, string $path): ?AssetMeta
    {
        return AssetMeta::fromPath($dataH, $path);
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
