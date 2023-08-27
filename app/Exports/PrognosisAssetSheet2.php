<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\FromView;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrognosisAssetSheet2
{
    private $name;
    private $asset;
    private $meta;
    private $spreadsheet;
    public $worksheet;

    public int $columns = 26;
    public int $rows = 6;
    public int $rowHeader = 5;

    public int $groups = 1;

    public function __construct($spreadsheet, $config, $asset, $meta)
    {
        $this->config = $config;
        $this->asset = $asset;

        $this->spreadsheet = $spreadsheet;

        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, $meta['name']);

        $this->worksheet->setCellValue('A1', $meta['name'] );

        $this->worksheet->setCellValue('A2', "kortnavn" );
        $this->worksheet->setCellValue('B2', $meta['name'] );
        $this->worksheet->setCellValue('C2', "Gruppe" );
        $this->worksheet->setCellValue('D2', $meta['group'] );
        $this->worksheet->setCellValue('E2', "Type" );
        $this->worksheet->setCellValue('F2', $meta['type'] );
        $this->worksheet->setCellValue('G2', "Aktiv" );
        $this->worksheet->setCellValue('H2', $meta['active'] );
        $this->worksheet->setCellValue('I2', "Beskrivelse" );
        $this->worksheet->setCellValue('J2', $meta['description'] );

        #Gruppering av kolonner med navn
        $this->worksheet->setCellValue('E4', "Lån" );
        $this->worksheet->setCellValue('N4', "Formue" );
        $this->worksheet->setCellValue('S4', "Bank" );
        $this->worksheet->setCellValue('X4', "F.I.R.E" );

        #Kolonne headinger
        $this->worksheet->setCellValue("A$this->rowHeader","År");
        $this->worksheet->setCellValue("B$this->rowHeader","Alder");
        $this->worksheet->setCellValue("C$this->rowHeader","Inntekt");
        $this->worksheet->setCellValue("D$this->rowHeader","Utgift");
        $this->worksheet->setCellValue("E$this->rowHeader","Termin");
        $this->worksheet->setCellValue("F$this->rowHeader","% Rente");
        $this->worksheet->setCellValue("G$this->rowHeader","Rente");
        $this->worksheet->setCellValue("H$this->rowHeader","Avdrag");
        $this->worksheet->setCellValue("I$this->rowHeader","Rest lån");
        $this->worksheet->setCellValue("J$this->rowHeader","% Skatt");
        $this->worksheet->setCellValue("K$this->rowHeader","Skatt");
        $this->worksheet->setCellValue("L$this->rowHeader","% Fradrag");
        $this->worksheet->setCellValue("M$this->rowHeader","Fradrag");
        $this->worksheet->setCellValue("N$this->rowHeader","Formue");
        $this->worksheet->setCellValue("O$this->rowHeader","Formue skattbar");
        $this->worksheet->setCellValue("P$this->rowHeader","% skatt");
        $this->worksheet->setCellValue("Q$this->rowHeader","Skatt");
        $this->worksheet->setCellValue("R$this->rowHeader","Cashflow");
        $this->worksheet->setCellValue("S$this->rowHeader","Formue fratrukket lån");
        $this->worksheet->setCellValue("T$this->rowHeader","Grad");
        $this->worksheet->setCellValue("U$this->rowHeader","Betjeningsevne");
        $this->worksheet->setCellValue("V$this->rowHeader","Max lån");
        $this->worksheet->setCellValue("W$this->rowHeader","Rest evne");
        $this->worksheet->setCellValue("X$this->rowHeader","FIRE sparing");
        $this->worksheet->setCellValue("Y$this->rowHeader","FIRE cashflow");
        $this->worksheet->setCellValue("Z$this->rowHeader","FIRE sparerate");
        $this->worksheet->setCellValue("AA$this->rowHeader","Description");

       #return;

        foreach($this->asset as $year => $data) {

            if($year == 'meta') { continue; }; #Hopp over metadata

            $this->worksheet->setCellValue("A$this->rows", $year);
            $this->worksheet->setCellValue("B$this->rows",(int) $year-Arr::get($this->config, 'meta.birthYear'));
            $this->worksheet->setCellValue("C$this->rows", Arr::get($data, "income.amount"));
            $this->worksheet->setCellValue("D$this->rows", Arr::get($data, "expence.amount"));
            $this->worksheet->setCellValue("E$this->rows", Arr::get($data, "mortgage.payment"));

            if(Arr::get($data, "mortgage.interest") != 0) {
                $this->worksheet->setCellValue("F$this->rows", Arr::get($data, "mortgage.interest",));
            }
            $this->worksheet->setCellValue("G$this->rows", Arr::get($data, "mortgage.interestAmount"));
            $this->worksheet->setCellValue("H$this->rows", Arr::get($data, "mortgage.principal"));
            $this->worksheet->setCellValue("I$this->rows", Arr::get($data, "mortgage.balance"));

            if(Arr::get($data, "tax.percentTaxableYearly") != 0) {
                $this->worksheet->setCellValue("J$this->rows", Arr::get($data, "tax.percentTaxableYearly"));
            }
            $this->worksheet->setCellValue("K$this->rows", Arr::get($data, "tax.amountTaxableYearly"));

            if(Arr::get($data, "tax.percentDeductableYearly") != 0) {
                $this->worksheet->setCellValue("L$this->rows", Arr::get($data, "tax.percentDeductableYearly"));
            }
            $this->worksheet->setCellValue("M$this->rows", Arr::get($data, "tax.amountDeductableYearly"));
            $this->worksheet->setCellValue("N$this->rows", Arr::get($data, "asset.amount"));
            $this->worksheet->setCellValue("O$this->rows", Arr::get($data, "fortune.taxableAmount"));
            $this->worksheet->setCellValue("P$this->rows", Arr::get($data, "fortune.taxPercent"));
            $this->worksheet->setCellValue("Q$this->rows", Arr::get($data, "fortune.taxAmount"));
            $this->worksheet->setCellValue("R$this->rows", Arr::get($data, "cashflow.amount"));
            $this->worksheet->setCellValue("S$this->rows", Arr::get($data, "asset.amountLoanDeducted"));

            if(Arr::get($data, "asset.loanPercentage") != 0) {
                $this->worksheet->setCellValue("T$this->rows", Arr::get($data, "asset.loanPercentage"));
            }
            $this->worksheet->setCellValue("U$this->rows", Arr::get($data, "potential.income"));
            $this->worksheet->setCellValue("V$this->rows", Arr::get($data, "potential.loan"));
            $this->worksheet->setCellValue("W$this->rows", Arr::get($data, "potential.debtCapacity"));
            $this->worksheet->setCellValue("X$this->rows", Arr::get($data, "fire.savingAmount"));

            if(Arr::get($data, "fire.savingAmount") > 0) {
                #print "$year: " . $meta['name'] . " " . Arr::get($data, "fire.savingAmount") . "\n";
            }
            $this->worksheet->setCellValue("Y$this->rows", Arr::get($data, "fire.cashFlow"));
            if(Arr::get($data, "fire.savingRate") != 0) {
                $this->worksheet->setCellValue("Z$this->rows", Arr::get($data, "fire.savingRate"));
            }
            $this->worksheet->setCellValue("AA$this->rows", Arr::get($data, "income.description") . Arr::get($data, "expence.description") . Arr::get($data, "asset.description"));


            $this->rows++;
        }
        $this->rows--;
    }
}
