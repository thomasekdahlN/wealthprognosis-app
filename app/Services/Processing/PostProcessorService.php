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

/**
 * PostProcessorService
 *
 * Orchestrates post-processing of prognosis data.
 * Coordinates YearlyProcessor for individual asset calculations and GroupProcessor for aggregations.
 */
class PostProcessorService
{
    public function __construct(
        private YearlyProcessor $yearlyProcessor,
        private GroupProcessor $groupProcessor
    ) {}

    /**
     * Execute all post-processing steps on prognosis data.
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  array<string, mixed>  $totalH  Reference to total group data
     * @param  array<string, mixed>  $companyH  Reference to company group data
     * @param  array<string, mixed>  $privateH  Reference to private group data
     * @param  array<string, mixed>  $groupH  Reference to group hierarchy data
     * @param  array<string, mixed>  $statisticsH  Reference to statistics data
     * @param  int  $economyStartYear  Start year for economy calculations
     * @param  int  $deathYear  End year for calculations
     * @param  int  $thisYear  Current year
     * @param  array<string, bool>  $assetTypeSavingMap  Map of asset types that count as savings
     * @param  callable  $isShownInStatistics  Callback to check if type should be shown in statistics
     */
    public function process(
        array &$dataH,
        array &$totalH,
        array &$companyH,
        array &$privateH,
        array &$groupH,
        array &$statisticsH,
        int $economyStartYear,
        int $deathYear,
        int $thisYear,
        array $assetTypeSavingMap,
        callable $isShownInStatistics
    ): void {
        // Step 1: Process yearly data for each asset
        $this->processYearlyData($dataH, $economyStartYear, $deathYear, $thisYear, $assetTypeSavingMap);

        // Step 2: Group and aggregate data
        $this->processGroupData(
            $dataH,
            $totalH,
            $companyH,
            $privateH,
            $groupH,
            $statisticsH,
            $economyStartYear,
            $deathYear,
            $isShownInStatistics
        );
    }

    /**
     * Process yearly data for all assets.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  int  $economyStartYear  Start year for economy calculations
     * @param  int  $deathYear  End year for calculations
     * @param  int  $thisYear  Current year
     * @param  array  $assetTypeSavingMap  Map of asset types that count as savings
     */
    private function processYearlyData(
        array &$dataH,
        int $economyStartYear,
        int $deathYear,
        int $thisYear,
        array $assetTypeSavingMap
    ): void {
        foreach ($dataH as $assetname => $assetH) {
            $meta = $assetH['meta'];
            if (! $meta['active']) {
                continue; // Skip inactive assets
            }

            for ($year = $economyStartYear; $year <= $deathYear; $year++) {
                $datapath = "$assetname.$year";

                $this->yearlyProcessor->processIncomeYearly($dataH, $datapath);
                $this->yearlyProcessor->processExpenceYearly($dataH, $datapath);
                $this->yearlyProcessor->processFortuneTaxYearly($dataH, $datapath, $thisYear);
                $this->yearlyProcessor->processCashFlowYearly($dataH, $datapath, $thisYear);
                $this->yearlyProcessor->processAssetYearly($dataH, $datapath);
                $this->yearlyProcessor->processRealizationYearly($dataH, $datapath);
                $this->yearlyProcessor->processPotentialYearly($dataH, $datapath);
                $this->yearlyProcessor->processYieldYearly($dataH, $datapath);
                $this->yearlyProcessor->processFireYearly($dataH, $assetname, $year, $meta, $assetTypeSavingMap);
            }
        }
    }

    /**
     * Process group aggregations and calculations.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  array  $totalH  Reference to total group data
     * @param  array  $companyH  Reference to company group data
     * @param  array  $privateH  Reference to private group data
     * @param  array  $groupH  Reference to group hierarchy data
     * @param  array  $statisticsH  Reference to statistics data
     * @param  int  $economyStartYear  Start year for economy calculations
     * @param  int  $deathYear  End year for calculations
     * @param  callable  $isShownInStatistics  Callback to check if type should be shown in statistics
     */
    private function processGroupData(
        array &$dataH,
        array &$totalH,
        array &$companyH,
        array &$privateH,
        array &$groupH,
        array &$statisticsH,
        int $economyStartYear,
        int $deathYear,
        callable $isShownInStatistics
    ): void {
        // Initialize group structures
        $this->groupProcessor->initGroups($privateH, $companyH, $economyStartYear, $deathYear);

        // Aggregate data from individual assets to groups
        foreach ($dataH as $assetname => $assetH) {
            $meta = $assetH['meta'];
            if (! $meta['active']) {
                continue; // Skip inactive assets
            }

            for ($year = $economyStartYear; $year <= $deathYear; $year++) {
                // Add all relevant fields to groups
                $this->addAllFieldsToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $assetH[$year]);
            }
        }

        // Perform advanced calculations on grouped data
        for ($year = $economyStartYear; $year <= $deathYear; $year++) {
            $this->groupProcessor->calculateFireSaveRate($totalH, $companyH, $privateH, $year);
            $this->groupProcessor->calculateFirediffPercent($totalH, $companyH, $privateH, $year);
            $this->groupProcessor->calculateFortuneTax($totalH, $companyH, $privateH, $year);
            $this->groupProcessor->calculateChangerates($totalH, $companyH, $privateH, $year);
            $this->groupProcessor->calculateCompanyDividendTax($companyH, $year);
            $this->groupProcessor->calculateYield($totalH, $companyH, $privateH, $year);
        }

        // Calculate asset type spread for statistics
        $this->groupProcessor->calculateAssetTypeSpread($groupH, $statisticsH, $isShownInStatistics);
    }

    /**
     * Add all relevant fields from an asset year to group totals.
     *
     * @param  array  $totalH  Reference to total group data
     * @param  array  $companyH  Reference to company group data
     * @param  array  $privateH  Reference to private group data
     * @param  array  $groupH  Reference to group hierarchy data
     * @param  int  $year  Year for the data
     * @param  array  $meta  Asset metadata
     * @param  array  $data  Year data for the asset
     */
    private function addAllFieldsToGroup(
        array &$totalH,
        array &$companyH,
        array &$privateH,
        array &$groupH,
        int $year,
        array $meta,
        array $data
    ): void {
        // Income fields
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'income.amount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'income.transferedAmount');

        // Expense fields
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'expence.amount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'expence.transferedAmount');

        // Cashflow fields
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'cashflow.beforeTaxAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'cashflow.afterTaxAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'cashflow.beforeTaxAggregatedAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'cashflow.afterTaxAggregatedAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'cashflow.taxAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'cashflow.transferedAmount');

        // Mortgage fields
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'mortgage.amount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'mortgage.termAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'mortgage.interestAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'mortgage.principalAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'mortgage.balanceAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'mortgage.extraDownpaymentAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'mortgage.gebyrAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'mortgage.taxDeductableAmount');

        // Asset fields
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.marketAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.marketMortgageDeductedAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.acquisitionAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.equityAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.paidAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.transferedAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.taxableAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.taxFortuneAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.taxablePropertyAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'asset.taxPropertyAmount');

        // Realization fields
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'realization.amount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'realization.taxableAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'realization.taxAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'realization.taxShieldAmount');

        // Potential fields
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'potential.incomeAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'potential.mortgageAmount');

        // FIRE fields
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'fire.incomeAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'fire.expenceAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'fire.savingAmount');
        $this->groupProcessor->additionToGroup($totalH, $companyH, $privateH, $groupH, $year, $meta, $data, 'fire.cashFlowAmount');
    }
}
