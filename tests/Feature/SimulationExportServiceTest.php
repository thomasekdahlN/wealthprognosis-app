<?php

namespace Tests\Feature;

use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use App\Models\User;
use App\Services\SimulationExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SimulationExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_excel_with_expected_formatting(): void
    {
        $user = User::factory()->create();

        // Create a simulation configuration with known ages/years
        $birthYear = 1980;
        $thisYear = (int) now()->year;
        $simulation = SimulationConfiguration::factory()
            ->withAges($birthYear, 85, 67)
            ->create([
                'name' => 'Demo Simulation',
                'user_id' => $user->id,
                'prognose_age' => 55,
                'export_start_age' => 25,
            ]);

        // Create one simulation asset linked to this simulation (note: FK is asset_configuration_id)
        $asset = SimulationAsset::factory()->create([
            'asset_configuration_id' => $simulation->id,
            'user_id' => $user->id,
            'name' => 'Equity Fund ABC',
            'asset_type' => 'equity',
            'group' => 'private',
            'sort_order' => 1,
        ]);

        // Create year rows (ensure we include current year for highlight row)
        SimulationAssetYear::factory()->create([
            'asset_id' => $asset->id,
            'year' => $thisYear,
            'asset_market_amount' => 1234567.0,
            'income_amount' => 15000.0,
            'expence_amount' => 5000.0,
        ]);
        SimulationAssetYear::factory()->create([
            'asset_id' => $asset->id,
            'year' => $thisYear + 1,
            'asset_market_amount' => 2345678.0,
            'income_amount' => 20000.0,
            'expence_amount' => 7000.0,
        ]);

        // Run export
        $path = SimulationExportService::export($simulation);
        $this->assertFileExists($path, 'Export file should be created');

        // Open and inspect
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($path);

        // First worksheet should be the first asset sheet
        $sheet = $spreadsheet->getSheet(0);

        // Header texts
        $this->assertSame('År', (string) $sheet->getCell('A5')->getValue());
        $this->assertSame('Inntekt', (string) $sheet->getCell('C5')->getValue());

        // Header formatting: bold & gray background
        $this->assertTrue($sheet->getStyle('A5')->getFont()->getBold(), 'Header should be bold');
        $headerFill = strtoupper($sheet->getStyle('A5')->getFill()->getStartColor()->getARGB());
        $this->assertStringEndsWith('CCCCCC', $headerFill, 'Header should have gray background');

        // Column color for income (C column)
        $incomeFill = strtoupper($sheet->getStyle('C6')->getFill()->getStartColor()->getARGB());
        $this->assertStringEndsWith('90EE90', $incomeFill, 'Income column should be light green');

        // Number format: Norwegian spacing, red negatives for amounts (column C)
        $formatC6 = $sheet->getStyle('C6')->getNumberFormat()->getFormatCode();
        $this->assertSame('# ##0;[Red]-# ##0', $formatC6, 'Amount format should use space thousand sep and red negatives');

        // Alignment: right-aligned numbers
        $this->assertSame(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            $sheet->getStyle('C6')->getAlignment()->getHorizontal(),
            'Amounts should be right-aligned'
        );

        // Optional: percent column has percent format (e.g., D column)
        $formatD6 = $sheet->getStyle('D6')->getNumberFormat()->getFormatCode();
        $this->assertSame('0.0%;[Red]-0.0%', $formatD6, 'Percent columns should have percent format');

        // Clean up
        @unlink($path);
    }
}
