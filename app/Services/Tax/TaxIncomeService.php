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

use App\Support\Contracts\TaxCalculatorInterface;
use App\Support\ValueObjects\IncomeTaxResult;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxIncomeService
 *
 * Handles income tax calculations for various asset and income types.
 * Supports different tax treatments for salary, pension, rental income,
 * investment income, and other income sources.
 *
 * Uses TaxConfigRepository for database-backed tax configuration lookups.
 */
class TaxIncomeService implements TaxCalculatorInterface
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
     * TaxSalaryService instance for salary/pension calculations.
     */
    private TaxSalaryService $taxsalary;

    /**
     * Create a new TaxIncomeService service.
     *
     * @param  string  $country  Country code for tax calculations (default: 'no')
     * @param  TaxConfigRepository|null  $taxConfigRepo  Optional repository instance for dependency injection
     * @param  TaxSalaryService|null  $taxSalary  Optional TaxSalaryService instance for dependency injection
     */
    public function __construct(
        string $country = 'no',
        ?TaxConfigRepository $taxConfigRepo = null,
        ?TaxSalaryService $taxSalary = null
    ) {
        $this->country = strtolower($country) ?: 'no';
        $this->taxConfigRepo = $taxConfigRepo ?? app(TaxConfigRepository::class);
        $this->taxsalary = $taxSalary ?? new TaxSalaryService($this->country, $this->taxConfigRepo);
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
     */
    public function taxCalculationIncome(
        bool $debug,
        string $taxGroup,
        ?string $taxType,
        int $year,
        ?float $income,
        ?float $expence,
        ?float $interestAmount
    ): IncomeTaxResult {
        // Skip tax calculation if tax_type is null
        if ($taxType === null) {
            return new IncomeTaxResult(
                taxAmount: 0,
                taxRate: 0,
                explanation: 'Tax type is null, no tax calculation performed'
            );
        }

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
            // For 'salary' tax type, calculate salary tax
            case 'salary':
                $salaryTaxResult = $this->taxsalary->calculatesalarytax($debug, $year, (int) $income, 'salary');
                $incomeTaxAmount = $salaryTaxResult->taxAmount;
                $incomeTaxRate = $salaryTaxResult->taxAveragePercent;
                $explanation = $salaryTaxResult->explanation;
                break;

                // For 'pension' tax type, calculate pension tax
            case 'pension':
                $salaryTaxResult = $this->taxsalary->calculatesalarytax($debug, $year, (int) $income, 'pension');
                $incomeTaxAmount = $salaryTaxResult->taxAmount;
                $incomeTaxRate = $salaryTaxResult->taxAveragePercent;
                $explanation = $salaryTaxResult->explanation;
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

        $result = new IncomeTaxResult(
            taxAmount: $incomeTaxAmount,
            taxRate: $incomeTaxRate,
            explanation: $explanation
        );

        Log::debug('Income tax calculation output', ['taxType' => $taxType, 'year' => $year, 'income' => $income, 'result' => (array) $result]);

        return $result;
    }
}
