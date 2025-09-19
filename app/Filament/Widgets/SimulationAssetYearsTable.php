<?php

namespace App\Filament\Widgets;

use App\Models\SimulationAsset;
use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SimulationAssetYearsTable extends BaseWidget
{
    public ?SimulationConfiguration $simulationConfiguration = null;
    public ?SimulationAsset $simulationAsset = null;

    protected static ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        $confId = request()->query('simulation_configuration_id');
        $assetId = request()->query('asset');

        if ($confId) {
            $this->simulationConfiguration = SimulationConfiguration::find((int) $confId);
        }
        if ($assetId) {
            $this->simulationAsset = SimulationAsset::find((int) $assetId);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->striped()
            ->columns([
                TextColumn::make('year')
                    ->label(__('Year'))
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->size('sm')
                    ->color('primary')
                    ->width('70px'),

                TextColumn::make('age')
                    ->label(__('Age'))
                    ->size('sm')
                    ->width('60px')
                    ->getStateUsing(function ($record) {
                        $birthYear = $this->simulationConfiguration->birth_year ?? 1990;
                        return $record->year - $birthYear;
                    }),

                TextColumn::make('income_amount')
                    ->label(__('Income'))
                    ->sortable()
                    ->size('sm')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->extraAttributes(['style' => 'background-color: #90EE90; color: #000;'])
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('income_changerate')
                    ->label(__('Inc %'))
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : '0%'),

                TextColumn::make('expence_amount')
                    ->label(__('Expense'))
                    ->sortable()
                    ->size('sm')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->extraAttributes(['style' => 'background-color: #FFCCCB; color: #000;'])
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('expence_changerate')
                    ->label(__('Exp %'))
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : '0%'),

                TextColumn::make('cashflow_tax_amount')
                    ->label(__('Tax'))
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('cashflow_tax_percent')
                    ->label(__('Tax %'))
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '0%'),

                TextColumn::make('mortgage_term_amount')
                    ->label(__('Mortgage Term'))
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('mortgage_interest_percent')
                    ->label(__('Mort %'))
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : '0%'),

                TextColumn::make('mortgage_interest_amount')
                    ->label(__('Interest'))
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('mortgage_principal_amount')
                    ->label(__('Principal'))
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('mortgage_balance_amount')
                    ->label(__('Balance'))
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('asset_market_amount')
                    ->label(__('Asset Value'))
                    ->sortable()
                    ->weight('bold')
                    ->size('sm')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('asset_changerate_percent')
                    ->label(__('Asset %'))
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '0%'),

                TextColumn::make('asset_equity_amount')
                    ->label(__('Equity'))
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') : '0'),

                TextColumn::make('asset_equity_percent')
                    ->label(__('Equity %'))
                    ->sortable()
                    ->size('sm')
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '%' : '0%'),
            ])
            ->defaultSort('year')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150]);
    }

    protected function getTableQuery(): Builder
    {
        if (!$this->simulationAsset) {
            return SimulationAssetYear::query()->whereRaw('1 = 0');
        }

        return SimulationAssetYear::query()
            ->where('asset_id', $this->simulationAsset->id)
            ->orderBy('year');
    }
}

