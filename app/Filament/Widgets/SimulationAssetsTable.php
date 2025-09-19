<?php

namespace App\Filament\Widgets;

use App\Models\SimulationAsset;
use App\Models\SimulationConfiguration;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SimulationAssetsTable extends BaseWidget
{
    public ?SimulationConfiguration $simulationConfiguration = null;

    protected static ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                IconColumn::make('assetType.icon')
                    ->label('')
                    ->icon(fn (?string $state): string => $state && \App\Helpers\HeroiconValidator::isValid($state) ? $state : 'heroicon-o-cube')
                    ->size('lg')
                    ->color(fn ($record): string => $record->assetType?->color ?: 'gray')
                    ->width('60px')
                    ->tooltip(fn ($record): ?string => $record->assetType?->name ?? 'Asset Type'),

                TextColumn::make('name')
                    ->label('Asset Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (SimulationAsset $record): string => $record->description ?? ''),

                TextColumn::make('asset_type')
                    ->label('Type')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('tax_type')
                    ->label(__('export.tax_type'))
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(function () {
                        if (!$this->simulationConfiguration) {
                            return [];
                        }
                        return $this->simulationConfiguration->simulationAssets()
                            ->distinct()
                            ->pluck('asset_type', 'asset_type')
                            ->mapWithKeys(fn ($value, $key) => [$key => ucfirst($value)])
                            ->toArray();
                    })
                    ->multiple(),

                SelectFilter::make('group')
                    ->label('Group')
                    ->options([
                        'private' => 'Private',
                        'company' => 'Company',
                    ])
                    ->multiple(),

                SelectFilter::make('tax_type')
                    ->label(__('export.tax_type'))
                    ->options(function () {
                        if (!$this->simulationConfiguration) {
                            return [];
                        }
                        return $this->simulationConfiguration->simulationAssets()
                            ->distinct()
                            ->pluck('tax_type', 'tax_type')
                            ->mapWithKeys(fn ($value, $key) => [$key => ucfirst($value)])
                            ->toArray();
                    })
                    ->multiple(),
            ])
            ->defaultSort('sort_order')
            ->paginated([50, 100, 150])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50, 100, 150])
            ->recordUrl(fn (SimulationAsset $record): string =>
                route('filament.admin.pages.simulation-asset-years', [
                    'simulation_configuration_id' => $this->simulationConfiguration?->id,
                    'asset' => $record->id,
                ])
            );
    }

    protected function getTableQuery(): Builder
    {
        if (!$this->simulationConfiguration) {
            return SimulationAsset::query()->whereRaw('1 = 0');
        }

        return SimulationAsset::query()
            ->where('asset_configuration_id', $this->simulationConfiguration->id)
            ->with(['simulationAssetYears', 'assetType'])
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}

