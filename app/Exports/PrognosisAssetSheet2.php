<?php
/* Copyright (C) 2024 Thomas Ekdahl
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
        $this->thisYear = now()->year;

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
        $this->worksheet->setCellValue('C4', 'Inntekt');
        $this->worksheet->setCellValue('E4', 'Utgift');
        $this->worksheet->setCellValue('I4', 'Lån');
        $this->worksheet->setCellValue('P4', 'Formue');
        $this->worksheet->setCellValue('U4', 'Formuesskatt');
        $this->worksheet->setCellValue('Y4', 'Eiendomsskatt');
        $this->worksheet->setCellValue('AA4', 'Salg');
        $this->worksheet->setCellValue('AE4', 'Skjermingsfradrag');
        $this->worksheet->setCellValue('AG4', 'Yield');
        $this->worksheet->setCellValue('AI4', 'Cashflow');
        $this->worksheet->setCellValue('AK4', 'Belåningsgrad');
        $this->worksheet->setCellValue('AL4', 'Bank');
        $this->worksheet->setCellValue('AN4', 'F.I.R.E');

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
        $this->worksheet->setCellValue("AE$this->rowHeader", 'Fradrag');
        $this->worksheet->setCellValue("AF$this->rowHeader", '% Fradrag');
        $this->worksheet->setCellValue("AG$this->rowHeader", 'Brutto'); //Yield
        $this->worksheet->setCellValue("AH$this->rowHeader", 'Netto'); //Yield
        $this->worksheet->setCellValue("AI$this->rowHeader", 'Cashflow');
        $this->worksheet->setCellValue("AJ$this->rowHeader", 'Akkumulert');
        $this->worksheet->setCellValue("AK$this->rowHeader", 'Belåningsgrad');
        $this->worksheet->setCellValue("AL$this->rowHeader", 'Betjeningsevne');
        $this->worksheet->setCellValue("AM$this->rowHeader", 'Max lån');
        $this->worksheet->setCellValue("AN$this->rowHeader", 'Sparing');
        $this->worksheet->setCellValue("AO$this->rowHeader", 'Cashflow');
        $this->worksheet->setCellValue("AP$this->rowHeader", 'Sparerate');
        $this->worksheet->setCellValue("AQ$this->rowHeader", 'Description');

        //return;
        ksort($this->asset); //Sorter på

        foreach ($this->asset as $year => $data) {

            if ($year == 'meta') {
                continue;
            } //Hopp over metadata
            if ($year < $this->thisYear) {
                continue;
            } //Bare generer visuelt fra dette året og fremover. Dette er ikke et historisk verktøy.

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
            $this->worksheet->setCellValue("W$this->rows", Arr::get($data, 'asset.taxFortuneAmount'));

            if (Arr::get($data, 'asset.marketAmount') != 0 && Arr::get($data, 'asset.taxFortuneAmount') > 0) {
                $this->worksheet->setCellValue("X$this->rows", Arr::get($data, 'asset.taxFortuneDecimal'));
            }

            $this->worksheet->setCellValue("Y$this->rows", Arr::get($data, 'asset.taxPropertyAmount'));

            if (Arr::get($data, 'asset.taxPropertyDecimal') != 0 && Arr::get($data, 'asset.taxPropertyAmount') > 0) {
                $this->worksheet->setCellValue("Z$this->rows", Arr::get($data, 'asset.taxPropertyDecimal'));
            }

            $this->worksheet->setCellValue("AA$this->rows", Arr::get($data, 'realization.amount'));
            $this->worksheet->setCellValue("AB$this->rows", Arr::get($data, 'realization.taxableAmount'));
            $this->worksheet->setCellValue("AC$this->rows", Arr::get($data, 'realization.taxAmount'));

            if (Arr::get($data, 'realization.taxDecimal') != 0 && Arr::get($data, 'realization.taxableAmount') != 0) {
                $this->worksheet->setCellValue("AD$this->rows", Arr::get($data, 'realization.taxDecimal'));
            }

            $this->worksheet->setCellValue("AE$this->rows", Arr::get($data, 'realization.taxShieldAmount'));

            if (Arr::get($data, 'realization.taxShieldDecimal') != 0 && Arr::get($data, 'realization.taxShieldAmount') != 0) {
                $this->worksheet->setCellValue("AF$this->rows", Arr::get($data, 'realization.taxShieldDecimal'));
            }

            if (Arr::get($data, 'yield.bruttoPercent') != 0) {
                $this->worksheet->setCellValue("AG$this->rows", $this->percentToExcel(Arr::get($data, 'yield.bruttoPercent')));
            }
            if (Arr::get($data, 'yield.nettoPercent') != 0) {
                $this->worksheet->setCellValue("AH$this->rows", $this->percentToExcel(Arr::get($data, 'yield.nettoPercent')));
            }

            $this->worksheet->setCellValue("AI$this->rows", Arr::get($data, 'cashflow.afterTaxAmount'));
            $this->worksheet->setCellValue("AJ$this->rows", Arr::get($data, 'cashflow.afterTaxAggregatedAmount'));
            if (Arr::get($data, 'asset.mortageRateDecimal') != 0) {
                $this->worksheet->setCellValue("AK$this->rows", Arr::get($data, 'asset.mortageRateDecimal'));
            }
            $this->worksheet->setCellValue("AL$this->rows", Arr::get($data, 'potential.incomeAmount'));
            $this->worksheet->setCellValue("AM$this->rows", Arr::get($data, 'potential.mortgageAmount'));

            $this->worksheet->setCellValue("AN$this->rows", Arr::get($data, 'fire.savingAmount'));

            $this->worksheet->setCellValue("AO$this->rows", Arr::get($data, 'fire.cashFlow'));
            if (Arr::get($data, 'fire.savingRateDecimal') != 0) {
                $this->worksheet->setCellValue("AP$this->rows", $this->percentToExcel(Arr::get($data, 'fire.savingRateDecimal')));
            }
            $this->worksheet->setCellValue("AQ$this->rows", Arr::get($data, 'income.description').Arr::get($data, 'expence.description').Arr::get($data, 'cashflow.description').Arr::get($data, 'asset.description').Arr::get($data, 'realization.description').Arr::get($data, 'mortgage.description'));

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
