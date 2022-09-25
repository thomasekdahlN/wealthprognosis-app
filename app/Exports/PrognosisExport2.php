<?php
namespace App\Exports;

use App\Models\Prognosis;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PrognosisExport2
{
    public $configfile;
    public $yearRow = 21;
    public $yearTenRow = 31;
    public $yearTwentyRow = 41;

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

        $prognosis = (new Prognosis($this->config));

        $prognosisTotal = new PrognosisTotalSheet2($spreadsheet, $this->config, $prognosis->totalH, $prognosis->groupH);
        $spreadsheet->addSheet($prognosisTotal->worksheet);
        $sheet = $spreadsheet->getActiveSheet();

        $lastcolexcel = $prognosisTotal->convertNumberToExcelCol($prognosisTotal->columns);
        $sheet->getStyle("C3:$lastcolexcel" . $prognosisTotal->rows)->getNumberFormat()->setFormatCode('#,##');


        for ($column = 1; $column <= $prognosisTotal->columns + 7; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $sheet->getStyle("C3:AT54")->getNumberFormat()->setFormatCode('#,##;[Red]-#,##'); #% styling

        $sheet->getStyle("N4:N54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("Z4:Z54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("AN4:AN54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("AU4:AU54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling


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

            $sheet->getStyle($colexcel . "4:$colexcel" . $prognosisTotal->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('90EE90');

            #Utgift
            $colexcel = $prognosisTotal->convertNumberToExcelCol($groupstart + 1);
            $sheet->getStyle($colexcel . "4:$colexcel". $prognosisTotal->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFCCCB');

            #cashflow blå
            $colexcel = $prognosisTotal->convertNumberToExcelCol($groupstart + 8);
            $sheet->getStyle($colexcel . "4:$colexcel". $prognosisTotal->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('ADD8E6');

            $groupstart += 12;
        }

        #I år
        $sheet->getStyle("A$this->yearRow:AV$this->yearRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('BBBBBB');

        #Om 10 år
        $sheet->getStyle("A$this->yearTenRow:AV$this->yearTenRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('BBBBBB');

        #Om 20 år
        $sheet->getStyle("A$this->yearTwentyRow:AV$this->yearTwentyRow")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('BBBBBB');

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

            $sheet->getStyle($colexcel . "4:$colexcel" . $prognosisGroup->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('90EE90');

            #Utgift
            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart + 1);
            $sheet->getStyle($colexcel . "4:$colexcel". $prognosisGroup->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFCCCB');

            #cashflow blå
            $colexcel = $prognosisGroup->convertNumberToExcelCol($groupstart + 4);
            $sheet->getStyle($colexcel . "4:$colexcel". $prognosisGroup->rows)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('ADD8E6');


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
        $sheet->getStyle("A$this->yearRow:" . $lastcolexcel . $this->yearRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('BBBBBB');

        #Om 10 år
        $sheet->getStyle("A$this->yearTenRow:" . $lastcolexcel . $this->yearTenRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('BBBBBB');

        ######################################################################
        foreach($prognosis->dataH as $assetname => $asset) {

            if(!$asset['meta']['active']) continue; #Hopp over de inaktive

            $prognosisAsset = new PrognosisAssetSheet2($spreadsheet, $this->config, $assetname, $asset);
            $spreadsheet->addSheet($prognosisAsset->worksheet);
            $spreadsheet->setActiveSheetIndexByName($assetname);
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->getStyle("B3:X54")->getNumberFormat()->setFormatCode('#,##;[Red]-#,##');

            $sheet->getStyle("F4:F54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
            $sheet->getStyle("J4:J54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
            $sheet->getStyle("L4:L54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
            $sheet->getStyle("R4:R54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
            $sheet->getStyle("Y4:Y54")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling


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
                ->getStartColor()->setARGB('90EE90');

            #cashflow blå
            $sheet->getStyle("N4:N$prognosisTotal->rows")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('ADD8E6');

            #Utgift
            $sheet->getStyle("D4:D$prognosisTotal->rows")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFCCCB');

            #I år
            $sheet->getStyle("A$this->yearRow:Z$this->yearRow")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('BBBBBB');

            #Om 10 år
            $sheet->getStyle("A$this->yearTenRow:Z$this->yearTenRow")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('BBBBBB');

            #Om 20 år
            $sheet->getStyle("A" . $this->yearTwentyRow . ":Z" . $this->yearTwentyRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('BBBBBB');

        }

        $spreadsheet->setActiveSheetIndexByName('Total');

        $writer = new Xlsx($spreadsheet);
        print "Lagrer: $exportfile\n";
        $writer->save($exportfile);
    }
}
