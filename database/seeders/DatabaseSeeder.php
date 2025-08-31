<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            TeamSeeder::class,
            AssetTypeSeeder::class,
            TaxTypeSeeder::class,
            AssetCategorySeeder::class,
            AssetConfigurationSeeder::class,
            DemoAssetConfigurationSeeder::class,
            AiInstructionSeeder::class,
            TaxConfigurationSeeder::class,
            ChangeRateConfigurationSeeder::class,
            PrognosisSeeder::class,
            // SampleScenarioSeeder::class, // Skip - Scenario model doesn't exist
            JsonConfigImportSeeder::class, // Import JSON configuration files
        ]);
    }
}
