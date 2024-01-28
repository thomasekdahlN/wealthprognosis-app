<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class TaxFortune extends Model
{
    use HasFactory;

    public $taxH = [];

    //Will be rewritten to support yearly tax differences, just faking for now.
    //Should probably be a deep nested json structure.
    public function __construct($config, $startYear, $stopYear)
    {

        $file = "tax/$config.json";
        $configH = json_decode(Storage::disk('local')->get($file), true);
        echo "Leser: '$file'\n";

        foreach ($configH as $type => $typeH) {
            $this->taxH[$type] = $typeH;
        }
    }

    //Returnerer hvor stor del av formuen som blir skattlagt
    public function getFortuneTaxable($taxGroup, $taxType, $year)
    {
        if($taxGroup == 'company') {
            //A company has the full value as taxable, 100%
            return 100/100;
        }

        return Arr::get($this->taxH, "$taxType.fortune", 0) / 100;
    }

    //Returnerer formuesskatten i %
    public function getFortuneTax($year, $type)
    {

        return Arr::get($this->taxH, "fortune.$type.yearly", 0) / 100;
    }

    public function getFortuneTaxAmount($year, $type)
    {

        return Arr::get($this->taxH, "fortune.$type.amount", 0);
    }

    public function getFortuneTaxStandardDeduction($taxGroup, $year)
    {

        return Arr::get($this->taxH, 'fortune.standardDeduction', 0);
    }

    public function getPropertyTaxable($taxGroup, $taxProperty, $year)
    {
        return Arr::get($this->taxH, "property.$taxProperty.fortune", 0) / 100;
    }

    public function getPropertyTaxStandardDeduction($taxGroup, $taxProperty, $year)
    {
        return Arr::get($this->taxH, "property.$taxProperty.standardDeduction", 0);
    }

    public function getPropertyTax($taxGroup, $taxProperty, $year)
    {
        return Arr::get($this->taxH, "property.$taxProperty.yearly", 0) / 100;
    }

    public function taxCalculationFortune(string $taxGroup, string $taxType, string $taxProperty = null, int $year, ?int $marketAmount = 0, ?int $taxableAmount = 0, ?bool $taxableAmountOverride = false)
    {
        $explanation = '';
        $explanation1 = '';
        $explanation2 = '';
        $taxAmount = 0;
        $taxPercent = 0;
        $explanation = '';
        $taxableFortuneAmount = 0;
        $taxablePropertyAmount = 0;

        //Property tax
        $taxPropertyPercent = 0;
        $taxablePropertyPercent = 0;
        $taxablePropertyAmount = $taxableAmount;
        $taxPropertyAmount = 0;
        $taxablePropertyAmount = 0;

        $taxablePercent = $this->getFortuneTaxable($taxGroup, $taxType, $year);

        if ($taxableAmountOverride && $taxableAmount > 0) {
            $taxablePropertyAmount = $taxableAmount;
            $taxableFortuneAmount = $taxableAmount;

            $taxablePercent = 0; //If $fortuneTaxableAmount is set, we ignore the $fortuneTaxablePercent since that should be calculated from the market value and when $fortuneTaxableAmount is set, we do not releate tax to market value anymore.
            $explanation = "Fixed taxable amount. ";
            //echo "   taxableAmount ovveride: $taxableAmount\n";
        } else {
            $taxablePropertyAmount = round($marketAmount);
            $taxableFortuneAmount = round($marketAmount * $taxablePercent); //Calculate the amount from wich the tax is calculated from the market value if $fortuneTaxableAmount is not set
            //echo "   taxableAmount normal: $taxableAmount\n";
            $explanation = "Market taxable amount. ";
        }

        [$taxAmount, $taxPercent, $explanation1] = $this->calculatefortunetax(false, $year, $taxGroup, $taxableFortuneAmount);

        if ($taxProperty) {
            [$taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation2] = $this->calculatePorpertyTax($year, $taxGroup, $taxProperty, $taxablePropertyAmount);
        }
        $explanation = $explanation1 . $explanation2;

        return [$taxableAmount, $taxablePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation];
    }

    function calculatePorpertyTax(int $year, string $taxGroup, $taxProperty, float $amount) {
        $taxablePropertyAmount = 0;
        $taxPropertyAmount = 0;
        $explanation = '';

        $taxablePropertyPercent = $this->getPropertyTaxable($taxGroup, $taxProperty, $year);
        $taxPropertyPercent = $this->getPropertyTax($taxGroup, $taxProperty, $year);
        $taxPropertyDeductionAmount = $this->getPropertyTaxStandardDeduction($taxGroup, $taxProperty, $year);

        $taxablePropertyAmount = ($amount - $taxPropertyDeductionAmount) * $taxablePropertyPercent;
        if ($taxablePropertyAmount > 0 && $taxPropertyPercent > 0) {
            $taxPropertyAmount = round($taxablePropertyAmount * $taxPropertyPercent);
            $explanation = "Property tax $taxPropertyPercent% of $taxablePropertyAmount.";
        } else {
            $taxablePropertyAmount = 0; //Can not be zero
            $taxablePropertyPercent = 0;
            $explanation = "No property tax. ";
        }
        //print "   $year.$taxGroup, taxProperty: $taxProperty, taxablePropertyAmount: $taxablePropertyAmount (" . $taxablePropertyPercent * 100 . "%), taxPropertyDeductionAmount: $taxPropertyDeductionAmount, taxPropertyAmount: $taxPropertyAmount (" . $taxPropertyPercent * 100 . "%)\n";
        return [$taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent, $explanation];
    }

    function calculatefortunetax(bool $debug, int $year, string $taxGroup, float $amount) {
        $taxAmount = 0;
        $taxPercent = 0;
        $explanation = '';

        $taxableDeductionAmount = $this->getFortuneTaxStandardDeduction($taxGroup, $year);

        //Fortune tax
        $taxLowPercent = $this->getFortuneTax($taxGroup, 'low', $year);
        $taxHighPercent = $this->getFortuneTax($taxGroup, 'high', $year);

        $taxLowLimitAmount = $this->getFortuneTaxAmount($taxGroup, 'low', $year);
        $taxHighLimitAmount = $this->getFortuneTaxAmount($taxGroup, 'high', $year);

        $taxableAmount = $amount - $taxableDeductionAmount; //Remove the deduction threshold for taxation
        if($taxableAmount < 0) {
            $taxableAmount = 0; //Tax can not go lower than zero
            $explanation = "No taxable fortune after deduction. ";
        }

        #FIX: 1.1% fortune tax if value is more than 20millions.
        if ($amount > $taxHighLimitAmount) {
            //Higher fortune tax on more than 20million pr 2024
            $taxHighAmount = ($amount - $taxHighLimitAmount) * $taxHighPercent; //Higher tax on the amount above 20 million
            $taxLowAmount  = ($taxHighLimitAmount - $taxableDeductionAmount) * $taxLowPercent; //Normal tax until 20 -1.7mill bunnfradrag million

            $taxAmount = $taxHighAmount + $taxLowAmount;
            $taxPercent = $taxHighPercent;
            $explanation = "High fortune tax > $taxHighLimitAmount (" . $taxHighPercent*100 . "%)";

        } elseif ($amount <= $taxHighLimitAmount && $taxableAmount > 0) {
            //Only fortune tax on more than 1.7million pr 2023
            $taxAmount = $taxableAmount * $taxLowPercent; //Calculate the tax you shall pay from the taxable fortune
            $taxPercent = $taxLowPercent;
            $explanation = "Low fortune tax < $taxHighLimitAmount (" . $taxLowPercent*100 . "%)";

        } else {
            $explanation = "No fortune tax on $amount ";
        }

        if($debug) {
            print "   $year.$taxGroup, amount:$amount, taxableAmount: $taxableAmount, taxLowLimitAmount:$taxLowLimitAmount, taxHighLimitAmount:$taxHighLimitAmount, $explanation\n";
        }
        return [$taxAmount, $taxPercent, $explanation];
    }
}
