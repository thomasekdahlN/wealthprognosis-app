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
use App\Support\ValueObjects\PropertyTaxResult;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxPropertyService
 *
 * Handles property tax calculations for real estate assets.
 * Property tax is calculated based on municipality-specific rates and taxable percentages.
 */
class TaxPropertyService
{
    protected string $country;

    /**
     * Shared TaxConfigPropertyRepository instance.
     */
    private \App\Services\Tax\TaxConfigPropertyRepository $taxPropertyConfig;

    public function __construct(
        string $country = 'no',
        private HelperService $helperService = new HelperService
    ) {
        $this->country = $country;

        // Use the singleton instance from the service container
        $this->taxPropertyConfig = app(\App\Services\Tax\TaxConfigPropertyRepository::class);
    }

    /**
     * Calculates the property tax based on the given parameters.
     *
     * Property tax (eiendomsskatt) is a municipal tax on real estate.
     * Calculation steps (example for Ringerike 2025):
     * 1. Market value: 3,000,000 kr
     * 2. Reduction (70% of market value): 2,100,000 kr (taxable portion)
     * 3. Deduction (bunnfradrag): -400,000 kr
     * 4. Tax base (skattegrunnlag): 1,700,000 kr
     * 5. Tax rate (skattesats): × 2.4‰ (× 0.0024)
     * 6. Property tax: 4,080 kr
     *
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $taxGroup  The tax group for the calculation ('private' or 'company').
     * @param  string  $taxPropertyArea  The municipality/property code for the calculation.
     * @param  float  $amount  The property market value for the calculation.
     */
    public function calculatePropertyTax(int $year, string $taxGroup, string $taxPropertyArea, float $amount): PropertyTaxResult
    {
        Log::info('PropertyTax METHOD ENTRY', [
            'year' => $year,
            'taxGroup' => $taxGroup,
            'taxPropertyArea' => $taxPropertyArea,
            'amount' => $amount,
        ]);

        $taxPropertyAmount = 0.0;
        $taxablePropertyAmount = 0.0;
        $explanation = '';

        // Get the property tax configuration (rate, deduction, and taxable percent) in one call
        $config = $this->taxPropertyConfig->getPropertyTaxConfig($taxGroup, $taxPropertyArea, $year);
        $taxPropertyRate = $config->taxRate;
        $taxPropertyPercent = $taxPropertyRate * 100; // Rate to percent (e.g., 0.0024 -> 0.24%)
        $taxPropertyDeductionAmount = $config->deductionAmount;
        $taxablePropertyPercent = $config->taxablePercent; // e.g., 70.00 for 70%
        $taxablePropertyRate = $this->helperService->percentToRate($taxablePropertyPercent);

        // Step 1: Apply the deduction (bunnfradrag) to the reduced value
        $taxablePropertyAmount = max(0, ($amount * $taxablePropertyRate) - $taxPropertyDeductionAmount);

        // Step 3: Calculate the property tax amount
        if ($taxablePropertyAmount > 0 && $taxPropertyRate > 0) {
            $taxPropertyAmount = round($taxablePropertyAmount * $taxPropertyRate);
            $explanation = sprintf(
                'Property tax: Market value %.0f kr × %.0f%% = %.0f kr (taxable), minus deduction %.0f kr × %.4f = %.0f kr',
                $amount,
                $taxablePropertyPercent,
                $taxablePropertyAmount,
                $taxPropertyDeductionAmount,
                $taxPropertyRate,
                $taxPropertyAmount
            );
        } else {
            $explanation = 'No property tax (rate is 0 or amount below deduction).';
        }

        $result = new PropertyTaxResult(
            taxPropertyArea: $taxPropertyArea,
            taxablePropertyAmount: $taxablePropertyAmount,
            taxablePropertyPercent: $taxablePropertyPercent,
            taxablePropertyRate: $taxablePropertyRate,
            taxPropertyDeductionAmount: $taxPropertyDeductionAmount,
            taxPropertyAmount: $taxPropertyAmount,
            taxPropertyPercent: $taxPropertyPercent,
            taxPropertyRate: $taxPropertyRate,
            explanation: $explanation
        );

        Log::debug('PropertyTaxResult', ['result' => (array) $result]);

        return $result;
    }
}
