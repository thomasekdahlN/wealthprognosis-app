<?php

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
                [$diffAmount, $explanation] = $this->calculationPercentage($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)\/(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                //When divisor sign is pipe / -Normal divisor

                [$diffAmount, $explanation] = $this->calculationDivisor($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)\|(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                //When divisor sign is pipe | - we dynamically count down divisor according to rules
                [$diffAmount, $rule, $explanation] = $this->calculationDynamicDivisor($debug, $amount, $ruleH);

            } elseif (preg_match('/(\+|\-)?(\d*)/i', $rule, $ruleH, PREG_OFFSET_CAPTURE)) {
                [$diffAmount, $explanation] = $this->calculationPlusMinus($debug, $amount, $ruleH, $factor);

            } else {
                echo "ERROR: calculateRule #$rule# not supported";
            }
        }

        $totalAmount = $diffAmount + $amount;

        return [$totalAmount, $diffAmount, $rule, $explanation];
    }

    public function calculationDynamicDivisor(bool $debug, string $amount, array $ruleH)
    {
        $rule = null;

        $diffAmount = round($amount / $ruleH[3][0]);

        if ($ruleH[1][0] == '-') {
            $newAmount = $amount - $diffAmount;
            $explanation = 'Subtracting divisor: '.$diffAmount;
            $ruleH[3][0]--; //Dynamic divisor

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = $amount + $diffAmount;
            $explanation = 'Adding divisor: '.$diffAmount;
            $ruleH[3][0]--; //Dynamic divisor
        } else {
            //When no sign is given, we reduce the amount. Its lake taking this divisor out of the amount.
            $newAmount = $amount - $diffAmount;
            $explanation = 'Adding divisor: '.$diffAmount;
            $ruleH[3][0]--; //Dynamic divisor
        }

        if ($ruleH[3][0] > 0) {
            $rule = $ruleH[1][0].$ruleH[2][0].'|'.$ruleH[3][0]; //ToDo: Note rule is rewritten for each iteration
        }

        $explanation .= " rewritten rule: $rule";

        if ($debug) {
            echo "  calculationDynamicDivisor OUTPUT(amount: $amount, diffAmount: $diffAmount, rule: $rule, explanation: $explanation)\n";
        }

        $diffAmount = $newAmount - $amount;

        return [$diffAmount, $rule, $explanation]; //Returns rewritten rule, has to be remembered
    }

    public function calculationDivisor(bool $debug, int $amount, array $ruleH)
    {
        $divisorAmount = round($amount / $ruleH[3][0]);

        if ($ruleH[1][0] == '-') {
            $newAmount = $amount - $divisorAmount;
            $explanation = 'Subtracting divisor: '.$divisorAmount;

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = $amount + $divisorAmount;
            $explanation = 'Adding divisor: '.$divisorAmount;
        } else {
            //When no sign is given, we reduce the amount. Its lake taking this divisor out of the amount.
            $newAmount = $amount - $divisorAmount;
            $explanation = 'Subtracting divisor: '.$divisorAmount;
        }

        $diffAmount = $newAmount - $amount;

        return [$diffAmount, $explanation];
    }

    public function calculationPercentage(bool $debug, string $amount, array $ruleH)
    {

        $percent = $ruleH[2][0];
        if ($ruleH[1][0] == '-') {
            $newAmount = round($amount * ((-$percent / 100) + 1));
            $explanation = "$amount-$percent%=$newAmount";

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = round($amount * (($percent / 100) + 1));
            $explanation = "$amount+$percent%=$newAmount";
        } else {
            $newAmount = round($amount * (($percent / 100) + 1));
            $explanation = "$amount+$percent%=$newAmount";
        }

        $diffAmount = $newAmount - $amount;

        return [$diffAmount, $explanation];
    }

    public function calculationPlusMinus(bool $debug, int $amount, array $ruleH, int $factor = 1)
    {
        $extraAmount = $ruleH[2][0];
        $calckAmount = round($extraAmount * $factor);

        if ($ruleH[1][0] == '-') {
            $newAmount = $amount - $calckAmount;
            $explanation = "Subtracting: $newAmount = $amount + ($extraAmount * $factor)";

        } elseif ($ruleH[1][0] == '+') {
            $newAmount = $amount + $calckAmount;
            $explanation = "Adding: $newAmount = $amount + ($extraAmount * $factor)";

        }

        $diffAmount = $newAmount - $amount;

        return [$diffAmount, $explanation];
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
