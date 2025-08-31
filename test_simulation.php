<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AssetConfiguration;
use App\Models\PrognosisNew;

echo "=== SIMULATION TEST ===" . PHP_EOL . PHP_EOL;

try {
    // Get first asset configuration
    $assetConfig = AssetConfiguration::with(['assets.assetYears'])->first();
    
    if (!$assetConfig) {
        echo "No AssetConfiguration found. Please create one first." . PHP_EOL;
        exit(1);
    }
    
    echo "Testing with AssetConfiguration: " . $assetConfig->name . PHP_EOL;
    echo "Birth Year: " . $assetConfig->birth_year . PHP_EOL;
    echo "Death Age: " . $assetConfig->death_age . PHP_EOL;
    echo "Assets Count: " . $assetConfig->assets->count() . PHP_EOL . PHP_EOL;
    
    // Test PrognosisNew creation
    echo "Creating PrognosisNew instance..." . PHP_EOL;
    $prognosis = new PrognosisNew($assetConfig, 'realistic', 'both');
    echo "✓ PrognosisNew created successfully" . PHP_EOL . PHP_EOL;
    
    // Test simulation run
    echo "Running simulation..." . PHP_EOL;
    $results = $prognosis->runSimulation();
    echo "✓ Simulation completed successfully" . PHP_EOL . PHP_EOL;
    
    // Display results
    echo "=== SIMULATION RESULTS ===" . PHP_EOL;
    echo "Configuration ID: " . $results['configuration']['asset_configuration_id'] . PHP_EOL;
    echo "Prognosis Type: " . $results['configuration']['prognosis_type'] . PHP_EOL;
    echo "Asset Scope: " . $results['configuration']['asset_scope'] . PHP_EOL;
    echo "Duration: " . $results['configuration']['duration_years'] . " years" . PHP_EOL;
    echo "Start Year: " . $results['configuration']['start_year'] . PHP_EOL;
    echo "End Year: " . $results['configuration']['end_year'] . PHP_EOL . PHP_EOL;
    
    echo "=== SUMMARY STATISTICS ===" . PHP_EOL;
    $summary = $results['summary'];
    echo "Total Assets Start: $" . number_format($summary['total_assets_start']) . PHP_EOL;
    echo "Total Assets End: $" . number_format($summary['total_assets_end']) . PHP_EOL;
    echo "Net Worth Change: $" . number_format($summary['net_worth_change']) . PHP_EOL;
    echo "Total Income: $" . number_format($summary['total_income']) . PHP_EOL;
    echo "Total Expenses: $" . number_format($summary['total_expenses']) . PHP_EOL;
    echo "Total Taxes: $" . number_format($summary['total_taxes']) . PHP_EOL;
    echo "Net Cash Flow: $" . number_format($summary['net_cash_flow']) . PHP_EOL;
    echo "FIRE Achieved: " . ($summary['fire_achieved'] ? 'Yes' : 'No') . PHP_EOL;
    if ($summary['fire_achieved']) {
        echo "FIRE Year: " . $summary['fire_year'] . PHP_EOL;
        echo "Years to FIRE: " . $summary['years_to_fire'] . PHP_EOL;
    }
    echo "FIRE Amount Needed: $" . number_format($summary['fire_amount_needed']) . PHP_EOL . PHP_EOL;
    
    echo "=== YEARLY DATA SAMPLE ===" . PHP_EOL;
    $yearlyData = $results['yearly_data'];
    $sampleYears = array_slice($yearlyData, 0, 5, true);
    
    foreach ($sampleYears as $year => $data) {
        echo "Year {$year} (Age {$data['age']}): Assets $" . number_format($data['total_assets']) . 
             ", Income $" . number_format($data['total_income']) . 
             ", Expenses $" . number_format($data['total_expenses']) . PHP_EOL;
    }
    
    if (count($yearlyData) > 5) {
        echo "... and " . (count($yearlyData) - 5) . " more years" . PHP_EOL;
    }
    echo PHP_EOL;
    
    echo "=== ASSET BREAKDOWN ===" . PHP_EOL;
    $assetBreakdown = $results['asset_breakdown'];
    foreach ($assetBreakdown as $assetId => $breakdown) {
        echo "Asset: " . $breakdown['asset_name'] . " (" . $breakdown['asset_type'] . ")" . PHP_EOL;
        echo "  Start Value: $" . number_format($breakdown['start_value']) . PHP_EOL;
        echo "  End Value: $" . number_format($breakdown['end_value']) . PHP_EOL;
        echo "  Total Growth: $" . number_format($breakdown['total_growth']) . PHP_EOL;
        echo "  Growth Rate: " . number_format($breakdown['growth_rate'] * 100, 2) . "%" . PHP_EOL . PHP_EOL;
    }
    
    echo "=== TEST COMPLETED SUCCESSFULLY ===" . PHP_EOL;
    echo "The simulation system is working correctly!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
