<?php

namespace Tests\Feature;

use App\Exports\PrognosisExport2;
use App\Models\Tax;
use App\Models\Changerate;
use App\Models\Prognosis;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PrognosisExport2Test extends TestCase
{
    /**
     * Test the construct function of PrognosisExport2 class
     *
     * @return void
     */
    public function testConstruct()
    {
        // You should replace the parameters with variable or input that you want to test
        $prognosis = "tenpercent"; //
        $generate = "private"; // All | Private | Company
        $filename = "house_$prognosis.xlsx";
        $configfile = "/Users/thomasek/Code/wealthprognosis-app/tests/Feature/config/house.json"; //
        $expected_exportfile = "/Users/thomasek/Code/wealthprognosis-app/tests/Feature/config/$filename"; //
        $generated_exportfile = "/tmp/$filename"; //

        // Instantiate a new PrognosisExport2 class
        $prognosisExport2 = new PrognosisExport2($configfile, $generated_exportfile, $prognosis, $generate);

        // Assert the properties of PrognosisExport2 class
        $this->assertIsString($prognosisExport2->configfile);
        $this->assertInstanceOf(Spreadsheet::class, $prognosisExport2->spreadsheet);
        $this->assertEquals($prognosisExport2->configfile, $configfile);

        print "$expected_exportfile\n";
        print "$generated_exportfile\n";
        $this->assertFileEquals($expected_exportfile, $generated_exportfile, "The generated file doesn't match the expected output.");
    }
}
