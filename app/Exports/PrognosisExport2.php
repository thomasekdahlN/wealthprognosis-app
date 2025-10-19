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

use App\Models\Core\Changerate;
use App\Models\Core\Prognosis;
use App\Models\Core\TaxFortune;
use App\Models\Core\TaxIncome;
use App\Models\Core\TaxRealization;
use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PrognosisExport2
{
    public $configfile;

    public $config;

    public $tax;

    public $changerate;

    public $birthYear;

    public $economyStartYear;

    public $thisYear;

    public $prognoseYear;

    public $pensionYear;

    public $deathYear;

    public $spreadsheet;

    public $birthRowColor = 'BBBBBB';

    public $economyStartRowColor = 'BBBBBB';

    public $thisYearRowColor = '32CD32';

    public $prognoseYearRowColor = '7FFFD4';

    public $pensionOfficialYearRowColor = 'CCCCCC';

    public $pensionWishYearRowColor = 'FFA500';

    public $deathYearRowColor = 'FFCCCB';

    public $incomeColor = '90EE90';

    public $expenceColor = 'FFCCCB';

    public $cashflowColor = 'ADD8E6';

    public function __construct($configfile, $exportfile, $prognosis, $generate)
    {
        $this->configfile = $configfile;

        $this->spreadsheet = new Spreadsheet;

        $this->spreadsheet->getProperties()
            ->setCreator('Thomas Ekdahl')
            ->setLastModifiedBy('Thomas Ekdahl')
            ->setTitle('Wealth prognosis')
            ->setSubject('Wealth prognosis Subject')
            ->setDescription(
                'Wealth prognosis Description'
            )
            ->setKeywords('Wealth prognosis')
            ->setCategory('Wealth prognosis');

        $this->spreadsheet->removeSheetByIndex(0); // Siden jeg ikke klarte å navne det første
        echo "Leser: '$configfile'\n";
        $content = file_get_contents($configfile);
        $this->config = json_decode($content, true);

        $this->birthYear = (int) Arr::get($this->config, 'meta.birthYear');
        $this->economyStartYear = $this->birthYear + 16; // We look at economy from 16 years of age
        $this->thisYear = now()->year;
        $this->prevYear = $this->thisYear - 1;
        $this->exportStartYear = (int) Arr::get($this->config, 'meta.exportStartYear', $this->prevYear);
        Arr::set($this->config, 'meta.exportStartYear', $this->exportStartYear);
        $this->prognoseYear = (int) $this->birthYear + Arr::get($this->config, 'meta.prognoseYear', 55);
        $this->pensionOfficialYear = (int) $this->birthYear + Arr::get($this->config, 'meta.pensionOfficialYear', 67);
        $this->pensionWishYear = (int) $this->birthYear + Arr::get($this->config, 'meta.pensionWishYear', 63);
        if ($this->pensionWishYear >= $this->birthYear + 63 && $this->pensionWishYear <= $this->birthYear + 67) {
            $this->otpStartYear = $this->pensionWishYear; // OTP begynner tidligst ved 63, senest ved 67 - men slutter på 77 uansett.
        } elseif ($this->pensionWishYear <= $this->birthYear + 63) {
            $this->otpStartYear = $this->birthYear + 62;
        } elseif ($this->pensionWishYear >= $this->birthYear + 67) {
            $this->otpStartYear = $this->birthYear + 67;
        }
        $this->otpEndYear = $this->birthYear + 77; // OTP slutter ved 77 uansett
        $this->otpYears = $this->otpEndYear - $this->otpStartYear + 1;

        $this->deathYear = (int) $this->birthYear + Arr::get($this->config, 'meta.deathYear', 82);
        $this->pensionWishYears = $this->deathYear - $this->pensionWishYear + 1; // The number of years you vil live with pension, used i divisor calculations
        $this->pensionOfficialYears = $this->deathYear - $this->pensionOfficialYear + 1; // The number of years you vil live with pension, used i divisor calculations
        $this->leftYears = $this->deathYear - $this->thisYear + 1; // The number of years until you die, used i divisor calculations
        $this->untilPensionYears = $this->pensionYear - $this->thisYear + 1; // The number of years until pension, used i divisor calculations
        $this->totalYears = $this->deathYear - $this->economyStartYear + 1; // Antall år vi gjør beregningen over
        $this->showYears = $this->deathYear - $this->exportStartYear + 1; // Antall år vi visualiserer beregningen

        // echo "totalYears: $this->totalYears, showYears: $this->showYears, exportStartYear: $this->exportStartYear, economyStartYear: $this->economyStartYear\n";

        // Variable replacement before start - but need to reed some variables before this, therefore generate json twice.
        $content = str_replace(
            ['$birthYear', '$economyStartYear', '$thisYear', '$prognoseYear', '$pensionOfficialYears', '$pensionWishYears', '$pensionOfficialYear', '$pensionWishYear', '$otpStartYear', '$otpEndYear', '$otpYears', '$deathYear', '$leftYears', '$untilPensionYears'],
            [$this->thisYear, $this->economyStartYear, $this->thisYear, $this->prognoseYear, $this->pensionOfficialYears, $this->pensionWishYears, $this->pensionOfficialYear, $this->pensionWishYear, $this->otpStartYear, $this->otpEndYear, $this->otpYears, $this->deathYear, $this->leftYears, $this->untilPensionYears],
            file_get_contents($configfile));

        // print $content;

        $this->config = json_decode($content, true);

        $this->taxincome = new TaxIncome('no/no-tax-2025', $this->economyStartYear, $this->deathYear);
        $this->taxfortune = new TaxFortune('no/no-tax-2025', $this->economyStartYear, $this->deathYear);
        $this->taxrealization = new TaxRealization('no/no-tax-2025', $this->economyStartYear, $this->deathYear);

        $this->changerate = new Changerate($prognosis, $this->economyStartYear, $this->deathYear);

        // print $this->changerate->getChangeratePercent('otp', '2024') . "\n";
        // print $this->changerate->getChangerateDecimal('otp', '2024') . "\n";

        $prognosis = (new Prognosis($this->config, $this->taxincome, $this->taxfortune, $this->taxrealization, $this->changerate));
        // dd($prognosis->privateH);
        $meta = [
            'active' => true,
            'name' => 'Sum total',
            'type' => '',
            'group' => '',
            'description' => 'Total oversikt over din økonomi',
        ];

        echo "generate: $generate\n";
        if ($generate == 'all' or $generate == 'total') {
            $this->page($prognosis->totalH, $meta);
        }

        if ($generate == 'all' or $generate == 'private') {
            $meta['name'] = 'Sum private';
            $this->page($prognosis->privateH, $meta);
        }

        if ($generate == 'all' or $generate == 'company') {
            $meta['name'] = 'Sum holding';
            $this->page($prognosis->companyH, $meta);
        }
        // $this->page($prognosis->groupH, $meta);
        // $this->page($prognosis->labelH, $meta); #New, does not exist yet
        // #####################################################################
        // Generate the spreadsheet pages

        foreach ($prognosis->dataH as $assetname => $asset) {

            if (! $asset['meta']['active']) {
                continue;
            } // Hopp over de inaktive
            if ($generate == 'all' or $generate == $asset['meta']['group']) {
                $this->page($asset, $asset['meta']);
            }
        }

        $assetSpreadSheet = new AssetSpreadSheet($this->spreadsheet, $prognosis->statisticsH);
        $this->spreadsheet->addSheet($assetSpreadSheet->worksheet);
        $this->spreadsheet->setActiveSheetIndexByName('Statistics');
        $sheet = $this->spreadsheet->getActiveSheet();

        // Fix - hva skal vi sette som default når det er totalt dynamisk?
        // $this->spreadsheet->setActiveSheetIndexByName('Total');

        $writer = new Xlsx($this->spreadsheet);
        echo "Lagrer: $exportfile\n";
        $writer->save($exportfile);
    }

    public function page($asset, $meta)
    {

        $prognosisAsset = new PrognosisAssetSheet2($this->spreadsheet, $this->config, $asset, $meta);
        $this->spreadsheet->addSheet($prognosisAsset->worksheet);
        if (! $meta['name']) {
            echo "Asset does not  have a name\n";
            exit;
        }
        // Excel sheet titles must be 31 characters or less - use same truncation as in PrognosisAssetSheet2
        $sheetTitle = strlen($meta['name']) > 31 ? substr($meta['name'], 0, 31) : $meta['name'];
        $this->spreadsheet->setActiveSheetIndexByName($sheetTitle);
        $sheet = $this->spreadsheet->getActiveSheet();

        $meta = [
            'thisYear' => $this->thisYear,
            'prevYear' => $this->prevYear,
            'prognoseYear' => $this->prognoseYear,
            'pensionOfficialYear' => $this->pensionOfficialYear,
            'pensionWishYear' => $this->pensionWishYear,
            'deathYear' => $this->deathYear,
        ];
        \App\Services\ExcelFormatting::applyCommonAssetSheetFormatting($sheet, $meta);

        for ($column = 1; $column <= 34 + 6; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $verticaloffsett = 6;

        // Inntekt - vertikal
        $sheet->getStyle('C6:C'.$this->showYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->incomeColor);

        // Utgift - vertikal
        $sheet->getStyle('E6:E'.$this->showYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->expenceColor);

        // Formue blå - vertikal
        $sheet->getStyle('P6:P'.$this->showYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->cashflowColor);

        // Formuesskatt blå - vertikal
        $sheet->getStyle('W6:W'.$this->showYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->expenceColor);

        // Eiendomsskatt blå - vertikal
        $sheet->getStyle('Y6:Y'.$this->showYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->expenceColor);

        // Realiseringsskatt blå - vertikal
        $sheet->getStyle('AC6:AC'.$this->showYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->expenceColor);

        // Cashflow blå - vertikal
        $sheet->getStyle('AI6:AI'.$this->showYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->cashflowColor);

        // I år - horozontal
        $row = $this->thisYear - $this->prevYear + $verticaloffsett;
        $sheet->getStyle("A$row:AQ$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->thisYearRowColor);

        // Prognosis year - horizontal
        $row = $this->prognoseYear - $this->prevYear + $verticaloffsett;
        if ($row > $verticaloffsett) {
            // print "Prognose year: $row = $this->prognoseYear - $this->prevYear + $verticaloffsett\n";
            $sheet->getStyle("A$row:AQ$row")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->prognoseYearRowColor);
        }

        // Pension official - horizontal
        $row = $this->pensionOfficialYear - $this->prevYear + $verticaloffsett;
        if ($row > $verticaloffsett) {
            // print "Pension official: $row = $this->pensionOfficialYear - $this->prevYear + $verticaloffsett\n";
            $sheet->getStyle("A$row:AQ$row")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->pensionOfficialYearRowColor);
        }

        // Pension wish - horizontal
        $row = $this->pensionWishYear - $this->prevYear + $verticaloffsett;
        if ($row > $verticaloffsett) {
            $sheet->getStyle("A$row:AQ$row")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->pensionOfficialYearRowColor);
        }
        // Deathyear - horizontal
        $row = $this->deathYear - $this->prevYear + $verticaloffsett;
        $sheet->getStyle('A'.$row.':AQ'.$row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->deathYearRowColor);
    }
}
