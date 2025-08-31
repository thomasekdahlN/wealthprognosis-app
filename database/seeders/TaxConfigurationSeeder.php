<?php

namespace Database\Seeders;

use App\Models\TaxConfiguration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TaxConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing tax configurations
        TaxConfiguration::truncate();

        // Load Norwegian tax configurations
        foreach ([2020, 2021, 2022, 2023, 2024, 2025] as $year) {
            $this->loadTaxConfiguration('no', $year);
        }
    }

    private function loadTaxConfiguration(string $countryCode, int $year): void
    {
        $filePath = config_path("tax/{$countryCode}/{$countryCode}-tax-{$year}.json");

        if (! File::exists($filePath)) {
            $this->command->warn("Tax configuration file not found: {$filePath}");

            return;
        }

        $taxData = json_decode(File::get($filePath), true);

        if (! $taxData) {
            $this->command->error("Invalid JSON in tax configuration file: {$filePath}");

            return;
        }

        foreach ($taxData as $assetType => $config) {
            $this->createTaxConfiguration($countryCode, $year, $assetType, $config);
        }

        $this->command->info("Loaded tax configuration for {$countryCode} {$year}");
    }

    private function createTaxConfiguration(string $countryCode, int $year, string $assetType, array $config): void
    {
        // Get or create a default user for seeding
        $user = \App\Models\User::first();
        if (! $user) {
            $user = \App\Models\User::create([
                'name' => 'System Admin',
                'email' => 'admin@system.local',
                'password' => bcrypt('password'),
            ]);
        }

        // Get or create a default team
        $team = \App\Models\Team::first();
        if (! $team) {
            $team = \App\Models\Team::create([
                'name' => 'Default Team',
                'description' => 'System default team',
                'owner_id' => $user->id,
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => hash('sha256', 'default_team_created'),
                'updated_checksum' => hash('sha256', 'default_team_updated'),
            ]);
        }
        // Handle nested configurations (like property with sub-configurations)
        if (isset($config['holmestrand']) || isset($config['ringerike'])) {
            // Property tax with municipality-specific configurations
            foreach ($config as $municipality => $municipalityConfig) {
                if (is_array($municipalityConfig)) {
                    TaxConfiguration::create([
                        'user_id' => $user->id,
                        'team_id' => $team->id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'created_checksum' => hash('sha256', 'tax_config_created_'.$countryCode.'_'.$year.'_'.$assetType.'_'.$municipality),
                        'updated_checksum' => hash('sha256', 'tax_config_updated_'.$countryCode.'_'.$year.'_'.$assetType.'_'.$municipality),

                        'country_code' => $countryCode,
                        'year' => $year,
                        'tax_type' => "{$assetType}_{$municipality}",
                        'income_tax_rate' => $municipalityConfig['income'] ?? 0,
                        'realization_tax_rate' => $municipalityConfig['realization'] ?? 0,
                        'fortune_tax_rate' => $municipalityConfig['fortune'] ?? 0,
                        'standard_deduction' => $municipalityConfig['standardDeduction'] ?? 0,
                        'is_active' => true,
                        'description' => ucfirst($municipality).' municipality',
                        'configuration_data' => $municipalityConfig,
                    ]);
                }
            }
        } else {
            // Standard asset configuration
            TaxConfiguration::create([
                'user_id' => $user->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => hash('sha256', 'tax_config_created_'.$countryCode.'_'.$year.'_'.$assetType),
                'updated_checksum' => hash('sha256', 'tax_config_updated_'.$countryCode.'_'.$year.'_'.$assetType),

                'country_code' => $countryCode,
                'year' => $year,
                'tax_type' => $assetType,
                'income_tax_rate' => $config['income'] ?? 0,
                'realization_tax_rate' => $config['realization'] ?? 0,
                'fortune_tax_rate' => $config['fortune'] ?? 0,
                'standard_deduction' => $config['standardDeduction'] ?? 0,
                'is_active' => true,
                'description' => ucfirst($assetType),
                'configuration_data' => $config,
            ]);
        }
    }
}
