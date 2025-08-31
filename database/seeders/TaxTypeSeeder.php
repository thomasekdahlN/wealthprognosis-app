<?php

namespace Database\Seeders;

use App\Models\TaxType;
use Illuminate\Database\Seeder;

class TaxTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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

        $taxTypes = [
            [
                'type' => 'income',
                'name' => 'Income Tax',
                'description' => 'Tax on regular income and salary',
                'default_rate' => 22.0000,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'type' => 'realization',
                'name' => 'Capital Gains Tax',
                'description' => 'Tax on realized capital gains from asset sales',
                'default_rate' => 37.8400,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'type' => 'fortune',
                'name' => 'Wealth Tax',
                'description' => 'Annual tax on net wealth/fortune',
                'default_rate' => 1.0000,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'type' => 'property',
                'name' => 'Property Tax',
                'description' => 'Municipal tax on real estate property',
                'default_rate' => 0.7000,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'type' => 'inheritance',
                'name' => 'Inheritance Tax',
                'description' => 'Tax on inherited assets',
                'default_rate' => 0.0000,
                'is_active' => false,
                'sort_order' => 5,
            ],
            [
                'type' => 'gift',
                'name' => 'Gift Tax',
                'description' => 'Tax on gifts and donations',
                'default_rate' => 0.0000,
                'is_active' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($taxTypes as $taxType) {
            TaxType::updateOrCreate(
                ['type' => $taxType['type']],
                $taxType + [
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_checksum' => hash('sha256', 'tax_type_created_'.$taxType['type']),
                    'updated_checksum' => hash('sha256', 'tax_type_updated_'.$taxType['type']),
                ]
            );
        }
    }
}
