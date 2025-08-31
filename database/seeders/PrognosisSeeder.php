<?php

namespace Database\Seeders;

use App\Models\PrognosisType as Prognosis;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrognosisSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'realistic', 'label' => 'Realistic', 'icon' => 'heroicon-o-check-badge', 'color' => 'success', 'description' => 'Standard market expectations based on historical data and current indicators.'],
            ['code' => 'positive', 'label' => 'Positive', 'icon' => 'heroicon-o-arrow-trending-up', 'color' => 'info', 'description' => 'Optimistic prognosis with higher growth rates and favorable conditions.'],
            ['code' => 'negative', 'label' => 'Negative', 'icon' => 'heroicon-o-arrow-trending-down', 'color' => 'danger', 'description' => 'Pessimistic prognosis with lower growth rates and challenging conditions.'],
            ['code' => 'tenpercent', 'label' => 'Ten Percent', 'icon' => 'heroicon-o-bolt', 'color' => 'warning', 'description' => 'Fixed 10% annual growth rate for comparison and testing.'],
            ['code' => 'zero', 'label' => 'Zero Growth', 'icon' => 'heroicon-o-minus', 'color' => 'gray', 'description' => 'No growth prognosis for conservative planning and worst-case analysis.'],
            ['code' => 'variable', 'label' => 'Variable', 'icon' => 'heroicon-o-adjustments-horizontal', 'color' => 'gray', 'description' => 'Uses custom variable rates from configuration.'],
        ];

        $user = User::query()->first() ?? User::factory()->create();

        foreach ($types as $i => $type) {
            Prognosis::updateOrCreate(
                ['user_id' => $user->id, 'code' => $type['code']],
                [
                    'label' => $type['label'],
                    'icon' => $type['icon'] ?? null,
                    'color' => $type['color'] ?? null,
                    'description' => $type['description'],
                    'public' => true,
                    'is_active' => true,
                    'team_id' => null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );
        }
    }
}
