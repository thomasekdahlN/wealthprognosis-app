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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SimulationFinancialMetricsHeatmapWidget extends BaseWidget
{
    protected static ?int $sort = 5;

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
                'simulationAssets.asset',
            ])
                ->where('user_id', auth()->id())
                ->find($simulationConfigurationId);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Financial Metrics Heatmap')
            ->description('Key financial metrics (ROI, DSCR, ROE, CoC, LTV, etc.) for all assets across all years')
            ->query(
                SimulationAssetYear::query()
                    ->when($this->simulationConfiguration, function (Builder $query) {
                        $query->whereHas('simulationAsset', function (Builder $q) {
                            $q->where('simulation_configuration_id', $this->simulationConfiguration->id);
                        });
                    })
                    ->with(['simulationAsset.asset'])
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

                TextColumn::make('metrics_roi_percent')
                    ->label('ROI %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $this->getMetricColor($state, 8, 5)),

                TextColumn::make('metrics_dscr')
                    ->label('DSCR')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $this->getDscrColor($state)),

                TextColumn::make('metrics_roe_percent')
                    ->label('ROE %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $this->getMetricColor($state, 12, 8)),

                TextColumn::make('metrics_coc_percent')
                    ->label('CoC %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $this->getMetricColor($state, 10, 6)),

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

                TextColumn::make('metrics_debt_yield_percent')
                    ->label('Debt Yield %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $this->getMetricColor($state, 10, 7)),

                TextColumn::make('metrics_equity_multiple')
                    ->label('Equity Multiple')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2).'x' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $this->getMetricColor($state, 2, 1.5)),

                TextColumn::make('yield_cap_percent')
                    ->label('Cap Rate %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $this->getMetricColor($state, 6, 4)),

                TextColumn::make('yield_net_percent')
                    ->label('Net Yield %')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).'%' : '-')
                    ->sortable()
                    ->alignRight()
                    ->toggleable()
                    ->color(fn ($state) => $this->getMetricColor($state, 5, 3)),
            ])
            ->filters([
                SelectFilter::make('asset')
                    ->label('Asset')
                    ->relationship('simulationAsset.asset', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        if (! $this->simulationConfiguration) {
                            return [];
                        }

                        $years = [];
                        foreach ($this->simulationConfiguration->simulationAssets as $asset) {
                            foreach ($asset->simulationAssetYears as $yearData) {
                                $years[$yearData->year] = $yearData->year;
                            }
                        }
                        ksort($years);

                        return $years;
                    })
                    ->searchable(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->defaultSort('year', 'asc')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50);
    }

    protected function getMetricColor(?float $value, float $goodThreshold, float $okThreshold): string
    {
        if ($value === null) {
            return 'gray';
        }

        if ($value >= $goodThreshold) {
            return 'success';
        }

        if ($value >= $okThreshold) {
            return 'warning';
        }

        return 'danger';
    }

    protected function getDscrColor(?float $value): string
    {
        if ($value === null) {
            return 'gray';
        }

        if ($value >= 1.25) {
            return 'success';
        }

        if ($value >= 1.0) {
            return 'warning';
        }

        return 'danger';
    }

    public function setSimulationConfiguration(SimulationConfiguration $simulationConfiguration): void
    {
        $this->simulationConfiguration = $simulationConfiguration;
    }
}
