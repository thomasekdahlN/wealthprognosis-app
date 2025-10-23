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
use Illuminate\Support\Arr;

class TaxFortune extends Model
{
    use HasFactory;

    protected $country;

    /**
     * Shared TaxConfigRepository instance.
     */
    private \App\Services\Tax\TaxConfigRepository $taxConfigRepo;

    /**
     * Shared TaxPropertyRepository instance.
     */
    private \App\Services\Tax\TaxPropertyRepository $taxPropertyRepo;

    /**
     * Constructor for the TaxFortune class.
     * Kept signature for backwards compatibility, but now reads from DB on demand.
     *
     * @param  string  $config  Path-like identifier (e.g., 'no/no-tax-2025') used to infer country.
     */
    public function __construct(string $country)
    {
        $this->country = $country;

        // Use the singleton instances from the service container
        $this->taxConfigRepo = app(\App\Services\Tax\TaxConfigRepository::class);
        $this->taxPropertyRepo = app(\App\Services\Tax\TaxPropertyRepository::class);
    }

    /**
     * Calculates the fortune tax and property tax based on the given parameters.
     *
     * @param  string  $taxGroup  The tax group for the calculation.
     * @param  string  $taxType  The tax type for the calculation.
     * @param  string|null  $taxProperty  The property type for the calculation. If null, property tax is not calculated.
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  int|null  $marketAmount  The market amount for the calculation. If null, it is considered as 0.
     * @param  int|null  $taxableAmount  The taxable amount for the calculation. If null, it is considered as 0.
     * @param  bool|null  $taxableAmountOverride  If true, the taxable amount is overridden. If null, it is considered as false.
     * @return array Returns an array containing the taxable amount, taxable percent, tax amount, tax percent, taxable property amount, taxable property percent, tax property amount, tax property percent and an explanation.
     */
    public function taxCalculationFortune(string $taxGroup, ?string $taxType, ?string $taxProperty, int $year, ?int $marketAmount, ?int $taxableInitialAmount, ?int $mortgageBalanceAmount, ?bool $taxableAmountOverride = false)
    {
        $explanation = '';
        $explanation1 = '';
        $explanation2 = '';
        $explanation = '';
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
            // echo "   taxableAmount ovveride: taxableInitialAmount:$taxableInitialAmount - mortgageBalanceAmount:$mortgageBalanceAmount\n";
        } else {
            $taxablePropertyAmount = round($marketAmount);
            $taxableFortuneAmount = round($marketAmount * $taxableFortuneRate); // Calculate the amount from which the tax is calculated from the market value minus mortgage.

            // echo "   taxableAmount normal: taxableFortuneAmount:$taxableFortuneAmount, taxableFortuneRate:$taxableFortuneRate\n";
            $explanation .= 'Market taxable. ';
        }

        [$taxAmount, $taxPercent, $taxableFortuneAmount, $explanation1] = $this->calculatefortunetax(false, $year, $taxGroup, $taxableFortuneAmount, $mortgageBalanceAmount, false);

        if ($taxProperty) {
            $propertyTax = app(\App\Models\Core\TaxProperty::class);
            [$taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation2] = $propertyTax->calculatePropertyTax($year, $taxGroup, $taxProperty, (float) $taxablePropertyAmount);
        }
        $explanation .= $explanation2.$explanation1;

        // echo "   taxCalculationFortuneReturn: taxableFortuneAmount:$taxableFortuneAmount, taxableFortuneRate:$taxableFortuneRate, taxAmount:$taxAmount taxPercent:$taxPercent, taxablePropertyAmount:$taxablePropertyAmount,taxablePropertyPercent:$taxablePropertyPercent,taxPropertyAmount:$taxPropertyAmount,taxPropertyPercent:$taxPropertyPercent,$explanation\n";

        return [$taxableFortuneAmount, $taxableFortuneRate, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation];
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
     * @param  bool  $debug  If true, debug information will be printed.
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $taxGroup  The tax group for the calculation.
     * @param  float  $amount  The amount of fortune for the calculation.
     * @return array Returns an array containing the calculated tax amount, tax percent and an explanation.
     */
    public function calculatefortunetax(bool $debug, int $year, string $taxGroup, float $amount, float $mortgage, bool $deduct = false)
    {
        $taxAmount = 0;
        $taxPercent = 0;
        $explanation = '';

        // Get the fortune tax bracket configuration (includes standard deduction as first bracket with 0%)
        $brackets = $this->taxConfigRepo->getFortuneTaxBracketConfig($year);

        if ($debug) {
            echo "   calculatefortunetax in: $year.$taxGroup, amount:$amount, mortgage: $mortgage\n";
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
                    echo "   Bracket: limit=$limit, percent=$percent%, amountInBracket=$amountInBracket, bracketTax=".($amountInBracket * $rate)."\n";
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

        // Print debug information if debug is true
        if ($debug) {
            echo "   calculatefortunetax out: $year.$taxGroup, taxAmount:$taxAmount, taxPercent:$taxPercent, taxableAmount: $taxableAmount, $explanation\n";
        }

        return [$taxAmount, $taxPercent, $taxableAmount, $explanation];
    }
}
