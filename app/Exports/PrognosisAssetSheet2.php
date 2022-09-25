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

    public function __construct($spreadsheet, $config, $name, $asset)
    {
        $this->config = $config;
        $this->name = $name;
        $this->asset = $asset;
        $this->meta = $this->asset['meta'];
        $this->name = $this->meta['name'];

        $this->spreadsheet = $spreadsheet;

        $this->worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->spreadsheet, $name);

        $this->worksheet->setCellValue('A1', $this->name );

        $this->worksheet->setCellValue('A2', "kortnavn" );
        $this->worksheet->setCellValue('B2', $name );
        $this->worksheet->setCellValue('C2', "Gruppe" );
        $this->worksheet->setCellValue('D2', $this->meta['group'] );
        $this->worksheet->setCellValue('E2', "Type" );
        $this->worksheet->setCellValue('F2', $this->meta['type'] );
        $this->worksheet->setCellValue('G2', "Aktiv" );
        $this->worksheet->setCellValue('H2', $this->meta['active'] );
        $this->worksheet->setCellValue('I2', "Beskrivelse" );
        $this->worksheet->setCellValue('J2', $this->meta['description'] );

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
        $this->worksheet->setCellValue("X$this->rowHeader","FIRE diff");
        $this->worksheet->setCellValue("Y$this->rowHeader","FIRE %");
        $this->worksheet->setCellValue("Z$this->rowHeader","Description");

       #return;

        foreach($this->asset as $year => $data) {

            if($year == 'meta') { continue; }; #Hopp over metadata

            $this->worksheet->setCellValue("A$this->rows", $year);
            $this->worksheet->setCellValue("B$this->rows",(int) $year-Arr::get($this->config, 'meta.birthyear'));
            $this->worksheet->setCellValue("C$this->rows", Arr::get($data, "income.amount"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("D$this->rows", Arr::get($data, "expence.amount"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("E$this->rows", Arr::get($data, "mortgage.payment"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("F$this->rows", Arr::get($data, "mortgage.interest"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("G$this->rows", Arr::get($data, "mortgage.interestAmount"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("H$this->rows", Arr::get($data, "mortgage.principal"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("I$this->rows", Arr::get($data, "mortgage.balance"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("J$this->rows", Arr::get($data, "tax.percentTaxableYearly"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("K$this->rows", Arr::get($data, "tax.amountTaxableYearly"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("L$this->rows", Arr::get($data, "tax.percentDeductableYearly"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("M$this->rows", Arr::get($data, "tax.amountDeductableYearly"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("N$this->rows", Arr::get($data, "cashflow.amount"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("O$this->rows", Arr::get($data, "asset.amount"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("P$this->rows", Arr::get($data, "tax.amountFortune"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("Q$this->rows", Arr::get($data, "asset.amountLoanDeducted"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("R$this->rows", Arr::get($data, "asset.loanPercentage"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("S$this->rows", Arr::get($data, "potential.income"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("T$this->rows", Arr::get($data, "potential.loan"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("U$this->rows", Arr::get($data, "potential.loan") - Arr::get($data, "mortgage.balance"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("V$this->rows", Arr::get($data, "fire.amountIncome"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("W$this->rows", Arr::get($data, "fire.amountExpence"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("X$this->rows", Arr::get($data, "fire.amountDiff"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("Y$this->rows", Arr::get($data, "fire.percentDiff"), DataType::TYPE_NUMERIC);
            $this->worksheet->setCellValue("Z$this->rows", Arr::get($data, "income.description") . Arr::get($data, "expence.description") . Arr::get($data, "asset.description"), DataType::TYPE_NUMERIC);


            $this->rows++;
        }
        $this->rows--;
    }
}
