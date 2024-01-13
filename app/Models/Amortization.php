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
        $this->balanceAmount = $this->amount;
        $this->year_end = $year + $this->term_years;
        $this->extraDownpaymentAmount = Arr::get($mortgage, 'extraDownpaymentAmount', 0); //Yearly extra downpayment

        if (isset($mortgages[$year + 1]) && $year + 1 < $this->year_end) {
            $this->year_end = $year;
        }

        $this->calculateAmortizationSchedule();
    }

    /**
     * Calculates the amortization schedule for the loan.
     *
     * This method calculates the amortization schedule for the loan by iterating over each year from the start to the end of the loan.
     * For each year, it checks if the balance of the loan is non-negative. If it is, it calls the `calculate()` method, passing the current year as an argument.
     * After the `calculate()` method is called, the loan amount is updated to be the current balance of the loan, and the period of the loan is decremented by one.
     * Once the amortization schedule has been calculated, the `assetChangerateValue` property is reset to `null`.
     */
    public function calculateAmortizationSchedule()
    {
        while ($this->balanceAmount > 0 && $this->year_start <= $this->year_end) {
            echo "$this->year_start, period: $this->period, extraDownpaymentAmount: $this->extraDownpaymentAmount\n";
            $this->calculate(true, $this->year_start++, $this->extraDownpaymentAmount);
            $this->remainingMortgageAmount = $this->balanceAmount;
            $this->period--;
        }
        $this->assetChangerateValue = null;
    }

    private function calculate(bool $debug, int $year, float $extraDownpaymentAmount = 0)
    {
        $description = null;
        //Retrieving interest pr year.
        [$interestPercent, $interestDecimal, $this->assetChangerateValue, $explanation] = $this->changerate->getChangerate(false, Arr::get($this->config, "$this->assettname.$year.mortgage.interest"), $year, $this->assetChangerateValue);
        $interestDecimal = $interestPercent / 100;

        $deno = 1 - (1 / pow((1 + $interestDecimal), $this->period));
        //print "##year: $year deno: $deno = 1 - (1 / pow((1+ $interestDecimal), $this->period))\n";

        if ($deno > 0) {
            $this->termAmount = ($this->remainingMortgageAmount * $interestDecimal) / $deno; //This makes the downpaymet go faster in years (instead of streatching it on the configured years), since we do not take into accoutn extra downpayments. Thats great.
            $interestAmount = $this->remainingMortgageAmount * $interestDecimal;

            $this->principalAmount = $this->termAmount - $interestAmount + $extraDownpaymentAmount; //Beregn avdrag denne terminen, extra nedbetalign teller som avdrag.
            $this->balanceAmount = $this->remainingMortgageAmount - $this->principalAmount; //Beregn gjenværende lånebeløp denne terminen
            if ($this->balanceAmount > 0) {

                if ($debug) {
                    echo "$year: $this->period : deno: $deno : $interestDecimal% = $interestDecimal : remainingMortgageAmount: " . round($this->remainingMortgageAmount) . ' termAmount: ' . round($this->termAmount) . ' : interestAmount ' . round($interestAmount) . ' : principalAmount: ' . round($this->principalAmount) . ' : balanceAmount: ' . round($this->balanceAmount) . "\n";
                }
                if ($extraDownpaymentAmount > 0) {
                    $description .= " extraDownpaymentAmount: $extraDownpaymentAmount\n";
                }

                $this->dataH[$this->assettname][$year]['mortgage'] = [
                    'amount' => round($this->amount), //Opprinnelig lånebeløp
                    'termAmount' => round($this->termAmount), //Terminbeløp (pr år)
                    'interest' => $interestPercent,
                    'interestDecimal' => $interestPercent / 100,
                    'interestAmount' => round($interestAmount), //Renter
                    'principalAmount' => round($this->principalAmount), //Avdrag
                    'balanceAmount' => round($this->balanceAmount), //Gjenværende lånebeløpm mappes til amount på reberegning av lån.
                    'extraDownpaymentAmount' => $extraDownpaymentAmount, //Extra innebtaling som er gjort dette året.
                    'years' => $this->period, //Remaining years, if we need to recalculate
                    'gebyrAmount' => 0,
                    'description' => $description,
                ];
            }    else {
                $this->balanceAmount = 0;
                echo "Lucky you. Mortgage downpayment $this->period years faster that configured\n";
                $this->dataH[$this->assettname][$year]['mortgage'] = [
                    'description' => "Mortgage payed $this->period years faster due to extraDownpayments",
                ];
        }

        //print_r($this->dataH[$this->assettname][$year]['mortgage']);
            //print "$year: " . $this->dataH[$this->assettname][$year]['fire']['savingAmount'] . "\n";
            //}
        } else {
            echo "Problems with Amortization deno: $deno, interest is probably 0 in config or changerates\n";
        }
    }

    public function getSummaryXXXX()
    {
        $this->calculate(0); //FIX??????
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
