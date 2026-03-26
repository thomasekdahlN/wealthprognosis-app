<?php

namespace Database\Seeders;

use App\Models\AssetConfiguration;
use App\Models\SimulationConfiguration;
use App\Models\User;
use App\Services\AssetImportService;
use App\Services\Prognosis\PrognosisService;
use App\Services\PrognosisSimulationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SimulationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create user with ID 1
        $user = User::find(1);
        if (! $user) {
            $user = User::firstOrCreate(
                ['email' => 'thomas@ekdahl.no'],
                [
                    'name' => 'Thomas Ekdahl',
                    'password' => bcrypt('ballball'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info("Running simulations as user: {$user->name} (ID: {$user->id})");

        // Define the JSON files to process
        $jsonFiles = [
            'cabin.json',
            'house.json',
            'rental.json',
            'crypto.json',
            'fond.json',
        ];

        $configDir = base_path('tests/Feature/config');

        if (! File::isDirectory($configDir)) {
            $this->command->error("Config directory not found: {$configDir}");

            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($jsonFiles as $filename) {
            $filePath = $configDir.'/'.$filename;

            if (! File::exists($filePath)) {
                $this->command->warn("  ⚠️  File not found: {$filename}");

                continue;
            }

            try {
                $this->command->line("Processing: {$filename}");

                // Import the asset configuration
                $assetConfiguration = $this->importAssetConfiguration($user, $filePath, $filename);

                if (! $assetConfiguration) {
                    $this->command->warn("  ⚠️  Failed to import {$filename}");
                    $errorCount++;

                    continue;
                }

                // Run simulations with realistic scenario for three different tax countries
                $taxCountries = [
                    'no' => 'Norway',
                    'se' => 'Sweden',
                    'ch' => 'Switzerland',
                ];

                foreach ($taxCountries as $taxCode => $taxCountryName) {
                    $simulationConfig = $this->runSimulation($assetConfiguration, $user, $taxCode, $taxCountryName);

                    if ($simulationConfig) {
                        $this->command->info("  ✅ Simulation created: {$simulationConfig->name} - {$taxCountryName} (ID: {$simulationConfig->id})");
                        $successCount++;
                    } else {
                        $this->command->warn("  ⚠️  Failed to create simulation for {$filename} with {$taxCountryName} tax");
                        $errorCount++;
                    }
                }

            } catch (\Exception $e) {
                $this->command->error("  ❌ Failed to process {$filename}: {$e->getMessage()}");
                Log::error("SimulationSeeder: Failed to process {$filename}", [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
            }
        }

        $this->command->newLine();
        $this->command->info('Simulation seeding completed:');
        $this->command->info("  ✅ Successful: {$successCount}");
        if ($errorCount > 0) {
            $this->command->warn("  ⚠️  Errors: {$errorCount}");
        }

        // Show summary
        $totalSimulations = SimulationConfiguration::where('user_id', $user->id)->count();
        $this->command->info("  📈 Total simulations for user {$user->id}: {$totalSimulations}");
    }

    /**
     * Import asset configuration from JSON file
     */
    protected function importAssetConfiguration(User $user, string $filePath, string $filename): ?AssetConfiguration
    {
        // Read the JSON to get the name
        $jsonContent = File::get($filePath);
        $sanitized = $this->sanitizeJson($jsonContent);
        $data = json_decode($sanitized, true);

        if (! $data || ! isset($data['meta']['name'])) {
            $this->command->warn("  ⚠️  Invalid JSON or missing meta.name in {$filename}");

            return null;
        }

        $configName = $data['meta']['name'];

        // Check if this configuration already exists for this user
        $existingConfig = AssetConfiguration::where('user_id', $user->id)
            ->where('name', $configName)
            ->first();

        // Delete existing configuration if it exists
        if ($existingConfig) {
            $this->command->line("  🔄 Deleting existing configuration: {$configName}");

            DB::transaction(function () use ($existingConfig) {
                // Get all simulation configurations for this asset configuration
                $simulationConfigs = SimulationConfiguration::where('asset_configuration_id', $existingConfig->id)->get();

                // Delete simulation data in correct order (respecting foreign keys)
                foreach ($simulationConfigs as $simConfig) {
                    // Get all simulation asset IDs for this simulation
                    $simulationAssetIds = \App\Models\SimulationAsset::where('simulation_configuration_id', $simConfig->id)
                        ->pluck('id')
                        ->toArray();

                    // 1. Delete simulation_asset_years first
                    if (! empty($simulationAssetIds)) {
                        \App\Models\SimulationAssetYear::whereIn('asset_id', $simulationAssetIds)->delete();
                    }

                    // 2. Delete simulation_assets second
                    \App\Models\SimulationAsset::where('simulation_configuration_id', $simConfig->id)->delete();

                    // 3. Delete simulation_configuration last
                    $simConfig->delete();
                }

                // Delete related asset years
                \App\Models\AssetYear::where('asset_configuration_id', $existingConfig->id)->delete();
                // Delete assets under this configuration
                \App\Models\Asset::where('asset_configuration_id', $existingConfig->id)->delete();
                // Finally delete the configuration
                $existingConfig->delete();
            });
        }

        // Import the asset configuration
        $importService = new AssetImportService($user);
        $assetConfiguration = $importService->importFromFile($filePath);

        $assetsCount = $assetConfiguration->assets()->count();
        $yearsCount = $assetConfiguration->assets()->withCount('years')->get()->sum('years_count');

        $this->command->info("  ✅ Imported: {$assetConfiguration->name} (ID: {$assetConfiguration->id}) - {$assetsCount} assets, {$yearsCount} year entries");

        return $assetConfiguration;
    }

    /**
     * Run simulation with realistic scenario
     */
    protected function runSimulation(AssetConfiguration $assetConfiguration, User $user, string $taxCountry = 'no', string $taxCountryName = 'Norway'): ?SimulationConfiguration
    {
        try {

            // Create simulation configuration
            $simulationService = new PrognosisSimulationService;

            // Build description with tax country name
            $description = "Realistic financial simulation for all assets with {$taxCountryName} tax system";

            $simulationData = [
                'asset_configuration_id' => $assetConfiguration->id,
                'prognosis_type' => 'realistic',
                'group' => 'all', // Run for all assets
                'tax_country' => $taxCountry,
                'description' => $description,
            ];

            // Create the simulation configuration and copy assets
            $simulationConfig = $simulationService->runSimulation($simulationData);
            $simulationConfigId = $simulationConfig['simulation_configuration_id'];
            $simulationConfigModel = SimulationConfiguration::find($simulationConfigId);

            // Now run the actual prognosis calculation
            $this->command->line('  🔄 Running prognosis calculation...');

            // Prepare the config data for PrognosisService
            $configData = $this->prepareConfigData($assetConfiguration);

            // Run the prognosis calculation (PrognosisService loads tax and prognosis config from services)
            $prognosisService = app(PrognosisService::class, ['config' => $configData]);

            // Get the calculated dataH
            $dataH = $prognosisService->dataH;

            // Store the results in simulation_asset_years table
            $simulationService->storePrognosisDataH($simulationConfigModel, $dataH);

            $this->command->line('  ✅ Prognosis calculation completed and stored');

            return $simulationConfigModel;

        } catch (\Exception $e) {
            $this->command->error("  ❌ Simulation failed: {$e->getMessage()}");
            Log::error('SimulationSeeder: Simulation failed', [
                'asset_configuration_id' => $assetConfiguration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Prepare config data for PrognosisService from AssetConfiguration
     */
    protected function prepareConfigData(AssetConfiguration $assetConfiguration): array
    {
        $configData = [
            'meta' => [
                'name' => $assetConfiguration->name,
                'description' => $assetConfiguration->description,
                'birthYear' => $assetConfiguration->birth_year,
                'deathAge' => $assetConfiguration->expected_death_age, // This is already an age, not a year
                'prognoseAge' => $assetConfiguration->prognose_age,
                'pensionOfficialAge' => $assetConfiguration->pension_official_age,
                'pensionWishAge' => $assetConfiguration->pension_wish_age,
                'expectedDeathAge' => $assetConfiguration->expected_death_age,
                'exportStartAge' => $assetConfiguration->export_start_age,
            ],
        ];

        // Add assets and their years
        foreach ($assetConfiguration->assets as $asset) {
            $assetName = $asset->name;
            $configData[$assetName] = [
                'meta' => [
                    'type' => $asset->asset_type,
                    'group' => $asset->group,
                    'name' => $asset->name,
                    'description' => $asset->description,
                    'active' => $asset->is_active,
                ],
            ];

            foreach ($asset->years as $assetYear) {
                $configData[$assetName][$assetYear->year] = [
                    'description' => $assetYear->description,
                    'asset' => [
                        'marketAmount' => (float) $assetYear->asset_market_amount,
                        'acquisitionAmount' => (float) $assetYear->asset_acquisition_amount,
                        'changerate' => $assetYear->asset_changerate,
                        'repeat' => $assetYear->asset_repeat,
                    ],
                    'income' => [
                        'amount' => (float) $assetYear->income_amount,
                        'factor' => $assetYear->income_factor,
                        'changerate' => $assetYear->income_changerate,
                        'repeat' => $assetYear->income_repeat,
                    ],
                    'expence' => [
                        'amount' => (float) $assetYear->expence_amount,
                        'factor' => $assetYear->expence_factor,
                        'changerate' => $assetYear->expence_changerate,
                        'repeat' => $assetYear->expence_repeat,
                    ],
                ];

                // Add mortgage if present
                if ($assetYear->mortgage_amount > 0) {
                    $configData[$assetName][$assetYear->year]['mortgage'] = [
                        'amount' => (float) $assetYear->mortgage_amount,
                        'interest' => $assetYear->mortgage_interest,
                        'years' => (int) $assetYear->mortgage_years,
                    ];
                }
            }
        }

        return $configData;
    }

    /**
     * Sanitize JSON content
     */
    protected function sanitizeJson(string $json): string
    {
        // Remove BOM if present
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);

        // Remove comments (// and /* */)
        $json = preg_replace('~//[^\n]*~', '', $json);
        $json = preg_replace('~/\*.*?\*/~s', '', $json);

        return $json;
    }
}
