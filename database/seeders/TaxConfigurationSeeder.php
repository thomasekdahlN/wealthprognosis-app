<?php

namespace Database\Seeders;

use App\Models\TaxConfiguration;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TaxConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Idempotent load: upsert per (country, year, tax_type)
        // TaxConfiguration::truncate();

        // Load tax configurations for all countries
        $countries = ['no', 'se', 'ch'];
        $years = [2020, 2021, 2022, 2023, 2024, 2025];

        foreach ($countries as $countryCode) {
            foreach ($years as $year) {
                $this->loadTaxConfiguration($countryCode, $year);
            }
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

        // Use file system timestamps for audit fields
        $createdAt = Carbon::createFromTimestamp(@filectime($filePath) ?: @filemtime($filePath));
        $updatedAt = Carbon::createFromTimestamp(@filemtime($filePath) ?: time());

        foreach ($taxData as $assetType => $config) {
            $this->createTaxConfiguration($countryCode, $year, $assetType, $config, $createdAt, $updatedAt);
        }

        $this->command->info("Loaded tax configuration for {$countryCode} {$year}");
    }

    /**
     * @param  mixed  $config  JSON-serializable configuration (array/object/scalar)
     */
    private function createTaxConfiguration(string $countryCode, int $year, string $assetType, mixed $config, Carbon $createdAt, Carbon $updatedAt): void
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
        if (is_array($config) && (isset($config['holmestrand']) || isset($config['ringerike']))) {
            // Property tax with municipality-specific configurations
            foreach ($config as $municipality => $municipalityConfig) {
                if (is_array($municipalityConfig)) {
                    $taxTypeKey = "{$assetType}_{$municipality}";

                    // Validate referenced tax type exists to avoid FK errors
                    if (! \App\Models\TaxType::query()->where('type', $taxTypeKey)->exists()) {
                        $this->command?->warn("Skipping tax configuration '{$countryCode} {$year} {$taxTypeKey}': Unknown tax type. Add '{$taxTypeKey}' to config/tax/tax_types.json and re-seed if needed.");

                        continue;
                    }

                    $model = TaxConfiguration::updateOrCreate(
                        [
                            'country_code' => $countryCode,
                            'year' => $year,
                            'tax_type' => $taxTypeKey,
                        ],
                        [
                            'user_id' => $user->id,
                            'team_id' => $team->id,
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                            'created_checksum' => hash('sha256', 'tax_config_created_'.$countryCode.'_'.$year.'_'.$assetType.'_'.$municipality),
                            'updated_checksum' => hash('sha256', 'tax_config_updated_'.$countryCode.'_'.$year.'_'.$assetType.'_'.$municipality),
                            'is_active' => true,
                            'description' => ucfirst($municipality).' municipality',
                            'configuration' => $municipalityConfig,
                        ]
                    );

                    // Apply file timestamps to created_at/updated_at
                    $model->timestamps = false;
                    $model->created_at = $createdAt;
                    $model->updated_at = $updatedAt;
                    $model->saveQuietly();
                }
            }
        } else {
            // Standard asset configuration
            $taxTypeKey = $assetType;

            // Validate referenced tax type exists to avoid FK errors
            if (! \App\Models\TaxType::query()->where('type', $taxTypeKey)->exists()) {
                $this->command?->warn("Skipping tax configuration '{$countryCode} {$year} {$taxTypeKey}': Unknown tax type. Add '{$taxTypeKey}' to config/tax/tax_types.json and re-seed if needed.");

                return; // Skip this item gracefully
            }

            $model = TaxConfiguration::updateOrCreate(
                [
                    'country_code' => $countryCode,
                    'year' => $year,
                    'tax_type' => $taxTypeKey,
                ],
                [
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_checksum' => hash('sha256', 'tax_config_created_'.$countryCode.'_'.$year.'_'.$assetType),
                    'updated_checksum' => hash('sha256', 'tax_config_updated_'.$countryCode.'_'.$year.'_'.$assetType),
                    'is_active' => true,
                    'description' => ucfirst($assetType),
                    'configuration' => $config,
                ]
            );

            // Apply file timestamps to created_at/updated_at
            $model->timestamps = false;
            $model->created_at = $createdAt;
            $model->updated_at = $updatedAt;
            $model->saveQuietly();
        }
    }
}
