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

            $assetValue = 0;
            $prevAssetValue = 0;
            $assetChangerate = 1; 
            $prevAssetChangerate = 1; 


            $income = 0;
            $prevIncome = 0;
            $incomeChangerate = 1; 
            $prevIncomeChangerate = 1; 

            $expence = 0;
            $prevExpence = 0;
            $expenceChangerate = 1; 
            $prevExpenceChangerate = 1; 

            $rest = 0;
            $restAccumulated = 0;

            #print_r($asset);

            for ($year = $this->periodStart; $year <= $this->periodEnd; $year++) {

                #####################################################
                #Cashflow expence
                $expenceChangerate = Arr::get($asset, "expence.$year.changerate");
                if($expenceChangerate) {
                    $prevExpenceChangerate = $expenceChangerate;
                } else {
                    $expenceChangerate = $prevExpenceChangerate;
                }

                $expence = Arr::get($asset, "expence.$year.value", 0);
                if(!$expence) {
                    $expence = $prevExpence;
                }

                #####################################################
                #Cashflow income
                $incomeChangerate = Arr::get($asset, "income.$year.changerate");
                if($incomeChangerate) {
                    $prevIncomeChangerate = $incomeChangerate;
                } else {
                    $incomeChangerate = $prevIncomeChangerate;
                }

                $income = Arr::get($asset, "income.$year.value", 0);
                if(!$income) {
                    $income = $prevIncome;
                }

                $rest = $income - $expence;
                $restAccumulated += $rest;

                $this->data[$assetname][$year]['income'] = [
                    'changerate' => $incomeChangerate,
                    'income' => $income,
                    'description' => Arr::get($asset, "income.$year.description"),
                    ];

                $this->data[$assetname][$year]['expence'] = [
                    'changerate' => $expenceChangerate,
                    'expence' => $expence,
                    'description' => Arr::get($asset, "expence.$year.description"),
                    ];

                $this->data[$assetname][$year]['cashflow'] = [
                    'amount' => $rest,
                    'amountAccumulated' => $restAccumulated,
                    ];

                #####################################################
                #Assett
                $assetChangerate = Arr::get($asset, "value.$year.changerate");
                if($assetChangerate) {
                    $prevAssetChangerate = $assetChangerate;
                } else {
                    $assetChangerate = $prevAssetChangerate;
                }

                #print_r(Arr::get($asset, "value.$year.increase"));

                $assetValue = Arr::get($asset, "value.$year.value");
                if(!$assetValue) {
                    $assetValue = $prevAssetValue;
                }
                #print "$assetValue = $prevAssetValue = $assetValue * $assetChangerate\n";

                #print_r(Arr::get($asset, "assets.0.value.$year"));
                #exit;
                   $this->data[$assetname][$year]['asset'] = collect([
                    'value' => $assetValue,
                    'changerate' => $assetChangerate,
                    'description' => Arr::get($asset, "value.$year.description"),
                    ]
                );
                #dd($this->data);
                $expence    = $prevExpence      = $expence * $expenceChangerate;
                $income     = $prevIncome       = $income * $incomeChangerate;
                $assetValue = $prevAssetValue   = $assetValue * $assetChangerate;
            }

            #####################################################
            #Loan
            //$this->collections = $this->collections->keyBy('year');
            #dd($this->collections);

            $mortgage = Arr::get($asset, "mortgage");
            #dd($mortgage);

            #$amortization = new Amortization($this->data, $mortgage, $assetname);

            //return $this->collections; #??????
        }


    #dd($this->data);    
    }
}
