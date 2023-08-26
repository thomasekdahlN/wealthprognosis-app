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

    public $columns = 26;
    public $rows = 4 ;
    public $rowHeader = 3;
    public $groups = 1;

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

        $this->worksheet->setCellValue("A$this->rowHeader","Year");
        $this->worksheet->setCellValue("B$this->rowHeader","Age");
        $this->worksheet->setCellValue("C$this->rowHeader","Inntekt");
        $this->worksheet->setCellValue("D$this->rowHeader","Utgift");
        $this->worksheet->setCellValue("E$this->rowHeader","Termin");
        $this->worksheet->setCellValue("F$this->rowHeader","Rente");
        $this->worksheet->setCellValue("G$this->rowHeader","Rente");
        $this->worksheet->setCellValue("H$this->rowHeader","Avdrag");
        $this->worksheet->setCellValue("I$this->rowHeader","Gjenværende");
        $this->worksheet->setCellValue("J$this->rowHeader","% Skatt");
        $this->worksheet->setCellValue("K$this->rowHeader","Skatt");
        $this->worksheet->setCellValue("L$this->rowHeader","% Fradrag");
        $this->worksheet->setCellValue("M$this->rowHeader","Fradrag");
        $this->worksheet->setCellValue("N$this->rowHeader","Cashflow");
        $this->worksheet->setCellValue("O$this->rowHeader","Asset");
        $this->worksheet->setCellValue("P$this->rowHeader","Skattbar formue");
        $this->worksheet->setCellValue("Q$this->rowHeader","Asset - lån");
        $this->worksheet->setCellValue("R$this->rowHeader","Grad");
        $this->worksheet->setCellValue("S$this->rowHeader","Betjeningsevne");
        $this->worksheet->setCellValue("T$this->rowHeader","Max lån");
        $this->worksheet->setCellValue("U$this->rowHeader","Rest evne");
        $this->worksheet->setCellValue("V$this->rowHeader","FIRE inntekt");
        $this->worksheet->setCellValue("W$this->rowHeader","FIRE utgift");
        $this->worksheet->setCellValue("X$this->rowHeader","FIRE cashflow");
        $this->worksheet->setCellValue("Y$this->rowHeader","FIRE %");
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
            $this->worksheet->setCellValue("F$this->rows", Arr::get($data, "mortgage.interest", ));
            $this->worksheet->setCellValue("G$this->rows", Arr::get($data, "mortgage.interestAmount"));
            $this->worksheet->setCellValue("H$this->rows", Arr::get($data, "mortgage.principal"));
            $this->worksheet->setCellValue("I$this->rows", Arr::get($data, "mortgage.balance"));
            $this->worksheet->setCellValue("J$this->rows", Arr::get($data, "tax.percentTaxableYearly"));
            $this->worksheet->setCellValue("K$this->rows", Arr::get($data, "tax.amountTaxableYearly"));
            $this->worksheet->setCellValue("L$this->rows", Arr::get($data, "tax.percentDeductableYearly"));
            $this->worksheet->setCellValue("M$this->rows", Arr::get($data, "tax.amountDeductableYearly"));
            $this->worksheet->setCellValue("N$this->rows", Arr::get($data, "cashflow.amount"));
            $this->worksheet->setCellValue("O$this->rows", Arr::get($data, "asset.amount"));
            $this->worksheet->setCellValue("P$this->rows", Arr::get($data, "tax.amountFortune"));
            $this->worksheet->setCellValue("Q$this->rows", Arr::get($data, "asset.amountLoanDeducted"));
            $this->worksheet->setCellValue("R$this->rows", Arr::get($data, "asset.loanPercentage"));
            $this->worksheet->setCellValue("S$this->rows", Arr::get($data, "potential.income"));
            $this->worksheet->setCellValue("T$this->rows", Arr::get($data, "potential.loan"));
            $this->worksheet->setCellValue("U$this->rows", Arr::get($data, "potential.loan") - Arr::get($data, "mortgage.balance"));
            $this->worksheet->setCellValue("V$this->rows", Arr::get($data, "fire.amountIncome"));
            $this->worksheet->setCellValue("W$this->rows", Arr::get($data, "fire.amountExpence"));
            $this->worksheet->setCellValue("X$this->rows", Arr::get($data, "fire.cashFlow"));
            $this->worksheet->setCellValue("Y$this->rows", Arr::get($data, "fire.percentDiff"));
            $this->worksheet->setCellValue("Z$this->rows", Arr::get($data, "fire.savingRate"));
            $this->worksheet->setCellValue("AA$this->rows", Arr::get($data, "income.description") . Arr::get($data, "expence.description") . Arr::get($data, "asset.description"));


            $this->rows++;
        }
        $this->rows--;
    }
}
