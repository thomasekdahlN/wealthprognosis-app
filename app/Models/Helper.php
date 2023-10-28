<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Helper extends Model
{
    use HasFactory;


    /**
    -- "=1000" - Sets the value equal to this value
    -- "1000" - Adds 1000 to the value (evaluates to same as +1000
    -- "+10%" - Adds 10% to value (Supported now, but syntax : 10)
    -- "+1000" - Adds 1000 to value
    -- "-10%" - Subtracts 10% from value
    -- "-1000" - Subtracts 1000 from value (Supported now - same syntax)
    -- =+1/10" - Adds 1 tenth of the amount yearly
    -- =-1/10" - Subtracts 1 tenth of the amount yearly (To simulate i.e OTP payment). The rest amount will be zero after 10
     */
    public function valueAdjustment(bool $debug, ?string $prevValue, ?string $currentValue, string $rule = NULL, int $factor = 1){
        #Careful: This divisor rule thing will be impossible to stop, since it has its own memory. Onlye divisor should have memory.

        $newValue = 0;
        $explanation = '';

        if($debug) {
            print "INPUT( PV: $prevValue, CV: $currentValue, rule: $rule, factor: $factor)\n";
        }

        if($this->isRule($currentValue)) {
            #print "** Value looks like rule\n";

            #Previous value has to be an integer from a previous calculation in this case. Only divisor should remember a rule
            list($newValue, $rule, $explanation) = $this->calculateRule($debug, $prevValue, $currentValue);

        } elseif($rule && is_numeric($currentValue) && $currentValue != 0){
            #print "** rule is set: $rule using current value $currentValue\n";
            list($newValue, $rule, $explanation) = $this->calculateRule( $debug, $currentValue, $rule);

        } elseif($rule && is_numeric($prevValue) && $prevValue != 0){
            #print "** rule is set: $rule using prev value $prevValue\n";
            list($newValue, $rule, $explanation) = $this->calculateRule( $debug, $prevValue, $rule);

        } elseif(!$currentValue) {

            #Set it to the previous value
            $newValue = $prevValue; #Previous value is already factored, only new values has to be factored
            if ($newValue != null) {
                #print "value: $value\n";
                $explanation = "Using previous value: " . round($newValue);
            }
        } elseif(is_numeric($currentValue)) {
            $newValue = $currentValue;
        } else {
            print "ERROR: currentValue: $currentValue not catched by logic";
        }

        if($debug) {
            print "OUTPUT( newValue: $newValue, rule: $rule, explanation: $explanation)\n";
        }

        #$newValue = intval($newValue * $factor);
        #print "return valueAdjustment($newValue, $rule, $explanation)\n";
        return [$newValue, $rule, $explanation]; #Rule is adjusted if it is a divisor, it has to be remembered to the next round
    }

    //$rule has to be a rule, plus, minus, percent, divisor
    public function calculateRule(bool $debug, int $value, string $rule) {

        $newValue = 0;
        $explanation = null;

        if($rule) {
            if (preg_match('/(\+?|\-?)(\d*)(\%)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #Percentages
                list($newValue, $rule, $explanation) = $this->calculationPercentage($debug, $value, $matches);

            } elseif (preg_match('/(\+?|\-?)(\d*)\/(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #Divison ex 1/12
                #NOTE: Only division should have the rule remembered!!!!
                list($newValue, $rule, $explanation) = $this->calculationDivisor($debug, $value, $matches);
                #print "--- divisor: ( $newValue, $rule, $explanation ) \n";

            } elseif (preg_match('/(\+)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with + to be added
                list($newValue, $rule, $explanation) = $this->calculationAddition($debug, $value, $rule);

            } elseif (preg_match('/(\-)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with - to be subtracted
                list($newValue, $rule, $explanation) = $this->calculationSubtraction($debug, $value, $rule);

            } elseif (preg_match('/(\=)(\d*)/i', $rule, $matches, PREG_OFFSET_CAPTURE)) {
                #number that starts with = Fixed number override

                list($newValue, $rule, $explanation) = $this->calculationFixed($debug, $value, $matches);
            } elseif (is_numeric($rule)) {
                #number that is positive, really starts with a + and should be added when it ends up in rule
                $newValue = $rule;
                $explanation = 'rule is numeric so value is set to rule';
            } else {
                print "ERROR: calculateRule #$rule# not supported";
            }
        }

        #print "return calculateRule($newValue, $rule, $explanation)\n";
        return [$newValue, $rule, $explanation];
    }

    public function calculationDivisor(bool $debug, string $value, array $matches) {
        $rule = null;

        $divisorValue = round($value / $matches[3][0]);

        if($matches[1][0] == '-') {
            $newValue = $value - $divisorValue;
            $explanation = "Subtracting divisor: " . $divisorValue;

        } elseif($matches[1][0] == '+') {
            $newValue = $value + $divisorValue;
            $explanation = "Adding divisor: " . $divisorValue;
        } else {
            #No sign, just give the divisor of the value
            $newValue = $divisorValue;
            $explanation = "Divisor is: " . $divisorValue;

            #ToDo - maybe not the correct behaviour in all instances? But great for countdown
            $matches[3][0]--; #We reduce the divisor each time we run it, so its the same as the remaining runs.
        }

        if($matches[3][0] > 0) {
            $rule = $matches[1][0] . $matches[2][0] . "/" . $matches[3][0]; #ToDo: Note rule is rewritten for each iteration, will make problems.....
        }

        $explanation .= " new rule: $rule";

        return [$newValue, $rule, $explanation];
    }

    public function calculationPercentage(bool $debug, string $value, array $matches) {

        $rule = null; #percentage should not have rule memory
        $percent = $matches[2][0];
        if($matches[1][0] == '-') {
            $newValue = round($value  * ((-$percent  / 100) + 1));
            $rule = "-$percent%";
            $explanation = "Subtracting percent: $newValue";

        } elseif($matches[1][0] == '+') {
            $newValue = round($value *  (($percent  / 100) + 1));
            $rule = "+$percent%";
            $explanation = "Adding percent: $newValue";

        } else {
            #No sign, just give the percentage of the value
            $newValue = round($value *  ($percent  / 100));
            $rule = "$percent%";
            $explanation = "Percent is: $newValue";
        }

        return [$newValue, $rule, $explanation];
    }

    public function calculationAddition(bool $debug, int $value, int $add) {
        $rule = "+$add";

        $newValue = $value + $add; #Should fix both + and -
        $explanation = "Adding: add";

        return [$newValue, $rule, $explanation];
    }

    public function calculationSubtraction(bool $debug, int $value, int $subtract) {
        $rule = $subtract;

        $newValue = $value + $subtract; #Should fix both + and -
        $explanation = "Subtracting: $subtract";

        if($newValue < 0) {
            $newValue = $value;
            $explanation = "Not subtracting asset gets negative";
        }

        return [$newValue, $rule, $explanation];
    }

    public function calculationFixed(bool $debug, string $value, array $matches) {
        $rule = null;

        $newValue = round($matches[2][0]); #Should fix both + and -
        $explanation = "Fixed number override =$newValue";

        return [$newValue, $rule, $explanation];
    }

    public function isRule(?string $value)
    {
        #print "isRule: $value : ";
        if (preg_match('/(\+|\-|\%|\/|\=)/i', $value, $matches, PREG_OFFSET_CAPTURE)) {
            #print "yes\n";
            return true;
        } elseif(!is_numeric($value)) {
            #Catches text strings, like variable confirguration
            #print "yes\n";
            return true;
        }

        #print "no\n";
        return false;
    }

    public function isTransfer(?string $transfer) {
        if(preg_match('/(\w+\.\$\w+\.\w+\.\w+)(\*|=)([0-9]*[.]?[0-9]+|value|diff)/i', $transfer, $matches, PREG_OFFSET_CAPTURE)) {
            return $matches;
        }
        return false;
    }

    public function add(string $assettname, int $year, string $type, array $dataH){
        #$this->dataH[$assettname][$year][$type] = $dataH;
    }

}
