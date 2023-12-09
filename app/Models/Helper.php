<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Helper extends Model
{
    use HasFactory;


    /**
    -- "=1000" - Sets the amount equal to this amount
    -- "1000" - Adds 1000 to the amount (evaluates to same as +1000
    -- "+10%" - Adds 10% to amount (Supported now, but syntax : 10)
    -- "+1000" - Adds 1000 to amount
    -- "-10%" - Subtracts 10% from amount
    -- "-1000" - Subtracts 1000 from amount (Supported now - same syntax)
    -- =+1/10" - Adds 1 tenth of the amount yearly
    -- =-1/10" - Subtracts 1 tenth of the amount yearly (To simulate i.e OTP payment). The rest amount will be zero after 10
     */
    public function adjustAmount(bool $debug, ?string $prevAmount, ?string $currentAmount, int $depositedAmount, string $rule = NULL, int $factor = 1){
        #Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor should have memory.

        $newAmount = 0;
        $explanation = '';

        if($debug) {
            print "INPUT( PV: $prevAmount, CV: $currentAmount, rule: $rule, factor: $factor)\n";
        }

        if($this->isRule($currentAmount)) {
            #print "** Amount looks like rule\n";

            #Previous amount has to be an integer from a previous calculation in this case. Only divisor should remember a rule
            list($newAmount, $depositedAmount, $rule, $explanation) = $this->calculateRule($debug, $prevAmount, $depositedAmount, $currentAmount, $factor);

        } elseif($rule && is_numeric($currentAmount) && $currentAmount != 0){
            #print "** rule is set: $rule using current amount $currentAmount\n";
            list($newAmount, $depositedAmount, $rule, $explanation) = $this->calculateRule( $debug, $currentAmount, $depositedAmount, $rule, $factor);

        } elseif($rule && is_numeric($prevAmount) && $prevAmount != 0){
            #print "** rule is set: $rule using prev amount $prevAmount\n";
            list($newAmount, $depositedAmount, $rule, $explanation) = $this->calculateRule( $debug, $prevAmount, $depositedAmount, $rule, $factor);

        } elseif(!$currentAmount) {

            #Set it to the previous amount
            $newAmount = $prevAmount; #Previous amount is already factored, only new amounts has to be factored
            if ($newAmount != null) {
                #print "amount: $amount\n";
                $explanation = "Using previous amount: " . round($newAmount);
            }
        } elseif(is_numeric($currentAmount)) {
            $explanation = "Using current amount: " . round($currentAmount) . " * $factor";
            $newAmount = $depositedAmount = $currentAmount * $factor;

        } else {
            print "ERROR: currentAmount: $currentAmount not catched by logic";
        }

        if($debug) {
            print "OUTPUT( newAmount: $newAmount, rule: $rule, explanation: $explanation)\n";
        }

        #$newAmount = intval($newAmount * $factor);
        #print "return amountAdjustment($newAmount, $rule, $explanation)\n";
        return [$newAmount, $depositedAmount, $rule, $explanation]; #Rule is adjusted if it is a divisor, it has to be remembered to the next round
    }

    //$rule has to be a rule, plus, minus, percent, divisor
    public function calculateRule(bool $debug, int $amount, float $depositedAmount, string $rule, int $factor = 1) {

        $newAmount = 0;
        $explanation = null;

        if($rule) {
            if (preg_match('/(\+?|\-?)(\d*)(\%)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #Percentages
                list($newAmount, $rule, $explanation) = $this->calculationPercentage($debug, $amount, $matches);

            } elseif (preg_match('/(\+?|\-?)(\d*)\/(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #Divison ex 1/12
                #NOTE: Only division should have the rule remembered!!!!
                list($newAmount, $depositedAmount, $rule, $explanation) = $this->calculationDivisor($debug, $amount, $depositedAmount, $matches);
                #print "--- divisor: ( $newAmount, $rule, $explanation ) \n";

            } elseif (preg_match('/(\+)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with + to be added
                list($newAmount, $depositedAmount, $rule, $explanation) = $this->calculationAddition($debug, $amount, $depositedAmount, $rule, $factor);

            } elseif (preg_match('/(\-)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with - to be subtracted
                list($newAmount, $depositedAmount, $rule, $explanation) = $this->calculationSubtraction($debug, $amount, $depositedAmount, $rule, $factor);

            } elseif (preg_match('/(\=)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with = Fixed number override

                list($newAmount, $depositedAmount, $rule, $explanation) = $this->calculationFixed($debug, $amount, $depositedAmount, $matches, $factor);
            } elseif (is_numeric($rule)) {
                #number that is positive, really starts with a + and should be added when it ends up in rule
                $depositedAmount = $rule;
                $newAmount = $rule;
                $explanation = 'rule is numeric so amount is set to rule';
            } else {
                print "ERROR: calculateRule #$rule# not supported";
            }
        }

        #print "return calculateRule($newAmount, $depositedAmount, $rule, $explanation)\n";
        return [$newAmount, $depositedAmount, $rule, $explanation];
    }

    public function calculationDivisor(bool $debug, string $amount, int $depositedAmount, array $matches) {
        $rule = null;

        $divisorAmount = round($amount / $matches[3][0]);

        if($matches[1][0] == '-') {
            $newAmount = $amount - $divisorAmount;
            $depositedAmount -= $divisorAmount;
            $explanation = "Subtracting divisor: " . $divisorAmount;

        } elseif($matches[1][0] == '+') {
            $newAmount = $amount + $divisorAmount;
            $depositedAmount += $divisorAmount;
            $explanation = "Adding divisor: " . $divisorAmount;
        } else {
            #No sign, just give the divisor of the amount
            $newAmount = $divisorAmount;
            $depositedAmount -= $divisorAmount; #We reduce the deposited amounts
            $explanation = "Divisor is: " . $divisorAmount;

            #ToDo - maybe not the correct behaviour in all instances? But great for countdown
            $matches[3][0]--; #We reduce the divisor each time we run it, so its the same as the remaining runs.
        }

        if($matches[3][0] > 0) {
            $rule = $matches[1][0] . $matches[2][0] . "/" . $matches[3][0]; #ToDo: Note rule is rewritten for each iteration, will make problems.....
        }

        $explanation .= " new rule: $rule";

        return [$newAmount, $depositedAmount, $rule, $explanation];
    }

    public function calculationPercentage(bool $debug, string $amount, array $matches) {

        $rule = null; #percentage should not have rule memory
        $percent = $matches[2][0];
        if($matches[1][0] == '-') {
            $newAmount = round($amount  * ((-$percent  / 100) + 1));
            $rule = "-$percent%";
            $explanation = "Subtracting percent: $newAmount";

        } elseif($matches[1][0] == '+') {
            $newAmount = round($amount *  (($percent  / 100) + 1));
            $rule = "+$percent%";
            $explanation = "Adding percent: $newAmount";

        } else {
            #No sign, just give the percentage of the amount
            $newAmount = round($amount *  ($percent  / 100));
            $rule = "$percent%";
            $explanation = "Percent is: $newAmount";
        }

        return [$newAmount, $rule, $explanation];
    }

    public function calculationAddition(bool $debug, int $amount, int $depositedAmount, int $add, int $factor = 1) {
        $rule = "+$add";
        $addAmount = round($add * $factor);
        $newAmount = $amount + $addAmount; #Should fix both + and -
        #print "calculationAddition(depositedAmount: $depositedAmount += $addAmount)\n";

        $depositedAmount += $addAmount;
        $explanation = "Adding: add $newAmount = $amount + ($add * $factor)";
        return [$newAmount, $depositedAmount, $rule, $explanation];
    }

    /**
     * Perform subtraction calculation on the given amount.
     *
     * @param bool $debug Specifies whether to enable debug mode.
     * @param int $amount The original amount.
     * @param int $depositedAmount The amount that has been deposited.
     * @param int $subtract The amount to subtract from the original amount.
     * @param int $factor The factor to multiply by. Defaults to 1.
     * @return array An array containing the new amount, deposited amount, rule, and explanation.
     */
    public function calculationSubtraction(bool $debug, int $amount, int $depositedAmount, int $subtract, int $factor = 1) {
        $rule = $subtract;

        $subtractAmount = round($subtract * $factor);
        $newAmount = $amount + $subtractAmount; #Should fix both + and -
        $depositedAmount += $subtractAmount;
        $explanation = "Subtracting: $subtract";

        if($newAmount < 0) {
            $newAmount = $amount;
            $explanation = "Not subtracting asset gets negative";
        }

        return [$newAmount, $depositedAmount, $rule, $explanation];
    }

    public function calculationFixed(bool $debug, string $amount, int $depositedAmount, array $matches, int $factor = 1) {
        $rule = null;

        $newAmount = $depositedAmount = round($matches[2][0] * $factor); #Should fix both + and -
        $explanation = "Fixed number override " . $matches[2][0] . " * $factor=$newAmount";

        return [$newAmount, $depositedAmount, $rule, $explanation];
    }

    public function isRule(?string $amount)
    {
        #print "isRule: $amount : ";
        if (preg_match('/(\+|\-|\%|\/|\=)/i', $amount, $matches, PREG_OFFSET_CAPTURE)) {
            #print "yes\n";
            return true;
        } elseif(!is_numeric($amount)) {
            #Catches text strings, like variable confirguration
            #print "yes\n";
            return true;
        }

        #print "no\n";
        return false;
    }

    public function isTransfer(?string $transfer) {
        if(preg_match('/(\w+\.\$\w+\.\w+\.\w+)(\*|=)([0-9]*[.]?[0-9]+|amount|diff)/i', $transfer, $matches, PREG_OFFSET_CAPTURE)) {
            return $matches;
        }
        return false;
    }

    public function add(string $assettname, int $year, string $type, array $dataH){
        #$this->dataH[$assettname][$year][$type] = $dataH;
    }

}
