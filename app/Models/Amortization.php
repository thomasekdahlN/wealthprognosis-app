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

/*
            $mortgageconfig = array(
                'year_start'    => $this->periodStart,
                'year_end'      => $this->periodEnd,
                'loan_amount'   => 1000000,
                'term_years'    => 10,
                'interest'      => 2.02,
                'terms'         => 1
                );
*/
    public function __construct($data, $config, $assettname)
    {
            $this->data         = $data;
            $this->assettname   = $assettname;
            foreach($config as $year => $mortgage) {

                if(!$mortgage) { continue; }
                $this->year_start   = (int) $year;
                $this->year_end     = (int) $year + 20;

#                $this->year_end     = (int) $year + $mortgage['years'];
                #$this->loan_amount  = (float) $mortgage['value'];
                #$this->term_years   = (int) $mortgage['years'];
                #$this->interest     = (float) $mortgage['interest'];
                $this->terms        = (int) 1; //1 termin i året
                
                #$this->terms        = ($this->terms == 0) ? 1 : $this->terms;

                #$this->period       = $this->terms * $this->term_years;
                #$this->interest     = ($this->interest/100) / $this->terms;

                $this->getSchedule();
            }
            #dd($this->data);
    }

    private function calculate($year)
    {
        #handle extra payment
        $paymentExtra = Arr::get($this->data,"$this->assettname.$year.cashflow.amount", 0);

        print "$year: pow((1+ interest: $this->interest), period: $this->period)\n";
        $deno = 1 - 1 / pow((1+ $this->interest),$this->period);

        if($deno > 0) {
            $this->term_pay = ($this->loan_amount * $this->interest) / $deno;
            $interest = $this->loan_amount * $this->interest;

            #$this->principal = $this->term_pay + $paymentExtra - $interest ; //Experimental
            $this->principal = $this->term_pay - $interest; //Normal

            #$this->balance = $this->loan_amount - $this->principal - $paymentExtra;
            $this->balance = $this->loan_amount - $this->principal;

            if($this->balance > 0) {
                $this->data [$this->assettname][$year]['mortgage'] = [
                    'payment' => $this->term_pay,
                    'paymentExtra' => $paymentExtra,
                    'interest' => $this->interest,
                    'interestAmount' => $interest,
                    'principal' => $this->principal,
                    'balance' => $this->balance,
                    'gebyr' => 0,
                    'description' => '',
                ];
            }
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

    public function getSchedule ()
    {
        for ($year = $this->year_start; $year <= $this->year_end; $year++) {

            if($this->balance >= 0) {
                $this->calculate($year);
                //collect(
                //    $this->calculate($year)
                //);
                $this->loan_amount = $this->balance;
                $this->period--;
            }
        }
    ##dd($this->data); 
    }
}
