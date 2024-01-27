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

    //Tax types where we check for propertyTax
    public $taxPropertyTypes = [
        'house' => true,
        'rental' => true,
        'cabin' => true,
        'property' => true,
    ];

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
    public function getTaxableFortune($taxGroup, $taxType, $year)
    {
        if($taxGroup == 'company') {
            //A company has the full value as taxable, 100%
            return 100/100;
        }

        return Arr::get($this->taxH, "$taxType.fortune", 0) / 100;
    }

    //Returnerer formuesskatten i %
    public function getFortuneTax($year)
    {

        return Arr::get($this->taxH, 'fortune.yearly', 0) / 100;
    }

    public function getFortuneTaxStandardDeduction($taxGroup, $year)
    {

        return Arr::get($this->taxH, 'fortune.standardDeduction', 0);
    }

    public function getPropertyTaxable($taxGroup, $year)
    {
        return Arr::get($this->taxH, 'property.fortune', 0) / 100;
    }

    public function getPropertyTaxStandardDeduction($taxGroup, $year)
    {
        return Arr::get($this->taxH, 'property.standardDeduction', 0);
    }

    public function getPropertyTax($taxGroup, $year)
    {
        return Arr::get($this->taxH, 'property.yearly', 0) / 100;
    }

    //Only run on totals. Difficult to run on separate assets as you cannot add them together.
    //ToDo different tax'es for companies?
    public function fortuneTaxGroupCalculation($group, $fortuneTaxableAmount, $year)
    {

        $fortuneTaxPercent = $this->getFortuneTax($year);
        $taxStandardDeductionAmount = $this->getFortuneTaxStandardDeduction($group, $year);

        if ($group == 'private' || $group == 'total') {
            //ToDo: Check if bunnfradrag bare er for private
            if ($fortuneTaxableAmount <= $taxStandardDeductionAmount) {
                $fortuneTaxableAmount = 0; //Det betales ikke formuesskatt på skattbar formue under 1.7 mill. FIX lese fra confid, støtte årlige forskjeller. Nesten i mål. Tar år som input, men ignorerer år enn så lenge.
            } else {
                $fortuneTaxableAmount -= $taxStandardDeductionAmount;
            }
        }

        $fortuneTaxAmount = $fortuneTaxableAmount * $fortuneTaxPercent; //Calculate the tax you shall pay from the taxable fortune

        return [$fortuneTaxAmount, $fortuneTaxPercent];
    }

    public function taxCalculationFortune(string $taxGroup, string $taxType, int $year, ?int $marketAmount = 0, ?int $taxableAmount = 0, ?bool $taxableAmountOverride = false)
    {
        $taxAmount = 0;
        $taxStandardDeductionAmount = $this->getFortuneTaxStandardDeduction($taxGroup, $year);
        $taxPercent = $this->getFortuneTax($taxGroup, $year);
        $taxablePercent = $this->getTaxableFortune($taxGroup, $taxType, $year);
        $taxablePropertyAmount = 0;
        $taxPropertyAmount = 0;
        $taxablePropertyPercent = $this->getPropertyTaxable($taxGroup, $year);
        $taxPropertyPercent = $this->getPropertyTax($taxGroup, $year);
        $taxPropertyStandardDeductionAmount = $this->getPropertyTaxStandardDeduction($taxGroup, $year);

        if ($taxableAmountOverride && $taxableAmount > 0) {
            $taxablePercent = 0; //If $fortuneTaxableAmount is set, we ignore the $fortuneTaxablePercent since that should be calculated from the market value and when $fortuneTaxableAmount is set, we do not releate tax to market value anymore.
            //echo "   taxableAmount ovveride: $taxableAmount\n";
        } else {
            $taxableAmount = $marketAmount * $taxablePercent; //Calculate the amount from wich the tax is calculated from the market value if $fortuneTaxableAmount is not set
            //echo "   taxableAmount normal: $taxableAmount\n";
        }

        //Only fortune tax on more than 1.7-20million pr 2023
        if ($taxableAmount > $taxStandardDeductionAmount && $taxableAmount < 20000000) { //FIX: Should be read from config
            $taxAmount = ($taxableAmount - $taxStandardDeductionAmount) * $taxPercent; //Calculate the tax you shall pay from the taxable fortune
        }
        //print "$AmountTaxableFortune, $taxAmount, $taxPercent\n";

        #FIX: 1.1% fortuen tax if value is more than 20millions.
        if ($taxableAmount > 20000000) { //FIX: Should be read from config
            $taxAmount = (20000000 - $taxStandardDeductionAmount) * $taxPercent; //Different tax on first interval 1.7-20mill
            $taxPercent = 0.011; //FIX: Should be read from config
            $taxAmount += ($taxableAmount - 20000000) * $taxPercent; //Different tax above 20mill
        }

        if (Arr::get($this->taxPropertyTypes, $taxType)) {
            $taxablePropertyAmount = ($marketAmount - $taxPropertyStandardDeductionAmount) * $taxablePropertyPercent;
            if ($taxablePropertyAmount > 0 && $taxPropertyPercent > 0) {
                $taxPropertyAmount = $taxablePropertyAmount * $taxPropertyPercent;
            } else {
                $taxablePropertyPercent = 0;
            }
            //print "   taxablePropertyAmount: $taxablePropertyAmount, taxPropertyAmount: $taxPropertyAmount\n";
        }

        return [$taxableAmount, $taxablePercent, $taxAmount, $taxPercent, $taxablePropertyAmount, $taxablePropertyPercent, $taxPropertyAmount, $taxPropertyPercent];
    }
}
