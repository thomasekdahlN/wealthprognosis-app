<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function __construct($dataH, $config, $assettname)
    {
            $this->dataH = $dataH;
            $this->assettname   = $assettname;

            foreach($config as $year => $mortgage) { #OBS på at det kan være loop med mer som forekommer her, det er ikke håndtert.
                if(!$this->year_start) {
                    $this->year_start   = (int) $year;
                    $this->term_years   = (int) Arr::get($mortgage, "years");
                    $this->year_end     = (int) $year + $this->term_years;
                    $this->loan_amount  = (float) Arr::get($mortgage, "value");
                    $this->interest     = (float) Arr::get($mortgage, "interest") / 100;
                    $this->terms        = 1; //1 termin i året pga visningen her
                    $this->period       = (int) $this->terms * $this->term_years;
                }
            }
            #dd($this);
            $this->getSchedule();
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
        #print "$deno = 1 - (1 / pow((1+ $this->interest), $this->period))\n";

        #if($deno > 0) {
            $this->term_pay = ($this->loan_amount * $this->interest) / $deno;
            $interest = $this->loan_amount * $this->interest;

            #$this->principal = $this->term_pay + $paymentExtra - $interest ; //Experimental
            $this->principal = $this->term_pay - $interest; //Normal

            #$this->balance = $this->loan_amount - $this->principal - $paymentExtra;
            $this->balance = $this->loan_amount - $this->principal;

            #if($this->balance > 0) {

                print "$year: $this->period : deno: $deno : $this->interest : loanamount: " . round($this->loan_amount)  . " $this->interest : terminbelop: " . round($this->term_pay)  . " : renter " . round($interest) . " : avdrag: " . round($this->principal) . " : balance: " . round($this->balance) . "\n";

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
                $amountDeductableYearly = $interest * 0.22; #Remove hardcoded percentage later
                $this->dataH[$this->assettname][$year]['tax']['amountDeductableYearly'] = $amountDeductableYearly;
                $this->dataH[$this->assettname][$year]['cashflow']['amount'] = $this->dataH[$this->assettname][$year]['cashflow']['amount'] + $amountDeductableYearly - $this->term_pay;
                $this->dataH[$this->assettname][$year]['cashflow']['amountAccumulated'] = $this->dataH[$this->assettname][$year]['cashflow']['amountAccumulated'] + $amountDeductableYearly - $this->term_pay;  #Cashflow accumulated må reberegnes til slutt???
                $this->dataH[$this->assettname][$year]['asset']['amountLoanDeducted'] -= $this->balance;  #Cashflow accumulated må reberegnes til slutt???
                $this->dataH[$this->assettname][$year]['asset']['loanPercentage'] = $this->balance / $this->dataH[$this->assettname][$year]['asset']['amount'];  #Cashflow accumulated må reberegnes til slutt???


            #}
        #}
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
