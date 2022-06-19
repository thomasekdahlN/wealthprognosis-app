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
    private $assett;

    public function __construct($data, $config, $assett)
    {
            $this->data         = $data;
            $this->assett       = $assett;
            $this->year_start   = (float) $config['year_start'];
            $this->year_end     = (float) $config['year_end'];
            $this->loan_amount  = (float) $config['loan_amount'];
            $this->term_years   = (int) $config['term_years'];
            $this->interest     = (float) $config['interest'];
            $this->terms        = (int) $config['terms'];
            
            $this->terms = ($this->terms == 0) ? 1 : $this->terms;

            $this->period = $this->terms * $this->term_years;
            $this->interest = ($this->interest/100) / $this->terms;

            $results = array(
                #'inputs' => $config,
                #'summary' => $this->getSummary(),
                'schedule' => $this->getSchedule(),
                );

            //dd($this->data);
    }

    private function calculate($year)
    {


        #handle extra payment
        $paymentExtra = Arr::get($this->data,"$this->assett.$year.cashflow.amount", 0);

        print "$year: pow((1+ interest: $this->interest), period: $this->period)\n";
        $deno = 1 - 1 / pow((1+ $this->interest),$this->period);

        $this->term_pay = ($this->loan_amount * $this->interest) / $deno;
        $interest = $this->loan_amount * $this->interest;

        $this->principal = $this->term_pay - $interest + $paymentExtra; //Experimental
        $this->balance = $this->loan_amount - $this->principal;


        $this->data [$this->assett][$year]['mortgage'] = [
            'payment' => $this->term_pay,
            'paymentExtra' => $paymentExtra,
            'interest' => $interest,
            'principal' => $this->principal,
            'balance' => $this->balance,
            'gebyr' => 0,
            'description' => '',
        ];
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

            if($this->period > 0) {
                $this->calculate($year);
                //collect(
                //    $this->calculate($year)
                //);
                $this->loan_amount = $this->balance;
                $this->period--;
            }
        }
    dd($this->data); 
    }
}
