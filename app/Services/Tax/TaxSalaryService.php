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

namespace App\Services\Tax;

use App\Services\Utilities\HelperService;
use App\Support\Contracts\TaxCalculatorInterface;
use App\Support\ValueObjects\BracketTaxResult;
use App\Support\ValueObjects\SalaryTaxResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxSalaryService
 *
 * Handles salary and pension tax calculations including:
 * - Common tax (fellesskatt)
 * - Bracket tax (trinnskatt)
 * - Social security tax (trygdeavgift)
 * - Standard deductions (minstefradrag)
 */
class TaxSalaryService implements TaxCalculatorInterface
{
    /**
     * Create a new TaxSalaryService service.
     *
     * @param  string  $country  Country code for tax calculations (default: 'no')
     * @param  TaxConfigRepository|null  $taxConfigRepo  Optional repository instance for dependency injection
     */
    public function __construct(
        private string $country = 'no',
        private ?TaxConfigRepository $taxConfigRepo = null,
        private HelperService $helperService = new HelperService
    ) {
        $this->country = strtolower($country) ?: 'no';
        $this->taxConfigRepo = $taxConfigRepo ?? app(TaxConfigRepository::class);
    }

    /**
     * Get the country code this calculator is configured for.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Calculate total salary tax including all components.
     *
     * Calculates common tax, bracket tax, and social security tax,
     * then returns the total tax amount and effective tax rate.
     *
     * @param  bool  $debug  Whether to output debug information
     * @param  int  $year  The tax year
     * @param  int  $amount  The salary/pension amount
     * @param  string  $taxType  The tax type ('salary' or 'pension')
     */
    public function calculatesalarytax(bool $debug, int $year, int $amount, string $taxType = 'salary'): SalaryTaxResult
    {
        $explanation = '';
        $commonTaxAmount = 0; // Fellesskatt
        $bracketTaxAmount = 0; // Trinnskatt
        $socialSecurityTaxAmount = 0; // Trygdeavgift
        $totalTaxAmount = 0; // Utregnet hva skatten faktisk er basert på de faktiske skattebeløpene.
        $taxAveragePercent = 0; // Utregnet hva skatten faktisak er i prosent
        $taxAverageRate = 0; // Utregnet hva skatten faktisak er i rate

        // Get tax rates based on tax type (salary or pension)
        if ($taxType === 'pension') {
            $commonTaxRate = $this->taxConfigRepo->getPensionTaxCommonRate($year);
            $commonTaxDeductionAmount = $this->commonDeduction($year, $amount, 'pension');
            $socialSecurityTaxRate = $this->taxConfigRepo->getPensionTaxSocialSecurityRate($year);
        } else {
            $commonTaxRate = $this->taxConfigRepo->getSalaryTaxCommonRate($year);
            $commonTaxDeductionAmount = $this->commonDeduction($year, $amount, 'salary');
            $socialSecurityTaxRate = $this->taxConfigRepo->getSalaryTaxSocialSecurityRate($year);
        }

        // Debug logging
        Log::debug('TaxSalaryService rates', [
            'country' => $this->country,
            'year' => $year,
            'amount' => $amount,
            'common_rate' => $commonTaxRate,
            'social_security_rate' => $socialSecurityTaxRate,
            'common_deduction' => $commonTaxDeductionAmount,
        ]);

        $socialSecurityTaxableAmount = $amount; // Man betaler trygdeavgift av hele lønnen uten fradrag
        if ($socialSecurityTaxableAmount > 0) {
            $socialSecurityTaxAmount = round($socialSecurityTaxableAmount * $socialSecurityTaxRate);
        }

        $commonTaxableAmount = $amount - $socialSecurityTaxAmount - $commonTaxDeductionAmount; // Man betaler fellesskatt av lønnen etter at trygdeavgidt og minstefradraget er trukket fra
        $commonTaxAmount = round($commonTaxableAmount * $commonTaxRate);

        $bracketTaxResult = $this->calculateBracketTax($debug, $year, $amount, $taxType); // Man betaler trinnskatt av hele lønnen uten fradrag
        $bracketTaxAmount = $bracketTaxResult->taxAmount;
        $explanation = $bracketTaxResult->explanation;

        $explanation = ' Fellesskatt: '.$commonTaxRate * 100 ."% gir $commonTaxAmount skatt, Trygdeavgift ".$socialSecurityTaxRate * 100 ."% gir $socialSecurityTaxAmount skatt ".$explanation;

        $totalTaxAmount = $bracketTaxAmount + $commonTaxAmount + $socialSecurityTaxAmount;

        if ($amount > 0) {
            $taxAverageRate = round(($totalTaxAmount / $amount), 2); // We calculate a total percentage using the amounts
            $taxAveragePercent = $taxAverageRate * 100;
        }

        $result = new SalaryTaxResult(
            taxAmount: $totalTaxAmount,
            taxAveragePercent: $taxAveragePercent,
            taxAverageRate: $taxAverageRate,
            explanation: $explanation
        );

        Log::debug('SalaryTaxResult', ['result' => (array) $result]);

        return $result;
    }

    /**
     * Calculate bracket tax (trinnskatt) based on progressive tax brackets.
     *
     * Applies different tax rates to income ranges defined in the tax configuration.
     * Each bracket has a limit and a rate, with higher income taxed at higher rates.
     *
     * @param  bool  $debug  Whether to output debug information
     * @param  int  $year  The tax year
     * @param  int  $amount  The salary/pension amount
     * @param  string  $taxType  The tax type ('salary' or 'pension')
     */
    public function calculateBracketTax(bool $debug, int $year, int $amount, string $taxType = 'salary'): BracketTaxResult
    {
        // Get bracket configuration based on tax type
        if ($taxType === 'pension') {
            $brackets = $this->taxConfigRepo->getPensionTaxBracketConfig($year);
        } else {
            $brackets = $this->taxConfigRepo->getSalaryTaxBracketConfig($year);
        }
        $totalTaxAmount = 0;
        $prevLimitAmount = 0;
        $explanationParts = [];

        foreach ($brackets as $index => $bracket) {
            $bracketPercent = $bracket['percent'] ?? 0;
            $bracketRate = $this->helperService->percentToRate($bracketPercent); // Convert percent to decimal rate (e.g., 1.7% -> 0.017)
            $bracketLimit = $bracket['limit'] ?? null;

            // Determine taxable amount for this bracket
            if ($bracketLimit !== null && $amount > $bracketLimit) {
                // Income exceeds this bracket's limit - tax the full bracket range
                $taxableAmount = $bracketLimit - $prevLimitAmount;
                $taxAmount = round($taxableAmount * $bracketRate);
                $totalTaxAmount += $taxAmount;

                $explanationParts[] = "Bracket$index ({$bracketLimit}){$bracketPercent}%={$taxAmount}";

                Log::debug('Bracket tax - within limit', [
                    'bracket' => $index,
                    'bracketLimit' => $bracketLimit,
                    'amount' => $amount,
                    'taxableAmount' => $taxableAmount,
                    'bracketPercent' => $bracketPercent,
                    'taxAmount' => $taxAmount,
                ]);

                $prevLimitAmount = $bracketLimit;
            } elseif ($bracketLimit !== null) {
                // Income is below this bracket's limit - tax remaining amount and stop
                $taxableAmount = $amount - $prevLimitAmount;
                $taxAmount = round($taxableAmount * $bracketRate);
                $totalTaxAmount += $taxAmount;

                $explanationParts[] = "Bracket$index ({$amount}<{$bracketLimit}){$bracketPercent}%={$taxAmount}";

                Log::debug('Bracket tax - below limit', [
                    'bracket' => $index,
                    'amount' => $amount,
                    'limit' => $bracketLimit,
                    'taxable_amount' => $taxableAmount,
                    'percent' => $bracketPercent,
                    'rate' => $bracketRate,
                    'tax' => $taxAmount,
                ]);

                break;
            } else {
                // No limit - this is the final bracket for all remaining income
                $taxableAmount = $amount - $prevLimitAmount;
                $taxAmount = round($taxableAmount * $bracketRate);
                $totalTaxAmount += $taxAmount;

                $explanationParts[] = "Bracket$index (>{$prevLimitAmount}){$bracketPercent}%={$taxAmount}";

                Log::debug('Bracket tax - above all limits', [
                    'bracket' => $index,
                    'prev_limit' => $prevLimitAmount,
                    'taxable_amount' => $taxableAmount,
                    'percent' => $bracketPercent,
                    'rate' => $bracketRate,
                    'tax' => $taxAmount,
                ]);

                break;
            }
        }

        // Calculate average tax rate and percent
        $averageRate = $amount > 0 ? $totalTaxAmount / $amount : 0;
        $averagePercent = $averageRate * 100;

        $explanation = ' Trinnskatt: '.$totalTaxAmount.' snitt '.round($averagePercent, 2).'%, '.implode(', ', $explanationParts);

        $result = new BracketTaxResult(
            taxAmount: $totalTaxAmount,
            taxAveragePercent: $averagePercent,
            taxAverageRate: $averageRate,
            explanation: $explanation
        );

        Log::debug('BracketTaxResult', ['result' => (array) $result]);

        return $result;
    }

    /**
     * Calculate the standard deduction (minstefradrag) for salary/pension income.
     *
     * The deduction is calculated as a percentage of income, with minimum and maximum limits.
     * This deduction reduces the taxable base for common tax calculations.
     *
     * @param  int  $year  The tax year
     * @param  int  $amount  The salary/pension amount
     * @param  string  $taxType  The tax type ('salary' or 'pension')
     * @return float The deduction amount
     */
    public function commonDeduction(int $year, int $amount, string $taxType = 'salary'): float
    {
        // Get deduction configuration based on tax type
        if ($taxType === 'pension') {
            $deductionConfig = $this->taxConfigRepo->getPensionTaxDeductionConfig($year);
        } else {
            $deductionConfig = $this->taxConfigRepo->getSalaryTaxDeductionConfig($year);
        }

        $minAmount = Arr::get($deductionConfig, 'min');
        $maxAmount = Arr::get($deductionConfig, 'max');
        $percent = Arr::get($deductionConfig, 'percent');
        $rate = $this->helperService->percentToRate($percent);

        $deduction = $amount * $rate;
        if ($deduction > $maxAmount) {
            $deduction = $maxAmount;
        }
        if ($deduction < $minAmount) {
            $deduction = $minAmount;
        }

        Log::debug('commonDeduction', [
            'taxType' => $taxType,
            'amount' => $amount,
            'min' => $minAmount,
            'max' => $maxAmount,
            'percent' => $percent,
            'deduction' => $deduction,
        ]);

        return $deduction;
    }
}
