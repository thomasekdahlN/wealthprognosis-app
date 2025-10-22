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

use App\Services\Tax\TaxConfigRepository;

/**
 * Class TaxProperty
 *
 * Handles property tax calculations for real estate assets.
 * Property tax is calculated based on municipality-specific rates and taxable percentages.
 */
class TaxProperty
{
    /**
     * Create a new TaxProperty calculator.
     *
     * @param  TaxConfigRepository  $taxconfig  Repository for accessing tax configuration data
     */
    public function __construct(public TaxConfigRepository $taxconfig) {}

    /**
     * Calculates the property tax based on the given parameters.
     *
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $taxGroup  The tax group for the calculation.
     * @param  string  $taxProperty  The property type for the calculation.
     * @param  float  $amount  The amount of property for the calculation.
     * @return array{0: float, 1: float, 2: float, 3: float, 4: string}
     */
    public function calculatePropertyTax(int $year, string $taxGroup, string $taxProperty, float $amount): array
    {
        $taxablePropertyAmount = 0.0;
        $taxPropertyAmount = 0.0;
        $explanation = '';

        // Get the taxable property percent for the given tax group, property type and year
        $taxablePropertyPercent = $this->taxconfig->getPropertyTaxable($taxGroup, $taxProperty, $year);

        // Get the property tax percent for the given tax group, property type and year
        $taxPropertyPercent = $this->taxconfig->getPropertyTax($taxGroup, $taxProperty, $year);

        // Get the standard deduction for the given tax group, property type and year
        $taxPropertyDeductionAmount = $this->taxconfig->getPropertyTaxStandardDeduction($taxGroup, $taxProperty, $year);

        // Calculate the taxable property amount after deduction
        $taxablePropertyAmount = ($amount * $taxablePropertyPercent) - $taxPropertyDeductionAmount;

        // Calculate the tax property amount and provide explanation based on the taxable property amount and tax property percent
        if ($taxablePropertyAmount > 0 && $taxPropertyPercent > 0) {
            $taxPropertyAmount = round($taxablePropertyAmount * $taxPropertyPercent);
            $explanation = "Property tax $taxPropertyPercent% of $taxablePropertyAmount.";
        } else {
            $taxablePropertyAmount = 0.0; // Taxable property amount can not be zero
            $taxablePropertyPercent = 0.0;
            $explanation = 'No property tax. ';
        }

        return [$taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation];
    }
}
