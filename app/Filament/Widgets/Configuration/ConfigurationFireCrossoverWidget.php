<?php

namespace App\Filament\Widgets\Configuration;

use App\Services\CurrentAssetConfiguration;
use App\Services\FireCalculationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConfigurationFireCrossoverWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $data = FireCalculationService::getFinancialData(app(CurrentAssetConfiguration::class)->id());

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
