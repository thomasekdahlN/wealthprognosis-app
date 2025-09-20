<?php

namespace App\Filament\Widgets;

use App\Services\FireCalculationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FireCrossoverWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 6, 'md' => 6, 'lg' => 6, 'xl' => 6]; // Place side-by-side on md+ in one row

    protected function getStats(): array
    {
        $data = FireCalculationService::getFinancialData(app(\App\Services\CurrentAssetConfiguration::class)->id());

        if (! $data['user']) {
            return [
                Stat::make('FIRE: Crossover Point', 'Please log in')
                    ->color('warning'),
            ];
        }

        return [
            Stat::make('FIRE: Crossover Point', $data['crossoverAchieved'] ? 'Achieved!' : 'Not Yet')
                ->description($data['crossoverAchieved'] ? 'Passive income > expenses' : 'Passive income < expenses')
                ->descriptionIcon($data['crossoverAchieved'] ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($data['crossoverAchieved'] ? 'success' : 'warning'),
        ];
    }
}
