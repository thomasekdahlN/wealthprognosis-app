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
use Illuminate\Support\Facades\Log;

/**
 * Class TaxIncome
 *
 * Handles income tax calculations for various asset and income types.
 * Supports different tax treatments for salary, pension, rental income,
 * investment income, and other income sources.
 *
 * Uses TaxConfigRepository for database-backed tax configuration lookups.
 */
class TaxIncome implements TaxCalculatorInterface
{
    /**
     * Country code for tax lookups (e.g., 'no').
     */
    private string $country;

    /**
     * Shared TaxConfigRepository instance.
     */
    private TaxConfigRepository $taxConfigRepo;

    /**
     * TaxSalary instance for salary/pension calculations.
     */
    private TaxSalary $taxsalary;

    /**
     * Create a new TaxIncome service.
     *
     * @param  string  $country  Country code for tax calculations (default: 'no')
     * @param  TaxConfigRepository|null  $taxConfigRepo  Optional repository instance for dependency injection
     * @param  TaxSalary|null  $taxSalary  Optional TaxSalary instance for dependency injection
     */
    public function __construct(
        string $country = 'no',
        ?TaxConfigRepository $taxConfigRepo = null,
        ?TaxSalary $taxSalary = null
    ) {
        $this->country = strtolower($country) ?: 'no';
        $this->taxConfigRepo = $taxConfigRepo ?? app(TaxConfigRepository::class);
        $this->taxsalary = $taxSalary ?? new TaxSalary($this->country, $this->taxConfigRepo);
    }

    /**
     * Get the country code this calculator is configured for.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Calculates the income tax based on the tax group, tax type, year, income, expense, and interest amount.
     *
     * @param  bool  $debug  Indicates whether to log debug information.
     * @param  string  $taxGroup  The tax group to which the income belongs.
     * @param  string  $taxType  The type of tax to be calculated.
     * @param  int  $year  The year for which the tax is to be calculated.
     * @param  float|null  $income  The income for the tax calculation.
     * @param  float|null  $expence  The expense for the tax calculation.
     * @param  float|null  $interestAmount  The interest amount for the tax calculation.
     * @return array{0: float, 1: float, 2: string} Returns array for backward compatibility
     */
    public function taxCalculationIncome(
        bool $debug,
        string $taxGroup,
        string $taxType,
        int $year,
        ?float $income,
        ?float $expence,
        ?float $interestAmount
    ): array {
        // Initialize explanation and income tax percent
        $explanation = '';
        $incomeTaxRate = $this->taxConfigRepo->getTaxIncomeRate($taxType, $year);
        $incomeTaxAmount = 0;

        // Log debug information if debug is true
        if ($debug) {
            Log::debug('Income tax calculation input', [
                'tax_group' => $taxGroup,
                'tax_type' => $taxType,
                'year' => $year,
                'income' => $income,
                'expence' => $expence,
                'income_tax_rate' => $incomeTaxRate,
            ]);
        }

        // Calculate income tax amount based on tax type
        switch ($taxType) {
            // For 'salary' and 'pension' tax types, calculate salary tax
            case 'salary':
                [$incomeTaxAmount, $incomeTaxRate, $explanation] = $this->taxsalary->calculatesalarytax($debug, $year, (int) $income);
                break;

            case 'pension':
                [$incomeTaxAmount, $incomeTaxRate, $explanation] = $this->taxsalary->calculatesalarytax($debug, $year, (int) $income);
                break;

                // For 'income' tax type, calculate income tax after transfer to this category
            case 'income':
                $incomeTaxAmount = round(($income - $expence) * $incomeTaxRate);
                break;

                // For 'house', 'rental', 'property', 'stock', 'equityfund', 'ask', 'otp', 'ips' tax types, calculate income tax after deducting expenses
            case 'house':
            case 'rental':
            case 'property':
            case 'stock':
            case 'equityfund':
            case 'ask':
            case 'otp':
            case 'ips':
                $incomeTaxAmount = round(($income - $expence) * $incomeTaxRate);
                break;

                // For 'cabin' tax type, calculate Airbnb tax after deducting standard deduction
            case 'cabin':
                $standardDeduction = $this->taxConfigRepo->getTaxStandardDeductionAmount('airbnb', $year);
                if (($income - $standardDeduction) > 0) {
                    $incomeTaxRate = $this->taxConfigRepo->getTaxIncomeRate('airbnb', $year);
                    $incomeTaxAmount = round(($income - $standardDeduction) * $incomeTaxRate);
                }
                break;

                // For 'bank', 'cash', 'equitybond' tax types, calculate tax on interest
            case 'bank':
            case 'cash':
            case 'equitybond':
                $incomeTaxAmount = round(((float) $interestAmount) * $incomeTaxRate);
                if ($incomeTaxAmount != 0) {
                    $explanation = ($incomeTaxRate * 100)."% tax on interest $interestAmount=$incomeTaxAmount";
                }
                break;
            case 'none':
                $incomeTaxAmount = 0;
                $incomeTaxRate = 0;
                $explanation = 'Tax type set to none, calculating without tax';
                break;
                // For other tax types, calculate income tax after deducting expenses
            default:
                $incomeTaxAmount = ($income - $expence) * $incomeTaxRate;
                $explanation = "No tax rule found for: $taxType";
                break;
        }

        // Log debug information if debug is true
        if ($debug) {
            Log::debug('Income tax calculation output', [
                'tax_type' => $taxType,
                'year' => $year,
                'income' => $income,
                'income_tax_amount' => $incomeTaxAmount,
                'income_tax_rate' => $incomeTaxRate,
                'explanation' => $explanation,
            ]);
        }

        // Return the calculated income tax amount, income tax percent, and explanation (array for backward compatibility)
        return [$incomeTaxAmount, $incomeTaxRate, $explanation];
    }
}

