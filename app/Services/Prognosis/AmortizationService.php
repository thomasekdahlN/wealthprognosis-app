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

use App\Services\Tax\TaxConfigRepository;
use App\Services\Utilities\HelperService;
use App\Support\ValueObjects\MortgageCalculation;
use App\Support\ValueObjects\MortgageData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * AmortizationService
 *
 * Service for calculating mortgage amortization schedules.
 * Handles both regular amortization and interest-only periods.
 *
 * @property bool $debug Enable debug logging
 * @property int $amount Original loan amount
 * @property int $year_start Starting year for amortization
 * @property int $year_end Ending year for amortization
 * @property int $term_years Total loan term in years
 * @property float $interest Interest rate (can be a reference to changerate)
 * @property int $terms Number of payment terms per year (always 1 for annual)
 * @property int $period Remaining periods on the loan
 * @property float $principalAmount Current period's principal payment
 * @property float $balanceAmount Current remaining balance
 * @property float $termAmount Current period's total payment
 * @property string $assettname Name of the asset
 * @property array<string, mixed> $dataH Data history array
 * @property object $changerate Changerate service for interest rate lookup
 * @property float|int|null $assetChangerateValue Cached changerate value
 * @property float $remainingMortgageAmount Remaining mortgage amount for current calculation
 * @property int $interestOnlyYears Remaining interest-only years
 * @property int $interestOnlyYearEnd Year when interest-only period ends
 * @property int $extraDownpaymentAmount Annual extra downpayment amount
 */
class AmortizationService
{
    /** @var int Original loan amount */
    private int $amount;

    /** @var int Starting year for amortization */
    private int $year_start;

    /** @var int Ending year for amortization */
    private int $year_end;

    /** @var int Total loan term in years */
    private int $term_years;

    /** @var string|float Interest rate (can be a reference to changerate like "changerates.interest" or a numeric value) */
    private string|float $interest;

    /** @var int Number of payment terms per year (always 1 for annual) */
    private int $terms;

    /** @var int Remaining years on the loan */
    private int $remainingYears;

    /** @var float Current remaining balance */
    private float $balanceAmount = 0;

    /** @var string|null Cached changerate type/variable name */
    private ?string $assetChangerateValue;

    /** @var float Remaining mortgage amount for current calculation */
    private float $remainingMortgageAmount;

    /** @var int Remaining interest-only years */
    private int $interestOnlyYears;

    /** @var int Year when interest-only period ends */
    private int $interestOnlyYearEnd;

    /** @var int Annual extra downpayment amount */
    private int $extraDownpaymentAmount;

    /** @var string Tax type for the asset */
    private string $taxType;

    /** @var string Country code for tax calculations */
    private string $taxCountry;

    /** @var TaxConfigRepository Tax configuration repository */
    private TaxConfigRepository $taxConfigRepo;

    /**
     * Create a new AmortizationService instance and calculate the amortization schedule.
     *
     * Initializes the service with mortgage details and immediately calculates
     * the complete amortization schedule from the starting year to loan maturity.
     *
     * @param  bool  $debug  Enable detailed debug logging
     * @param  array<string, mixed>  $config  Configuration array (kept for API compatibility, not used)
     * @param  object  $changerate  Changerate service for interest rate lookup
     * @param  array<string, mixed>  $dataH  Data history array to store results
     * @param  array<string, mixed>  $mortgage  Mortgage configuration with keys:
     *                                          - amount: Original loan amount
     *                                          - years: Loan term in years
     *                                          - interest: Interest rate or changerate reference
     *                                          - interestOnlyYears: Number of interest-only years (optional)
     *                                          - extraDownpaymentAmount: Annual extra payment (optional)
     * @param  string  $assettname  Name of the asset associated with the mortgage
     * @param  int  $year  Starting year for the amortization schedule
     * @param  HelperService  $helperService  Helper service for utility functions
     */
    public function __construct(
        private bool $debug,
        array $config,
        private object $changerate,
        private array $dataH,
        array $mortgage,
        private string $assettname,
        int $year,
        private HelperService $helperService = new HelperService
    ) {
        $this->assetChangerateValue = null;

        // Extract tax information from config
        $this->taxType = Arr::get($config, "$assettname.meta.tax_type", 'none');
        $this->taxCountry = Arr::get($config, "$assettname.meta.taxCountry", 'no');

        // Initialize tax config repository
        $this->taxConfigRepo = new TaxConfigRepository($this->taxCountry, $this->helperService);

        // Initialize loan parameters
        $this->year_start = (int) $year;
        $this->term_years = (int) Arr::get($mortgage, 'years');
        $this->amount = $this->remainingMortgageAmount = (int) Arr::get($mortgage, 'amount');

        // Interest can be a changerate reference (string) or a numeric value
        $interestValue = Arr::get($mortgage, 'interest');
        $this->interest = is_numeric($interestValue) ? (float) $interestValue : (string) $interestValue;

        $this->terms = 1; // Annual payments
        $this->remainingYears = $this->terms * $this->term_years;
        $this->balanceAmount = $this->amount;
        $this->year_end = $year + $this->term_years - 1; // Last year of the loan (inclusive)
        $this->interestOnlyYears = (int) Arr::get($mortgage, 'interestOnlyYears', 0);
        $this->interestOnlyYearEnd = $year + $this->interestOnlyYears;
        $this->extraDownpaymentAmount = (int) Arr::get($mortgage, 'extraDownpaymentAmount', 0);

        // Clean up any existing mortgage data and calculate schedule
        $this->removeMortgageFrom($this->year_start);
        $this->calculateAmortizationSchedule();
    }

    /**
     * Calculates the amortization schedule for the loan.
     *
     * This method calculates the amortization schedule for the loan by iterating over each year from the start to the end of the loan.
     * For each year, it checks if the balance of the loan is non-negative. If it is, it calls the `calculate()` method, passing the current year as an argument.
     * After the `calculate()` method is called, the loan amount is updated to be the current balance of the loan, and the period of the loan is decremented by one.
     * Once the amortization schedule has been calculated, the `assetChangerateValue` property is reset to `null`.
     */
    public function calculateAmortizationSchedule(): void
    {
        while ($this->balanceAmount > 0 && $this->year_start <= $this->year_end) {
            // echo "$this->year_start, period: $this->remainingYears, extraDownpaymentAmount: $this->extraDownpaymentAmount\n";
            $this->calculate(false, $this->year_start++, $this->extraDownpaymentAmount);
            $this->remainingMortgageAmount = $this->balanceAmount;
            $this->remainingYears--; // Teller ned på antall år i lånet.
            if ($this->interestOnlyYears > 0) {
                $this->interestOnlyYears--; // Teller ned på antall rentefrie år
            }
        }
        $this->assetChangerateValue = null;
    }

    /**
     * Calculate the amortization denominator.
     *
     * @param  float  $interestRate  Interest rate as decimal
     * @param  int  $remainingYears  Number of remaining years
     */
    private function calculateDenominator(float $interestRate, int $remainingYears): float
    {
        return 1 - (1 / pow((1 + $interestRate), $remainingYears));
    }

    /**
     * Calculate interest amount for the period.
     *
     * @param  int  $remainingBalanceAmount  Remaining mortgage balance
     * @param  float  $interestRate  Interest rate as decimal
     */
    private function calculateInterestAmount(int $remainingBalanceAmount, float $interestRate): float
    {
        return $remainingBalanceAmount * $interestRate;
    }

    /**
     * Calculate term amount (total payment) for the period.
     *
     * @param  int  $remainingBalanceAmount  Remaining mortgage balance
     * @param  float  $interestRate  Interest rate as decimal
     * @param  float  $denominator  Amortization denominator
     */
    private function calculateTermAmount(int $remainingBalanceAmount, float $interestRate, float $denominator): float
    {
        return ($remainingBalanceAmount * $interestRate) / $denominator;
    }

    /**
     * Perform mortgage calculation for a specific year.
     *
     * @param  int  $year  Year to calculate
     * @param  float  $interestPercent  Interest rate as percentage
     * @param  float  $interestRate  Interest rate as decimal
     * @param  float  $extraDownpaymentAmount  Extra downpayment amount
     */
    private function performCalculation(
        int $year,
        float $interestPercent,
        float $interestRate,
        float $extraDownpaymentAmount
    ): MortgageCalculation {
        $denominator = $this->calculateDenominator($interestRate, $this->remainingYears);

        if ($denominator <= 0) {
            // Fallback for invalid denominator
            $interestAmount = $this->calculateInterestAmount($this->remainingMortgageAmount, $interestRate);

            return MortgageCalculation::fallback(
                interestPercent: $interestPercent,
                interestRate: $interestRate,
                interestAmount: $interestAmount,
                extraDownpaymentAmount: $extraDownpaymentAmount,
                remainingBalanceAmount: $this->remainingMortgageAmount
            );
        }

        $interestAmount = $this->calculateInterestAmount($this->remainingMortgageAmount, $interestRate);

        if ($year < $this->interestOnlyYearEnd) {
            // Interest-only period
            return MortgageCalculation::interestOnly(
                interestPercent: $interestPercent,
                interestRate: $interestRate,
                interestAmount: $interestAmount,
                remainingBalanceAmount: $this->remainingMortgageAmount,
                extraDownpaymentAmount: $extraDownpaymentAmount,
                denominator: $denominator
            );
        }

        // Regular amortization period
        $termAmount = $this->calculateTermAmount($this->remainingMortgageAmount, $interestRate, $denominator);

        return MortgageCalculation::regular(
            interestPercent: $interestPercent,
            interestRate: $interestRate,
            interestAmount: $interestAmount,
            termAmount: $termAmount,
            extraDownpaymentAmount: $extraDownpaymentAmount,
            remainingBalanceAmount: $this->remainingMortgageAmount,
            denominator: $denominator
        );
    }

    /**
     * Create MortgageData from calculation result.
     *
     * @param  MortgageCalculation  $calculation  Calculation result
     * @param  float  $extraDownpaymentAmount  Extra downpayment amount
     * @param  int  $year  Year for tax rate lookup
     */
    private function createMortgageData(MortgageCalculation $calculation, float $extraDownpaymentAmount, int $year): MortgageData
    {
        $description = null;
        if ($extraDownpaymentAmount > 0) {
            $description = "extraDownpaymentAmount: $extraDownpaymentAmount";
        }
        if ($calculation->explanation) {
            $description = $description ? "$description\n{$calculation->explanation}" : $calculation->explanation;
        }

        // Get tax deduction rate from tax configuration
        $taxDeductableRate = $this->taxConfigRepo->getTaxIncomeRate($this->taxType, $year);
        $taxDeductablePercent = $taxDeductableRate * 100;
        $taxDeductableAmount = $calculation->interestAmount * $taxDeductableRate;

        return new MortgageData(
            amount: $this->amount,
            termAmount: $calculation->termAmount,
            interestAmount: $calculation->interestAmount,
            interestPercent: $calculation->interestPercent,
            interestRate: $calculation->interestRate,
            principalAmount: $calculation->principalAmount,
            balanceAmount: $calculation->balanceAmount,
            extraDownpaymentAmount: $extraDownpaymentAmount,
            years: $this->remainingYears,
            interestOnlyYears: $this->interestOnlyYears,
            gebyrAmount: 0,
            taxDeductableAmount: $taxDeductableAmount,
            taxDeductablePercent: $taxDeductablePercent,
            taxDeductableRate: $taxDeductableRate,
            description: $description
        );
    }

    /**
     * Calculate mortgage for a specific year.
     *
     * @param  bool  $debug  Enable debug logging
     * @param  int  $year  Year to calculate
     * @param  float  $extraDownpaymentAmount  Extra downpayment amount
     */
    private function calculate(bool $debug, int $year, float $extraDownpaymentAmount = 0): void
    {
        // Retrieve interest rate for the year
        [$interestPercent, $interestDecimal, $this->assetChangerateValue, $explanation] = $this->changerate->getChangerate(
            false,
            $this->interest,
            $year,
            $this->assetChangerateValue
        );
        $interestRate = $this->helperService->percentToRate($interestPercent);

        // Perform calculation
        $calculation = $this->performCalculation($year, $interestPercent, $interestRate, $extraDownpaymentAmount);

        // Update instance state
        $this->balanceAmount = $calculation->balanceAmount;

        // Debug logging
        if ($this->debug) {
            $this->logCalculation($year, $calculation, $interestPercent, $interestRate);
        }

        // Store mortgage data - always store if we had a remaining balance at the start of the year
        $mortgageData = $this->createMortgageData($calculation, $extraDownpaymentAmount, $year);
        $this->dataH[$this->assettname][$year]['mortgage'] = $mortgageData->toArray();
    }

    /**
     * Log calculation details for debugging.
     *
     * @param  int  $year  Year being calculated
     * @param  MortgageCalculation  $calculation  Calculation result
     * @param  float  $interestPercent  Interest rate as percentage
     * @param  float  $interestRate  Interest rate as decimal
     */
    private function logCalculation(
        int $year,
        MortgageCalculation $calculation,
        float $interestPercent,
        float $interestRate
    ): void {
        Log::debug('Mortgage amortization calculation', [
            'year' => $year,
            'remaining_years' => $this->remainingYears,
            'interest_only_years' => $this->interestOnlyYears,
            'denominator' => $calculation->denominator,
            'interest_percent' => $interestPercent,
            'interest_rate' => $interestRate,
            'remaining_mortgage_amount' => $this->remainingMortgageAmount,
            'term_amount' => $calculation->termAmount,
            'interest_amount' => $calculation->interestAmount,
            'principal_amount' => $calculation->principalAmount,
            'balance_amount' => $calculation->balanceAmount,
        ]);

        if (app()->runningInConsole()) {
            echo "      $year: years: $this->remainingYears, interestOnlyYears: $this->interestOnlyYears, deno: {$calculation->denominator} : $interestPercent% = $interestRate : remainingMortgageAmount: {$this->remainingMortgageAmount} termAmount: {$calculation->termAmount} : interestAmount {$calculation->interestAmount} : principalAmount: {$calculation->principalAmount} : balanceAmount: {$calculation->balanceAmount}\n";
        }
    }

    /**
     * Remove mortgage data from the data history for a range of years.
     *
     * Cleans up existing mortgage data to prevent conflicts when recalculating
     * amortization schedules (e.g., when extra downpayments change the schedule).
     *
     * @param  int  $fromYear  Starting year to remove mortgage data
     */
    public function removeMortgageFrom(int $fromYear): void
    {
        $toYear = $fromYear + 80; // Remove up to 80 years ahead

        for ($year = $fromYear; $year <= $toYear; $year++) {
            unset($this->dataH[$this->assettname][$year]['mortgage']);
        }
    }

    /**
     * Add data to the data history for a specific year and type.
     *
     * @param  int  $year  Year to add data for
     * @param  string  $type  Type of data (e.g., 'mortgage', 'income', 'expense')
     * @param  mixed  $row  Data to add
     */
    public function add(int $year, string $type, mixed $row): void
    {
        $this->dataH[$this->assettname][$year][$type] = $row;
    }

    /**
     * Get the complete data history with calculated mortgage data.
     *
     * @return array<string, mixed> Data history array with mortgage calculations
     */
    public function get(): array
    {
        return $this->dataH;
    }
}
