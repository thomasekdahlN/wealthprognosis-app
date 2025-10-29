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

use App\Services\Prognosis\PrognosisService;
use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PrognosisExport2
{
    public string $configfile;

    /** @var array<string, mixed> */
    public array $config;

    /** @var array<string, mixed> */
    public array $tax;

    /** @var array<string, mixed> */
    public array $changerate;

    public int $birthYear;

    public int $economyStartYear;

    public int $thisYear;

    public int $prognoseYear;

    public int $deathYear;

    public Spreadsheet $spreadsheet;

    public string $country;

    public int $prevYear;

    public int $exportStartYear;

    public int $pensionOfficialYear;

    public int $pensionWishYear;

    public int $otpStartYear;

    public int $otpEndYear;

    public int $otpYears;

    public int $pensionWishYears;

    public int $pensionOfficialYears;

    public int $leftYears;

    public int $untilPensionYears;

    public int $totalYears;

    public int $showYears;

    public string $birthRowColor = 'BBBBBB';

    public string $economyStartRowColor = 'BBBBBB';

    public string $thisYearRowColor = '32CD32';

    public string $prognoseYearRowColor = '7FFFD4';

    public string $pensionOfficialYearRowColor = 'CCCCCC';

    public string $pensionWishYearRowColor = 'FFA500';

    public string $deathYearRowColor = 'FFCCCB';

    public string $incomeColor = '90EE90';

    public string $expenceColor = 'FFCCCB';

    public string $cashflowColor = 'ADD8E6';

    public function __construct(string $configfile, string $exportfile, string $prognosisType, string $generate)
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

        // Validate file exists
        if (! file_exists($configfile)) {
            $errorMsg = "\n❌ ERROR: Configuration file not found!\n";
            $errorMsg .= "   File: {$configfile}\n";
            $errorMsg .= "   Please check that the file path is correct and the file exists.\n\n";
            echo $errorMsg;
            throw new \InvalidArgumentException("Configuration file not found: {$configfile}");
        }

        echo "Leser: '$configfile'\n";

        // Read file content
        $content = file_get_contents($configfile);
        if ($content === false) {
            $errorMsg = "\n❌ ERROR: Failed to read configuration file!\n";
            $errorMsg .= "   File: {$configfile}\n";
            $errorMsg .= "   Please check file permissions.\n\n";
            echo $errorMsg;
            throw new \RuntimeException("Failed to read configuration file: {$configfile}");
        }

        // Decode JSON
        $this->config = json_decode($content, true);

        // Validate JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = "\n❌ ERROR: Invalid JSON in configuration file!\n";
            $errorMsg .= "   File: {$configfile}\n";
            $errorMsg .= '   JSON Error: '.json_last_error_msg()."\n";
            $errorMsg .= "   Please validate your JSON file using a JSON validator.\n";
            $errorMsg .= "   Common issues:\n";
            $errorMsg .= "   - Missing or extra commas\n";
            $errorMsg .= "   - Unquoted keys or values\n";
            $errorMsg .= "   - Trailing commas before } or ]\n";
            $errorMsg .= "   - Invalid escape sequences\n\n";
            echo $errorMsg;
            throw new \InvalidArgumentException("Invalid JSON in file {$configfile}: ".json_last_error_msg());
        }

        if (! is_array($this->config)) {
            $errorMsg = "\n❌ ERROR: JSON content must be an object/array!\n";
            $errorMsg .= "   File: {$configfile}\n";
            $errorMsg .= "   The JSON file must contain a valid configuration object.\n\n";
            echo $errorMsg;
            throw new \InvalidArgumentException("JSON content must be an object/array in file: {$configfile}");
        }

        $this->birthYear = (int) Arr::get($this->config, 'meta.birthYear');
        $this->country = (int) Arr::get($this->config, 'meta.country');
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
        $this->untilPensionYears = $this->pensionWishYear - $this->thisYear + 1; // The number of years until pension, used i divisor calculations
        $this->totalYears = $this->deathYear - $this->economyStartYear + 1; // Antall år vi gjør beregningen over
        $this->showYears = $this->deathYear - $this->exportStartYear + 1; // Antall år vi visualiserer beregningen

        // echo "totalYears: $this->totalYears, showYears: $this->showYears, exportStartYear: $this->exportStartYear, economyStartYear: $this->economyStartYear\n";

        // Variable replacement before start - but need to reed some variables before this, therefore generate json twice.
        $rawContent = file_get_contents($configfile);
        if ($rawContent === false) {
            $errorMsg = "\n❌ ERROR: Failed to read configuration file for variable replacement!\n";
            $errorMsg .= "   File: {$configfile}\n";
            $errorMsg .= "   Please check file permissions.\n\n";
            echo $errorMsg;
            throw new \RuntimeException("Failed to read configuration file: {$configfile}");
        }

        $content = str_replace(
            ['$birthYear', '$economyStartYear', '$thisYear', '$prognoseYear', '$pensionOfficialYears', '$pensionWishYears', '$pensionOfficialYear', '$pensionWishYear', '$otpStartYear', '$otpEndYear', '$otpYears', '$deathYear', '$leftYears', '$untilPensionYears'],
            [(string) $this->thisYear, (string) $this->economyStartYear, (string) $this->thisYear, (string) $this->prognoseYear, (string) $this->pensionOfficialYears, (string) $this->pensionWishYears, (string) $this->pensionOfficialYear, (string) $this->pensionWishYear, (string) $this->otpStartYear, (string) $this->otpEndYear, (string) $this->otpYears, (string) $this->deathYear, (string) $this->leftYears, (string) $this->untilPensionYears],
            $rawContent);

        // print $content;

        $this->config = json_decode($content, true);

        // Validate JSON after variable replacement
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = "\n❌ ERROR: Invalid JSON after variable replacement!\n";
            $errorMsg .= "   File: {$configfile}\n";
            $errorMsg .= '   JSON Error: '.json_last_error_msg()."\n";
            $errorMsg .= "   This may be caused by invalid variable placeholders in the JSON.\n";
            $errorMsg .= "   Please check that all $ variables are properly formatted.\n\n";
            echo $errorMsg;
            throw new \InvalidArgumentException("Invalid JSON after variable replacement in file {$configfile}: ".json_last_error_msg());
        }

        // Bind ChangerateService with the specified prognosis type
        app()->singleton(\App\Services\Prognosis\ChangerateService::class, function () use ($prognosisType) {
            return new \App\Services\Prognosis\ChangerateService($prognosisType);
        });

        // Prognosis gets Tax and Changerate singletons from the service container automatically
        $prognosis = (new PrognosisService($this->config));
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

    /**
     * @param  array<string, mixed>  $asset
     * @param  array<string, mixed>  $meta
     */
    public function page(array $asset, array $meta): void
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

        // Set auto-size for all columns
        for ($column = 1; $column <= 34 + 6; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        // Column AN (40) = FIRE Sparing
        // Column AO (41) = FIRE Cashflow - should be same width as AN
        // Column AQ (43) = Description - auto-sized

        // First, let Excel calculate the auto-size width for column AN
        $sheet->calculateColumnWidths();
        $anWidth = $sheet->getColumnDimension('AN')->getWidth();

        // Set AO to same width as AN
        $sheet->getColumnDimension('AO')->setAutoSize(false);
        $sheet->getColumnDimension('AO')->setWidth($anWidth);

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
