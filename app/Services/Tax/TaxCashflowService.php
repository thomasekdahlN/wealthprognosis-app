<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services\Tax;

use App\Services\Utilities\HelperService;
use App\Support\ValueObjects\CashflowCalculationResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * TaxCashflowService
 *
 * Handles cashflow calculations including before-tax and after-tax amounts.
 * Provides shared logic for both initial calculations and post-processing recalculations.
 */
class TaxCashflowService
{
    public function __construct(
        private TaxIncomeService $taxIncomeService,
        private HelperService $helper
    ) {}

    /**
     * Calculate initial cashflow with income tax calculation and transfers.
     * Used by PrognosisService for the initial cashflow calculation.
     *
     * @param  bool  $debug  Debug mode flag
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     * @param  string  $taxGroup  Tax group (private/company)
     * @param  string  $taxType  Tax type for income calculation
     * @param  int  $year  Current year
     * @param  float  $incomeAmount  Income amount
     * @param  float  $expenceAmount  Expense amount
     * @param  float  $interestAmount  Interest amount for tax calculation
     * @param  float  $assetTaxFortuneAmount  Fortune tax amount
     * @param  float  $assetTaxPropertyAmount  Property tax amount
     */
    public function calculateInitialCashflow(
        bool $debug,
        array &$dataH,
        string $path,
        string $taxGroup,
        ?string $taxType,
        int $year,
        float $incomeAmount,
        float $expenceAmount,
        float $interestAmount,
        float $assetTaxFortuneAmount,
        float $assetTaxPropertyAmount
    ): CashflowCalculationResult {
        // Calculate income tax on cashflow
        $incomeTaxResult = $this->taxIncomeService->taxCalculationIncome(
            $debug,
            $taxGroup,
            $taxType,
            $year,
            $incomeAmount,
            $expenceAmount,
            $interestAmount
        );

        $cashflowTaxAmount = $incomeTaxResult->taxAmount;
        $cashflowTaxPercent = $incomeTaxResult->taxRate;
        $cashflowTaxRate = $incomeTaxResult->taxRate; // Same as percent for rate
        $cashflowDescription = $incomeTaxResult->explanation;

        // Calculate before-tax cashflow
        $cashflowBeforeTaxAmount = $this->calculateBeforeTaxCashflow(
            $dataH,
            $path,
            $incomeAmount,
            $expenceAmount,
            false // Don't include extra mortgage payments for initial calculation
        );

        // Calculate after-tax cashflow
        $cashflowAfterTaxAmount = $this->calculateAfterTaxCashflow(
            $dataH,
            $path,
            $cashflowBeforeTaxAmount,
            $cashflowTaxAmount,
            $assetTaxFortuneAmount,
            $assetTaxPropertyAmount
        );

        Log::info('Initial cashflow calculation result', [
            'path' => $path,
            'income_amount' => $incomeAmount,
            'expence_amount' => $expenceAmount,
            'interest_amount' => $interestAmount,
            'cashflow_tax_amount' => $cashflowTaxAmount,
            'cashflow_before_tax_amount' => $cashflowBeforeTaxAmount,
            'cashflow_after_tax_amount' => $cashflowAfterTaxAmount,
            'transferred_amount' => $this->ArrGet($dataH, "$path.income.transferedAmount"),
        ]);

        return new CashflowCalculationResult(
            beforeTaxAmount: $cashflowBeforeTaxAmount,
            afterTaxAmount: $cashflowAfterTaxAmount,
            taxAmount: $cashflowTaxAmount,
            taxPercent: $cashflowTaxPercent,
            taxRate: $cashflowTaxRate,
            description: $cashflowDescription
        );
    }

    /**
     * Recalculate cashflow for post-processing adjustments.
     * Used by YearlyProcessor to adjust for mortgage extra payments and other changes.
     *
     * @param  array  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path (e.g., "assetname.year")
     * @param  int  $thisYear  Current year for aggregation calculations
     */
    public function recalculateCashflow(array &$dataH, string $path, int $thisYear): CashflowCalculationResult
    {
        [$assetname, $year, $type, $field] = $this->helper->pathToElements("$path.cashflow.beforeTaxAmount");
        $prevYear = (int) $year - 1;

        // Recalculate before-tax cashflow with all mortgage adjustments
        $cashflowBeforeTaxAmount = $this->calculateBeforeTaxCashflow(
            $dataH,
            $path,
            $this->ArrGet($dataH, "$path.income.amount"),
            $this->ArrGet($dataH, "$path.expence.amount"),
            true // Include extra mortgage payments for recalculation
        );

        // Recalculate after-tax cashflow using existing tax amount
        $cashflowAfterTaxAmount = $this->calculateAfterTaxCashflow(
            $dataH,
            $path,
            $cashflowBeforeTaxAmount,
            $this->ArrGet($dataH, "$path.cashflow.taxAmount"), // Use existing tax amount
            $this->ArrGet($dataH, "$path.asset.taxFortuneAmount"),
            $this->ArrGet($dataH, "$path.asset.taxPropertyAmount")
        );

        // Update the data structure
        $this->ArrSet($dataH, "$path.cashflow.beforeTaxAmount", $cashflowBeforeTaxAmount);
        $this->ArrSet($dataH, "$path.cashflow.afterTaxAmount", $cashflowAfterTaxAmount);

        // Calculate aggregated amounts for current and future years
        if ($year >= $thisYear) {
            $this->ArrSet($dataH, "$path.cashflow.beforeTaxAggregatedAmount",
                $cashflowBeforeTaxAmount + $this->ArrGet($dataH, "$assetname.$prevYear.cashflow.beforeTaxAggregatedAmount"));
            $this->ArrSet($dataH, "$path.cashflow.afterTaxAggregatedAmount",
                $cashflowAfterTaxAmount + $this->ArrGet($dataH, "$assetname.$prevYear.cashflow.afterTaxAggregatedAmount"));
        }

        return new CashflowCalculationResult(
            beforeTaxAmount: $cashflowBeforeTaxAmount,
            afterTaxAmount: $cashflowAfterTaxAmount,
            taxAmount: $this->ArrGet($dataH, "$path.cashflow.taxAmount"),
            taxPercent: 0, // Not recalculated in post-processing
            taxRate: 0, // Not recalculated in post-processing
            description: '' // Not recalculated in post-processing
        );
    }

    /**
     * Calculate before-tax cashflow amount.
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path
     * @param  float  $incomeAmount  Income amount
     * @param  float  $expenceAmount  Expense amount
     * @param  bool  $includeExtraMortgagePayments  Whether to include extra mortgage payments
     */
    private function calculateBeforeTaxCashflow(
        array &$dataH,
        string $path,
        float $incomeAmount,
        float $expenceAmount,
        bool $includeExtraMortgagePayments = false
    ): float {
        $cashflowBeforeTaxAmount =
            $incomeAmount
            + $this->ArrGet($dataH, "$path.income.transferedAmount")
            - $expenceAmount
            - $this->ArrGet($dataH, "$path.mortgage.termAmount");

        if ($includeExtraMortgagePayments) {
            $cashflowBeforeTaxAmount -= $this->ArrGet($dataH, "$path.expence.transferedAmount");
            $cashflowBeforeTaxAmount -= $this->ArrGet($dataH, "$path.mortgage.extraDownpaymentAmount");
            $cashflowBeforeTaxAmount -= $this->ArrGet($dataH, "$path.mortgage.gebyrAmount");
        }

        return $cashflowBeforeTaxAmount;
    }

    /**
     * Calculate after-tax cashflow amount.
     *
     * @param  array<string, mixed>  $dataH  Reference to the main data structure
     * @param  string  $path  Asset path
     * @param  float  $cashflowBeforeTaxAmount  Before-tax cashflow amount
     * @param  float  $cashflowTaxAmount  Income tax amount
     * @param  float  $assetTaxFortuneAmount  Fortune tax amount
     * @param  float  $assetTaxPropertyAmount  Property tax amount
     */
    private function calculateAfterTaxCashflow(
        array &$dataH,
        string $path,
        float $cashflowBeforeTaxAmount,
        float $cashflowTaxAmount,
        float $assetTaxFortuneAmount,
        float $assetTaxPropertyAmount
    ): float {
        return $cashflowBeforeTaxAmount
            + $this->ArrGet($dataH, "$path.mortgage.taxDeductableAmount") // Plus skattefradrag på renter
            - $cashflowTaxAmount // Minus skatt på cashflow (Kan være både positiv og negativ)
            - $assetTaxFortuneAmount // Minus formuesskatt
            - $assetTaxPropertyAmount; // Minus eiendomsskatt
    }

    /**
     * Helper to get values from dataH with defaults.
     *
     * @param  array<string, mixed>  $dataH
     */
    private function ArrGet(array $dataH, string $path, mixed $default = null): mixed
    {
        if (Str::contains($path, ['Amount', 'Decimal', 'Percent', 'amount', 'decimal', 'percent', 'factor'])) {
            $default = 0;
        }

        return Arr::get($dataH, $path, $default);
    }

    /**
     * Helper to set values in dataH.
     *
     * @param  array  $dataH
     */
    private function ArrSet(array &$dataH, string $path, mixed $value): void
    {
        Arr::set($dataH, $path, $value);
    }
}
