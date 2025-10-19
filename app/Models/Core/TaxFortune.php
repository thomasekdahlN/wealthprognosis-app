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

namespace App\Models\Core;

use App\Models\TaxProperty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TaxFortune extends Model
{
    use HasFactory;

    public $taxH = [];

    protected $countryCode;

    protected $startYear;

    protected $stopYear;

    /**
     * Constructor for the TaxFortune class.
     * Reads the tax configuration from a JSON file and stores it in the taxH property.
     *
     * @param  string  $config  The name of the tax configuration file (without the .json extension).
     * @param  int  $startYear  The start year for the tax calculation (currently not used).
     * @param  int  $stopYear  The stop year for the tax calculation (currently not used).
     */
    public function __construct($config, $startYear, $stopYear)
    {
        $file = config_path("tax/$config.json");
        $configH = File::json($file);
        echo "Leser: '$file'\n";

        foreach ($configH as $type => $typeH) {
            $this->taxH[$type] = $typeH;
        }

        // Extract country code from config path (e.g., 'no/no-tax-2025' -> 'no')
        $this->countryCode = explode('/', $config)[0] ?? 'no';
        $this->startYear = $startYear;
        $this->stopYear = $stopYear;
    }

    /**
     * Returns the portion of the fortune that is taxable.
     *
     * @param  string  $taxGroup  The tax group (e.g., 'company').
     * @param  string  $taxType  The type of tax.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The portion of the fortune that is taxable.
     */
    public function getFortuneTaxable($taxGroup, $taxType, $year)
    {
        if ($taxGroup == 'company') {
            // A company has the full value as taxable, 100%
            return 100 / 100;
        }

        return Arr::get($this->taxH, "$taxType.fortune", 0) / 100;
    }

    /**
     * Returns the fortune tax percentage.
     *
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $type  The type of tax.
     * @return float The fortune tax percentage.
     */
    public function getFortuneTax($year, $type)
    {
        return Arr::get($this->taxH, "fortune.$type.income", 0) / 100;
    }

    /**
     * Returns the fortune tax amount.
     *
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $type  The type of tax.
     * @return int The fortune tax amount.
     */
    public function getFortuneTaxAmount($year, $type)
    {
        return Arr::get($this->taxH, "fortune.$type.amount", 0);
    }

    /**
     * Returns the standard deduction for the fortune tax.
     *
     * @param  string  $taxGroup  The tax group.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return int The standard deduction for the fortune tax.
     */
    public function getFortuneTaxStandardDeduction($taxGroup, $year)
    {
        return Arr::get($this->taxH, 'fortune.standardDeduction', 0);
    }

    /**
     * Returns the taxable portion of the property.
     * First tries to get from JSON config, then falls back to database TaxProperty model.
     *
     * @param  string  $taxGroup  The tax group (e.g., 'private', 'company').
     * @param  string  $taxProperty  The type of property (municipality code).
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The taxable portion of the property as a decimal (e.g., 0.70 for 70%).
     */
    public function getPropertyTaxable($taxGroup, $taxProperty, $year)
    {
        // First try to get from JSON config
        $jsonValue = Arr::get($this->taxH, "property.$taxProperty.fortune");

        if ($jsonValue !== null) {
            return $jsonValue / 100;
        }

        // Fall back to database lookup
        try {
            $taxPropertyModel = TaxProperty::query()
                ->forCountry($this->countryCode)
                ->forYear($year)
                ->where('code', $taxProperty)
                ->active()
                ->first();

            if ($taxPropertyModel && $taxPropertyModel->fortune_taxable_percent !== null) {
                // Database stores as percentage (e.g., 70.00), return as decimal (0.70)
                return $taxPropertyModel->fortune_taxable_percent / 100;
            }
        } catch (\Throwable $e) {
            // Log error if needed
        }

        // Default to 0 if neither source has the value
        return 0;
    }

    /**
     * Returns the standard deduction for the property tax.
     *
     * @param  string  $taxGroup  The tax group.
     * @param  string  $taxProperty  The type of property.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return int The standard deduction for the property tax.
     */
    public function getPropertyTaxStandardDeduction($taxGroup, $taxProperty, $year)
    {
        return Arr::get($this->taxH, "property.$taxProperty.standardDeduction", 0);
    }

    /**
     * Returns the property tax percentage.
     *
     * @param  string  $taxGroup  The tax group.
     * @param  string  $taxProperty  The type of property.
     * @param  int  $year  The year for which the tax is being calculated.
     * @return float The property tax percentage.
     */
    public function getPropertyTax($taxGroup, $taxProperty, $year)
    {
        return Arr::get($this->taxH, "property.$taxProperty.income", 0) / 100;
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
    public function taxCalculationFortune(string $taxGroup, string $taxType, ?string $taxProperty, int $year, ?int $marketAmount, ?int $taxableInitialAmount, ?int $mortgageBalanceAmount, ?bool $taxableAmountOverride = false)
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

        $taxableFortunePercent = $this->getFortuneTaxable($taxGroup, $taxType, $year);

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
            $taxableFortuneAmount = round($marketAmount * $taxableFortunePercent); // Calculate the amount from which the tax is calculated from the market value minus mortgage.

            // echo "   taxableAmount normal: taxableFortuneAmount:$taxableFortuneAmount, taxableFortunePercent:$taxableFortunePercent\n";
            $explanation .= 'Market taxable. ';
        }

        [$taxAmount, $taxPercent, $taxableFortuneAmount, $explanation1] = $this->calculatefortunetax(false, $year, $taxGroup, $taxableFortuneAmount, $mortgageBalanceAmount, false);

        if ($taxProperty) {
            [$taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation2] = $this->calculatePorpertyTax($year, $taxGroup, $taxProperty, $taxablePropertyAmount);
        }
        $explanation .= $explanation2.$explanation1;

        // echo "   taxCalculationFortuneReturn: taxableFortuneAmount:$taxableFortuneAmount, taxableFortunePercent:$taxableFortunePercent, taxAmount:$taxAmount taxPercent:$taxPercent, taxablePropertyAmount:$taxablePropertyAmount,taxablePropertyPercent:$taxablePropertyPercent,taxPropertyAmount:$taxPropertyAmount,taxPropertyPercent:$taxPropertyPercent,$explanation\n";

        return [$taxableFortuneAmount, $taxableFortunePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation];
    }

    /**
     * Calculates the property tax based on the given parameters.
     *
     * @param  int  $year  The year for which the tax is being calculated.
     * @param  string  $taxGroup  The tax group for the calculation.
     * @param  string  $taxProperty  The property type for the calculation.
     * @param  float  $amount  The amount of property for the calculation.
     * @return array Returns an array containing the taxable property amount, taxable property percent, tax property amount, tax property percent and an explanation.
     */
    public function calculatePorpertyTax(int $year, string $taxGroup, $taxProperty, float $amount)
    {
        $taxablePropertyAmount = 0;
        $taxPropertyAmount = 0;
        $explanation = '';

        // Get the taxable property percent for the given tax group, property type and year
        $taxablePropertyPercent = $this->getPropertyTaxable($taxGroup, $taxProperty, $year);

        // Get the property tax percent for the given tax group, property type and year
        $taxPropertyPercent = $this->getPropertyTax($taxGroup, $taxProperty, $year);

        // Get the standard deduction for the given tax group, property type and year
        $taxPropertyDeductionAmount = $this->getPropertyTaxStandardDeduction($taxGroup, $taxProperty, $year);

        // Calculate the taxable property amount after deduction
        $taxablePropertyAmount = ($amount * $taxablePropertyPercent) - $taxPropertyDeductionAmount;
        // echo "    taxablePropertyAmount:$taxablePropertyAmount = ($amount - $taxPropertyDeductionAmount) * $taxablePropertyPercent\n";
        // echo "    taxPropertyPercent:$taxPropertyPercent\n";

        // Calculate the tax property amount and provide explanation based on the taxable property amount and tax property percent
        if ($taxablePropertyAmount > 0 && $taxPropertyPercent > 0) {
            $taxPropertyAmount = round($taxablePropertyAmount * $taxPropertyPercent);
            $explanation = "Property tax $taxPropertyPercent% of $taxablePropertyAmount.";
        } else {
            $taxablePropertyAmount = 0; // Taxable property amount can not be zero
            $taxablePropertyPercent = 0;
            $explanation = 'No property tax. ';
        }

        return [$taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation];
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
        $taxableDeductionAmount = $this->getFortuneTaxStandardDeduction($taxGroup, $year);

        // Get the low and high tax percentages for the given tax group and year
        $taxLowPercent = $this->getFortuneTax($taxGroup, 'low', $year);
        $taxHighPercent = $this->getFortuneTax($taxGroup, 'high', $year);

        // Get the low and high limit amounts for the given tax group and year
        $taxLowLimitAmount = $this->getFortuneTaxAmount($taxGroup, 'low', $year);
        $taxHighLimitAmount = $this->getFortuneTaxAmount($taxGroup, 'high', $year);

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
