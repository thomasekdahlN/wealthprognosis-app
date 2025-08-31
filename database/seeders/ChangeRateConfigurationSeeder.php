<?php

namespace Database\Seeders;

use App\Models\PrognosisChangeRate as AssetChangeRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ChangeRateConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing change rate configurations
        AssetChangeRate::truncate();

        // Load prognosis types from database
        $scenarios = \App\Models\PrognosisType::query()->active()->pluck('code')->all();

        foreach ($scenarios as $scenario) {
            $this->loadChangeRateConfiguration($scenario);
        } // prognosis types loaded
    }

    private function loadChangeRateConfiguration(string $scenarioType): void
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
        $filePath = config_path("prognosis/{$scenarioType}.json");

        if (! File::exists($filePath)) {
            $this->command->warn("Prognosis change rate configuration file not found: {$filePath}");

            return;
        }

        $changeRateData = json_decode(File::get($filePath), true);

        if (! $changeRateData) {
            $this->command->error("Invalid JSON in prognosis change rate configuration file: {$filePath}");

            return;
        }

        foreach ($changeRateData as $assetType => $yearlyRates) {
            foreach ($yearlyRates as $year => $rate) {
                AssetChangeRate::create([
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'scenario_type' => $scenarioType,
                    'asset_type' => $assetType,
                    'year' => (int) $year,
                    'change_rate' => (float) $rate,
                    'is_active' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_checksum' => hash('sha256', "{$scenarioType}_{$assetType}_{$year}_created"),
                    'updated_checksum' => hash('sha256', "{$scenarioType}_{$assetType}_{$year}_updated"),
                ]);
            }
        }

        $this->command->info("Loaded Prognosis Change Rates for prognosis: {$scenarioType}");
    }
}
