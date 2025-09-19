<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TeamSeeder::class,
            TaxTypesFromConfigSeeder::class, // Seed tax types first (from config)
            AssetCategorySeeder::class,      // Then categories
            AssetTypeSeeder::class,          // Then asset types (will link to tax types)
            AssetConfigurationSeeder::class,
            AiInstructionSeeder::class,
            TaxConfigurationSeeder::class,
            PrognosisSeeder::class,
            ChangeRateConfigurationSeeder::class,
            // SampleScenarioSeeder::class, // Skip - Scenario model doesn't exist
            JsonConfigImportSeeder::class,   // Import JSON configuration files
        ]);
    }
}
