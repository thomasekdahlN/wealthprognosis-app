<?php

/**
 * AMORTIZATION CALCULATOR
 * https://gist.github.com/pranid/c4f44eff085ae5646e6e52460b15d33e
 *
 * @author PRANEETH NIDARSHAN
 *
 * @version V1.0
 */
class Amortization
{
    private $loan_amount;

    private $term_years;

    private $interest;

    private $terms;

    private $period;

    private $currency = 'XXX';

    private $principal;

    private $balance;

    private $term_pay;

    public function __construct($data)
    {

        $this->loan_amount = (float) $data['loan_amount'];
        $this->term_years = (int) $data['term_years'];
        $this->interest = (float) $data['interest'];
        $this->terms = (int) $data['terms'];

        $this->terms = ($this->terms == 0) ? 1 : $this->terms;

        $this->period = $this->terms * $this->term_years;
        $this->interest = ($this->interest / 100) / $this->terms;

        $results = [
            //'inputs' => $data,
            //'summary' => $this->getSummary(),
            'schedule' => $this->getSchedule(),
        ];

        print_r($results);
    }

    private function calculate($i)
    {
        $deno = 1 - 1 / pow((1 + $this->interest), $this->period);

        $this->term_pay = ($this->loan_amount * $this->interest) / $deno;
        $interest = $this->loan_amount * $this->interest;

        $this->principal = $this->term_pay - $interest;
        $this->balance = $this->loan_amount - $this->principal;

        return [
            'i' => $i,
            'payment' => $this->term_pay,
            'interest' => $interest,
            'principal' => $this->principal,
            'balance' => $this->balance,
        ];
    }

    public function getSummary()
    {
        $this->calculate(0);
        $total_pay = $this->term_pay * $this->period;
        $total_interest = $total_pay - $this->loan_amount;

        return [
            'total_pay' => $total_pay,
            'total_interest' => $total_interest,
        ];
    }

    public function getSchedule()
    {
        $schedule = [];

        $i = 1;
        while ($i <= $this->terms * $this->term_years) {
            array_push($schedule, $this->calculate($i));
            $this->loan_amount = $this->balance;
            $this->period--;
            $i++;
        }

        return $schedule;

    }
}

$data = [
    'loan_amount' => 1000000,
    'term_years' => 10,
    'interest' => 2.02,
    'terms' => 1,
];

$amortization = new Amortization($data);
