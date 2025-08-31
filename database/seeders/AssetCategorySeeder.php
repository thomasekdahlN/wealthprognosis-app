<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
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

        $categories = [
            [
                'code' => 'investment_funds',
                'name' => 'Investment Funds',
                'description' => 'Mutual funds, index funds, and other pooled investment vehicles',
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'success',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'securities',
                'name' => 'Securities',
                'description' => 'Individual stocks, bonds, options, and other tradeable securities',
                'icon' => 'heroicon-o-building-office',
                'color' => 'info',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'real_assets',
                'name' => 'Real Assets',
                'description' => 'Real estate, property, and physical assets',
                'icon' => 'heroicon-o-home',
                'color' => 'warning',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'cash_equivalents',
                'name' => 'Cash Equivalents',
                'description' => 'Cash, bank accounts, and highly liquid short-term investments',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'gray',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'alternative_investments',
                'name' => 'Alternative Investments',
                'description' => 'Commodities, precious metals, art, and other alternative assets',
                'icon' => 'heroicon-o-star',
                'color' => 'danger',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'personal_assets',
                'name' => 'Personal Assets',
                'description' => 'Vehicles, jewelry, furniture, and other personal property',
                'icon' => 'heroicon-o-truck',
                'color' => 'purple',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'code' => 'pension_retirement',
                'name' => 'Pension & Retirement',
                'description' => 'Pension plans, retirement accounts, and long-term savings',
                'icon' => 'heroicon-o-academic-cap',
                'color' => 'indigo',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'code' => 'income',
                'name' => 'Income',
                'description' => 'Salary, wages, and other income sources',
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => 'green',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'code' => 'business',
                'name' => 'Business',
                'description' => 'Business investments, partnerships, and entrepreneurial ventures',
                'icon' => 'heroicon-o-briefcase',
                'color' => 'blue',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'code' => 'insurance_protection',
                'name' => 'Insurance & Protection',
                'description' => 'Life insurance, endowments, and protective financial products',
                'icon' => 'heroicon-o-shield-check',
                'color' => 'cyan',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'debt_liabilities',
                'name' => 'Debt & Liabilities',
                'description' => 'Mortgages, loans, and other financial obligations',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'red',
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'code' => 'special',
                'name' => 'Special',
                'description' => 'Inheritance, gifts, and other special asset categories',
                'icon' => 'heroicon-o-gift',
                'color' => 'pink',
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'code' => 'reference',
                'name' => 'Reference',
                'description' => 'Benchmarks, indices, and reference assets for comparison',
                'icon' => 'heroicon-o-chart-pie',
                'color' => 'slate',
                'is_active' => true,
                'sort_order' => 13,
            ],
        ];

        foreach ($categories as $category) {
            AssetCategory::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, [
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_checksum' => hash('sha256', $category['code'].'_created'),
                    'updated_checksum' => hash('sha256', $category['code'].'_updated'),
                ])
            );
        }
    }
}
