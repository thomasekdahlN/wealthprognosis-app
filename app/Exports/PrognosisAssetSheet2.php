<?php

namespace App\Exports;

use Illuminate\Support\Arr;

class PrognosisAssetSheet2
{
    private $name;

    private $asset;

    private $meta;

    private $spreadsheet;

    public $worksheet;

    public int $columns = 28;

    public int $rows = 6;

    public int $rowHeader = 5;

    public int $groups = 1;

    public function __construct($spreadsheet, $config, $asset, $meta)
    {
        $this->config = $config;
        $this->asset = $asset;

        $this->spreadsheet = $spreadsheet;

        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, $meta['name']);

        $this->worksheet->setCellValue('A1', $meta['name']);

        $this->worksheet->setCellValue('A2', 'kortnavn');
        $this->worksheet->setCellValue('B2', $meta['name']);
        $this->worksheet->setCellValue('C2', 'Gruppe');
        $this->worksheet->setCellValue('D2', $meta['group']);
        $this->worksheet->setCellValue('E2', 'Type');
        $this->worksheet->setCellValue('F2', $meta['type']);
        $this->worksheet->setCellValue('G2', 'Aktiv');
        $this->worksheet->setCellValue('H2', $meta['active']);
        $this->worksheet->setCellValue('I2', 'Beskrivelse');
        $this->worksheet->setCellValue('J2', $meta['description']);

        //Gruppering av kolonner med navn
        $this->worksheet->setCellValue('C4', 'Cashflow');
        $this->worksheet->setCellValue('I4', 'Lån');
        $this->worksheet->setCellValue('P4', 'Formue');
        $this->worksheet->setCellValue('U4', 'Formuesskatt');
        $this->worksheet->setCellValue('Y4', 'Eiendomsskatt');
        $this->worksheet->setCellValue('AA4', 'Salg');
        $this->worksheet->setCellValue('AE4', 'Cashflow');
        $this->worksheet->setCellValue('AG4', 'Belåningsgrad');
        $this->worksheet->setCellValue('AH4', 'Bank');
        $this->worksheet->setCellValue('AJ4', 'F.I.R.E');

        //Kolonne headinger
        $this->worksheet->setCellValue("A$this->rowHeader", 'År');
        $this->worksheet->setCellValue("B$this->rowHeader", 'Alder');
        $this->worksheet->setCellValue("C$this->rowHeader", 'Inntekt');
        $this->worksheet->setCellValue("D$this->rowHeader", '% Endr');
        $this->worksheet->setCellValue("E$this->rowHeader", 'Utgift');
        $this->worksheet->setCellValue("F$this->rowHeader", '%Endr');
        $this->worksheet->setCellValue("G$this->rowHeader", 'Skatt');
        $this->worksheet->setCellValue("H$this->rowHeader", '% Skatt');
        $this->worksheet->setCellValue("I$this->rowHeader", 'Termin');
        $this->worksheet->setCellValue("J$this->rowHeader", '% Rente');
        $this->worksheet->setCellValue("K$this->rowHeader", 'Rente');
        $this->worksheet->setCellValue("L$this->rowHeader", 'Avdrag');
        $this->worksheet->setCellValue("M$this->rowHeader", 'Rest lån');
        $this->worksheet->setCellValue("N$this->rowHeader", 'Fradrag');
        $this->worksheet->setCellValue("O$this->rowHeader", '% Fradrag');
        $this->worksheet->setCellValue("P$this->rowHeader", 'Markedsverdi');
        $this->worksheet->setCellValue("Q$this->rowHeader", '%Endr');
        $this->worksheet->setCellValue("R$this->rowHeader", 'Markedsverdi fratrukket lån');
        $this->worksheet->setCellValue("S$this->rowHeader", 'Anskaffelsesverdi');
        $this->worksheet->setCellValue("T$this->rowHeader", 'Finans kostnader)');
        $this->worksheet->setCellValue("U$this->rowHeader", 'Skattbar');
        $this->worksheet->setCellValue("V$this->rowHeader", '% skattbar');
        $this->worksheet->setCellValue("W$this->rowHeader", 'Skatt');
        $this->worksheet->setCellValue("X$this->rowHeader", '% Skatt');
        $this->worksheet->setCellValue("Y$this->rowHeader", 'Skatt');
        $this->worksheet->setCellValue("Z$this->rowHeader", '% Skatt');
        $this->worksheet->setCellValue("AA$this->rowHeader", 'Salgsverdi');
        $this->worksheet->setCellValue("AB$this->rowHeader", 'Skatteverdi');
        $this->worksheet->setCellValue("AC$this->rowHeader", 'Skatt');
        $this->worksheet->setCellValue("AD$this->rowHeader", '% Skatt');
        $this->worksheet->setCellValue("AE$this->rowHeader", 'Cashflow');
        $this->worksheet->setCellValue("AF$this->rowHeader", 'Akkumulert');
        $this->worksheet->setCellValue("AG$this->rowHeader", 'Belåningsgrad');
        $this->worksheet->setCellValue("AH$this->rowHeader", 'Betjeningsevne');
        $this->worksheet->setCellValue("AI$this->rowHeader", 'Max lån');
        $this->worksheet->setCellValue("AJ$this->rowHeader", 'Sparing');
        $this->worksheet->setCellValue("AK$this->rowHeader", 'Cashflow');
        $this->worksheet->setCellValue("AL$this->rowHeader", 'Sparerate');
        $this->worksheet->setCellValue("AM$this->rowHeader", 'Description');

        //return;

        foreach ($this->asset as $year => $data) {

            if ($year == 'meta') {
                continue;
            } //Hopp over metadata

            $this->worksheet->setCellValue("A$this->rows", $year);
            $this->worksheet->setCellValue("B$this->rows", (int) $year - Arr::get($this->config, 'meta.birthYear'));
            $this->worksheet->setCellValue("C$this->rows", Arr::get($data, 'income.amount'));
            if (Arr::get($data, 'income.changeratePercent') != 0 && Arr::get($data, 'income.amount') > 0) {
                $this->worksheet->setCellValue("D$this->rows", $this->percentToExcel(Arr::get($data, 'income.changeratePercent')));
            }
            $this->worksheet->setCellValue("E$this->rows", Arr::get($data, 'expence.amount'));
            if (Arr::get($data, 'expence.changeratePercent') != 0 && Arr::get($data, 'expence.amount') > 0) {
                $this->worksheet->setCellValue("F$this->rows", $this->percentToExcel(Arr::get($data, 'expence.changeratePercent')));
            }

            $this->worksheet->setCellValue("G$this->rows", Arr::get($data, 'cashflow.taxAmount'));
            if (Arr::get($data, 'cashflow.taxAmount') != 0) {
                $this->worksheet->setCellValue("H$this->rows", Arr::get($data, 'cashflow.taxDecimal'));
            }
            $this->worksheet->setCellValue("I$this->rows", Arr::get($data, 'mortgage.termAmount'));

            if (Arr::get($data, 'mortgage.interestDecimal') != 0) {
                $this->worksheet->setCellValue("J$this->rows", Arr::get($data, 'mortgage.interestDecimal'));
            }
            $this->worksheet->setCellValue("K$this->rows", Arr::get($data, 'mortgage.interestAmount'));
            $this->worksheet->setCellValue("L$this->rows", Arr::get($data, 'mortgage.principalAmount'));
            $this->worksheet->setCellValue("M$this->rows", Arr::get($data, 'mortgage.balanceAmount'));

            $this->worksheet->setCellValue("N$this->rows", Arr::get($data, 'mortgage.taxDeductableAmount'));

            if (Arr::get($data, 'mortgage.taxDeductableDecimal') != 0) {
                $this->worksheet->setCellValue("O$this->rows", Arr::get($data, 'mortgage.taxDeductableDecimal'));
            }

            $this->worksheet->setCellValue("P$this->rows", Arr::get($data, 'asset.marketAmount'));
            if (Arr::get($data, 'asset.changeratePercent') != 0 && Arr::get($data, 'asset.marketAmount') > 0) {
                $this->worksheet->setCellValue("Q$this->rows", $this->percentToExcel(Arr::get($data, 'asset.changeratePercent')));
            }
            $this->worksheet->setCellValue("R$this->rows", Arr::get($data, 'asset.marketMortgageDeductedAmount'));

            $this->worksheet->setCellValue("S$this->rows", Arr::get($data, 'asset.acquisitionAmount'));
            $this->worksheet->setCellValue("T$this->rows", Arr::get($data, 'asset.paidAmount'));
            $this->worksheet->setCellValue("U$this->rows", Arr::get($data, 'asset.taxableAmount'));

            if (Arr::get($data, 'asset.taxableDecimal') > 0 && Arr::get($data, 'asset.marketAmount') != 0) {
                $this->worksheet->setCellValue("V$this->rows", Arr::get($data, 'asset.taxableDecimal'));
            }
            $this->worksheet->setCellValue("W$this->rows", Arr::get($data, 'asset.taxAmount'));

            if (Arr::get($data, 'asset.marketAmount') != 0) {
                $this->worksheet->setCellValue("X$this->rows", Arr::get($data, 'asset.taxDecimal'));
            }

            $this->worksheet->setCellValue("Y$this->rows", Arr::get($data, 'asset.taxPropertyAmount'));

            if (Arr::get($data, 'asset.taxPropertyAmount') > 0) {
                $this->worksheet->setCellValue("Z$this->rows", Arr::get($data, 'asset.taxPropertyDecimal'));
            }

            $this->worksheet->setCellValue("AA$this->rows", Arr::get($data, 'asset.realizationAmount'));
            $this->worksheet->setCellValue("AB$this->rows", Arr::get($data, 'asset.realizationTaxableAmount'));
            $this->worksheet->setCellValue("AC$this->rows", Arr::get($data, 'asset.realizationTaxAmount'));


            if (Arr::get($data, 'asset.realizationTaxableAmount') != 0) {
                $this->worksheet->setCellValue("AD$this->rows", Arr::get($data, 'asset.realizationTaxDecimal'));
            }

            $this->worksheet->setCellValue("AE$this->rows", Arr::get($data, 'cashflow.afterTaxAmount'));
            $this->worksheet->setCellValue("AF$this->rows", Arr::get($data, 'cashflow.afterTaxAggregatedAmount'));
            if (Arr::get($data, 'asset.mortageRateDecimal') != 0) {
                $this->worksheet->setCellValue("AG$this->rows", Arr::get($data, 'asset.mortageRateDecimal'));
            }
            $this->worksheet->setCellValue("AH$this->rows", Arr::get($data, 'potential.incomeAmount'));
            $this->worksheet->setCellValue("AI$this->rows", Arr::get($data, 'potential.mortgageAmount'));

            $this->worksheet->setCellValue("AJ$this->rows", Arr::get($data, 'fire.savingAmount'));

            $this->worksheet->setCellValue("AK$this->rows", Arr::get($data, 'fire.cashFlow'));
            if (Arr::get($data, 'fire.savingRateDecimal') != 0) {
                $this->worksheet->setCellValue("AL$this->rows", $this->percentToExcel(Arr::get($data, 'fire.savingRateDecimal')));
            }
            $this->worksheet->setCellValue("AM$this->rows", Arr::get($data, 'income.description').Arr::get($data, 'expence.description').Arr::get($data, 'asset.description'));

            $this->rows++;
        }
        $this->rows--;
    }

    //Really to Excel.
    public function percentToExcel(int $percent)
    {

        if ($percent > 0) {
            $decimal = ($percent / 100);
        } elseif ($percent < 0) {
            $decimal = -($percent) / 100;
        } else {
            $decimal = 0;
        }

        return $decimal;
    }
}
