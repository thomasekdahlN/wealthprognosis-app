<?php

namespace App\Filament\Resources\ChangeRateConfigurations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChangeRateConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scenario_type')
                    ->label('Prognosis')
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        $p = \App\Models\PrognosisType::where('code', $state)->first();

                        return $p?->label ?? ucfirst($state);
                    })
                    ->icon(fn (string $state) => optional(\App\Models\PrognosisType::where('code', $state)->first())->icon)
                    ->color(fn (string $state) => optional(\App\Models\PrognosisType::where('code', $state)->first())->color ?? 'gray')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('scenario_type')
                    ->label('Last updated by')
                    ->formatStateUsing(fn (string $state): string => optional(\App\Models\PrognosisType::where('code', $state)->first()?->updatedBy)->name ?? '—')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('asset_type')
                    ->label('Asset Type')
                    ->badge()
                    ->formatStateUsing(function (string $state): string {
                        return \App\Models\AssetType::query()->where('type', $state)->value('name') ?? ucfirst($state);
                    })
                    ->color('primary')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->alignStart()
                    ->formatStateUsing(fn (?int $state): string => $state ? (string) $state : '')
                    ->sortable()
                    ->searchable(),

                TextInputColumn::make('change_rate')
                    ->label('Change Rate (%)')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['required', 'numeric'])
                    ->extraAttributes(\App\Helpers\AmountHelper::getRightAlignedStyle())
                    ->sortable(),

                TextInputColumn::make('description')
                    ->label('Description')
                    ->placeholder('Add description...')
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('scenario_type')
                    ->label('Prognosis Type')
                    ->options(fn () => \App\Models\PrognosisType::query()->active()->orderBy('code')->pluck('label', 'code')->all())
                    ->multiple()
                    ->preload(),

                SelectFilter::make('asset_type')
                    ->label('Asset Type')
                    ->options(\App\Models\PrognosisChangeRate::assetTypeOptions())
                    ->multiple()
                    ->preload(),

                Filter::make('year_range')
                    ->form([
                        TextInput::make('year_from')
                            ->label('From Year')
                            ->numeric()
                            ->placeholder('2020'),
                        TextInput::make('year_to')
                            ->label('To Year')
                            ->numeric()
                            ->placeholder('2030'),
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

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All records')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->filtersFormColumns(2)

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scenario_type', 'asc')
            ->defaultSort('asset_type', 'asc')
            ->defaultSort('year', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
