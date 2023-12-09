<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Amortization extends Model
{
    use HasFactory;

    private int $loan_amount;
    private $year_start;
    private $year_end;
    private int $term_years;
    private float $interest;
    private int $terms;
    private int $period;
    private string $currency = "XXX";
    private float $principal;
    private $balance;
    private float $term_pay;
    private array $data;
    private string $assettname;
    private array $dataH = array();

   /**
 * Amortization constructor.
 *
 * This constructor initializes the Amortization object with the provided configuration, change rate, data history, mortgages, and asset name.
 * It then calculates the amortization schedule for each mortgage in the provided mortgages array.
 *
 * @param array $config Configuration array for the amortization calculation.
 * @param object $changerate Object containing the change rate for the loan.
 * @param array $dataH Array containing the data history for the loan.
 * @param array $mortgages Array containing the mortgage details for the loan.
 * @param string $assettname Name of the asset associated with the loan.
 */
public function __construct(array $config, object $changerate, array $dataH, array $mortgages, string $assettname)
{
    $this->dataH = $dataH;
    $this->config = $config;
    $this->assettname = $assettname;
    $this->changerate = $changerate;
    $this->assetChangerateValue = null;

    foreach ($mortgages as $year => $mortgage) {
        $this->year_start = (int)$year;
        $this->term_years = (int)$mortgage['years'];
        $this->loan_amount = (float)$mortgage['amount'];
        $this->terms = 1;
        $this->period = $this->terms * $this->term_years;
        $this->balance = 0;
        $this->year_end = $year + $this->term_years;

        if (isset($mortgages[$year + 1]) && $year + 1 < $this->year_end) {
            $this->year_end = $year;
        }

        $this->getSchedule();
    }
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
    while ($this->balance >= 0 && $this->year_start <= $this->year_end) {
        $this->calculate($this->year_start++);
        $this->loan_amount = $this->balance;
        $this->period--;
    }
    $this->assetChangerateValue = null;
}
    private function calculate(int $year)
    {
        #handle extra payment
        #$paymentExtra = Arr::get($this->mortgageH,"$this->assettname.$year.cashflow.amount", 0); #Håndterer ikke ekstra innbetalinger pr nå

        #New: Retrieving interest pr year.
        list($interestPercent, $interestDecimal, $this->assetChangerateValue, $explanation) = $this->changerate->convertChangerate(true, Arr::get($this->config, "assets.$this->assettname.mortgage.$year.interest"), $year, $this->assetChangerateValue);
        $interestConverted = $interestPercent / 100;

        $deno = 1 - (1 / pow((1 + $interestConverted), $this->period));
        print "##year: $year deno: $deno = 1 - (1 / pow((1+ $interestConverted), $this->period))\n";

        if ($deno > 0) {
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

            print_r($this->dataH[$this->assettname][$year]['mortgage']);
            print "#####\n";

            #Tax calculations
            $amountDeductableYearly = $interestAmount * 0.22; #ToDo: Remove hardcoded percentage later to read from ta x config
            $this->dataH[$this->assettname][$year]['tax']['amountDeductableYearly'] = $amountDeductableYearly;
            if (isset($this->dataH[$this->assettname][$year]['cashflow'])) {
                $this->dataH[$this->assettname][$year]['cashflow']['amount'] = $this->dataH[$this->assettname][$year]['cashflow']['amount'] + $amountDeductableYearly - $this->term_pay;
                $this->dataH[$this->assettname][$year]['cashflow']['amountAccumulated'] = $this->dataH[$this->assettname][$year]['cashflow']['amountAccumulated'] + $amountDeductableYearly - $this->term_pay;  #Cashflow accumulated må reberegnes til slutt???
            }
            print "#####\n";

            if (isset($this->dataH[$this->assettname][$year]['asset'])) {
                $this->dataH[$this->assettname][$year]['asset']['amountLoanDeducted'] -= $this->balance;  #Cashflow accumulated må reberegnes til slutt???
            }
            print "#####$$\n";

            if (isset($this->dataH[$this->assettname][$year]['asset']['amount']) && $this->dataH[$this->assettname][$year]['asset']['amount'] > 0) {
                print "****\n";

                $this->dataH[$this->assettname][$year]['asset']['loanPercentage'] = $this->balance / $this->dataH[$this->assettname][$year]['asset']['amount'];  #Cashflow accumulated må reberegnes til slutt???
            }

            print "#####\n";

            #FIRE
            Arr::set($$this->dataH, "$this->assettname.$year.fire.amountIncome", Arr::get($$this->dataH, "$this->assettname.$year.fire.amountIncome") + $this->principal + $amountDeductableYearly); #Vi legger til avdrag og rentefradrag som inntekt.
            print "****\n";
            Arr::set($$this->dataH, "$this->assettname.$year.fire.amountExpence", Arr::get($$this->dataH, "$this->assettname.$year.fire.amountExpence") + $interestAmount); #Vi legger rentene av lånet som kostnad (ikke totalt innbetalt)
            Arr::set($$this->dataH, "$this->assettname.$year.fire.cashFlow", Arr::get($this->dataH, "$this->assettname.$year.fire.amountIncome") - Arr::get($this->dataH, "$this->assettname.$year.fire.amountExpence"));
            Arr::set($$this->dataH, "$this->assettname.$year.fire.percentDiff", Arr::get($this->dataH, "$this->assettname.$year.fire.amountIncome") / Arr::get($this->dataH, "$this->assettname.$year.fire.amountExpence"));
            Arr::set($$this->dataH, "$this->assettname.$year.fire.savingAmount",$this->principal); #FIRE sparing er bare det du bevisst sparer. Ikke all inntekt som er til overs.

            if (isset($this->dataH[$this->assettname][$year]['income']['amount']) && $this->dataH[$this->assettname][$year]['income']['amount'] > 0) {
                print "****\n";
                $this->dataH[$this->assettname][$year]['fire']['savingRate'] = $this->dataH[$this->assettname][$year]['fire']['savingAmount'] / $this->dataH[$this->assettname][$year]['income']['amount'];
            }
            #print "$year: " . $this->dataH[$this->assettname][$year]['fire']['savingAmount'] . "\n";
            #}
        }
    }

    public function getSummary()
    {
        $this->calculate(0);
        $total_pay = $this->term_pay * $this->period;
        $total_interest = $total_pay - $this->loan_amount;

        return array(
            'total_pay' => $total_pay,
            'total_interest' => $total_interest,
        );
    }

    public function add($year, $type, $row)
    {
        $this->dataH[$this->assettname][$year][$type] = $row;
    }

    public function get()
    {
        return $this->dataH;
        #dd($this->dataH);
    }
}
