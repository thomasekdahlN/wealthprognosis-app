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

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TaxIncome
 *
 * Handles income tax calculations for various asset and income types.
 * Supports different tax treatments for salary, pension, rental income,
 * investment income, and other income sources.
 *
 * Uses TaxConfigRepository for database-backed tax configuration lookups.
 */
class TaxIncome extends Model
{
    use HasFactory;

    /**
     * Country code for tax lookups (e.g., 'no').
     */
    private string $country = 'no';

    /**
     * Shared TaxConfigRepository instance.
     */
    private \App\Services\Tax\TaxConfigRepository $taxConfigRepo;

    /**
     * Keep constructor signature but switch to DB-backed loading.
     *
     * @param  string  $config  Path-like identifier used only to infer country code (e.g., 'no/no-tax-2025').
     */
    public function __construct(string $country = 'no')
    {

        $this->country = $country;

        $this->taxsalary = new \App\Models\Core\TaxSalary($this->country);

        // Use the singleton instance from the service container
        $this->taxConfigRepo = app(\App\Services\Tax\TaxConfigRepository::class);
    }

    /**
     * Calculates the income tax based on the tax group, tax type, year, income, expense, and interest amount.
     *
     * @param  bool  $debug  Indicates whether to print debug information.
     * @param  string  $taxGroup  The tax group to which the income belongs.
     * @param  string  $taxType  The type of tax to be calculated.
     * @param  int  $year  The year for which the tax is to be calculated.
     * @param  float|null  $income  The income for the tax calculation.
     * @param  float|null  $expence  The expense for the tax calculation.
     * @param  float|null  $interestAmount  The interest amount for the tax calculation.
     * @return array{0: float|int, 1: float|int, 2: string} Returns [amount, percent, explanation].
     */
    public function taxCalculationIncome(bool $debug, string $taxGroup, string $taxType, int $year, ?float $income, ?float $expence, ?float $interestAmount)
    {
        // Initialize explanation and income tax percent
        $debug = true;
        $explanation = '';
        $incomeTaxRate = $this->taxConfigRepo->getTaxIncomeRate($taxType, $year);
        $incomeTaxAmount = 0;

        // Print debug information if debug is true
        if ($debug) {
            echo "\n\n\n********** taxtype: $taxGroup.$taxType.$year: income: $income, expence: $expence, incomeTaxPercent: $incomeTaxRate\n";
        }

        // Calculate income tax amount based on tax type
        switch ($taxType) {
            // For 'salary' and 'pension' tax types, calculate salary tax
            case 'salary':
                [$incomeTaxAmount, $incomeTaxRate, $explanation] = $this->taxsalary->calculatesalarytax(true, $year, (int) $income);
                break;

            case 'pension':
                [$incomeTaxAmount, $incomeTaxRate, $explanation] = $this->taxsalary->calculatesalarytax(true, $year, (int) $income);
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

        // Print debug information if debug is true
        if ($debug) {
            echo "$taxType.$year: income: $income, incomeTaxAmount: $incomeTaxAmount, incomeTaxPercent: $incomeTaxRate, explanation:$explanation\n";
        }

        // Return the calculated income tax amount, income tax percent, and explanation
        return [$incomeTaxAmount, $incomeTaxRate, $explanation];
    }
}
