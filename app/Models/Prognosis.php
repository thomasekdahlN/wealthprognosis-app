<?php

//Asset, 
//Mortgage, 
//CashFlow



namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prognosis
{
    use HasFactory;
    public $periodStart;
    public $periodEnd;
    public $config;
    public $data = array();

    public function __construct($filename)
    {
        $this->config = json_decode(file_get_contents($filename), true);

        $this->periodStart = (integer) Arr::get($this->config, 'period.start');
        $this->periodEnd  = (integer) Arr::get($this->config, 'period.end');

        foreach(Arr::get($this->config, 'assets') as $assetname => $asset) {
            $increase = 1; 
            $prevIncrease = 1; 
            $value = 0;
            $prevValue = 0;
            $income = 0;
            $prevIncome = 0;
            $expence = 0;
            $prevExpence = 0;
            $rest = 0;
            $restAccumulated = 0;
            $loan = 0;
            $loanPrev = 0;

            #print_r($asset);

            for ($year = $this->periodStart; $year <= $this->periodEnd; $year++) {

                #####################################################
                #Cashflow
                $expences = Arr::get($asset, "expences.$year.value");
                if($expence) {
                    $prevExpence = $expence;
                } else {
                    $expence = $prevExpence;
                }

                $income = Arr::get($asset, "income.$year.value");
                if($income) {
                    $prevIncome = $income;
                } else {
                    $income = $prevIncome;
                }

                $rest = $income - $expence;
                $restAccumulated += $rest;

                $this->data[$assetname][$year]['cashflow'] = [
                    'changerate' => 1.05,
                    'income' => $income,
                    'expence' => $expence,
                    'amount' => $income - $expences,
                    'amountAccumulated' => $restAccumulated,
                    'description' => Arr::get($asset, "value.$year.description"),
                    ];

                #####################################################
                #Assett
                $increase = Arr::get($asset, "value.$year.increase");
                if($increase) {
                    $prevIncrease = $increase;
                } else {
                    $increase = $prevIncrease;
                }

                #print_r(Arr::get($asset, "value.$year.increase"));

                $value = Arr::get($asset, "value.$year.value");
                if(!$value) {
                    $value = $prevValue;
                }
                print "$value = $prevValue = $value * $increase\n";

                #print_r(Arr::get($asset, "assets.0.value.$year"));
                #exit;
                   $this->data[$assetname][$year]['asset'] = collect([
                    'value' => $value,
                    'changerate' => $increase,
                    'description' => Arr::get($asset, "value.$year.description"),
                    ]
                );
                #dd($this->data);
                $value = $prevValue = $value * $increase;
            }

            #####################################################
            #Loan
            //$this->collections = $this->collections->keyBy('year');
            #dd($this->collections);
            $mortgageconfig = array(
                'year_start'    => $this->periodStart,
                'year_end'      => $this->periodEnd,
                'loan_amount'   => 1000000,
                'term_years'    => 10,
                'interest'      => 2.02,
                'terms'         => 1
                );

            $amortization = new Amortization($this->data, $mortgageconfig, $assetname);

            //return $this->collections; #??????
        }


    dd($this->data);    
    }
}
