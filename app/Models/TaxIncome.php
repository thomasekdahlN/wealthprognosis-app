<?php
/* Copyright (C) 2024 Thomas Ekdahl
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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TaxIncome extends Model
{
    use HasFactory;

    public $taxH = [];

    //Will be rewritten to support yearly tax differences, just faking for now.
    //Should probably be a deep nested json structure.
    public function __construct($config, $startYear, $stopYear)
    {

        $file = config_path("tax/$config.json");
        $configH = File::json($file);
        echo "Leser: '$file'\n";

        foreach ($configH as $type => $typeH) {
            $this->taxH[$type] = $typeH;
        }

        $this->taxsalary = new \App\Models\TaxSalary();
    }

    public function getTaxIncome($taxGroup, $taxType, $year)
    {

        return Arr::get($this->taxH, "$taxType.income", 0) / 100;
    }

    public function getTaxStandardDeduction($taxGroup, $taxType, $year)
    {
        return Arr::get($this->taxH, "$taxType.standardDeduction", 0);
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
     * @return array Returns an array containing the calculated income tax amount, income tax percent, and an explanation.
     */
    public function taxCalculationIncome(bool $debug, string $taxGroup, string $taxType, int $year, ?float $income, ?float $expence, ?float $interestAmount)
    {
        // Initialize explanation and income tax percent
        $explanation = '';
        $incomeTaxPercent = $this->getTaxIncome($taxGroup, $taxType, $year); //FIX
        $incomeTaxAmount = 0;

        // Print debug information if debug is true
        if ($debug) {
            echo "\ntaxtype: $taxGroup.$taxType.$year: income: $income, expence: $expence, incomeTaxPercent: $incomeTaxPercent\n";
        }

        // Calculate income tax amount based on tax type
        switch ($taxType) {
            // For 'salary' and 'pension' tax types, calculate salary tax
            case 'salary':
            case 'pension':
                [$incomeTaxAmount, $incomeTaxPercent, $explanation] = $this->taxsalary->calculatesalarytax(false, $year, $income);
                break;

                // For 'income' tax type, calculate income tax after transfer to this category
            case 'income':
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
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
                $incomeTaxAmount = round($income - $expence) * $incomeTaxPercent;
                break;

                // For 'cabin' tax type, calculate Airbnb tax after deducting standard deduction
            case 'cabin':
                $standardDeduction = $this->getTaxStandardDeduction($taxGroup, 'airbnb', $year);
                if ($income - $standardDeduction > 0) {
                    $incomeTaxPercent = $this->getTaxIncome($taxGroup, 'airbnb', $year);
                    $incomeTaxAmount = round(($income - $standardDeduction) * $incomeTaxPercent);
                }
                break;

                // For 'bank', 'cash', 'equitybond' tax types, calculate tax on interest
            case 'bank':
            case 'cash':
            case 'equitybond':
                $incomeTaxAmount = round($interestAmount * $incomeTaxPercent);
                if ($incomeTaxAmount != 0) {
                    $explanation = $incomeTaxPercent * 100 ."% tax on interest $interestAmount=$incomeTaxAmount";
                }
                break;
            case 'none':
                $incomeTaxAmount = 0;
                $incomeTaxPercent = 0;
                $explanation = "Tax type set to none, calculating without tax";
                break;
                // For other tax types, calculate income tax after deducting expenses
            default:
                $incomeTaxAmount = ($income - $expence) * $incomeTaxPercent;
                $explanation = "No tax rule found for: $taxType";
                break;
        }

        // Print debug information if debug is true
        if ($debug) {
            echo "$taxType.$year: income: $income, incomeTaxAmount: $incomeTaxAmount, incomeTaxPercent: $incomeTaxPercent, explanation:$explanation\n";
        }

        // Return the calculated income tax amount, income tax percent, and explanation
        return [$incomeTaxAmount, $incomeTaxPercent, $explanation];
    }
}
