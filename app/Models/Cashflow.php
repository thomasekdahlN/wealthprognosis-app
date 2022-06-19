<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cashflow extends Model
{
    use HasFactory;
    public $periodStart;
    public $periodEnd;
    public $config;

    public function __construct($file)
    {

        #print "file: $file\n";
        $this->config = json_decode(readfile($file), true);
        //dd($this->config);


        $this->periodStart = (integer) Arr::get($this->config, 'period.start');
        $this->periodEnd  = (integer) Arr::get($this->config, 'period.end');

        #print_r(Arr::get($config, 'period'));

        #echo "<pre>";
        //dd(Arr::get($this->config, 'assets'));
        foreach(Arr::get($this->config, 'assets') as $asset) {
            $increase = 1; 
            $prevIncrease = 1; 
            $value = 0;
            $prevValue = 0;
            $income = 0;
            $prevIncome = 0;
            $expences = 0;
            $prevExpences = 0;
            $rest = 0;
            $restAccumulated = 0;
            $loan = 0;
            $loanPrev = 0;

            #print_r($asset);

            for ($year = $periodStart; $year <= $periodEnd; $year++) {

                $expences = Arr::get($asset, "expences.$year.value");
                if($expences) {
                    $prevExpences = $expences;
                } else {
                    $expences = $prevExpences;
                }

                $income = Arr::get($asset, "income.$year.value");
                if($income) {
                    $prevIncome = $income;
                } else {
                    $income = $prevIncome;
                }

                $rest = $income - $expences;
                $restAccumulated += $rest;

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
                   $collections [$year]= collect([
                    'year' => $year,
                    'value' => $value,
                    'increase' => $increase,
                    'description' => Arr::get($asset, "value.$year.description"),
                    'income' => $income,
                    'expences' => $expences,
                    'rest' => $income - $expences,
                    'restAccumulated' => $restAccumulated,
                    ]
                );
                $value = $prevValue = $value * $increase;

            }

            $data = array(
                'year_start'    => $periodStart,
                'year_end'      => $periodEnd,
                'loan_amount'   => 1000000,
                'term_years'    => 10,
                'interest'      => 2.02,
                'terms'         => 1
                );

            $amortization = new Amortization($collections, $data);
        }
    }
}
