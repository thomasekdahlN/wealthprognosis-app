<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Amortization extends Model
{
    use HasFactory;

    private $loan_amount;
    private $year_start;
    private $year_end;
    private $term_years;
    private $interest;
    private $terms;
    private $period;
    private $currency = "XXX";
    private $principal;
    private $balance;
    private $term_pay;
    private $data;
    private $assettname;
    private $dataH = array();

    public function __construct($config, $changerate, $dataH, $mortgages, $assettname)
    {
            $this->dataH = $dataH;
            $this->config = $config;
            $this->assettname   = $assettname;
            $this->changerate = $changerate;

            $keys = array_keys( $mortgages );
            $size = sizeof($keys);

            for($x = 0; $x < $size; $x++ ) {
            #foreach($mortgages as $year => $mortgage) { #OBS på at det kan være loop med mer som forekommer her, det er ikke håndtert.
                $year = $keys[$x]; #+1
                #dd($mortgages);

                #Reset for each loop
                $this->year_start   = (int) $year;
                $this->term_years   = (int) Arr::get($mortgages, "$year.years");
                $this->loan_amount  = (float) Arr::get($mortgages, "$year.value");
                $this->interest     = $this->percentToDecimal2(Arr::get($mortgages, "$year.interest"));
                $this->terms        = 1; //1 termin i året pga visningen her
                $this->period       = (int) $this->terms * $this->term_years;
                $this->balance      = 0;
                $this->year_end     = $year + $this->term_years;

                #$next_year = $keys[$x+1]; #Look ahead, but the last element in array will not exist

                if(isset($keys[$x+1]) && $keys[$x+1] < $this->year_end) { #If a next mortgage year exists
                    #Overwrite year end in this scenario, since the nextmortgage starts before the first one is finished
                    $this->year_end = $keys[$x+1] - 1;  #Asset has multiple mortgages that has to be recalculated, but they may not overlap in time, and they have to be stopped the year before the next starts
                }
                #print "$x: from: $this->year_start, to: $this->year_end, year: $year\n";
                #Calculate
                $this->getSchedule();
            }
            #dd($this);

    }

    public function getSchedule ()
    {
        for ($year = $this->year_start; $year <= $this->year_end; $year++) {

            if($this->balance >= 0) {
                $this->calculate($year);
                $this->loan_amount = $this->balance;
                $this->period--;
            }
        }
    }

    private function calculate($year)
    {
        #handle extra payment
        #$paymentExtra = Arr::get($this->mortgageH,"$this->assettname.$year.cashflow.amount", 0); #Håndterer ikke ekstra innbetalinger pr nå

        $deno = 1 - (1 / pow((1+ $this->interest), $this->period));
        #print "##year: $year deno: $deno = 1 - (1 / pow((1+ $this->interest), $this->period))\n";

        if($deno > 0) {
            $this->term_pay = ($this->loan_amount * $this->interest) / $deno;
            $interest = $this->loan_amount * $this->interest;

            #$this->principal = $this->term_pay + $paymentExtra - $interest ; //Experimental
            $this->principal = $this->term_pay - $interest; //Normal

            #$this->balance = $this->loan_amount - $this->principal - $paymentExtra;
            $this->balance = $this->loan_amount - $this->principal;

            #if($this->balance > 0) {

                #print "$year: $this->period : deno: $deno : $this->interest : loanamount: " . round($this->loan_amount)  . " $this->interest : terminbelop: " . round($this->term_pay)  . " : renter " . round($interest) . " : avdrag: " . round($this->principal) . " : balance: " . round($this->balance) . "\n";
                $this->dataH[$this->assettname][$year]['mortgage'] = [
                        'payment' => $this->term_pay,
                        'interest' => $this->interest,
                        'interestAmount' => $interest,
                        'principal' => $this->principal,
                        'balance' => $this->balance,
                        'gebyr' => 0,
                        'description' => '',
                    ];

                #Tax calculations
                $amountDeductableYearly = $interest * 0.22; #FIX: Remove hardcoded percentage later to read from ta x config
                $this->dataH[$this->assettname][$year]['tax']['amountDeductableYearly'] = $amountDeductableYearly;
                if(isset($this->dataH[$this->assettname][$year]['cashflow'])) {
                    $this->dataH[$this->assettname][$year]['cashflow']['amount'] = $this->dataH[$this->assettname][$year]['cashflow']['amount'] + $amountDeductableYearly - $this->term_pay;
                    $this->dataH[$this->assettname][$year]['cashflow']['amountAccumulated'] = $this->dataH[$this->assettname][$year]['cashflow']['amountAccumulated'] + $amountDeductableYearly - $this->term_pay;  #Cashflow accumulated må reberegnes til slutt???
                }
                if(isset($this->dataH[$this->assettname][$year]['asset'])) {
                    $this->dataH[$this->assettname][$year]['asset']['amountLoanDeducted'] -= $this->balance;  #Cashflow accumulated må reberegnes til slutt???
                }
                if($this->dataH[$this->assettname][$year]['asset']['amount'] > 0) {
                    $this->dataH[$this->assettname][$year]['asset']['loanPercentage'] = $this->balance / $this->dataH[$this->assettname][$year]['asset']['amount'];  #Cashflow accumulated må reberegnes til slutt???
                }

                $this->dataH[$this->assettname][$year]['fire']['amountIncome'] += $this->principal + $amountDeductableYearly; #Vi legger til avdrag og rentefradrag som inntekt.
                $this->dataH[$this->assettname][$year]['fire']['amountExpence'] += $interest; #Vi legger rentene av lånet som kostnad (ikke totalt innbetalt)
                $this->dataH[$this->assettname][$year]['fire']['cashFlow'] = $this->dataH[$this->assettname][$year]['fire']['amountIncome'] - $this->dataH[$this->assettname][$year]['fire']['amountExpence'];
                $this->dataH[$this->assettname][$year]['fire']['percentDiff'] = $this->dataH[$this->assettname][$year]['fire']['amountIncome'] / $this->dataH[$this->assettname][$year]['fire']['amountExpence'];
                $this->dataH[$this->assettname][$year]['fire']['savingRate'] = ($this->dataH[$this->assettname][$year]['fire']['amountIncome'] - $this->dataH[$this->assettname][$year]['fire']['amountExpence']) / $this->dataH[$this->assettname][$year]['fire']['amountIncome'];
            #}
        }
    }

    public function getSummary()
    {
        $this->calculate(0);
        $total_pay = $this->term_pay *  $this->period;
        $total_interest = $total_pay - $this->loan_amount;

        return array (
            'total_pay' => $total_pay,
            'total_interest' => $total_interest,
            );
    }



    public function add($year, $type, $row){
        $this->dataH[$this->assettname][$year][$type] = $row;
    }

    public function get(){
        return $this->dataH;
        #dd($this->dataH);
    }

    public function percentToDecimal2($percent){

        #print "percent: $percent\n";
        if($percent != null && Str::isAscii($percent)) { #Allow to read the numbers from a config
            preg_match('/changerates.(\w*)/i', $percent, $matches, PREG_OFFSET_CAPTURE);
            $percent = Arr::get($this->changerate, $matches[1][0], null);
        }

        if($percent != null && is_numeric($percent)) { #Allow numbers directly
            return ($percent / 100);
        } else {
            return 0; #We need zero in return for tests to be correct, if we found no value
        }
    }
}
