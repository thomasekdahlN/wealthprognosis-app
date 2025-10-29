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
            Arr::set($totalH, "$year.fire.diffDecimal", Arr::get($totalH, "$year.fire.incomeAmount", 0) / Arr::get($totalH, "$year.fire.expenceAmount", 0));
        }

        if (Arr::get($companyH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($companyH, "$year.fire.diffDecimal", Arr::get($companyH, "$year.fire.incomeAmount", 0) / Arr::get($companyH, "$year.fire.expenceAmount", 0));
        }

        if (Arr::get($privateH, "$year.fire.expenceAmount", 0) > 0) {
            Arr::set($privateH, "$year.fire.diffDecimal", Arr::get($privateH, "$year.fire.incomeAmount", 0) / Arr::get($privateH, "$year.fire.expenceAmount", 0));
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
     */
    public function calculateFortuneTax(array &$totalH, array &$companyH, array &$privateH, int $year): void
    {
        [$assetTaxFortuneAmount, $fortuneTaxDecimal, $taxableAmount, $explanation1] = $this->taxfortune->calculatefortunetax(false, $year, 'total', Arr::get($totalH, "$year.asset.taxableAmount", 0), 0, true);
        Arr::set($totalH, "$year.asset.taxableAmount", $taxableAmount);
        Arr::set($totalH, "$year.asset.taxFortuneAmount", $assetTaxFortuneAmount);

        [$assetTaxFortuneAmount, $fortuneTaxDecimal, $taxableAmount, $explanation1] = $this->taxfortune->calculatefortunetax(false, $year, 'company', Arr::get($companyH, "$year.asset.taxableAmount", 0), 0, true);
        Arr::set($companyH, "$year.asset.taxableAmount", $taxableAmount);
        Arr::set($companyH, "$year.asset.taxFortuneAmount", $assetTaxFortuneAmount);

        [$assetTaxFortuneAmount, $fortuneTaxDecimal, $taxableAmount, $explanation1] = $this->taxfortune->calculatefortunetax(false, $year, 'private', Arr::get($privateH, "$year.asset.taxableAmount", 0), 0, true);
        Arr::set($privateH, "$year.asset.taxableAmount", $taxableAmount);
        Arr::set($privateH, "$year.asset.taxFortuneAmount", $assetTaxFortuneAmount);
    }

    /**
     * Calculate actual change rates of income, expense and assets - not the prognosed one.
     *
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  int  $year  Year to calculate for
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
        // The tax rate for transferring company assets to a private person.
        $realizationTaxDecimal = 37.8 / 100;

        // Retrieve the amount after normal taxation from realization in the companyH array.
        $originalAmount = Arr::get($companyH, "$year.realization.amount");
        $originalTaxAmount = Arr::get($companyH, "$year.realization.taxAmount");

        if ($originalAmount > 0) {
            $dividendTaxAmount = round($originalAmount * $realizationTaxDecimal);

            // Calculate the final amount by subtracting the dividend tax from the original amount.
            $amount = round($originalAmount - $dividendTaxAmount);

            // Calculate the tax amount by adding the company tax to the private person tax.
            $taxAmount = $originalTaxAmount + $dividendTaxAmount;

            // Print the calculated values for debugging purposes.
            $description = " Company dividend tax on originalAmount: $originalAmount, originalTaxAmount: $originalTaxAmount, dividendTaxAmount:$dividendTaxAmount, newTaxAmount: $taxAmount, realizationamount: $amount";

            // Update the companyH array with the calculated values.
            Arr::set($companyH, "$year.realization.amount", $amount);
            Arr::set($companyH, "$year.realization.taxAmount", $taxAmount);
            Arr::set($companyH, "$year.realization.taxDecimal", $realizationTaxDecimal);
            Arr::set($companyH, "$year.realization.description", $description);
        }

        Arr::set($companyH, "$year.realization.taxShieldAmount", 0);
        Arr::set($companyH, "$year.realization.taxShieldDecimal", 0);
    }

    /**
     * Calculate yield percentages for groups.
     *
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  int  $year  Year to calculate for
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
