<?php
namespace App\Exports;

use App\Models\Tax;
use App\Models\Changerate;
use App\Models\Prognosis;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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

        $this->spreadsheet = new Spreadsheet();

        $this->spreadsheet->getProperties()
            ->setCreator("Thomas Ekdahl")
            ->setLastModifiedBy("Thomas Ekdahl")
            ->setTitle("Wealth prognosis")
            ->setSubject("Wealth prognosis Subject")
            ->setDescription(
                "Wealth prognosis Description"
            )
            ->setKeywords("Wealth prognosis")
            ->setCategory("Wealth prognosis");

        $this->spreadsheet->removeSheetByIndex(0); //Siden jeg ikke klarte å navne det første
        print "Leser: '$configfile'\n";
        $content = file_get_contents($configfile);
        $this->config = json_decode($content, true);


        $this->birthYear = (integer)Arr::get($this->config, 'meta.birthYear');
        $this->economyStartYear = $this->birthYear + 16; #We look at economy from 16 years of age
        $this->thisYear = now()->year;
        $this->prognoseYear = (integer)$this->birthYear + Arr::get($this->config, 'meta.prognoseYear', 55);
        $this->pensionOfficialYear = (integer)$this->birthYear + Arr::get($this->config, 'meta.pensionOfficialYear', 67);
        $this->pensionWishYear = (integer)$this->birthYear + Arr::get($this->config, 'meta.pensionWishYear', 63);
        if ($this->pensionWishYear >= $this->birthYear + 63 && $this->pensionWishYear <= $this->birthYear + 67) {
            $this->otpStartYear = $this->pensionWishYear; #OTP begynner tidligst ved 63, senest ved 67 - men slutter på 77 uansett.
        } elseif ($this->pensionWishYear <= $this->birthYear + 63) {
            $this->otpStartYear = $this->birthYear + 63;
        } elseif ($this->pensionWishYear >= $this->birthYear + 67) {
            $this->otpStartYear = $this->birthYear + 67;
        }
        $this->otpEndYear = $this->birthYear + 77; #OTP slutter ved 77 uansett
        $this->otpYears = $this->otpEndYear - $this->otpStartYear;

        $this->deathYear = (integer)$this->birthYear + Arr::get($this->config, 'meta.deathYear', 82);
        $this->pensionWishYears = $this->deathYear - $this->pensionWishYear + 1; #The number of years you vil live with pension, used i divisor calculations
        $this->pensionOfficialYears = $this->deathYear - $this->pensionOfficialYear + 1; #The number of years you vil live with pension, used i divisor calculations
        $this->leftYears = $this->deathYear - $this->thisYear + 1; #The number of years until you die, used i divisor calculations
        $this->untilPensionYears = $this->pensionYear - $this->thisYear + 1; #The number of years until pension, used i divisor calculations
        $this->totalYears = $this->deathYear - $this->economyStartYear + 1; #Antall år vi gjør beregningen over

        #Variable replacement before start - but need to reed some variables before this, therefore generate json twice.
        $content = str_replace(
            ['$birthYear', '$economyStartYear', '$thisYear', '$prognoseYear', '$pensionOfficialYears', '$pensionWishYears', '$pensionOfficialYear', '$pensionWishYear', '$otpStartYear', '$otpEndYear', '$otpYears', '$deathYear', '$leftYears', '$untilPensionYears'],
            [$this->thisYear, $this->economyStartYear, $this->thisYear, $this->prognoseYear, $this->pensionOfficialYears, $this->pensionWishYears, $this->pensionOfficialYear, $this->pensionWishYear, $this->otpStartYear, $this->otpEndYear, $this->otpYears, $this->deathYear, $this->leftYears, $this->untilPensionYears],
            file_get_contents($configfile));

        #print $content;

        $this->config = json_decode($content, true);

        $this->tax = new Tax('tax', $this->economyStartYear, $this->deathYear);
        $this->changerate = new Changerate($prognosis, $this->economyStartYear, $this->deathYear);

        #print $this->changerate->getChangeratePercent('otp', '2024') . "\n";
        #print $this->changerate->getChangerateDecimal('otp', '2024') . "\n";

        $prognosis = (new Prognosis($this->config, $this->tax, $this->changerate));
        #dd($prognosis->privateH);
        $meta = [
            'active' => true,
            'name' => 'total',
            'type' => '',
            'group' => '',
            'description' => 'Total oversikt over din økonomi'
        ];

        print "generate: $generate\n";
        if ($generate == 'all' or $generate == 'total') {
           $this->page($prognosis->totalH, $meta);
        }

        if ($generate == 'all' or $generate == 'private') {
            $meta['name'] = 'private';
            $this->page($prognosis->privateH, $meta);
        }

        if ($generate == 'all' or $generate == 'company') {
            $meta['name'] = 'company';
            $this->page($prognosis->companyH, $meta);
        }
        #$this->page($prognosis->groupH, $meta);
        #$this->page($prognosis->labelH, $meta); #New, does not exist yet

        ######################################################################
        #Generate the spreadsheet pages
        foreach($prognosis->dataH as $assetname => $asset) {

            if(!$asset['meta']['active']) continue; #Hopp over de inaktive
            if ($generate == 'all' or $generate == $asset['meta']['group']) {
                $this->page($asset, $asset['meta']);
            }
        }

        $assetSpreadSheet = new AssetSpreadSheet($this->spreadsheet, $prognosis->statisticsH);
        $this->spreadsheet->addSheet($assetSpreadSheet->worksheet);
        $this->spreadsheet->setActiveSheetIndexByName('Statistics');
        $sheet = $this->spreadsheet->getActiveSheet();

        #Fix - hva skal vi sette som default når det er totalt dynamisk?
        #$this->spreadsheet->setActiveSheetIndexByName('Total');

        $writer = new Xlsx($this->spreadsheet);
        print "Lagrer: $exportfile\n";
        $writer->save($exportfile);
    }

    public function page($asset, $meta){

        $prognosisAsset = new PrognosisAssetSheet2($this->spreadsheet, $this->config, $asset, $meta);
        $this->spreadsheet->addSheet($prognosisAsset->worksheet);
        $this->spreadsheet->setActiveSheetIndexByName($meta['name']);
        $sheet = $this->spreadsheet->getActiveSheet();

        $sheet->getStyle("B6:AC80")->getNumberFormat()->setFormatCode('#,##;[Red]-#,##');

        #Kolonner med prosenter i innhold
        $sheet->getStyle("D6:D80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("F6:F80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling

        $sheet->getStyle("H6:H80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("L6:L80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("N6:N80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("Q6:Q80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling

        $sheet->getStyle("T6:S80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("X6:W80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling
        $sheet->getStyle("AD6:AC80")->getNumberFormat()->setFormatCode('0.0%;[Red]-0.0%'); #% styling


        for ($column = 1; $column <= 20+6; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $verticaloffsett = 6;
        #Grå Kolonne header
        $sheet->getStyle('A5:AE5')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('CCCCCC');

        #Inntekt - vertikal
        $sheet->getStyle("C6:C" . $this->totalYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->incomeColor);

        #Utgift - vertikal
        $sheet->getStyle("E6:E" . $this->totalYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->expenceColor);

        #Formue blå - vertikal
        $sheet->getStyle("P6:P" . $this->totalYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->cashflowColor);


        #cashflow blå - vertikal
        $sheet->getStyle("U6:U" . $this->totalYears + $verticaloffsett - 1)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->cashflowColor);

        #I år - horozontal
        $row = $this->thisYear - $this->economyStartYear + $verticaloffsett;
        $sheet->getStyle("A$row:AD$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->thisYearRowColor);

        #Prognosis year - horizontal
        $row = $this->prognoseYear - $this->economyStartYear + $verticaloffsett;
        $sheet->getStyle("A$row:AD$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->prognoseYearRowColor);

        #Pension official - horizontal
        $row = $this->pensionOfficialYear - $this->economyStartYear + $verticaloffsett;
        $sheet->getStyle("A$row:AD$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->pensionOfficialYearRowColor);

        #Pension wish - horizontal
        $row = $this->pensionWishYear - $this->economyStartYear + $verticaloffsett;
        $sheet->getStyle("A$row:AD$row")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->pensionOfficialYearRowColor);

        #Deathyear - horizontal
        $row = $this->deathYear - $this->economyStartYear + $verticaloffsett;
        $sheet->getStyle("A" . $row . ":AD" . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($this->deathYearRowColor);
    }
}
