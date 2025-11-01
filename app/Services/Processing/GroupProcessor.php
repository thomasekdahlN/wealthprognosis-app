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

use App\Services\Tax\TaxFortuneService;
use Illuminate\Support\Arr;

/**
 * GroupProcessor
 *
 * Handles grouping and aggregation of prognosis data across assets.
 * Processes totals, company/private splits, and statistical spreads.
 */
class GroupProcessor
{
    public function __construct(
        private TaxFortuneService $taxfortune
    ) {}

    /**
     * Initialize group structures with empty data for proper sorting.
     *
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  int  $economyStartYear  Start year for economy calculations
     * @param  int  $deathYear  End year for calculations
     *
     * @param-out array<string, mixed> $privateH
     * @param-out array<string, mixed> $companyH
     */
    public function initGroups(array &$privateH, array &$companyH, int $economyStartYear, int $deathYear): void
    {
        // Just to get the sorting right, it's better to start with an empty structure in correct yearly order
        for ($year = $economyStartYear; $year <= $deathYear; $year++) {
            Arr::set($privateH, "$year.asset.marketAmount", 0);
            Arr::set($companyH, "$year.asset.marketAmount", 0);
        }
    }

    /**
     * Add a value to group totals.
     *
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  array<string, mixed>  $groupH  Reference to group hierarchy data
     * @param  int  $year  Year for the data
     * @param  array<string, mixed>  $meta  Asset metadata
     * @param  array<string, mixed>  $data  Year data for the asset
     * @param  string  $dotpath  Dot-notation path to the value
     *
     * @param-out array<string, mixed> $totalH
     * @param-out array<string, mixed> $companyH
     * @param-out array<string, mixed> $privateH
     * @param-out array<string, mixed> $groupH
     */
    public function additionToGroup(
        array &$totalH,
        array &$companyH,
        array &$privateH,
        array &$groupH,
        int $year,
        array $meta,
        array $data,
        string $dotpath
    ): void {
        // Add to total
        Arr::set($totalH, "$year.$dotpath", Arr::get($totalH, "$year.$dotpath", 0) + Arr::get($data, $dotpath, 0));

        // Add to company group
        if (Arr::get($meta, 'group') == 'company') {
            Arr::set($companyH, "$year.$dotpath", Arr::get($companyH, "$year.$dotpath", 0) + Arr::get($data, $dotpath, 0));
        }

        // Add to private group
        if (Arr::get($meta, 'group') == 'private') {
            Arr::set($privateH, "$year.$dotpath", Arr::get($privateH, "$year.$dotpath", 0) + Arr::get($data, $dotpath, 0));
        }

        // Add to group and type hierarchies
        $grouppath = Arr::get($meta, 'group').".$year.$dotpath";
        $typepath = Arr::get($meta, 'type').".$year.$dotpath";
        Arr::set($groupH, $grouppath, Arr::get($groupH, $grouppath, 0) + Arr::get($data, $dotpath, 0));
        Arr::set($groupH, $typepath, Arr::get($groupH, $typepath, 0) + Arr::get($data, $dotpath, 0));
    }

    /**
     * Set a value to group (not additive).
     *
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  array<string, mixed>  $groupH  Reference to group hierarchy data
     * @param  int  $year  Year for the data
     * @param  array<string, mixed>  $meta  Asset metadata
     * @param  array<string, mixed>  $data  Year data for the asset
     * @param  string  $dotpath  Dot-notation path to the value
     *
     * @param-out array<string, mixed> $totalH
     * @param-out array<string, mixed> $companyH
     * @param-out array<string, mixed> $privateH
     * @param-out array<string, mixed> $groupH
     */
    public function setToGroup(
        array &$totalH,
        array &$companyH,
        array &$privateH,
        array &$groupH,
        int $year,
        array $meta,
        array $data,
        string $dotpath
    ): void {
        if (Arr::get($data, $dotpath)) {
            // Set to total
            Arr::set($totalH, "$year.$dotpath", Arr::get($data, $dotpath));

            // Set to company group
            if (Arr::get($meta, 'group') == 'company') {
                Arr::set($companyH, "$year.$dotpath", Arr::get($data, $dotpath));
            }

            // Set to private group
            if (Arr::get($meta, 'group') == 'private') {
                Arr::set($privateH, "$year.$dotpath", Arr::get($data, $dotpath));
            }

            // Set to group and type hierarchies
            $grouppath = Arr::get($meta, 'group').".$year.$dotpath";
            $typepath = Arr::get($meta, 'type').".$year.$dotpath";
            Arr::set($groupH, $grouppath, Arr::get($data, $dotpath));
            Arr::set($groupH, $typepath, Arr::get($data, $dotpath));
        }
    }

    /**
     * Calculate FIRE saving rate for groups.
     *
     * Savings rate = savingAmount / incomeAmount
     * This represents what percentage of income is being saved.
     *
     * @param  array  $totalH  Reference to total group data
     * @param  array  $companyH  Reference to company group data
     * @param  array  $privateH  Reference to private group data
     * @param  int  $year  Year to calculate for
     */
    public function calculateFireSaveRate(array &$totalH, array &$companyH, array &$privateH, int $year): void
    {
        $totalIncome = Arr::get($totalH, "$year.fire.incomeAmount", 0);
        if ($totalIncome > 0) {
            Arr::set($totalH, "$year.fire.savingRate", Arr::get($totalH, "$year.fire.savingAmount", 0) / $totalIncome);
        }

        $companyIncome = Arr::get($companyH, "$year.fire.incomeAmount", 0);
        if ($companyIncome > 0) {
            Arr::set($companyH, "$year.fire.savingRate", Arr::get($companyH, "$year.fire.savingAmount", 0) / $companyIncome);
        }

        $privateIncome = Arr::get($privateH, "$year.fire.incomeAmount", 0);
        if ($privateIncome > 0) {
            Arr::set($privateH, "$year.fire.savingRate", Arr::get($privateH, "$year.fire.savingAmount", 0) / $privateIncome);
        }
    }

    /**
     * Calculate FIRE difference percentage for groups.
     *
     * @param  array  $totalH  Reference to total group data
     * @param  array  $companyH  Reference to company group data
     * @param  array  $privateH  Reference to private group data
     * @param  int  $year  Year to calculate for
     */
    public function calculateFirediffPercent(array &$totalH, array &$companyH, array &$privateH, int $year): void
    {
        if (Arr::get($totalH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($totalH, "$year.fire.diffRate", Arr::get($totalH, "$year.fire.incomeAmount", 0) / Arr::get($totalH, "$year.fire.expenceAmount", 0));
        }

        if (Arr::get($companyH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($companyH, "$year.fire.diffRate", Arr::get($companyH, "$year.fire.incomeAmount", 0) / Arr::get($companyH, "$year.fire.expenceAmount", 0));
        }

        if (Arr::get($privateH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($privateH, "$year.fire.diffRate", Arr::get($privateH, "$year.fire.incomeAmount", 0) / Arr::get($privateH, "$year.fire.expenceAmount", 0));
        }
    }

    /**
     * Calculate fortune tax for groups.
     * We can not subtract mortgage again, it is already subtracted in the taxableAmount part, therefore we send in zero as mortgage here.
     *
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  int  $year  Year to calculate for
     *
     * @param-out array<string, mixed> $totalH
     * @param-out array<string, mixed> $companyH
     * @param-out array<string, mixed> $privateH
     */
    public function calculateFortuneTax(array &$totalH, array &$companyH, array &$privateH, int $year): void
    {
        $totalResult = $this->taxfortune->calculatefortunetax(false, $year, 'total', Arr::get($totalH, "$year.asset.taxableAmount", 0), 0, true);
        Arr::set($totalH, "$year.asset.taxableAmount", $totalResult->taxableFortuneAmount);
        Arr::set($totalH, "$year.asset.taxablePercent", $totalResult->taxableFortunePercent);
        Arr::set($totalH, "$year.asset.taxableRate", $totalResult->taxableFortuneRate);

        Arr::set($totalH, "$year.asset.taxFortuneAmount", $totalResult->taxFortuneAmount);
        Arr::set($totalH, "$year.asset.taxFortunePercent", $totalResult->taxFortunePercent); // Use Rate not Percent for Excel export
        Arr::set($totalH, "$year.asset.taxFortuneRate", $totalResult->taxFortuneRate); // Use Rate not Percent for Excel export

        $companyResult = $this->taxfortune->calculatefortunetax(false, $year, 'company', Arr::get($companyH, "$year.asset.taxableAmount", 0), 0, true);
        Arr::set($companyH, "$year.asset.taxableAmount", $companyResult->taxableFortuneAmount);
        Arr::set($companyH, "$year.asset.taxablePercent", $companyResult->taxableFortunePercent);
        Arr::set($companyH, "$year.asset.taxableRate", $companyResult->taxableFortuneRate);

        Arr::set($companyH, "$year.asset.taxFortuneAmount", $companyResult->taxFortuneAmount);
        Arr::set($companyH, "$year.asset.taxFortunePercent", $companyResult->taxFortunePercent); // Use Rate not Percent for Excel export
        Arr::set($companyH, "$year.asset.taxFortuneRate", $companyResult->taxFortuneRate); // Use Rate not Percent for Excel export

        $privateResult = $this->taxfortune->calculatefortunetax(false, $year, 'private', Arr::get($privateH, "$year.asset.taxableAmount", 0), 0, true);
        Arr::set($privateH, "$year.asset.taxableAmount", $privateResult->taxableFortuneAmount);
        Arr::set($privateH, "$year.asset.taxFortuneAmount", $privateResult->taxFortuneAmount);
        Arr::set($privateH, "$year.asset.taxFortunePercent", $privateResult->taxFortunePercent); // Use Rate (not Percent for Excel export
        Arr::set($privateH, "$year.asset.taxFortuneRate", $privateResult->taxFortuneRate); // Use Rate not Percent for Excel export
    }

    /**
     * Calculate group-level financial metrics aggregation.
     * Aggregates metrics from individual assets to group totals (total, company, private).
     *
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  int  $year  Year to calculate for
     *
     * @param-out array<string, mixed> $totalH
     * @param-out array<string, mixed> $companyH
     * @param-out array<string, mixed> $privateH
     */
    public function calculateGroupFinancialMetrics(array &$totalH, array &$companyH, array &$privateH, int $year): void
    {
        $this->calculateGroupInvestmentReturns($totalH, $year);
        $this->calculateGroupInvestmentReturns($companyH, $year);
        $this->calculateGroupInvestmentReturns($privateH, $year);

        $this->calculateGroupPropertyMetrics($totalH, $year);
        $this->calculateGroupPropertyMetrics($companyH, $year);
        $this->calculateGroupPropertyMetrics($privateH, $year);

        $this->calculateGroupLeverageMetrics($totalH, $year);
        $this->calculateGroupLeverageMetrics($companyH, $year);
        $this->calculateGroupLeverageMetrics($privateH, $year);

        $this->calculateGroupProfitabilityRatios($totalH, $year);
        $this->calculateGroupProfitabilityRatios($companyH, $year);
        $this->calculateGroupProfitabilityRatios($privateH, $year);

        $this->calculateGroupValuationMetrics($totalH, $year);
        $this->calculateGroupValuationMetrics($companyH, $year);
        $this->calculateGroupValuationMetrics($privateH, $year);

        $this->calculateGroupLiquidityMetrics($totalH, $year);
        $this->calculateGroupLiquidityMetrics($companyH, $year);
        $this->calculateGroupLiquidityMetrics($privateH, $year);
    }

    /**
     * Calculate group-level investment return metrics.
     *
     * @param  array<string, mixed>  $groupH  Reference to group data
     * @param  int  $year  Year to calculate for
     */
    private function calculateGroupInvestmentReturns(array &$groupH, int $year): void
    {
        $acquisitionAmount = Arr::get($groupH, "$year.asset.acquisitionAmount", 0);
        $marketAmount = Arr::get($groupH, "$year.asset.marketAmount", 0);
        $paidAmount = Arr::get($groupH, "$year.asset.paidAmount", 0);
        $cashflowAfterTax = Arr::get($groupH, "$year.cashflow.afterTaxAmount", 0);

        // ROI (Return on Investment)
        if ($acquisitionAmount > 0) {
            $totalGain = ($marketAmount - $acquisitionAmount) + $cashflowAfterTax;
            $roiRate = round($totalGain / $acquisitionAmount, 4);
            $roiPercent = round($roiRate * 100, 2);
            Arr::set($groupH, "$year.metrics.roiRate", $roiRate);
            Arr::set($groupH, "$year.metrics.roiPercent", $roiPercent);
        }

        // Total Return
        $totalReturnAmount = ($marketAmount - $acquisitionAmount) + $cashflowAfterTax;
        if ($acquisitionAmount > 0) {
            $totalReturnRate = round($totalReturnAmount / $acquisitionAmount, 4);
            $totalReturnPercent = round($totalReturnRate * 100, 2);
            Arr::set($groupH, "$year.metrics.totalReturnAmount", round($totalReturnAmount, 2));
            Arr::set($groupH, "$year.metrics.totalReturnRate", $totalReturnRate);
            Arr::set($groupH, "$year.metrics.totalReturnPercent", $totalReturnPercent);
        }

        // Cash-on-Cash Return (CoC)
        if ($paidAmount > 0) {
            $cocRate = round($cashflowAfterTax / $paidAmount, 4);
            $cocPercent = round($cocRate * 100, 2);
            Arr::set($groupH, "$year.metrics.cocRate", $cocRate);
            Arr::set($groupH, "$year.metrics.cocPercent", $cocPercent);
        }
    }

    /**
     * Calculate group-level property metrics.
     *
     * @param  array<string, mixed>  $groupH  Reference to group data
     * @param  int  $year  Year to calculate for
     */
    private function calculateGroupPropertyMetrics(array &$groupH, int $year): void
    {
        $marketAmount = Arr::get($groupH, "$year.asset.marketAmount", 0);
        $incomeAmount = Arr::get($groupH, "$year.income.amount", 0);
        $expenceAmount = Arr::get($groupH, "$year.expence.amount", 0);
        $propertyTaxAmount = Arr::get($groupH, "$year.asset.taxPropertyAmount", 0);

        // Net Operating Income (NOI)
        $noi = $incomeAmount - $expenceAmount - $propertyTaxAmount;
        Arr::set($groupH, "$year.metrics.noi", round($noi, 2));

        // Gross Rent Multiplier (GRM)
        if ($incomeAmount > 0) {
            $grm = round($marketAmount / $incomeAmount, 2);
            Arr::set($groupH, "$year.metrics.grm", $grm);
        }
    }

    /**
     * Calculate group-level leverage metrics.
     *
     * @param  array<string, mixed>  $groupH  Reference to group data
     * @param  int  $year  Year to calculate for
     */
    private function calculateGroupLeverageMetrics(array &$groupH, int $year): void
    {
        $marketAmount = Arr::get($groupH, "$year.asset.marketAmount", 0);
        $equityAmount = Arr::get($groupH, "$year.asset.equityAmount", 0);
        $mortgageBalance = Arr::get($groupH, "$year.mortgage.balanceAmount", 0);
        $mortgageTerm = Arr::get($groupH, "$year.mortgage.termAmount", 0);
        $noi = Arr::get($groupH, "$year.metrics.noi", 0);

        // Debt Service Coverage Ratio (DSCR)
        if ($mortgageTerm > 0) {
            $dscr = round($noi / $mortgageTerm, 2);
            Arr::set($groupH, "$year.metrics.dscr", $dscr);
        }

        // Loan-to-Value (LTV) Ratio
        if ($marketAmount > 0) {
            $ltvRate = round($mortgageBalance / $marketAmount, 4);
            $ltvPercent = round($ltvRate * 100, 2);
            Arr::set($groupH, "$year.metrics.ltvRate", $ltvRate);
            Arr::set($groupH, "$year.metrics.ltvPercent", $ltvPercent);
        }

        // Debt-to-Equity (D/E) Ratio
        if ($equityAmount > 0) {
            $deRatio = round($mortgageBalance / $equityAmount, 2);
            Arr::set($groupH, "$year.metrics.deRatio", $deRatio);
        }
    }

    /**
     * Calculate group-level profitability ratios.
     *
     * @param  array<string, mixed>  $groupH  Reference to group data
     * @param  int  $year  Year to calculate for
     */
    private function calculateGroupProfitabilityRatios(array &$groupH, int $year): void
    {
        $marketAmount = Arr::get($groupH, "$year.asset.marketAmount", 0);
        $equityAmount = Arr::get($groupH, "$year.asset.equityAmount", 0);
        $cashflowAfterTax = Arr::get($groupH, "$year.cashflow.afterTaxAmount", 0);

        // Return on Equity (ROE)
        if ($equityAmount > 0) {
            $roeRate = round($cashflowAfterTax / $equityAmount, 4);
            $roePercent = round($roeRate * 100, 2);
            Arr::set($groupH, "$year.metrics.roeRate", $roeRate);
            Arr::set($groupH, "$year.metrics.roePercent", $roePercent);
        }

        // Return on Assets (ROA)
        if ($marketAmount > 0) {
            $roaRate = round($cashflowAfterTax / $marketAmount, 4);
            $roaPercent = round($roaRate * 100, 2);
            Arr::set($groupH, "$year.metrics.roaRate", $roaRate);
            Arr::set($groupH, "$year.metrics.roaPercent", $roaPercent);
        }
    }

    /**
     * Calculate group-level valuation metrics.
     *
     * @param  array<string, mixed>  $groupH  Reference to group data
     * @param  int  $year  Year to calculate for
     */
    private function calculateGroupValuationMetrics(array &$groupH, int $year): void
    {
        $marketAmount = Arr::get($groupH, "$year.asset.marketAmount", 0);
        $equityAmount = Arr::get($groupH, "$year.asset.equityAmount", 0);
        $mortgageBalance = Arr::get($groupH, "$year.mortgage.balanceAmount", 0);
        $noi = Arr::get($groupH, "$year.metrics.noi", 0);

        // Price-to-Book (P/B) Ratio
        if ($equityAmount > 0) {
            $pbRatio = round($marketAmount / $equityAmount, 2);
            Arr::set($groupH, "$year.metrics.pbRatio", $pbRatio);
        }

        // Enterprise Value/EBITDA (EV/EBITDA)
        if ($noi > 0) {
            $enterpriseValue = $marketAmount + $mortgageBalance;
            $evEbitda = round($enterpriseValue / $noi, 2);
            Arr::set($groupH, "$year.metrics.evEbitda", $evEbitda);
        }
    }

    /**
     * Calculate group-level liquidity metrics.
     *
     * @param  array<string, mixed>  $groupH  Reference to group data
     * @param  int  $year  Year to calculate for
     */
    private function calculateGroupLiquidityMetrics(array &$groupH, int $year): void
    {
        $equityAmount = Arr::get($groupH, "$year.asset.equityAmount", 0);
        $cashflowAfterTax = Arr::get($groupH, "$year.cashflow.afterTaxAmount", 0);
        $mortgageTerm = Arr::get($groupH, "$year.mortgage.termAmount", 0);

        // Current Ratio
        if ($mortgageTerm > 0) {
            $currentAssets = abs($cashflowAfterTax) + $equityAmount;
            $currentRatio = round($currentAssets / $mortgageTerm, 2);
            Arr::set($groupH, "$year.metrics.currentRatio", $currentRatio);
        }
    }

    /**
     * Calculate actual change rates of income, expense and assets - not the prognosed one.
     *
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  int  $year  Year to calculate for
     *
     * @param-out array<string, mixed> $totalH
     * @param-out array<string, mixed> $companyH
     * @param-out array<string, mixed> $privateH
     */
    public function calculateChangerates(array &$totalH, array &$companyH, array &$privateH, int $year): void
    {
        $prevYear = $year - 1;

        // Total income changerate
        if (Arr::get($totalH, "$prevYear.income.amount") > 0) {
            Arr::set($totalH, "$year.income.changeratePercent", ((Arr::get($totalH, "$year.income.amount") / Arr::get($totalH, "$prevYear.income.amount")) - 1) * 100);
        } else {
            Arr::set($totalH, "$year.income.changeratePercent", 0);
        }

        // Total expense changerate
        if (Arr::get($totalH, "$prevYear.expence.amount") > 0) {
            Arr::set($totalH, "$year.expence.changeratePercent", ((Arr::get($totalH, "$year.expence.amount") / Arr::get($totalH, "$prevYear.expence.amount")) - 1) * 100);
        } else {
            Arr::set($totalH, "$year.expence.changeratePercent", 0);
        }

        // Total asset changerate
        if (Arr::get($totalH, "$prevYear.asset.marketAmount") > 0) {
            Arr::set($totalH, "$year.asset.changeratePercent", ((Arr::get($totalH, "$year.asset.marketAmount") / Arr::get($totalH, "$prevYear.asset.marketAmount")) - 1) * 100);
        } else {
            Arr::set($totalH, "$year.asset.changeratePercent", 0);
        }

        // Company income changerate
        if (Arr::get($companyH, "$prevYear.income.amount") > 0) {
            Arr::set($companyH, "$year.income.changeratePercent", ((Arr::get($companyH, "$year.income.amount") / Arr::get($companyH, "$prevYear.income.amount")) - 1) * 100);
        } else {
            Arr::set($companyH, "$year.income.changeratePercent", 0);
        }

        // Company expense changerate
        if (Arr::get($companyH, "$prevYear.expence.amount") > 0) {
            Arr::set($companyH, "$year.expence.changeratePercent", ((Arr::get($companyH, "$year.expence.amount") / Arr::get($companyH, "$prevYear.expence.amount")) - 1) * 100);
        } else {
            Arr::set($companyH, "$year.expence.changeratePercent", 0);
        }

        // Company asset changerate
        if (Arr::get($companyH, "$prevYear.asset.marketAmount") > 0) {
            Arr::set($companyH, "$year.asset.changeratePercent", ((Arr::get($companyH, "$year.asset.marketAmount") / Arr::get($companyH, "$prevYear.asset.marketAmount")) - 1) * 100);
        } else {
            Arr::set($companyH, "$year.asset.changeratePercent", 0);
        }

        // Private income changerate
        if (Arr::get($privateH, "$prevYear.income.amount") > 0) {
            Arr::set($privateH, "$year.income.changeratePercent", ((Arr::get($privateH, "$year.income.amount") / Arr::get($privateH, "$prevYear.income.amount")) - 1) * 100);
        } else {
            Arr::set($privateH, "$year.income.changeratePercent", 0);
        }

        // Private expense changerate
        if (Arr::get($privateH, "$prevYear.expence.amount") > 0) {
            Arr::set($privateH, "$year.expence.changeratePercent", ((Arr::get($privateH, "$year.expence.amount") / Arr::get($privateH, "$prevYear.expence.amount")) - 1) * 100);
        } else {
            Arr::set($privateH, "$year.expence.changeratePercent", 0);
        }

        // Private asset changerate
        if (Arr::get($privateH, "$prevYear.asset.marketAmount") > 0) {
            Arr::set($privateH, "$year.asset.changeratePercent", ((Arr::get($privateH, "$year.asset.marketAmount") / Arr::get($privateH, "$prevYear.asset.marketAmount")) - 1) * 100);
        } else {
            Arr::set($privateH, "$year.asset.changeratePercent", 0);
        }
    }

    /**
     * Calculate company dividend tax.
     * This method calculates the amount that would be realized if company assets were transferred to a private person.
     * It takes into account the tax implications of such a transfer.
     *
     * @param  array  $companyH  Reference to company group data
     * @param  int  $year  Year to calculate for
     */
    public function calculateCompanyDividendTax(array &$companyH, int $year): void
    {
        // FIX:  The tax rate for transferring company assets to a private person. Something is missing here
        $realizationTaxRate = 37.8 / 100;

        // Retrieve the amount after normal taxation from realization in the companyH array.
        $originalAmount = Arr::get($companyH, "$year.realization.amount");
        $originalTaxAmount = Arr::get($companyH, "$year.realization.taxAmount");

        if ($originalAmount > 0) {
            $dividendTaxAmount = round($originalAmount * $realizationTaxRate);

            // Calculate the final amount by subtracting the dividend tax from the original amount.
            $amount = round($originalAmount - $dividendTaxAmount);

            // Calculate the tax amount by adding the company tax to the private person tax.
            $taxAmount = $originalTaxAmount + $dividendTaxAmount;

            // Print the calculated values for debugging purposes.
            $description = " Company dividend tax on originalAmount: $originalAmount, originalTaxAmount: $originalTaxAmount, dividendTaxAmount:$dividendTaxAmount, newTaxAmount: $taxAmount, realizationamount: $amount";

            // Update the companyH array with the calculated values.
            Arr::set($companyH, "$year.realization.amount", $amount);
            Arr::set($companyH, "$year.realization.taxAmount", $taxAmount);
            Arr::set($companyH, "$year.realization.taxRate", $realizationTaxRate);
            Arr::set($companyH, "$year.realization.description", $description);
        }

        Arr::set($companyH, "$year.realization.taxShieldAmount", 0);
        Arr::set($companyH, "$year.realization.taxShieldRate", 0);
    }

    /**
     * Calculate yield percentages for groups.
     *
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  int  $year  Year to calculate for
     *
     * @param-out array<string, mixed> $totalH
     * @param-out array<string, mixed> $companyH
     * @param-out array<string, mixed> $privateH
     */
    public function calculateYield(array &$totalH, array &$companyH, array &$privateH, int $year): void
    {
        // Total yield
        $bruttoPercent = 0;
        $nettoPercent = 0;
        if (Arr::get($totalH, "$year.asset.acquisitionAmount") > 1) {
            $bruttoPercent = round((Arr::get($totalH, "$year.income.amount") / Arr::get($totalH, "$year.asset.acquisitionAmount")) * 100, 1);
            $nettoPercent = round(((Arr::get($totalH, "$year.income.amount") - Arr::get($totalH, "$year.expence.amount")) / Arr::get($totalH, "$year.asset.acquisitionAmount")) * 100, 1);
        }
        Arr::set($totalH, "$year.yield.bruttoPercent", $bruttoPercent);
        Arr::set($totalH, "$year.yield.nettoPercent", $nettoPercent);

        // Company yield
        $bruttoPercent = 0;
        $nettoPercent = 0;
        if (Arr::get($companyH, "$year.asset.acquisitionAmount") > 1) {
            $bruttoPercent = round((Arr::get($companyH, "$year.income.amount") / Arr::get($companyH, "$year.asset.acquisitionAmount")) * 100, 1);
            $nettoPercent = round(((Arr::get($companyH, "$year.income.amount") - Arr::get($companyH, "$year.expence.amount")) / Arr::get($companyH, "$year.asset.acquisitionAmount")) * 100, 1);
        }
        Arr::set($companyH, "$year.yield.bruttoPercent", $bruttoPercent);
        Arr::set($companyH, "$year.yield.nettoPercent", $nettoPercent);

        // Private yield
        $bruttoPercent = 0;
        $nettoPercent = 0;
        if (Arr::get($privateH, "$year.asset.acquisitionAmount") > 1) {
            $bruttoPercent = round((Arr::get($privateH, "$year.income.amount") / Arr::get($privateH, "$year.asset.acquisitionAmount")) * 100, 1);
            $nettoPercent = round(((Arr::get($privateH, "$year.income.amount") - Arr::get($privateH, "$year.expence.amount")) / Arr::get($privateH, "$year.asset.acquisitionAmount")) * 100, 1);
        }
        Arr::set($privateH, "$year.yield.bruttoPercent", $bruttoPercent);
        Arr::set($privateH, "$year.yield.nettoPercent", $nettoPercent);
    }

    /**
     * Calculate asset type spread for statistics.
     *
     * @param  array  $groupH  Reference to group hierarchy data
     * @param  array  $statisticsH  Reference to statistics data
     * @param  callable  $isShownInStatistics  Callback to check if type should be shown in statistics
     */
    public function calculateAssetTypeSpread(array $groupH, array &$statisticsH, callable $isShownInStatistics): void
    {
        foreach ($groupH as $type => $asset) {
            if ($isShownInStatistics($type)) {
                foreach ($asset as $year => $data) {
                    $amount = round(Arr::get($data, 'asset.marketAmount', 0));
                    $statisticsH[$year][$type]['amount'] = $amount;
                    $statisticsH[$year]['total']['amount'] = Arr::get($statisticsH, "$year.total.amount", 0) + $amount;
                }

                // Generate % spread
                foreach ($statisticsH as $year => $typeH) {
                    foreach ($typeH as $typename => $data) {
                        if ($typeH['total']['amount'] > 0) {
                            $statisticsH[$year][$typename]['percent'] = round(($data['amount'] / $typeH['total']['amount']) * 100);
                        } else {
                            $statisticsH[$year][$typename]['percent'] = 0;
                        }
                    }
                }
            }
        }
    }
}
