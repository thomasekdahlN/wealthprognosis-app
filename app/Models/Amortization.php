<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Amortization extends Model
{
    use HasFactory;

    private int $amount;

    private $year_start;

    private $year_end;

    private int $term_years;

    private float $interest;

    private int $terms;

    private int $period;

    private string $currency = 'XXX';

    private float $principal;

    private $balance;

    private float $term_pay;

    private array $data;

    private string $assettname;

    private array $dataH = [];

    /**
     * Amortization constructor.
     *
     * This constructor initializes the Amortization object with the provided configuration, change rate, data history, mortgages, and asset name.
     * It then calculates the amortization schedule for each mortgage in the provided mortgages array.
     *
     * @param  array  $config Configuration array for the amortization calculation.
     * @param  object  $changerate Object containing the change rate for the loan.
     * @param  array  $dataH Array containing the data history for the loan.
     * @param  array  $mortgages Array containing the mortgage details for the loan.
     * @param  string  $assettname Name of the asset associated with the loan.
     */
    public function __construct(array $config, object $changerate, array $dataH, array $mortgage, string $assettname, int $year)
    {
        $this->dataH = $dataH;
        $this->config = $config;
        $this->assettname = $assettname;
        $this->changerate = $changerate;
        $this->assetChangerateValue = null;

        $this->year_start = (int) $year;
        $this->term_years = (int) Arr::get($mortgage, 'years');
        $this->amount = $this->remainingMortgageAmount = (float) Arr::get($mortgage, 'amount');
        $this->terms = 1;
        $this->period = $this->terms * $this->term_years;
        $this->balanceAmount = 0;
        $this->year_end = $year + $this->term_years;

        if (isset($mortgages[$year + 1]) && $year + 1 < $this->year_end) {
            $this->year_end = $year;
        }

        $this->getSchedule();
    }

    /**
     * Calculates the amortization schedule for the loan.
     *
     * This method calculates the amortization schedule for the loan by iterating over each year from the start to the end of the loan.
     * For each year, it checks if the balance of the loan is non-negative. If it is, it calls the `calculate()` method, passing the current year as an argument.
     * After the `calculate()` method is called, the loan amount is updated to be the current balance of the loan, and the period of the loan is decremented by one.
     * Once the amortization schedule has been calculated, the `assetChangerateValue` property is reset to `null`.
     */
    public function getSchedule()
    {
        while ($this->balanceAmount >= 0 && $this->year_start <= $this->year_end) {
            $this->calculate($this->year_start++);
            $this->remainingMortgageAmount = $this->balanceAmount;
            $this->period--;
        }
        $this->assetChangerateValue = null;
    }

    private function calculate(int $year)
    {
        //handle extra payment
        $extraDownpaymentAmount = 0;
        //$extraDownpaymentAmount = Arr::get($this->mortgageH,"$this->assettname.$year.cashflow.amount", 0); #Håndterer ikke ekstra innbetalinger pr nå

        //New: Retrieving interest pr year.
        [$interestPercent, $interestDecimal, $this->assetChangerateValue, $explanation] = $this->changerate->getChangerate(false, Arr::get($this->config, "$this->assettname.$year.mortgage.interest"), $year, $this->assetChangerateValue);
        $interestDecimal = $interestPercent / 100;

        $deno = 1 - (1 / pow((1 + $interestDecimal), $this->period));
        //print "##year: $year deno: $deno = 1 - (1 / pow((1+ $interestDecimal), $this->period))\n";

        if ($deno > 0) {
            $this->termAmount = ($this->remainingMortgageAmount * $interestDecimal) / $deno;
            $interestAmount = $this->remainingMortgageAmount * $interestDecimal;

            //$this->principalAmount = $this->termAmount + $paymentExtra - $interestAmount ; //Experimental
            $this->principalAmount = $this->termAmount - $interestAmount; //Normal

            //$this->balanceAmount = $this->remainingMortgageAmount - $this->principalAmount - $paymentExtra;
            $this->balanceAmount = $this->remainingMortgageAmount - $this->principalAmount;

            //print "$year: $this->period : deno: $deno : $interestDecimal% = $interestDecimal : remainingMortgageAmount: " . round($this->remainingMortgageAmount)  . " termAmount: " . round($this->termAmount)  . " : interestAmount " . round($interestAmount) . " : principalAmount: " . round($this->principalAmount) . " : balanceAmount: " . round($this->balanceAmount) . "\n";
            $this->dataH[$this->assettname][$year]['mortgage'] = [
                'amount' => round($this->amount),
                'termAmount' => round($this->termAmount),
                'interestDecimal' => $interestPercent / 100,
                'interestAmount' => round($interestAmount),
                'principalAmount' => round($this->principalAmount),
                'balanceAmount' => round($this->balanceAmount),
                'extraDownpaymentAmount' => $extraDownpaymentAmount,
                'gebyrAmount' => 0,
                'description' => '',
            ];

        //print_r($this->dataH[$this->assettname][$year]['mortgage']);
        //print "$year: " . $this->dataH[$this->assettname][$year]['fire']['savingAmount'] . "\n";
        //}
        } else {
            echo "Problems with Amortization deno: $deno, interest is probably 0 in config or changerates\n";
        }
    }

    public function getSummary()
    {
        $this->calculate(0);
        $total_pay = $this->termAmount * $this->period;
        $total_interest = $total_pay - $this->remainingMortgageAmount;

        return [
            'total_pay' => $total_pay,
            'total_interest' => $total_interest,
        ];
    }

    public function add($year, $type, $row)
    {
        $this->dataH[$this->assettname][$year][$type] = $row;
    }

    public function get()
    {
        return $this->dataH;
        //dd($this->dataH);
    }
}
