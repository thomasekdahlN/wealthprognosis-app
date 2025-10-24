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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Class TaxFortuneService
 *
 * Handles fortune (wealth) tax calculations including:
 * - Fortune tax (formuesskatt)
 * - Property tax (eiendomsskatt)
 * - Bracket-based progressive taxation
 */
class TaxFortuneService implements TaxCalculatorInterface
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
     * Shared TaxConfigPropertyRepository instance.
     */
    private TaxConfigPropertyRepository $taxPropertyRepo;

    /**
     * Create a new TaxFortuneService service.
     *
     * @param  string  $country  Country code for tax calculations (default: 'no')
     * @param  TaxConfigRepository|null  $taxConfigRepo  Optional repository instance for dependency injection
     * @param  TaxConfigPropertyRepository|null  $taxPropertyRepo  Optional property repository instance for dependency injection
     */
    public function __construct(
        string $country = 'no',
        ?TaxConfigRepository $taxConfigRepo = null,
        ?TaxConfigPropertyRepository $taxPropertyRepo = null
    ) {
        $this->country = strtolower($country) ?: 'no';
        $this->taxConfigRepo = $taxConfigRepo ?? app(TaxConfigRepository::class);
        $this->taxPropertyRepo = $taxPropertyRepo ?? app(TaxConfigPropertyRepository::class);
    }

    /**
     * Get the country code this calculator is configured for.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Calculates the fortune tax and property tax based on the given parameters.
     *
     * @param  string  $taxGroup  The tax group for the calculation.
     * @param  string|null  $taxType  The tax type for the calculation.
     * @param  string|null  $taxProperty  The property type for the calculation. If null, property tax is not calculated.
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  int|null  $marketAmount  The market amount for the calculation. If null, it is considered as 0.
     * @param  int|null  $taxableInitialAmount  The taxable amount for the calculation. If null, it is considered as 0.
     * @param  int|null  $mortgageBalanceAmount  The mortgage balance amount.
     * @param  bool|null  $taxableAmountOverride  If true, the taxable amount is overridden. If null, it is considered as false.
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float, 6: float, 7: float, 8: string} Returns array for backward compatibility
     */
    public function taxCalculationFortune(
        string $taxGroup,
        ?string $taxType,
        ?string $taxProperty,
        int $year,
        ?int $marketAmount,
        ?int $taxableInitialAmount,
        ?int $mortgageBalanceAmount,
        ?bool $taxableAmountOverride = false
    ): array {
        $explanation = '';
        $explanation1 = '';
        $explanation2 = '';
        $taxableFortuneAmount = 0;

        // Property tax
        $taxPropertyPercent = 0;
        $taxablePropertyPercent = 0;
        $taxPropertyAmount = 0;
        $taxablePropertyAmount = 0;

        $taxableFortuneRate = $this->taxConfigRepo->getTaxFortuneTaxableRate($taxType, $year);

        if ($taxableAmountOverride) {
            // Fortune tax can be negative by the amount of taxableInitualAmount minus mortgage if the asset value had been zero. This is how it is calculated in the tax system.

            if ($taxableInitialAmount > 0) {
                $taxablePropertyAmount = $taxableInitialAmount;
            }
            $taxableFortuneAmount = $taxableInitialAmount;
            $taxableFortunePercent = 0; // If $fortuneTaxableAmount is set, we ignore the $fortuneTaxablePercent since that should be calculated from the market value and when $fortuneTaxableAmount is set, we do not releate tax to market value anymore.
            $explanation .= 'Override taxable. ';

            Log::debug('Fortune tax calculation - override', [
                'taxable_initial_amount' => $taxableInitialAmount,
                'mortgage_balance_amount' => $mortgageBalanceAmount,
            ]);
        } else {
            $taxablePropertyAmount = round($marketAmount);
            $taxableFortuneAmount = round($marketAmount * $taxableFortuneRate); // Calculate the amount from which the tax is calculated from the market value minus mortgage.

            Log::debug('Fortune tax calculation - normal', [
                'taxable_fortune_amount' => $taxableFortuneAmount,
                'taxable_fortune_rate' => $taxableFortuneRate,
            ]);

            $explanation .= 'Market taxable. ';
        }

        [$taxAmount, $taxPercent, $taxableFortuneAmount, $explanation1] = $this->calculatefortunetax(false, $year, $taxGroup, $taxableFortuneAmount, $mortgageBalanceAmount, false);

        if ($taxProperty) {
            $propertyTax = app(\App\Models\Core\TaxProperty::class);
            [$taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation2] = $propertyTax->calculatePropertyTax($year, $taxGroup, $taxProperty, (float) $taxablePropertyAmount);
        }
        $explanation .= $explanation2.$explanation1;

        Log::debug('Fortune tax calculation result', [
            'taxable_fortune_amount' => $taxableFortuneAmount,
            'taxable_fortune_rate' => $taxableFortuneRate,
            'tax_amount' => $taxAmount,
            'tax_percent' => $taxPercent,
            'taxable_property_amount' => $taxablePropertyAmount,
            'taxable_property_percent' => $taxablePropertyPercent,
            'tax_property_amount' => $taxPropertyAmount,
            'tax_property_percent' => $taxPropertyPercent,
        ]);

        // Return array for backward compatibility
        return [
            $taxableFortuneAmount,
            $taxableFortuneRate,
            $taxAmount,
            $taxPercent,
            $taxablePropertyAmount,
            $taxablePropertyPercent,
            $taxPropertyAmount,
            $taxPropertyPercent,
            $explanation,
        ];
    }

    /**
     * Proxy method to get property taxable rate from repository.
     * This method exists for backwards compatibility with tests.
     *
     * @param  string  $taxGroup  The tax group (e.g., 'private', 'company').
     * @param  string  $taxProperty  The type of property (municipality code).
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The taxable portion of the property as a decimal (e.g., 0.70 for 70%).
     */
    public function getPropertyTaxable(string $taxGroup, string $taxProperty, int $year): float
    {
        return $this->taxPropertyRepo->getPropertyTaxableRate($taxGroup, $taxProperty, $year);
    }

    /**
     * Calculates the fortune tax based on the given parameters.
     *
     * @param  bool  $debug  If true, debug information will be logged.
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $taxGroup  The tax group for the calculation.
     * @param  float  $amount  The amount of fortune for the calculation.
     * @param  float  $mortgage  The mortgage amount to deduct.
     * @param  bool  $deduct  Whether to apply deductions (currently unused).
     * @return array{0: float, 1: float, 2: float, 3: string} [taxAmount, taxPercent, taxableAmount, explanation]
     */
    public function calculatefortunetax(bool $debug, int $year, string $taxGroup, float $amount, float $mortgage, bool $deduct = false): array
    {
        $taxAmount = 0;
        $taxPercent = 0;
        $explanation = '';

        // Get the fortune tax bracket configuration (includes standard deduction as first bracket with 0%)
        $brackets = $this->taxConfigRepo->getFortuneTaxBracketConfig($year);

        if ($debug) {
            Log::debug('Fortune tax calculation input', [
                'year' => $year,
                'tax_group' => $taxGroup,
                'amount' => $amount,
                'mortgage' => $mortgage,
            ]);
        }

        // FIX: Not all assets is allowed to have mortgage deducted. Only private house/rental/cabins. Check tax laws.
        $taxableAmount = $amount - $mortgage;

        // Calculate the tax amount using bracket-based progressive taxation
        // The first bracket is typically the standard deduction with 0% tax
        $previousLimit = 0;

        foreach ($brackets as $bracket) {
            $percent = Arr::get($bracket, 'percent', 0); // Percentage (e.g., 1.0 = 1%)
            $rate = $percent / 100; // Convert percentage to decimal
            $limit = Arr::get($bracket, 'limit', PHP_FLOAT_MAX); // If no limit, use max float

            if ($amount > $previousLimit) {
                // Calculate the amount in this bracket
                $amountInBracket = min($amount, $limit) - $previousLimit;

                // Add tax for this bracket
                $taxAmount += $amountInBracket * $rate;

                if ($debug) {
                    Log::debug('Fortune tax bracket calculation', [
                        'limit' => $limit,
                        'percent' => $percent,
                        'amount_in_bracket' => $amountInBracket,
                        'bracket_tax' => $amountInBracket * $rate,
                    ]);
                }

                // If we've reached the amount, stop
                if ($amount <= $limit) {
                    break;
                }

                $previousLimit = $limit;
            }
        }

        $taxPercent = $amount > 0 ? $taxAmount / $amount : 0;
        $explanation = 'Fortune tax calculated using bracket system. ';

        // Handle negative taxable amount (mortgage > asset value)
        if ($taxableAmount < 0) {
            $explanation = 'Negative asset value after deducting mortgage, reducing fortune value. ';
        }

        // Log debug information if debug is true
        if ($debug) {
            Log::debug('Fortune tax calculation output', [
                'year' => $year,
                'tax_group' => $taxGroup,
                'tax_amount' => $taxAmount,
                'tax_percent' => $taxPercent,
                'taxable_amount' => $taxableAmount,
                'explanation' => $explanation,
            ]);
        }

        return [$taxAmount, $taxPercent, $taxableAmount, $explanation];
    }
}
