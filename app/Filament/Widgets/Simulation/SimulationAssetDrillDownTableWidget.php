<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace App\Filament\Widgets\Simulation;

use App\Models\SimulationAssetYear;
use App\Models\SimulationConfiguration;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SimulationAssetDrillDownTableWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public ?SimulationConfiguration $simulationConfiguration = null;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.simulation-detailed-reporting-dashboard');
    }

    public function mount(?SimulationConfiguration $simulationConfiguration = null): void
    {
        if ($simulationConfiguration) {
            $this->simulationConfiguration = $simulationConfiguration;

            return;
        }

        $simulationConfigurationId = request()->get('simulation_configuration_id');

        if ($simulationConfigurationId) {
            $this->simulationConfiguration = SimulationConfiguration::with([
                'assetConfiguration',
                'simulationAssets.simulationAssetYears',
                'simulationAssets.asset.assetType',
            ])
                ->where('user_id', auth()->id())
                ->find($simulationConfigurationId);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Asset & Liability Drill-Down')
            ->description('Detailed view of all assets with market value, debt, equity, LTV, cash flow, and financial metrics')
            ->query(
                SimulationAssetYear::query()
                    ->when($this->simulationConfiguration, function (Builder $query) {
                        $query->whereHas('simulationAsset', function (Builder $q) {
                            $q->where('simulation_configuration_id', $this->simulationConfiguration->id);
                        });
                    })
                    ->with(['simulationAsset.asset.assetType'])
            )
            ->columns([
                TextColumn::make('simulationAsset.asset.name')
                    ->label('Asset')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->alignLeft()
                    ->toggleable(),

                TextColumn::make('asset_market_amount')
                    ->label('Market Value')
                    ->money('NOK')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('mortgage_balance_amount')
                    ->label('Debt')
                    ->money('NOK')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('asset_equity_amount')
                    ->label('Equity')
                    ->money('NOK')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('metrics_ltv_percent')
                    ->label('LTV %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state <= 70 => 'success',
                        $state <= 85 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('cashflow_after_tax_amount')
                    ->label('Cash Flow (Annual)')
                    ->money('NOK')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),

                TextColumn::make('metrics_coc_percent')
                    ->label('CoC %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('yield_cap_percent')
                    ->label('Cap Rate %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('year')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('year_from')
                                    ->label('From Year')
                                    ->numeric()
                                    ->placeholder('e.g., 2024'),
                                \Filament\Forms\Components\TextInput::make('year_to')
                                    ->label('To Year')
                                    ->numeric()
                                    ->placeholder('e.g., 2050'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year_from'],
                                fn (Builder $query, $year): Builder => $query->where('year', '>=', $year),
                            )
                            ->when(
                                $data['year_to'],
                                fn (Builder $query, $year): Builder => $query->where('year', '<=', $year),
                            );
                    }),

                SelectFilter::make('asset')
                    ->label('Asset')
                    ->relationship('simulationAsset.asset', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->defaultSort('year', 'asc')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50);
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}
