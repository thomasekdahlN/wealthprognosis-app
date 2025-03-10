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

class TaxSalary extends Model
{
    use HasFactory;

    public $taxH = [];

    // Will be rewritten to support yearly tax differences, just faking for now.
    // Should probably be a deep nested json structure.
    public function __construct()
    {

        $file = config_path('tax/no/no-tax-2025.json');
        $configH = File::json($file);
        // echo "Leser: '$file'\n";

        foreach ($configH as $type => $typeH) {
            $this->taxH[$type] = $typeH;
        }
    }

    public function getTaxIncome($taxGroup, $taxType, $year)
    {

        return Arr::get($this->taxH, "$taxType.income", 0) / 100;
    }

    public function getTax($taxGroup, $taxType, $year)
    {
        return Arr::get($this->taxH, "salary.$taxType", 0) / 100;
    }

    public function getDeduction($taxGroup, $taxType, $year)
    {
        return Arr::get($this->taxH, "salary.$taxType", 0);
    }

    public function getTaxBracket($taxGroup, $year)
    {

        return Arr::get($this->taxH, 'salary.bracket');
    }

    public function calculatesalarytax(bool $debug, int $year, int $amount)
    {
        $explanation = '';
        $commonTaxAmount = 0; // Fellesskatt
        $bracketTaxAmount = 0; // Trinnskatt
        $socialSecurityTaxAmount = 0; // Trygdeavgift
        $totalTaxAmount = 0; // Utregnet hva skatten faktisak er basert på de faktiske skattebeløpene.

        $commonTaxPercent = $this->getTax('private', 'common.rate', $year);
        $commonTaxDeductionAmount = $this->commonDeduction($year, $amount);

        $socialSecurityTaxPercent = $this->getTax('private', 'socialsecurity.rate', $year);
        $socialSecurityTaxDeductionAmount = $this->getDeduction('private', 'socialsecurity.deduction', $year);
        $totalTaxPercent = 0; // Utregnet hva skatten faktisak er basert på de faktiske skattebeløpene.

        $socialSecurityTaxableAmount = $amount; // Man betaler trygdeavgift av hele lønnen uten fradrag
        if ($socialSecurityTaxableAmount > 0) {
            $socialSecurityTaxAmount = round($socialSecurityTaxableAmount * $socialSecurityTaxPercent);
        }

        $commonTaxableAmount = $amount - $socialSecurityTaxAmount - $commonTaxDeductionAmount; // Man betaler fellesskatt av lønnen etter at trygdeavgidt og minstefradraget er trukket fra
        $commonTaxAmount = round($commonTaxableAmount * $commonTaxPercent);

        [$bracketTaxAmount, $bracketTaxPercent, $explanation] = $this->calculateBracketTax(true, $year, $amount); // Man betaler trinnskatt av hele lønnen uten fradrag

        $explanation = ' Fellesskatt: '.$commonTaxPercent * 100 ."% gir $commonTaxAmount skatt, Trygdeavgift ".$socialSecurityTaxPercent * 100 ."% gir $socialSecurityTaxAmount skatt ".$explanation;

        $totalTaxAmount = $bracketTaxAmount + $commonTaxAmount + $socialSecurityTaxAmount;

        if ($amount > 0) {
            $totalTaxPercent = round(($totalTaxAmount / $amount), 2); // We calculate a total percentage using the amounts
        }
        // Print debug information if debug is true
        if ($debug) {
            echo "   $year amount:$amount, commonTaxDeductionAmount:$commonTaxDeductionAmount, commonTaxableAmount:$commonTaxableAmount, totalTaxAmount:$totalTaxAmount, totalTaxPercent:".$totalTaxPercent * 100 .", $explanation\n";
        }

        return [$totalTaxAmount, $totalTaxPercent, $explanation];
    }

    public function calculateBracketTax(bool $debug, int $year, int $amount)
    {
        $count = 0;
        $explanation = '';
        $brackets = $this->getTaxBracket('private', 'bracket', $year);

        $bracketTaxAmount = 0;
        $bracketTotalTaxAmount = 0;
        $bracketTaxPercent = 0;
        $bracketTotalTaxPercent = 0;

        $prevLimitAmount = 0;
        foreach ($brackets as $bracket) {
            // print "Trinn " . $amount . " > " . $bracket['limit'] . "\n";
            $bracketTaxPercent = $bracket['rate'] / 100;

            if (isset($bracket['limit']) && $amount > $bracket['limit']) {
                $bracketTaxableAmount = $bracket['limit'] - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;

                $explanation .= " Bracket$count ($bracket[limit])$bracket[rate]%=$bracketTaxAmount,";
                // echo "Bracket limit $bracket[limit], amount: $amount, taxableAmount:$bracketTaxableAmount * $bracket[rate]% = tax: $bracketTaxAmount\n";

            } elseif (isset($bracket['limit'])) {
                // Amount is lower than limit, we are at the end and calculate the rest of the amount.
                $bracketTaxableAmount = $amount - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;
                $explanation .= " Bracket$count ($amount<)".$bracket['limit'].")$bracket[rate]%=$bracketTaxAmount";
                // echo "Bracket $amount < " . $bracket['limit'] . " taxableAmount:$bracketTaxableAmount * $bracket[rate]% = tax: $bracketTaxAmount\n";

                break;
            } else {
                // Not set, then all tax after this is on bigger than logic, we are at the end of the calculation
                $bracketTaxableAmount = $amount - $prevLimitAmount;
                $bracketTaxAmount = round($bracketTaxableAmount * $bracketTaxPercent);
                $bracketTotalTaxAmount += $bracketTaxAmount;
                $explanation .= " Bracket$count (>$prevLimitAmount)$bracket[rate]%=$bracketTaxAmount";
                // echo "Bracket limit bigger than $prevLimitAmount taxableAmount:$bracketTaxableAmount * $bracket[rate]% = tax: $bracketTaxAmount\n";

                break;
            }
            $prevLimitAmount = $bracket['limit'];
            $count++;
        }

        if ($amount > 0) {
            $bracketTotalTaxPercent = round(($bracketTaxAmount / $amount), 2); // We calculate a total percentage using the amounts
        }

        $explanation = " Trinnskatt:$bracketTotalTaxAmount snitt ".$bracketTotalTaxPercent * 100 .'%, '.$explanation;

        return [$bracketTotalTaxAmount, $bracketTotalTaxPercent, $explanation];
    }

    // Beregning av minstefradrag
    public function commonDeduction($year, $amount)
    {
        $minAmount = $this->getDeduction('private', 'deduction.min', $year);
        $maxAmount = $this->getDeduction('private', 'deduction.max', $year);
        $percent = $this->getTax('private', 'deduction.percent', $year);

        $deduction = $amount * $percent;
        if ($deduction > $maxAmount) {
            $deduction = $maxAmount;
        }
        if ($deduction < $minAmount) {
            $deduction = $minAmount;
        }

        // echo "amount: $amount, min: $minAmount, max: $maxAmount, percent: $percent, deduction: $deduction\n";

        return $deduction;
    }
}
