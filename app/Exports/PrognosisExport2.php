<?php
namespace App\Exports;

use App\Models\Prognosis;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PrognosisExport2
{
    public $configfile;
    public $config;
    public $birthYear;
    public $economyStartYear;
    public $thisYear;
    public $prognoseYear;
    public $pensionYear;
    public $deathYear;


    public $birthRowColor = 'BBBBBB';
    public $economyStartRowColor = 'BBBBBB';
    public $thisYearRowColor = '32CD32';
    public $prognoseYearRowColor = '7FFFD4';
    public $pensionYearRowColor = 'FFA500';
    public $deathYearRowColor = 'FFCCCB';
    public $incomeColor = '90EE90';
    public $expenceColor = 'FFCCCB';
    public $cashflowColor = 'ADD8E6';


    public function __construct($configfile, $exportfile)
    {
        $this->configfile = $configfile;


        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()
            ->setCreator("Thomas Ekdahl")
            ->setLastModifiedBy("Thomas Ekdahl")
            ->setTitle("Wealth prognosis")
            ->setSubject("Wealth prognosis Subject")
            ->setDescription(
                "Wealth prognosis Description"
            )
            ->setKeywords("Wealth prognosis")
            ->setCategory("Wealth prognosis");

        $spreadsheet->removeSheetByIndex(0); //Siden jeg ikke klarte å navne det første
        $this->config = json_decode(file_get_contents($configfile), true);

        $this->birthYear  = (integer) Arr::get($this->config, 'meta.birthyear');
        $this->economyStartYear = $this->birthYear + 16; #We look at economy from 16 years of age
        $this->thisYear  = now()->year;
        $this->prognoseYear  = (integer) Arr::get($this->config, 'meta.prognoseYear');
        $this->pensionOfficialYear  = (integer) Arr::get($this->config, 'meta.pensionOfficialYear');
        $this->pensionWishYear  = (integer) Arr::get($this->config, 'meta.pensionWishYear');
        $this->deathYear  = (integer) Arr::get($this->config, 'meta.deathYear');
        $this->pensionYears = $this->deathYear - $this->pensionWishYear + 1; #The number of years you vil live with pension, used i divisor calculations
        $this->leftYears = $this->deathYear - $this->thisYear + 1; #The number of years until you die, used i divisor calculations
        $this->untilPensionYears = $this->pensionYear - $this->thisYear + 1; #The number of years until pension, used i divisor calculations

        #Variable replacement before start
        $content = str_replace(
            ['$birthYear','$economyStartYear','$thisYear','$prognoseYear', '$pensionOfficialYear', '$pensionWishYear', '$deathYear', '$pensionYears','$leftYears','$untilPensionYears'],
            [$this->thisYear, $this->economyStartYear, $this->thisYear, $this->prognoseYear, $this->pensionOfficialYear, $this->pensionWishYear, $this->deathYear, $this->pensionYears, $this->leftYears, $this->untilPensionYears],
            file_get_contents($configfile));

        #print $content;

        $this->config = json_decode($content, true);


        $prognosis = (new Prognosis($this->config));

        $prognosisTotal = new PrognosisTotalSheet2($spreadsheet, $this->config, $prognosis->totalH, $prognosis->groupH);
        $spreadsheet->addSheet($prognosisTotal->worksheet);
        $sheet = $spreadsheet->getActiveSheet();

        $lastcolexcel = $prognosisTotal->convertNumberToExcelCol($prognosisTotal->columns);
        $sheet->getStyle("C3:$lastcolexcel" . $prognosisTotal->rows)->getNumberFormat()->setFormatCode('#,##');


        for ($column = 1; $column <= $prognosisTotal->columns + 7; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $sheet->getStyle("C3:AT71")->getNumberFormat()->setFormatCode('#,##;[Red]-#,##'); #% styling

        $sheet->getStyle("N4:N71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("Z4:Z71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("AN4:AN71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("AU4:AU71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling


        $sheet->getStyle('A3:AV3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('CCCCCC');
        $sheet->getStyle('A3:AV3')->getFont()->setSize(18);

        #Kolonne header
        $sheet->getStyle('A3:AV3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('CCCCCC');

        $groupstart = 3;
        for ($groups = 1; $groups <= $prognosisTotal->groups; $groups++) {
            #Inntekt
            $colexcel = $prognosisTotal->convertNumberToExcelCol($groupstart);

            #Income
            $sheet->getStyle($colexcel . "4:$colexcel" . $prognosisTotal->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->incomeColor);

            #Utgift
            $colexcel = $prognosisTotal->convertNumberToExcelCol($groupstart + 1);
            $sheet->getStyle($colexcel . "4:$colexcel". $prognosisTotal->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->expenceColor);

            #cashflow blå
            $colexcel = $prognosisTotal->convertNumberToExcelCol($groupstart + 8);
            $sheet->getStyle($colexcel . "4:$colexcel". $prognosisTotal->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->cashflowColor);

            $groupstart += 12;
        }

        #I år
        $row = $this->thisYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:AV$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->thisYearRowColor);

        #Prognose
        $row = $this->prognoseYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:AV$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->prognoseYearRowColor);

        #Pension official - offisiell pensjonsdato
        $row = $this->pensionOfficialYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:AV$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->pensionYearRowColor);

        #Pension wish - ønsket pensjonsdato
        $row = $this->pensionWishYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:AV$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->pensionYearRowColor);

        #Death
        $row = $this->deathYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:AV$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->deathYearRowColor);

        ##########################################################################################
        $prognosisGroup = new PrognosisTypeSheet2($spreadsheet, $this->config, $prognosis->groupH);
        $spreadsheet->addSheet($prognosisGroup->worksheet);

        $spreadsheet->setActiveSheetIndexByName('Group');
        $sheet = $spreadsheet->getActiveSheet();

        $lastcolexcel = $prognosisGroup->convertNumberToExcelCol($prognosisGroup->columns+1);
        $sheet->getStyle("C3:$lastcolexcel" . $prognosisGroup->rows)->getNumberFormat()->setFormatCode('#,##');

        for ($column = 1; $column <= $prognosisGroup->columns; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $sheet->getStyle("A3:$lastcolexcel" . "3")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('CCCCCC');
        $sheet->getStyle("A3:$lastcolexcel" . "3")->getFont()->setSize(18);

        #Kolonne header
        $sheet->getStyle("A3:$lastcolexcel" . "3")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('CCCCCC');

        $groupstart = 3;
        for ($groups = 1; $groups <= $prognosisGroup->groups; $groups++) {
            #Inntekt
            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart);

            $sheet->getStyle("E3:E50")->getNumberFormat()->setFormatCode('#,##;[Red]-#,##'); #% styling

            #Income
            $sheet->getStyle($colexcel . "4:$colexcel" . $prognosisGroup->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->incomeColor);

            #Utgift
            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart + 1);
            $sheet->getStyle($colexcel . "4:$colexcel". $prognosisGroup->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->expenceColor);

            #cashflow blå
            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart + 4);
            $sheet->getStyle($colexcel . "4:$colexcel". $prognosisGroup->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->cashflowColor);


            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart +2);
            $sheet->getStyle($colexcel . "4:$colexcel" . $prognosisGroup->rows)->getNumberFormat()->setFormatCode('#,##;[Red]-#,##'); #% styling

            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart +3);
            $sheet->getStyle($colexcel . "4:$colexcel" . $prognosisGroup->rows)->getNumberFormat()->setFormatCode('#,##;[Red]-#,##'); #% styling

            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart +4);
            $sheet->getStyle($colexcel . "4:$colexcel" . $prognosisGroup->rows)->getNumberFormat()->setFormatCode('#,##;[Red]-#,##'); #% styling

            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart +5);
            $sheet->getStyle($colexcel . "4:$colexcel" . $prognosisGroup->rows)->getNumberFormat()->setFormatCode('#,##;[Red]-#,##'); #% styling


            $groupstart += 7;
        }

        #I år
        $row = $this->thisYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:" . $lastcolexcel . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->thisYearRowColor);

        #Prognose
        $row = $this->prognoseYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:" . $lastcolexcel . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->prognoseYearRowColor);

        #Pension Official
        $row = $this->pensionOfficialYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:" . $lastcolexcel . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->pensionYearRowColor);

        #Pension Wish
        $row = $this->pensionWishYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:" . $lastcolexcel . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->pensionYearRowColor);

        #Death
        $row = $this->deathYear - $this->economyStartYear + 4;
        $sheet->getStyle("A$row:" . $lastcolexcel . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->deathYearRowColor);

        ######################################################################
        foreach($prognosis->dataH as $assetname => $asset) {

            if(!$asset['meta']['active']) continue; #Hopp over de inaktive

            $prognosisAsset = new PrognosisAssetSheet2($spreadsheet, $this->config, $assetname, $asset);
            $spreadsheet->addSheet($prognosisAsset->worksheet);
            $spreadsheet->setActiveSheetIndexByName($assetname);
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->getStyle("B3:X71")->getNumberFormat()->setFormatCode('#,##;[Red]-#,##');

            $sheet->getStyle("F4:F71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
            $sheet->getStyle("J4:J71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
            $sheet->getStyle("L4:L71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
            $sheet->getStyle("R4:R71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
            $sheet->getStyle("Y4:Y71")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling


            for ($column = 1; $column <= 20+6; $column++) {
                $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
            }

            #Kolonne header
            $sheet->getStyle('A3:Z3')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('CCCCCC');

            #Inntekt
            $sheet->getStyle("C4:C$prognosisTotal->rows")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->incomeColor);

            #Utgift
            $sheet->getStyle("D4:D$prognosisTotal->rows")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->expenceColor);

            #cashflow blå
            $sheet->getStyle("N4:N$prognosisTotal->rows")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->cashflowColor);

            #I år
            $row = $this->thisYear - $this->economyStartYear + 4;
            $sheet->getStyle("A$row:Z$row")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->thisYearRowColor);

            #Prognosis year
            $row = $this->prognoseYear - $this->economyStartYear + 4;
            $sheet->getStyle("A$row:Z$row")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->prognoseYearRowColor);

            #Pension official
            $row = $this->pensionOfficialYear - $this->economyStartYear + 4;
            $sheet->getStyle("A$row:Z$row")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->pensionYearRowColor);

            #Pension wish
            $row = $this->pensionWishYear - $this->economyStartYear + 4;
            $sheet->getStyle("A$row:Z$row")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->pensionYearRowColor);


            #Deathyear
            $row = $this->deathYear - $this->economyStartYear + 4;
            $sheet->getStyle("A" . $row . ":Z" . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($this->deathYearRowColor);

        }

        $spreadsheet->setActiveSheetIndexByName('Total');

        $writer = new Xlsx($spreadsheet);
        print "Lagrer: $exportfile\n";
        $writer->save($exportfile);
    }
}
