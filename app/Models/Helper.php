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
    public function adjustAmount(bool $debug, ?string $prevAmount, ?string $currentAmount, string $rule = NULL, int $factor = 1){
        #Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor should have memory.

        $newAmount = 0;
        $explanation = '';

        if($debug) {
            print "INPUT( PV: $prevAmount, CV: $currentAmount, rule: $rule, factor: $factor)\n";
        }

        if($this->isRule($currentAmount)) {
            #print "** Amount looks like rule\n";

            #Previous amount has to be an integer from a previous calculation in this case. Only divisor should remember a rule
            list($newAmount, $rule, $explanation) = $this->calculateRule($debug, $prevAmount, $currentAmount, $factor);

        } elseif($rule && is_numeric($currentAmount) && $currentAmount != 0){
            #print "** rule is set: $rule using current amount $currentAmount\n";
            list($newAmount, $rule, $explanation) = $this->calculateRule( $debug, $currentAmount, $rule, $factor);

        } elseif($rule && is_numeric($prevAmount) && $prevAmount != 0){
            #print "** rule is set: $rule using prev amount $prevAmount\n";
            list($newAmount, $rule, $explanation) = $this->calculateRule( $debug, $prevAmount, $rule, $factor);

        } elseif(!$currentAmount) {

            #Set it to the previous amount
            $newAmount = $prevAmount; #Previous amount is already factored, only new amounts has to be factored
            if ($newAmount != null) {
                #print "amount: $amount\n";
                $explanation = "Using previous amount: " . round($newAmount);
            }
        } elseif(is_numeric($currentAmount)) {
            $explanation = "Using current amount: " . round($currentAmount) . " * $factor";
            $newAmount = $currentAmount * $factor;
        } else {
            print "ERROR: currentAmount: $currentAmount not catched by logic";
        }

        if($debug) {
            print "OUTPUT( newAmount: $newAmount, rule: $rule, explanation: $explanation)\n";
        }

        #$newAmount = intval($newAmount * $factor);
        #print "return amountAdjustment($newAmount, $rule, $explanation)\n";
        return [$newAmount, $rule, $explanation]; #Rule is adjusted if it is a divisor, it has to be remembered to the next round
    }

    //$rule has to be a rule, plus, minus, percent, divisor
    public function calculateRule(bool $debug, int $amount, string $rule, int $factor = 1) {

        $newAmount = 0;
        $explanation = null;

        if($rule) {
            if (preg_match('/(\+?|\-?)(\d*)(\%)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #Percentages
                list($newAmount, $rule, $explanation) = $this->calculationPercentage($debug, $amount, $matches);

            } elseif (preg_match('/(\+?|\-?)(\d*)\/(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #Divison ex 1/12
                #NOTE: Only division should have the rule remembered!!!!
                list($newAmount, $rule, $explanation) = $this->calculationDivisor($debug, $amount, $matches);
                #print "--- divisor: ( $newAmount, $rule, $explanation ) \n";

            } elseif (preg_match('/(\+)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with + to be added
                list($newAmount, $rule, $explanation) = $this->calculationAddition($debug, $amount, $rule, $factor);

            } elseif (preg_match('/(\-)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with - to be subtracted
                list($newAmount, $rule, $explanation) = $this->calculationSubtraction($debug, $amount, $rule, $factor);

            } elseif (preg_match('/(\=)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with = Fixed number override

                list($newAmount, $rule, $explanation) = $this->calculationFixed($debug, $amount, $matches, $factor);
            } elseif (is_numeric($rule)) {
                #number that is positive, really starts with a + and should be added when it ends up in rule
                $newAmount = $rule;
                $explanation = 'rule is numeric so amount is set to rule';
            } else {
                print "ERROR: calculateRule #$rule# not supported";
            }
        }

        #print "return calculateRule($newAmount, $rule, $explanation)\n";
        return [$newAmount, $rule, $explanation];
    }

    public function calculationDivisor(bool $debug, string $amount, array $matches) {
        $rule = null;

        $divisorAmount = round($amount / $matches[3][0]);

        if($matches[1][0] == '-') {
            $newAmount = $amount - $divisorAmount;
            $explanation = "Subtracting divisor: " . $divisorAmount;

        } elseif($matches[1][0] == '+') {
            $newAmount = $amount + $divisorAmount;
            $explanation = "Adding divisor: " . $divisorAmount;
        } else {
            #No sign, just give the divisor of the amount
            $newAmount = $divisorAmount;
            $explanation = "Divisor is: " . $divisorAmount;

            #ToDo - maybe not the correct behaviour in all instances? But great for countdown
            $matches[3][0]--; #We reduce the divisor each time we run it, so its the same as the remaining runs.
        }

        if($matches[3][0] > 0) {
            $rule = $matches[1][0] . $matches[2][0] . "/" . $matches[3][0]; #ToDo: Note rule is rewritten for each iteration, will make problems.....
        }

        $explanation .= " new rule: $rule";

        return [$newAmount, $rule, $explanation];
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

    public function calculationAddition(bool $debug, int $amount, int $add, int $factor = 1) {
        $rule = "+$add";

        $newAmount = round($amount + ($add * $factor)); #Should fix both + and -
        $explanation = "Adding: add $newAmount = $amount + ($add * $factor)";
        return [$newAmount, $rule, $explanation];
    }

    public function calculationSubtraction(bool $debug, int $amount, int $subtract, int $factor = 1) {
        $rule = $subtract;

        $newAmount = round($amount + ($subtract * $factor)); #Should fix both + and -
        $explanation = "Subtracting: $subtract";

        if($newAmount < 0) {
            $newAmount = $amount;
            $explanation = "Not subtracting asset gets negative";
        }

        return [$newAmount, $rule, $explanation];
    }

    public function calculationFixed(bool $debug, string $amount, array $matches, int $factor = 1) {
        $rule = null;

        $newAmount = round($matches[2][0] * $factor); #Should fix both + and -
        $explanation = "Fixed number override " . $matches[2][0] . " * $factor=$newAmount";

        return [$newAmount, $rule, $explanation];
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
