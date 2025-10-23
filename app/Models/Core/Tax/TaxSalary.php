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

namespace App\Models\Core\Tax;

use App\Models\Core\Contracts\TaxCalculatorInterface;
use App\Models\Core\ValueObjects\TaxCalculationResult;
use App\Services\Tax\TaxConfigRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxSalary
 *
 * Handles salary and pension tax calculations including:
 * - Common tax (fellesskatt)
 * - Bracket tax (trinnskatt)
 * - Social security tax (trygdeavgift)
 * - Standard deductions (minstefradrag)
 */
class TaxSalary implements TaxCalculatorInterface
{
    /**
     * Country code for tax lookups.
     */
    private string $country;

    /**
     * Shared TaxConfigRepository instance.
     */
    private TaxConfigRepository $taxConfigRepo;

    /**
     * Create a new TaxSalary service.
     *
     * @param  string  $country  Country code for tax calculations (default: 'no')
     * @param  TaxConfigRepository|null  $taxConfigRepo  Optional repository instance for dependency injection
     */
    public function __construct(string $country = 'no', ?TaxConfigRepository $taxConfigRepo = null)
    {
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
     * @return array{0: float, 1: float, 2: string}|TaxCalculationResult Returns array for backward compatibility
     */
    public function calculatesalarytax(bool $debug, int $year, int $amount): array
    {
        $explanation = '';
        $commonTaxAmount = 0; // Fellesskatt
        $bracketTaxAmount = 0; // Trinnskatt
        $socialSecurityTaxAmount = 0; // Trygdeavgift
        $totalTaxAmount = 0; // Utregnet hva skatten faktisk er basert på de faktiske skattebeløpene.

        $commonTaxRate = $this->taxConfigRepo->getSalaryTaxCommonRate($year);
        $commonTaxDeductionAmount = $this->commonDeduction($year, $amount);

        $socialSecurityTaxRate = $this->taxConfigRepo->getSalaryTaxSocialSecurityRate($year);
        $totalTaxPercent = 0; // Utregnet hva skatten faktisak er i kroner basert på de faktiske skattebeløpene.

        $socialSecurityTaxableAmount = $amount; // Man betaler trygdeavgift av hele lønnen uten fradrag
        if ($socialSecurityTaxableAmount > 0) {
            $socialSecurityTaxAmount = round($socialSecurityTaxableAmount * $socialSecurityTaxRate);
        }

        $commonTaxableAmount = $amount - $socialSecurityTaxAmount - $commonTaxDeductionAmount; // Man betaler fellesskatt av lønnen etter at trygdeavgidt og minstefradraget er trukket fra
        $commonTaxAmount = round($commonTaxableAmount * $commonTaxRate);

        [$bracketTaxAmount, $bracketTaxPercent, $explanation] = $this->calculateBracketTax($debug, $year, $amount); // Man betaler trinnskatt av hele lønnen uten fradrag

        $explanation = ' Fellesskatt: '.$commonTaxRate * 100 ."% gir $commonTaxAmount skatt, Trygdeavgift ".$socialSecurityTaxRate * 100 ."% gir $socialSecurityTaxAmount skatt ".$explanation;

        $totalTaxAmount = $bracketTaxAmount + $commonTaxAmount + $socialSecurityTaxAmount;

        if ($amount > 0) {
            $totalTaxPercent = round(($totalTaxAmount / $amount), 2); // We calculate a total percentage using the amounts
        }

        // Log debug information if debug is true
        if ($debug) {
            $debugData = [
                'year' => $year,
                'amount' => $amount,
                'common_tax_deduction_amount' => $commonTaxDeductionAmount,
                'common_taxable_amount' => $commonTaxableAmount,
                'total_tax_amount' => $totalTaxAmount,
                'total_tax_percent' => $totalTaxPercent * 100,
                'explanation' => $explanation,
            ];
            Log::debug('Salary tax calculation', $debugData);

            // Also output to console for CLI commands
            if (app()->runningInConsole()) {
                echo "Salary tax: year=$year, amount=$amount, tax=$totalTaxAmount (".($totalTaxPercent * 100)."%), $explanation\n";
            }
        }

        // Return array for backward compatibility
        return [$totalTaxAmount, $totalTaxPercent, $explanation];
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
     * @return array{0: float, 1: float, 2: string} Returns array for backward compatibility
     */
    public function calculateBracketTax(bool $debug, int $year, int $amount): array
    {
        $count = 0;
        $explanation = '';
        $brackets = $this->taxConfigRepo->getSalaryTaxBracketConfig($year);

        $bracketTaxAmount = 0;
        $bracketTotalTaxAmount = 0;
        $bracketTaxPercent = 0;
        $bracketTotalTaxPercent = 0;

        $prevLimitAmount = 0;
        foreach ($brackets as $bracket) {

            $bracketTaxPercent = ($bracket['percent'] ?? 0) / 100;

            if (isset($bracket['limit']) && $amount > $bracket['limit']) {
                $bracketTaxableAmount = $bracket['limit'] - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;

                $explanation .= " Bracket$count ($bracket[limit])$bracket[percent]%=$bracketTaxAmount,";

                if ($debug) {
                    $debugData = [
                        'bracket' => $count,
                        'limit' => $bracket['limit'],
                        'amount' => $amount,
                        'taxable_amount' => $bracketTaxableAmount,
                        'percent' => $bracket['percent'],
                        'tax' => $bracketTaxAmount,
                    ];
                    Log::debug('Bracket tax calculation - within limit', $debugData);

                    if (app()->runningInConsole()) {
                        echo "  Bracket $count: limit={$bracket['limit']}, taxable=$bracketTaxableAmount, rate={$bracket['percent']}%, tax=$bracketTaxAmount\n";
                    }
                }

            } elseif (isset($bracket['limit'])) {
                // Amount is lower than limit, we are at the end and calculate the rest of the amount.
                $bracketTaxableAmount = $amount - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;
                $explanation .= " Bracket$count ($amount<)".$bracket['limit'].")$bracket[percent]%=$bracketTaxAmount";

                if ($debug) {
                    $debugData = [
                        'bracket' => $count,
                        'amount' => $amount,
                        'limit' => $bracket['limit'],
                        'taxable_amount' => $bracketTaxableAmount,
                        'percent' => $bracket['percent'],
                        'tax' => $bracketTaxAmount,
                    ];
                    Log::debug('Bracket tax calculation - below limit', $debugData);

                    if (app()->runningInConsole()) {
                        echo "  Bracket $count: amount=$amount < limit={$bracket['limit']}, taxable=$bracketTaxableAmount, rate={$bracket['percent']}%, tax=$bracketTaxAmount\n";
                    }
                }

                break;
            } else {
                // Not set, then all tax after this is on bigger than logic, we are at the end of the calculation
                $bracketTaxableAmount = $amount - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;
                $explanation .= " Bracket$count (>$prevLimitAmount)$bracket[percent]%=$bracketTaxAmount";

                if ($debug) {
                    $debugData = [
                        'bracket' => $count,
                        'prev_limit' => $prevLimitAmount,
                        'taxable_amount' => $bracketTaxableAmount,
                        'percent' => $bracket['percent'],
                        'tax' => $bracketTaxAmount,
                    ];
                    Log::debug('Bracket tax calculation - above all limits', $debugData);

                    if (app()->runningInConsole()) {
                        echo "  Bracket $count: amount > $prevLimitAmount, taxable=$bracketTaxableAmount, rate={$bracket['percent']}%, tax=$bracketTaxAmount\n";
                    }
                }

                break;
            }
            $prevLimitAmount = $bracket['limit'] ?? $prevLimitAmount;
            $count++;
        }

        if ($amount > 0) {
            $bracketTotalTaxPercent = round(($bracketTaxAmount / $amount), 2); // We calculate a total percentage using the amounts
        }

        $explanation = " Trinnskatt:$bracketTotalTaxAmount snitt ".$bracketTotalTaxPercent * 100 .'%, '.$explanation;

        // Return array for backward compatibility
        return [$bracketTotalTaxAmount, $bracketTotalTaxPercent, $explanation];
    }

    /**
     * Calculate the standard deduction (minstefradrag) for salary income.
     *
     * The deduction is calculated as a percentage of income, with minimum and maximum limits.
     * This deduction reduces the taxable base for common tax calculations.
     *
     * @param  int  $year  The tax year
     * @param  int  $amount  The salary/pension amount
     * @return float The deduction amount
     */
    public function commonDeduction(int $year, int $amount): float
    {
        $deductionConfig = $this->taxConfigRepo->getSalaryTaxDeductionConfig($year);
        $minAmount = Arr::get($deductionConfig, 'deduction.min');
        $maxAmount = Arr::get($deductionConfig, 'deduction.max');
        $percent = Arr::get($deductionConfig, 'deduction.percent');

        $deduction = $amount * $percent;
        if ($deduction > $maxAmount) {
            $deduction = $maxAmount;
        }
        if ($deduction < $minAmount) {
            $deduction = $minAmount;
        }

        Log::debug('Common deduction calculation', [
            'amount' => $amount,
            'min' => $minAmount,
            'max' => $maxAmount,
            'percent' => $percent,
            'deduction' => $deduction,
        ]);

        return $deduction;
    }
}
