<?php

namespace App\Filament\Widgets;

use App\Services\FireCalculationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MonthlyCashflowWidget extends BaseWidget
{
    protected static ?int $sort = 1; // Row 2: Monthly Cash Flow

    public ?int $assetOwnerId = null;

    public function mount(?int $assetOwnerId = null): void
    {
        $this->assetOwnerId = $assetOwnerId ?? request()->get('asset_owner_id') ?? session('dashboard_asset_owner_id');
    }

    protected function getStats(): array
    {
        $data = FireCalculationService::getFinancialData($this->assetOwnerId);

        if (! $data['user']) {
            return [
                Stat::make('Monthly Income', 'Please log in')->color('warning'),
                Stat::make('Monthly Expenses', 'Please log in')->color('warning'),
                Stat::make('Monthly Cashflow', 'Please log in')->color('warning'),
                Stat::make('Expense Ratio', 'Please log in')->color('warning'),
            ];
        }

        $monthlyCashflow = $data['monthlyIncome'] - $data['monthlyExpenses'];
        $expenseRatio = $data['annualIncome'] > 0 ? ($data['annualExpenses'] / $data['annualIncome']) * 100 : 0;

        return [
            // Monthly Income
            Stat::make('Monthly Income', 'NOK '.number_format($data['monthlyIncome'], 0, ',', ' '))
                ->description('Annual: NOK '.number_format($data['annualIncome'], 0, ',', ' '))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            // Monthly Expenses
            Stat::make('Monthly Expenses', 'NOK '.number_format($data['monthlyExpenses'], 0, ',', ' '))
                ->description('Annual: NOK '.number_format($data['annualExpenses'], 0, ',', ' '))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($data['monthlyExpenses'] > $data['monthlyIncome'] ? 'danger' : 'success'),

            // Monthly Cashflow
            Stat::make('Monthly Cashflow', 'NOK '.number_format($monthlyCashflow, 0, ',', ' '))
                ->description('Annual: NOK '.number_format($data['theGap'], 0, ',', ' '))
                ->descriptionIcon($monthlyCashflow >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyCashflow >= 0 ? 'success' : 'danger'),

            // Expense Ratio
            Stat::make('Expense Ratio', number_format($expenseRatio, 1).'%')
                ->description('Expenses as % of income')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($expenseRatio <= 50 ? 'success' : ($expenseRatio <= 80 ? 'warning' : 'danger')),
        ];
    }
}
