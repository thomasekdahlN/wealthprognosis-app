<?php

namespace Database\Seeders;

use App\Models\AssetConfiguration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@system.local'],
            ['name' => 'System Admin', 'password' => bcrypt('password')]
        );

        $team = Team::updateOrCreate(
            ['name' => 'Default Team'],
            ['description' => 'System default team', 'owner_id' => $user->id, 'is_active' => true]
        );

        AssetConfiguration::updateOrCreate(
            ['name' => 'Example Advanced Wealth Prognosis', 'user_id' => $user->id],
            [
                'description' => null,
                'birth_year' => 1985,
                'prognose_age' => 50,
                'pension_official_age' => 67,
                'pension_wish_age' => 63,
                'death_age' => 80,
                'export_start_age' => 2020,
                'risk_tolerance' => 'moderate_aggressive',
                'user_id' => $user->id,
                'team_id' => $team->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_checksum' => hash('sha256', 'example_wealth_prognosis_created'),
                'updated_checksum' => hash('sha256', 'example_wealth_prognosis_updated'),
            ]
        );
    }
}
