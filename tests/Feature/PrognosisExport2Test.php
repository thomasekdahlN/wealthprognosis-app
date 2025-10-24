<?php

namespace Tests\Feature;

use App\Exports\PrognosisExport2;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class PrognosisExport2Test extends TestCase
{
    /**
     * Test the construct function of PrognosisExport2 class
     *
     * @return void
     */
    public function Construct()
    {
        // You should replace the parameters with variable or input that you want to test
        $prognosis = 'tenpercent'; //
        $generate = 'private'; // All | Private | Company
        $filename = "house_$prognosis.xlsx";
        $configfile = '/Users/thomasek/Code/wealthprognosis-app/tests/Feature/config/house.json'; //
        $expected_exportfile = "/Users/thomasek/Code/wealthprognosis-app/tests/Feature/config/$filename"; //
        $generated_exportfile = "/tmp/$filename"; //

        // Instantiate a new PrognosisExport2 class
        $prognosisExport2 = new PrognosisExport2($configfile, $generated_exportfile, $prognosis, $generate);

        // Assert the properties of PrognosisExport2 class
        $this->assertIsString($prognosisExport2->configfile);
        $this->assertInstanceOf(Spreadsheet::class, $prognosisExport2->spreadsheet);
        $this->assertEquals($prognosisExport2->configfile, $configfile);

        echo "$expected_exportfile\n";
        echo "$generated_exportfile\n";
        $this->assertFileEquals($expected_exportfile, $generated_exportfile, "The generated file doesn't match the expected output.");
    }

    /**
     * Test that PrognosisExport2 throws exception when file does not exist
     */
    public function test_throws_exception_when_file_not_found(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file not found');

        $nonExistentFile = '/tmp/nonexistent_file_'.uniqid().'.json';
        $exportFile = '/tmp/export_'.uniqid().'.xlsx';

        new PrognosisExport2($nonExistentFile, $exportFile, 'realistic', 'all');
    }

    /**
     * Test that PrognosisExport2 throws exception when JSON is invalid
     */
    public function test_throws_exception_when_json_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        // Create a temporary file with invalid JSON
        $invalidJsonFile = tempnam(sys_get_temp_dir(), 'invalid_json_');
        file_put_contents($invalidJsonFile, '{ "meta": { "name": "Test", invalid json here }');

        $exportFile = '/tmp/export_'.uniqid().'.xlsx';

        try {
            new PrognosisExport2($invalidJsonFile, $exportFile, 'realistic', 'all');
        } finally {
            @unlink($invalidJsonFile);
        }
    }

    /**
     * Test that PrognosisExport2 throws exception when JSON is not an object/array
     */
    public function test_throws_exception_when_json_not_object(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON content must be an object/array');

        // Create a temporary file with valid JSON but not an object
        $invalidStructureFile = tempnam(sys_get_temp_dir(), 'invalid_structure_');
        file_put_contents($invalidStructureFile, '"just a string"');

        $exportFile = '/tmp/export_'.uniqid().'.xlsx';

        try {
            new PrognosisExport2($invalidStructureFile, $exportFile, 'realistic', 'all');
        } finally {
            @unlink($invalidStructureFile);
        }
    }
}
