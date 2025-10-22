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

class TaxFortune extends Model
{
    use HasFactory;

    protected $countryCode;

    protected $startYear;

    protected $stopYear;

    /**
     * Shared repository for loading tax configs.
     */
    private \App\Services\Tax\TaxConfigRepository $repo;

    /**
     * Constructor for the TaxFortune class.
     * Kept signature for backwards compatibility, but now reads from DB on demand.
     *
     * @param  string  $config  Path-like identifier (e.g., 'no/no-tax-2025') used to infer country.
     * @param  int  $startYear  The start year for the tax calculation (informational only).
     * @param  int  $stopYear  The stop year for the tax calculation (informational only).
     */
    public function __construct($config, $startYear, $stopYear)
    {
        // Extract country code from config path (e.g., 'no/no-tax-2025' -> 'no')
        $this->countryCode = strtolower(explode('/', (string) $config)[0] ?? 'no');
        $this->startYear = (int) $startYear;
        $this->stopYear = (int) $stopYear;
        $this->taxconfig = new \App\Services\Tax\TaxConfigRepository($this->countryCode);
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

        $taxableFortuneRate = $this->taxconfig->getTaxFortuneTaxableRate($taxType, $year);

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
            $propertyTax = new \App\Models\Core\TaxProperty($this->taxconfig);
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
        return $this->taxconfig->getPropertyTaxable($taxGroup, $taxProperty, $year);
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

        // Get the standard deduction for the given tax group and year
        $taxableDeductionAmount = $this->taxconfig->getFortuneTaxStandardDeduction($taxGroup, $year);

        // Get the low and high tax percentages for the given tax group and year
        $taxLowPercent = $this->taxconfig->getFortuneTax($taxGroup, 'low', $year);
        $taxHighPercent = $this->taxconfig->getFortuneTax($taxGroup, 'high', $year);

        // Get the low and high limit amounts for the given tax group and year
        $taxLowLimitAmount = $this->taxconfig->getFortuneTaxAmount($taxGroup, 'low', $year);
        $taxHighLimitAmount = $this->taxconfig->getFortuneTaxAmount($taxGroup, 'high', $year);

        if ($debug) {
            echo "   calculatefortunetax in: $year.$taxGroup, amount:$amount, mortgage: $mortgage\n";
        }

        // FIX: Not all assets is allowed to have mortgage deducted. Only rivate house/rental/cabins. Check tax laws.
        $taxableAmount = $amount - $mortgage;

        if ($taxableAmount < 0) {
            // If the taxable amount is negative before deduction, it is because the asset has a mortgage higher than the value of the asset. This is deductable on the fortune tax and should be calculated and returned as negative values reducing fortune taxable and fortune tax
            // Just pass through and use the taxableAmount as is. Funny that this a if without changing anuthing, but it is here for readability
            $explanation = 'Negative asset value after deducting mortgage, reducing fortune value. ';
        } elseif ($deduct) {
            // We are not deducting from every asset, because we sum the value afterwards and the calculation gets wrong. We only deduct on grouped assets since the deduction is on the total
            // https://www.skatteetaten.no/person/skatt/hjelp-til-riktig-skatt/verdsettingsrabatt-ved-fastsetting-av-formue/

            if ($taxableAmount - $taxableDeductionAmount > 0) {
                // If the taxable amount is bigger than the deduction, resulting in a postive taxable amount, we use the deduction to reduce the taxable amount
                // The value can never go negative because of the deduction, so this scenario should never give a negative taxable amount or negative tax
                $taxableAmount = $taxableAmount - $taxableDeductionAmount; // FIX. We can not deduct on every asset, only on the total - if not this gets very wrong when summed to the totals.
                $explanation = 'Positive asset value after deducting. ';

            } else {
                // If the taxable amount is less than the deduction, we set the taxable amount to zero, since nothing taxable and value can not be negative after deduction
                // The value can never go negative because of the deduction, so this scenario should never give a negative taxable amount or negative tax
                $taxableAmount = 0;
                $explanation = 'Asset value set to zero after deducting. ';
            }
        }

        // Calculate the tax amount and percentage based on the amount and the tax limits
        if ($amount > $taxHighLimitAmount) {
            // Higher fortune tax on more than 20million pr 2024
            $taxHighAmount = ($amount - $taxHighLimitAmount) * $taxHighPercent;
            $taxLowAmount = ($taxHighLimitAmount - $taxableDeductionAmount) * $taxLowPercent;

            $taxAmount = $taxHighAmount + $taxLowAmount;
            $taxPercent = $taxHighPercent;
            $explanation .= "High fortune tax > $taxHighLimitAmount (".$taxHighPercent * 100 .'%)';

        } elseif ($amount <= $taxHighLimitAmount) {
            // Only fortune tax on more than 1.7million pr 2023
            $taxAmount = $taxableAmount * $taxLowPercent;
            $taxPercent = $taxLowPercent;
            $explanation .= "Low fortune tax < $taxHighLimitAmount (".$taxLowPercent * 100 .'%)';
        }

        // Print debug information if debug is true
        if ($debug) {
            echo "   calculatefortunetax out: $year.$taxGroup, taxAmount:$taxAmount, taxPercent:$taxPercent, taxableAmount: $taxableAmount, taxLowLimitAmount:$taxLowLimitAmount, taxHighLimitAmount:$taxHighLimitAmount, $explanation\n";
        }

        return [$taxAmount, $taxPercent, $taxableAmount, $explanation];
    }
}
