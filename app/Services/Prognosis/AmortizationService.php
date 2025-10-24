<?php

/* Copyright (C) 2025 Thomas Ekdahl
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace App\Services\Prognosis;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AmortizationService
{
    private bool $debug;

    private int $amount;

    private $year_start;

    private $year_end;

    private int $term_years;

    private $interest;

    private int $terms;

    private int $period;

    private string $currency = 'XXX';

    private float $principal;

    private $balance;

    private float $term_pay;

    private array $data;

    private float $principalAmount = 0;

    private float $balanceAmount = 0;

    private float $termAmount = 0;

    private string $assettname;

    private array $dataH = [];

    /**
     * Amortization constructor.
     *
     * This constructor initializes the Amortization object with the provided configuration, change rate, data history, mortgages, and asset name.
     * It then calculates the amortization schedule for each mortgage in the provided mortgages array.
     *
     * @param  bool  $debug  Debug flag to enable detailed logging.
     * @param  array  $config  Configuration array for the amortization calculation.
     * @param  object  $changerate  Object containing the change rate for the loan.
     * @param  array  $dataH  Array containing the data history for the loan.
     * @param  array  $mortgages  Array containing the mortgage details for the loan.
     * @param  string  $assettname  Name of the asset associated with the loan.
     */
    public function __construct(bool $debug, array $config, object $changerate, array $dataH, array $mortgage, string $assettname, int $year)
    {
        $this->debug = $debug;
        $this->dataH = $dataH;
        $this->config = $config;
        $this->assettname = $assettname;
        $this->changerate = $changerate;
        $this->assetChangerateValue = null;

        $this->year_start = (int) $year;
        $this->term_years = (int) Arr::get($mortgage, 'years');
        $this->amount = $this->remainingMortgageAmount = (float) Arr::get($mortgage, 'amount');
        $this->interest = Arr::get($mortgage, 'interest');
        $this->terms = 1;
        $this->period = $this->terms * $this->term_years;
        $this->balanceAmount = $this->amount;
        $this->year_end = $year + $this->term_years;
        $this->interestOnlyYears = Arr::get($mortgage, 'interestOnlyYears'); // Antall år med avdragsfrihet. Betaler da kun renter.
        $this->interestOnlyYearEnd = $year + $this->interestOnlyYears; // Antall år med avdragsfrihet. Betaler da kun renter.

        $this->extraDownpaymentAmount = Arr::get($mortgage, 'extraDownpaymentAmount', 0); // Yearly extra downpayment

        if (isset($mortgages[$year + 1]) && $year + 1 < $this->year_end) {
            $this->year_end = $year;
        }

        $this->removeMortgageFrom($this->year_start); // JUst clean up the structure because of extra downpayment faster mortage payment later on will leave traces in not removed properly.
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
            // echo "$this->year_start, period: $this->period, extraDownpaymentAmount: $this->extraDownpaymentAmount\n";
            $this->calculate(false, $this->year_start++, $this->extraDownpaymentAmount);
            $this->remainingMortgageAmount = $this->balanceAmount;
            $this->period--; // Teller ned på antall år i lånet.
            if ($this->interestOnlyYears > 0) {
                $this->interestOnlyYears--; // Teller ned på antall rentefrie år
            }
        }
        $this->assetChangerateValue = null;
    }

    private function calculate(bool $debug, int $year, float $extraDownpaymentAmount = 0)
    {
        $description = null;
        // Retrieving interest pr year.
        [$interestPercent, $interestDecimal, $this->assetChangerateValue, $explanation] = $this->changerate->getChangerate(false, $this->interest, $year, $this->assetChangerateValue);
        $interestDecimal = $interestPercent / 100;

        $deno = 1 - (1 / pow((1 + $interestDecimal), $this->period));
        // print "      ##year: $year deno: $deno = 1 - (1 / pow((1+ $interestDecimal), $this->period))\n";

        if ($deno > 0) {

            $interestAmount = $this->remainingMortgageAmount * $interestDecimal;

            if ($year < $this->interestOnlyYearEnd) {
                // Avdragsfritt lån
                $this->termAmount = $interestAmount; // Terminkostnadene er bare renter
                $this->principalAmount += $extraDownpaymentAmount; // Ingen normale avdrag denne terminen, men ekstra nedbetalign teller som avdrag.
                $this->balanceAmount = $this->remainingMortgageAmount; // Gjenværende lånebeløp er det samme som før terminen siden vi bare har betalt renter
                // echo "    Interest only year: $year: termAmount: $this->termAmount, balanceAmount: $this->balanceAmount \n";
            } else {
                // Avdrag
                $this->termAmount = ($this->remainingMortgageAmount * $interestDecimal) / $deno; // This makes the downpaymet go faster in years (instead of streatching it on the configured years), since we do not take into accoutn extra downpayments. Thats great.
                $this->principalAmount = $this->termAmount - $interestAmount + $extraDownpaymentAmount; // Beregn avdrag denne terminen, extra nedbetalign teller som avdrag.
                $this->balanceAmount = $this->remainingMortgageAmount - $this->principalAmount; // Beregn gjenværende lånebeløp denne terminen
            }

            if ($this->balanceAmount > 0) {

                if ($this->debug) {
                    Log::debug('Mortgage amortization calculation', [
                        'year' => $year,
                        'period' => $this->period,
                        'interest_only_years' => $this->interestOnlyYears,
                        'deno' => $deno,
                        'interest_percent' => $interestPercent,
                        'interest_decimal' => $interestDecimal,
                        'remaining_mortgage_amount' => round($this->remainingMortgageAmount),
                        'term_amount' => round($this->termAmount),
                        'interest_amount' => round($interestAmount),
                        'principal_amount' => round($this->principalAmount),
                        'balance_amount' => round($this->balanceAmount),
                    ]);
                    if (app()->runningInConsole()) {
                        echo "      $year: years: $this->period, interestOnlyYears: $this->interestOnlyYears, deno: $deno : $interestPercent% = $interestDecimal : remainingMortgageAmount: ".round($this->remainingMortgageAmount).' termAmount: '.round($this->termAmount).' : interestAmount '.round($interestAmount).' : principalAmount: '.round($this->principalAmount).' : balanceAmount: '.round($this->balanceAmount)."\n";
                    }
                }
                if ($extraDownpaymentAmount > 0) {
                    $description .= " extraDownpaymentAmount: $extraDownpaymentAmount\n";
                }

                $this->dataH[$this->assettname][$year]['mortgage'] = [
                    'amount' => round($this->amount), // Opprinnelig lånebeløp
                    'termAmount' => round($this->termAmount), // Terminbeløp (pr år)
                    'interest' => $this->assetChangerateValue, // We want the reference to changerates, to be dynamic, not a number.
                    'interestDecimal' => $interestPercent / 100,
                    'interestAmount' => round($interestAmount), // Renter
                    'principalAmount' => round($this->principalAmount), // Avdrag
                    'balanceAmount' => round($this->balanceAmount), // Gjenværende lånebeløpm mappes til amount på reberegning av lån.
                    'extraDownpaymentAmount' => $extraDownpaymentAmount, // Extra innebtaling som er gjort dette året.
                    'years' => $this->period, // Remaining years, if we need to recalculate
                    'interestOnlyYears' => $this->interestOnlyYears, // Remaining years to only pay interest.
                    'gebyrAmount' => 0,
                    'description' => $description,
                    'taxDeductableAmount' => round($interestAmount) * 0.22, // FIX - Should be read from tax config yearly
                    'taxDeductableDecimal' => 0.22, // FIX - Should be read from tax config yearly
                ];
            } else {
                $this->balanceAmount = 0;
                // echo "Lucky you. Mortgage downpayment $this->period years faster that configured\n";
                $this->dataH[$this->assettname][$year]['mortgage'] = [
                    'description' => "Mortgage payed $this->period years faster due to extraDownpayments",
                ];
            }

            // print_r($this->dataH[$this->assettname][$year]['mortgage']);
            // print "$year: " . $this->dataH[$this->assettname][$year]['fire']['savingAmount'] . "\n";
            // }
        } else {
            // Fallback: handle zero or invalid denominator gracefully (e.g., zero/negative interest)
            $interestAmount = max(0.0, $this->remainingMortgageAmount * $interestDecimal);
            // In a fallback scenario, treat as interest-only year; principal only from extra downpayment
            $this->termAmount = $interestAmount;
            $this->principalAmount = max(0.0, $extraDownpaymentAmount);
            $this->balanceAmount = max(0.0, $this->remainingMortgageAmount - $this->principalAmount);

            $this->dataH[$this->assettname][$year]['mortgage'] = [
                'amount' => round($this->amount),
                'termAmount' => round($this->termAmount),
                'interest' => $this->assetChangerateValue,
                'interestDecimal' => $interestPercent / 100,
                'interestAmount' => round($interestAmount),
                'principalAmount' => round($this->principalAmount),
                'balanceAmount' => round($this->balanceAmount),
                'extraDownpaymentAmount' => $extraDownpaymentAmount,
                'years' => $this->period,
                'interestOnlyYears' => $this->interestOnlyYears,
                'gebyrAmount' => 0,
                'description' => 'Fallback calculation used due to invalid denominator.',
                'taxDeductableAmount' => round($interestAmount) * 0.22,
                'taxDeductableDecimal' => 0.22,
            ];
        }
    }

    public function removeMortgageFrom($fromYear)
    {
        $toYear = $fromYear + 80;
        // print "    removeMortgageFrom($this->assettname, $fromYear)\n";

        for ($year = $fromYear; $year <= $toYear; $year++) {
            // print "    Removing mortgage from dataH[$year]\n";
            unset($this->dataH[$this->assettname][$year]['mortgage']);
        }
    }

    public function add($year, $type, $row)
    {
        $this->dataH[$this->assettname][$year][$type] = $row;
    }

    public function get()
    {
        return $this->dataH;
        // dd($this->dataH);
    }
}
