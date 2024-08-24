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

class Helper extends Model
{
    use HasFactory;

    public function calculateRule(bool $debug, int $amount, int $acquisitionAmount, string $rule, int $factor = 1)
    {
        $totalAmount = 0;
        $explanation = null;

        if ($debug) {
            echo "  calculateRule INPUT(amount: $amount, rule: $rule)\n";
        }

        if ($rule) {
            if (preg_match('/(\+|\-)?(\d*)(\%)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                [$newAmount, $calcAmount, $explanation] = $this->calculationPercentage($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)\/(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                //When divisor sign is pipe / -Normal divisor

                [$newAmount, $calcAmount, $explanation] = $this->calculationDivisor($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)\|(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                //When divisor sign is pipe | - we dynamically count down divisor according to rules
                [$newAmount, $calcAmount, $rule, $explanation] = $this->calculationDynamicDivisor($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                [$newAmount, $calcAmount, $explanation] = $this->calculationPlusMinus($debug, $amount, $ruleH, $factor);

            } else {
                echo "ERROR: calculateRule #$rule# not supported";
            }
        }

        return [$newAmount, $calcAmount, $rule, $explanation];
    }

    public function calculationDynamicDivisor(bool $debug, string $amount, array $ruleH)
    {

        $rule = null;

        [$newAmount, $calcAmount, $explanation] = $this->calculationDivisor($debug, $amount, $ruleH);

        $ruleH[3][0]--; //Dynamic divisor, we count down.

        if ($ruleH[3][0] > 0) {
            $rule = $ruleH[1][0].$ruleH[2][0].'|'.$ruleH[3][0];
        }

        $explanation .= " rewritten rule: $rule";

        if ($debug) {
            echo "  calculationDynamicDivisor OUTPUT(amount: $amount, newAmount: $newAmount, calcAmount: $calcAmount, newrule: $rule, explanation: $explanation)\n";
        }

        return [$newAmount, $calcAmount, $rule, $explanation]; //Returns rewritten rule, has to be remembered
    }

    public function calculationDivisor(bool $debug, int $amount, array $ruleH)
    {
        $divisor = $ruleH[3][0];
        $calcAmount = round($amount / $divisor);

        if ($ruleH[1][0] == '-') {
            $newAmount = $amount - $calcAmount;
            $explanation = "Subtracting division: $amount/$divisor=".$calcAmount;

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = $amount + $divisorAmount;
            $explanation = "Adding division: $amount/$divisor=".$calcAmount;
        } else {
            //When no sign is given,  we only want the part of the amount. Its like taking this divisor out of the amount.
            $newAmount = $amount; //We do not change the original amount
            $explanation = "Division: $amount/$divisor=".$calcAmount;
        }

        return [$newAmount, $calcAmount, $explanation];
    }

    public function calculationPercentage(bool $debug, string $amount, array $ruleH)
    {

        $percent = $ruleH[2][0];
        $calcAmount = 0;

        if ($ruleH[1][0] == '-') {
            $newAmount = round($amount * ((-$percent / 100) + 1));
            $explanation = "$amount-$percent%=$newAmount";
            $calcAmount = $newAmount - $amount;

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = round($amount * (($percent / 100) + 1));
            $explanation = "$amount+$percent%=$newAmount";
            $calcAmount = $newAmount - $amount;

        } else {
            //When no sign is given, we only want the part of the amount. Its like taking this percentage out of the amount.
            $newAmount = $amount; //We do not change the original amount
            $calcAmount = round($amount * ($percent / 100));
            $explanation = "$percent% of $amount=$calcAmount";
            //$diffAmount = $newAmount - $amount;
        }

        return [$newAmount, $calcAmount, $explanation];
    }

    public function calculationPlusMinus(bool $debug, int $amount, array $ruleH, int $factor = 1)
    {
        $extraAmount = $ruleH[2][0];
        $calcAmount = round($extraAmount * $factor);

        if ($ruleH[1][0] == '-') {
            $newAmount = $amount - $calcAmount;
            $explanation = "Subtracting: $amount-($extraAmount*$factor)=$newAmount ";

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = $amount + $calcAmount;
            $explanation = "Adding: $amount+($extraAmount*$factor)=$newAmount ";
        } else {
            //When no sign is given, we only want the part of the amount. Its like taking this extra amount out of the amount.
            $newAmount = $amount; //We do not change the original amount
            $explanation = "Extra amount: $extraAmount*$factor=$calcAmount ";
        }

        return [$newAmount, $calcAmount, $explanation];
    }

    public function pathToElements($path)
    {
        $assetname = null;
        $year = null;
        $type = null;
        $field = null;

        if (preg_match('/(\w+).(\w+).(\w+).(\w+)/i', $path, $matchesH, PREG_OFFSET_CAPTURE)) {
            $assetname = $matchesH[1][0];
            $year = $matchesH[2][0];
            $type = $matchesH[3][0];
            $field = $matchesH[4][0];
        } else {
            echo "ERRROR: pathToElements($path)\n";
        }

        return [$assetname, $year, $type, $field];
    }
}
