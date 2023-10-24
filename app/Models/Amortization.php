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

    public function __construct(array $config, object $changerate, array $dataH, array $mortgages, string $assettname)
    {
            $this->dataH = $dataH;
            $this->config = $config;
            $this->assettname   = $assettname;
            $this->changerate = $changerate;
            $this->assetChangerateValue = null;

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
        $this->assetChangerateValue = null; #Reset interest memory fro each new calculation.
    }

    private function calculate(int $year)
    {
        #handle extra payment
        #$paymentExtra = Arr::get($this->mortgageH,"$this->assettname.$year.cashflow.amount", 0); #Håndterer ikke ekstra innbetalinger pr nå

        #New: Retrieving interest pr year.
        list($interestPercent, $interestDecimal, $this->assetChangerateValue, $explanation) = $this->changerate->convertChangerate(0, Arr::get($this->config, "assets.$this->assettname.mortgage.$year.interest"), $year, $this->assetChangerateValue);
        $interestConverted = $interestPercent / 100;

        $deno = 1 - (1 / pow((1+ $interestConverted), $this->period));
        print "##year: $year deno: $deno = 1 - (1 / pow((1+ $interestConverted), $this->period))\n";

        if($deno > 0) {
            $this->term_pay = ($this->loan_amount * $interestConverted) / $deno;
            $interestAmount = $this->loan_amount * $interestConverted;

            #$this->principal = $this->term_pay + $paymentExtra - $interestAmount ; //Experimental
            $this->principal = $this->term_pay - $interestAmount; //Normal

            #$this->balance = $this->loan_amount - $this->principal - $paymentExtra;
            $this->balance = $this->loan_amount - $this->principal;

            #if($this->balance > 0) {

                print "$year: $this->period : deno: $deno : $interestPercent% = $interestConverted : loanamount: " . round($this->loan_amount)  . " terminbelop: " . round($this->term_pay)  . " : renter " . round($interestAmount) . " : avdrag: " . round($this->principal) . " : balance: " . round($this->balance) . "\n";
                $this->dataH[$this->assettname][$year]['mortgage'] = [
                        'payment' => $this->term_pay,
                        'interestPercent' => $interestPercent / 100,
                        'interestAmount' => $interestAmount,
                        'principal' => $this->principal,
                        'balance' => $this->balance,
                        'gebyr' => 0,
                        'description' => '',
                    ];

                #Tax calculations
                $amountDeductableYearly = $interestAmount * 0.22; #ToDo: Remove hardcoded percentage later to read from ta x config
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

                #FIRE
                $this->dataH[$this->assettname][$year]['fire']['amountIncome'] += $this->principal + $amountDeductableYearly; #Vi legger til avdrag og rentefradrag som inntekt.
                $this->dataH[$this->assettname][$year]['fire']['amountExpence'] += $interestAmount; #Vi legger rentene av lånet som kostnad (ikke totalt innbetalt)
                $this->dataH[$this->assettname][$year]['fire']['cashFlow'] = $this->dataH[$this->assettname][$year]['fire']['amountIncome'] - $this->dataH[$this->assettname][$year]['fire']['amountExpence'];
                $this->dataH[$this->assettname][$year]['fire']['percentDiff'] = $this->dataH[$this->assettname][$year]['fire']['amountIncome'] / $this->dataH[$this->assettname][$year]['fire']['amountExpence'];
                $this->dataH[$this->assettname][$year]['fire']['savingAmount'] = $this->principal; #FIRE sparing er bare det du bevisst sparer. Ikke all inntekt som er til overs.

                if($this->dataH[$this->assettname][$year]['income']['amount'] > 0) {
                    $this->dataH[$this->assettname][$year]['fire']['savingRate'] = $this->dataH[$this->assettname][$year]['fire']['savingAmount'] / $this->dataH[$this->assettname][$year]['income']['amount'];
                }
                #print "$year: " . $this->dataH[$this->assettname][$year]['fire']['savingAmount'] . "\n";
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
}
