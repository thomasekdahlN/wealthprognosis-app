<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DemoAssetConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@system.local'],
            ['name' => 'System Admin', 'password' => bcrypt('password')]
        );

        $assetTypes = \App\Models\AssetType::pluck('type')->toArray();

        for ($i = 1; $i <= 10; $i++) {
            $owner = AssetConfiguration::create([
                'name' => "Configuration {$i}",
                'description' => 'Demo configuration',
                'public' => (bool) random_int(0, 1),
                'icon' => 'heroicon-o-user-circle',
                'image' => 'database/seeders/files/seed/configurations/configuration-'.$i.'.svg',
                'color' => Arr::random(['#64748b', '#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6']),
                'tags' => ['demo', 'configuration-'.$i],
                'birth_year' => rand(1960, 1995),
                'prognose_age' => 50,
                'pension_official_age' => 67,
                'pension_wish_age' => 63,
                'death_age' => 85,
                'export_start_age' => 2020,
                'risk_tolerance' => Arr::random(['conservative', 'moderate_conservative', 'moderate', 'moderate_aggressive', 'aggressive']),
                'user_id' => $user->id,
                'team_id' => null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            for ($j = 1; $j <= 15; $j++) {
                $type = Arr::random($assetTypes);
                $asset = Asset::create([
                    'asset_configuration_id' => $owner->id,
                    'user_id' => $user->id,
                    'team_id' => null,
                    'code' => Str::slug($type)."-{$i}-{$j}",
                    'name' => ucfirst($type)." {$j}",
                    'description' => 'Demo asset',
                    'asset_type' => $type,
                    'group' => 'private',
                    'tax_type' => $type === 'salary' ? 'salary' : 'none',
                    'tax_property' => null,
                    'tax_country' => 'no',
                    'is_active' => true,
                    'sort_order' => $j,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_checksum' => hash('sha256', $type.$i.$j.'_created'),
                    'updated_checksum' => hash('sha256', $type.$i.$j.'_updated'),
                ]);

                foreach ([2023, 2024, 2025] as $year) {
                    $asset->years()->create([
                        'user_id' => $user->id,
                        'team_id' => null,
                        'asset_configuration_id' => $owner->id,
                        'year' => $year,
                        'income_description' => 'Demo income',
                        'income_amount' => rand(1000, 100000),
                        'income_factor' => 'monthly',
                        'income_changerate' => 'changerates.kpi',
                        'income_repeat' => true,
                        'expence_description' => 'Demo expense',
                        'expence_amount' => rand(1000, 50000),
                        'expence_factor' => 'monthly',
                        'expence_changerate' => 'changerates.kpi',
                        'expence_repeat' => true,
                        'asset_description' => 'Demo asset state',
                        'asset_market_amount' => rand(1000, 100000),
                        'asset_acquisition_amount' => rand(0, 100000),
                        'asset_equity_amount' => rand(0, 100000),
                        'asset_taxable_initial_amount' => rand(0, 100000),
                        'asset_paid_amount' => rand(0, 100000),
                        'asset_changerate' => 'changerates.kpi',
                        'asset_repeat' => true,
                        'mortgage_description' => 'Demo mortgage',
                        'mortgage_amount' => rand(0, 100000),
                        'mortgage_years' => rand(0, 30),
                        'mortgage_interest' => '5.00',
                        'mortgage_interest_only_years' => 0,
                        'mortgage_extra_downpayment_amount' => '0',
                        'mortgage_gebyr' => 600,
                        'mortgage_tax' => 22,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }
        }
    }
}
